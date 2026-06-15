"""Write robots.txt (allowing Mediapartners-Google + sitemap) and ads.txt to the site root.

Run: python -m scripts.seo_files
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    host = (cfg.wp_url or "https://overlaytop.com").rstrip("/")
    robots = (
        "User-agent: *\n"
        "Disallow: /wp-admin/\n"
        "Allow: /wp-admin/admin-ajax.php\n\n"
        "User-agent: Mediapartners-Google\n"
        "Allow: /\n\n"
        f"Sitemap: {host}/wp-sitemap.xml\n"
    )

    pub = (cfg.adsense_pub_id or "").strip()
    norm = pub.lower()
    if pub.startswith("pub-") or (pub and pub.replace("-", "").isalnum() and "later" not in norm and not pub.startswith("#")):
        pubid = pub if pub.startswith("pub-") else "pub-" + pub
        ads = f"google.com, {pubid}, DIRECT, f08c47fec0942fa0\n"
    else:
        ads = (
            "# ads.txt for GrowHaven\n"
            "# Once your Google AdSense account is approved, replace the line below with your own\n"
            "# publisher line (find it in AdSense -> Sites -> ads.txt):\n"
            "# google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0\n"
        )

    for name, content in (("robots.txt", robots), ("ads.txt", ads)):
        c.put_bytes(name, content.encode("utf-8"))
        print("wrote", c.fs_path(name))
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
