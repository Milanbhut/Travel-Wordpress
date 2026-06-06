<?php
namespace AdSenseChecklist;

use AdSenseChecklist\Checks\Check;

if (!defined('ABSPATH')) {
    exit;
}

class Scanner
{
    private array $registry;
    private Repository $repo;

    public function __construct(array $registry, Repository $repo)
    {
        $this->registry = $registry;
        $this->repo     = $repo;
    }

    public function scan(): int
    {
        $scan_id = $this->repo->start_scan();
        $findings_count = 0;
        $passed_count   = 0;

        foreach ($this->registry as $entry) {
            try {
                /** @var Check $check */
                $check = new $entry['check_class']();
                $findings = $check->run($entry);
            } catch (\Throwable $e) {
                error_log('[adsense-checklist] check ' . $entry['key'] . ' threw: ' . $e->getMessage());
                $findings = [new Finding([
                    'check_key' => $entry['key'],
                    'situation' => 'Internal error running check: ' . $entry['key'],
                    'severity'  => $entry['severity'] ?? 'moderate',
                    'evidence'  => ['error' => $e->getMessage()],
                ])];
            }

            foreach ($findings as $f) {
                if (empty($f->url_example)) {
                    $f->url_example = function_exists('home_url') ? (string) home_url() : '';
                }
                if (!isset($f->evidence['_fix']) && !empty($entry['fix'])) {
                    $f->evidence['_fix'] = $entry['fix'];
                }
            }

            if (empty($findings)) {
                // No violations: emit a synthetic "passed" so the registry-to-row mapping stays 1:1.
                $this->repo->save_findings([new Finding([
                    'check_key' => $entry['key'],
                    'situation' => $entry['situation'],
                    'severity'  => $entry['severity'],
                    'status'    => 'passed',
                ])], $scan_id);
                $passed_count++;
            } else {
                $this->repo->save_findings($findings, $scan_id);
                $findings_count += count($findings);
            }
        }

        $this->repo->finish_scan($scan_id, $findings_count, $passed_count);
        return $scan_id;
    }
}
