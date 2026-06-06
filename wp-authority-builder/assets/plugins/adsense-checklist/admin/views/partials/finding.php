<?php
/**
 * One finding card. Expects $finding (object row from DB) in scope.
 */
if (!defined('ABSPATH')) { exit; }

if (empty($finding)) {
    return;
}

$ev = is_string($finding->evidence ?? '') ? (json_decode((string) $finding->evidence, true) ?: []) : ($finding->evidence ?? []);
$status            = (string) ($finding->status ?? '');
$is_carried_resolved = !empty($ev['_previously_resolved']);
$is_carried_ignored  = !empty($ev['_previously_ignored']);
$is_carried        = $is_carried_resolved || $is_carried_ignored;

$classes = ['adsc-finding'];
if ($is_carried)         { $classes[] = 'adsc-finding--carried'; }
if ($status === 'ignored')  { $classes[] = 'adsc-finding--ignored'; }
if ($status === 'resolved') { $classes[] = 'adsc-finding--resolved'; }
?>
<div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-finding-id="<?php echo (int) ($finding->id ?? 0); ?>">
    <div class="adsc-finding-main">
        <div class="adsc-finding-title">
            <?php echo esc_html((string) ($finding->situation ?? '')); ?>
            <?php if ($is_carried_ignored): ?>
                <span class="adsc-carried-tag">Previously ignored &mdash; verify still not an issue</span>
            <?php elseif ($is_carried_resolved): ?>
                <span class="adsc-carried-tag">Previously resolved &mdash; verify still fixed</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($finding->url_example)): ?>
            <a class="adsc-finding-url" href="<?php echo esc_url((string) $finding->url_example); ?>" target="_blank" rel="noopener"><?php echo esc_html((string) $finding->url_example); ?></a>
        <?php endif; ?>
        <?php if (!empty($ev['_fix'])): ?>
            <div class="adsc-fix"><strong>How to fix:</strong> <?php echo esc_html((string) $ev['_fix']); ?></div>
        <?php endif; ?>
        <?php
        // Render any remaining evidence (excluding internal underscore keys) in collapsible details
        $public_ev = array_filter($ev, fn($v, $k) => strpos((string) $k, '_') !== 0, ARRAY_FILTER_USE_BOTH);
        if (!empty($public_ev)):
        ?>
            <details class="adsc-finding-details">
                <summary>Technical details</summary>
                <pre><?php echo esc_html(json_encode($public_ev, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
            </details>
        <?php endif; ?>
    </div>
    <div class="adsc-finding-action">
        <select class="adsc-status" aria-label="Status">
            <option value="pending"       <?php selected($status, 'pending'); ?>>Pending</option>
            <option value="manual_review" <?php selected($status, 'manual_review'); ?>>Manual review</option>
            <option value="resolved"      <?php selected($status, 'resolved'); ?>>Resolved</option>
            <option value="ignored"       <?php selected($status, 'ignored'); ?>>Ignore</option>
        </select>
    </div>
</div>
