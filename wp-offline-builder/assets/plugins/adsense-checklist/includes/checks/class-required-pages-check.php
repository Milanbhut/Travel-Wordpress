<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Pages;

if (!defined('ABSPATH')) {
    exit;
}

class Required_Pages_Check implements Check
{
    public function run(array $entry): array
    {
        $aliases  = $entry['check_args']['aliases']  ?? [];
        $keywords = $entry['check_args']['keywords'] ?? [];
        if (empty($aliases) && empty($keywords)) {
            return [];
        }
        if (Pages::find_by_aliases($aliases, $keywords) !== null) {
            return [];
        }
        return [new Finding([
            'check_key' => $entry['key'],
            'situation' => $entry['situation'] . ' - page missing or not published.',
            'severity'  => $entry['severity'],
            'evidence'  => [
                'searched_aliases'  => $aliases,
                'searched_keywords' => $keywords,
            ],
        ])];
    }
}
