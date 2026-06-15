"""Readability measurement for article HTML (Flesch-Kincaid grade + reading ease).

Measures two bodies:
  - prose : the flowing sentences inside <p> tags (the meaningful FK target)
  - full  : all visible text with tags stripped (matches a paste-into-checker)

Run: python -m scripts.readability content/<slug>.html [more.html ...]
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

import textstat


def strip_comments(html: str) -> str:
    return re.sub(r"<!--.*?-->", " ", html, flags=re.S)


def strip_tags(html: str) -> str:
    text = re.sub(r"<[^>]+>", " ", html)
    return re.sub(r"\s+", " ", text).strip()


def paragraph_text(html: str) -> str:
    paras = re.findall(r"<p[^>]*>(.*?)</p>", html, flags=re.S | re.I)
    return " ".join(strip_tags(p) for p in paras).strip()


def measure(label: str, text: str) -> dict:
    words = len(text.split())
    sents = max(1, textstat.sentence_count(text))
    return {
        "body": label,
        "words": words,
        "sentences": sents,
        "words_per_sentence": round(words / sents, 1),
        "syll_per_word": round(textstat.syllable_count(text) / max(1, words), 2),
        "fk_grade": round(textstat.flesch_kincaid_grade(text), 1),
        "reading_ease": round(textstat.flesch_reading_ease(text), 1),
    }


def report(path: Path) -> None:
    html = strip_comments(path.read_text(encoding="utf-8"))
    prose = paragraph_text(html)
    full = strip_tags(html)
    print(f"\n=== {path.name} ===")
    for body in (measure("prose (<p> only)", prose), measure("full visible text", full)):
        flag = "" if 7 <= body["fk_grade"] <= 9 else "  <-- OUT OF 7-9"
        print(
            f"  {body['body']:<20} FK grade {body['fk_grade']:>4}  | ease {body['reading_ease']:>5}"
            f"  | {body['words']} words, {body['words_per_sentence']} w/sent, {body['syll_per_word']} syll/word{flag}"
        )


def grades(path: Path) -> tuple[float, float]:
    html = strip_comments(path.read_text(encoding="utf-8"))
    return (
        round(textstat.flesch_kincaid_grade(paragraph_text(html)), 1),
        round(textstat.flesch_kincaid_grade(strip_tags(html)), 1),
    )


def summary(folder: str = "content") -> None:
    import statistics as st

    rows = sorted(((grades(p), p.name) for p in Path(folder).glob("*.html")), reverse=True)
    prose = [g[0][0] for g in rows]
    full = [g[0][1] for g in rows]
    print(f"\nmeasured {len(rows)} files in {folder}/  (prose FK | full FK)")
    print(f"  prose FK : min {min(prose)}  mean {round(st.mean(prose),1)}  max {max(prose)}")
    print(f"  full  FK : min {min(full)}  mean {round(st.mean(full),1)}  max {max(full)}")
    print(f"  prose >9: {sum(1 for x in prose if x>9)}   prose >10: {sum(1 for x in prose if x>10)}   in 7-9: {sum(1 for x in prose if 7<=x<=9)}")
    print("  hardest 8:")
    for (pf, ff), name in rows[:8]:
        print(f"    {pf:>4} | {ff:>4}  {name}")


def main() -> int:
    args = sys.argv[1:]
    if not args:
        print("usage: python -m scripts.readability <file.html> [...]  |  --summary")
        return 2
    if args[0] == "--summary":
        summary(args[1] if len(args) > 1 else "content")
        return 0
    for a in args:
        report(Path(a))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
