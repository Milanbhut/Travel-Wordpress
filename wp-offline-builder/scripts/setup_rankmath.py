"""Configure Rank Math SEO for the site (run after the plugin is installed + active).

Sets knowledge graph (Organization), default Article/BlogPosting schema, homepage meta,
breadcrumbs, sitemap; assigns a per-post focus keyword derived from each slug; points
robots.txt at Rank Math's sitemap. Run: python -m scripts.setup_rankmath
"""
from __future__ import annotations

import json

from scripts.config import load_config
from scripts.wp import WPClient, WPError

PHP_CONFIG = r"""<?php
$name = get_bloginfo('name');
$desc = get_bloginfo('description');
$logo_id = (int) get_theme_mod('custom_logo');
if (!$logo_id) { $logo_id = (int) get_option('site_icon'); }
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';

$t = get_option('rank-math-options-titles', array()); if (!is_array($t)) { $t = array(); }
$t['knowledgegraph_type'] = 'company';
$t['knowledgegraph_name'] = $name;
if ($logo_url) { $t['knowledgegraph_logo'] = $logo_url; $t['knowledgegraph_logo_id'] = $logo_id; }
$t['pt_post_default_rich_snippet'] = 'article';
$t['pt_post_default_article_type'] = 'BlogPosting';
$t['pt_post_default_snippet_name'] = '%title%';
$t['pt_post_default_snippet_desc'] = '%excerpt%';
$t['homepage_title'] = '%sitename% %sep% %sitedesc%';
$t['homepage_description'] = $desc;
$t['disable_date_archives'] = 'on';
update_option('rank-math-options-titles', $t);

$g = get_option('rank-math-options-general', array()); if (!is_array($g)) { $g = array(); }
$g['breadcrumbs'] = 'on';
$g['breadcrumbs_home'] = 'on';
$g['breadcrumbs_separator'] = '/';
update_option('rank-math-options-general', $g);

$s = get_option('rank-math-options-sitemap', array()); if (!is_array($s)) { $s = array(); }
$s['items_per_page'] = '200';
$s['include_images'] = 'on';
$s['pt_post_sitemap'] = 'on';
$s['pt_page_sitemap'] = 'on';
$s['tax_category_sitemap'] = 'on';
update_option('rank-math-options-sitemap', $s);

update_option('rank_math_wizard_completed', true);
update_option('rank_math_registration_skip', true);
flush_rewrite_rules(false);
echo 'RANKMATH_OK name=' . $name . ' logo=' . ($logo_url ? 'yes' : 'no') . "\n";
"""

STOP = set(
    "how to vs explained case what a an the your for and of on in is you it that my i "
    "really actually do does into when where".split()
)


def focus_kw(slug: str) -> str:
    words = [w for w in slug.split("-") if w and w not in STOP and not w.isdigit()]
    return " ".join(words[:5])


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    # 1. Apply settings via eval-file (merges into Rank Math option arrays).
    try:
        print(c.eval_php(PHP_CONFIG))
    except WPError as e:
        print("eval-file error:", e)

    # 2. Per-post focus keywords from slugs.
    posts = json.loads(c.wp(["post", "list", "--post_type=post", "--post_status=publish", "--fields=ID,post_name", "--format=json"]))
    n = 0
    for p in posts:
        kw = focus_kw(p["post_name"])
        if kw:
            try:
                c.wp(["post", "meta", "update", str(p["ID"]), "rank_math_focus_keyword", kw])
                n += 1
            except WPError:
                pass
    print(f"focus keywords set on {n}/{len(posts)} posts")

    # 3. Point robots.txt at Rank Math's sitemap.
    host = (cfg.wp_url or "https://jobskhojo.com").rstrip("/")
    robots = (
        "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\n"
        "User-agent: Mediapartners-Google\nAllow: /\n\n"
        f"Sitemap: {host}/sitemap_index.xml\n"
    )
    c.put_bytes("robots.txt", robots.encode("utf-8"))
    c.wp(["rewrite", "flush", "--hard"])
    print("robots.txt -> /sitemap_index.xml; rank math configured")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
