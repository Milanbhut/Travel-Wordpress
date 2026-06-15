<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Author_Box_Check implements Check
{
    private const MARKERS = [
        'class="author-bio"',
        'class="author-box"',
        'class="entry-author"',
        'class="post-author"',
        'rel="author"',
        'itemprop="author"',
    ];

    public function run(array $entry): array
    {
        $posts = get_posts(['post_status' => 'publish', 'post_type' => 'post', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC']);
        if (empty($posts)) {
            return [];
        }
        $url  = (string) get_permalink($posts[0]->ID);
        $resp = wp_remote_get($url, ['timeout' => 10, 'redirection' => 3]);
        if (is_wp_error($resp) || (int) wp_remote_retrieve_response_code($resp) !== 200) {
            return [new Finding([
                'check_key'   => $entry['key'],
                'situation'   => 'Could not fetch a published post to check for an author box.',
                'severity'    => $entry['severity'],
                'url_example' => $url,
            ])];
        }

        $body = (string) wp_remote_retrieve_body($resp);
        foreach (self::MARKERS as $marker) {
            if (stripos($body, $marker) !== false) {
                return [];
            }
        }

        return [new Finding([
            'check_key'   => $entry['key'],
            'situation'   => 'No author-bio markers found on a sample post (theme may not render an author box).',
            'severity'    => $entry['severity'],
            'url_example' => $url,
            'evidence'    => ['searched_markers' => self::MARKERS],
        ])];
    }
}
