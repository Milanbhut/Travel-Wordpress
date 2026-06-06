<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Post_Visuals_Check implements Check
{
    private const WORDS_FLOOR = 100;

    public function run(array $entry): array
    {
        $settings  = new Settings();
        $max_posts = (int) $settings->get('max_posts_to_scan');
        $posts = \get_posts([
            'post_status'    => 'publish',
            'post_type'      => 'post',
            'posts_per_page' => $max_posts,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $findings = [];
        foreach ((array) $posts as $post) {
            $html  = (string) ($post->post_content ?? '');
            $text  = \wp_strip_all_tags($html);
            $words = $text === '' ? 0 : count(preg_split('/\s+/u', trim($text)));
            if ($words < self::WORDS_FLOOR) {
                continue;
            }
            if ($this->has_visual($html, (int) $post->ID)) {
                continue;
            }
            $findings[] = new Finding([
                'check_key'   => $entry['key'] . '.post_' . (int) $post->ID,
                'situation'   => $entry['situation'] . ' — no visuals in this post.',
                'severity'    => $entry['severity'],
                'url_example' => \get_permalink($post->ID),
                'evidence'    => [
                    'post_id'    => (int) $post->ID,
                    'post_title' => (string) ($post->post_title ?? ''),
                    'word_count' => $words,
                ],
            ]);
        }
        return $findings;
    }

    private function has_visual(string $html, int $post_id): bool
    {
        if (\has_post_thumbnail($post_id)) {
            return true;
        }
        return (bool) preg_match('/<(img|video|iframe|picture)\b/i', $html);
    }
}
