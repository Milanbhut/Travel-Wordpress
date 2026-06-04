"""Generate an on-brand favicon/site icon and set it in WordPress.

A teal tile with a white "O" ring (Overlaytop) and a coral accent dot. Saves a local copy
and sets it as the WordPress site icon. Run: python -m scripts.make_favicon
"""
from __future__ import annotations

import io

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient

LOCAL = AGENT_DIR / "assets" / "master-theme" / "overlaytop" / "assets" / "img" / "icon.png"


def make_png() -> bytes:
    from PIL import Image, ImageDraw

    s = 512
    img = Image.new("RGBA", (s, s), (47, 143, 130, 255))  # teal
    d = ImageDraw.Draw(img)
    cx = cy = s // 2
    d.ellipse([cx - 178, cy - 178, cx + 178, cy + 178], fill=(255, 255, 255, 255))  # white disc
    d.ellipse([cx - 92, cy - 92, cx + 92, cy + 92], fill=(47, 143, 130, 255))       # teal hole -> "O"
    d.ellipse([cx + 96, cy - 184, cx + 184, cy - 96], fill=(232, 115, 77, 255))      # coral accent dot
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

    remote = "/tmp/overlaytop-icon.png"
    sftp = c._ssh.open_sftp()
    try:
        with sftp.open(remote, "wb") as fh:
            fh.write(data)
    finally:
        sftp.close()
    att = c.wp(["media", "import", remote, "--porcelain", "--title=Overlaytop Site Icon"]).strip()
    c._ssh_exec("rm -f " + remote)
    if att.isdigit():
        c.wp(["option", "update", "site_icon", att])
        print("site_icon set to attachment", att)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
