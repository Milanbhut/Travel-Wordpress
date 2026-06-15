<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Http_Cache;

if (!defined('ABSPATH')) {
    exit;
}

class UX_CSS_Check implements Check
{
    private const MIN_FONT_PX        = 14;
    private const MIN_LINE_HEIGHT    = 1.4;

    public function run(array $entry): array
    {
        $url = (string) \get_stylesheet_uri();
        $r   = Http_Cache::instance()->get($url);
        if ($r['error']) {
            return [$this->manual_review($entry, $url, 'Network error fetching theme stylesheet.')];
        }
        $css = (string) $r['body'];

        // Extract the FIRST `body { ... }` rule (most themes have exactly one).
        if (!preg_match('/(?:^|[}\s])body\s*\{([^}]*)\}/i', $css, $m)) {
            return [$this->manual_review($entry, $url, 'No body rule found in stylesheet - typography defined elsewhere.')];
        }
        $body_rule = $m[1];

        // Bail if the body rule references CSS variables - can't resolve values reliably.
        if (preg_match('/var\s*\(/i', $body_rule)) {
            return [$this->manual_review($entry, $url, 'Body rule uses CSS variables - values cannot be resolved without rendering.')];
        }

        $findings = [];

        // font-size check
        if (preg_match('/font-size\s*:\s*(\d+(?:\.\d+)?)\s*(px|rem|em)\b/i', $body_rule, $fm)) {
            $value = (float) $fm[1];
            $unit  = strtolower($fm[2]);
            $px = $unit === 'px' ? $value : $value * 16; // 1rem/1em ≈ 16px
            if ($px < self::MIN_FONT_PX) {
                $findings[] = new Finding([
                    'check_key'   => $entry['key'],
                    'situation'   => $entry['situation'] . " - body font-size is {$value}{$unit} (~{$px}px), below the readable floor of " . self::MIN_FONT_PX . 'px.',
                    'severity'    => $entry['severity'],
                    'url_example' => $url,
                    'evidence'    => ['font_size_value' => $value, 'font_size_unit' => $unit, 'computed_px' => $px],
                ]);
            }
        }

        // line-height check (only flag if unitless ratio is readable)
        if (preg_match('/line-height\s*:\s*(\d+(?:\.\d+)?)\s*(?:;|\}|$)/i', $body_rule, $lm)) {
            $line = (float) $lm[1];
            if ($line < self::MIN_LINE_HEIGHT) {
                $findings[] = new Finding([
                    'check_key'   => $entry['key'],
                    'situation'   => $entry['situation'] . " - body line-height is {$line}, below the readable floor of " . self::MIN_LINE_HEIGHT . '.',
                    'severity'    => $entry['severity'],
                    'url_example' => $url,
                    'evidence'    => ['line_height' => $line],
                ]);
            }
        }

        return $findings;
    }

    private function manual_review(array $entry, string $url, string $note): Finding
    {
        return new Finding([
            'check_key'   => $entry['key'],
            'situation'   => $entry['situation'] . ' - ' . $note,
            'severity'    => $entry['severity'],
            'status'      => 'manual_review',
            'url_example' => $url,
            'evidence'    => ['_note' => $note],
        ]);
    }
}
