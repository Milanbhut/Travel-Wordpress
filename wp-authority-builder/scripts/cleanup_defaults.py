"""Remove WordPress default content (Hello World post, Sample Page, default comment).

Run: python -m scripts.cleanup_defaults
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient, WPError


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    for slug, ptype in (("hello-world", "post"), ("sample-page", "page")):
        ids = c.wp(["post", "list", f"--post_type={ptype}", f"--name={slug}", "--post_status=any", "--field=ID"]).strip()
        for pid in ids.split():
            try:
                c.wp(["post", "delete", pid, "--force"])
                print(f"deleted {ptype} {pid} ({slug})")
            except WPError as e:
                print(f"skip {ptype} {pid}: {e}")

    # WordPress auto-creates a Privacy Policy page as a DRAFT. Remove only that stub —
    # never a published Privacy Policy (build_pages owns the real one at this slug).
    stubs = c.wp(["post", "list", "--post_type=page", "--name=privacy-policy", "--post_status=draft,auto-draft,pending", "--field=ID"]).strip()
    for pid in stubs.split():
        try:
            c.wp(["post", "delete", pid, "--force"])
            print(f"deleted privacy-policy draft stub {pid}")
        except WPError as e:
            print(f"skip privacy stub {pid}: {e}")

    try:
        c.wp(["comment", "delete", "1", "--force"])
        print("deleted default comment")
    except WPError:
        pass

    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
