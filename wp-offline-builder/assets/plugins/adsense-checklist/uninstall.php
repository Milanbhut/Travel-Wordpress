<?php
// Fired by WP when the plugin is deleted via the admin UI.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$findings = $wpdb->prefix . 'adsense_checklist_findings';
$scans    = $wpdb->prefix . 'adsense_checklist_scans';

$wpdb->query("DROP TABLE IF EXISTS {$findings}");
$wpdb->query("DROP TABLE IF EXISTS {$scans}");

delete_option('adsense_checklist_settings');
delete_option('adsense_checklist_db_version');
