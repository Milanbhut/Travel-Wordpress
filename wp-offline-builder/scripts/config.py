"""Configuration loader: reads secrets and reports usable control channels."""
from __future__ import annotations

import os
from pathlib import Path
from typing import Mapping, Optional

from dotenv import dotenv_values

AGENT_DIR = Path(__file__).resolve().parents[1]      # scripts/ -> wp-offline-builder/
CONFIG_DIR = AGENT_DIR / "config"
DEFAULT_ENV = CONFIG_DIR / "secrets.env"


class ConfigError(Exception):
    """Raised when no usable control channel is configured."""


class Config:
    def __init__(self, env: Mapping[str, str]):
        self.wp_path: str = env.get("WP_PATH") or ""
        self.wp_url: Optional[str] = env.get("WP_URL") or None
        self.wp_admin_user: Optional[str] = env.get("WP_ADMIN_USER") or None
        self.wp_app_password: Optional[str] = env.get("WP_APP_PASSWORD") or None
        self.unsplash_key: Optional[str] = env.get("UNSPLASH_ACCESS_KEY") or None
        self.pexels_key: Optional[str] = env.get("PEXELS_API_KEY") or None
        self.adsense_pub_id: Optional[str] = env.get("ADSENSE_PUB_ID") or None
        # Database + WP-CLI runtime (for installing/managing WordPress)
        self.db_name: Optional[str] = env.get("DB_NAME") or None
        self.db_user: Optional[str] = env.get("DB_USER") or None
        self.db_password: Optional[str] = env.get("DB_PASSWORD") or None
        self.db_host: str = env.get("DB_HOST") or "localhost"
        self.wp_admin_password: Optional[str] = env.get("WP_ADMIN_PASSWORD") or None
        # --- LOCAL (offline) build runtime: WP-CLI via a local PHP (XAMPP), no SSH ---
        self.local_php: str = env.get("LOCAL_PHP") or r"C:\xampp\php\php.exe"
        self.wp_cli_phar: str = env.get("WP_CLI_PHAR") or str(AGENT_DIR / "bin" / "wp-cli.phar")
        self.mysql_bin: str = env.get("MYSQL_BIN") or r"C:\xampp\mysql\bin\mysql.exe"
        self.site_slug: str = env.get("SITE_SLUG") or "offline-site"
        self.wp_admin_email: str = env.get("WP_ADMIN_EMAIL") or "admin@example.com"

    @property
    def has_local(self) -> bool:
        return bool(self.local_php and self.wp_cli_phar and self.wp_path)

    @property
    def has_rest(self) -> bool:
        return bool(self.wp_url and self.wp_admin_user and self.wp_app_password)

    def validate(self) -> None:
        if not (self.has_local or self.has_rest):
            raise ConfigError(
                "No usable control channel. For an offline build set WP_PATH (local WordPress dir) "
                "+ LOCAL_PHP + WP_CLI_PHAR in secrets.env."
            )


def load_config(env_path: Optional[str] = None) -> Config:
    path = Path(env_path) if env_path else DEFAULT_ENV
    values = dict(os.environ)
    if path.exists():
        values.update({k: v for k, v in dotenv_values(path).items() if v is not None})
    return Config(values)
