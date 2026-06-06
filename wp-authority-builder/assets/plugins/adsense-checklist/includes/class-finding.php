<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

class Finding
{
    public const SEVERITIES = ['urgent', 'severe', 'moderate', 'minor'];
    public const STATUSES   = ['pending', 'resolved', 'manual_review', 'passed', 'ignored'];

    public string $check_key;
    public string $situation;
    public string $severity;
    public ?string $url_example;
    public array $evidence;
    public string $status;

    public function __construct(array $data)
    {
        foreach (['check_key', 'situation', 'severity'] as $required) {
            if (empty($data[$required])) {
                throw new \InvalidArgumentException("Finding requires '{$required}'.");
            }
        }

        if (!in_array($data['severity'], self::SEVERITIES, true)) {
            throw new \InvalidArgumentException("Invalid severity: {$data['severity']}");
        }

        $status = $data['status'] ?? 'pending';
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $this->check_key   = (string) $data['check_key'];
        $this->situation   = (string) $data['situation'];
        $this->severity    = (string) $data['severity'];
        $this->url_example = isset($data['url_example']) ? (string) $data['url_example'] : null;
        $this->evidence    = is_array($data['evidence'] ?? null) ? $data['evidence'] : [];
        $this->status      = $status;
    }

    public function to_array(): array
    {
        return [
            'check_key'   => $this->check_key,
            'situation'   => $this->situation,
            'severity'    => $this->severity,
            'url_example' => $this->url_example,
            'evidence'    => $this->evidence,
            'status'      => $this->status,
        ];
    }
}
