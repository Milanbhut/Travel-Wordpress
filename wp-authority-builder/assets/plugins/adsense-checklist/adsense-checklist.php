<?php
/**
 * Plugin Name: AdSense Checklist
 * Description: Scans a WordPress blog against a Google AdSense screening checklist and emits a report.
 * Version:     0.3.0
 * Author:      Cuelix
 * License:     GPL-2.0-or-later
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Text Domain: adsense-checklist
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ADSENSE_CHECKLIST_VERSION', '0.3.0');
define('ADSENSE_CHECKLIST_FILE', __FILE__);
define('ADSENSE_CHECKLIST_PATH', plugin_dir_path(__FILE__));
define('ADSENSE_CHECKLIST_URL', plugin_dir_url(__FILE__));

require_once ADSENSE_CHECKLIST_PATH . 'autoload.php';
adsense_checklist_register_autoloader(ADSENSE_CHECKLIST_PATH . 'includes');

// Bootstrap once everything is loaded.
add_action('plugins_loaded', function () {
    \AdSenseChecklist\Plugin::instance()->boot();
});

// Activation / uninstall hooks (defined in later tasks).
register_activation_hook(__FILE__, ['AdSenseChecklist\\Activator', 'activate']);
