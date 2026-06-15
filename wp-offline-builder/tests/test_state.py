from scripts.state import Manifest


def test_roundtrip_persists_units(tmp_path):
    m = Manifest(tmp_path / "site.json")
    m.set_unit("phase1:connect", "done")
    reloaded = Manifest(tmp_path / "site.json")
    assert reloaded.is_done("phase1:connect") is True


def test_is_done_false_for_unknown(tmp_path):
    m = Manifest(tmp_path / "site.json")
    assert m.is_done("nope") is False


def test_unit_status_returns_value(tmp_path):
    m = Manifest(tmp_path / "site.json")
    m.set_unit("x", "in_progress")
    assert m.unit_status("x") == "in_progress"


def test_set_meta_persists(tmp_path):
    m = Manifest(tmp_path / "site.json")
    m.set_meta("domain", "example.com")
    assert Manifest(tmp_path / "site.json").data["domain"] == "example.com"
