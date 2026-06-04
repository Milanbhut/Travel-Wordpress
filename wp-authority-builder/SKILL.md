---
name: wp-authority-builder
description: Build a complete, premium, Google-AdSense-ready WordPress authority blog on a Hostinger site from a single niche or title — custom token-driven theme, ~90 human-voiced articles across 6 categories, real authors with photos, trust/legal pages, full schema/SEO, CMP, and QA. Use when the user wants to create a new WordPress blog from a niche/title, or scale/finish an existing Overlaytop-style site.
---

# WP Authority Builder

Given a **niche or title**, drive a Hostinger WordPress install (WP-CLI over SSH, REST fallback) to a
finished, AdSense-ready authority blog.

**Engine location:** `D:\1Agent1\wp-authority-builder` (scripts, venv, theme, references, config).
Run every script with the repo venv and PYTHONPATH:

```
$py = "D:\1Agent1\wp-authority-builder\.venv\Scripts\python.exe"
$env:PYTHONPATH = "D:\1Agent1\wp-authority-builder"
& $py -m scripts.<name>
```

Full design spec: `docs/superpowers/specs/2026-06-03-wp-authority-builder-design.md`.

## Prerequisites (per site)
1. A Hostinger site with SSH enabled (hPanel → Advanced → SSH Access; port is usually **65002**).
2. A MySQL database created in hPanel.
3. `config/secrets.env` filled (copy `config/secrets.env.example`): SSH host/port/user/pass, `WP_PATH`,
   `WP_URL`, `WP_CLI_PHP` (e.g. `/opt/alt/php82/usr/bin/php`), `DB_NAME/DB_USER/DB_PASSWORD`,
   `UNSPLASH_ACCESS_KEY`, `PEXELS_API_KEY`, optional `ADSENSE_PUB_ID`.

## Workflow (run in order; ★ = user checkpoint)

**1. Connect & install**
- `scripts.probe_host` → require `[GATE 1: OK] control channel = wpcli`.
- `scripts.check_db` → confirm the database is reachable/empty.
- `scripts.install_wp` → installs WordPress (core download + config + install) if not present.
- `scripts.fix_permalinks` → writes `.htaccess` + pretty permalinks (LiteSpeed needs this).

**0. Brand brief ★** — From the niche, generate `config/<domain>.brief.json` (name, tagline, palette
tokens, 6 one-word category labels + full titles, 4–5 authors with voices + photo queries, contact
email, country-of-operation). **Present for approval.** Then write the palette into the theme's
`assets/master-theme/<slug>/assets/css/tokens.css`.

**0.5 Editorial plan** — Dispatch ONE subagent to draft 90 titles (15/category) with archetypes, unique
slugs, image queries, and angles → `config/<domain>.plan-raw.json`. Then `scripts.build_editorial_plan`
(assigns uneven beat-matched authors + the internal-link graph) → `config/<domain>.editorial-plan.json`.

**2. Theme + structure**
- `scripts.deploy_theme` (SFTP upload + `wp theme activate`).
- `scripts.scaffold_authors` (creates the authors with bios + real photos).
- `scripts.build_demo` (identity, permalinks, 6 categories, one-word primary menu) — or run pieces.
- `scripts.cleanup_defaults` (removes Hello World / Sample Page).

**3. Content (subagent-driven — the fast path)**
- Generate all ~90 articles with **parallel subagents via the Workflow tool** (~16 concurrent). Each
  reads its plan entry + `references/content-style-guide.md` and writes `content/<slug>.html` (raw HTML
  with an `<!--excerpt:-->` lead). Validate with the first 4 (one per archetype) before fanning out the rest.
- `scripts.publish_article --all` → lint-gated publish (word count ≥1000, banned-phrase check) + a
  deduped featured image per post (Unsplash → Pexels fallback).

**4. Trust + SEO + compliance**
- `scripts.build_pages` → About/Contact/Privacy/Terms/Disclaimer/Editorial/Affiliate + Contact Form 7
  (follows `references/legal-pages-requirements.md`: Google ad-personalization links, real contact email,
  governing-law placeholder).
- `scripts.create_template_pages` (/blog, /categories) + `scripts.build_menus` (header + footer).
- Theme `inc/schema.php` ships JSON-LD (BlogPosting/FAQPage/BreadcrumbList/Person/Organization/WebSite) +
  OG/Twitter automatically.
- `scripts.seo_files` (robots.txt allowing `Mediapartners-Google` + ads.txt) · `scripts.make_favicon` ·
  install Complianz (`wp plugin install complianz-gdpr --activate`) **and run its setup wizard** for TCF.

**5. QA + report**
- `scripts.audit_images` (backfill missing + perceptual-hash dedupe → all images distinct).
- `scripts.audit_links` (zero orphans / zero broken internal links).
- `scripts.stagger_dates` (natural publishing cadence over ~3 weeks).
- `scripts.final_report` (totals, word stats, images, schema, author distribution).
- Screenshots: `scripts.preview <path> <name>` builds a local browsable copy (works pre-DNS); once the
  domain resolves, do the live 7-breakpoint + console-error sweep.

## Diagnostics / ops helpers
`scripts.wpcli <wp args>` · `scripts.sh "<shell>"` · `scripts.fetch_origin <path> [--full]` (fetches the
origin bypassing DNS + CDN cache).

## Honest limits
- **Provisioning boundary:** a blank WP (or at least an empty DB) must exist first; the agent owns
  everything after. From-zero hPanel provisioning is out of scope.
- **AdSense:** this makes a site *build-ready*. Approval still needs the domain pointed (DNS), the
  `ADSENSE_PUB_ID` in ads.txt, the Terms country filled, a maturation period of consistent publishing,
  and a human read of a content sample (Google treats unedited AI as a severe violation).
