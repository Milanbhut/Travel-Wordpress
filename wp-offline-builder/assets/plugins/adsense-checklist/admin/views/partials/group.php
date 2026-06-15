<?php
/**
 * Collapsible severity section wrapping a list of findings.
 *
 * Caller sets these variables before include:
 *   $group_severity  string  'urgent' | 'severe' | 'moderate' | 'minor' | 'manual'
 *   $group_label     string  Display title ('Urgent', 'Severe', ...)
 *   $group_window    string  Recommended fix window ('Fix within 48 hours' etc.)
 *   $group_findings  array   Finding rows (objects) for this group
 *   $group_open      bool    Whether to render with the <details> open attribute
 */
if (!defined('ABSPATH')) { exit; }

if (empty($group_findings)) {
    return; // Don't render an empty group.
}

$open_attr = !empty($group_open) ? ' open' : '';
$severity  = (string) ($group_severity ?? 'urgent');
$label     = (string) ($group_label ?? ucfirst($severity));
$window    = (string) ($group_window ?? '');
$count     = count($group_findings);
?>
<details class="adsc-group adsc-group--<?php echo esc_attr($severity); ?>"<?php echo $open_attr; ?>>
    <summary class="adsc-group-header">
        <span class="adsc-group-caret" aria-hidden="true"></span>
        <span class="adsc-dot"></span>
        <span class="adsc-group-title"><?php echo esc_html($label); ?></span>
        <?php if ($window !== ''): ?>
            <span class="adsc-group-window"><?php echo esc_html($window); ?></span>
        <?php endif; ?>
        <span class="adsc-group-count"><?php echo (int) $count; ?></span>
    </summary>
    <div class="adsc-group-body">
        <?php foreach ($group_findings as $finding): ?>
            <?php include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/finding.php'; ?>
        <?php endforeach; ?>
    </div>
</details>
