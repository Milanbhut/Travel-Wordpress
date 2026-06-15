"""WordPress control channel for LOCAL (offline) builds.

Drives a WordPress install on THIS machine (e.g. under XAMPP) by invoking WP-CLI
through a local PHP binary + wp-cli.phar. File operations are plain local-filesystem
copies (no SSH/SFTP). The public API mirrors the remote agent's WPClient so the build
scripts work unchanged; the file/exec helpers (put_bytes/put_file/stage_tmp/eval_php)
replace the remote agent's direct SFTP/_ssh_exec usage.
"""
from __future__ import annotations

import os
import shutil
import subprocess
import tempfile
from pathlib import Path
from typing import Any, Optional

import requests
from requests.auth import HTTPBasicAuth

from scripts.config import Config


class WPError(Exception):
    """Raised on control-channel failures."""


class WPClient:
    def __init__(self, config: Config):
        self.cfg = config
        self.channel: Optional[str] = None  # "wpcli" | "rest"
        self._tmpdir = Path(tempfile.gettempdir()) / "wp-offline-builder"

    # --- local WP-CLI plumbing ---
    def _wp_cmd(self, args: list[str]) -> list[str]:
        cmd = [self.cfg.local_php, self.cfg.wp_cli_phar]
        if self.cfg.wp_path:
            cmd.append(f"--path={self.cfg.wp_path}")
        cmd.extend(args)
        return cmd

    def _run(self, cmd: list[str]) -> tuple[int, str, str]:
        proc = subprocess.run(cmd, capture_output=True, text=True, encoding="utf-8", errors="replace")
        return proc.returncode, proc.stdout or "", proc.stderr or ""

    # --- public API (mirrors the remote WPClient) ---
    def verify(self) -> dict:
        """Gate 1: confirm a usable local channel; prefer WP-CLI, else REST."""
        code, out, err = self._run(self._wp_cmd(["--info", "--skip-plugins", "--skip-themes"]))
        if code == 0 and "WP-CLI" in out:
            self.channel = "wpcli"
            return {"channel": "wpcli", "info": out.strip()}
        if self.cfg.has_rest:
            r = requests.get((self.cfg.wp_url or "").rstrip("/") + "/wp-json/", timeout=20)
            if r.ok:
                self.channel = "rest"
                return {"channel": "rest", "namespaces": r.json().get("namespaces", [])}
        raise WPError(
            f"No usable local WP-CLI channel. `wp --info` exit {code}: {(err or out).strip()} "
            f"(check LOCAL_PHP={self.cfg.local_php}, WP_CLI_PHAR={self.cfg.wp_cli_phar}, WP_PATH={self.cfg.wp_path})."
        )

    def wp(self, args: list[str]) -> str:
        """Run a WP-CLI command locally and return stdout (raises on non-zero exit)."""
        if self.channel != "wpcli":
            raise WPError("wp() requires the 'wpcli' channel; call verify() first.")
        code, out, err = self._run(self._wp_cmd(args))
        if code != 0:
            raise WPError(f"`wp {' '.join(args)}` failed (exit {code}): {(err or out).strip()}")
        return out.strip()

    def rest(self, method: str, endpoint: str, **kwargs: Any) -> Any:
        """Call the WP REST API with application-password auth (fallback channel)."""
        if not self.cfg.has_rest:
            raise WPError("REST channel not configured.")
        url = (self.cfg.wp_url or "").rstrip("/") + "/wp-json/" + endpoint.lstrip("/")
        auth = HTTPBasicAuth(self.cfg.wp_admin_user, self.cfg.wp_app_password)
        r = requests.request(method, url, auth=auth, timeout=30, **kwargs)
        if not r.ok:
            raise WPError(f"REST {method} {endpoint} failed ({r.status_code}): {r.text[:300]}")
        return r.json() if r.content else None

    # --- filesystem helpers (LOCAL; scripts use these instead of SFTP/_ssh_exec) ---
    def fs_path(self, rel: str) -> str:
        """Absolute path of a WordPress-relative path (e.g. 'wp-content/themes/x/style.css')."""
        return os.path.join(self.cfg.wp_path, rel.replace("/", os.sep))

    def mkdirs(self, rel: str) -> None:
        Path(self.fs_path(rel)).mkdir(parents=True, exist_ok=True)

    def put_bytes(self, rel: str, data: bytes) -> None:
        """Write bytes into the WP filesystem at a WP-relative path."""
        p = Path(self.fs_path(rel))
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_bytes(data)

    def put_file(self, src_local: str, rel: str) -> None:
        """Copy a local file into the WP filesystem at a WP-relative path."""
        dst = Path(self.fs_path(rel))
        dst.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(src_local, dst)

    def stage_tmp(self, name: str, data: bytes) -> str:
        """Write data to a temp file the local WP-CLI can read; return its absolute path.

        Used for `wp media import <path>` and `wp eval-file <path>`.
        """
        self._tmpdir.mkdir(parents=True, exist_ok=True)
        p = self._tmpdir / name
        p.write_bytes(data)
        return str(p)

    def unstage_tmp(self, path: str) -> None:
        try:
            os.remove(path)
        except OSError:
            pass

    def eval_php(self, code: str) -> str:
        """Run PHP with WordPress loaded via `wp eval-file` (stage -> eval-file -> cleanup)."""
        tmp = self.stage_tmp("_eval.php", code.encode("utf-8"))
        try:
            return self.wp(["eval-file", tmp])
        finally:
            self.unstage_tmp(tmp)

    def close(self) -> None:
        pass
