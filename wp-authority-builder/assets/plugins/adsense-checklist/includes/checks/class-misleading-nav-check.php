<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Http_Cache;

if (!defined('ABSPATH')) {
    exit;
}

class Misleading_Nav_Check implements Check
{
    public function run(array $entry): array
    {
        $url = \home_url('/');
        $r   = Http_Cache::instance()->get($url);
        if ($r['error']) {
            return [new Finding([
                'check_key'   => $entry['key'],
                'situation'   => $entry['situation'] . ' — network error while probing home page.',
                'severity'    => $entry['severity'],
                'status'      => 'manual_review',
                'url_example' => $url,
                'evidence'    => ['_error' => 'wp_error'],
            ])];
        }
        $body = (string) $r['body'];
        $findings = [];

        // Pattern 1: <ins class="adsbygoogle"> or <ins class="adsense"> inside any <nav>...</nav>.
        if (preg_match_all('/<nav\b[^>]*>(.*?)<\/nav>/is', $body, $navs)) {
            foreach ($navs[1] as $i => $inner) {
                if (preg_match('/<ins\b[^>]*class\s*=\s*["\'][^"\']*(adsbygoogle|adsense)/i', $inner)) {
                    $findings[] = new Finding([
                        'check_key'   => $entry['key'],
                        'situation'   => $entry['situation'] . ' — ad inside navigation block #' . ($i + 1) . '.',
                        'severity'    => $entry['severity'],
                        'url_example' => $url,
                        'evidence'    => ['pattern' => 'ad_inside_nav'],
                    ]);
                    break; // one finding per page; user can fix all at once
                }
            }
        }

        // Pattern 2: <form action="external-host">...<input type="search">...</form>
        $home_host = parse_url($url, PHP_URL_HOST) ?: '';
        if (preg_match_all('/<form\b[^>]*action\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/form>/is', $body, $forms, PREG_SET_ORDER)) {
            foreach ($forms as $form) {
                $action = $form[1];
                $inner  = $form[2];
                if (!preg_match('/<input\b[^>]*type\s*=\s*["\']search["\']/i', $inner)) {
                    continue;
                }
                $action_host = parse_url($action, PHP_URL_HOST);
                if ($action_host !== null && $action_host !== '' && $action_host !== $home_host) {
                    $findings[] = new Finding([
                        'check_key'   => $entry['key'],
                        'situation'   => $entry['situation'] . " — fake search input submits to {$action_host}.",
                        'severity'    => $entry['severity'],
                        'url_example' => $url,
                        'evidence'    => ['pattern' => 'fake_search', 'action_host' => $action_host],
                    ]);
                    break;
                }
            }
        }

        // Pattern 3: <a href="external"> with text starting/containing "Download".
        if (preg_match_all('/<a\b[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>([^<]{0,80})<\/a>/i', $body, $links, PREG_SET_ORDER)) {
            foreach ($links as $link) {
                $href = $link[1];
                $text = strtolower(trim($link[2]));
                if (!preg_match('/\b(download|free download)\b/', $text)) {
                    continue;
                }
                $href_host = parse_url($href, PHP_URL_HOST);
                if ($href_host !== null && $href_host !== '' && $href_host !== $home_host) {
                    $findings[] = new Finding([
                        'check_key'   => $entry['key'],
                        'situation'   => $entry['situation'] . " — fake download link points to {$href_host}.",
                        'severity'    => $entry['severity'],
                        'url_example' => $url,
                        'evidence'    => ['pattern' => 'fake_download', 'link_host' => $href_host, 'link_text' => $text],
                    ]);
                    break;
                }
            }
        }

        return $findings;
    }
}
