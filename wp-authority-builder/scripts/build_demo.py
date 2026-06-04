"""Gate G1: scaffold the site + publish ONE fully-formed sample article.

Sets identity/permalinks, creates the 6 categories + a one-word primary menu, creates
author Maya (with a real Unsplash portrait), and publishes a complete sample post with a
real featured image. Idempotent where practical. Run: python -m scripts.build_demo
"""
from __future__ import annotations

import json

from scripts.config import load_config, AGENT_DIR
from scripts.images import search_unsplash, trigger_unsplash_download, import_image_to_wp
from scripts.wp import WPClient, WPError

BRIEF = AGENT_DIR / "config" / "overlaytop.brief.json"

MAYA_BIO = (
    "Maya Okonkwo spent six years as a long-haul flight attendant before trading the galley for a "
    "backpack and a notebook. She has booked her way across 40-odd countries on points, error fares, "
    "and stubborn patience, and now writes Overlaytop's flights and money-saving guides. She still "
    "gets a small thrill every time a fare drops below $300."
)

ART_TITLE = "How to Travel Europe on $50 a Day (Without Missing the Good Stuff)"
ART_SLUG = "europe-on-50-a-day"
ART_EXCERPT = (
    "Fifty dollars a day in Europe sounds impossible until you break it down. Here is the exact mix of "
    "trains, hostels, and street food that makes it work — with real numbers."
)
ART_HTML = """<p>The first time I tried to &ldquo;do Europe on a budget,&rdquo; I came home with three espresso receipts, a sunburn, and a credit-card bill that made my stomach drop. I had spent nearly $140 a day without once feeling like I was being extravagant. So I did what any slightly stubborn person does: I figured out exactly where the money went, then rebuilt the whole trip from the ground up.</p>
<p>These days I move through Europe on about $50 a day and rarely feel like I'm missing out. Here is how the math actually works.</p>
<h2>Start with the number, not the destination</h2>
<p>Most people pick a city first and let the costs happen to them. Flip it around. Decide your daily number, then choose the places and habits that fit inside it.</p>
<p>For $50 a day across most of Central and Eastern Europe, a realistic split looks like this:</p>
<div class="callout"><strong>The $50-a-day breakdown</strong><p>Bed: $18 &middot; Food: $15 &middot; Transport: $9 &middot; Sights &amp; fun: $8. It flexes by country &mdash; Portugal and Poland stretch further than France &mdash; but the shape stays the same.</p></div>
<p>Western Europe pushes that closer to $65&ndash;75 a day. Pick your regions with the number in mind and it does a lot of the work for you.</p>
<h2>Sleep cheap, but sleep well</h2>
<p>Accommodation is where budgets quietly explode, so it is the first thing to fix. A bed in a well-reviewed hostel dorm runs $12&ndash;22 in most of the continent, and the good ones are clean, social, and central.</p>
<p>Two habits keep costs down without turning the trip into an endurance test:</p>
<ul>
<li>Book private hostel rooms when you are splitting with someone &mdash; often cheaper per person than a hotel, with the same door that locks.</li>
<li>Use overnight trains or buses for long hops, so you pay for the journey and the bed at the same time.</li>
</ul>
<div class="tip-box"><strong>A small trick that saves real money</strong><p>Filter hostels by &ldquo;rating 8+&rdquo; and read the three most recent reviews, not the average score. A place that was great in 2019 and neglected since will show up in the new reviews first.</p></div>
<h2>Move slow and book the right transport</h2>
<p>The cheapest trip is rarely the fastest one, and that is fine. Slowing down means fewer paid transfers, fewer &ldquo;I am exhausted, let us just grab a taxi&rdquo; moments, and more time to enjoy a place you already paid to reach.</p>
<p>Here is how the main options stack up for a typical four-to-six-hour hop:</p>
<div class="table-scroll"><table><thead><tr><th>Option</th><th>Typical cost</th><th>Best for</th></tr></thead><tbody>
<tr><td>Budget flight</td><td>$25&ndash;60</td><td>Long distances, booked 3+ weeks out</td></tr>
<tr><td>Intercity bus</td><td>$10&ndash;30</td><td>Tight budgets and flexible timing</td></tr>
<tr><td>Regional train</td><td>$20&ndash;45</td><td>Comfort, city-center to city-center</td></tr>
<tr><td>Overnight train</td><td>$35&ndash;70</td><td>Saving a night of accommodation</td></tr>
</tbody></table></div>
<p>Book buses and trains a couple of weeks ahead and the cheap seats are usually still there. Wait until the platform and you pay the &ldquo;I have no choice&rdquo; price.</p>
<h2>Eat like a local, not a tourist</h2>
<p>Food is the budget line that is easiest to enjoy cutting. The pattern that works almost everywhere: a bakery or market breakfast, a proper sit-down lunch when the menu of the day is cheapest, and a light, self-assembled dinner from a grocery store.</p>
<p>One picnic dinner on a riverbank with bread, cheese, fruit, and a $3 bottle of something local has beaten more expensive restaurant meals than I can count.</p>
<h2>The free stuff is often the best stuff</h2>
<p>Nearly every European city runs tip-based walking tours, and they are a genuinely good way to get your bearings on day one. Many museums keep a free evening or first-Sunday slot. Parks, churches, viewpoints, and markets cost nothing and tend to be where a city actually lives.</p>
<p>Pay for the one or two things you will remember forever, and skip the dozen you booked out of mild guilt.</p>
<h2>A realistic five-day example</h2>
<p>Here is a recent stretch through Poland and Czechia, rounded to the nearest dollar: five hostel nights ($95), one overnight train ($48), local transport ($31), food ($78), and three paid sights plus a couple of tours ($40). That is $292 for five days &mdash; about $58 a day, in a region I would call mid-priced.</p>
<p>Shift the same plan to Portugal or the Balkans and you slide comfortably under $50. Move it to Switzerland and no spreadsheet on earth will save you.</p>
<h2>Common questions</h2>
<div class="faq">
<div class="faq__item"><p class="faq__q">Is $50 a day realistic in summer?</p><div class="faq__a"><p>In shoulder season, easily. In peak July and August it is tighter because beds cost more, so lean toward cheaper regions or book your accommodation earlier than feels necessary.</p></div></div>
<div class="faq__item"><p class="faq__q">Do I need a rail pass?</p><div class="faq__a"><p>Usually not. Point-to-point tickets booked ahead beat a pass for most budget trips. A pass only wins if you are moving almost every day across expensive countries.</p></div></div>
<div class="faq__item"><p class="faq__q">What wrecks a travel budget fastest?</p><div class="faq__a"><p>Last-minute decisions. Every unplanned taxi, same-day train, and &ldquo;we are too tired to cook&rdquo; dinner chips away at the number. A loose plan protects your wallet more than any single discount.</p></div></div>
</div>
<p>None of this requires suffering. It just asks you to decide what you actually care about, spend there, and quietly trim everywhere else. Do that, and Europe stops being a place you save up for and starts being a place you can simply go.</p>"""


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

    # 3. Default category -> hacks; remove Uncategorized
    if cat_ids.get("hacks"):
        c.wp(["option", "update", "default_category", cat_ids["hacks"]])
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

    # 5. Author Maya (+ real portrait)
    maya = first_id(wp_try(c, ["user", "get", "maya-okonkwo", "--field=ID"]))
    if not maya:
        maya = c.wp(
            ["user", "create", "maya-okonkwo", "maya@overlaytop.com", "--role=author",
             "--display_name=Maya Okonkwo", "--first_name=Maya", "--last_name=Okonkwo", "--porcelain"]
        ).strip()
    c.wp(["user", "update", maya, f"--description={MAYA_BIO}"])
    c.wp(["user", "meta", "update", maya, "overlaytop_role", "Founder & Lead Writer"])
    av_set = first_id(wp_try(c, ["user", "meta", "get", maya, "overlaytop_avatar"]))
    if cfg.unsplash_key and not av_set:
        ports = search_unsplash(brief["authors"][0]["photo_query"], cfg.unsplash_key, per_page=6)
        if ports:
            p = ports[0]
            att = import_image_to_wp(
                c, cfg, p["raw"] + "&w=500&h=500&fit=crop&crop=faces&q=80&fm=jpg",
                filename="author-maya-okonkwo.jpg", title="Maya Okonkwo", alt="Maya Okonkwo", max_w=500,
            )
            if att:
                c.wp(["user", "meta", "update", maya, "overlaytop_avatar", att])
                trigger_unsplash_download(p["download_location"], cfg.unsplash_key)
    print("author Maya ready; id =", maya)

    # 6. Sample post (+ real featured image)
    post = first_id(wp_try(c, ["post", "list", "--post_type=post", f"--name={ART_SLUG}", "--post_status=any", "--field=ID"]))
    if not post:
        post = c.wp(
            ["post", "create", "--post_type=post", "--post_status=publish",
             f"--post_title={ART_TITLE}", f"--post_name={ART_SLUG}", f"--post_author={maya}",
             f"--post_category={cat_ids['hacks']}", f"--post_excerpt={ART_EXCERPT}",
             f"--post_content={ART_HTML}", "--porcelain"]
        ).strip()
    thumb_set = first_id(wp_try(c, ["post", "meta", "get", post, "_thumbnail_id"]))
    if cfg.unsplash_key and not thumb_set:
        imgs = search_unsplash("europe travel train station backpacker", cfg.unsplash_key, per_page=8)
        if imgs:
            im = imgs[0]
            att = import_image_to_wp(
                c, cfg, im["raw"] + "&w=1600&q=80&fit=crop&fm=jpg",
                filename="europe-on-50-a-day.jpg", post_id=post, featured=True, title=ART_TITLE, alt=im["alt"],
            )
            if att:
                trigger_unsplash_download(im["download_location"], cfg.unsplash_key)
    purl = c.wp(["post", "list", "--post_type=post", f"--include={post}", "--field=url"]).strip()
    print("sample post id =", post)
    print("POST URL:", purl)
    print("HOME URL:", home)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
