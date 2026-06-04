"""Create the /categories and /blog pages and assign their custom templates. Idempotent.

Run: python -m scripts.create_template_pages
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient, WPError


def first_id(s):
    s = (s or "").strip()
    p = s.split()
    return p[0] if p and p[0].isdigit() else None


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    def wt(args):
        try:
            return c.wp(args)
        except WPError as e:
            return "ERR:" + str(e)

    for title, slug, tpl in (
        ("Categories", "categories", "page-categories.php"),
        ("Blog", "blog", "page-blog.php"),
    ):
        pid = first_id(wt(["post", "list", "--post_type=page", f"--name={slug}", "--post_status=any", "--field=ID"]))
        if not pid:
            pid = c.wp([
                "post", "create", "--post_type=page", "--post_status=publish",
                f"--post_title={title}", f"--post_name={slug}", "--porcelain",
            ]).strip()
            print(f"created {slug} -> page {pid}")
        else:
            print(f"exists {slug} -> page {pid}")
        c.wp(["post", "meta", "update", pid, "_wp_page_template", tpl])

    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
