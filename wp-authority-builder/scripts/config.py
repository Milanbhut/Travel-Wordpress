"""Configuration loader: reads secrets and reports usable control channels."""
from __future__ import annotations

import os
from pathlib import Path
from typing import Mapping, Optional

from dotenv import dotenv_values

AGENT_DIR = Path(__file__).resolve().parents[1]      # scripts/ -> wp-authority-builder/
CONFIG_DIR = AGENT_DIR / "config"
DEFAULT_ENV = CONFIG_DIR / "secrets.env"


class ConfigError(Exception):
    """Raised when no usable control channel is configured."""


class Config:
    def __init__(self, env: Mapping[str, str]):
        self.ssh_host: Optional[str] = env.get("SSH_HOST") or None
        self.ssh_port: int = int(env.get("SSH_PORT") or 22)
        self.ssh_user: Optional[str] = env.get("SSH_USER") or None
        self.ssh_key: Optional[str] = env.get("SSH_KEY") or None
        self.ssh_pass: Optional[str] = env.get("SSH_PASS") or None
        self.wp_path: str = env.get("WP_PATH") or ""
        self.wp_url: Optional[str] = env.get("WP_URL") or None
        self.wp_admin_user: Optional[str] = env.get("WP_ADMIN_USER") or None
        self.wp_app_password: Optional[str] = env.get("WP_APP_PASSWORD") or None
        self.unsplash_key: Optional[str] = env.get("UNSPLASH_ACCESS_KEY") or None
        self.pexels_key: Optional[str] = env.get("PEXELS_API_KEY") or None
        self.adsense_pub_id: Optional[str] = env.get("ADSENSE_PUB_ID") or None
        # Cloudflare fronts the Hostinger origin (DNS + CDN/proxy). Used for QA cache-purge
        # and SSL/HTTPS verification (Plan E). Optional for Gate 1.
        self.cloudflare_api_token: Optional[str] = env.get("CLOUDFLARE_API_TOKEN") or None
        self.cf_zone_id: Optional[str] = env.get("CF_ZONE_ID") or None
        # Database + WP-CLI runtime (for installing/managing WordPress)
        self.db_name: Optional[str] = env.get("DB_NAME") or None
        self.db_user: Optional[str] = env.get("DB_USER") or None
        self.db_password: Optional[str] = env.get("DB_PASSWORD") or None
        self.db_host: str = env.get("DB_HOST") or "localhost"
        self.wp_cli_php: Optional[str] = env.get("WP_CLI_PHP") or None
        self.wp_admin_password: Optional[str] = env.get("WP_ADMIN_PASSWORD") or None

    @property
    def has_ssh(self) -> bool:
        return bool(self.ssh_host and self.ssh_user and (self.ssh_key or self.ssh_pass))

    @property
    def has_rest(self) -> bool:
        return bool(self.wp_url and self.wp_admin_user and self.wp_app_password)

    def validate(self) -> None:
        if not (self.has_ssh or self.has_rest):
            raise ConfigError(
                "No usable control channel. Provide SSH (SSH_HOST+SSH_USER+SSH_KEY/SSH_PASS) "
                "or REST (WP_URL+WP_ADMIN_USER+WP_APP_PASSWORD) in secrets.env."
            )


def load_config(env_path: Optional[str] = None) -> Config:
    path = Path(env_path) if env_path else DEFAULT_ENV
    values = dict(os.environ)
    if path.exists():
        values.update({k: v for k, v in dotenv_values(path).items() if v is not None})
    return Config(values)
