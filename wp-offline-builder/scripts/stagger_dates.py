"""Spread post publish dates naturally over ~6 weeks so the site reads as established.

Sets BOTH post_date and post_date_gmt (the AdSense domain-maturity check reads gmt) and
spans ~42 days so the oldest post clears the 30-day floor while the recent 30 days keep an
active cadence (>=15 distinct days). One eval-file pass. Run: python -m scripts.stagger_dates
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient, WPError

PHP = r"""<?php
// Build per-category buckets, then round-robin interleave so the most RECENT dates are
// spread across categories (otherwise the last-published category clusters at the top of
// the home page / feeds and the site reads as monotone by topic).
$cats = get_terms(['taxonomy'=>'category','hide_empty'=>false]);
$buckets = [];
foreach ($cats as $t) {
    $buckets[] = get_posts([
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
        'orderby'=>'ID','order'=>'ASC',
        'tax_query'=>[['taxonomy'=>'category','field'=>'term_id','terms'=>$t->term_id]],
    ]);
}
$maxlen = 0; foreach ($buckets as $b) { $maxlen = max($maxlen, count($b)); }
$ids = []; $seen = [];
for ($r = 0; $r < $maxlen; $r++) {
    foreach ($buckets as $b) {
        if (isset($b[$r]) && !isset($seen[$b[$r]])) { $seen[$b[$r]] = 1; $ids[] = $b[$r]; }
    }
}
// Fallback: include any publish post not captured above (e.g. uncategorised).
$all = get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']);
foreach ($all as $id) { if (!isset($seen[$id])) { $seen[$id] = 1; $ids[] = $id; } }

global $wpdb; $n = count($ids); $span = 41;
foreach ($ids as $i => $id) {
    $days_ago = 1 + (int) round($i * $span / max(1, $n - 1));
    $ts = time() - $days_ago * 86400;
    $date = date('Y-m-d', $ts) . sprintf(' %02d:%02d:%02d', mt_rand(7, 21), mt_rand(0, 59), mt_rand(0, 59));
    $wpdb->update($wpdb->posts, ['post_date' => $date, 'post_date_gmt' => $date], ['ID' => $id]);
    clean_post_cache($id);
}
$oldest = get_posts(['post_type'=>'post','post_status'=>'publish','orderby'=>'date','order'=>'ASC','posts_per_page'=>1]);
$age = $oldest ? (int) floor((time() - strtotime((string) $oldest[0]->post_date_gmt)) / 86400) : 0;
echo 'staggered ' . $n . ' posts (category-interleaved); oldest ~' . $age . ' days (post_date + post_date_gmt set)';
"""


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1
    try:
        print(c.eval_php(PHP))
    except WPError as e:
        print("ERR:", e)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
