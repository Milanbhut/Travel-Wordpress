"""Verify DB credentials over SSH and report DB state (read-only).

Tries the configured DB user; if access is denied, also tries the prefixed/unprefixed
variant (Hostinger prefixes names with the account id). Prints which user connected
and the databases/tables visible - revealing the real DB name.

Run: python -m scripts.check_db
"""
from __future__ import annotations

import shlex

from scripts.config import load_config
from scripts.wp import WPClient


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs SSH/WP-CLI channel.")
        return 1

    cfg = c.cfg
    pw = cfg.db_password or ""
    host = cfg.db_host or "localhost"
    ssh_user = cfg.ssh_user or ""

    def variants(value: str):
        out = [value]
        if value and ssh_user:
            pref = f"{ssh_user}_"
            out.append(value[len(pref):] if value.startswith(pref) else pref + value)
        return [v for v in out if v]

    connected_user = None
    for u in variants(cfg.db_user or ""):
        cmd = (f"MYSQL_PWD={shlex.quote(pw)} mysql -u{shlex.quote(u)} "
               f"-h{shlex.quote(host)} --connect-timeout=8 -e 'SELECT 1;' 2>&1")
        _code, out, err = c._ssh_exec(cmd)
        body = (out or err).strip()
        ok = ("ERROR" not in body) and ("denied" not in body.lower())
        print(f"db user '{u}': {'OK' if ok else body[:140]}")
        if ok:
            connected_user = u
            break

    if not connected_user:
        print("\n-> Could not connect with any user variant. Check DB user/password.")
        c.close()
        return 1

    base = (f"MYSQL_PWD={shlex.quote(pw)} mysql -u{shlex.quote(connected_user)} "
            f"-h{shlex.quote(host)} --connect-timeout=8")
    _c, out, err = c._ssh_exec(f"{base} -e 'SHOW DATABASES;' 2>&1")
    dbs = [d for d in (out or err).split() if d != "Database"]
    print(f"\nvisible databases: {dbs}")

    for name in variants(cfg.db_name or ""):
        if name in dbs:
            _c, out, err = c._ssh_exec(f"{base} {shlex.quote(name)} -e 'SHOW TABLES;' 2>&1")
            t = (out or err).strip()
            print(f"  DB '{name}': {'EMPTY (ready for install)' if not t else t[:200]}")

    print(f"\nRESULT connected_user={connected_user}")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
