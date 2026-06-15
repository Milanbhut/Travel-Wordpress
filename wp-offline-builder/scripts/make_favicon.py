"""Generate an on-brand favicon/site icon and set it in WordPress.

A moss-green tile with a white two-leaf sprout (GrowHaven). Saves a local copy and sets it
as the WordPress site icon. Run: python -m scripts.make_favicon
"""
from __future__ import annotations

import io
import math

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient

LOCAL = AGENT_DIR / "assets" / "master-theme" / "growhaven" / "assets" / "img" / "icon.png"

GREEN = (93, 124, 63, 255)    # --teal-500 #5d7c3f
WHITE = (255, 255, 255, 255)


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

    s = 512
    img = Image.new("RGBA", (s, s), GREEN)
    d = ImageDraw.Draw(img)
    draw_sprout(d, s // 2, int(s * 0.43), 1.0, WHITE)
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
    print("saved local", LOCAL.name)

    staged = c.stage_tmp("growhaven-icon.png", data)
    try:
        att = c.wp(["media", "import", staged, "--porcelain", "--title=GrowHaven Site Icon"]).strip()
    finally:
        c.unstage_tmp(staged)
    if att.isdigit():
        c.wp(["option", "update", "site_icon", att])
        print("site_icon set to attachment", att)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
