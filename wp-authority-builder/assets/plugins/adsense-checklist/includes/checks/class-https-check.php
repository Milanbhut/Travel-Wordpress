<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Https_Check implements Check
{
    public function run(array $entry): array
    {
        $site = (string) site_url();
        $home = (string) home_url();
        $ssl  = (bool) is_ssl();
        $site_ok = strpos($site, 'https://') === 0;
        $home_ok = strpos($home, 'https://') === 0;
        if ($ssl && $site_ok && $home_ok) {
            return [];
        }
        return [new Finding([
            'check_key' => $entry['key'],
            'situation' => 'Site is not enforcing HTTPS - Google rejects unencrypted sites algorithmically.',
            'severity'  => $entry['severity'],
            'url_example' => $site_ok ? null : $site,
            'evidence'  => [
                'is_ssl'   => $ssl,
                'site_url' => $site,
                'home_url' => $home,
            ],
        ])];
    }
}
