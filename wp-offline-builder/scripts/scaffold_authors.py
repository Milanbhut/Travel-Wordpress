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
    "elena-marsh": (
        "Elena Marsh spent fifteen years turning a patch of compacted clay into a kitchen garden that feeds "
        "her family before she founded GrowHaven. She writes the vegetable and soil guides here, convinced "
        "that almost every disappointing harvest traces back to something fixable underground. Her approach "
        "is patient and unfussy: feed the soil, water deeply, and let the plants do the rest. She would "
        "rather give you one routine you will keep than ten tricks you will forget by spring."
    ),
    "theo-bramley": (
        "Theo Bramley trained as a horticulturist and has designed everything from cottage borders to "
        "balcony jungles. He covers flowers and houseplants for GrowHaven, writing the way he plants - with "
        "an eye on the finished picture and a clear route to get there. He believes a good border is mostly "
        "planning and a little nerve, and that no room is too dark for the right plant. He is happiest when "
        "a reader sends a photo of something that finally bloomed."
    ),
    "rosa-delgado": (
        "Rosa Delgado tends a small permaculture orchard and thinks in decades, not weekends. She writes "
        "GrowHaven's tree, shrub and seasonal coverage, untangling pruning, grafting and the long rhythm of "
        "a productive plot. Her core belief: the best time to plant a tree was twenty years ago, and the "
        "second-best time is this weekend, done properly. She is allergic to shortcuts that cost you the "
        "next ten seasons."
    ),
    "arjun-patel": (
        "Arjun Patel grows more vegetables than two people can reasonably eat and tests every method on his "
        "own allotment before recommending it. He handles edible gardening and seasonal tasks for GrowHaven, "
        "turning vague advice into clear, do-it-this-weekend checklists. He is practical, faintly "
        "competitive about tomatoes, and honest when a 'must-try' technique turns out not to be worth the "
        "bother. He would rather you succeed with three crops than fail with thirty."
    ),
    "nina-whitlock": (
        "Nina Whitlock shares her home with more houseplants than she will admit to and has propagated most "
        "of them from a single cutting. She writes GrowHaven's indoor-greenery and flower coverage from the "
        "plant's point of view, decoding drooping leaves and stubborn cuttings with calm and curiosity. Her "
        "belief: a struggling plant is rarely being dramatic, it is usually telling you something about "
        "light or water you have not learned to read yet. She is gentle with nervous beginners and their "
        "overwatered ferns."
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
                ["user", "create", slug, f"{slug}@growhaven.com", "--role=author",
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
