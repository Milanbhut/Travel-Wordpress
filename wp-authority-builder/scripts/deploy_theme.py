"""Deploy a theme directory to the server over SFTP, then activate it.

Run: python -m scripts.deploy_theme [local_theme_dir] [slug]
Defaults: assets/master-theme/overlaytop  ->  wp-content/themes/overlaytop  (activated).
Re-deployable: existing files are overwritten; directories are created as needed.
"""
from __future__ import annotations

import posixpath
import stat
import sys
from pathlib import Path

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient


def _ensure_remote_dir(sftp, remote_dir: str) -> None:
    parts = [p for p in remote_dir.split("/") if p]
    cur = ""
    for p in parts:
        cur = cur + "/" + p
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def main() -> int:
    default_dir = AGENT_DIR / "assets" / "master-theme" / "overlaytop"
    theme_dir = Path(sys.argv[1]) if len(sys.argv) > 1 else default_dir
    slug = sys.argv[2] if len(sys.argv) > 2 else theme_dir.name
    if not theme_dir.is_dir():
        print(f"Theme dir not found: {theme_dir}")
        return 2

    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Deploy requires the SSH/WP-CLI channel.")
        return 1

    remote_base = posixpath.join(cfg.wp_path, "wp-content", "themes", slug)
    sftp = c._ssh.open_sftp()
    _ensure_remote_dir(sftp, remote_base)

    count = 0
    for path in sorted(theme_dir.rglob("*")):
        rel = path.relative_to(theme_dir).as_posix()
        remote = posixpath.join(remote_base, rel)
        if path.is_dir():
            _ensure_remote_dir(sftp, remote)
        else:
            _ensure_remote_dir(sftp, posixpath.dirname(remote))
            sftp.put(str(path), remote)
            count += 1
    sftp.close()
    print(f"uploaded {count} files -> {remote_base}")

    # Activate, then confirm it is the active template
    try:
        out = c.wp(["theme", "activate", slug])
        print(out)
    except Exception as e:
        print(f"[activate] {e}")
        c.close()
        return 1
    active = c.wp(["option", "get", "template"])
    print(f"active theme (template) = {active}")
    c.close()
    return 0 if active == slug else 1


if __name__ == "__main__":
    raise SystemExit(main())
