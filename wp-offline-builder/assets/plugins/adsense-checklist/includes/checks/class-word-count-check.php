<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Word_Count_Check implements Check
{
    public function run(array $entry): array
    {
        $settings     = new Settings();
        $max_posts    = (int) $settings->get('max_posts_to_scan');
        // Settings is authoritative; registry check_args provides a fallback default.
        $min_words    = (int) ($settings->get('min_word_count') ?? $entry['check_args']['min_words'] ?? 1000);
        $min_headings = (int) ($entry['check_args']['min_headings'] ?? 0);

        $posts = get_posts([
            'post_status'    => 'publish',
            'post_type'      => 'post',
            'posts_per_page' => $max_posts,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $findings = [];
        foreach ((array) $posts as $post) {
            $html     = (string) ($post->post_content ?? '');
            $text     = wp_strip_all_tags($html);
            $words    = $text === '' ? 0 : count(preg_split('/\s+/u', trim($text)));
            $h2_count = preg_match_all('/<h2[\s>]/i', $html);

            $too_short = $words < $min_words;
            $too_flat  = $min_headings > 0 && $h2_count < $min_headings;

            if ($too_short || $too_flat) {
                $issues = [];
                if ($too_short) $issues[] = "{$words} words (min {$min_words})";
                if ($too_flat)  $issues[] = "{$h2_count} H2 (min {$min_headings})";

                $findings[] = new Finding([
                    'check_key'   => $entry['key'] . '.post_' . $post->ID,
                    'situation'   => $entry['situation'] . ' [' . implode(', ', $issues) . ']',
                    'severity'    => $entry['severity'],
                    'url_example' => get_permalink($post->ID),
                    'evidence'    => [
                        'post_id'    => (int) $post->ID,
                        'post_title' => (string) ($post->post_title ?? ''),
                        'word_count' => $words,
                        'h2_count'   => (int) $h2_count,
                        'min_words'  => $min_words,
                    ],
                ]);
            }
        }
        return $findings;
    }
}
