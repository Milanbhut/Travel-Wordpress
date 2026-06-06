"""Clear the AdSense-checklist duplicate-images finding on jobskhojo.com.

41 byte-identical attachments exist (a fallback image the dedup pass left behind);
only post 245 still uses one as its featured image. Source a fresh unique image for
245, then delete the orphan duplicates. If sourcing fails, keep 245's current image
and delete only the other 40 — either way no byte-duplicate group remains.

Run: python -m scripts.fix_dupe_images
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.images import import_image_to_wp, search_pexels, search_unsplash, trigger_unsplash_download
from scripts.wp import WPClient

DUPE_IDS = [
    246, 244, 242, 240, 238, 236, 234, 232, 230, 228, 226, 224, 222, 220, 218, 216,
    214, 212, 210, 208, 206, 204, 202, 200, 198, 196, 194, 192, 190, 188, 186, 184,
    182, 180, 178, 176, 174, 172, 170, 168, 98,
]
POST_ID = 245
KEEP_ID = 246  # the one currently featured on POST_ID
QUERIES = ["campervan cooking meal", "van life kitchen food", "camping cooking outdoor"]


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()

    new_att = None
    pick = None
    for q in QUERIES:
        results = []
        if cfg.unsplash_key:
            try:
                results = search_unsplash(q, cfg.unsplash_key, per_page=8)
            except Exception as e:  # noqa: BLE001
                print(f"[unsplash {q!r}] {e}")
        if not results and cfg.pexels_key:
            try:
                results = search_pexels(q, cfg.pexels_key, per_page=8)
            except Exception as e:  # noqa: BLE001
                print(f"[pexels {q!r}] {e}")
        if results:
            pick = results[0]
            new_att = import_image_to_wp(
                c, cfg, pick["url"], "vanlife-food-meals.jpg",
                post_id=POST_ID, featured=True,
                title="A budget meal cooked in a camper van", alt=pick["alt"],
            )
            if new_att and pick["src"] == "unsplash" and pick.get("download_location"):
                trigger_unsplash_download(pick["download_location"], cfg.unsplash_key)
            if new_att:
                print(f"sourced new featured image att={new_att} ({pick['src']}, {q!r}) for post {POST_ID}")
                break

    to_delete = DUPE_IDS if new_att else [i for i in DUPE_IDS if i != KEEP_ID]
    if not new_att:
        print(f"[fallback] image sourcing failed — keeping att {KEEP_ID} on post {POST_ID}, deleting the other {len(to_delete)} orphans")

    deleted = 0
    for aid in to_delete:
        try:
            c.wp(["post", "delete", str(aid), "--force"])
            deleted += 1
        except Exception as e:  # noqa: BLE001
            print(f"[skip {aid}] {e}")
    print(f"deleted {deleted}/{len(to_delete)} duplicate attachments")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
