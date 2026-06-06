<?php
/**
 * Page header partial.
 *
 * @var array $context Data from Admin_Page::render():
 *   - scan_id    int|null   Latest scan id (null = no scan yet)
 *   - site_host  string     Host extracted from home_url()
 *   - findings   object[]   Finding rows (we use only the latest created_at for the header meta)
 *
 * Optional caller-provided keys via $header_args (defaulted below):
 *   - subtitle    string  Top breadcrumb-style label (default "AdSense Readiness")
 *   - title       string  Big H1 text (default "Scan Report")
 *   - show_actions bool   Whether to render Run/Export buttons (default true on Report)
 */
if (!defined('ABSPATH')) { exit; }

$header_args = $header_args ?? [];
$subtitle     = $header_args['subtitle']     ?? 'AdSense Readiness';
$title        = $header_args['title']        ?? 'Scan Report';
$show_actions = $header_args['show_actions'] ?? true;

$scan_id   = $context['scan_id']   ?? null;
$site_host = $context['site_host'] ?? '';

// Compute meta line
$meta_parts = [];
if ($site_host !== '') {
    $meta_parts[] = esc_html($site_host);
}
if ($scan_id) {
    // findings_for_scan rows carry created_at; the latest one is the scan's effective "last scan" timestamp
    $latest_ts = null;
    foreach (($context['findings'] ?? []) as $f) {
        $ts = isset($f->created_at) ? strtotime((string) $f->created_at) : false;
        if ($ts !== false && ($latest_ts === null || $ts > $latest_ts)) {
            $latest_ts = $ts;
        }
    }
    if ($latest_ts) {
        $diff = function_exists('human_time_diff') ? human_time_diff($latest_ts, time()) : (int) ((time() - $latest_ts) / 60) . ' minutes';
        $meta_parts[] = 'last scan ' . esc_html($diff) . ' ago';
    }
}
$meta = implode(' &middot; ', $meta_parts);

$nonce = wp_create_nonce(\AdSenseChecklist\Ajax::NONCE_ACTION);
$ajax  = admin_url('admin-ajax.php');
?>
<header class="adsc-page-header">
    <div class="adsc-page-header-text">
        <div class="adsc-subtitle"><?php echo esc_html($subtitle); ?></div>
        <h1 class="adsc-title"><?php echo esc_html($title); ?></h1>
        <?php if ($meta !== ''): ?>
            <div class="adsc-meta"><?php echo $meta; /* already escaped piecewise above */ ?></div>
        <?php endif; ?>
    </div>
    <?php if ($show_actions): ?>
        <div class="adsc-page-header-actions">
            <a class="adsc-btn adsc-btn--secondary" href="<?php echo esc_url($ajax . '?action=adsense_checklist_export&format=csv&_wpnonce=' . $nonce); ?>">&#8595; CSV</a>
            <a class="adsc-btn adsc-btn--secondary" href="<?php echo esc_url($ajax . '?action=adsense_checklist_export&format=md&_wpnonce=' . $nonce); ?>">&#8595; Markdown</a>
            <button type="button" class="adsc-btn adsc-btn--primary" id="adsc-scan">&#9654; Run Scan</button>
            <span id="adsc-scan-status" class="adsc-scan-status"></span>
        </div>
    <?php endif; ?>
</header>
