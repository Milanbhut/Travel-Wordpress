"""Generate an on-brand horizontal wordmark and set it as the WordPress custom logo.

Teal tile + white "O" ring + coral accent dot (matches the favicon), then the
"Overlaytop" wordmark in a serif. Saves a local copy, uploads, sets custom_logo.
Run: python -m scripts.make_logo
"""
from __future__ import annotations

import io
import os

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient

LOCAL = AGENT_DIR / "assets" / "master-theme" / "overlaytop" / "assets" / "img" / "logo.png"
WORDMARK = "Overlaytop"


def _font(size: int):
    from PIL import ImageFont

    candidates = [
        r"C:\Windows\Fonts\georgiab.ttf",
        r"C:\Windows\Fonts\Georgia.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf",
    ]
    for p in candidates:
        if os.path.exists(p):
            return ImageFont.truetype(p, size)
    return ImageFont.load_default()


def make_png() -> bytes:
    from PIL import Image, ImageDraw

    teal = (47, 143, 130, 255)
    white = (255, 255, 255, 255)
    coral = (232, 115, 77, 255)
    ink = (27, 36, 32, 255)

    H = 132
    tile = 104
    gap = 28
    pad = 6
    font = _font(80)

    measure = ImageDraw.Draw(Image.new("RGBA", (8, 8)))
    bbox = measure.textbbox((0, 0), WORDMARK, font=font)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    W = pad + tile + gap + tw + pad

    img = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)

    ty = (H - tile) // 2
    d.rounded_rectangle([pad, ty, pad + tile, ty + tile], radius=28, fill=teal)
    cx, cy = pad + tile // 2, ty + tile // 2
    d.ellipse([cx - 36, cy - 36, cx + 36, cy + 36], fill=white)   # O outer
    d.ellipse([cx - 17, cy - 17, cx + 17, cy + 17], fill=teal)    # O hole
    d.ellipse([cx + 18, cy - 40, cx + 40, cy - 18], fill=coral)   # coral accent

    tx = pad + tile + gap
    d.text((tx, (H - th) // 2 - bbox[1]), WORDMARK, font=font, fill=ink)

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

    remote = "/tmp/overlaytop-logo.png"
    sftp = c._ssh.open_sftp()
    try:
        with sftp.open(remote, "wb") as fh:
            fh.write(data)
    finally:
        sftp.close()
    att = c.wp(["media", "import", remote, "--porcelain", "--title=Overlaytop"]).strip()
    c._ssh_exec("rm -f " + remote)
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
