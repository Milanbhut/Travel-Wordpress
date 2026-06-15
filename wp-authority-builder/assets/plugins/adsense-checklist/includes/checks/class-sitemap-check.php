<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Http_Cache;

if (!defined('ABSPATH')) {
    exit;
}

class Sitemap_Check implements Check
{
    private const CANDIDATES = ['/wp-sitemap.xml', '/sitemap.xml', '/sitemap_index.xml'];

    public function run(array $entry): array
    {
        $cache = Http_Cache::instance();
        $tried = [];
        $network_error = false;

        foreach (self::CANDIDATES as $path) {
            $url     = \home_url($path);
            $tried[] = $url;
            // Use GET (not HEAD) - many WP setups (XAMPP, custom rewrites, cache plugins)
            // either route HEAD differently than GET, or strip the Content-Type header on HEAD.
            // The user's browser uses GET, so we mimic that.
            $r       = $cache->get($url);
            if ($r['error']) {
                $network_error = true;
                continue;
            }
            if ($r['code'] !== 200) {
                continue;
            }
            $ct = strtolower((string) ($r['headers']['content-type'] ?? ''));
            if (strpos($ct, 'xml') !== false) {
                return []; // pass via content-type
            }
            // Content-type might be wrong or missing (XAMPP / caching plugins / CDNs do this).
            // Sniff the body: a sitemap starts with <?xml or contains <urlset / <sitemapindex.
            if ($this->looks_like_xml_sitemap((string) $r['body'])) {
                return []; // pass via body inspection
            }
        }

        if ($network_error && count($tried) === count(self::CANDIDATES)) {
            return [new Finding([
                'check_key' => $entry['key'],
                'situation' => $entry['situation'] . ' - network error while probing.',
                'severity'  => $entry['severity'],
                'status'    => 'manual_review',
                'evidence'  => ['searched_urls' => $tried, '_error' => 'wp_error'],
            ])];
        }

        return [new Finding([
            'check_key'   => $entry['key'],
            'situation'   => $entry['situation'] . ' - no XML sitemap found.',
            'severity'    => $entry['severity'],
            'url_example' => $tried[0] ?? \home_url('/'),
            'evidence'    => ['searched_urls' => $tried],
        ])];
    }

    /**
     * True if the response body looks like an XML sitemap regardless of Content-Type.
     * Sitemap protocol: top-level element is <urlset> (for /wp-sitemap-posts-post-N.xml)
     * or <sitemapindex> (for /wp-sitemap.xml and /sitemap_index.xml).
     */
    private function looks_like_xml_sitemap(string $body): bool
    {
        $head = ltrim($body);
        if ($head === '') {
            return false;
        }
        if (strpos($head, '<?xml') === 0) {
            return true;
        }
        // Some setups strip the XML prolog. Look for the sitemap root elements directly.
        return stripos($head, '<urlset') !== false || stripos($head, '<sitemapindex') !== false;
    }
}
