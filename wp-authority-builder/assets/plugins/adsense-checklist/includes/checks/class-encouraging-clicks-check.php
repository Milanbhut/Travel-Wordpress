<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Http_Cache;

if (!defined('ABSPATH')) {
    exit;
}

class Encouraging_Clicks_Check implements Check
{
    private const ARROW_CHARS = ['→', '➜', '➡', '►', '▶', '⇒', '⟹'];
    private const TEXT_PATTERNS = [
        'click here',
        'click below',
        'click the ad',
        'recommended sites',
        'sponsored offer',
        'great offer below',
    ];
    private const CONTEXT_CHARS = 200;

    public function run(array $entry): array
    {
        $url = \home_url('/');
        $r   = Http_Cache::instance()->get($url);
        if ($r['error']) {
            return [new Finding([
                'check_key'   => $entry['key'],
                'situation'   => $entry['situation'] . ' — network error while probing home page.',
                'severity'    => $entry['severity'],
                'status'      => 'manual_review',
                'url_example' => $url,
                'evidence'    => ['_error' => 'wp_error'],
            ])];
        }
        $body = (string) $r['body'];

        // Find all AdSense ad-block positions (case-insensitive). Match BOTH adsbygoogle and adsense classes.
        if (!preg_match_all('/<ins\b[^>]*class\s*=\s*["\']([^"\']*)["\'][^>]*>/i', $body, $matches, PREG_OFFSET_CAPTURE)) {
            return []; // no ad blocks → nothing to flag
        }

        $findings = [];
        foreach ($matches[0] as $i => $match) {
            $class_attr = strtolower((string) $matches[1][$i][0]);
            if (strpos($class_attr, 'adsbygoogle') === false && strpos($class_attr, 'adsense') === false) {
                continue;
            }
            $pos = (int) $match[1];
            $start = max(0, $pos - self::CONTEXT_CHARS);
            $context = substr($body, $start, self::CONTEXT_CHARS * 2);
            $context_lower = strtolower($context);

            // Arrow detection
            foreach (self::ARROW_CHARS as $arrow) {
                if (strpos($context, $arrow) !== false) {
                    $findings[] = $this->emit($entry, $url, "arrow ({$arrow}) near ad block at offset {$pos}");
                    continue 2;
                }
            }
            // Text-pattern detection
            foreach (self::TEXT_PATTERNS as $needle) {
                if (strpos($context_lower, $needle) !== false) {
                    $findings[] = $this->emit($entry, $url, "click-encouraging text ('{$needle}') near ad block at offset {$pos}");
                    continue 2;
                }
            }
        }
        return $findings;
    }

    private function emit(array $entry, string $url, string $description): Finding
    {
        return new Finding([
            'check_key'   => $entry['key'],
            'situation'   => $entry['situation'] . ' — ' . $description,
            'severity'    => $entry['severity'],
            'url_example' => $url,
            'evidence'    => ['pattern' => $description],
        ]);
    }
}
