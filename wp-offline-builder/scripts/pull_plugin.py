"""Download a server WordPress plugin folder into the repo (for packaging + inspection).

Remote-only operation: it pulls a plugin's files off a live server over SFTP. The local
(offline) builder has no remote server to pull from, so this is a deliberate no-op here.

Run: python -m scripts.pull_plugin <plugin-slug> [dest_dir]
"""
from __future__ import annotations


def main() -> int:
    print(
        "pull_plugin is a remote-only operation (downloads a plugin over SFTP). "
        "The offline builder has no remote server to pull from - nothing to do."
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
