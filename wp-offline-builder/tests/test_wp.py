"""Tests for the LOCAL (offline) WPClient.

This WPClient drives a WordPress install on the local machine via a local PHP
binary + wp-cli.phar (no SSH/SFTP). Tests cover the command builder, channel
verification, the wp() runner, and the real-filesystem helpers.
"""
import pytest

from scripts.config import Config
from scripts.wp import WPClient, WPError


def _local_cfg(tmp_path, **overrides):
    """Build a Config wired for a local WP-CLI channel under tmp_path."""
    env = {
        "LOCAL_PHP": r"C:\php\php.exe",
        "WP_CLI_PHAR": r"C:\wp\wp-cli.phar",
        "WP_PATH": str(tmp_path),
    }
    env.update(overrides)
    return Config(env)


# --- (a) _wp_cmd builds the local PHP + phar + --path + args command -----------

def test_wp_cmd_builds_local_php_phar_path_args(tmp_path):
    cfg = _local_cfg(tmp_path)
    c = WPClient(cfg)
    cmd = c._wp_cmd(["post", "list", "--format=ids"])
    assert cmd == [
        cfg.local_php,
        cfg.wp_cli_phar,
        f"--path={cfg.wp_path}",
        "post",
        "list",
        "--format=ids",
    ]


def test_wp_cmd_omits_path_flag_when_wp_path_empty():
    cfg = Config({"LOCAL_PHP": r"C:\php\php.exe", "WP_CLI_PHAR": r"C:\wp\wp-cli.phar"})
    c = WPClient(cfg)
    cmd = c._wp_cmd(["--info"])
    assert cmd == [cfg.local_php, cfg.wp_cli_phar, "--info"]


# --- (b) verify() sets channel='wpcli' on a successful local --info -------------

def test_verify_sets_wpcli_channel(monkeypatch, tmp_path):
    c = WPClient(_local_cfg(tmp_path))
    captured = {}

    def fake_run(self, cmd):
        captured["cmd"] = cmd
        return (0, "WP-CLI 2.10.0\nPHP 8.2.0", "")

    monkeypatch.setattr(WPClient, "_run", fake_run)
    info = c.verify()
    assert info["channel"] == "wpcli"
    assert c.channel == "wpcli"
    # verify() runs through the local _wp_cmd builder
    assert captured["cmd"][0] == c.cfg.local_php
    assert captured["cmd"][1] == c.cfg.wp_cli_phar


# --- (c) verify() raises WPError when WP-CLI fails and no REST is configured ----

def test_verify_raises_when_wpcli_fails_and_no_rest(monkeypatch, tmp_path):
    c = WPClient(_local_cfg(tmp_path))
    monkeypatch.setattr(WPClient, "_run", lambda self, cmd: (1, "", "php not found"))
    assert not c.cfg.has_rest
    with pytest.raises(WPError):
        c.verify()
    assert c.channel is None


# --- (d) wp() returns stdout on success, raises WPError on non-zero exit --------

def test_wp_returns_stdout_on_success(monkeypatch, tmp_path):
    c = WPClient(_local_cfg(tmp_path))
    c.channel = "wpcli"
    captured = {}

    def fake_run(self, cmd):
        captured["cmd"] = cmd
        return (0, "  1 2 3\n", "")

    monkeypatch.setattr(WPClient, "_run", fake_run)
    out = c.wp(["post", "list", "--format=ids"])
    assert out == "1 2 3"  # stdout, stripped
    assert captured["cmd"] == c._wp_cmd(["post", "list", "--format=ids"])


def test_wp_raises_on_nonzero_exit(monkeypatch, tmp_path):
    c = WPClient(_local_cfg(tmp_path))
    c.channel = "wpcli"
    monkeypatch.setattr(WPClient, "_run", lambda self, cmd: (1, "", "boom"))
    with pytest.raises(WPError):
        c.wp(["bad", "command"])


def test_wp_requires_wpcli_channel(tmp_path):
    c = WPClient(_local_cfg(tmp_path))  # channel not set by verify()
    with pytest.raises(WPError):
        c.wp(["post", "list"])


# --- (e) real-filesystem behavior of fs_path/put_bytes/stage_tmp/eval_php -------

def test_fs_path_joins_under_wp_path(tmp_path):
    import os

    c = WPClient(_local_cfg(tmp_path))
    p = c.fs_path("wp-content/themes/x/style.css")
    assert p == os.path.join(str(tmp_path), "wp-content", "themes", "x", "style.css")


def test_put_bytes_writes_file_and_creates_parents(tmp_path):
    from pathlib import Path

    c = WPClient(_local_cfg(tmp_path))
    c.put_bytes("wp-content/themes/x/style.css", b"body{}")
    dst = Path(c.fs_path("wp-content/themes/x/style.css"))
    assert dst.exists()
    assert dst.read_bytes() == b"body{}"


def test_stage_tmp_writes_data_and_returns_path(tmp_path):
    from pathlib import Path

    c = WPClient(_local_cfg(tmp_path))
    path = c.stage_tmp("_thing.php", b"<?php echo 1;")
    p = Path(path)
    assert p.exists()
    assert p.read_bytes() == b"<?php echo 1;"
    # cleanup is a no-op-safe removal
    c.unstage_tmp(path)
    assert not p.exists()


def test_eval_php_stages_runs_and_cleans_up(monkeypatch, tmp_path):
    """eval_php should stage the PHP, hand the staged path to wp(), then remove it."""
    from pathlib import Path

    c = WPClient(_local_cfg(tmp_path))
    seen = {}

    def fake_wp(self, args):
        # args == ["eval-file", <staged path>]; the file must exist at call time.
        assert args[0] == "eval-file"
        staged = args[1]
        seen["path"] = staged
        seen["existed"] = Path(staged).exists()
        return "OK"

    monkeypatch.setattr(WPClient, "wp", fake_wp)
    result = c.eval_php("<?php echo 'OK';")
    assert result == "OK"
    assert seen["existed"] is True
    # staged temp file is cleaned up afterward
    assert not Path(seen["path"]).exists()
