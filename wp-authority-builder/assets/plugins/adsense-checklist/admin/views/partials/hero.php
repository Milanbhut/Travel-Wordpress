<?php
/**
 * Hero band: circular score gauge + status sentence + severity pill chips.
 *
 * @var array $context From Admin_Page::render() - must contain:
 *   - score    int       0-100 readiness percentage
 *   - passed   int       Count of passing checks
 *   - total    int       Total registry size
 *   - findings object[]  All findings; we compute per-severity active counts here
 */
if (!defined('ABSPATH')) { exit; }

$score    = (int) ($context['score'] ?? 0);
$passed   = (int) ($context['passed'] ?? 0);
$passing_total = (int) ($context['passing_total'] ?? $passed);
// Effective total drops ignored checks; falls back to raw total for older callers.
$total    = (int) ($context['effective_total'] ?? $context['total'] ?? 0);
$findings = $context['findings'] ?? [];

// Count active findings by severity. Skip everything that has its own group elsewhere
// (passed strip, resolved group, manual_review group) so the chips here exactly match
// the counts on the Urgent/Severe/Moderate/Minor collapsible groups in the report.
$counts = ['urgent' => 0, 'severe' => 0, 'moderate' => 0, 'minor' => 0];
foreach ($findings as $f) {
    $status = $f->status ?? '';
    if ($status === 'passed' || $status === 'resolved' || $status === 'ignored' || $status === 'manual_review') {
        continue;
    }
    $sev = $f->severity ?? '';
    if (isset($counts[$sev])) {
        $counts[$sev]++;
    }
}

// Score → gauge color class
if ($score >= 90)      { $score_class = 'adsc-gauge--green'; }
elseif ($score >= 60)  { $score_class = 'adsc-gauge--yellow'; }
else                   { $score_class = 'adsc-gauge--red'; }

// Status sentence
if ($score >= 90)      { $headline = "Ready to apply. {$passing_total} of {$total} checks passing."; }
elseif ($score >= 60)  { $headline = "Almost ready. {$passing_total} of {$total} checks passing."; }
else                   { $headline = "{$passing_total} of {$total} checks passing - significant work remains."; }

// Follow-up sentence references the highest-severity bucket with active items
if ($counts['urgent'] > 0) {
    $followup = "Address the {$counts['urgent']} urgent " . ($counts['urgent'] === 1 ? 'item' : 'items') . ' before applying.';
} elseif ($counts['severe'] > 0) {
    $followup = "Address the {$counts['severe']} severe " . ($counts['severe'] === 1 ? 'item' : 'items') . ' before applying.';
} else {
    $followup = 'Most issues are minor - review and resolve at your pace.';
}
?>
<section class="adsc-hero">
    <div class="adsc-gauge <?php echo esc_attr($score_class); ?>" style="--score:<?php echo (int) $score; ?>">
        <svg viewBox="0 0 36 36" aria-hidden="true">
            <circle class="adsc-gauge-track" cx="18" cy="18" r="16"></circle>
            <circle class="adsc-gauge-fill"  cx="18" cy="18" r="16"></circle>
        </svg>
        <div class="adsc-gauge-text">
            <strong><?php echo (int) $score; ?>%</strong>
            <span>Ready</span>
        </div>
    </div>
    <div class="adsc-hero-text">
        <div class="adsc-hero-headline"><?php echo esc_html($headline); ?></div>
        <div class="adsc-hero-followup"><?php echo esc_html($followup); ?></div>
        <div class="adsc-pills">
            <span class="adsc-pill adsc-pill--urgent"><span class="adsc-dot"></span><?php echo (int) $counts['urgent']; ?> Urgent</span>
            <span class="adsc-pill adsc-pill--severe"><span class="adsc-dot"></span><?php echo (int) $counts['severe']; ?> Severe</span>
            <span class="adsc-pill adsc-pill--moderate"><span class="adsc-dot"></span><?php echo (int) $counts['moderate']; ?> Moderate</span>
            <span class="adsc-pill adsc-pill--passing"><span class="adsc-dot"></span><?php echo (int) $counts['minor']; ?> Minor</span>
        </div>
    </div>
</section>
