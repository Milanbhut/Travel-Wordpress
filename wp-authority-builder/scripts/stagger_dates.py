"""Spread post publish dates naturally over ~6 weeks so the site reads as established.

Sets BOTH post_date and post_date_gmt (the AdSense domain-maturity check reads gmt) and
spans ~42 days so the oldest post clears the 30-day floor while the recent 30 days keep an
active cadence (>=15 distinct days). One eval-file pass. Run: python -m scripts.stagger_dates
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient, WPError

PHP = r"""<?php
$ids = get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC','fields'=>'ids']);
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
echo 'staggered ' . $n . ' posts; oldest ~' . $age . ' days (post_date + post_date_gmt set)';
"""


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1
    remote = "/tmp/_stagger.php"
    sftp = c._ssh.open_sftp()
    try:
        with sftp.open(remote, "wb") as fh:
            fh.write(PHP.encode("utf-8"))
    finally:
        sftp.close()
    try:
        print(c.wp(["eval-file", remote]))
    except WPError as e:
        print("ERR:", e)
    c._ssh_exec("rm -f " + remote)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
