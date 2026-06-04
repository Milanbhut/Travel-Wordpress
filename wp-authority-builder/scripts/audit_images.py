"""Media audit: backfill any missing featured images and replace near-duplicate images.

Uses a perceptual dHash (Pillow only) to find visually similar featured images and swaps
duplicates for fresh distinct ones. Run: python -m scripts.audit_images
"""
from __future__ import annotations

import io
import json
import posixpath

from scripts.config import load_config, AGENT_DIR
from scripts.images import search_unsplash, search_pexels, trigger_unsplash_download, import_image_to_wp
from scripts.wp import WPClient, WPError

PLAN = AGENT_DIR / "config" / "overlaytop.editorial-plan.json"
USED = AGENT_DIR / "state" / "used_images.json"


def dhash(img, size=8):
    img = img.convert("L").resize((size + 1, size))
    px = img.load()
    bits = 0
    idx = 0
    for y in range(size):
        for x in range(size):
            bits |= (1 if px[x, y] < px[x + 1, y] else 0) << idx
            idx += 1
    return bits


def hamming(a, b):
    return bin(a ^ b).count("1")


def load_used():
    return set(json.loads(USED.read_text(encoding="utf-8"))) if USED.exists() else set()


def save_used(u):
    USED.parent.mkdir(parents=True, exist_ok=True)
    USED.write_text(json.dumps(sorted(u)), encoding="utf-8")


def source_image(c, cfg, entry, post_id, used, page=1):
    queries = [q for q in (entry.get("image_query"), entry.get("category_title"), "budget travel scenery") if q]
    results = []
    if cfg.unsplash_key:
        for q in queries:
            try:
                results = search_unsplash(q, cfg.unsplash_key, per_page=20, page=page)
            except Exception:
                results = []
            if results:
                break
    if not results and cfg.pexels_key:
        for q in queries:
            try:
                results = search_pexels(q, cfg.pexels_key, per_page=20, page=page)
            except Exception:
                results = []
            if results:
                break
    pick = next((r for r in results if r["id"] not in used), results[0] if results else None)
    if not pick:
        return None
    url = pick["raw"] + "&w=1600&q=80&fit=crop&fm=jpg" if pick["src"] == "unsplash" else pick["raw"]
    att = import_image_to_wp(c, cfg, url, filename=f"{entry['slug']}.jpg", post_id=post_id, featured=True, title=entry["title"], alt=pick["alt"])
    if att:
        used.add(pick["id"])
        save_used(used)
        if pick.get("download_location"):
            trigger_unsplash_download(pick["download_location"], cfg.unsplash_key)
    return att


def main() -> int:
    from PIL import Image

    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    plan = {e["slug"]: e for e in json.loads(PLAN.read_text(encoding="utf-8"))["articles"]}
    used = load_used()
    posts = json.loads(c.wp(["post", "list", "--post_type=post", "--post_status=publish", "--fields=ID,post_name", "--format=json"]))
    print(f"auditing {len(posts)} posts")

    def thumb_of(pid):
        try:
            t = c.wp(["post", "meta", "get", str(pid), "_thumbnail_id"]).strip()
            return t if t.isdigit() else None
        except WPError:
            return None

    def entry_for(name):
        return plan.get(name, {"slug": name, "title": name.replace("-", " ").title()})

    # Phase 1: backfill missing featured images.
    filled = 0
    for p in posts:
        if not thumb_of(p["ID"]):
            if source_image(c, cfg, entry_for(p["post_name"]), p["ID"], used):
                filled += 1
                print(f"  filled: {p['post_name']}")
    print(f"filled {filled} missing images")

    # Phase 2: replace perceptual-hash duplicates.
    sftp = c._ssh.open_sftp()

    def img_hash(thumb):
        try:
            rel = c.wp(["post", "meta", "get", str(thumb), "_wp_attached_file"]).strip()
            remote = posixpath.join(cfg.wp_path, "wp-content", "uploads", rel)
            with sftp.open(remote, "rb") as fh:
                return dhash(Image.open(io.BytesIO(fh.read())))
        except Exception:
            return None

    seen = []
    replaced = 0
    for p in posts:
        t = thumb_of(p["ID"])
        if not t:
            continue
        h = img_hash(t)
        if h is None:
            continue
        if any(hamming(h, h2) <= 8 for _, h2 in seen):
            new_att = source_image(c, cfg, entry_for(p["post_name"]), p["ID"], used, page=2)
            nh = img_hash(new_att) if new_att else None
            seen.append((p["post_name"], nh if nh is not None else h))
            if new_att:
                replaced += 1
                print(f"  replaced duplicate: {p['post_name']}")
        else:
            seen.append((p["post_name"], h))
    sftp.close()

    distinct = len({h for _, h in seen})
    print(f"replaced {replaced} duplicates; {distinct}/{len(seen)} distinct featured images")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
