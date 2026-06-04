"""Build the header (Primary) and footer menus: one-word category labels + About/Contact in
the header, and all trust pages in the footer. Idempotent (recreates both menus cleanly).

Run: python -m scripts.build_menus
"""
from __future__ import annotations

import json

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient, WPError

BRIEF = AGENT_DIR / "config" / "overlaytop.brief.json"


def first_id(s):
    s = (s or "").strip()
    parts = s.split()
    return parts[0] if parts and parts[0].isdigit() else None


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1
    brief = json.loads(BRIEF.read_text(encoding="utf-8"))

    def wt(args):
        try:
            return c.wp(args)
        except WPError as e:
            return "ERR:" + str(e)

    home = c.wp(["option", "get", "home"]).strip()
    page_id = lambda slug: first_id(wt(["post", "list", "--post_type=page", f"--name={slug}", "--field=ID"]))
    cat_id = lambda slug: first_id(wt(["term", "list", "category", f"--slug={slug}", "--field=term_id"]))

    # Header / Primary
    wt(["menu", "delete", "Primary"])
    pm = c.wp(["menu", "create", "Primary", "--porcelain"]).strip()
    wt(["menu", "item", "add-custom", pm, "Home", home])
    for cat in brief["categories"]:
        cid = cat_id(cat["slug"])
        if cid:
            wt(["menu", "item", "add-term", pm, "category", cid, f"--title={cat['label']}"])
    for slug, label in (("about", "About"), ("contact", "Contact")):
        pid = page_id(slug)
        if pid:
            wt(["menu", "item", "add-post", pm, pid, f"--title={label}"])
    wt(["menu", "location", "assign", pm, "primary"])
    print("header menu built")

    # Footer
    wt(["menu", "delete", "Footer"])
    fm = c.wp(["menu", "create", "Footer", "--porcelain"]).strip()
    footer_pages = [
        ("about", "About"), ("contact", "Contact"), ("privacy-policy", "Privacy Policy"),
        ("terms", "Terms of Use"), ("disclaimer", "Disclaimer"),
        ("editorial-policy", "Editorial Policy"), ("affiliate-disclosure", "Affiliate Disclosure"),
    ]
    for slug, label in footer_pages:
        pid = page_id(slug)
        if pid:
            wt(["menu", "item", "add-post", fm, pid, f"--title={label}"])
    wt(["menu", "location", "assign", fm, "footer"])
    print("footer menu built")

    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
