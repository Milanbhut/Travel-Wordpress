<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Categories_Volume_Check implements Check
{
    public const MIN_POSTS = 3;

    public function run(array $entry): array
    {
        $terms = get_terms([
            'taxonomy'   => 'category',
            'hide_empty' => false,
        ]);
        if (!is_array($terms)) return [];

        $findings = [];
        foreach ($terms as $term) {
            $count = (int) ($term->count ?? 0);
            $slug  = (string) ($term->slug ?? '');
            if ($count === 0 && $slug === 'uncategorized') continue;
            if ($count >= self::MIN_POSTS) continue;

            $findings[] = new Finding([
                'check_key'   => $entry['key'] . '.' . $slug,
                'situation'   => "Category '" . ($term->name ?? $slug) . "' has only {$count} posts (minimum " . self::MIN_POSTS . ').',
                'severity'    => $entry['severity'],
                'url_example' => (string) get_term_link((int) $term->term_id),
                'evidence'    => [
                    'category_name' => (string) ($term->name ?? $slug),
                    'post_count'    => $count,
                    'min_required'  => self::MIN_POSTS,
                ],
            ]);
        }
        return $findings;
    }
}
