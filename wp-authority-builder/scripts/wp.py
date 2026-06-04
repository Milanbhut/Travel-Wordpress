"""WordPress control channel: WP-CLI over SSH (primary) with REST API fallback."""
from __future__ import annotations

import shlex
from typing import Any, Optional

import paramiko
import requests
from requests.auth import HTTPBasicAuth

from scripts.config import Config


class WPError(Exception):
    """Raised on control-channel failures."""


class WPClient:
    def __init__(self, config: Config):
        self.cfg = config
        self.channel: Optional[str] = None          # "wpcli" | "rest"
        self._ssh: Optional[paramiko.SSHClient] = None

    # --- SSH plumbing ---
    def _connect_ssh(self) -> paramiko.SSHClient:
        client = paramiko.SSHClient()
        client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        kwargs: dict[str, Any] = dict(
            hostname=self.cfg.ssh_host, port=self.cfg.ssh_port,
            username=self.cfg.ssh_user, timeout=20,
        )
        if self.cfg.ssh_key:
            client.connect(key_filename=self.cfg.ssh_key, **kwargs)
        else:
            client.connect(password=self.cfg.ssh_pass, **kwargs)
        self._ssh = client
        return client

    def _ssh_exec(self, command: str) -> tuple[int, str, str]:
        if self._ssh is None:
            self._connect_ssh()
        _stdin, stdout, stderr = self._ssh.exec_command(command)
        out = stdout.read().decode("utf-8", "replace")
        err = stderr.read().decode("utf-8", "replace")
        code = stdout.channel.recv_exit_status()
        return code, out, err

    def _wp_prefix(self) -> str:
        path = f" --path={shlex.quote(self.cfg.wp_path)}" if self.cfg.wp_path else ""
        if self.cfg.wp_cli_php:
            # Run WP-CLI under a specific PHP (e.g. 8.2) instead of the host default (7.4).
            return f"{shlex.quote(self.cfg.wp_cli_php)} $(command -v wp){path}"
        return f"wp{path}"

    # --- public API ---
    def verify(self) -> dict:
        """Gate 1: confirm a usable channel; prefer WP-CLI, else REST."""
        if self.cfg.has_ssh:
            code, out, _err = self._ssh_exec(
                f"{self._wp_prefix()} --info --skip-plugins --skip-themes"
            )
            if code == 0 and "WP-CLI" in out:
                self.channel = "wpcli"
                return {"channel": "wpcli", "info": out.strip()}
        if self.cfg.has_rest:
            r = requests.get(self.cfg.wp_url.rstrip("/") + "/wp-json/", timeout=20)
            if r.ok:
                self.channel = "rest"
                return {"channel": "rest", "namespaces": r.json().get("namespaces", [])}
        raise WPError(
            "No usable control channel: WP-CLI over SSH failed and the REST API is unavailable."
        )

    def wp(self, args: list[str]) -> str:
        """Run a WP-CLI command remotely and return stdout (raises on non-zero exit)."""
        if self.channel != "wpcli":
            raise WPError("wp() requires the 'wpcli' channel; call verify() first.")
        cmd = self._wp_prefix() + " " + " ".join(shlex.quote(a) for a in args)
        code, out, err = self._ssh_exec(cmd)
        if code != 0:
            raise WPError(f"`wp {' '.join(args)}` failed (exit {code}): {err.strip()}")
        return out.strip()

    def rest(self, method: str, endpoint: str, **kwargs: Any) -> Any:
        """Call the WP REST API with application-password auth."""
        if not self.cfg.has_rest:
            raise WPError("REST channel not configured.")
        url = self.cfg.wp_url.rstrip("/") + "/wp-json/" + endpoint.lstrip("/")
        auth = HTTPBasicAuth(self.cfg.wp_admin_user, self.cfg.wp_app_password)
        r = requests.request(method, url, auth=auth, timeout=30, **kwargs)
        if not r.ok:
            raise WPError(f"REST {method} {endpoint} failed ({r.status_code}): {r.text[:300]}")
        return r.json() if r.content else None

    def close(self) -> None:
        if self._ssh is not None:
            self._ssh.close()
            self._ssh = None
