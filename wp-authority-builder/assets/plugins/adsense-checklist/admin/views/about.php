<?php
/**
 * About tab - sidebar + severity legend + coverage map grouped by source-checklist section.
 *
 * @var array $registry
 * @var array $context  Contains 'sections' (registry key → section name map).
 */
if (!defined('ABSPATH')) { exit; }

$sections_map = $context['sections'] ?? [];
$header_args = ['subtitle' => 'AdSense Checklist', 'title' => 'About', 'show_actions' => false];
include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/header.php';

// Group registry entries by source-checklist section, preserving section order
$section_order = ['Example violations','Domain & links','Required pages','Navigation','Content policies','Restricted content','Content quality','Account & technical','E-E-A-T','Brand safety & privacy'];
$grouped = array_fill_keys($section_order, []);
foreach ($registry as $entry) {
    $section = $sections_map[$entry['key']] ?? 'Other';
    if (!isset($grouped[$section])) {
        $grouped[$section] = [];
    }
    $grouped[$section][] = $entry;
}
?>
<div class="adsc-about">
    <aside class="adsc-about-sidebar">
        <div class="adsc-card">
            <div class="adsc-card-body adsc-about-plugin">
                <div class="adsc-about-icon">&#10003;</div>
                <div class="adsc-about-name">AdSense Checklist</div>
                <div class="adsc-about-version">Version <?php echo esc_html(ADSENSE_CHECKLIST_VERSION); ?></div>
                <div class="adsc-about-blurb">
                    Scans your blog against a 50-item AdSense screening checklist and produces a report.
                </div>
            </div>
        </div>
    </aside>

    <main class="adsc-about-main">
        <!-- Severity legend -->
        <section class="adsc-card">
            <header class="adsc-card-header">
                <h3>Severity levels</h3>
            </header>
            <div class="adsc-card-body">
                <div class="adsc-legend">
                    <div class="adsc-legend-item">
                        <span class="adsc-dot" style="background:var(--adsc-urgent)"></span>
                        <strong style="color:var(--adsc-urgent)">Urgent</strong>
                        <span>&mdash; fix within 48 hours</span>
                    </div>
                    <div class="adsc-legend-item">
                        <span class="adsc-dot" style="background:var(--adsc-severe-dot)"></span>
                        <strong style="color:var(--adsc-severe)">Severe</strong>
                        <span>&mdash; fix within 72 hours</span>
                    </div>
                    <div class="adsc-legend-item">
                        <span class="adsc-dot" style="background:var(--adsc-moderate-dot)"></span>
                        <strong style="color:var(--adsc-moderate)">Moderate</strong>
                        <span>&mdash; fix within a week</span>
                    </div>
                    <div class="adsc-legend-item">
                        <span class="adsc-dot" style="background:var(--adsc-passing-dot)"></span>
                        <strong style="color:var(--adsc-passing)">Minor</strong>
                        <span>&mdash; optimization</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Coverage map -->
        <section class="adsc-card">
            <header class="adsc-card-header">
                <h3>Checks covered <span class="adsc-coverage-total">(<?php echo (int) count($registry); ?> items)</span></h3>
                <p class="adsc-card-sub">Grouped by source-checklist section. Click a section to expand.</p>
            </header>
            <div class="adsc-card-body" style="padding:0">
                <?php foreach ($grouped as $section => $entries): if (empty($entries)) continue; ?>
                    <details class="adsc-coverage-section">
                        <summary class="adsc-coverage-section-head">
                            <span class="adsc-coverage-section-name"><?php echo esc_html($section); ?></span>
                            <span class="adsc-coverage-section-count"><?php echo (int) count($entries); ?></span>
                        </summary>
                        <ul class="adsc-coverage-list">
                            <?php foreach ($entries as $e): ?>
                                <li>
                                    <span class="adsc-dot adsc-dot--<?php echo esc_attr((string) $e['severity']); ?>"></span>
                                    <?php echo esc_html((string) $e['situation']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
