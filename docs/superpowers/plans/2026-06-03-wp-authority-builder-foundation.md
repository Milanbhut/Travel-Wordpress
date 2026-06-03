# WP Authority Builder — Plan A: Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the tested foundation of the agent — repo scaffold, config loader, a resumable state manifest, and the WordPress control channel (WP-CLI over SSH with a REST API fallback) — so every later plan has a verified way to talk to WordPress and track progress.

**Architecture:** A Python package `scripts/` inside the agent directory `D:\1Agent1\wp-authority-builder\`. `config.py` loads secrets and reports which control channels are usable. `wp.py` exposes a `WPClient` that prefers WP-CLI over SSH (via paramiko) and falls back to the WP REST API. `state.py` persists per-site build progress to JSON so runs are resumable and idempotent. Unit tests mock SSH/HTTP so the suite needs no live server; a final manual smoke test uses real credentials.

**Tech Stack:** Python 3.11+, pytest, paramiko (SSH), requests (REST), python-dotenv (config). Windows 11 host; WP-CLI commands execute remotely on Hostinger over SSH.

**Source-of-truth vs install:** Develop and test in the repo `D:\1Agent1\wp-authority-builder\`. A later plan adds `deploy.ps1` to copy the finished skill to `C:\Users\Home\.claude\skills\wp-authority-builder\` for "invoke from anywhere" use. The first full build (gates G1–G3) runs from the repo. Runtime files (`config/secrets.env`, `state/`) are gitignored and live wherever the agent runs.

---

## File Structure (locked here)

```
D:\1Agent1\
  .gitignore                              # ignores venv, secrets, state, caches
  docs/superpowers/...                    # spec + plans (already present)
  wp-authority-builder/
    pytest.ini                            # makes `scripts` importable
    requirements.txt
    SKILL.md                              # orchestrator stub (filled by later plans)
    scripts/
      __init__.py
      config.py                           # Config + load_config
      state.py                            # Manifest
      wp.py                               # WPClient (WP-CLI/SSH + REST fallback)
    tests/
      test_config.py
      test_state.py
      test_wp.py
    config/
      secrets.env.example                 # template; real secrets.env is gitignored
    assets/                               # (empty; theme + templates land in later plans)
    references/                           # (empty; design-system etc. land in later plans)
    state/                                # (runtime manifests; gitignored)
```

---

### Task 0: Repo scaffold, dependencies, git

**Files:**
- Create: `D:\1Agent1\.gitignore`
- Create: `D:\1Agent1\wp-authority-builder\requirements.txt`
- Create: `D:\1Agent1\wp-authority-builder\pytest.ini`
- Create: `D:\1Agent1\wp-authority-builder\scripts\__init__.py`
- Create: `D:\1Agent1\wp-authority-builder\config\secrets.env.example`

- [ ] **Step 1: Create the directory tree**

Run (PowerShell, from `D:\1Agent1`):
```powershell
New-Item -ItemType Directory -Force -Path `
  "D:\1Agent1\wp-authority-builder\scripts", `
  "D:\1Agent1\wp-authority-builder\tests", `
  "D:\1Agent1\wp-authority-builder\config", `
  "D:\1Agent1\wp-authority-builder\assets", `
  "D:\1Agent1\wp-authority-builder\references", `
  "D:\1Agent1\wp-authority-builder\state" | Out-Null
```
Expected: the six folders exist (no error).

- [ ] **Step 2: Write `.gitignore`**

`D:\1Agent1\.gitignore`:
```gitignore
# Python
.venv/
__pycache__/
*.pyc
.pytest_cache/

# Secrets & runtime
wp-authority-builder/config/secrets.env
wp-authority-builder/state/*.json

# OS
Thumbs.db
.DS_Store
```

- [ ] **Step 3: Write `requirements.txt`**

`D:\1Agent1\wp-authority-builder\requirements.txt`:
```text
paramiko==3.5.0
requests==2.32.3
python-dotenv==1.0.1
pytest==8.3.4
```

- [ ] **Step 4: Write `pytest.ini`** (so tests can `import scripts.*`)

`D:\1Agent1\wp-authority-builder\pytest.ini`:
```ini
[pytest]
pythonpath = .
testpaths = tests
```

- [ ] **Step 5: Create the package marker**

`D:\1Agent1\wp-authority-builder\scripts\__init__.py`:
```python
"""WP Authority Builder — automation scripts package."""
```

- [ ] **Step 6: Write the secrets template**

`D:\1Agent1\wp-authority-builder\config\secrets.env.example`:
```text
# Copy to secrets.env and fill in. secrets.env is gitignored.

# --- SSH / WP-CLI (primary control channel) ---
SSH_HOST=your-server.hostinger.com
SSH_PORT=22
SSH_USER=u123456789
# Provide ONE of SSH_KEY (path to private key) or SSH_PASS:
SSH_KEY=
SSH_PASS=
# Remote WordPress root used for `wp --path=...` (find with: ls ~/domains/<domain>/public_html):
WP_PATH=/home/u123456789/domains/example.com/public_html

# --- WP REST API (fallback control channel) ---
WP_URL=https://example.com
WP_ADMIN_USER=admin
WP_APP_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx

# --- Media APIs ---
UNSPLASH_ACCESS_KEY=
PEXELS_API_KEY=

# --- AdSense ---
ADSENSE_PUB_ID=
```

- [ ] **Step 7: Create the venv and install dependencies**

Run (PowerShell, from `D:\1Agent1\wp-authority-builder`):
```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```
Expected: `Successfully installed paramiko-3.5.0 ... pytest-8.3.4 ...`

- [ ] **Step 8: Initialize git and make the first commit**

> Skip this step if you opted out of local git. Otherwise run from `D:\1Agent1`:
```powershell
git init
git add .gitignore docs wp-authority-builder
git commit -m "chore: scaffold wp-authority-builder foundation"
```
Expected: a commit is created; `git status` shows a clean tree (venv/secrets ignored).

---

### Task 1: `config.py` — secrets loader + channel detection

**Files:**
- Create: `D:\1Agent1\wp-authority-builder\scripts\config.py`
- Test: `D:\1Agent1\wp-authority-builder\tests\test_config.py`

- [ ] **Step 1: Write the failing tests**

`tests/test_config.py`:
```python
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `python -m pytest tests/test_config.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'scripts.config'`.

- [ ] **Step 3: Write `scripts/config.py`**

```python
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
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `python -m pytest tests/test_config.py -v`
Expected: PASS — `5 passed`.

- [ ] **Step 5: Commit**

```powershell
git add wp-authority-builder/scripts/config.py wp-authority-builder/tests/test_config.py
git commit -m "feat: config loader with SSH/REST channel detection"
```

---

### Task 2: `state.py` — resumable build manifest

**Files:**
- Create: `D:\1Agent1\wp-authority-builder\scripts\state.py`
- Test: `D:\1Agent1\wp-authority-builder\tests\test_state.py`

- [ ] **Step 1: Write the failing tests**

`tests/test_state.py`:
```python
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `python -m pytest tests/test_state.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'scripts.state'`.

- [ ] **Step 3: Write `scripts/state.py`**

```python
"""Per-site build manifest: resumable, idempotent unit tracking."""
from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Optional, Union


class Manifest:
    def __init__(self, path: Union[str, Path]):
        self.path = Path(path)
        self.data: dict[str, Any] = {"domain": None, "phase": None, "units": {}, "articles": {}}
        if self.path.exists():
            self.data = json.loads(self.path.read_text(encoding="utf-8"))

    def save(self) -> None:
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self.path.write_text(
            json.dumps(self.data, indent=2, ensure_ascii=False), encoding="utf-8"
        )

    def set_unit(self, key: str, status: str) -> None:
        self.data["units"][key] = status
        self.save()

    def unit_status(self, key: str) -> Optional[str]:
        return self.data["units"].get(key)

    def is_done(self, key: str) -> bool:
        return self.data["units"].get(key) == "done"

    def set_meta(self, key: str, value: Any) -> None:
        self.data[key] = value
        self.save()
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `python -m pytest tests/test_state.py -v`
Expected: PASS — `4 passed`.

- [ ] **Step 5: Commit**

```powershell
git add wp-authority-builder/scripts/state.py wp-authority-builder/tests/test_state.py
git commit -m "feat: resumable build manifest"
```

---

### Task 3: `wp.py` — WordPress control channel (Gate 1)

**Files:**
- Create: `D:\1Agent1\wp-authority-builder\scripts\wp.py`
- Test: `D:\1Agent1\wp-authority-builder\tests\test_wp.py`

- [ ] **Step 1: Write the failing tests** (mock SSH + HTTP — no live server needed)

`tests/test_wp.py`:
```python
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `python -m pytest tests/test_wp.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'scripts.wp'`.

- [ ] **Step 3: Write `scripts/wp.py`**

```python
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
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `python -m pytest tests/test_wp.py -v`
Expected: PASS — `6 passed`.

- [ ] **Step 5: Run the full suite**

Run: `python -m pytest -v`
Expected: PASS — `15 passed` (5 config + 4 state + 6 wp).

- [ ] **Step 6: Commit**

```powershell
git add wp-authority-builder/scripts/wp.py wp-authority-builder/tests/test_wp.py
git commit -m "feat: WordPress control channel (WP-CLI/SSH + REST fallback)"
```

---

### Task 4: `verify_connection.py` — live smoke test + SKILL.md stub

This is the only step that touches your real server. It is run manually, once `config/secrets.env` is filled.

**Files:**
- Create: `D:\1Agent1\wp-authority-builder\scripts\verify_connection.py`
- Create: `D:\1Agent1\wp-authority-builder\SKILL.md`

- [ ] **Step 1: Write the connection smoke-test script**

`scripts/verify_connection.py`:
```python
"""Manual smoke test: load secrets.env, verify the control channel, print WP status.

Usage (from wp-authority-builder/, venv active):
    python -m scripts.verify_connection
"""
from __future__ import annotations

from scripts.config import load_config
from scripts.wp import WPClient, WPError


def main() -> int:
    cfg = load_config()
    try:
        cfg.validate()
    except Exception as e:  # ConfigError
        print(f"[CONFIG] {e}")
        return 2

    client = WPClient(cfg)
    try:
        info = client.verify()
    except WPError as e:
        print(f"[FAIL] {e}")
        return 1

    print(f"[OK] control channel = {info['channel']}")
    if info["channel"] == "wpcli":
        print(info["info"])
        # Prove a real command works end-to-end:
        try:
            version = client.wp(["core", "version"])
            print(f"[OK] WordPress core version = {version}")
        except WPError as e:
            print(f"[WARN] `wp core version` failed: {e}")
    else:
        print(f"REST namespaces: {info['namespaces']}")
    client.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
```

- [ ] **Step 2: Write the orchestrator stub** (`SKILL.md` — later plans flesh out the phases)

`wp-authority-builder/SKILL.md`:
```markdown
---
name: wp-authority-builder
description: Build a premium, AdSense-ready WordPress authority blog (90 articles across 6 categories, custom theme, real authors, legal pages, full SEO/schema, QA) on a Hostinger site from a single niche or title. Use when the user wants to create a new WordPress blog from a niche.
---

# WP Authority Builder

Given a niche or title, drive a blank Hostinger WordPress install (over WP-CLI/SSH, REST fallback)
to a finished, AdSense-ready authority blog. Spec: `docs/superpowers/specs/2026-06-03-wp-authority-builder-design.md`.

## Prerequisites
1. A blank WordPress install is standing on the target domain (user-provisioned).
2. `config/secrets.env` is filled (copy from `config/secrets.env.example`).
3. The venv is created and dependencies installed (`requirements.txt`).

## Phase 1 — Connect & verify (Gate 1)
Run: `python -m scripts.verify_connection`
- Must print `[OK] control channel = wpcli` (or `rest` as graceful fallback) before any build proceeds.

> Phases 0, 0.5, 2–10 are added by Plans B–E. Do not improvise them; follow the plan docs.
```

- [ ] **Step 3: Verify scripts import cleanly (no live creds needed)**

Run: `python -c "import scripts.config, scripts.state, scripts.wp, scripts.verify_connection; print('imports OK')"`
Expected: `imports OK`

- [ ] **Step 4: (Manual, requires your creds) Run the live smoke test**

Prereq: copy `config/secrets.env.example` to `config/secrets.env` and fill it in.
Run: `python -m scripts.verify_connection`
Expected: `[OK] control channel = wpcli` followed by `[OK] WordPress core version = 7.0` (or your version).
If it prints `[FAIL] No usable control channel`, the SSH/REST credentials need fixing before Plan B.

- [ ] **Step 5: Commit**

```powershell
git add wp-authority-builder/scripts/verify_connection.py wp-authority-builder/SKILL.md
git commit -m "feat: live connection smoke test + skill orchestrator stub"
```

---

## Self-Review

**Spec coverage (Plan A's slice of the spec):**
- §3 architecture (scripts package, config/state/wp boundaries) → Tasks 0–3 ✓
- §4 configuration (`secrets.env` keys) → Task 0 Step 6, Task 1 ✓
- §5 control channel + Gate 1 (WP-CLI primary, REST fallback, no silent proceed) → Task 3 `verify()`, Task 4 smoke test ✓
- §9 resumability/state manifest → Task 2 ✓
- Cloudflare/cache, theme, content, images, SEO, QA → **out of scope for Plan A** (Plans B–E).

**Placeholder scan:** No TBD/TODO; every code/test step contains complete content. ✓

**Type consistency:** `Config` accepts a `Mapping`; `WPClient(config)` uses `cfg.has_ssh/has_rest/ssh_*/wp_*`; `verify()` sets `self.channel` to `"wpcli"|"rest"`; `wp()` checks `channel == "wpcli"`; `Manifest.set_unit/is_done/unit_status/set_meta` names match across `state.py` and `test_state.py`. ✓

---

## Definition of Done (Plan A)
- [ ] `python -m pytest -v` → `15 passed`.
- [ ] `import scripts.*` succeeds.
- [ ] (With real creds) `python -m scripts.verify_connection` prints `[OK] control channel = wpcli` and a WordPress version — **this is the real Gate 1**, and the green light to start Plan B.
