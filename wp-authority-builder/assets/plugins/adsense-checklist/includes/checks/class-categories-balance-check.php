<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Categories_Balance_Check implements Check
{
    private const MIN_POSTS_PER_CATEGORY = 15;

    public function run(array $entry): array
    {
        $cats = \get_categories(['hide_empty' => false]);
        $findings = [];
        foreach ((array) $cats as $cat) {
            $name  = (string) ($cat->name  ?? '');
            $slug  = (string) ($cat->slug  ?? '');
            $count = (int)    ($cat->count ?? 0);
            $id    = (int)    ($cat->term_id ?? 0);

            // Skip the WP default "Uncategorized" when it has no posts.
            if ($count === 0 && strtolower($slug) === 'uncategorized') {
                continue;
            }
            if ($count >= self::MIN_POSTS_PER_CATEGORY) {
                continue;
            }
            $findings[] = new Finding([
                'check_key'   => $entry['key'] . '.cat_' . $id,
                'situation'   => $entry['situation'] . " - '{$name}' has only {$count} posts (min " . self::MIN_POSTS_PER_CATEGORY . ').',
                'severity'    => $entry['severity'],
                'url_example' => \get_category_link($id),
                'evidence'    => [
                    'category_id'   => $id,
                    'category_name' => $name,
                    'post_count'    => $count,
                    'min_required'  => self::MIN_POSTS_PER_CATEGORY,
                ],
            ]);
        }
        return $findings;
    }
}
