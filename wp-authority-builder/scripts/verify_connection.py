"""Manual smoke test: load secrets.env, verify the control channel, print WP status.

Usage (from wp-authority-builder/, venv active):
    python -m scripts.verify_connection
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient, WPError


def main() -> int:
    cfg = load_config()
    try:
        cfg.validate()
    except Exception as e:  # ConfigError
        print(f"[CONFIG] {e}")
        return 2

    client = WPClient(cfg)
    try:
        info = client.verify()
    except WPError as e:
        print(f"[FAIL] {e}")
        return 1

    print(f"[OK] control channel = {info['channel']}")
    if info["channel"] == "wpcli":
        print(info["info"])
        # Prove a real command works end-to-end:
        try:
            version = client.wp(["core", "version"])
            print(f"[OK] WordPress core version = {version}")
        except WPError as e:
            print(f"[WARN] `wp core version` failed: {e}")
    else:
        print(f"REST namespaces: {info['namespaces']}")
    client.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
