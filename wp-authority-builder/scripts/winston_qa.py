"""Winston plagiarism gate for generated articles.

WHY IT IS SPLIT THIS WAY: the Winston scan runs through the winston-ai MCP tool
(plagiarism-detection), which is the BUILD AGENT's tool, not a Python API this script
can call. So this script does the deterministic halves - clean the article text
(prepare), and turn the agent's recorded scores into a pass/flag report - and the agent
runs the MCP plagiarism scan in between.

SCOPE (decided 2026-06-23):
  * Plagiarism is the ONE enforceable gate, and it is gated on a SAMPLE to control cost.
    Scanning all ~90 articles is ~216k credits (2 cr/word) - too expensive. Instead, the
    validation sample (the first 4-6 articles, one per archetype) is scanned BEFORE the
    rest are written. The articles all come from the same model + style guide, so a clean
    sample predicts a clean full set; only the sample is paid for (~6 articles/build).
  * AI detection is OFF THE TABLE. Google AdSense judges originality (this plagiarism
    result) + helpfulness, not a third-party AI-detector score, and LLM prose cannot be
    made to pass such a detector anyway. So this QA does not run or report an AI score -
    only plagiarism.

Run:
  python -m scripts.winston_qa --prepare --files content/<slug>.html [content/<slug>.html ...]
      The SAMPLE GATE. Strips each written content file to clean text
      -> state/winston/<n>_<slug>.txt, writes manifest.json, prints the scan checklist.
      Use this on the validation sample before fanning out the remaining articles.
  python -m scripts.winston_qa --prepare [--all | --sample N]
      Optional post-build spot-check: pulls PUBLISHED posts (default 6 spread evenly,
      --all for every post) and strips them the same way.
  python -m scripts.winston_qa --report
      Joins results.json + manifest -> state/winston/report.md + prints the table and the
      plagiarism slugs to REGENERATE.

In every mode the agent then scans each .txt via the MCP and writes state/winston/results.json
as a list of {"id": <int>, "plagiarism_score": <0-100>, "plagiarism_sources": <int>}.
"""
from __future__ import annotations

import html as _html
import json
import re
import sys
from pathlib import Path

from scripts.config import AGENT_DIR, load_config
from scripts.wp import WPClient

QA_DIR = AGENT_DIR / "state" / "winston"
MANIFEST = QA_DIR / "manifest.json"
RESULTS = QA_DIR / "results.json"
REPORT = QA_DIR / "report.md"

# threshold (percent): a article above this, or with any matched source, is regenerated
PLAGIARISM_FLAG = 5.0


def html_to_text(raw: str) -> str:
    """Strip post HTML to readable prose, keeping block + table boundaries (so tables and
    callout labels do not run together, which would skew a scan)."""
    s = re.sub(r"(?s)<!--.*?-->", " ", raw)  # drop comments (e.g. the <!--excerpt:--> lead)
    s = re.sub(r"(?is)<(script|style)[^>]*>.*?</\1>", " ", s)
    s = re.sub(r"(?i)<br\s*/?>", "\n", s)
    s = re.sub(r"(?i)</(p|h[1-6]|li|tr|blockquote|div|section|figcaption)>", "\n\n", s)
    s = re.sub(r"(?i)</(td|th)>", " | ", s)
    s = re.sub(r"(?i)</(strong|em|b|i|u|a|span|code|mark|small)>", " ", s)
    s = re.sub(r"(?s)<[^>]+>", "", s)
    s = _html.unescape(s)
    s = re.sub(r"[ \t]+", " ", s)
    s = re.sub(r"\s+([.,;:!?])", r"\1", s)
    s = re.sub(r" *\n *", "\n", s)
    s = re.sub(r"\n{3,}", "\n\n", s)
    return s.strip()


def _slug(name: str) -> str:
    return re.sub(r"[^a-z0-9-]+", "-", (name or "").lower()).strip("-")[:60]


def _emit(manifest: list) -> int:
    QA_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.write_text(json.dumps(manifest, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"prepared {len(manifest)} article(s) -> {QA_DIR}")
    print("\nNEXT (build agent): scan each .txt for PLAGIARISM and write results.json:")
    print("  - mcp__winston-ai__plagiarism-detection(text=<file contents>)")
    print("      -> plagiarism_score = result.score ; plagiarism_sources = number of matched sources")
    print(f"  write {RESULTS} = [{{\"id\":N,\"plagiarism_score\":F,\"plagiarism_sources\":N}}, ...]")
    print("  (no AI-detection scan - that is off the table; plagiarism is the only gate)")
    print("  then: python -m scripts.winston_qa --report")
    print("\nFiles to scan:")
    for m in manifest:
        print(f"  {m['id']:>5}  {m['words']:>4}w  {m['title']}  [{m['txt']}]")
    return 0


def prepare_files(paths: list[str]) -> int:
    """Sample gate: scan written content/<slug>.html files directly, before publishing."""
    files = []
    for raw_path in paths:
        p = Path(raw_path)
        if not p.is_absolute():
            p = AGENT_DIR / raw_path
        if not p.exists():
            print(f"[skip] not found: {p}")
            continue
        files.append(p)
    if not files:
        print("No content files found for the selection.")
        return 1

    QA_DIR.mkdir(parents=True, exist_ok=True)
    manifest = []
    for i, p in enumerate(files, 1):
        text = html_to_text(p.read_text(encoding="utf-8"))
        slug = _slug(p.stem)
        out = QA_DIR / f"{i}_{slug}.txt"
        out.write_text(text, encoding="utf-8")
        manifest.append({
            "id": i,
            "slug": slug,
            "title": p.stem,
            "words": len(text.split()),
            "txt": str(out),
            "src": str(p),
        })
    return _emit(manifest)


def prepare(sample: int | None) -> int:
    """Optional post-build spot-check: pull PUBLISHED posts and strip them."""
    client = WPClient(load_config())
    client.verify()
    raw = client.wp([
        "post", "list", "--post_type=post", "--post_status=publish",
        "--posts_per_page=-1", "--orderby=ID", "--order=ASC",
        "--fields=ID,post_name,post_title,post_content", "--format=json",
    ])
    client.close()
    posts = json.loads(raw) if raw.strip() else []
    if not posts:
        print("No published posts found.")
        return 1

    if sample and sample < len(posts):
        step = len(posts) / sample
        posts = [posts[int(i * step)] for i in range(sample)]

    QA_DIR.mkdir(parents=True, exist_ok=True)
    manifest = []
    for p in posts:
        text = html_to_text(p.get("post_content") or "")
        path = QA_DIR / f"{p['ID']}_{_slug(p.get('post_name') or str(p.get('ID')))}.txt"
        path.write_text(text, encoding="utf-8")
        manifest.append({
            "id": int(p["ID"]),
            "slug": p.get("post_name", ""),
            "title": p.get("post_title", ""),
            "words": len(text.split()),
            "txt": str(path),
        })
    return _emit(manifest)


def report() -> int:
    if not MANIFEST.exists() or not RESULTS.exists():
        print(f"Missing {MANIFEST if not MANIFEST.exists() else RESULTS}. Run --prepare then have the agent write results.json.")
        return 2
    manifest = {m["id"]: m for m in json.loads(MANIFEST.read_text(encoding="utf-8"))}
    results = json.loads(RESULTS.read_text(encoding="utf-8"))

    rows, regenerate = [], []
    for r in results:
        m = manifest.get(int(r["id"]))
        if not m:
            continue
        plag = float(r.get("plagiarism_score", 0))
        sources = int(r.get("plagiarism_sources", 0))
        plag_fail = plag > PLAGIARISM_FLAG or sources > 0
        verdict = "REGENERATE" if plag_fail else "ok"
        rows.append((m, plag, sources, verdict))
        if plag_fail:
            regenerate.append(m["slug"])

    clean = len(rows) - len(regenerate)
    lines = ["# Winston plagiarism report", ""]
    lines.append("| ID | Article | Words | Plagiarism | Sources | Gate |")
    lines.append("|---|---|---|---|---|---|")
    for m, plag, sources, verdict in rows:
        lines.append(f"| {m['id']} | {m['title']} | {m['words']} | {plag:.1f}% | {sources} | {verdict} |")
    lines += [
        "",
        f"**Plagiarism gate:** {clean}/{len(rows)} clean. {len(regenerate)} article(s) over "
        f"{PLAGIARISM_FLAG}% or with a matched source -> regenerate, re-scan until 0% copied.",
        "",
        "Scope: this gates the validation SAMPLE; a clean sample predicts a clean full set (same model + "
        "style guide), so all ~90 are not scanned. AI detection is not run - AdSense judges originality "
        "(this result) + helpfulness, not a third-party AI score.",
    ]
    REPORT.write_text("\n".join(lines), encoding="utf-8")

    print("\n".join(lines))
    print(f"\nwrote {REPORT}")
    if regenerate:
        print("REGENERATE these slugs (plagiarism), then re-scan before fanning out:")
        for s in regenerate:
            print(f"  - {s}")
    return 0


def main() -> int:
    args = sys.argv[1:]
    if "--report" in args:
        return report()
    if "--prepare" in args:
        if "--files" in args:
            i = args.index("--files")
            paths = [a for a in args[i + 1:] if not a.startswith("--")]
            if not paths:
                print("usage: python -m scripts.winston_qa --prepare --files content/<slug>.html [...]")
                return 2
            return prepare_files(paths)
        sample = None
        if "--all" not in args:
            sample = 6
            if "--sample" in args:
                j = args.index("--sample")
                if j + 1 < len(args):
                    sample = int(args[j + 1])
        return prepare(sample)
    print("usage: python -m scripts.winston_qa --prepare --files <content/slug.html ...> | "
          "--prepare [--all | --sample N] | --report")
    return 2


if __name__ == "__main__":
    raise SystemExit(main())
