<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Robots_Txt_Check implements Check
{
    public function run(array $entry): array
    {
        $url = trailingslashit(home_url()) . 'robots.txt';
        $response = wp_remote_get($url, ['timeout' => 10, 'redirection' => 2]);

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $body  = (string) wp_remote_retrieve_body($response);
        $rules = $this->parse_groups($body);

        $mediapartners = $rules['mediapartners-google'] ?? null;
        if ($mediapartners !== null && $this->blocks_root($mediapartners)) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'robots.txt explicitly disallows Mediapartners-Google from crawling /',
                'severity'  => $entry['severity'],
                'url_example' => $url,
                'evidence'  => ['mediapartners_rules' => $mediapartners],
            ])];
        }

        $wildcard = $rules['*'] ?? null;
        if ($mediapartners === null && $wildcard !== null && $this->blocks_root($wildcard)) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => 'robots.txt wildcard rule blocks crawlers from / (no Mediapartners-Google override).',
                'severity'  => $entry['severity'],
                'url_example' => $url,
                'evidence'  => ['wildcard_rules' => $wildcard],
            ])];
        }

        return [];
    }

    private function parse_groups(string $body): array
    {
        $groups = [];
        $current_agents = [];
        foreach (preg_split("/\r?\n/", $body) as $line) {
            $line = trim($line);
            if ($line === '') {
                $current_agents = [];
                continue;
            }
            if ($line[0] === '#') {
                continue;
            }
            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $m)) {
                $agent = strtolower(trim($m[1]));
                $current_agents[] = $agent;
                if (!isset($groups[$agent])) {
                    $groups[$agent] = ['disallow' => [], 'allow' => []];
                }
                continue;
            }
            if (preg_match('/^(Disallow|Allow):\s*(.*)$/i', $line, $m)) {
                $directive = strtolower($m[1]);
                $value     = trim($m[2]);
                foreach ($current_agents as $agent) {
                    $groups[$agent][$directive][] = $value;
                }
            } else {
                $current_agents = [];
            }
        }
        return $groups;
    }

    private function blocks_root(array $rules): bool
    {
        $disallow = $rules['disallow'] ?? [];
        $allow    = $rules['allow'] ?? [];
        $blocks   = in_array('/', $disallow, true);
        $allows   = in_array('/', $allow, true);
        return $blocks && !$allows;
    }
}
