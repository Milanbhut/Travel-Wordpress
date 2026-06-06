<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Privacy_Disclosure_Check implements Check
{
    private const REQUIRED_TERMS = ['third-party', 'google', 'cookies', 'opt-out'];
    private const PRIVACY_ALIASES = ['privacy', 'privacy policy', 'privacy-policy'];

    public function run(array $entry): array
    {
        $pages = get_pages(['post_status' => 'publish']);
        $privacy = null;
        foreach ((array) $pages as $page) {
            $title = strtolower((string) ($page->post_title ?? ''));
            $slug  = strtolower((string) ($page->post_name ?? ''));
            if (in_array($title, self::PRIVACY_ALIASES, true) || in_array($slug, self::PRIVACY_ALIASES, true)) {
                $privacy = $page;
                break;
            }
        }

        if ($privacy === null) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'Privacy Policy page not found — cannot evaluate disclosure terms.',
                'severity'  => $entry['severity'],
            ])];
        }

        $text = strtolower(wp_strip_all_tags((string) ($privacy->post_content ?? '')));
        $missing = [];
        foreach (self::REQUIRED_TERMS as $term) {
            if (strpos($text, $term) === false) {
                $missing[] = $term;
            }
        }

        if (empty($missing)) {
            return [];
        }

        return [new Finding([
            'check_key'   => $entry['key'],
            'situation'   => 'Privacy Policy is missing required disclosure terms: ' . implode(', ', $missing),
            'severity'    => $entry['severity'],
            'url_example' => get_permalink($privacy->ID),
            'evidence'    => ['missing_terms' => $missing, 'required' => self::REQUIRED_TERMS],
        ])];
    }
}
