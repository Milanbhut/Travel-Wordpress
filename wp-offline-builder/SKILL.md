---
name: wp-offline-builder
description: Build a complete, premium WordPress authority blog LOCALLY under XAMPP from a single niche or title - custom token-driven theme, ~90 human-voiced articles across 6 categories, real authors with photos, trust/legal pages, full schema/SEO, and an AdSense-readiness scan. Real stock images are downloaded online once, then the result is fully offline at http://localhost/<slug>. Use when the user wants a local WordPress authority blog (no hosting, no domain) for development, demo, or portfolio purposes.
---

# WP Offline Builder

Given a **niche or title**, drive a **local** XAMPP WordPress install (WP-CLI via the bundled PHP)
to a finished, AdSense-ready authority blog that lives entirely on your machine at
`http://localhost/<slug>`.

**No hosting, no domain.** This agent never touches SSH, DNS, SSL, Cloudflare, or a live host. It
builds against `C:\xampp\htdocs\<slug>` and serves through your local Apache. Stock images are the
only thing fetched from the internet - they're downloaded **once** during the build, imported into
the WordPress media library, and from then on the site renders fully offline.

**Engine location:** `D:\1Agent1\wp-offline-builder` (scripts, venv, theme, references, config).
Run every script with the repo venv and PYTHONPATH:

```
$py = "D:\1Agent1\wp-authority-builder\.venv\Scripts\python.exe"
$env:PYTHONPATH = "D:\1Agent1\wp-offline-builder"
& $py -m scripts.<name>
```

## Prerequisites (per site)
1. **XAMPP running** - Apache **and** MySQL both started from the XAMPP Control Panel.
2. **`wp-cli.phar` in `bin/`** - drop it at `D:\1Agent1\wp-offline-builder\bin\wp-cli.phar`
   (one-time download; see `bin/README.md`). It runs under XAMPP's PHP (`C:\xampp\php\php.exe`).
3. `config/secrets.env` filled (copy `config/secrets.env.example`): `MODE=local`, `SITE_SLUG`,
   `WP_PATH` (e.g. `C:\xampp\htdocs\<slug>`), `WP_URL` (`http://localhost/<slug>`), `LOCAL_PHP`,
   `WP_CLI_PHAR`, `MYSQL_BIN`, `DB_NAME/DB_USER/DB_PASSWORD/DB_HOST`, `UNSPLASH_ACCESS_KEY`,
   `PEXELS_API_KEY`, and the `WP_ADMIN_*` credentials.

## Workflow (run in order; ★ = user checkpoint)

**1. Install (local)**
- `scripts.probe_host` → local Gate-1 readiness: confirms the WP-CLI channel works (local PHP +
  `wp-cli.phar` against `WP_PATH`) and reports whether WordPress is already installed there - read-only.
- `scripts.check_db` → confirm the local XAMPP MySQL (`MYSQL_BIN`, `DB_HOST`) is reachable and the
  `DB_NAME` database is empty or creatable.
- `scripts.install_wp` → creates the database (if needed) and installs WordPress locally under
  `WP_PATH` (core download + `wp-config.php` + `core install`) if not already present.
- `scripts.fix_permalinks` → writes `.htaccess` + pretty permalinks for Apache.

**0. Brand brief ★** - From the niche, generate `config/<slug>.brief.json` (name, tagline, palette
tokens, 6 one-word category labels + full titles, 4-5 authors with voices + photo queries, contact
email, country-of-operation). **Present for approval.** Then **record the theme recipe**:
`scripts.apply_theme <slug>` picks a recipe from `config/theme-recipes.json` (palette + font pair +
radius/density bucket + one layout variant per homepage section), pins it under `design.recipe` in the
brief (so re-runs are stable), and writes the theme's
`assets/master-theme/overlaytop/assets/css/tokens.css`. The pick is seeded from the slug and
niche-matched to the palette catalog - to override, set `design.recipe` in the brief before running.
This replaces hand-editing tokens.css.

**0.5 Editorial plan** - Dispatch ONE subagent to draft 90 titles (15/category) with archetypes, unique
slugs, image queries, and angles → `config/<slug>.plan-raw.json`. Then `scripts.build_editorial_plan`
(assigns uneven beat-matched authors + the internal-link graph) → `config/<slug>.editorial-plan.json`.

**2. Theme + structure**
- `scripts.apply_theme <slug>` first (if Step 0's recipe isn't already written) - this stamps the
  recipe into the master theme's `tokens.css` **before** it is copied into place.
- `scripts.deploy_theme` (copies the theme into `WP_PATH/wp-content/themes/` + `wp theme activate`
  via local WP-CLI - `php wp-cli.phar --path=WP_PATH`).
- `scripts.apply_theme <slug> --mods` **after** activation - sets the per-recipe theme mods on the
  local WordPress (`ot_recipe`, `ot_v_hero/about/latest/category/newsletter/footer`, `ot_fonts_href`)
  that `front-page.php` + `functions.php` read to vary the look. The theme must be active first.
- The homepage is a **fixed 5-section structure** (`front-page.php`, never reordered): Hero → About →
  Latest 6 (3×2) → 3 bento category sections → Newsletter. The **look** varies per recipe - each
  section carries a variant class (`overlaytop_variant()`) that CSS rearranges, on top of the
  palette/font/radius from `tokens.css`. Set the **content** theme-mods during build (e.g.
  `ot_hero_eyebrow/title/sub/trust`, `ot_about_title/body/stats/image`, `ot_home_cats`,
  `ot_news_title/sub/shortcode`) so each section has real per-site copy, not placeholders.
- `scripts.scaffold_authors` (creates the authors with bios + real photos).
- `scripts.build_demo` (identity, permalinks, 6 categories, one-word primary menu) - or run pieces.
- `scripts.cleanup_defaults` (removes Hello World / Sample Page).

**3. Content (subagent-driven - the fast path)**
- Generate all ~90 articles with **parallel subagents via the Workflow tool** (~16 concurrent). Each
  reads its plan entry + `references/content-style-guide.md` and writes `content/<slug>.html` (raw HTML
  with an `<!--excerpt:-->` lead). Validate with the first 4 (one per archetype) before fanning out the rest.
- `scripts.publish_article --all` → lint-gated publish (word count ≥1000, banned-phrase check) + a
  deduped featured image per post (Unsplash → Pexels fallback). **Images are fetched online once here
  and stored in the media library; the site stays self-contained afterward.**
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
- `scripts.audit_images` (backfill missing + perceptual-hash dedupe → all images distinct; deletes the
  replaced attachments so no byte-duplicate images linger in the media library).
- `scripts.audit_links` (zero orphans / zero broken internal links).
- `scripts.stagger_dates` (natural cadence over ~6 weeks; sets post_date **and** post_date_gmt so the
  oldest post clears the 30-day maturity floor while the recent 30 days stay active).
- `scripts.adsense_scan` - installs + activates the **AdSense Checklist** plugin
  (`assets/plugins/adsense-checklist`) and runs it headlessly. **Run on every site and resolve every
  *open* finding before handoff**, re-running until only known exceptions remain: *analytics* needs the
  owner's GA4 ID (set the GA4 option and the theme injects gtag); *HTTPS* will flag because a local
  `http://localhost` site has no SSL - that's expected and inherent to an offline build; *CDN* is optional.
  The *manual-review* items are human judgment (original/edited content, genuine audience, etc.).
  Check targets to design content toward: About ≥300 words; Privacy must contain `third-party`, `google`,
  `cookies`, `opt-out`; every article ≥1000 words with H2s and Flesch-Kincaid grade 6-12.
- `scripts.final_report` (totals, word stats, images, schema, author distribution).
- Screenshots: with Apache running, open `http://localhost/<slug>` directly and do the 7-breakpoint +
  console-error sweep in a browser. `scripts.preview <path> <name>` also fetches that page over HTTP
  from the local `WP_URL` (`http://localhost/<slug>`) and writes a static browsable copy under `preview/`.

## Diagnostics / ops helpers
`scripts.wpcli <wp args>` (runs `php wp-cli.phar --path=WP_PATH ...` locally) ·
`scripts.php_eval <file.php>` (run PHP with WP loaded, via `wp eval-file`) ·
`scripts.readability <file.html | --summary>` (Flesch-Kincaid grade) ·
`scripts.pull_plugin <slug>` (download a plugin into `assets/plugins/`).

**Remote-only, no-op offline:** `scripts.sh`, `scripts.fetch_origin`, `scripts.list_domains`,
`scripts.migrate_site`, `scripts.check_provision` are SSH/host operations from the remote agent. In this
offline build they are inert stubs (they just print a `[skip] … remote-only` line and exit 0) - don't use
them for local diagnostics; reach for `scripts.wpcli` / `scripts.php_eval` instead.

## Honest limits
- **Local only:** the result lives at `http://localhost/<slug>` and is reachable only on this machine.
  There is no public URL, no DNS, and no HTTPS - by design. Taking it live (real hosting + domain +
  SSL) is a separate step handled by the remote `wp-authority-builder` agent.
- **Provisioning boundary:** XAMPP (Apache + MySQL) must be installed and running first; the agent owns
  everything after - it creates the database and installs WordPress under `htdocs`.
- **AdSense:** this makes a site *build-ready* in content/structure/SEO terms. Actual AdSense approval is
  impossible for a `localhost` site (Google can't crawl it) - you must first take the build live on a real
  domain (DNS + HTTPS), fill `ADSENSE_PUB_ID` in ads.txt and the Terms country, let it mature with
  consistent publishing, and have a human read a content sample (Google treats unedited AI as a severe
  violation).
