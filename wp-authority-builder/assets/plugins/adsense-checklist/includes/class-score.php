<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gauge math. Pure functions over findings + registry.
 *
 * Semantics:
 *   - passed         = count of findings with status='passed' (synthetic per-check pass rows)
 *   - ignored_checks = count of registry entries where EVERY matching finding is status='ignored'.
 *                      A check_key matches if it equals the registry key OR starts with key+'.'.
 *   - effective_total = registry size - ignored_checks
 *   - score          = round(100 * passed / effective_total), or 0 if effective_total <= 0.
 */
class Score
{
    /**
     * @param array $findings each item has ->check_key and ->status
     * @param array $registry each entry has ['key' => string, ...]
     * @return array{score:int, passed:int, resolved_checks:int, passing_total:int, effective_total:int, ignored_checks:int}
     */
    public static function compute(array $findings, array $registry): array
    {
        $total            = count($registry);
        $ignored_checks   = self::ignored_check_count($findings, $registry);
        $resolved_checks  = self::resolved_check_count($findings, $registry);
        $effective        = max(0, $total - $ignored_checks);
        $passed           = 0;
        foreach ($findings as $f) {
            if (($f->status ?? '') === 'passed') {
                $passed++;
            }
        }
        $passing_total = $passed + $resolved_checks;
        $score = $effective > 0 ? (int) round(100 * $passing_total / $effective) : 0;
        return [
            'score'           => $score,
            'passed'          => $passed,
            'resolved_checks' => $resolved_checks,
            'passing_total'   => $passing_total,
            'effective_total' => $effective,
            'ignored_checks'  => $ignored_checks,
        ];
    }

    /**
     * Count registry entries whose findings are ALL status='ignored'. An entry with no
     * findings at all is NOT considered ignored - it simply produced no rows that scan.
     */
    public static function ignored_check_count(array $findings, array $registry): int
    {
        $n = 0;
        foreach ($registry as $entry) {
            $key      = (string) ($entry['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $matched  = 0;
            $all_skip = true; // true while every matched finding is 'ignored'
            foreach ($findings as $f) {
                $fk = (string) ($f->check_key ?? '');
                if ($fk !== $key && strpos($fk, $key . '.') !== 0) {
                    continue;
                }
                $matched++;
                if (($f->status ?? '') !== 'ignored') {
                    $all_skip = false;
                }
            }
            if ($matched > 0 && $all_skip) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Count registry entries whose findings are ALL status='resolved'. The user manually
     * resolved every failing finding for the check; treat the check as passing for the gauge.
     * Mirrors ignored_check_count but credits the numerator instead of shrinking the denominator.
     */
    public static function resolved_check_count(array $findings, array $registry): int
    {
        $n = 0;
        foreach ($registry as $entry) {
            $key = (string) ($entry['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $matched      = 0;
            $all_resolved = true;
            foreach ($findings as $f) {
                $fk = (string) ($f->check_key ?? '');
                if ($fk !== $key && strpos($fk, $key . '.') !== 0) {
                    continue;
                }
                $matched++;
                if (($f->status ?? '') !== 'resolved') {
                    $all_resolved = false;
                }
            }
            if ($matched > 0 && $all_resolved) {
                $n++;
            }
        }
        return $n;
    }
}
