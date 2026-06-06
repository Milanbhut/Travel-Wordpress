<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Publication_Cadence_Check implements Check
{
    private const WINDOW_DAYS     = 30;
    private const MIN_ACTIVE_DAYS = 15;

    public function run(array $entry): array
    {
        $posts = \get_posts([
            'post_status'    => 'publish',
            'post_type'      => 'post',
            'posts_per_page' => 500,
            'date_query'     => [['after' => self::WINDOW_DAYS . ' days ago']],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $days = [];
        foreach ((array) $posts as $post) {
            $date = (string) ($post->post_date ?? '');
            if ($date === '') continue;
            $day = substr($date, 0, 10); // YYYY-MM-DD
            $days[$day] = true;
        }
        $distinct = count($days);
        if ($distinct >= self::MIN_ACTIVE_DAYS) {
            return [];
        }
        return [new Finding([
            'check_key' => $entry['key'],
            'situation' => $entry['situation'] . " — only {$distinct} of the last " . self::WINDOW_DAYS . ' days had a post (recommended ' . self::MIN_ACTIVE_DAYS . ').',
            'severity'  => $entry['severity'],
            'evidence'  => [
                'distinct_days'   => $distinct,
                'window_days'     => self::WINDOW_DAYS,
                'min_active_days' => self::MIN_ACTIVE_DAYS,
            ],
        ])];
    }
}
