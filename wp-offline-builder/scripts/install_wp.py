"""Provision a fresh LOCAL WordPress under XAMPP via WP-CLI.

Unlike the remote agent (which configures an *existing* WordPress over SSH/WP-CLI),
this script stands up a brand-new WordPress install on THIS machine, served by XAMPP's
Apache + MySQL.

PREREQUISITES (must be true before running):
  * XAMPP Apache AND MySQL are running (start them from the XAMPP control panel).
  * wp-cli.phar exists at cfg.wp_cli_phar (default: <agent>/bin/wp-cli.phar).
  * cfg.local_php points at XAMPP's php.exe (default: C:\\xampp\\php\\php.exe).
  * cfg.wp_path is the target install dir, typically under C:\\xampp\\htdocs\\<slug>.

Flow:
  1. ensure cfg.wp_path exists
  2. wp core download  (skipped if wp-load.php already present)
  3. wp config create  (--dbhost=127.0.0.1 --force)
  4. create the database (wp db create, falling back to mysql.exe)
  5. wp core install
  6. print success + the http://localhost/<slug> URL

NOTE on channel ordering: WPClient.verify() (which sets c.channel) only succeeds once
WordPress is actually installed, so it CANNOT gate the pre-install steps below. We therefore
invoke WP-CLI directly through the local PHP + wp-cli.phar paths using c._run(c._wp_cmd([...])),
which shells out via cfg.local_php / cfg.wp_cli_phar without requiring a verified channel.

Run: python -m scripts.install_wp ["Site Title"]
"""
from __future__ import annotations

import subprocess
import sys
from pathlib import Path

from scripts.config import load_config
from scripts.wp import WPClient

DEFAULT_TITLE = "Offline Site"


def _show(label: str, code: int, out: str, err: str) -> None:
    """Print the final non-empty line of a WP-CLI / subprocess result."""
    lines = (out or err).strip().splitlines()
    tail = lines[-1] if lines else "(done)"
    print(f"  [{ 'ok' if code == 0 else 'exit %d' % code }] {label}: {tail}")


def main() -> int:
    cfg = load_config()
    if not (cfg.db_name and cfg.db_user is not None):
        print("Missing DB_NAME / DB_USER in secrets.env.")
        return 2

    title = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_TITLE

    # The XAMPP DB lives on the local MySQL; address it as 127.0.0.1 so PHP uses TCP
    # rather than a (Windows-absent) unix socket.
    db_host = "127.0.0.1"

    c = WPClient(cfg)

    # --- (1) ensure the install directory exists -------------------------------------
    wp_path = Path(cfg.wp_path)
    if not cfg.wp_path:
        print("WP_PATH is not set in secrets.env.")
        return 2
    wp_path.mkdir(parents=True, exist_ok=True)
    print(f"install dir : {wp_path}")

    # --- (2) download WordPress core (skip if already present) ------------------------
    if (wp_path / "wp-load.php").exists():
        print("  [ok] core download: wp-load.php already present, skipping")
    else:
        print("downloading WordPress core (latest, en_US)...")
        code, out, err = c._run(c._wp_cmd(["core", "download", "--locale=en_US"]))
        _show("core download", code, out, err)
        if code != 0:
            print("\n[FAIL] core download failed; see output above.")
            return 1

    # --- (3) create wp-config.php -----------------------------------------------------
    print("creating wp-config.php...")
    code, out, err = c._run(c._wp_cmd([
        "config", "create",
        f"--dbname={cfg.db_name}",
        f"--dbuser={cfg.db_user}",
        f"--dbpass={cfg.db_password or ''}",
        f"--dbhost={db_host}",
        "--force",
    ]))
    _show("config create", code, out, err)
    if code != 0:
        print("\n[FAIL] config create failed; see output above.")
        return 1

    # --- (4) create the database ------------------------------------------------------
    # Preferred path: WP-CLI's `db create` (reads creds from the wp-config we just wrote).
    # We invoke it via _run rather than c.wp([...]) because the 'wpcli' channel is only
    # verified post-install; _run shells out through the cfg PHP/phar paths regardless.
    print("creating database...")
    code, out, err = c._run(c._wp_cmd(["db", "create"]))
    if code == 0:
        _show("db create (wp-cli)", code, out, err)
    else:
        # Fallback: talk to MySQL directly via XAMPP's mysql.exe. CREATE IF NOT EXISTS
        # keeps this idempotent. Omit -p entirely when the DB password is empty
        # (the XAMPP default root has no password).
        print("  wp db create failed; falling back to mysql.exe ...")
        mysql_cmd = [
            cfg.mysql_bin,
            "-u", cfg.db_user,
            "--host=127.0.0.1",
            "--connect-timeout=5",
        ]
        if cfg.db_password:
            mysql_cmd.append(f"--password={cfg.db_password}")
        mysql_cmd += ["-e", f"CREATE DATABASE IF NOT EXISTS {cfg.db_name}"]
        proc = subprocess.run(mysql_cmd, capture_output=True, text=True,
                              encoding="utf-8", errors="replace")
        _show("db create (mysql)", proc.returncode, proc.stdout or "", proc.stderr or "")
        if proc.returncode != 0:
            print("\n[FAIL] could not create the database; is XAMPP MySQL running?")
            return 1

    # --- (5) install WordPress --------------------------------------------------------
    admin_user = cfg.wp_admin_user or "admin"
    admin_pw = cfg.wp_admin_password or "admin"
    url = cfg.wp_url or f"http://localhost/{cfg.site_slug}"
    print("running core install...")
    code, out, err = c._run(c._wp_cmd([
        "core", "install",
        f"--url={url}",
        f"--title={title}",
        f"--admin_user={admin_user}",
        f"--admin_password={admin_pw}",
        f"--admin_email={cfg.wp_admin_email}",
        "--skip-email",
    ]))
    _show("core install", code, out, err)
    if code != 0:
        print("\n[FAIL] core install failed; is XAMPP MySQL running and the DB reachable?")
        return 1

    # --- (6) success ------------------------------------------------------------------
    site_url = f"http://localhost/{cfg.site_slug}"
    print(f"\n[OK] WordPress '{title}' installed at {wp_path}")
    print(f"     site       : {site_url}")
    print(f"     login      : {site_url}/wp-admin/")
    print(f"     admin user : {admin_user}")
    print(f"     admin pass : {admin_pw}")
    print(f"     admin email: {cfg.wp_admin_email}")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
