<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Domain_Maturity_Check implements Check
{
    public function run(array $entry): array
    {
        $settings = new Settings();
        $min_days = (int) $settings->get('min_domain_age_days');

        $oldest = get_posts([
            'post_status'    => 'publish',
            'post_type'      => 'post',
            'orderby'        => 'date',
            'order'          => 'ASC',
            'posts_per_page' => 1,
        ]);

        if (empty($oldest)) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'No published posts found — cannot evaluate domain maturation.',
                'severity'  => $entry['severity'],
            ])];
        }

        $first_ts = strtotime((string) ($oldest[0]->post_date_gmt ?? ''));
        if ($first_ts === false) {
            return [];
        }

        $age_days = (int) floor((time() - $first_ts) / 86400);
        if ($age_days >= $min_days) {
            return [];
        }

        return [new Finding([
            'check_key' => $entry['key'],
            'situation' => "Oldest post is only {$age_days} days old (minimum recommended: {$min_days}).",
            'severity'  => $entry['severity'],
            'evidence'  => ['age_days' => $age_days, 'min_required_days' => $min_days],
        ])];
    }
}
