<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

class Exporter
{
    public const COLUMNS = ['Violation / Situation', 'Error Contained URL', 'Classification', 'Resolved?'];

    public function to_csv(array $rows): string
    {
        $lines = [];
        // Force-quote every field for consistent, predictable output.
        $lines[] = implode(',', array_map(fn($c) => $this->csv_quote($c), self::COLUMNS));
        foreach ($rows as $r) {
            $lines[] = implode(',', [
                $this->csv_quote((string) ($r->situation ?? '')),
                $this->csv_quote((string) ($r->url_example ?? '')),
                $this->csv_quote(ucfirst((string) ($r->severity ?? ''))),
                $this->csv_quote($this->resolved_label((string) ($r->status ?? ''))),
            ]);
        }
        return implode("\n", $lines) . "\n";
    }

    public function to_markdown(array $rows, array $meta = []): string
    {
        $lines = [];
        $lines[] = '# AdSense Checklist Report';
        if (!empty($meta['site'])) $lines[] = '**Site:** ' . $meta['site'];
        if (!empty($meta['date'])) $lines[] = '**Date:** ' . $meta['date'];
        $lines[] = '';
        $lines[] = '## Risk Glossary';
        $lines[] = '- **Red: Urgent** - fix within 48h';
        $lines[] = '- **Orange: Severe** - fix within 72h';
        $lines[] = '- **Yellow: Moderate** - fix within a week';
        $lines[] = '- **Green: Minor** - optimization';
        $lines[] = '';
        $lines[] = '## Findings';
        $lines[] = '| ' . implode(' | ', self::COLUMNS) . ' |';
        $lines[] = '| ' . implode(' | ', array_fill(0, count(self::COLUMNS), '---')) . ' |';
        foreach ($rows as $r) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s |',
                $this->md_escape((string) ($r->situation ?? '')),
                ($r->url_example ?? '') !== '' ? $r->url_example : '-',
                ucfirst((string) ($r->severity ?? '')),
                $this->resolved_label((string) ($r->status ?? ''))
            );
        }
        return implode("\n", $lines) . "\n";
    }

    private function csv_quote(string $s): string
    {
        return '"' . str_replace('"', '""', $s) . '"';
    }

    private function md_escape(string $s): string
    {
        return str_replace(['|', "\n"], [' ', ' '], $s);
    }

    private function resolved_label(string $status): string
    {
        switch ($status) {
            case 'resolved':      return 'Resolved';
            case 'ignored':       return 'Ignored';
            case 'manual_review': return 'Manual review';
            case 'passed':        return 'Passed';
            default:              return 'Pending';
        }
    }
}
