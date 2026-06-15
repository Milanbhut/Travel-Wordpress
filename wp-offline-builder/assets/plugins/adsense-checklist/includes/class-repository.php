<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

class Repository
{
    private string $findings_table;
    private string $scans_table;

    public function __construct()
    {
        global $wpdb;
        $this->findings_table = $wpdb->prefix . 'adsense_checklist_findings';
        $this->scans_table    = $wpdb->prefix . 'adsense_checklist_scans';
    }

    public function start_scan(): int
    {
        global $wpdb;
        $wpdb->insert($this->scans_table, [
            'started_at'     => gmdate('Y-m-d H:i:s'),
            'findings_count' => 0,
            'passed_count'   => 0,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function finish_scan(int $scan_id, int $findings_count, int $passed_count): void
    {
        global $wpdb;
        $wpdb->update($this->scans_table, [
            'finished_at'    => gmdate('Y-m-d H:i:s'),
            'findings_count' => $findings_count,
            'passed_count'   => $passed_count,
        ], ['id' => $scan_id]);
    }

    /** @param Finding[] $findings */
    public function save_findings(array $findings, int $scan_id): void
    {
        global $wpdb;
        foreach ($findings as $f) {
            $situation = $f->situation;
            $evidence  = $f->evidence;
            $status    = $f->status;

            // Carry-forward: ignored takes precedence over resolved. Synthetic 'passed'
            // findings are never carried - the scanner re-decides cleanly each scan.
            if ($f->status !== 'passed') {
                if ($this->was_ignored($f->check_key, $f->url_example)) {
                    $situation .= ' - previously marked ignored; verify still not an issue.';
                    $evidence['_previously_ignored'] = true;
                    $status = 'ignored';
                } elseif ($this->was_resolved($f->check_key, $f->url_example)) {
                    $situation .= ' - previously marked resolved; verify still fixed.';
                    $evidence['_previously_resolved'] = true;
                    $status = 'resolved';
                }
            }

            $wpdb->insert($this->findings_table, [
                'scan_id'     => $scan_id,
                'check_key'   => $f->check_key,
                'situation'   => $situation,
                'severity'    => $f->severity,
                'url_example' => $f->url_example,
                'evidence'    => self::json_encode_safe($evidence),
                'status'      => $status,
                'created_at'  => gmdate('Y-m-d H:i:s'),
            ]);
        }
    }

    public function mark_resolved(int $finding_id, int $user_id): void
    {
        global $wpdb;
        $wpdb->update($this->findings_table, [
            'status'      => 'resolved',
            'resolved_at' => gmdate('Y-m-d H:i:s'),
            'resolved_by' => $user_id,
        ], ['id' => $finding_id]);
    }

    public function mark_status(int $finding_id, string $status): void
    {
        global $wpdb;
        if (!in_array($status, Finding::STATUSES, true)) {
            throw new \InvalidArgumentException("Bad status: {$status}");
        }
        $wpdb->update($this->findings_table, ['status' => $status], ['id' => $finding_id]);
    }

    public function was_resolved(string $check_key, ?string $url_example): bool
    {
        return $this->was_status($check_key, $url_example, 'resolved');
    }

    public function was_ignored(string $check_key, ?string $url_example): bool
    {
        return $this->was_status($check_key, $url_example, 'ignored');
    }

    private function was_status(string $check_key, ?string $url_example, string $status): bool
    {
        global $wpdb;
        if ($url_example === null) {
            $sql = $wpdb->prepare(
                "SELECT id FROM {$this->findings_table} WHERE check_key = %s AND status = %s AND (url_example IS NULL OR url_example = '') LIMIT 1",
                $check_key,
                $status
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT id FROM {$this->findings_table} WHERE check_key = %s AND status = %s AND url_example = %s LIMIT 1",
                $check_key,
                $status,
                $url_example
            );
        }
        return !empty($wpdb->get_results($sql));
    }

    public function latest_scan_id(): ?int
    {
        global $wpdb;
        $id = $wpdb->get_var("SELECT id FROM {$this->scans_table} ORDER BY id DESC LIMIT 1");
        return $id ? (int) $id : null;
    }

    public function findings_for_scan(int $scan_id): array
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->findings_table} WHERE scan_id = %d ORDER BY FIELD(severity,'urgent','severe','moderate','minor'), id",
            $scan_id
        );
        return $wpdb->get_results($sql);
    }

    private static function json_encode_safe($value): string
    {
        if (function_exists('wp_json_encode')) {
            return (string) wp_json_encode($value);
        }
        $encoded = json_encode($value);
        return $encoded === false ? '[]' : $encoded;
    }
}
