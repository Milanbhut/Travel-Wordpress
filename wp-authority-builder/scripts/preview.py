"""Build a local, browsable copy of a rendered page for screenshots/QA (works pre-DNS).

Fetches the page from the origin (over SSH), rewrites theme asset URLs to the local repo
theme files, downloads uploaded media via SFTP, and writes preview/<name>.html.
Run: python -m scripts.preview <path> <name>
"""
from __future__ import annotations

import posixpath
import re
import shlex
import sys
from pathlib import Path
from urllib.parse import urlparse

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient

PREVIEW_DIR = AGENT_DIR / "preview"
THEME_DIR = AGENT_DIR / "assets" / "master-theme" / "overlaytop"


def _fileurl(p: Path) -> str:
    return "file:///" + str(p.resolve()).replace("\\", "/")


def main() -> int:
    path = sys.argv[1] if len(sys.argv) > 1 else "/"
    name = sys.argv[2] if len(sys.argv) > 2 else "home"
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    host = urlparse(cfg.wp_url or "https://overlaytop.com").hostname or "overlaytop.com"

    url = f"https://{host}{path if path.startswith('/') else '/' + path}"
    cmd = f"curl -skL --max-time 25 --resolve {host}:443:127.0.0.1 --resolve {host}:80:127.0.0.1 {shlex.quote(url)}"
    _code, html, _err = c._ssh_exec(cmd)

    PREVIEW_DIR.mkdir(parents=True, exist_ok=True)

    # 1. Theme assets -> local repo files (strip ?ver=).
    theme_base = f"https://{host}/wp-content/themes/overlaytop/"
    html = re.sub(
        re.escape(theme_base) + r'([^"\'\s)]+)',
        lambda m: _fileurl(THEME_DIR / m.group(1).split("?")[0]),
        html,
    )

    # 2. Uploaded media -> SFTP download + local file URLs.
    sftp = c._ssh.open_sftp()
    uploads_base = f"https://{host}/wp-content/uploads/"
    seen: dict[str, str] = {}

    def upload_rewrite(m):
        rel = m.group(1).split("?")[0]
        if rel in seen:
            return seen[rel]
        remote = posixpath.join(cfg.wp_path, "wp-content", "uploads", rel)
        local = PREVIEW_DIR / "uploads" / rel
        local.parent.mkdir(parents=True, exist_ok=True)
        try:
            sftp.get(remote, str(local))
            fu = _fileurl(local)
        except Exception:
            fu = m.group(0)
        seen[rel] = fu
        return fu

    html = re.sub(re.escape(uploads_base) + r'([^"\'\s)]+)', upload_rewrite, html)
    sftp.close()
    c.close()

    out = PREVIEW_DIR / f"{name}.html"
    out.write_text(html, encoding="utf-8")
    print(_fileurl(out))
    print(f"({len(seen)} media files localized)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
