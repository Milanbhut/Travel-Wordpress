"""Run an arbitrary shell command on the server over SSH (diagnostics/ops).

Run: python -m scripts.sh "<command>"
"""
from __future__ import annotations

import sys

from scripts.config import load_config
from scripts.wp import WPClient


def main() -> int:
    if len(sys.argv) < 2:
        print("usage: python -m scripts.sh '<command>'")
        return 2
    c = WPClient(load_config())
    c.verify()
    code, out, err = c._ssh_exec(sys.argv[1])
    if out:
        print(out.rstrip())
    if err:
        print("STDERR:", err.rstrip())
    print(f"[exit {code}]")
    c.close()
    return code


if __name__ == "__main__":
    raise SystemExit(main())
