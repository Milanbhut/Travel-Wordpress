<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Manual_Review_Check implements Check
{
    public function run(array $entry): array
    {
        return [new Finding([
            'check_key' => $entry['key'],
            'situation' => $entry['situation'],
            'severity'  => $entry['severity'],
            'status'    => 'manual_review',
            'evidence'  => ['hint' => $entry['hint'] ?? 'Manual review required.'],
        ])];
    }
}
