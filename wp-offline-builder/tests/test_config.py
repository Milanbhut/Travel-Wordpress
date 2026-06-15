import pytest
from scripts.config import Config, ConfigError, load_config


def test_has_local_true_with_wp_path():
    # local_php + wp_cli_phar carry XAMPP defaults, so a set WP_PATH is enough.
    c = Config({"WP_PATH": r"C:\xampp\htdocs\site"})
    assert c.has_local is True


def test_has_local_false_without_wp_path():
    c = Config({})  # no WP_PATH -> no local channel
    assert c.has_local is False


def test_has_rest_true_when_complete():
    c = Config({"WP_URL": "http://localhost/site", "WP_ADMIN_USER": "a", "WP_APP_PASSWORD": "pw"})
    assert c.has_rest is True


def test_validate_raises_without_any_channel():
    with pytest.raises(ConfigError):
        Config({}).validate()


def test_load_config_reads_env_file(tmp_path):
    env = tmp_path / "secrets.env"
    env.write_text(
        "WP_PATH=C:\\xampp\\htdocs\\offline-site\nLOCAL_PHP=C:\\xampp\\php\\php.exe\n",
        encoding="utf-8",
    )
    c = load_config(str(env))
    assert c.wp_path == "C:\\xampp\\htdocs\\offline-site"
    assert c.has_local is True
