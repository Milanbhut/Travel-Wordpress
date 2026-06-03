import pytest
from scripts.config import Config, ConfigError, load_config


def test_has_ssh_true_with_password():
    c = Config({"SSH_HOST": "h", "SSH_USER": "u", "SSH_PASS": "p"})
    assert c.has_ssh is True


def test_has_ssh_false_without_credentials():
    c = Config({"SSH_HOST": "h"})  # no user/key/pass
    assert c.has_ssh is False


def test_has_rest_true_when_complete():
    c = Config({"WP_URL": "https://x", "WP_ADMIN_USER": "a", "WP_APP_PASSWORD": "pw"})
    assert c.has_rest is True


def test_validate_raises_without_any_channel():
    with pytest.raises(ConfigError):
        Config({}).validate()


def test_load_config_reads_env_file(tmp_path):
    env = tmp_path / "secrets.env"
    env.write_text("SSH_HOST=example.com\nSSH_USER=root\nSSH_PASS=secret\n", encoding="utf-8")
    c = load_config(str(env))
    assert c.ssh_host == "example.com"
    assert c.has_ssh is True
