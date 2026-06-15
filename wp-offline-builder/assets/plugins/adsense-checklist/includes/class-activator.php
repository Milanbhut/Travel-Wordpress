<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

class Activator
{
    public static function activate(): void
    {
        global $wpdb;

        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $charset_collate = $wpdb->get_charset_collate();
        $findings        = $wpdb->prefix . 'adsense_checklist_findings';
        $scans           = $wpdb->prefix . 'adsense_checklist_scans';

        $sql_findings = "CREATE TABLE {$findings} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_id BIGINT UNSIGNED NOT NULL,
            check_key VARCHAR(64) NOT NULL,
            situation TEXT NOT NULL,
            severity VARCHAR(16) NOT NULL DEFAULT 'moderate',
            url_example TEXT NULL,
            evidence LONGTEXT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            resolved_by BIGINT UNSIGNED NULL,
            PRIMARY KEY  (id),
            KEY scan_id (scan_id),
            KEY check_key (check_key),
            KEY scan_status (scan_id, status)
        ) {$charset_collate};";

        $sql_scans = "CREATE TABLE {$scans} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME NULL,
            findings_count INT NOT NULL DEFAULT 0,
            passed_count INT NOT NULL DEFAULT 0,
            notes TEXT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        dbDelta($sql_findings);
        dbDelta($sql_scans);

        if (defined('ADSENSE_CHECKLIST_VERSION')) {
            update_option('adsense_checklist_db_version', ADSENSE_CHECKLIST_VERSION);
        }
    }
}
