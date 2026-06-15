# PetNestIQ (petcare) - build resume notes

Local XAMPP build of a pet-care authority blog. Site: http://localhost/petcare
Brand: **PetNestIQ** · slug `petcare` · WP_PATH `C:\xampp\htdocs\petcare`

## Status (as of 2026-06-13, paused on a session rate limit)
- **Blocker:** the bulk article-generation workflow hit the Claude **session limit** (resets **18:30 IST**). 16 of 90 articles are done + published; **74 remain to generate**.
- WordPress 6.7.2 installed; PetNestIQ theme active; permalinks fixed (subdirectory `.htaccess` → `/petcare/`).
- 5 authors w/ photos, 6 categories, header/footer menus, trust/legal pages (+ CF7), logo, favicon, robots.txt, ads.txt, Complianz active (CMP detected on homepage).
- GD enabled in `C:\xampp\php\php.ini` (was blocking all image imports).
- Engine config repointed: `config/overlaytop.{brief,editorial-plan,plan-raw}.json` now hold **petcare** data (many scripts hardcode the `overlaytop.*` names). Canonical copies: `config/petcare.*.json`.
- Per-article specs in `content/specs/<slug>.json`; finished articles in `content/<slug>.html`.

## Prereqs each resume
- XAMPP **Apache** + **MySQL** running. MySQL was started as a background process; if the box/session restarted:
  `cd C:\xampp; mysql\bin\mysqld --defaults-file=mysql\bin\my.ini --standalone` (background), and `apache\bin\httpd.exe`.
- Runner: `$py="D:\1Agent1\wp-authority-builder\.venv\Scripts\python.exe"; $env:PYTHONPATH="D:\1Agent1\wp-offline-builder"`

## To finish (run AFTER 18:30 IST when the limit resets)
1. **Generate the 74 remaining articles** via the Workflow tool (same writer prompt used before; ~16 concurrent). Remaining slugs = petcare plan slugs minus existing `content/*.html`. Compute with:
   `& $py -c "import json,glob,os; P=json.load(open(r'D:\1Agent1\wp-offline-builder\config\petcare.editorial-plan.json',encoding='utf-8')); done={os.path.splitext(os.path.basename(f))[0] for f in glob.glob(r'D:\1Agent1\wp-offline-builder\content\*.html')}; print([a['slug'] for a in P['articles'] if a['slug'] not in done])"`
   Each writer agent: reads `content/specs/<slug>.json` + `references/content-style-guide.md`, writes `content/<slug>.html`.
2. **Publish:** `& $py -m scripts.publish_article --all`  (idempotent; lint-gated; deduped featured image per post)
3. **QA:** `scripts.audit_images` → `scripts.audit_links` → `scripts.stagger_dates` → `scripts.adsense_scan` → `scripts.final_report`
4. **Screenshots / preview:** 7-breakpoint sweep of http://localhost/petcare (Apache running), or `scripts.preview`.

## Known expected exceptions (offline build)
- AdSense scan: **HTTPS** will flag (localhost has no SSL) - inherent/expected. **Analytics** needs the owner's GA4 ID. **CDN** optional.
- Complianz: CMP is **detected** (homepage emits `complianz-gdpr`), satisfying the scan; finishing its admin wizard for a fully-rendered TCF banner is a quick manual step in wp-admin if desired.
- Terms/Disclaimer governing-law country is a placeholder - owner fills before going live.
