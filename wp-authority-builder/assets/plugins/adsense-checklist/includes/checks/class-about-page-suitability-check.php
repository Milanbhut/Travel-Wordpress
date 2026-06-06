<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class About_Page_Suitability_Check implements Check
{
    public const MIN_WORDS = 300;
    private const ALIASES   = ['about', 'about us', 'about-us', 'about-me', 'who-we-are', 'who we are', 'about me'];
    private const KEYWORDS  = ['about'];

    public function run(array $entry): array
    {
        $pages = get_pages(['post_status' => 'publish']);
        $about = null;
        foreach ((array) $pages as $page) {
            $title = strtolower((string) ($page->post_title ?? ''));
            $slug  = strtolower((string) ($page->post_name ?? ''));
            if (in_array($title, self::ALIASES, true) || in_array($slug, self::ALIASES, true)) {
                $about = $page;
                break;
            }
            $tokens = array_merge(
                preg_split('/[-_\s&]+/', $slug)  ?: [],
                preg_split('/[-_\s&]+/', $title) ?: []
            );
            $tokens = array_filter(array_map('trim', $tokens));
            foreach (self::KEYWORDS as $kw) {
                if (in_array($kw, $tokens, true)) {
                    $about = $page;
                    break 2;
                }
            }
        }

        if ($about === null) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'About page is missing.',
                'severity'  => $entry['severity'],
            ])];
        }

        $text  = wp_strip_all_tags((string) ($about->post_content ?? ''));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', trim($text)));

        if ($words < self::MIN_WORDS) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'About page is too thin (< ' . self::MIN_WORDS . ' words).',
                'severity'  => $entry['severity'],
                'url_example' => get_permalink($about->ID),
                'evidence'  => ['word_count' => $words, 'min_required' => self::MIN_WORDS],
            ])];
        }
        return [];
    }
}
