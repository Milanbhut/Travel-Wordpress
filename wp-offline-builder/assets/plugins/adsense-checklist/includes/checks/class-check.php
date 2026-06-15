<?php
namespace AdSenseChecklist\Checks;

if (!defined('ABSPATH')) {
    exit;
}

interface Check
{
    /**
     * @param array $entry Registry entry (key, situation, severity, check_class, check_args, hint).
     * @return \AdSenseChecklist\Finding[]
     */
    public function run(array $entry): array;
}
