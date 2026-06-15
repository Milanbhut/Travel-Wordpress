"""Provisioning-detail probe over SSH (MySQL/PHP/target dir) - remote-only; N/A for offline (local) builds.

Run: python -m scripts.check_provision
"""
from __future__ import annotations


def main() -> int:
    print("[skip] check_provision: remote-only operation; not applicable to offline (local) builds.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
