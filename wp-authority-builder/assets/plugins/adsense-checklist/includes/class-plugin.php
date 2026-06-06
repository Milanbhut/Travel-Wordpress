<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    private static ?Plugin $instance = null;
    private Settings $settings;
    private Repository $repository;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->settings   = new Settings();
        $this->repository = new Repository();
    }

    public function settings(): Settings { return $this->settings; }
    public function repository(): Repository { return $this->repository; }

    public function boot(): void
    {
        (new Ajax())->register();
        if (is_admin() && class_exists('AdSenseChecklist\\Admin\\Admin_Page')) {
            (new Admin\Admin_Page($this))->register();
        }
    }
}
