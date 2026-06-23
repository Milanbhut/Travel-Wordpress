---
name: wp-authority-builder
description: Build a complete, premium, Google-AdSense-ready WordPress authority blog on a Hostinger site from a single niche or title - custom token-driven theme, ~90 human-voiced articles across 6 categories, real authors with photos, trust/legal pages, full schema/SEO, CMP, and QA. Use when the user wants to create a new WordPress blog from a niche/title, or scale/finish an existing Overlaytop-style site.
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

**0. Brand brief ★** - From the niche, generate `config/<domain>.brief.json` (name, tagline, 6 one-word
category labels + full titles, 4-5 authors with voices + photo queries, contact email,
country-of-operation). In the `design` block, record a **recipe** (palette nudged by niche, font pair,
radius, one layout variant per section) - choose/confirm one from `config/theme-recipes.json` and pin it
under `design.recipe`, or leave it out and let `scripts.apply_theme` pick one deterministically. **Present
for approval.**

**0.5 Editorial plan** - Dispatch ONE subagent to draft 90 titles (15/category) with archetypes, unique
slugs, image queries, and angles → `config/<domain>.plan-raw.json`. Then `scripts.build_editorial_plan`
(assigns uneven beat-matched authors + the internal-link graph) → `config/<domain>.editorial-plan.json`.

**2. Theme + structure**
- `scripts.apply_theme <domain>` (BEFORE deploy - writes `tokens.css` from the recipe + records it in the brief).
- `scripts.deploy_theme` (SFTP upload + `wp theme activate`).
- `scripts.apply_theme <domain> --mods` (AFTER activate - sets the `ot_v_*` layout variants, `ot_fonts_href`,
  and the about/hero/newsletter content mods on the live WP).
- `scripts.scaffold_authors` (creates the authors with bios + real photos).
- `scripts.build_demo` (identity, permalinks, 6 categories, one-word primary menu) - or run pieces.
- `scripts.cleanup_defaults` (removes Hello World / Sample Page).
- The homepage is now a **fixed 5-section structure** (Hero → About text+image → Latest 6 in a 3×2 grid →
  3 bento category sections → Newsletter); only the *look* varies per recipe. Set the copy mods with
  `wp theme mod set` during build: `ot_hero_title/ot_hero_sub/ot_hero_eyebrow`,
  `ot_about_title/ot_about_body/ot_about_image`, `ot_home_cats` (3 category slugs), and
  `ot_news_title/ot_news_sub`. Do NOT set `ot_about_stats` or `ot_hero_trust` - the user does not
  want stat-counter labels ("90 guides / 6 topics / 5 experts") on blog homepages by default.

**3. Content (subagent-driven - the fast path)**
- Generate the **validation sample first** - the first 4-6 articles (one per archetype) - with subagents via
  the **Workflow tool**. Each reads its plan entry + `references/content-style-guide.md` and writes
  `content/<slug>.html` (raw HTML with an `<!--excerpt:-->` lead). Review them for quality.
- **Plagiarism-gate the sample before writing the rest** (cost control - scanning all 90 is ~216k credits):
  `scripts.winston_qa --prepare --files content/<slug>.html ...` (the 4-6 sample files) → scan each with the
  **winston-ai MCP** (`plagiarism-detection` only) → `scripts.winston_qa --report`. Fan out the remaining ~84
  articles **only once every sample article is 0% copied** (score ≤5%, no matched source). The whole batch
  comes from the same model + style guide, so a clean sample predicts a clean full set - the other 84 are not
  scanned. If a sample article matches a source, fix the generation and re-scan before fanning out.
- Then generate the remaining ~84 articles (~16 concurrent) the same way.
- `scripts.publish_article --all` → lint-gated publish (word count ≥1000, banned-phrase check) + a
  deduped featured image per post (Unsplash → Pexels fallback).
- Articles must be **em-dash-free** (hard rule, see `references/content-style-guide.md`): never an em dash
  or en dash anywhere. The QA `scripts.scrub_dashes` pass strips any long dashes that slip through, but the
  writing should already be clean.

**4. Trust + SEO + compliance**
- `scripts.build_pages` → About/Contact/Privacy/Terms/Disclaimer/Editorial/Affiliate + Contact Form 7
  (follows `references/legal-pages-requirements.md`: Google ad-personalization links, real contact email,
  governing-law placeholder).
- `scripts.create_template_pages` (/blog, /categories) + `scripts.build_menus` (header + footer).
- Theme `inc/schema.php` ships JSON-LD (BlogPosting/FAQPage/BreadcrumbList/Person/Organization/WebSite) +
  OG/Twitter automatically.
- `scripts.seo_files` (robots.txt allowing `Mediapartners-Google` + ads.txt) · `scripts.make_favicon` ·
  `scripts.make_logo` (on-brand wordmark → WP custom logo; the AdSense logo check needs one set) ·
  install Complianz (`wp plugin install complianz-gdpr --activate`) **and run its setup wizard** for TCF.

**5. QA + report**
- `scripts.scrub_dashes` → strips every em/en dash from post content, excerpts, titles, term descriptions,
  and theme mods. The site must contain ZERO long dashes (hard user rule). Run after all content, pages, and
  mods are in place.
- Plagiarism is already gated on the validation sample in **Step 3** (winston-ai `plagiarism-detection` on the
  first 4-6 articles), so there is **no full 90-article scan here** - that would cost ~216k credits and a clean
  sample already predicts a clean full set. Optional extra assurance: `scripts.winston_qa --prepare --sample 6`
  pulls 6 evenly-spread published posts to spot-check, then scan each + `scripts.winston_qa --report`;
  regenerate any with matches. **No AI-detection scan** - off the table: AdSense judges originality +
  helpfulness, not a third-party AI score.
- `scripts.audit_images` (backfill missing + perceptual-hash dedupe → all images distinct; deletes the
  replaced attachments so no byte-duplicate images linger in the media library).
- `scripts.audit_links` (zero orphans / zero broken internal links).
- `scripts.stagger_dates` (natural cadence over ~6 weeks; sets post_date **and** post_date_gmt so the
  oldest post clears the 30-day domain-maturity floor while the recent 30 days stay active).
- `scripts.adsense_scan` - installs + activates the **AdSense Checklist** plugin
  (`assets/plugins/adsense-checklist`) and runs it headlessly. **Run on every site and resolve every
  *open* finding before handoff**, re-running until only known exceptions remain: *analytics* needs the
  owner's GA4 ID (set `OVERLAYTOP_GA4_ID` or the `overlaytop_ga4_id` option and the theme injects gtag);
  *HTTPS* is a CLI-only false flag (`is_ssl()` is false headless - confirm in a browser); *CDN* is optional.
  The *manual-review* items are human judgment (original/edited content, genuine audience, etc.).
  Check targets to design content toward: About ≥300 words; Privacy must contain `third-party`, `google`,
  `cookies`, `opt-out`; every article ≥1000 words with H2s and Flesch-Kincaid grade 6-12.
- `scripts.final_report` (totals, word stats, images, schema, author distribution).
- Screenshots: `scripts.preview <path> <name>` builds a local browsable copy (works pre-DNS); once the
  domain resolves, do the live 7-breakpoint + console-error sweep.

## Diagnostics / ops helpers
`scripts.wpcli <wp args>` · `scripts.sh "<shell>"` · `scripts.fetch_origin <path> [--full]` (fetches the
origin bypassing DNS + CDN cache) · `scripts.php_eval <file.php>` (run PHP on the server with WP loaded) ·
`scripts.readability <file.html | --summary>` (Flesch-Kincaid grade) · `scripts.pull_plugin <slug>`
(download a server plugin into `assets/plugins/`).

## Honest limits
- **Provisioning boundary:** a blank WP (or at least an empty DB) must exist first; the agent owns
  everything after. From-zero hPanel provisioning is out of scope.
- **AdSense:** this makes a site *build-ready*. Approval still needs the domain pointed (DNS), the
  `ADSENSE_PUB_ID` in ads.txt, the Terms country filled, a maturation period of consistent publishing,
  and a human read of a content sample (Google treats unedited AI as a severe violation).
