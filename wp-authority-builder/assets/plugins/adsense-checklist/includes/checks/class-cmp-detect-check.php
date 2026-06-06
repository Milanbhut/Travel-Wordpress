<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Cmp_Detect_Check implements Check
{
    private const SIGNATURES = [
        'Cookiebot'        => ['consent.cookiebot.com', 'data-cbid='],
        'OneTrust'         => ['cookielaw.org', 'otSDKStub.js'],
        'Quantcast Choice' => ['quantcast.mgr.consensu.org', 'choice.js'],
        'Sourcepoint'      => ['sourcepoint.mgr.consensu.org', 'cmp.sp-prod.net'],
        'Didomi'           => ['sdk.privacy-center.org', 'didomi.io'],
        'iubenda'          => ['cdn.iubenda.com', 'iubenda_policy'],
        'Complianz'        => ['complianz-gdpr', '/cmplz/'],
        'CookieYes'        => ['cookie-law-info', 'cookieyes.com'],
    ];

    public function run(array $entry): array
    {
        $url = (string) home_url();
        $resp = wp_remote_get($url, ['timeout' => 10, 'redirection' => 3]);
        if (is_wp_error($resp) || (int) wp_remote_retrieve_response_code($resp) !== 200) {
            return [new Finding([
                'check_key'   => $entry['key'],
                'situation'   => 'Could not fetch homepage to detect a CMP.',
                'severity'    => $entry['severity'],
                'url_example' => $url,
            ])];
        }

        $body = (string) wp_remote_retrieve_body($resp);
        foreach (self::SIGNATURES as $name => $needles) {
            foreach ($needles as $needle) {
                if (stripos($body, $needle) !== false) {
                    return [];
                }
            }
        }

        return [new Finding([
            'check_key'   => $entry['key'],
            'situation'   => 'No certified CMP script detected on the homepage (required for EU/UK ad serving).',
            'severity'    => $entry['severity'],
            'url_example' => $url,
            'evidence'    => ['searched_vendors' => array_keys(self::SIGNATURES)],
        ])];
    }
}
