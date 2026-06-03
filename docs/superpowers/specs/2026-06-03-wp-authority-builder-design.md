# WP Authority Builder — Design Spec

**Date:** 2026-06-03
**Status:** Draft for review
**Author:** Claude (Opus 4.8) with user
**Host environment:** Windows 11, `D:\1Agent1`; agent installed user-level at `C:\Users\Home\.claude\skills\`

---

## 1. Objective

A **user-level Claude Code skill** that, given only a **niche or title**, drives a real Hostinger
WordPress install (over SSH + WP-CLI) to produce a complete, premium, **Google-AdSense-ready
authority blog**: a token-driven custom theme, 90 original human-tone articles across 6 categories,
real authors, legal/trust pages, full schema/SEO, and a multi-breakpoint QA audit.

The site must read as though designed, written, edited, and maintained by a real editorial team —
not a generic AI blog.

### The two governing priorities (everything else serves these)

1. **AdSense approval readiness** (top priority)
2. **Premium design + proper editorial flow**

---

## 2. Goals / Non-Goals

**Goals**
- One command → a finished, AdSense-ready 90-article site on a Hostinger domain.
- Reusable from any folder (user-level skill), repeatable across niches.
- Deterministic, resumable, idempotent at 90-article scale.
- Faithful to the extracted design language of the user's approved reference sites, *corrected*
  where requirements.txt asks for improvements (one-word nav labels; no banned phrases).

**Non-Goals (explicit boundaries)**
- **From-zero provisioning is out of scope.** The user stands up a blank WordPress install once
  (Hostinger 1-click, or agent via SSH if available); the agent owns everything after. (User's chosen
  handoff boundary — chosen for reliability over the fragile hPanel-browser-automation path.)
- **The agent makes a site *ready*, not *instantly approved*.** Per the user's own AdSense checklist,
  domains must *mature* (consistent publishing over weeks) before applying. No claim of guaranteed approval.
- No reuse of the user's existing theme code — the master theme is written fresh ("from scratch") in
  the extracted design *language*.

---

## 3. Architecture

A Claude Code skill where **judgment-heavy work runs with Claude live in the loop** (writing, design,
QA interpretation) and **repeatable work runs as deterministic scripts** the skill calls (provisioning,
media processing, schema, sitemaps, link/QA audits).

```
~/.claude/skills/wp-authority-builder/
  SKILL.md                  # orchestrator: the phased workflow + gates
  references/
    design-system.md        # extracted tokens, type scale, component anatomy (from reference sites)
    adsense-checklist.md     # the Notion compliance gates, normalized
    content-style-guide.md   # human-tone rules, banned-phrase list, archetype + voice-profile specs
  assets/
    master-theme/            # fresh, token-driven WordPress theme source
    legal-templates/         # About / Privacy / Terms / Disclaimer / Editorial / Affiliate scaffolds
  scripts/                   # Python + Bash, run on Windows host; WP-CLI invoked remotely over SSH
    wp.py                    # WP-CLI-over-SSH wrapper (+ REST API fallback)
    images.py                # Unsplash/Pexels fetch, compress, perceptual-hash dedupe, assign
    seo.py                   # schema, sitemap.xml, robots.txt, ads.txt
    audit.py                 # broken-link, schema-validate, image-dedupe, word-count
    qa_browser.py            # drives chrome-devtools MCP: 7 breakpoints, console, Lighthouse
  state/
    <domain>.manifest.json   # per-site build state (resumable, idempotent)
  config/
    secrets.env              # gitignored: SSH, WP, Unsplash, Pexels, AdSense ID
    <domain>.brief.json      # approved brand brief + editorial plan (90 locked slugs)
```

**Why a skill + scripts (not a pure standalone app):** the hardest, highest-value parts of this job —
premium design judgment, genuinely human writing, and interpreting QA — are exactly where Claude-live
beats fixed prompts. Scripts isolate the deterministic plumbing so it is testable and so the logic could
later graduate to a standalone app if desired.

---

## 4. Inputs & Configuration

**Run-time input:** a niche or title (e.g. `"home solar energy"`).

**`config/secrets.env`** (user fills once; never pasted in chat):
- `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `SSH_KEY` or `SSH_PASS`
- `WP_URL`, `WP_ADMIN_USER`, `WP_APP_PASSWORD` (REST fallback)
- `UNSPLASH_ACCESS_KEY`, `PEXELS_API_KEY`
- `ADSENSE_PUB_ID` (for `ads.txt`; optional — can be added later)

**`config/<domain>.brief.json`** (generated at Phase 0, user-approved): site name, tagline, 6 one-word
nav labels + full category titles, color palette, author personas + voice profiles, logo concept, and
the **editorial plan** (90 titles → category, archetype, author, **locked slug**, planned internal links).

---

## 5. Control Channel — first hard gate

1. **Gate 1 (blocking):** SSH in and verify `wp --info` executes on the host. If yes → WP-CLI is the
   primary channel (full power: install themes/plugins, bulk content, write `ads.txt`/`robots.txt`).
2. **Fallback (graceful):** if WP-CLI is unavailable, degrade to the **WP REST API + Application
   Password** for posts/media/authors/menus/categories; flag the capabilities that require manual help
   (theme/plugin install, raw server files). The build does not silently proceed as if WP-CLI exists.
3. Cloudflare proxies the origin; **SSH goes direct to Hostinger** (CF untouched). During QA, purge/bypass
   CF + LiteSpeed cache so audits hit live pages, not cached ones.

---

## 6. Pipeline

Each phase is a **verifiable goal** with an explicit check. State is written to the manifest after every
unit so any phase is resumable.

| # | Phase | Output | Verify |
|---|-------|--------|--------|
| 0 | **Intake → Brand brief** | Name, tagline, 6 one-word labels, palette, **4–5 authors with voice profiles**, logo concept | **CHECKPOINT: user approves brief** |
| 0.5 | **Editorial plan** | 90 titles (15/category), each tagged with archetype + author + **locked slug** + planned internal links | Category balance = 15×6; zero slug collisions; user glance |
| 1 | **Connect & verify** | SSH + WP-CLI confirmed; permalinks/timezone set; default content stripped | **Gate 1**: `wp --info` OK (else REST fallback) |
| 2 | **Deploy theme** | Fresh master theme + injected niche palette/fonts/brand; activated | Renders; no PHP errors; header is sticky+glass |
| 3 | **Scaffold** | 6 categories (no "Uncategorized"), author accounts (bio+photo+socials), header/footer menus, core pages | Menus show one-word labels; authors have Person data |
| 4 | **Trust pages** | Real About, Contact (working form), Privacy, Terms, Disclaimer, Editorial Policy, Affiliate Disclosure | All linked in nav/footer; non-template content |
| 5 | **Content factory** | 90 articles, 1,000–1,500 words, archetype+voice-rotated, humanization-edited, internal links inline, author-assigned | Per-article: word count, banned-phrase lint, uniqueness check pass |
| 6 | **Media** | Unique Unsplash/Pexels photo per post: downloaded, compressed, alt text, responsive, lazy-load | **Perceptual-hash dedupe**: zero near-duplicates; title match |
| 7 | **SEO/technical** | Schema (BlogPosting/FAQ/Breadcrumb/Organization/Person/WebSite), OG/Twitter, sitemap.xml, robots.txt (allows `Mediapartners-Google`), ads.txt, canonicals, meta, breadcrumbs, **Complianz CMP** | Schema validates; sitemap reachable; CMP live |
| 8 | **Internal linking** | Contextual + category + related links (mostly inline via locked slugs) | Audit: zero orphans; every post ≥3 contextual outbound links and linked from ≥2 others |
| 9 | **QA audit** | 7 breakpoints (1440→320), broken-link, schema, console errors, image audit, CTA checks, Lighthouse | No overflow/clipping/broken menus; no console errors |
| 10 | **Final report** | Totals + AdSense-readiness assessment | All §13 success criteria met |

---

## 7. Anti-Homogeneity System (first-class — protects priority #1)

The dominant approval risk at 90 articles is not one bad post; it is 90 posts sharing one skeleton, rhythm,
and rhetorical tics — which Google's systems are tuned to detect. The user's checklist flags unedited AI as
a **severe** violation. Countermeasures, designed in (not prompt-tweaked):

- **Archetype rotation.** Each article is pre-assigned one of: listicle, how-to, comparison, explainer,
  mistakes-and-fixes, case-study/scenario. Distribution is planned in the editorial map so *structure*
  varies by design across the 90.
- **Per-author voice profiles.** Each of the **4–5 authors** gets a distinct profile: vocabulary leanings,
  sentence-length distribution, opening and closing habits, formatting tendencies — a measurably different
  voice serving E-E-A-T credibility *and* anti-homogeneity at once. Article counts per author are
  **deliberately uneven** (a realistic newsroom spread — a lead writer with more, contributors with fewer —
  summing to 90, never an equal split), each author kept above a sensible minimum so none looks fake.
  Authors are matched to the categories that fit their stated expertise.
- **Banned-phrase linter.** Hard-blocks the requirements.txt list ("the bottom line", "in conclusion",
  "ultimately", "when it comes to", "in today's world", "needless to say", "as mentioned above") and
  repetitive transitions. (Note: the reference sites themselves violate this — the new engine corrects it.)
- **Structural variation rules.** Vary intro patterns, H2 phrasings, FAQ count/wording, and the
  presence/placement of callouts, tip-boxes, and comparison tables per archetype.
- **Uniqueness check.** Before accepting an article, compare its structural skeleton against already-written
  ones in the manifest; flag and rewrite on excessive similarity.
- **Humanization/edit pass** (its own step): adds practical examples, realistic scenarios, common mistakes,
  small observations; smooths AI cadence.
- **Honest caveat (in final report):** full automation at this scale carries irreducible approval risk; the
  checklist itself favors human review. The report will recommend the user read a sample before applying.

---

## 8. Image System (protects "no duplicate images", "match title")

- Source **only** Unsplash + Pexels (real photographs). No AI images, illustrations, or generated artwork.
- **Multi-query pool-and-assign:** generate several search queries per article from its title/topic, build a
  candidate pool, then assign to maximize visual distinctiveness across the whole site.
- **Perceptual-hash dedupe** (not just exact URL/byte match) to catch near-duplicates common in a narrow niche.
- Per image: download locally, compress, generate responsive sizes, descriptive alt text, lazy-load.
- Final image audit replaces any mismatch or near-duplicate before sign-off.

---

## 9. Resumability & State

`state/<domain>.manifest.json` records every unit's status (planned → written → edited → imaged → published →
linked → verified). Rules: write-after-each-unit; re-running skips completed units; never duplicate a post or
re-upload an existing image. A run interrupted at article 47 resumes at 48.

---

## 10. Three Quality Gates (scaled to the stakes)

1. **Gate G1 — 1 article (spine).** connect → theme → 1 category + 1 author + 1 fully-formed post + the legal
   pages → schema + desktop/mobile render QA. Proves the end-to-end machinery.
2. **Gate G2 — 15 articles (one full category).** The smallest unit that exposes uniqueness drift, link-graph
   coherence, image-pool depth, and aggregate runtime/cost. Must read well end-to-end before scaling.
3. **Gate G3 — 90 articles (full commit).** Remaining 75 generated in resumable batches, then full-site QA.

---

## 11. Design System (extracted from reference sites — to be captured in `references/design-system.md`)

Verified live from `leafystart-theme` (representative of the shared DNA):
- **Type:** `Fraunces` (serif headings) + `Inter` (sans body). h1 ≈ 56px / line-height 1.08 / letter-spacing
  −1.2px; body 17.5px / line-height 1.65. Per-niche mood may swap the serif.
- **Tokens:** full color scales (e.g. `--green-50…900`, neutral `--ink-300…900`), `--accent`, `--cream`/`--paper`,
  layered `--shadow*`, `--radius*` (6/10/16/24), `--max-w: 1180px`, **`--read-w: 720px`**, `--gutter: clamp(...)`.
  Per-niche reskin = swap palette + accent + fonts; **structure stays** (proven AdSense-friendly).
- **Header:** sticky, `backdrop-filter: saturate(1.5) blur(10px)` translucent — the required glassmorphism.
- **Homepage anatomy:** skip-link → glass header → mobile-drawer + backdrop → search-overlay → editorial hero
  (eyebrow pill, giant serif headline, dual CTAs, emoji trust indicators, floating accent shapes) → featured
  card + secondary stack → content sections → footer.
- **Article anatomy:** breadcrumbs → single-hero (category pill, lead, byline+photo, featured image) → auto TOC
  → callouts / tip-boxes / scrollable comparison tables → FAQ → author-bio box → related posts.
- **Responsive:** premium layouts at 1440 / 1280 / 1024 / 768 / 425 / 375 / 320; slide-in mobile drawer.
- **Corrections vs reference:** one-word header labels (full titles on category pages); no banned phrases.

---

## 12. QA Audit & Final Report

**Audit:** every article ≥1,000 words; unique title-matched image per post; no duplicate images; no broken
links; no orphan posts; schema valid; all pages complete; premium header + mobile menu + author box; blog &
categories pages present; logo + favicon set; responsive at all 7 breakpoints; no console errors; AdSense-ready
structure; fast pages; clean typography/spacing; all CTAs work.

**Final report:** Total Posts / Categories / Pages · Average Word Count · Unique Image Count · Schema Coverage ·
Broken-Link Audit · Responsiveness Audit · Performance Notes · **AdSense Readiness Assessment** (incl. the
maturation caveat and human-review recommendation).

---

## 13. Success Criteria (verifiable)

- [ ] 90 published posts, 15 per category × 6, every post authored by a created author with byline + bio + Person schema.
- [ ] 4–5 authors with deliberately uneven article counts (no equal split), each above a sensible minimum.
- [ ] Every post 1,000–1,500 words, passes banned-phrase lint and uniqueness check.
- [ ] 90 unique, title-matched images; perceptual-hash dedupe finds zero near-duplicates.
- [ ] All 7 trust/legal pages present with real content, linked in nav/footer.
- [ ] Schema (BlogPosting/FAQ/Breadcrumb/Organization/Person/WebSite) validates site-wide; sitemap.xml + robots.txt
      (allows `Mediapartners-Google`) + ads.txt present; Complianz CMP live.
- [ ] Zero orphan posts; zero broken links; zero console errors.
- [ ] Premium render with no overflow/clipping/broken menus at 1440/1280/1024/768/425/375/320.
- [ ] Final report produced; G1 and G2 gates passed before G3.

---

## 14. Risks & Honest Caveats

- **Approval is not guaranteed or instant** — domain maturation + human review sample still required.
- **Content homogeneity** is the top residual risk; §7 mitigates but cannot fully eliminate it at full automation.
- **Image scarcity** in tight niches; §8 mitigates via multi-query + perceptual hashing.
- **WP-CLI availability** unverified until Gate 1; REST fallback defined.
- **Runtime/cost** at 90 articles is large; batched + resumable execution, likely across multiple sessions.

---

## 15. Open Items (needed from user before/at build)

- First niche/title to build (for G1).
- `secrets.env` filled (user will do this).
- Confirm a target domain has a **blank WP install standing**.
- Unsplash + Pexels API keys ("I'll check"); AdSense Publisher ID optional at start.
