"""Fetch a path from the Hostinger origin over SSH (bypasses public DNS + Cloudflare cache).

Lets us render-check / QA the site before the domain resolves publicly.
Run: python -m scripts.fetch_origin [path] [--full]
"""
from __future__ import annotations

import re
import shlex
import sys
from urllib.parse import urlparse

from scripts.config import load_config
from scripts.wp import WPClient


def main() -> int:
    path = "/"
    full = False
    for a in sys.argv[1:]:
        if a == "--full":
            full = True
        else:
            path = a if a.startswith("/") else "/" + a

    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    host = urlparse(cfg.wp_url or "https://overlaytop.com").hostname or "overlaytop.com"
    url = f"https://{host}{path}"
    cmd = (
        f"curl -skL --max-time 25 "
        f"--resolve {host}:443:127.0.0.1 --resolve {host}:80:127.0.0.1 "
        f"{shlex.quote(url)}"
    )
    _code, html, err = c._ssh_exec(cmd)
    c.close()

    if full:
        print(html)
        return 0

    print(f"Origin fetch: {url}  ->  {len(html)} bytes")
    low = html.lower()
    flagged = False
    for marker in ("fatal error", "parse error", "warning:", "notice:", "deprecated:"):
        idx = low.find(marker)
        if idx != -1:
            print(f"  [PHP {marker}] …{html[max(0, idx - 10):idx + 160].strip()}")
            flagged = True
    if not flagged:
        print("  [PHP] no fatal/parse/warning markers in output")
    m = re.search(r"<title>(.*?)</title>", html, re.I | re.S)
    print("  <title>:", (m.group(1).strip()[:120] if m else "(none)"))
    for cls in ("site-header", "header__inner", "brand", "site-footer", "card-grid", "overlaytop-main-css"):
        print(f"  contains '{cls}':", cls in html)
    if err.strip():
        print("  [stderr]", err.strip()[:160])
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
