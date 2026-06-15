"""Local Gate-1 readiness probe (offline build).

Checks that this machine has everything the offline WordPress builder needs BEFORE
any install/build runs. No SSH, no SFTP, no paramiko, no Hostinger ~/domains probes -
just local checks against the configured PHP, WP-CLI, MySQL, and target directory.

Checklist (OK/FAIL per item):
  1. LOCAL_PHP exists and prints a PHP version.
  2. WP_CLI_PHAR exists and `php wp-cli.phar --info` runs (WP_PATH need not exist yet).
  3. MySQL reachable via MYSQL_BIN to 127.0.0.1 (SELECT 1, --connect-timeout=5).
  4. WP_PATH parent directory is writable.

Exits non-zero if any HARD requirement fails.

Run (from wp-authority-builder/, venv active):
    python -m scripts.probe_host
"""
from __future__ import annotations

import os
import subprocess
from pathlib import Path

from scripts.config import load_config
from scripts.wp import WPClient


def _line(label: str, ok: bool, detail: str = "") -> bool:
    status = "OK  " if ok else "FAIL"
    suffix = f" - {detail}" if detail else ""
    print(f"  [{status}] {label}{suffix}")
    return ok


def _first_line(text: str) -> str:
    for ln in (text or "").splitlines():
        if ln.strip():
            return ln.strip()
    return ""


def check_php(cfg, client) -> bool:
    """1. LOCAL_PHP exists and prints a PHP version."""
    php = cfg.local_php
    if not php or not Path(php).is_file():
        return _line("PHP binary (LOCAL_PHP)", False, f"not found at {php or '(unset)'}")
    code, out, err = client._run([php, "-v"])
    version = _first_line(out) or _first_line(err)
    if code != 0 or "PHP" not in (out + err):
        return _line("PHP binary (LOCAL_PHP)", False,
                     f"`php -v` exit {code}: {(_first_line(err) or _first_line(out)) or 'no output'}")
    return _line("PHP binary (LOCAL_PHP)", True, version)


def check_wp_cli(cfg, client) -> bool:
    """2. WP_CLI_PHAR exists and WP-CLI runs (WP_PATH may not exist yet)."""
    phar = cfg.wp_cli_phar
    if not phar or not Path(phar).is_file():
        return _line("WP-CLI phar (WP_CLI_PHAR)", False, f"not found at {phar or '(unset)'}")
    # --path may not exist yet; --skip-plugins/--skip-themes keeps this from loading WP.
    code, out, err = client._run(client._wp_cmd(["--info", "--skip-plugins", "--skip-themes"]))
    if code != 0 or "WP-CLI" not in out:
        return _line("WP-CLI phar (WP_CLI_PHAR)", False,
                     f"`wp --info` exit {code}: {(_first_line(err) or _first_line(out)) or 'no output'}")
    ver = ""
    for ln in out.splitlines():
        if ln.lower().startswith("wp-cli version"):
            ver = ln.strip()
            break
    return _line("WP-CLI phar (WP_CLI_PHAR)", True, ver or "wp --info OK")


def check_mysql(cfg, client) -> bool:
    """3. MySQL reachable via MYSQL_BIN to 127.0.0.1 (SELECT 1)."""
    mysql = cfg.mysql_bin
    if not mysql or not Path(mysql).is_file():
        return _line("MySQL reachable (MYSQL_BIN)", False, f"client not found at {mysql or '(unset)'}")
    cmd = [
        mysql,
        "-h", "127.0.0.1",
        "-u", (cfg.db_user or "root"),
        "--connect-timeout=5",
        "-e", "SELECT 1;",
    ]
    # Pass the password via env (MYSQL_PWD) so it never appears in the arg list / process table.
    env = os.environ.copy()
    if cfg.db_password:
        env["MYSQL_PWD"] = cfg.db_password
    proc = subprocess.run(
        cmd, capture_output=True, text=True, encoding="utf-8", errors="replace", env=env
    )
    body = (proc.stderr or proc.stdout).strip()
    if proc.returncode != 0:
        return _line("MySQL reachable (MYSQL_BIN)", False,
                     f"connect failed (exit {proc.returncode}): {_first_line(body) or 'no output'}")
    return _line("MySQL reachable (MYSQL_BIN)", True, f"127.0.0.1 as '{cfg.db_user or 'root'}'")


def check_wp_path_parent(cfg, client) -> bool:
    """4. WP_PATH parent directory exists and is writable."""
    if not cfg.wp_path:
        return _line("WP_PATH parent writable", False, "WP_PATH is unset")
    parent = Path(cfg.wp_path).resolve().parent
    if not parent.is_dir():
        return _line("WP_PATH parent writable", False, f"parent does not exist: {parent}")
    probe = parent / ".gate1_write_probe"
    try:
        probe.write_text("ok", encoding="utf-8")
        probe.unlink()
    except OSError as e:
        return _line("WP_PATH parent writable", False, f"not writable: {parent} ({e})")
    return _line("WP_PATH parent writable", True, str(parent))


def main() -> int:
    cfg = load_config()
    client = WPClient(cfg)

    print("== Gate 1: local build readiness ==")
    print(f"  LOCAL_PHP    : {cfg.local_php or '(empty)'}")
    print(f"  WP_CLI_PHAR  : {cfg.wp_cli_phar or '(empty)'}")
    print(f"  MYSQL_BIN    : {cfg.mysql_bin or '(empty)'}")
    print(f"  WP_PATH      : {cfg.wp_path or '(empty)'}")
    print()

    results = [
        check_php(cfg, client),
        check_wp_cli(cfg, client),
        check_mysql(cfg, client),
        check_wp_path_parent(cfg, client),
    ]

    failed = results.count(False)
    print()
    if failed:
        print(f"[GATE 1: FAIL] {failed} of {len(results)} checks failed - resolve the FAIL items above.")
        return 1
    print(f"[GATE 1: OK] all {len(results)} checks passed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
