<?php
namespace AdSenseChecklist\Admin;

use AdSenseChecklist\Ajax;
use AdSenseChecklist\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Page
{
    public const SLUG = 'adsense-checklist';
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        add_action('admin_menu',           [$this, 'add_menu']);
        add_action('admin_enqueue_scripts',[$this, 'enqueue_assets']);
        add_action('admin_init',           [$this, 'register_settings']);
    }

    public function add_menu(): void
    {
        add_management_page(
            'AdSense Checklist',
            'AdSense Checklist',
            'manage_options',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function enqueue_assets(string $hook): void
    {
        if ($hook !== 'tools_page_' . self::SLUG) {
            return;
        }
        wp_enqueue_style(
            'adsense-checklist-tokens',
            ADSENSE_CHECKLIST_URL . 'admin/css/tokens.css',
            [],
            ADSENSE_CHECKLIST_VERSION
        );
        wp_enqueue_style(
            'adsense-checklist-admin',
            ADSENSE_CHECKLIST_URL . 'admin/css/admin.css',
            ['adsense-checklist-tokens'],
            ADSENSE_CHECKLIST_VERSION
        );
        wp_enqueue_script(
            'adsense-checklist-admin',
            ADSENSE_CHECKLIST_URL . 'admin/js/admin.js',
            ['jquery'],
            ADSENSE_CHECKLIST_VERSION,
            true
        );
        wp_localize_script('adsense-checklist-admin', 'AdSenseChecklist', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(Ajax::NONCE_ACTION),
            'strings' => [
                'scanning' => 'Scanning…',
                'failed'   => 'Scan failed. Check the WP error log.',
            ],
        ]);
    }

    public function register_settings(): void
    {
        register_setting('adsense_checklist', \AdSenseChecklist\Settings::OPTION_KEY, [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }

    public function sanitize_settings($input): array
    {
        $input = is_array($input) ? $input : [];
        $clean = [];
        foreach (\AdSenseChecklist\Settings::DEFAULTS as $key => $default) {
            if (!isset($input[$key])) {
                $clean[$key] = is_bool($default) ? false : $default;
                continue;
            }
            if (is_bool($default)) {
                $clean[$key] = (bool) $input[$key];
            } elseif (is_int($default)) {
                $clean[$key] = max(0, (int) $input[$key]);
            } else {
                $clean[$key] = sanitize_text_field((string) $input[$key]);
            }
        }
        return $clean;
    }

    public function render(): void
    {
        $tab      = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'report';
        $repo     = $this->plugin->repository();
        $settings = $this->plugin->settings();
        $registry = include ADSENSE_CHECKLIST_PATH . 'includes/checklist-registry.php';
        $sections = include ADSENSE_CHECKLIST_PATH . 'includes/about-sections.php';

        // Build the data $context the partials need
        $scan_id  = $repo->latest_scan_id();
        $findings = $scan_id ? $repo->findings_for_scan($scan_id) : [];
        $score_data = \AdSenseChecklist\Score::compute($findings, $registry);
        $context  = [
            'scan_id'         => $scan_id,
            'findings'        => $findings,
            'total'           => count($registry),
            'effective_total' => $score_data['effective_total'],
            'ignored_checks'  => $score_data['ignored_checks'],
            'passed'          => $score_data['passed'],
            'resolved_checks' => $score_data['resolved_checks'],
            'passing_total'   => $score_data['passing_total'],
            'score'           => $score_data['score'],
            'site_host'       => parse_url((string) home_url(), PHP_URL_HOST) ?: '',
            'sections'        => $sections,
        ];

        echo '<div class="wrap adsense-checklist">';
        echo '<h1 style="display:none">AdSense Checklist</h1>'; // hidden - header partial renders the real title
        $base_url = admin_url('tools.php?page=' . self::SLUG);
        echo '<nav class="adsc-tabs">';
        foreach (['report' => 'Report', 'settings' => 'Settings', 'about' => 'About'] as $key => $label) {
            $class = $tab === $key ? 'adsc-tab adsc-tab--active' : 'adsc-tab';
            echo '<a href="' . esc_url($base_url . '&tab=' . $key) . '" class="' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';

        $view = ADSENSE_CHECKLIST_PATH . 'admin/views/' . $tab . '.php';
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<p>Unknown tab.</p>';
        }
        echo '</div>';
    }

}
