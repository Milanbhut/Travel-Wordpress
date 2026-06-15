"""Ensure the WordPress .htaccess rewrite rules exist so pretty permalinks work.

WP-CLI's `rewrite flush --hard` does not reliably create .htaccess under LiteSpeed, so we
write the canonical rules into the WP root, then flush. Run: python -m scripts.fix_permalinks
"""
from __future__ import annotations

from urllib.parse import urlparse

from scripts.config import load_config
from scripts.wp import WPClient


def _base_path(wp_url: str | None) -> str:
    """Derive the mod_rewrite base from WP_URL, so subdirectory installs (e.g.
    http://localhost/petcare) get RewriteBase /petcare/ instead of /."""
    path = urlparse(wp_url or "").path.strip("/")
    return f"/{path}/" if path else "/"


def _htaccess(base: str) -> str:
    return (
        "# BEGIN WordPress\n"
        "<IfModule mod_rewrite.c>\n"
        "RewriteEngine On\n"
        f"RewriteBase {base}\n"
        "RewriteRule ^index\\.php$ - [L]\n"
        "RewriteCond %{REQUEST_FILENAME} !-f\n"
        "RewriteCond %{REQUEST_FILENAME} !-d\n"
        f"RewriteRule . {base}index.php [L]\n"
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

    base = _base_path(cfg.wp_url)
    c.put_bytes(".htaccess", _htaccess(base).encode("utf-8"))
    print("RewriteBase:", base)
    print("wrote", c.fs_path(".htaccess"))

    c.wp(["rewrite", "structure", "/%postname%/", "--hard"])
    c.wp(["rewrite", "flush", "--hard"])
    print("permalink structure set + flushed")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
