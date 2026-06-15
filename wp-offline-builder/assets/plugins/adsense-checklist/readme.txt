=== AdSense Checklist ===
Contributors: cuelix
Tags: adsense, checklist, audit, compliance
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later

Scan your blog against a Google AdSense screening checklist and produce a report.

== Description ==

Adds a Tools → AdSense Checklist admin page. Click Run Scan to evaluate 50 individual checks against your site (HTTPS, ads.txt, robots.txt, required pages, post lengths, broken links, CMP detection, privacy disclosures, and more). Items that require human judgment are emitted as "Manual review required" with hints so nothing on the checklist is missed.

Exports findings to CSV or Markdown matching a four-column format:
Violation / Situation | URL example | Classification | Resolved?

== Installation ==

1. Upload the `adsense-checklist` folder to `/wp-content/plugins/`.
2. Activate via the Plugins screen.
3. Visit Tools → AdSense Checklist.

== Changelog ==

= 0.3.0 =
* UI redesign: Google/AdSense-styled dashboard with circular readiness gauge.
* Severity-grouped collapsible sections (Urgent / Severe / Moderate / Manual review).
* Card-grouped Settings tab with toggle switches.
* About tab now shows coverage grouped by source-checklist section.
* Required-page detection now uses tokenized matching (handles "terms-and-conditions", "tos", etc.).
* "How to fix" plain-English remediation steps on every finding.

= 0.1.0 =
* Initial release.
