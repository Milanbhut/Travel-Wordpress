<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Broken_Links_Check implements Check
{
    public function run(array $entry): array
    {
        $settings      = new Settings();
        $max_posts     = (int) $settings->get('max_posts_to_scan');
        $scan_external = (bool) $settings->get('scan_external_links');
        $home          = rtrim((string) home_url(), '/');
        $home_host     = parse_url($home, PHP_URL_HOST) ?: '';

        $posts = get_posts([
            'post_status'    => 'publish',
            'post_type'      => ['post', 'page'],
            'posts_per_page' => $max_posts,
        ]);

        $by_url = [];
        foreach ((array) $posts as $post) {
            $html = (string) ($post->post_content ?? '');
            if (!preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
                continue;
            }
            foreach ($m[1] as $href) {
                $target = $this->normalize($href, $home);
                if ($target === null) continue;
                $target_host = parse_url($target, PHP_URL_HOST) ?: '';
                $is_external = $target_host !== '' && $target_host !== $home_host;
                if ($is_external && !$scan_external) continue;
                $by_url[$target][] = (int) $post->ID;
            }
        }

        $findings = [];
        foreach ($by_url as $url => $source_ids) {
            $resp = wp_remote_head($url, ['timeout' => 10, 'redirection' => 3]);
            $code = is_wp_error($resp) ? 0 : (int) wp_remote_retrieve_response_code($resp);
            if ($code >= 400 || $code === 0) {
                $first_source = $source_ids[0];
                $findings[] = new Finding([
                    'check_key'   => $entry['key'] . '.' . md5($url),
                    'situation'   => "Broken link ({$code}) on post — target: {$url}",
                    'severity'    => $entry['severity'],
                    'url_example' => get_permalink($first_source),
                    'evidence'    => [
                        'broken_url'    => $url,
                        'response_code' => $code,
                        'source_posts'  => $source_ids,
                    ],
                ]);
            }
        }
        return $findings;
    }

    private function normalize(string $href, string $home_base): ?string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($href === '' || strpos($href, '#') === 0 || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0 || strpos($href, 'javascript:') === 0) {
            return null;
        }
        if (strpos($href, '//') === 0) {
            return 'https:' . $href;
        }
        if ($href[0] === '/') {
            return $home_base . $href;
        }
        if (!preg_match('#^https?://#i', $href)) {
            return null;
        }
        return $href;
    }
}
