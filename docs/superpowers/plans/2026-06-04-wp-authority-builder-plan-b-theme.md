# WP Authority Builder — Plan B: Master Theme

**Goal:** Build `overlaytop` — a fresh, hand-coded, token-driven premium WordPress theme implementing the
extracted design language (leafystart anatomy) + requirements.txt, parameterized by `config/<domain>.brief.json`.

**Approach:** Classic (non-FSE) theme with PHP templates + a CSS custom-property design system. Per-niche
reskin = swap the `:root` token block (from the brief) + branding; structure stays. Render-tested, not
unit-tested: verification is "activates with no PHP errors, renders the core templates, responsive at 7
breakpoints, no console errors, schema present."

**Build location:** `wp-authority-builder/assets/master-theme/overlaytop/` (repo, versioned).
**Deploy:** SFTP the theme dir to `…/public_html/wp-content/themes/overlaytop/` over SSH, then
`wp theme activate overlaytop` (PHP 8.2). Re-deployable/idempotent.

---

## File manifest

```
overlaytop/
  style.css                 # theme header + imports
  functions.php             # setup: menus, image sizes, supports, enqueue, author fields, helpers
  inc/
    schema.php              # JSON-LD: Organization, WebSite, BlogPosting, FAQPage, BreadcrumbList, Person
    breadcrumbs.php         # breadcrumb trail builder
    template-tags.php       # author-box, post-card, TOC, reading-time helpers
  assets/
    css/
      tokens.css            # :root design tokens (INJECTED from brief.json)
      main.css              # the full design system (header, hero, cards, article, footer, responsive)
    js/
      main.js               # sticky/glass header, mobile drawer, search overlay, auto-TOC, lazy
    img/                    # logo.svg, sprite/icons (generated in Plan B.1)
  template-parts/
    header.php  hero.php  card.php  author-box.php  faq.php  related.php
  header.php  footer.php  index.php
  front-page.php            # editorial hero + featured + secondary stack + latest sections
  single.php                # breadcrumb -> single-hero -> TOC -> content -> FAQ -> author-box -> related
  archive.php  category.php # premium archive (featured + filters + cards + pagination)
  page.php                  # default page
  page-blog.php             # /blog premium archive
  page-categories.php       # /categories hub
  search.php  404.php
  comments.php
```

## Build order (each step ends with a render check)

1. **Foundation** — `style.css`, `tokens.css` (Overlaytop palette from brief), `main.css` core system
   (reset, type scale Fraunces/Inter, layout vars, header, footer), `functions.php` (theme supports,
   menus, image sizes, enqueue). → Deploy + `wp theme activate`; confirm no PHP errors, fonts load.
2. **Header + footer + JS** — glass sticky header (one-word nav), mobile slide-in drawer + backdrop,
   search overlay, premium footer. → Render; check sticky/glass + drawer at 768/425/375/320.
3. **Front page** — editorial hero (eyebrow, Fraunces headline, dual CTA, trust row, floating accents),
   featured card + secondary stack, latest-by-section. → Render front-page.
4. **Single** — breadcrumb → single-hero (category pill, byline+photo, featured img) → auto-TOC →
   content styles (callout, tip-box, scrollable tables) → FAQ → author-box → related. → Render a post.
5. **Archives** — archive/category premium layout, `page-blog`, `page-categories` hub. → Render.
6. **SEO plumbing** — `inc/schema.php` (full JSON-LD graph), OG/Twitter meta, breadcrumbs, canonical,
   meta description. → Validate JSON-LD on a rendered post.
7. **Author system** — extra author profile fields (bio, photo, role, socials), Person schema, author
   archive template. → Render an author page.

## Verification gates
- **Theme activates** cleanly: `wp theme activate overlaytop` → no PHP fatals (`wp option get template`).
- **Renders** front-page, single, archive without errors (curl origin with Host header / WP-CLI eval).
- **Responsive** 1440/1280/1024/768/425/375/320 — no overflow/clipping/broken menus.
- **No console errors**; **schema** present and valid on a post.
- Feeds directly into **Gate G1** (Plan C): one fully-formed post renders premium.

## Notes
- Build with the `frontend-design` skill for quality (avoid generic AI aesthetics).
- Improvements over the leafystart reference: one-word nav labels; banned-phrase-free; tightened a11y.
- A `scripts/deploy_theme.py` (SFTP upload + activate) is added as the first task of execution.
