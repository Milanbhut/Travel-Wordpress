<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Article_Count_Check implements Check
{
    public function run(array $entry): array
    {
        // check_args wins when set (different entries express different tiers).
        // Otherwise fall back to Settings, then to hard default 20.
        // NB: This is the OPPOSITE precedence from Word_Count_Check, where Settings
        // is authoritative because there is only one tier of word-count threshold.
        if (isset($entry['check_args']['min_articles'])) {
            $min = (int) $entry['check_args']['min_articles'];
        } else {
            $settings = new Settings();
            $min = (int) $settings->get('min_article_count');
            if ($min <= 0) {
                $min = 20;
            }
        }
        $counts = wp_count_posts();
        $publish = isset($counts->publish) ? (int) $counts->publish : 0;
        if ($publish >= $min) {
            return [];
        }
        return [new Finding([
            'check_key' => $entry['key'],
            'situation' => "Site has only {$publish} published posts (minimum recommended: {$min}).",
            'severity'  => $entry['severity'],
            'evidence'  => ['published_count' => $publish, 'min_required' => $min],
        ])];
    }
}
