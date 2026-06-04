"""Turn the raw 90-title plan into the final editorial plan.

Assigns authors (uneven, beat-matched distribution via largest-remainder) and builds the
internal-link graph (2 same-category siblings + 1 cross-category, guaranteeing no orphans).
Writes config/overlaytop.editorial-plan.json. Run: python -m scripts.build_editorial_plan
"""
from __future__ import annotations

import json
from collections import defaultdict

from scripts.config import AGENT_DIR

RAW = AGENT_DIR / "config" / "overlaytop.plan-raw.json"
BRIEF = AGENT_DIR / "config" / "overlaytop.brief.json"
OUT = AGENT_DIR / "config" / "overlaytop.editorial-plan.json"

AUTHORS = {
    "maya": "maya-okonkwo",
    "diego": "diego-alvarez",
    "priya": "priya-nair",
    "tom": "tom-whitfield",
    "sofia": "sofia-marchetti",
}

# Beat-matched author weights per category (drives an uneven, realistic distribution).
CATEGORY_AUTHOR_WEIGHTS = {
    "flights": {"maya": 3, "tom": 1},
    "stays": {"diego": 3, "sofia": 1},
    "destinations": {"maya": 1, "diego": 1, "priya": 1, "sofia": 1},
    "hacks": {"maya": 2, "priya": 1, "tom": 1},
    "packing": {"priya": 1, "tom": 1},
    "food": {"sofia": 3, "diego": 1},
}

# Topical cross-links between categories.
CROSS = {
    "flights": "hacks",
    "hacks": "flights",
    "stays": "destinations",
    "destinations": "stays",
    "packing": "flights",
    "food": "destinations",
}


def hamilton(total, weights):
    s = sum(weights.values())
    raw = {k: total * w / s for k, w in weights.items()}
    base = {k: int(raw[k]) for k in weights}
    rem = total - sum(base.values())
    order = sorted(weights, key=lambda k: raw[k] - base[k], reverse=True)
    for i in range(rem):
        base[order[i % len(order)]] += 1
    return base


def interleave_authors(counts):
    counts = dict(counts)
    order = sorted(counts, key=lambda k: counts[k], reverse=True)
    seq = []
    while sum(counts.values()) > 0:
        for k in order:
            if counts[k] > 0:
                seq.append(k)
                counts[k] -= 1
    return seq


def main() -> int:
    raw = json.loads(RAW.read_text(encoding="utf-8"))
    brief = json.loads(BRIEF.read_text(encoding="utf-8"))
    cat_title = {c["slug"]: c["title"] for c in brief["categories"]}
    author_name = {a["slug"]: a["name"] for a in brief["authors"]}

    cats = {c["slug"]: c["articles"] for c in raw["categories"]}
    assert len(cats) == 6, f"expected 6 categories, got {len(cats)}"
    all_slugs = []
    for slug, arts in cats.items():
        assert len(arts) == 15, f"{slug} has {len(arts)} articles (expected 15)"
        all_slugs += [a["slug"] for a in arts]
    assert len(all_slugs) == len(set(all_slugs)) == 90, "slug count/uniqueness failed"

    plan = []
    author_totals = defaultdict(int)
    archetype_totals = defaultdict(int)
    for cslug, arts in cats.items():
        counts = hamilton(15, CATEGORY_AUTHOR_WEIGHTS[cslug])
        author_seq = interleave_authors(counts)
        cross_cat = CROSS[cslug]
        for i, art in enumerate(arts):
            aslug = AUTHORS[author_seq[i]]
            links = [
                arts[(i + 1) % 15]["slug"],
                arts[(i + 2) % 15]["slug"],
                cats[cross_cat][i]["slug"],
            ]
            plan.append(
                {
                    "slug": art["slug"],
                    "title": art["title"],
                    "archetype": art["archetype"],
                    "category_slug": cslug,
                    "category_title": cat_title[cslug],
                    "author_slug": aslug,
                    "author_name": author_name[aslug],
                    "image_query": art["image_query"],
                    "angle": art["angle"],
                    "internal_links": links,
                    "word_min": 1000,
                    "word_target": 1200,
                    "status": "planned",
                }
            )
            author_totals[aslug] += 1
            archetype_totals[art["archetype"]] += 1

    out = {
        "domain": brief["domain"],
        "niche": brief["niche"],
        "total": len(plan),
        "author_totals": dict(author_totals),
        "archetype_totals": dict(archetype_totals),
        "articles": plan,
    }
    OUT.write_text(json.dumps(out, indent=2, ensure_ascii=False), encoding="utf-8")

    print(f"wrote {OUT.name}: {len(plan)} articles")
    print("author_totals:", dict(author_totals))
    print("archetype_totals:", dict(archetype_totals))
    linked = set()
    for a in plan:
        linked.update(a["internal_links"])
    orphans = [a["slug"] for a in plan if a["slug"] not in linked]
    print("orphans:", orphans or "none")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
