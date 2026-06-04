"""Provisioning-detail probe (read-only): MySQL access, PHP 8 availability, target dir.

Determines the minimal manual steps before WordPress can be installed.
Run: python -m scripts.check_provision
"""
from __future__ import annotations

import shlex

from scripts.config import load_config
from scripts.wp import WPClient


def main() -> int:
    c = WPClient(load_config())
    info = c.verify()
    if c.channel != "wpcli":
        print("Needs the SSH/WP-CLI channel; got:", info.get("channel"))
        return 1

    p = c.cfg.wp_path
    parent = p.rsplit("/", 1)[0] if "/" in p else p

    checks = {
        "MySQL access (SELECT 1, 5s timeout)": "mysql --connect-timeout=5 -e 'SELECT 1;' 2>&1 | head -3",
        "MySQL list databases": "mysql --connect-timeout=5 -e 'SHOW DATABASES;' 2>&1 | head -12",
        "~/.my.cnf present?": "test -f ~/.my.cnf && echo 'yes (-> mysql may auto-auth)' || echo 'no'",
        "domain dir": f"ls -la {shlex.quote(parent)} 2>&1 | head -20",
        "public_html contents": f"ls -A {shlex.quote(p)} 2>&1 | head -20",
        "PHP 8.1": "/opt/alt/php81/usr/bin/php -v 2>&1 | head -1",
        "PHP 8.2": "/opt/alt/php82/usr/bin/php -v 2>&1 | head -1",
        "PHP 8.3": "/opt/alt/php83/usr/bin/php -v 2>&1 | head -1",
        "wp version": "wp --version 2>&1 | head -1",
    }

    for label, cmd in checks.items():
        _code, out, err = c._ssh_exec(cmd)
        body = (out or err).strip() or "(no output)"
        print(f"--- {label} ---")
        for ln in body.splitlines()[:20]:
            print("  " + ln)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
