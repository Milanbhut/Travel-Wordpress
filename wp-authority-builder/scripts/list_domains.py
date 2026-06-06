"""List ~/domains with public_html file counts, to find empty (safe-to-use) domains.

Run: python -m scripts.list_domains
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient

CMD = (
    'for d in "$HOME"/domains/*/; do '
    'n=$(ls -A "${d}public_html" 2>/dev/null | wc -l); '
    'echo "$n $(basename "$d")"; '
    'done | sort -n'
)


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1
    _code, out, err = c._ssh_exec(CMD)
    print("files  domain")
    print((out or err).strip())
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
