"""List ~/domains with public_html file counts over SSH - remote-only; N/A for offline (local) builds.

Run: python -m scripts.list_domains
"""
from __future__ import annotations


def main() -> int:
    print("[skip] list_domains: remote-only operation; not applicable to offline (local) builds.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
