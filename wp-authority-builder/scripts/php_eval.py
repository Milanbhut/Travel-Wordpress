"""Run a local PHP file on the server via `wp eval-file` (SFTP -> eval -> cleanup).

For diagnostics and one-off data fixes that need WordPress loaded.
Run: python -m scripts.php_eval <local_file.php>
"""
from __future__ import annotations

import sys
from pathlib import Path

from scripts.config import load_config
from scripts.wp import WPClient, WPError


def main() -> int:
    if len(sys.argv) < 2:
        print("usage: python -m scripts.php_eval <file.php>")
        return 2
    php = Path(sys.argv[1]).read_text(encoding="utf-8")
    c = WPClient(load_config())
    c.verify()
    remote = "/tmp/_ote_eval.php"
    sftp = c._ssh.open_sftp()
    try:
        with sftp.open(remote, "wb") as fh:
            fh.write(php.encode("utf-8"))
    finally:
        sftp.close()
    try:
        print(c.wp(["eval-file", remote]))
    except WPError as e:
        print("ERR:", e)
    c._ssh_exec("rm -f " + remote)
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
