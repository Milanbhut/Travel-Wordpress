"""Image sourcing from Unsplash + Pexels (real photographs only).

Search helpers return normalized dicts; download/compress/dedupe live in the full
content engine. Unsplash ToS: trigger the download endpoint when an image is used.
Test: python -m scripts.images "budget travel"
"""
from __future__ import annotations

import io
import sys

import requests

from scripts.config import load_config


def search_unsplash(query: str, key: str, per_page: int = 12, page: int = 1, orientation: str = "landscape"):
    r = requests.get(
        "https://api.unsplash.com/search/photos",
        params={"query": query, "per_page": per_page, "page": page, "orientation": orientation, "content_filter": "high"},
        headers={"Authorization": f"Client-ID {key}", "Accept-Version": "v1"},
        timeout=25,
    )
    r.raise_for_status()
    out = []
    for x in r.json().get("results", []):
        out.append(
            {
                "src": "unsplash",
                "id": x["id"],
                "url": x["urls"]["regular"],
                "raw": x["urls"]["raw"],
                "download_location": x["links"]["download_location"],
                "alt": (x.get("alt_description") or query).strip(),
                "author": x["user"]["name"],
                "author_url": x["user"]["links"]["html"],
            }
        )
    return out


def trigger_unsplash_download(download_location: str, key: str) -> None:
    """Required by Unsplash API guidelines when an image is actually used."""
    try:
        requests.get(download_location, headers={"Authorization": f"Client-ID {key}"}, timeout=15)
    except requests.RequestException:
        pass


def search_pexels(query: str, key: str, per_page: int = 12, page: int = 1, orientation: str = "landscape"):
    r = requests.get(
        "https://api.pexels.com/v1/search",
        params={"query": query, "per_page": per_page, "page": page, "orientation": orientation},
        headers={"Authorization": key},
        timeout=25,
    )
    r.raise_for_status()
    out = []
    for x in r.json().get("photos", []):
        out.append(
            {
                "src": "pexels",
                "id": str(x["id"]),
                "url": x["src"]["large2x"],
                "raw": x["src"]["original"],
                "download_location": None,
                "alt": (x.get("alt") or query).strip(),
                "author": x["photographer"],
                "author_url": x["photographer_url"],
            }
        )
    return out


def download_compress(url: str, max_w: int = 1600, quality: int = 82) -> bytes:
    """Download an image and re-encode as an optimized progressive JPEG."""
    from PIL import Image  # lazy import: only needed when importing media

    r = requests.get(url, timeout=40)
    r.raise_for_status()
    img = Image.open(io.BytesIO(r.content)).convert("RGB")
    if img.width > max_w:
        new_h = round(img.height * max_w / img.width)
        img = img.resize((max_w, new_h), Image.LANCZOS)
    buf = io.BytesIO()
    img.save(buf, format="JPEG", quality=quality, optimize=True, progressive=True)
    return buf.getvalue()


def import_image_to_wp(c, cfg, url, filename, post_id=None, featured=False, title=None, alt=None, max_w=1600):
    """Download+compress an image, stage it locally, then `wp media import` it.

    Returns the attachment ID (str) or None. `filename` must end in a real extension (e.g. .jpg).
    """
    data = download_compress(url, max_w=max_w)
    staged = c.stage_tmp(filename, data)

    args = ["media", "import", staged, "--porcelain"]
    if post_id:
        args.append(f"--post_id={post_id}")
    if featured:
        args.append("--featured_image")
    if title:
        args.append(f"--title={title}")
    try:
        att = c.wp(args).strip()
    except Exception as e:  # noqa: BLE001 - report and continue
        c.unstage_tmp(staged)
        print(f"[image import failed] {e}")
        return None
    c.unstage_tmp(staged)
    if att.isdigit():
        if alt:
            c.wp(["post", "meta", "update", att, "_wp_attachment_image_alt", alt[:120]])
        return att
    return None


def _main() -> int:
    cfg = load_config()
    query = sys.argv[1] if len(sys.argv) > 1 else "budget travel backpacker"
    print(f"query: {query!r}\n")
    if cfg.unsplash_key:
        try:
            res = search_unsplash(query, cfg.unsplash_key, per_page=5)
            print(f"Unsplash: {len(res)} results")
            for r in res[:3]:
                print(f"  - {r['id']} by {r['author']}: {r['alt'][:60]}")
        except Exception as e:
            print(f"Unsplash ERROR: {e}")
    else:
        print("Unsplash: no key set")
    print()
    if cfg.pexels_key:
        try:
            res = search_pexels(query, cfg.pexels_key, per_page=5)
            print(f"Pexels: {len(res)} results")
            for r in res[:3]:
                print(f"  - {r['id']} by {r['author']}: {r['alt'][:60]}")
        except Exception as e:
            print(f"Pexels ERROR: {e}")
    else:
        print("Pexels: no key set")
    return 0


if __name__ == "__main__":
    raise SystemExit(_main())
