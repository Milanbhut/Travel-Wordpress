<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Http_Cache;

if (!defined('ABSPATH')) {
    exit;
}

class CDN_Check implements Check
{
    /** Header => substring it should contain (case-insensitive). Empty substring = presence-only. */
    private const SIGNATURES = [
        'cf-ray'              => '',          // Cloudflare
        'cf-cache-status'     => '',          // Cloudflare
        'x-served-by'         => '',          // Fastly
        'x-cdn'               => '',          // generic / BunnyCDN advertise this
        'x-akamai-request-id' => '',          // Akamai
        'server'              => 'cloudfront',// AWS CloudFront
    ];
    private const SERVER_SUBSTRINGS = ['cloudflare', 'cloudfront', 'akamaighost', 'fastly'];

    public function run(array $entry): array
    {
        $url = \home_url('/');
        $r   = Http_Cache::instance()->head($url);
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
        $headers = $this->lowercase_keys($r['headers']);
        if ($this->matches_any_signature($headers)) {
            return [];
        }
        return [new Finding([
            'check_key'   => $entry['key'],
            'situation'   => $entry['situation'] . ' - no CDN headers detected.',
            'severity'    => $entry['severity'],
            'url_example' => $url,
            'evidence'    => ['server_header' => $headers['server'] ?? ''],
        ])];
    }

    private function matches_any_signature(array $headers): bool
    {
        foreach (self::SIGNATURES as $name => $needle) {
            if (!isset($headers[$name])) {
                continue;
            }
            $value = strtolower((string) (is_array($headers[$name]) ? implode(' ', $headers[$name]) : $headers[$name]));
            if ($needle === '' || strpos($value, $needle) !== false) {
                return true;
            }
        }
        $server = strtolower((string) ($headers['server'] ?? ''));
        foreach (self::SERVER_SUBSTRINGS as $sub) {
            if (strpos($server, $sub) !== false) {
                return true;
            }
        }
        return false;
    }

    private function lowercase_keys(array $headers): array
    {
        $out = [];
        foreach ($headers as $k => $v) {
            $out[strtolower((string) $k)] = $v;
        }
        return $out;
    }
}
