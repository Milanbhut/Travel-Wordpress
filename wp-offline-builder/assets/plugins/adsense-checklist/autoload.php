<?php
/**
 * Shared PSR-4 autoloader with WordPress-style class-foo-bar.php filename convention.
 * Registered from two places: the plugin main file (production) and tests/bootstrap.php (testing).
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('adsense_checklist_register_autoloader')) {
    function adsense_checklist_register_autoloader(string $includes_dir): void
    {
        $includes_dir = rtrim($includes_dir, "/\\");
        spl_autoload_register(function ($class) use ($includes_dir) {
            $prefix = 'AdSenseChecklist\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $parts    = explode('\\', $relative);
            $file     = array_pop($parts);
            $file     = preg_replace('/(?<!^)([A-Z])/', '-$1', $file);
            $file     = strtolower(str_replace('_', '-', $file));
            $file     = preg_replace('/-+/', '-', $file);
            $file     = 'class-' . $file . '.php';
            $subdir   = $parts ? strtolower(implode('/', $parts)) . '/' : '';
            $path     = $includes_dir . '/' . $subdir . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        });
    }
}
