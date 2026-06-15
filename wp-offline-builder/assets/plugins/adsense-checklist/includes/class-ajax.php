<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax
{
    public const NONCE_ACTION = 'adsense_checklist';

    public function register(): void
    {
        add_action('wp_ajax_adsense_checklist_scan',    [$this, 'handle_scan']);
        add_action('wp_ajax_adsense_checklist_resolve', [$this, 'handle_resolve']);
        add_action('wp_ajax_adsense_checklist_export',  [$this, 'handle_export']);
    }

    public function handle_scan(): void
    {
        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions', 403);
            return;
        }

        try {
            $registry = include ADSENSE_CHECKLIST_PATH . 'includes/checklist-registry.php';
            $scanner  = new Scanner($registry, Plugin::instance()->repository());
            $scan_id  = $scanner->scan();
            wp_send_json_success(['scan_id' => $scan_id]);
        } catch (\Throwable $e) {
            error_log('[adsense-checklist] scan failed: ' . $e->getMessage());
            wp_send_json_error('Scan failed: ' . $e->getMessage(), 500);
        }
    }

    public function handle_resolve(): void
    {
        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions', 403);
            return;
        }

        $finding_id = isset($_POST['finding_id']) ? (int) $_POST['finding_id'] : 0;
        $status     = isset($_POST['status']) ? sanitize_text_field((string) $_POST['status']) : 'resolved';
        if ($finding_id <= 0) {
            wp_send_json_error('Missing finding_id', 400);
            return;
        }

        try {
            $repo = Plugin::instance()->repository();
            if ($status === 'resolved') {
                $repo->mark_resolved($finding_id, get_current_user_id());
            } else {
                $repo->mark_status($finding_id, $status);
            }
            wp_send_json_success(['finding_id' => $finding_id, 'status' => $status]);
        } catch (\Throwable $e) {
            wp_send_json_error('Update failed: ' . $e->getMessage(), 500);
        }
    }

    public function handle_export(): void
    {
        if (!check_ajax_referer(self::NONCE_ACTION, '_wpnonce', false)) {
            wp_die('Invalid nonce', 'Forbidden', ['response' => 403]);
        }
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden', 'Forbidden', ['response' => 403]);
        }

        $format = isset($_GET['format']) ? sanitize_key((string) $_GET['format']) : 'csv';
        $repo   = Plugin::instance()->repository();
        $scan_id = $repo->latest_scan_id();
        $rows = $scan_id ? $repo->findings_for_scan($scan_id) : [];

        // Exclude synthetic passed rows from exports.
        $rows = array_values(array_filter($rows, fn($r) => ($r->status ?? '') !== 'passed'));

        $exporter = new Exporter();
        $date = gmdate('Y-m-d');
        $site = parse_url((string) home_url(), PHP_URL_HOST) ?: 'site';

        if ($format === 'md') {
            header('Content-Type: text/markdown; charset=utf-8');
            header("Content-Disposition: attachment; filename=adsense-checklist-report-{$date}.md");
            echo $exporter->to_markdown($rows, ['site' => $site, 'date' => $date]);
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=adsense-checklist-report-{$date}.csv");
            echo $exporter->to_csv($rows);
        }
        exit;
    }
}
