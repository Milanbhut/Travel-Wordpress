<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

class Settings
{
    public const OPTION_KEY = 'adsense_checklist_settings';

    public const DEFAULTS = [
        'scan_external_links'      => false,
        'enable_slur_scan'         => false,
        'enable_hate_scan'         => false,
        'enable_illegal_scan'      => false,
        'enable_animal_scan'       => false,
        'enable_unreliable_scan'   => false,
        'enable_mail_brides_scan'  => false,
        'enable_shocking_scan'     => false,
        'min_word_count'           => 1000,
        'min_article_count'        => 20,
        'min_domain_age_days'      => 30,
        'max_posts_to_scan'        => 500,
        'publisher_id'             => '',
    ];

    private array $values;

    public function __construct()
    {
        $stored = get_option(self::OPTION_KEY, []);
        $stored = is_array($stored) ? $stored : [];
        $this->values = array_merge(self::DEFAULTS, $stored);
    }

    public function get(string $key)
    {
        return $this->values[$key] ?? null;
    }

    public function all(): array
    {
        return $this->values;
    }

    public function save(array $new_values): void
    {
        $merged = array_merge($this->values, array_intersect_key($new_values, self::DEFAULTS));
        update_option(self::OPTION_KEY, $merged);
        $this->values = $merged;
    }
}
