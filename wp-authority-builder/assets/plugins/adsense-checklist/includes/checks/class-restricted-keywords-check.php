<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Restricted_Keywords_Check implements Check
{
    public function run(array $entry): array
    {
        $args     = $entry['check_args'] ?? [];
        $keywords = (array) ($args['keywords'] ?? []);
        $opt_in   = $args['opt_in_setting'] ?? null;

        if (empty($keywords)) {
            return [];
        }

        $settings = new Settings();
        if ($opt_in !== null && !((bool) $settings->get($opt_in))) {
            return [];
        }

        $max_posts = (int) $settings->get('max_posts_to_scan');
        $posts = get_posts([
            'post_status'    => 'publish',
            'post_type'      => ['post', 'page'],
            'posts_per_page' => $max_posts,
        ]);

        $escaped = array_map(fn($k) => preg_quote($k, '/'), $keywords);
        $pattern = '/\b(' . implode('|', $escaped) . ')\b/i';

        $findings = [];
        foreach ((array) $posts as $post) {
            $text = wp_strip_all_tags(((string) ($post->post_title ?? '')) . "\n" . ((string) ($post->post_content ?? '')));
            if (!preg_match_all($pattern, $text, $m)) {
                continue;
            }
            $hits = array_count_values(array_map('strtolower', $m[1]));
            arsort($hits);
            $top = array_key_first($hits);

            $findings[] = new Finding([
                'check_key'   => $entry['key'] . '.post_' . $post->ID,
                'situation'   => $entry['situation'] . " — possible match in post (keyword: '{$top}').",
                'severity'    => $entry['severity'],
                'status'      => 'manual_review',
                'url_example' => (string) get_permalink($post->ID),
                'evidence'    => [
                    'post_id'            => (int) $post->ID,
                    'post_title'         => (string) ($post->post_title ?? ''),
                    'matches'            => $hits,
                    'searched_keywords'  => $keywords,
                ],
            ]);
        }
        return $findings;
    }
}
