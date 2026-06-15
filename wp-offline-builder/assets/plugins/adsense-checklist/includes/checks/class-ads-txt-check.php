<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Ads_Txt_Check implements Check
{
    public function run(array $entry): array
    {
        $url = trailingslashit(home_url()) . 'ads.txt';
        $response = wp_remote_get($url, ['timeout' => 10, 'redirection' => 2]);

        if (is_wp_error($response)) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'ads.txt is not reachable.',
                'severity'  => $entry['severity'],
                'url_example' => $url,
                'evidence'  => ['error' => 'wp_remote_get error'],
            ])];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        if ($code !== 200) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'ads.txt is not reachable.',
                'severity'  => $entry['severity'],
                'url_example' => $url,
                'evidence'  => ['response_code' => $code],
            ])];
        }

        $trimmed = trim($body);
        if ($trimmed === '' || stripos($trimmed, 'google.com,') === false) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'ads.txt exists but does not contain an AdSense (google.com, pub-...) line.',
                'severity'  => $entry['severity'],
                'url_example' => $url,
                'evidence'  => [
                    'reason'    => $trimmed === '' ? 'empty file' : 'no google.com line',
                    'body_head' => substr($trimmed, 0, 200),
                ],
            ])];
        }
        return [];
    }
}
