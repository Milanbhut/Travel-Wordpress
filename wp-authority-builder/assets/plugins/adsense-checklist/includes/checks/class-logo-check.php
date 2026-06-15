<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Logo_Check implements Check
{
    public function run(array $entry): array
    {
        if (\has_custom_logo()) {
            return [];
        }
        return [new Finding([
            'check_key' => $entry['key'],
            'situation' => $entry['situation'] . ' - no custom logo configured.',
            'severity'  => $entry['severity'],
            'evidence'  => ['has_custom_logo' => false],
        ])];
    }
}
