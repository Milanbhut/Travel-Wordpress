"""Run an arbitrary shell command on the server over SSH - remote-only; N/A for offline (local) builds.

Run: python -m scripts.sh "<command>"
"""
from __future__ import annotations


def main() -> int:
    print("[skip] sh: remote-only operation; not applicable to offline (local) builds.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
