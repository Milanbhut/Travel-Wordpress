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
    "elena": "elena-marsh",
    "theo": "theo-bramley",
    "rosa": "rosa-delgado",
    "arjun": "arjun-patel",
    "nina": "nina-whitlock",
}

# Beat-matched author weights per category (drives an uneven, realistic distribution).
# Each category sums to 15, so per-author totals land exactly on the brief:
# elena 22, theo 20, rosa 18, arjun 16, nina 14.
CATEGORY_AUTHOR_WEIGHTS = {
    "vegetables": {"elena": 11, "arjun": 4},
    "flowers": {"theo": 8, "nina": 7},
    "houseplants": {"theo": 8, "nina": 7},
    "soil": {"elena": 11, "rosa": 4},
    "trees": {"theo": 4, "rosa": 11},
    "seasons": {"rosa": 3, "arjun": 12},
}

# Topical cross-links between categories (reciprocal pairs).
CROSS = {
    "vegetables": "soil",
    "soil": "vegetables",
    "flowers": "houseplants",
    "houseplants": "flowers",
    "trees": "seasons",
    "seasons": "trees",
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
