<?php
/**
 * Report tab — assembles header, hero, severity groups, passing strip.
 *
 * @var \AdSenseChecklist\Repository $repo
 * @var \AdSenseChecklist\Settings   $settings
 * @var array $registry
 * @var array $context Already prepared by Admin_Page::render():
 *   - scan_id, findings, total, passed, score, site_host, sections
 */
if (!defined('ABSPATH')) { exit; }

// Bucket findings
$buckets = [
    'urgent'        => [],
    'severe'        => [],
    'moderate'      => [],
    'minor'         => [],
    'manual_review' => [],
    'resolved'      => [],
    'ignored'       => [],
    'passed'        => [],
];
foreach (($context['findings'] ?? []) as $f) {
    $status = $f->status ?? '';
    if ($status === 'passed') {
        $buckets['passed'][] = $f;
        continue;
    }
    if ($status === 'resolved') {
        // All resolved findings go to the dedicated bucket. Carry-forward regressions
        // get their tag rendered inside the partial via _previously_resolved evidence.
        $buckets['resolved'][] = $f;
        continue;
    }
    if ($status === 'ignored') {
        // All ignored findings appear in their own group (unlike resolved, which only
        // surfaces carry-forward regressions). User wants visibility into what they've ignored.
        $buckets['ignored'][] = $f;
        continue;
    }
    if ($status === 'manual_review') {
        $buckets['manual_review'][] = $f;
        continue;
    }
    $sev = $f->severity ?? '';
    if (isset($buckets[$sev])) {
        $buckets[$sev][] = $f;
    }
}

// Page header
$header_args = ['subtitle' => 'AdSense Readiness', 'title' => 'Scan Report'];
include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/header.php';

if (!$context['scan_id']) {
    include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/empty-state.php';
    return;
}

// Hero
include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/hero.php';

// Severity groups (in order)
$groups = [
    ['severity' => 'urgent',        'label' => 'Urgent',                  'window' => 'Fix within 48 hours',  'open' => true,  'findings' => $buckets['urgent']],
    ['severity' => 'severe',        'label' => 'Severe',                  'window' => 'Fix within 72 hours',  'open' => false, 'findings' => $buckets['severe']],
    ['severity' => 'moderate',      'label' => 'Moderate',                'window' => 'Fix within a week',    'open' => false, 'findings' => $buckets['moderate']],
    ['severity' => 'minor',         'label' => 'Minor',                   'window' => 'Optimization',         'open' => false, 'findings' => $buckets['minor']],
    ['severity' => 'manual',        'label' => 'Manual review required',  'window' => 'Subjective — verify',  'open' => false, 'findings' => $buckets['manual_review']],
];

foreach ($groups as $group) {
    if (empty($group['findings'])) {
        continue;
    }
    $group_severity = $group['severity'];
    $group_label    = $group['label'];
    $group_window   = $group['window'];
    $group_open     = $group['open'];
    $group_findings = $group['findings'];
    include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/group.php';
}

// Resolved group — all user-resolved findings (incl. carry-forwards from prior scans)
if (!empty($buckets['resolved'])) {
    $group_severity = 'manual';
    $group_label    = 'Resolved';
    $group_window   = 'Counts toward the readiness score';
    $group_open     = false;
    $group_findings = $buckets['resolved'];
    include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/group.php';
}

// Ignored group — excluded from gauge, dimmed, collapsed by default.
if (!empty($buckets['ignored'])) {
    $group_severity = 'ignored';
    $group_label    = 'Ignored';
    $group_window   = 'Excluded from the readiness score';
    $group_open     = false;
    $group_findings = $buckets['ignored'];
    include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/group.php';
}

// Passing strip
$passing_count = count($buckets['passed']);
if ($passing_count > 0):
?>
<section class="adsc-passing">
    <div class="adsc-passing-bar">
        <span class="adsc-passing-icon" aria-hidden="true">&#10003;</span>
        <span class="adsc-passing-label"><?php echo (int) $passing_count; ?> checks passing</span>
        <a href="#" class="adsc-passing-toggle" id="adsc-passing-toggle">View all</a>
    </div>
    <ul class="adsc-passing-list" id="adsc-passing-list" hidden>
        <?php foreach ($buckets['passed'] as $p): ?>
            <li><span class="adsc-dot adsc-dot--<?php echo esc_attr((string) ($p->severity ?? 'minor')); ?>"></span> <?php echo esc_html((string) ($p->situation ?? '')); ?></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
