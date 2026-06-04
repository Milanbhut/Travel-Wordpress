"""Create all 5 Overlaytop authors as WordPress users with bios, roles, and real photos.

Idempotent: skips users that already exist; skips photos already set.
Run: python -m scripts.scaffold_authors
"""
from __future__ import annotations

import json

from scripts.config import load_config, AGENT_DIR
from scripts.images import search_unsplash, trigger_unsplash_download, import_image_to_wp
from scripts.wp import WPClient, WPError

BRIEF = AGENT_DIR / "config" / "overlaytop.brief.json"

BIOS = {
    "maya-okonkwo": (
        "Maya Okonkwo spent six years as a long-haul flight attendant before trading the galley for a "
        "backpack and a notebook. She has booked her way across 40-odd countries on points, error fares, "
        "and stubborn patience, and now writes Overlaytop's flights and money-saving guides. She still "
        "gets a small thrill every time a fare drops below $300."
    ),
    "diego-alvarez": (
        "Diego Alvarez has slept in more hostels than he can count, and a few places that technically "
        "were not accommodation at all. A former tour-bus guide, he now tests budget stays and long-stay "
        "tricks across Latin America and Southern Europe, and is deeply suspicious of any room that looks "
        "too good in its photos."
    ),
    "priya-nair": (
        "Priya Nair plans trips the way other people plan expeditions: spreadsheets, contingencies, and a "
        "carry-on packed to the gram. A mother of two who refuses to check a bag, she writes Overlaytop's "
        "packing and trip-planning guides with a soft spot for anyone traveling with kids on a budget."
    ),
    "tom-whitfield": (
        "Tom Whitfield is the person who reads the baggage policy so you do not have to. A data analyst by "
        "training, he tracks fares, fees, and the apps that genuinely save money, and takes quiet "
        "satisfaction in a perfectly optimized booking."
    ),
    "sofia-marchetti": (
        "Sofia Marchetti follows her appetite. She has eaten her way through markets from Oaxaca to Hanoi "
        "on a student budget, and writes Overlaytop's food and destination guides with the conviction that "
        "the best meal of any trip usually costs the least."
    ),
}


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

    def wp_try(args):
        try:
            return c.wp(args)
        except WPError as e:
            return "ERR:" + str(e)

    for a in brief["authors"]:
        slug = a["slug"]
        name = a["name"]
        first, _, last = name.partition(" ")
        uid = first_id(wp_try(["user", "get", slug, "--field=ID"]))
        if not uid:
            uid = c.wp(
                ["user", "create", slug, f"{slug}@overlaytop.com", "--role=author",
                 f"--display_name={name}", f"--first_name={first}", f"--last_name={last}", "--porcelain"]
            ).strip()
        c.wp(["user", "update", uid, f"--description={BIOS[slug]}"])
        c.wp(["user", "meta", "update", uid, "overlaytop_role", a["role"]])

        if cfg.unsplash_key and not first_id(wp_try(["user", "meta", "get", uid, "overlaytop_avatar"])):
            ports = search_unsplash(a["photo_query"], cfg.unsplash_key, per_page=6)
            if ports:
                p = ports[0]
                att = import_image_to_wp(
                    c, cfg, p["raw"] + "&w=500&h=500&fit=crop&crop=faces&q=80&fm=jpg",
                    filename=f"author-{slug}.jpg", title=name, alt=name, max_w=500,
                )
                if att:
                    c.wp(["user", "meta", "update", uid, "overlaytop_avatar", att])
                    trigger_unsplash_download(p["download_location"], cfg.unsplash_key)
        print(f"  {name} ({slug}) -> user {uid}")

    print("all authors ready")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
