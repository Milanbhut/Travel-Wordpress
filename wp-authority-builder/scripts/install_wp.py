"""Install WordPress at WP_PATH over SSH/WP-CLI.

Idempotent: if WordPress is already installed, it reports and exits 0.
Steps: resolve wp-cli (prefer PHP 8.2) -> core download -> config create -> core install.
Generates a strong admin password and persists WP_ADMIN_USER / WP_ADMIN_PASSWORD to
secrets.env so the same credentials are reused on re-runs.

Run: python -m scripts.install_wp
"""
from __future__ import annotations

import secrets as pysecrets
import shlex
import string

from scripts.config import load_config, CONFIG_DIR
from scripts.wp import WPClient

ADMIN_EMAIL = "maradmusalo1122@gmail.com"
SITE_TITLE = "Overlaytop"  # temporary; replaced by the brand brief during scaffolding


def _gen_password(n: int = 20) -> str:
    alphabet = string.ascii_letters + string.digits + "!@#%^*-_=+"
    return "".join(pysecrets.choice(alphabet) for _ in range(n))


def _set_env_var(key: str, value: str) -> None:
    """Replace or append KEY='value' in secrets.env (single-quoted -> literal)."""
    envf = CONFIG_DIR / "secrets.env"
    lines = envf.read_text(encoding="utf-8").splitlines() if envf.exists() else []
    newline = f"{key}='{value}'"
    out, found = [], False
    for ln in lines:
        if ln.lstrip().startswith(f"{key}="):
            out.append(newline)
            found = True
        else:
            out.append(ln)
    if not found:
        out.append(newline)
    envf.write_text("\n".join(out) + "\n", encoding="utf-8")


def _last(out: str, err: str) -> str:
    lines = (out or err).strip().splitlines()
    return lines[-1] if lines else "(done)"


def main() -> int:
    cfg = load_config()
    if not (cfg.db_name and cfg.db_user and cfg.db_password):
        print("Missing DB_NAME / DB_USER / DB_PASSWORD in secrets.env.")
        return 2

    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("WordPress install requires the SSH/WP-CLI channel.")
        return 1

    # Resolve the wp-cli command, preferring PHP 8.2
    _c, wpbin, _e = c._ssh_exec("command -v wp")
    wpbin = wpbin.strip()
    php = cfg.wp_cli_php or ""
    wpcmd = "wp"
    if php and wpbin:
        cand = f"{shlex.quote(php)} {shlex.quote(wpbin)}"
        _c, out, _e = c._ssh_exec(f"{cand} --info 2>&1")
        if "WP-CLI" in out:
            wpcmd = cand
    base = f"{wpcmd} --path={shlex.quote(cfg.wp_path)}"
    print(f"wp command : {wpcmd}")

    # Idempotency: already installed?
    _c, out, _e = c._ssh_exec(f"{base} core is-installed; echo EXIT:$?")
    if "EXIT:0" in out:
        _c, ver, _e = c._ssh_exec(f"{base} core version")
        _c, surl, _e = c._ssh_exec(f"{base} option get siteurl")
        print(f"[OK] WordPress already installed (v{ver.strip()}, {surl.strip()}).")
        c.close()
        return 0

    # 1. Download core
    print("downloading WordPress core (latest, en_US)...")
    _c, out, err = c._ssh_exec(f"{base} core download --locale=en_US 2>&1")
    print("  " + _last(out, err))

    # 2. Create wp-config.php
    print("creating wp-config.php...")
    cfg_cmd = (f"{base} config create"
               f" --dbname={shlex.quote(cfg.db_name)}"
               f" --dbuser={shlex.quote(cfg.db_user)}"
               f" --dbpass={shlex.quote(cfg.db_password)}"
               f" --dbhost={shlex.quote(cfg.db_host)}"
               f" --skip-check 2>&1")
    _c, out, err = c._ssh_exec(cfg_cmd)
    print("  " + _last(out, err))

    # 3. core install
    admin_user = cfg.wp_admin_user if (cfg.wp_admin_user and cfg.wp_admin_user != "admin") else "overlaytop_admin"
    admin_pw = cfg.wp_admin_password or _gen_password()
    _set_env_var("WP_ADMIN_USER", admin_user)
    _set_env_var("WP_ADMIN_PASSWORD", admin_pw)
    url = cfg.wp_url or "https://overlaytop.com"
    print("running core install...")
    inst = (f"{base} core install"
            f" --url={shlex.quote(url)}"
            f" --title={shlex.quote(SITE_TITLE)}"
            f" --admin_user={shlex.quote(admin_user)}"
            f" --admin_password={shlex.quote(admin_pw)}"
            f" --admin_email={shlex.quote(ADMIN_EMAIL)}"
            f" --skip-email 2>&1")
    _c, out, err = c._ssh_exec(inst)
    print("  " + _last(out, err))

    # Verify
    _c, isinst, _e = c._ssh_exec(f"{base} core is-installed; echo EXIT:$?")
    if "EXIT:0" not in isinst:
        print("\n[FAIL] WordPress still not installed; see output above.")
        c.close()
        return 1
    _c, ver, _e = c._ssh_exec(f"{base} core version")
    print(f"\n[OK] WordPress {ver.strip()} installed at {cfg.wp_path}")
    print(f"     login : {url}/wp-admin/")
    print(f"     admin user : {admin_user}")
    print(f"     admin email: {ADMIN_EMAIL}")
    print("     admin pass : saved in secrets.env as WP_ADMIN_PASSWORD")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
