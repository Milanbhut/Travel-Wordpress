"""Generate an on-brand horizontal wordmark and set it as the WordPress custom logo.

Moss-green tile + white two-leaf sprout (matches the favicon), then the "GrowHaven" wordmark
in a bold sans. Saves a local copy, uploads, sets custom_logo. Run: python -m scripts.make_logo
"""
from __future__ import annotations

import io
import math
import os

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient

LOCAL = AGENT_DIR / "assets" / "master-theme" / "growhaven" / "assets" / "img" / "logo.png"
WORDMARK = "GrowHaven"

GREEN = (93, 124, 63, 255)    # --teal-500 #5d7c3f
WHITE = (255, 255, 255, 255)
INK = (32, 39, 28, 255)       # --ink-900 #20271c


def _font(size: int):
    from PIL import ImageFont

    candidates = [
        r"C:\Windows\Fonts\segoeuib.ttf",
        r"C:\Windows\Fonts\arialbd.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
    ]
    for p in candidates:
        if os.path.exists(p):
            return ImageFont.truetype(p, size)
    return ImageFont.load_default()


def draw_sprout(d, cx, cy, scale, color):
    """Draw a centered two-leaf seedling (stem + splayed leaves), scaled by `scale`."""
    L = 200 * scale            # leaf length
    W = 96 * scale             # leaf width
    fork = (cx, cy + 14 * scale)
    sw = max(3, 24 * scale)    # stem width
    d.rounded_rectangle(
        [cx - sw / 2, cy - 6 * scale, cx + sw / 2, cy + 172 * scale],
        radius=sw / 2, fill=color,
    )
    for theta_deg in (-145, -35):
        th = math.radians(theta_deg)
        dx, dy = math.cos(th), math.sin(th)
        nx, ny = -math.sin(th), math.cos(th)
        n = 26
        pts = []
        for i in range(n + 1):
            t = math.pi * i / n
            u = L * (i / n)
            w = (W / 2) * math.sin(t)
            pts.append((fork[0] + u * dx + w * nx, fork[1] + u * dy + w * ny))
        for i in range(n, -1, -1):
            t = math.pi * i / n
            u = L * (i / n)
            w = (W / 2) * math.sin(t)
            pts.append((fork[0] + u * dx - w * nx, fork[1] + u * dy - w * ny))
        d.polygon(pts, fill=color)


def make_png() -> bytes:
    from PIL import Image, ImageDraw

    H = 132
    tile = 104
    gap = 26
    pad = 6
    font = _font(74)

    measure = ImageDraw.Draw(Image.new("RGBA", (8, 8)))
    bbox = measure.textbbox((0, 0), WORDMARK, font=font)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    W = pad + tile + gap + tw + pad

    img = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)

    ty = (H - tile) // 2
    d.rounded_rectangle([pad, ty, pad + tile, ty + tile], radius=28, fill=GREEN)
    draw_sprout(d, pad + tile // 2, ty + int(tile * 0.43), tile / 512.0, WHITE)

    tx = pad + tile + gap
    d.text((tx, (H - th) // 2 - bbox[1]), WORDMARK, font=font, fill=INK)

    buf = io.BytesIO()
    img.save(buf, "PNG")
    return buf.getvalue()


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    data = make_png()
    LOCAL.parent.mkdir(parents=True, exist_ok=True)
    LOCAL.write_bytes(data)
    print("saved local", LOCAL.name, f"({len(data)} bytes)")

    staged = c.stage_tmp("growhaven-logo.png", data)
    try:
        att = c.wp(["media", "import", staged, "--porcelain", "--title=GrowHaven"]).strip()
    finally:
        c.unstage_tmp(staged)
    if att.isdigit():
        c.wp(["theme", "mod", "set", "custom_logo", att])
        print("custom_logo set to attachment", att)
    else:
        print("media import failed:", att)
        return 1
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
