<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

class Pages
{
    /**
     * Find the first published page whose slug or title matches one of $aliases
     * (exact, case-insensitive) OR whose slug/title tokens include one of $keywords.
     *
     * @param string[] $aliases  Exact slug-or-title matches (lowercased internally).
     * @param string[] $keywords Token matches; slug/title split on -_ \s &.
     * @return object|null WP_Post-like object with ID/post_title/post_name, or null.
     */
    public static function find_by_aliases(array $aliases, array $keywords): ?object
    {
        $aliases  = array_map('strtolower', $aliases);
        $keywords = array_map('strtolower', $keywords);
        if (empty($aliases) && empty($keywords)) {
            return null;
        }
        $pages = \get_pages(['post_status' => 'publish']);
        foreach ((array) $pages as $page) {
            if (($page->post_status ?? '') !== 'publish') {
                continue;
            }
            $title = strtolower((string) ($page->post_title ?? ''));
            $slug  = strtolower((string) ($page->post_name  ?? ''));
            if (in_array($title, $aliases, true) || in_array($slug, $aliases, true)) {
                return $page;
            }
            if (!empty($keywords)) {
                $tokens = array_merge(
                    preg_split('/[-_\s&]+/', $slug)  ?: [],
                    preg_split('/[-_\s&]+/', $title) ?: []
                );
                $tokens = array_filter(array_map('trim', $tokens));
                foreach ($keywords as $kw) {
                    if (in_array($kw, $tokens, true)) {
                        return $page;
                    }
                }
            }
        }
        return null;
    }
}
