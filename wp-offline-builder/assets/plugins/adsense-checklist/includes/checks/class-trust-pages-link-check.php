<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Trust_Pages_Link_Check implements Check
{
    private const SLOTS = [
        'About'   => ['about', 'about us', 'about-us', 'about-me', 'who-we-are'],
        'Contact' => ['contact', 'contact us', 'contact-us', 'reach-us', 'get-in-touch'],
        'Privacy' => ['privacy', 'privacy policy', 'privacy-policy', 'privacy-notice'],
        'Terms'   => ['terms', 'terms of service', 'terms of use', 'terms-of-service', 'terms-of-use', 'terms-and-conditions', 'tos', 'legal'],
    ];

    public function run(array $entry): array
    {
        $titles_lower = [];
        foreach ((array) wp_get_nav_menus() as $menu) {
            foreach ((array) wp_get_nav_menu_items($menu->term_id) as $item) {
                $title = strtolower(trim((string) ($item->title ?? '')));
                $url   = strtolower(trim((string) ($item->url ?? '')));
                $titles_lower[] = $title;
                $titles_lower[] = trim($url, '/');
            }
        }

        $findings = [];
        foreach (self::SLOTS as $slot => $aliases) {
            $found = false;
            foreach ($aliases as $alias) {
                foreach ($titles_lower as $t) {
                    if ($t === $alias || strpos($t, $alias) !== false) {
                        $found = true; break 2;
                    }
                }
            }
            if (!$found) {
                $findings[] = new Finding([
                    'check_key' => $entry['key'] . '.' . strtolower($slot),
                    'situation' => $slot . ' page is not linked from any registered menu.',
                    'severity'  => $entry['severity'],
                    'evidence'  => ['searched_aliases' => $aliases],
                ]);
            }
        }
        return $findings;
    }
}
