<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Suggestions
{
    public static function analyze_post(int $postId): array
    {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post) {
            return self::empty_result();
        }

        $rawContent = (string) $post->post_content;
        $renderedContent = (string) apply_filters('the_content', $rawContent);
        $plainText = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($renderedContent)) ?? '');

        $wordCount = str_word_count(wp_strip_all_tags($plainText));
        $paragraphLengths = self::extract_paragraph_word_counts($rawContent);
        $headingLevels = self::extract_heading_levels($rawContent);
        $imagesMissingAlt = self::count_images_missing_alt($rawContent);
        $internalLinks = self::count_internal_links($rawContent);
        $faqSectionPresent = self::has_faq_section($rawContent, $renderedContent);

        $score = 100;
        $suggestions = [];

        if ($wordCount < 300) {
            $score -= 20;
            $suggestions[] = [
                'severity' => 'high',
                'title' => __('Thin content', 'markdownai-converter'),
                'detail' => __('Expand this page to at least 300-500 words to improve AI extraction quality and context depth.', 'markdownai-converter'),
            ];
        }

        if ($headingLevels === []) {
            $score -= 20;
            $suggestions[] = [
                'severity' => 'high',
                'title' => __('No heading structure', 'markdownai-converter'),
                'detail' => __('Add clear H2/H3 sections so crawlers can segment topics and answer-specific passages.', 'markdownai-converter'),
            ];
        } elseif (in_array(1, $headingLevels, true) === false && count($headingLevels) > 1) {
            $score -= 8;
            $suggestions[] = [
                'severity' => 'medium',
                'title' => __('Heading hierarchy may be weak', 'markdownai-converter'),
                'detail' => __('Use a logical heading outline (H1→H2→H3) and avoid skipping levels.', 'markdownai-converter'),
            ];
        }

        $longParagraphs = array_filter($paragraphLengths, static fn(int $count): bool => $count > 120);
        if (count($longParagraphs) >= 2) {
            $score -= 12;
            $suggestions[] = [
                'severity' => 'medium',
                'title' => __('Long paragraphs detected', 'markdownai-converter'),
                'detail' => __('Break large paragraphs into shorter blocks to improve readability and chunk extraction.', 'markdownai-converter'),
            ];
        }

        if ($imagesMissingAlt > 0) {
            $score -= min(15, $imagesMissingAlt * 3);
            $suggestions[] = [
                'severity' => 'medium',
                'title' => __('Images missing alt text', 'markdownai-converter'),
                'detail' => sprintf(__('Add descriptive alt text to %d image(s) so content remains understandable in markdown output.', 'markdownai-converter'), $imagesMissingAlt),
            ];
        }

        if ($wordCount >= 300 && $internalLinks < 2) {
            $score -= 10;
            $suggestions[] = [
                'severity' => 'low',
                'title' => __('Low internal linking', 'markdownai-converter'),
                'detail' => __('Add 2-5 relevant internal links to strengthen topic context and entity association.', 'markdownai-converter'),
            ];
        }

        if (! $faqSectionPresent) {
            $score -= 6;
            $suggestions[] = [
                'severity' => 'low',
                'title' => __('No FAQ-style section found', 'markdownai-converter'),
                'detail' => __('Consider adding a short Q&A section to improve answer extraction opportunities for AI agents.', 'markdownai-converter'),
            ];
        }

        $score = max(0, min(100, (int) round($score)));

        return [
            'post_id' => $post->ID,
            'title' => get_the_title($post),
            'url' => get_permalink($post),
            'score' => $score,
            'word_count' => $wordCount,
            'internal_links' => $internalLinks,
            'images_missing_alt' => $imagesMissingAlt,
            'faq_section_present' => $faqSectionPresent,
            'suggestions' => $suggestions,
        ];
    }

    public static function analyze_recent_posts(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $publicPostTypes = get_post_types(['public' => true], 'names');
        $postIds = get_posts([
            'post_type' => array_values($publicPostTypes),
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => 'modified',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);

        if (! is_array($postIds) || $postIds === []) {
            return [];
        }

        $results = [];
        foreach ($postIds as $postId) {
            $results[] = self::analyze_post((int) $postId);
        }

        usort($results, static fn(array $a, array $b): int => ($a['score'] ?? 0) <=> ($b['score'] ?? 0));
        return $results;
    }

    private static function empty_result(): array
    {
        return [
            'post_id' => 0,
            'title' => '',
            'url' => '',
            'score' => 0,
            'word_count' => 0,
            'internal_links' => 0,
            'images_missing_alt' => 0,
            'faq_section_present' => false,
            'suggestions' => [],
        ];
    }

    private static function extract_paragraph_word_counts(string $content): array
    {
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $matches);
        if (! isset($matches[1]) || ! is_array($matches[1])) {
            return [];
        }

        $counts = [];
        foreach ($matches[1] as $paragraphHtml) {
            $text = trim(wp_strip_all_tags((string) $paragraphHtml));
            if ($text === '') {
                continue;
            }
            $counts[] = str_word_count($text);
        }

        return $counts;
    }

    private static function extract_heading_levels(string $content): array
    {
        preg_match_all('/<h([1-6])[^>]*>/i', $content, $matches);
        if (! isset($matches[1]) || ! is_array($matches[1])) {
            return [];
        }

        return array_map('intval', $matches[1]);
    }

    private static function count_images_missing_alt(string $content): int
    {
        preg_match_all('/<img\b[^>]*>/i', $content, $images);
        if (! isset($images[0]) || ! is_array($images[0])) {
            return 0;
        }

        $missing = 0;
        foreach ($images[0] as $imgTag) {
            if (preg_match('/\balt\s*=\s*(["\'])(.*?)\1/i', $imgTag, $altMatch) !== 1) {
                $missing++;
                continue;
            }
            if (trim((string) ($altMatch[2] ?? '')) === '') {
                $missing++;
            }
        }

        return $missing;
    }

    private static function count_internal_links(string $content): int
    {
        preg_match_all('/<a\b[^>]*href\s*=\s*(["\'])(.*?)\1/i', $content, $matches);
        if (! isset($matches[2]) || ! is_array($matches[2])) {
            return 0;
        }

        $homeHost = wp_parse_url(home_url('/'), PHP_URL_HOST);
        $count = 0;

        foreach ($matches[2] as $href) {
            $href = trim((string) $href);
            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }

            if (str_starts_with($href, '/')) {
                $count++;
                continue;
            }

            $host = wp_parse_url($href, PHP_URL_HOST);
            if ($host !== null && $homeHost !== null && is_string($host) && is_string($homeHost) && strcasecmp($host, $homeHost) === 0) {
                $count++;
            }
        }

        return $count;
    }

    private static function has_faq_section(string $rawContent, string $renderedContent): bool
    {
        $haystack = strtolower($rawContent . ' ' . $renderedContent);
        return str_contains($haystack, 'faq')
            || str_contains($haystack, 'frequently asked')
            || str_contains($haystack, 'q:')
            || str_contains($haystack, 'question');
    }
}
