"""Publish article(s) from content/<slug>.json files to WordPress.

Reads the editorial plan for metadata (title, author, category, image query, links) and the
generated content/<slug>.json for {html, excerpt}. Lints (word count + banned phrases),
creates the post, and attaches a deduplicated featured image. Idempotent (skips existing slugs).

Run:
  python -m scripts.publish_article <slug> [<slug> ...]
  python -m scripts.publish_article --category=flights
  python -m scripts.publish_article --all
"""
from __future__ import annotations

import json
import re
import sys

from scripts.config import load_config, AGENT_DIR
from scripts.images import search_unsplash, trigger_unsplash_download, import_image_to_wp
from scripts.wp import WPClient, WPError

PLAN = AGENT_DIR / "config" / "overlaytop.editorial-plan.json"
CONTENT = AGENT_DIR / "content"
USED_IMAGES = AGENT_DIR / "state" / "used_images.json"

BANNED = [
    "the bottom line", "in conclusion", "ultimately", "when it comes to",
    "in today's world", "needless to say", "as mentioned above",
    "unlock", "dive in", "game-changer", "navigate the world of", "in this article", "in this guide",
]


def word_count(html: str) -> int:
    return len(re.sub(r"<[^>]+>", " ", html).split())


def lint(html: str):
    issues = []
    wc = word_count(html)
    if wc < 1000:
        issues.append(f"word count {wc} < 1000")
    low = html.lower()
    for b in BANNED:
        if b in low:
            issues.append(f"banned phrase: '{b}'")
    return wc, issues


def load_used():
    if USED_IMAGES.exists():
        return set(json.loads(USED_IMAGES.read_text(encoding="utf-8")))
    return set()


def save_used(used):
    USED_IMAGES.parent.mkdir(parents=True, exist_ok=True)
    USED_IMAGES.write_text(json.dumps(sorted(used)), encoding="utf-8")


def main() -> int:
    args = sys.argv[1:]
    plan = json.loads(PLAN.read_text(encoding="utf-8"))["articles"]
    if args and args[0].startswith("--category="):
        cat = args[0].split("=", 1)[1]
        sel = [e for e in plan if e["category_slug"] == cat]
    elif args and args[0] == "--all":
        sel = plan
    elif args:
        sel = [e for e in plan if e["slug"] in args]
    else:
        print("usage: publish_article <slug...> | --category=<slug> | --all")
        return 2

    sel = [e for e in sel if (CONTENT / f"{e['slug']}.html").exists()]
    if not sel:
        print("No content/<slug>.html files for the selection.")
        return 1

    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    used = load_used()
    authors, cats = {}, {}
    published, skipped = [], []

    for e in sel:
        slug = e["slug"]
        raw = (CONTENT / f"{slug}.html").read_text(encoding="utf-8")
        m = re.search(r"<!--\s*excerpt:\s*(.*?)-->", raw, re.S | re.I)
        excerpt = m.group(1).strip() if m else ""
        html = re.sub(r"<!--\s*excerpt:.*?-->", "", raw, flags=re.S | re.I).strip()
        wc, issues = lint(html)
        if issues:
            print(f"[LINT FAIL {slug}] {issues}")
            skipped.append(slug)
            continue
        if c.wp(["post", "list", "--post_type=post", f"--name={slug}", "--post_status=any", "--field=ID"]).strip():
            print(f"[exists] {slug}")
            continue
        if e["author_slug"] not in authors:
            authors[e["author_slug"]] = c.wp(["user", "get", e["author_slug"], "--field=ID"]).strip()
        if e["category_slug"] not in cats:
            cats[e["category_slug"]] = c.wp(["term", "list", "category", f"--slug={e['category_slug']}", "--field=term_id"]).strip()

        post = c.wp([
            "post", "create", "--post_type=post", "--post_status=publish",
            f"--post_title={e['title']}", f"--post_name={slug}",
            f"--post_author={authors[e['author_slug']]}", f"--post_category={cats[e['category_slug']]}",
            f"--post_excerpt={excerpt}", f"--post_content={html}", "--porcelain",
        ]).strip()

        if cfg.unsplash_key:
            results = search_unsplash(e.get("image_query") or e["title"], cfg.unsplash_key, per_page=15)
            pick = next((r for r in results if r["id"] not in used), results[0] if results else None)
            if pick:
                att = import_image_to_wp(
                    c, cfg, pick["raw"] + "&w=1600&q=80&fit=crop&fm=jpg",
                    filename=f"{slug}.jpg", post_id=post, featured=True, title=e["title"], alt=pick["alt"],
                )
                if att:
                    used.add(pick["id"])
                    save_used(used)
                    trigger_unsplash_download(pick["download_location"], cfg.unsplash_key)
        published.append((slug, post, wc))
        print(f"[published] {slug} -> post {post} ({wc} words)")

    print(f"\npublished {len(published)}, skipped {len(skipped)}")
    if skipped:
        print("skipped (lint):", skipped)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
