"""Download a server WordPress plugin folder into the repo (for packaging + inspection).

Run: python -m scripts.pull_plugin <plugin-slug> [dest_dir]
Default dest: assets/plugins/<slug>/
"""
from __future__ import annotations

import posixpath
import stat
import sys
from pathlib import Path

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient


def _walk_download(sftp, remote_dir: str, local_dir: Path) -> int:
    local_dir.mkdir(parents=True, exist_ok=True)
    count = 0
    for entry in sftp.listdir_attr(remote_dir):
        rpath = posixpath.join(remote_dir, entry.filename)
        lpath = local_dir / entry.filename
        if stat.S_ISDIR(entry.st_mode):
            count += _walk_download(sftp, rpath, lpath)
        else:
            sftp.get(rpath, str(lpath))
            count += 1
    return count


def main() -> int:
    if len(sys.argv) < 2:
        print("usage: python -m scripts.pull_plugin <slug> [dest]")
        return 2
    slug = sys.argv[1]
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    remote = posixpath.join(cfg.wp_path, "wp-content", "plugins", slug)
    dest = Path(sys.argv[2]) if len(sys.argv) > 2 else AGENT_DIR / "assets" / "plugins" / slug
    sftp = c._ssh.open_sftp()
    try:
        n = _walk_download(sftp, remote, dest)
    finally:
        sftp.close()
    c.close()
    print(f"downloaded {n} files -> {dest}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
