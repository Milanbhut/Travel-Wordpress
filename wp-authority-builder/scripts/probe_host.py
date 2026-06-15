"""Read-only host probe (Gate 1 + provisioning detection).

Reports which secrets.env fields are set (secret values are MASKED, never printed),
verifies the control channel, and - over WP-CLI - detects WordPress / database state
WITHOUT changing anything on the server.

Run (from wp-authority-builder/, venv active):
    python -m scripts.probe_host
"""
from __future__ import annotations

import shlex

from scripts.config import load_config
from scripts.wp import WPClient, WPError


def _set(v) -> str:
    return "SET" if v else "(empty)"


def main() -> int:
    cfg = load_config()

    print("== secrets.env fields (secrets masked) ==")
    print(f"  SSH_HOST            : {cfg.ssh_host or '(empty)'}")
    print(f"  SSH_PORT            : {cfg.ssh_port}")
    print(f"  SSH_USER            : {cfg.ssh_user or '(empty)'}")
    print(f"  SSH_KEY             : {cfg.ssh_key or '(empty)'}")
    print(f"  SSH_PASS            : {_set(cfg.ssh_pass)}")
    print(f"  WP_PATH             : {cfg.wp_path or '(empty)'}")
    print(f"  WP_URL              : {cfg.wp_url or '(empty)'}")
    print(f"  WP_ADMIN_USER       : {cfg.wp_admin_user or '(empty)'}")
    print(f"  WP_APP_PASSWORD     : {_set(cfg.wp_app_password)}")
    print(f"  UNSPLASH_ACCESS_KEY : {_set(cfg.unsplash_key)}")
    print(f"  PEXELS_API_KEY      : {_set(cfg.pexels_key)}")
    print(f"  ADSENSE_PUB_ID      : {cfg.adsense_pub_id or '(empty)'}")
    print(f"  CLOUDFLARE_API_TOKEN: {_set(getattr(cfg, 'cloudflare_api_token', None))}")
    print(f"  CF_ZONE_ID          : {getattr(cfg, 'cf_zone_id', None) or '(empty)'}")

    try:
        cfg.validate()
    except Exception as e:
        print(f"\n[CONFIG] {e}")
        return 2

    client = WPClient(cfg)
    try:
        info = client.verify()
    except WPError as e:
        print(f"\n[GATE 1: FAIL] {e}")
        return 1
    except Exception as e:  # paramiko auth/network errors
        print(f"\n[GATE 1: SSH ERROR] {type(e).__name__}: {e}")
        print("  -> Likely wrong SSH_HOST/SSH_PORT/SSH_USER, wrong password/key, custom port, or firewall.")
        return 1

    print(f"\n[GATE 1: OK] control channel = {info['channel']}")
    if info["channel"] != "wpcli":
        print("  REST fallback active; namespaces:", info.get("namespaces"))
        client.close()
        return 0

    def sh(command: str):
        return client._ssh_exec(command)

    wp = client._wp_prefix()
    print("\n== host capabilities (read-only) ==")
    for line in (info.get("info") or "").splitlines()[:6]:
        if line.strip():
            print("  " + line.strip())

    # WordPress install state at WP_PATH
    _c, out, _e = sh(f"{wp} core is-installed; echo EXIT:$?")
    installed = "EXIT:0" in out
    print(f"\n  WordPress installed at WP_PATH : {'YES' if installed else 'NO'}")
    if installed:
        _c, ver, _e = sh(f"{wp} core version")
        _c, surl, _e = sh(f"{wp} option get siteurl")
        print(f"    version : {ver.strip()}")
        print(f"    siteurl : {surl.strip()}")
    elif cfg.wp_path:
        _c, listing, _e = sh(f"ls -A {shlex.quote(cfg.wp_path)} 2>&1 | head -30")
        body = listing.strip() or "(empty / path not found)"
        print("    WP_PATH contents:")
        for ln in body.splitlines():
            print("      " + ln)

    # Database-creation capability
    _c, mysql_bin, _e = sh("command -v mysql || echo MISSING")
    _c, mysqladmin_bin, _e = sh("command -v mysqladmin || echo MISSING")
    print(f"\n  mysql client     : {'present' if 'MISSING' not in mysql_bin else 'not found'}")
    print(f"  mysqladmin       : {'present' if 'MISSING' not in mysqladmin_bin else 'not found'}")

    # Hostinger account structure
    _c, home, _e = sh("echo $HOME")
    _c, domains, _e = sh("ls -1 ~/domains 2>/dev/null")
    print(f"  $HOME            : {home.strip()}")
    print(f"  ~/domains        : {', '.join(domains.split()) if domains.strip() else '(none / not present)'}")

    client.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
