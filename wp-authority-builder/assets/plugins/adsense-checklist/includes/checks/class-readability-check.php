<?php
namespace AdSenseChecklist\Checks;

use AdSenseChecklist\Finding;
use AdSenseChecklist\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Readability_Check implements Check
{
    private const WORDS_FLOOR     = 100;
    private const GRADE_MIN       = 6.0;
    private const GRADE_MAX       = 12.0;

    public function run(array $entry): array
    {
        $settings  = new Settings();
        $max_posts = (int) $settings->get('max_posts_to_scan');
        $posts = \get_posts([
            'post_status'    => 'publish',
            'post_type'      => 'post',
            'posts_per_page' => $max_posts,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $findings = [];
        foreach ((array) $posts as $post) {
            $text = \wp_strip_all_tags((string) ($post->post_content ?? ''));
            $word_count = $this->count_words($text);
            if ($word_count < self::WORDS_FLOOR) {
                continue;
            }
            $grade = $this->flesch_kincaid_grade($text, $word_count);
            if ($grade >= self::GRADE_MIN && $grade <= self::GRADE_MAX) {
                continue;
            }
            $reason = $grade < self::GRADE_MIN ? 'too simplistic' : 'too academic / hard to read';
            $findings[] = new Finding([
                'check_key'   => $entry['key'] . '.post_' . (int) $post->ID,
                'situation'   => $entry['situation'] . ' — ' . $reason . ' (Flesch-Kincaid grade ' . number_format($grade, 1) . ').',
                'severity'    => $entry['severity'],
                'url_example' => \get_permalink($post->ID),
                'evidence'    => [
                    'post_id'     => (int) $post->ID,
                    'post_title'  => (string) ($post->post_title ?? ''),
                    'word_count'  => $word_count,
                    'grade_level' => round($grade, 2),
                    'reason'      => $reason,
                ],
            ]);
        }
        return $findings;
    }

    private function count_words(string $text): int
    {
        $text = trim($text);
        if ($text === '') return 0;
        return count(preg_split('/\s+/u', $text) ?: []);
    }

    private function count_sentences(string $text): int
    {
        $count = preg_match_all('/[.!?]+/', $text);
        return max(1, (int) $count);
    }

    /** Rough vowel-cluster syllable counting per word. Sum across all words in text. */
    private function count_syllables(string $text): int
    {
        $total = 0;
        $words = preg_split('/\s+/u', $text) ?: [];
        foreach ($words as $word) {
            $w = strtolower(preg_replace('/[^a-z]/i', '', $word));
            if ($w === '' || $w === null) continue;
            // Strip trailing 'e' (silent) — common heuristic
            $w = preg_replace('/e$/', '', $w);
            $clusters = preg_match_all('/[aeiouy]+/', $w);
            $total += max(1, (int) $clusters);
        }
        return $total;
    }

    private function flesch_kincaid_grade(string $text, int $word_count): float
    {
        if ($word_count === 0) return 0.0;
        $sentences = $this->count_sentences($text);
        $syllables = $this->count_syllables($text);
        return 0.39 * ($word_count / $sentences) + 11.8 * ($syllables / $word_count) - 15.59;
    }
}
