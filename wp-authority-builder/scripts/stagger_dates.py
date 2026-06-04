"""Spread post publish dates naturally over the past ~3 weeks (a few per day) so the site
reads like it was published on a normal cadence rather than dumped at once.

Run: python -m scripts.stagger_dates
"""
from __future__ import annotations

import datetime
import random

from scripts.config import load_config
from scripts.wp import WPClient


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    ids = c.wp(["post", "list", "--post_type=post", "--post_status=publish", "--field=ID", "--format=ids"]).split()
    random.shuffle(ids)
    now = datetime.datetime.now()

    updated = 0
    for pid in ids:
        days_ago = random.randint(1, 21)
        when = (now - datetime.timedelta(days=days_ago)).replace(
            hour=random.randint(7, 22), minute=random.randint(0, 59), second=random.randint(0, 59)
        )
        try:
            c.wp(["post", "update", pid, f"--post_date={when:%Y-%m-%d %H:%M:%S}"])
            updated += 1
        except Exception as e:
            print(f"  {pid}: {e}")
    print(f"staggered dates on {updated} posts across the last 21 days")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
