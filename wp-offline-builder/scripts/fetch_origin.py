"""Fetch a path from the Hostinger origin over SSH - remote-only; N/A for offline (local) builds.

Run: python -m scripts.fetch_origin [path] [--full]
"""
from __future__ import annotations


def main() -> int:
    print("[skip] fetch_origin: remote-only operation; not applicable to offline (local) builds.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
