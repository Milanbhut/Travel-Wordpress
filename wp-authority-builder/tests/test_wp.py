import pytest
from scripts.config import Config
from scripts.wp import WPClient, WPError


def _ssh_cfg():
    return Config({
        "SSH_HOST": "h", "SSH_USER": "u", "SSH_PASS": "p",
        "WP_PATH": "/home/u/public_html",
    })


def test_verify_prefers_wpcli(monkeypatch):
    c = WPClient(_ssh_cfg())
    monkeypatch.setattr(c, "_ssh_exec", lambda cmd: (0, "WP-CLI 2.10.0\nPHP 8.2", ""))
    info = c.verify()
    assert info["channel"] == "wpcli"
    assert c.channel == "wpcli"


def test_verify_falls_back_to_rest(monkeypatch):
    cfg = Config({"WP_URL": "https://x.com", "WP_ADMIN_USER": "a", "WP_APP_PASSWORD": "pw"})
    c = WPClient(cfg)

    class FakeResp:
        ok = True
        content = b"{}"
        def json(self):
            return {"namespaces": ["wp/v2"]}

    monkeypatch.setattr("scripts.wp.requests.get", lambda *a, **k: FakeResp())
    info = c.verify()
    assert info["channel"] == "rest"
    assert c.channel == "rest"


def test_verify_raises_without_any_channel():
    c = WPClient(Config({}))
    with pytest.raises(WPError):
        c.verify()


def test_wp_builds_and_runs_command(monkeypatch):
    c = WPClient(_ssh_cfg())
    c.channel = "wpcli"
    captured = {}

    def fake_exec(cmd):
        captured["cmd"] = cmd
        return (0, "ok-output", "")

    monkeypatch.setattr(c, "_ssh_exec", fake_exec)
    out = c.wp(["post", "list", "--format=ids"])
    assert out == "ok-output"
    assert captured["cmd"] == "wp --path=/home/u/public_html post list --format=ids"


def test_wp_raises_on_nonzero_exit(monkeypatch):
    c = WPClient(_ssh_cfg())
    c.channel = "wpcli"
    monkeypatch.setattr(c, "_ssh_exec", lambda cmd: (1, "", "boom"))
    with pytest.raises(WPError):
        c.wp(["bad", "command"])


def test_wp_requires_wpcli_channel():
    c = WPClient(_ssh_cfg())  # channel not set
    with pytest.raises(WPError):
        c.wp(["post", "list"])
