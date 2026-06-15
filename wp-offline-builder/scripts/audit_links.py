"""Link audit: report orphan posts (linked from nowhere) and broken internal links.

Run: python -m scripts.audit_links
"""
from __future__ import annotations

import json
import re

from scripts.config import load_config
from scripts.wp import WPClient


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    posts = json.loads(c.wp(["post", "list", "--post_type=post", "--post_status=publish", "--fields=ID,post_name", "--format=json"]))
    pages = json.loads(c.wp(["post", "list", "--post_type=page", "--post_status=publish", "--fields=ID,post_name", "--format=json"]))
    cats = json.loads(c.wp(["term", "list", "category", "--fields=slug", "--format=json"]))

    post_slugs = {p["post_name"] for p in posts}
    page_slugs = {p["post_name"] for p in pages}
    cat_slugs = {x["slug"] for x in cats}
    known = post_slugs | page_slugs | cat_slugs | {"", "blog", "categories"}

    linked_to = set()
    broken = {}
    for p in posts:
        content = c.wp(["post", "get", str(p["ID"]), "--field=post_content"])
        for raw in re.findall(r'href="/([^"#?]*?)/?"', content):
            target = raw.strip("/")
            if target.startswith("category/"):
                target = target.split("/", 1)[1]
            if target:
                linked_to.add(target)
            if target and target not in known:
                broken.setdefault(p["post_name"], set()).add("/" + raw.strip("/") + "/")

    orphans = sorted(post_slugs - linked_to)
    print(f"posts scanned: {len(post_slugs)}")
    print(f"orphan posts (linked from nowhere): {len(orphans)}")
    for s in orphans:
        print(f"  - {s}")
    total_broken = sum(len(v) for v in broken.values())
    print(f"\nposts with broken internal links: {len(broken)} ({total_broken} links)")
    for s, links in sorted(broken.items()):
        print(f"  - {s}: {sorted(links)}")

    c.close()
    return 0 if not broken and not orphans else 0


if __name__ == "__main__":
    raise SystemExit(main())
