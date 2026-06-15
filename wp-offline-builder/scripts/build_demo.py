"""Scaffold the site identity + taxonomy + primary menu (no sample content).

Sets blogname/description from the brief, pretty permalinks, creates the 6 categories,
points the default category at a real category, removes Uncategorized, and builds a
one-word primary menu. Authors come from scaffold_authors; posts from publish_article.
Idempotent where practical. Run: python -m scripts.build_demo
"""
from __future__ import annotations

import json

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient, WPError

BRIEF = AGENT_DIR / "config" / "overlaytop.brief.json"

DEFAULT_CATEGORY = "vegetables"  # a real category the brief defines


def wp_try(c, args):
    try:
        return c.wp(args)
    except WPError as e:
        return "ERR:" + str(e)


def first_id(s):
    s = (s or "").strip()
    return s if s.isdigit() else None


def main() -> int:
    cfg = load_config()
    brief = json.loads(BRIEF.read_text(encoding="utf-8"))
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    # 1. Identity + permalinks
    c.wp(["option", "update", "blogname", brief["brand"]["name"]])
    c.wp(["option", "update", "blogdescription", brief["brand"]["tagline"]])
    c.wp(["rewrite", "structure", "/%postname%/", "--hard"])
    c.wp(["rewrite", "flush", "--hard"])
    home = c.wp(["option", "get", "home"]).strip()
    print("identity + permalinks set; home =", home)

    # 2. Categories
    cat_ids = {}
    for cat in brief["categories"]:
        existing = first_id(wp_try(c, ["term", "list", "category", f"--slug={cat['slug']}", "--field=term_id"]))
        if existing:
            cat_ids[cat["slug"]] = existing
        else:
            cat_ids[cat["slug"]] = c.wp(
                ["term", "create", "category", cat["title"], f"--slug={cat['slug']}", f"--description={cat['description']}", "--porcelain"]
            ).strip()
    print("categories:", cat_ids)

    # 3. Default category -> a real category; remove Uncategorized
    if cat_ids.get(DEFAULT_CATEGORY):
        c.wp(["option", "update", "default_category", cat_ids[DEFAULT_CATEGORY]])
    unc = first_id(wp_try(c, ["term", "list", "category", "--slug=uncategorized", "--field=term_id"]))
    if unc:
        wp_try(c, ["term", "delete", "category", unc])

    # 4. Primary menu with ONE-WORD labels
    wp_try(c, ["menu", "delete", "Primary"])
    menu_id = c.wp(["menu", "create", "Primary", "--porcelain"]).strip()
    wp_try(c, ["menu", "item", "add-custom", menu_id, "Home", home])
    for cat in brief["categories"]:
        wp_try(c, ["menu", "item", "add-term", menu_id, "category", cat_ids[cat["slug"]], f"--title={cat['label']}"])
    wp_try(c, ["menu", "location", "assign", menu_id, "primary"])
    print("primary menu built + assigned")

    print("HOME URL:", home)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
