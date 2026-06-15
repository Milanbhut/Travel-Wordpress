<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Mobile_Responsive_Check implements Check
{
    public function run(array $entry): array
    {
        $url = (string) home_url();
        $resp = wp_remote_get($url, ['timeout' => 10, 'redirection' => 3]);
        if (is_wp_error($resp) || (int) wp_remote_retrieve_response_code($resp) !== 200) {
            return [new Finding([
                'check_key'   => $entry['key'],
                'situation'   => 'Could not fetch homepage to verify mobile viewport meta.',
                'severity'    => $entry['severity'],
                'url_example' => $url,
            ])];
        }

        $body = (string) wp_remote_retrieve_body($resp);
        if (preg_match('/<meta[^>]+name=["\']viewport["\']/i', $body)) {
            return [];
        }

        return [new Finding([
            'check_key'   => $entry['key'],
            'situation'   => 'Homepage HTML is missing the viewport meta tag (theme is not mobile-responsive).',
            'severity'    => $entry['severity'],
            'url_example' => $url,
        ])];
    }
}
