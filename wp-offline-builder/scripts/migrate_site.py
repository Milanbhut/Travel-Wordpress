"""Migrate the built WordPress site between Hostinger docroots over SSH - remote-only; N/A for offline (local) builds.

Usage: python -m scripts.migrate_site <source_domain> <target_domain>
"""
from __future__ import annotations


def main() -> int:
    print("[skip] migrate_site: remote-only operation; not applicable to offline (local) builds.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
