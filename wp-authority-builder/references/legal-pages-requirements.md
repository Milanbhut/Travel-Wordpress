# Legal Pages — Compliance Requirements (Privacy, Terms, Disclaimer)

Source: user-provided audit/upgrade prompt (2026-06-04). These are **hard rules** the legal-page
generator (Phase 4) must satisfy automatically for every site. Goal: legal transparency, user trust,
E-E-A-T, and Google advertising/transparency compliance — reading like a real business, not a template.

## Privacy Policy — required

1. **Advertising Personalization Controls** — a dedicated, plain-language section explaining that
   advertising partners (incl. Google) may use cookies and personalization technologies, and that users
   can manage/disable personalized ads via Google's official tools. Must include **clickable links**:
   - https://adssettings.google.com
   - https://myadcenter.google.com
2. **No placeholder contact info.** Never ship `your@email.com`, `contact@example.com`,
   `support@website.com`, `[INSERT EMAIL]`, `[CONTACT DETAILS]`. Use the site's real contact email.
   If a real value is genuinely unavailable, emit a clearly-marked `[OWNER ACTION REQUIRED: …]` flag.
3. Must always contain: **contact email**, **website name**, a **reference/link to the Contact page**,
   and a **Last Updated date**.
4. Disclose third-party ad serving, Google advertising cookies, and link to industry opt-out registries
   (ties into the CMP / privacy-disclosure checklist item).

## Terms of Use / Terms & Conditions — required

1. **Governing Law clause with a specific jurisdiction.** Remove vague wording: "local laws",
   "applicable laws", "site laws", "relevant laws", "laws where the site operates", fictional/undefined
   jurisdictions.
2. Use a concrete structure, e.g.:
   > **Governing Law** — These Terms shall be governed by and interpreted in accordance with the laws of
   > **[COUNTRY]**, without regard to conflict-of-law principles. Any disputes arising under these Terms
   > shall be subject to the applicable courts of **[COUNTRY]**, unless otherwise required by law.
3. **Do NOT invent** countries, states, courts, or legal entities. If the country of operation is unknown,
   insert the literal `[INSERT ACTUAL COUNTRY OF OPERATION]` and flag that the owner must replace it
   before publication.

## Global (both pages + Disclaimer)

- Add a **Last Updated** date if missing.
- Clear headings; remove duplicate/contradictory statements.
- Privacy Policy and Terms must be **internally consistent** (same site name, same contact info).
- Verify the **website name** appears correctly throughout.
- Remove generic template language that reduces credibility; keep it human-readable and professional.
- All links must function; ensure mobile readability/formatting.
- Preserve the site's existing design/branding.

## Inputs needed from the owner (collect before generating legal pages)

- **Contact email** (real) — used across Privacy, Contact, and Terms.
- **Country of operation** — used for the Governing Law clause (else `[INSERT ACTUAL COUNTRY OF OPERATION]`).

## Final acceptance check

Complies with Google advertising/transparency expectations · includes the two Google ad-personalization
links · accurate contact info · clearly identified governing jurisdiction · zero vague legal wording ·
strong E-E-A-T/trust · reads like a legitimate business, not a generic template.
