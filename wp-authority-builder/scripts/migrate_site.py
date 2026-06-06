"""Migrate the built WordPress site from one domain's docroot to another on the SAME Hostinger
account: backs up the target, copies files server-side, reuses the source database, and
search-replaces the domain across all tables.

Usage: python -m scripts.migrate_site <source_domain> <target_domain>
e.g.   python -m scripts.migrate_site overlaytop.com jobskhojo.com
"""
from __future__ import annotations

import sys
import time

from scripts.config import load_config
from scripts.wp import WPClient


def main() -> int:
    if len(sys.argv) < 3:
        print("usage: python -m scripts.migrate_site <source_domain> <target_domain>")
        return 2
    src_dom, dst_dom = sys.argv[1], sys.argv[2]

    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    home = c._ssh_exec("echo $HOME")[1].strip()
    src = f"{home}/domains/{src_dom}/public_html"
    dst = f"{home}/domains/{dst_dom}/public_html"
    wp = f"{cfg.wp_cli_php} $(command -v wp)" if cfg.wp_cli_php else "wp"

    def run(cmd):
        code, out, err = c._ssh_exec(cmd)
        body = (out or err).strip()
        print(f"$ {cmd}\n  {body[:600]}")
        return code, body

    # Sanity checks
    _, ok = run(f"{wp} core is-installed --path={src} && echo SRC-OK || echo SRC-MISSING")
    if "SRC-OK" not in ok:
        print("Source is not a WordPress install; aborting.")
        return 1
    run(f"test -d {dst} && echo DST-OK || echo DST-MISSING")

    ts = time.strftime("%Y%m%d-%H%M%S")
    # 1. Back up the target docroot
    run(f"mkdir -p {home}/backups && tar czf {home}/backups/{dst_dom}-{ts}.tgz -C {dst} . 2>/dev/null; echo backed_up:{home}/backups/{dst_dom}-{ts}.tgz")
    # 2. Clear the target docroot (contents only, keep the dir)
    run(f"find {dst} -mindepth 1 -maxdepth 1 -exec rm -rf {{}} +; echo cleared")
    # 3. Copy the source site in (including dotfiles like .htaccess)
    run(f"cp -a {src}/. {dst}/; echo copied")
    # 4. Rewrite the domain across the (shared) database
    run(f"{wp} search-replace {src_dom} {dst_dom} --path={dst} --all-tables --skip-columns=guid --report-changed-only")
    # 5. Flush permalinks
    run(f"{wp} rewrite flush --path={dst} --hard")
    # 6. Verify
    run(f"{wp} option get home --path={dst}")
    run(f"{wp} option get siteurl --path={dst}")
    run(f"{wp} post list --post_type=post --post_status=publish --format=count --path={dst}")
    print(f"\nNEW WP_PATH={dst}")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
