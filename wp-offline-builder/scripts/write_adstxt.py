"""Write a placeholder physical ads.txt to the site root (editable later via hPanel File
Manager or by re-running with a real publisher line). Run: python -m scripts.write_adstxt
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient

CONTENT = (
    "# ads.txt for Overlaytop\n"
    "# Replace the line below with your Google AdSense publisher line once approved.\n"
    "# Edit anytime via Hostinger hPanel -> File Manager -> public_html -> ads.txt\n"
    "# google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0\n"
)


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    c.put_bytes("ads.txt", CONTENT.encode("utf-8"))
    print("wrote", c.fs_path("ads.txt"))
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
