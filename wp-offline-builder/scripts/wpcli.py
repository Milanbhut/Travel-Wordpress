"""Run an arbitrary WP-CLI command over SSH (PHP 8.2). For diagnostics/ad-hoc ops.

Run: python -m scripts.wpcli post list --post_type=post --field=ID
"""
from __future__ import annotations

import sys

from scripts.config import load_config
from scripts.wp import WPClient, WPError


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1
    try:
        print(c.wp(sys.argv[1:]))
    except WPError as e:
        print("ERR:", e)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
