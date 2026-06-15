"""Strip long dashes from the site. HARD RULE: a generated website must NEVER show
an em dash. LLM-written content leaks them despite instructions, so this
deterministic pass is the backstop, not a nicety. Replaces U+2014 (em dash),
U+2013 (en dash) and U+2015 (horizontal bar) with a plain hyphen, leaving the
surrounding spacing intact ("a - b" stays "a - b"; "a-b" stays "a-b").

Run:
  python -m scripts.scrub_dashes           # sanitize the LIVE WordPress DB
                                            # (post content/excerpt/title, term descriptions, options + theme mods)
  python -m scripts.scrub_dashes --files    # scrub this agent's source text files
                                            # (theme templates, bundled content, references, scripts)
"""
from __future__ import annotations

import sys

from scripts.config import AGENT_DIR, load_config
from scripts.wp import WPClient

LONG_DASHES = (chr(0x2014), chr(0x2013), chr(0x2015))  # em, en, horizontal bar via code points (no literal long dash in this file)
TEXT_EXT = {".php", ".css", ".js", ".html", ".htm", ".md", ".json", ".py", ".txt", ".ps1", ".xml"}
SKIP_DIRS = {".git", ".venv", "__pycache__", ".pytest_cache", "preview", "node_modules"}


def _scrub(text: str) -> str:
    for dash in LONG_DASHES:
        text = text.replace(dash, "-")
    return text


def scrub_files() -> int:
    """Replace long dashes in every source text file under the agent dir."""
    changed = 0
    for path in AGENT_DIR.rglob("*"):
        if not path.is_file() or path.suffix.lower() not in TEXT_EXT:
            continue
        if any(part in SKIP_DIRS for part in path.relative_to(AGENT_DIR).parts):
            continue
        try:
            original = path.read_text(encoding="utf-8")
        except (UnicodeDecodeError, OSError):
            continue
        scrubbed = _scrub(original)
        if scrubbed != original:
            path.write_text(scrubbed, encoding="utf-8")
            changed += 1
            print(f"  scrubbed {path.relative_to(AGENT_DIR)}")
    print(f"done: scrubbed long dashes from {changed} file(s) under {AGENT_DIR}.")
    return 0


def scrub_wp() -> int:
    """Replace long dashes across the live WordPress database via WP-CLI search-replace."""
    client = WPClient(load_config())
    client.verify()
    for dash in LONG_DASHES:
        out = client.wp([
            "search-replace", dash, "-",
            "--precise", "--skip-columns=guid", "--report-changes-only",
        ])
        print(out.strip() or f"(no occurrences of U+{ord(dash):04X})")
    client.close()
    print("done: live WordPress sanitized (posts, terms, options/theme-mods).")
    return 0


def main() -> int:
    if "--files" in sys.argv[1:]:
        return scrub_files()
    return scrub_wp()


if __name__ == "__main__":
    raise SystemExit(main())
