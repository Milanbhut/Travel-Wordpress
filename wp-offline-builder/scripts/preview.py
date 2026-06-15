"""Fetch a rendered page from the LOCAL site and save its HTML for screenshots/QA.

For a localhost WordPress install the page is directly reachable over HTTP, so this
just GETs cfg.wp_url + <path> with `requests` and writes preview/<name>.html.
Run: python -m scripts.preview <path> <name>
"""
from __future__ import annotations

import sys
from pathlib import Path

import requests

from scripts.config import load_config, AGENT_DIR

PREVIEW_DIR = AGENT_DIR / "preview"


def main() -> int:
    path = sys.argv[1] if len(sys.argv) > 1 else "/"
    name = sys.argv[2] if len(sys.argv) > 2 else "home"
    cfg = load_config()

    base = (cfg.wp_url or "http://localhost").rstrip("/")
    url = base + (path if path.startswith("/") else "/" + path)

    resp = requests.get(url, timeout=30)

    PREVIEW_DIR.mkdir(parents=True, exist_ok=True)
    out = PREVIEW_DIR / f"{name}.html"
    out.write_text(resp.text, encoding="utf-8")

    print(f"HTTP {resp.status_code} {url}")
    print(str(out.resolve()))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
