<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Http_Cache;

if (!defined('ABSPATH')) {
    exit;
}

class Analytics_Check implements Check
{
    private const PASS_SIGNATURES = [
        'googletagmanager.com/gtm.js',
        'googletagmanager.com/gtag/js',
        'google-analytics.com/g/collect',
        'gtag(',
    ];

    public function run(array $entry): array
    {
        $url = \home_url('/');
        $r   = Http_Cache::instance()->get($url);
        if ($r['error']) {
            return [new Finding([
                'check_key'   => $entry['key'],
                'situation'   => $entry['situation'] . ' - network error while probing.',
                'severity'    => $entry['severity'],
                'status'      => 'manual_review',
                'url_example' => $url,
                'evidence'    => ['_error' => 'wp_error'],
            ])];
        }
        $body = (string) $r['body'];
        foreach (self::PASS_SIGNATURES as $needle) {
            if (strpos($body, $needle) !== false) {
                return [];
            }
        }
        // Legacy UA detection - fail with a more specific message.
        if (preg_match('/UA-\d+-\d+/', $body)) {
            return [new Finding([
                'check_key'   => $entry['key'],
                'situation'   => $entry['situation'] . ' - only legacy Universal Analytics found; UA was sunset July 2023.',
                'severity'    => $entry['severity'],
                'url_example' => $url,
                'evidence'    => ['detected' => 'legacy_ua'],
            ])];
        }
        return [new Finding([
            'check_key'   => $entry['key'],
            'situation'   => $entry['situation'] . ' - no analytics tag detected on home page.',
            'severity'    => $entry['severity'],
            'url_example' => $url,
            'evidence'    => ['detected' => 'none'],
        ])];
    }
}
