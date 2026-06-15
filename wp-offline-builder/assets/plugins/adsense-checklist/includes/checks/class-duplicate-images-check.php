<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;

if (!defined('ABSPATH')) {
    exit;
}

class Duplicate_Images_Check implements Check
{
    public function run(array $entry): array
    {
        $attachments = get_posts([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
        ]);

        $by_hash = [];
        foreach ((array) $attachments as $att) {
            $path = get_attached_file($att->ID);
            if (!$path || !is_readable($path)) continue;
            $hash = md5_file($path);
            if ($hash === false) continue;
            $by_hash[$hash][] = (int) $att->ID;
        }

        $findings = [];
        foreach ($by_hash as $hash => $ids) {
            if (count($ids) < 2) continue;
            $urls = array_map('wp_get_attachment_url', $ids);
            $findings[] = new Finding([
                'check_key'   => $entry['key'] . '.' . substr($hash, 0, 8),
                'situation'   => 'Duplicate image (identical bytes) used by ' . count($ids) . ' media items.',
                'severity'    => $entry['severity'],
                'url_example' => $urls[0],
                'evidence'    => [
                    'hash'           => $hash,
                    'attachment_ids' => $ids,
                    'duplicate_urls' => $urls,
                ],
            ]);
        }
        return $findings;
    }
}
