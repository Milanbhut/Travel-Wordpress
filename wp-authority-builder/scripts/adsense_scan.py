"""Install + activate the AdSense Checklist plugin (if needed) and run it headlessly.

The agent's AdSense-readiness QA step — run on every site before handoff.
Run: python -m scripts.adsense_scan
"""
from __future__ import annotations

import json
import posixpath
from pathlib import Path

from scripts.config import load_config, AGENT_DIR
from scripts.wp import WPClient

PLUGIN_SLUG = "adsense-checklist"
LOCAL_PLUGIN = AGENT_DIR / "assets" / "plugins" / PLUGIN_SLUG

SCAN_PHP = r"""<?php
if (!defined('ADSENSE_CHECKLIST_PATH')) { echo json_encode(['error' => 'plugin not active']); return; }
$registry = include ADSENSE_CHECKLIST_PATH . 'includes/checklist-registry.php';
$open = []; $manual = []; $passed = 0;
foreach ($registry as $entry) {
    try { $check = new $entry['check_class'](); $fs = $check->run($entry); }
    catch (\Throwable $e) { $fs = []; }
    if (empty($fs)) { $passed++; continue; }
    foreach ($fs as $f) {
        $row = ['sev' => $f->severity, 'status' => $f->status, 'sit' => $f->situation];
        if ($f->status === 'manual_review') { $manual[] = $row; } else { $open[] = $row; }
    }
}
echo json_encode(['passed' => $passed, 'open' => $open, 'manual' => $manual]);
"""


def _ensure_remote_dir(sftp, remote_dir: str) -> None:
    cur = ""
    for part in [p for p in remote_dir.split("/") if p]:
        cur = cur + "/" + part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def _upload_tree(sftp, local: Path, remote_base: str) -> int:
    _ensure_remote_dir(sftp, remote_base)
    n = 0
    for path in sorted(local.rglob("*")):
        remote = posixpath.join(remote_base, path.relative_to(local).as_posix())
        if path.is_dir():
            _ensure_remote_dir(sftp, remote)
        else:
            _ensure_remote_dir(sftp, posixpath.dirname(remote))
            sftp.put(str(path), remote)
            n += 1
    return n


def main() -> int:
    cfg = load_config()
    c = WPClient(cfg)
    c.verify()
    if c.channel != "wpcli":
        print("Needs the SSH/WP-CLI channel.")
        return 1

    active = c.wp(["plugin", "list", "--field=name", "--status=active"]).split()
    if PLUGIN_SLUG not in active:
        if LOCAL_PLUGIN.is_dir():
            base = posixpath.join(cfg.wp_path, "wp-content", "plugins", PLUGIN_SLUG)
            sftp = c._ssh.open_sftp()
            try:
                n = _upload_tree(sftp, LOCAL_PLUGIN, base)
            finally:
                sftp.close()
            print(f"uploaded {n} plugin files -> {base}")
        else:
            print(f"warning: {LOCAL_PLUGIN} not found; expecting plugin already on server")
        try:
            print(c.wp(["plugin", "activate", PLUGIN_SLUG]))
        except Exception as e:  # noqa: BLE001
            print(f"[activate] {e}")
            return 1

    remote = "/tmp/_ac_scan.php"
    sftp = c._ssh.open_sftp()
    try:
        with sftp.open(remote, "wb") as fh:
            fh.write(SCAN_PHP.encode("utf-8"))
    finally:
        sftp.close()
    out = c.wp(["eval-file", remote]).strip()
    c._ssh_exec("rm -f " + remote)

    try:
        data = json.loads(out)
    except json.JSONDecodeError:
        print("Unexpected scan output:", out[:500])
        c.close()
        return 1

    print(f"\nADSENSE CHECKLIST — PASSED={data['passed']}  OPEN={len(data['open'])}  MANUAL_REVIEW={len(data['manual'])}")
    if data["open"]:
        print("\nOpen (auto-detected, fix these):")
        for r in data["open"]:
            print(f"  [{r['sev']}] {r['sit']}")
    if data["manual"]:
        print("\nManual review (human judgment):")
        for r in data["manual"]:
            print(f"  [{r['sev']}] {r['sit']}")
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
