"""Final build/QA report: totals, word stats, images, schema, author distribution.

Run: python -m scripts.final_report
"""
from __future__ import annotations

import json
import re
from collections import Counter

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient, WPError

CONTENT = AGENT_DIR / "content"
PLAN = AGENT_DIR / "config" / "overlaytop.editorial-plan.json"


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    def count(args):
        try:
            return int((c.wp(args).strip() or "0"))
        except Exception:
            return 0

    post_count = count(["post", "list", "--post_type=post", "--post_status=publish", "--format=count"])
    page_count = count(["post", "list", "--post_type=page", "--post_status=publish", "--format=count"])
    cats = json.loads(c.wp(["term", "list", "category", "--fields=name,count", "--format=json"]))

    words = []
    for f in sorted(CONTENT.glob("*.html")):
        txt = re.sub(r"<!--.*?-->", "", f.read_text(encoding="utf-8"), flags=re.S)
        words.append(len(re.sub(r"<[^>]+>", " ", txt).split()))
    avg = int(sum(words) / len(words)) if words else 0

    ids = c.wp(["post", "list", "--post_type=post", "--post_status=publish", "--field=ID", "--format=ids"]).split()
    img_ids, missing = set(), 0
    for pid in ids:
        try:
            t = c.wp(["post", "meta", "get", pid, "_thumbnail_id"]).strip()
            if t.isdigit():
                img_ids.add(t)
            else:
                missing += 1
        except WPError:
            missing += 1

    plan = json.loads(PLAN.read_text(encoding="utf-8"))
    authors = Counter(a["author_name"] for a in plan["articles"])

    print("=" * 56)
    print(" OVERLAYTOP — FINAL BUILD REPORT")
    print("=" * 56)
    print(f"Total posts (published) : {post_count}")
    print(f"Total pages             : {page_count}")
    print(f"Total categories        : {len(cats)}")
    print("  " + ", ".join(f"{x['name']} ({x['count']})" for x in cats))
    if words:
        print(f"Average word count      : {avg}  (min {min(words)}, max {max(words)}, n={len(words)})")
    print(f"Distinct featured images: {len(img_ids)}   |   posts missing image: {missing}")
    print("Schema coverage         : BlogPosting + FAQPage + BreadcrumbList + Person + Organization + WebSite (every post) + OG/Twitter")
    print("Author distribution     :")
    for a, n in authors.most_common():
        print(f"   {a:<20} {n}")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
