"""Ensure the WordPress .htaccess rewrite rules exist so pretty permalinks work.

WP-CLI's `rewrite flush --hard` does not reliably create .htaccess under LiteSpeed, so we
write the canonical rules over SFTP, then flush. Run: python -m scripts.fix_permalinks
"""
from __future__ import annotations

import posixpath

from scripts.config import load_config
from scripts.wp import WPClient

HTACCESS = (
    "# BEGIN WordPress\n"
    "<IfModule mod_rewrite.c>\n"
    "RewriteEngine On\n"
    "RewriteBase /\n"
    "RewriteRule ^index\\.php$ - [L]\n"
    "RewriteCond %{REQUEST_FILENAME} !-f\n"
    "RewriteCond %{REQUEST_FILENAME} !-d\n"
    "RewriteRule . /index.php [L]\n"
    "</IfModule>\n"
    "# END WordPress\n"
)


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    remote = posixpath.join(cfg.wp_path, ".htaccess")
    sftp = c._ssh.open_sftp()
    try:
        with sftp.open(remote, "wb") as fh:
            fh.write(HTACCESS.encode("utf-8"))
    finally:
        sftp.close()
    print("wrote", remote)

    c.wp(["rewrite", "structure", "/%postname%/", "--hard"])
    c.wp(["rewrite", "flush", "--hard"])
    print("permalink structure set + flushed")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
