"""Step-1 pre-install check: confirm XAMPP MySQL is reachable and report DB state.

LOCAL (offline) build version: talks to XAMPP's MySQL directly via cfg.mysql_bin over
TCP to 127.0.0.1 (no SSH/SFTP). Confirms the server answers, then reports whether the
target database (cfg.db_name) already exists and - if so - whether it is empty (ready for
a fresh install) or already populated.

Mirrors install_wp.py's mysql.exe fallback: connects with cfg.db_user and omits the
--password flag entirely when cfg.db_password is empty (the XAMPP default root has no
password). Uses --connect-timeout=5 so an unreachable server fails fast.

Run: python -m scripts.check_db
"""
from __future__ import annotations

import subprocess

from scripts.config import load_config


def _mysql_base(cfg) -> list[str]:
    """Common mysql.exe argv prefix: user + TCP host + timeout (+ password if set)."""
    cmd = [
        cfg.mysql_bin,
        "-u", cfg.db_user or "root",
        "--host=127.0.0.1",
        "--connect-timeout=5",
    ]
    # Omit --password entirely when empty (matches install_wp.py's fallback path).
    if cfg.db_password:
        cmd.append(f"--password={cfg.db_password}")
    return cmd


def _run(cmd: list[str]) -> tuple[int, str, str]:
    proc = subprocess.run(cmd, capture_output=True, text=True,
                          encoding="utf-8", errors="replace")
    return proc.returncode, proc.stdout or "", proc.stderr or ""


def main() -> int:
    cfg = load_config()

    if not cfg.db_name:
        print("[FAIL] DB_NAME is not set in secrets.env.")
        return 2

    base = _mysql_base(cfg)

    # --- (1) is XAMPP MySQL reachable? ----------------------------------------------
    code, out, err = _run(base + ["-e", "SELECT 1;"])
    if code != 0:
        body = (err or out).strip()
        print(f"[FAIL] cannot reach MySQL at 127.0.0.1 via {cfg.mysql_bin}")
        print(f"       {body[:200]}")
        print("       Is XAMPP MySQL running? Check DB_USER / DB_PASSWORD in secrets.env.")
        return 1
    print(f"[ok] MySQL reachable at 127.0.0.1 as user '{cfg.db_user or 'root'}'")

    # --- (2) does the target database exist? ----------------------------------------
    code, out, err = _run(base + ["-N", "-e", "SHOW DATABASES;"])
    if code != 0:
        body = (err or out).strip()
        print(f"[FAIL] connected, but SHOW DATABASES failed: {body[:200]}")
        return 1
    databases = {line.strip() for line in out.splitlines() if line.strip()}

    if cfg.db_name not in databases:
        print(f"[ok] database '{cfg.db_name}' does not exist yet "
              f"(install_wp will create it).")
        print(f"\nRESULT PASS: MySQL reachable, '{cfg.db_name}' ready to be created.")
        return 0

    # --- (3) database exists - empty (ready) or already populated? -------------------
    code, out, err = _run(base + ["-N", "-e", "SHOW TABLES;", cfg.db_name])
    if code != 0:
        body = (err or out).strip()
        print(f"[FAIL] database '{cfg.db_name}' exists but SHOW TABLES failed: {body[:200]}")
        return 1
    tables = [line.strip() for line in out.splitlines() if line.strip()]

    if not tables:
        print(f"[ok] database '{cfg.db_name}' exists and is EMPTY (ready for install).")
        print(f"\nRESULT PASS: MySQL reachable, '{cfg.db_name}' exists and is empty.")
    else:
        print(f"[warn] database '{cfg.db_name}' exists and already has "
              f"{len(tables)} table(s); a fresh install may clobber it.")
        print(f"\nRESULT PASS: MySQL reachable, '{cfg.db_name}' exists "
              f"(already populated - {len(tables)} table(s)).")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
