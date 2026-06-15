# AdSense Readiness Checklist (normalized from the user's screening notes)

The QA phase must satisfy these. Source: EN Website Screening Notes (ActiveView-style gates).

## Part A - Infrastructure
- [ ] HTTPS enforced sitewide (SSL). *(Hostinger origin + the domain's SSL once DNS is pointed.)*

## Part B - Technical SEO & crawlability
- [ ] `ads.txt` in the root with the exact Publisher ID (`google.com, pub-XXXX, DIRECT, f08c47fec0942fa0`).
- [ ] `robots.txt` grants `User-agent: Mediapartners-Google` → `Allow: /`.
- [ ] No broken links (404s).
- [ ] Responsive, mobile-first theme.

## Part C - Content & trust (E-E-A-T)
- [ ] Original, human-reviewed content (unedited AI = severe violation).
- [ ] 20-30+ fully-indexed articles before applying (this build ships ~90).
- [ ] 1,000-1,500 words per article, clear headings.
- [ ] Trust pages linked from nav/footer: About, Contact, Privacy Policy, Terms.
- [ ] Domain maturation: consistent publishing over several weeks before applying.

## Part D - Brand safety & privacy
- [ ] No prohibited material (adult/illegal/dangerous/copyright/hate).
- [ ] A Google-certified CMP (IAB TCF v2.2) for EU/UK - e.g. Complianz, wizard completed.
- [ ] Privacy Policy discloses third-party ad serving + Google advertising cookies, with opt-out /
      ad-personalization links (adssettings.google.com, myadcenter.google.com).

## Top flagged violations to avoid
- Weak/empty "About" page (Severe) · categories with low content volume (Severe) ·
  duplicate images (Moderate) · missing author box (Mild).
