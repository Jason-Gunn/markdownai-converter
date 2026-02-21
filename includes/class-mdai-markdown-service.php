<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Markdown_Service
{
    public static function generate_for_post(int $postId, bool $forceRegenerate = false): string
    {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post) {
            return '';
        }

        if (! $forceRegenerate) {
            $cached = self::get_cached_markdown($post);
            if ($cached !== '') {
                return $cached;
            }
        }

        $markdown = self::build_markdown($post);
        self::save_cached_markdown($post, $markdown);

        return $markdown;
    }

    private static function get_cached_markdown(\WP_Post $post): string
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mdai_content_cache';

        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT markdown_blob, post_modified_gmt FROM {$table} WHERE post_id = %d LIMIT 1",
                $post->ID
            ),
            ARRAY_A
        );

        if (! is_array($record)) {
            return '';
        }

        if (($record['post_modified_gmt'] ?? '') !== $post->post_modified_gmt) {
            return '';
        }

        return isset($record['markdown_blob']) ? (string) $record['markdown_blob'] : '';
    }

    private static function save_cached_markdown(\WP_Post $post, string $markdown): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mdai_content_cache';
        $checksum = hash('sha256', $markdown);

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table}
                (post_id, post_modified_gmt, markdown_blob, checksum, generated_at)
                VALUES (%d, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    post_modified_gmt = VALUES(post_modified_gmt),
                    markdown_blob = VALUES(markdown_blob),
                    checksum = VALUES(checksum),
                    generated_at = VALUES(generated_at)",
                $post->ID,
                $post->post_modified_gmt,
                $markdown,
                $checksum,
                current_time('mysql', true)
            )
        );
    }

    private static function build_markdown(\WP_Post $post): string
    {
        $htmlContent = apply_filters('the_content', $post->post_content);
        $htmlContent = is_string($htmlContent) ? $htmlContent : '';

        $metadata = [
            '---',
            'title: ' . self::escape_yaml_line(get_the_title($post)),
            'source_url: ' . esc_url_raw(get_permalink($post)),
            'post_type: ' . self::escape_yaml_line($post->post_type),
            'modified_gmt: ' . self::escape_yaml_line($post->post_modified_gmt),
            'language: ' . self::escape_yaml_line(get_bloginfo('language')),
            '---',
        ];

        $bodyMarkdown = self::html_to_markdown($htmlContent);
        $bodyMarkdown = preg_replace("/\n{3,}/", "\n\n", $bodyMarkdown);

        return implode("\n", $metadata) . "\n\n" . trim((string) $bodyMarkdown) . "\n";
    }

    private static function escape_yaml_line(string $value): string
    {
        $value = wp_strip_all_tags($value);
        return str_replace(["\r", "\n"], ' ', $value);
    }

    private static function html_to_markdown(string $html): string
    {
        if ($html === '') {
            return '';
        }

        if (! class_exists('DOMDocument')) {
            return trim(wp_strip_all_tags($html));
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $markdown = self::render_children($dom);

        return trim($markdown);
    }

    private static function render_children(\DOMNode $node, int $depth = 0): string
    {
        $buffer = '';

        foreach ($node->childNodes as $child) {
            $buffer .= self::node_to_markdown($child, $depth);
        }

        return $buffer;
    }

    private static function node_to_markdown(\DOMNode $node, int $depth = 0): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return preg_replace('/\s+/u', ' ', $node->nodeValue ?? '');
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        $tag = strtolower($node->nodeName);
        $content = trim(self::render_children($node, $depth));

        if (in_array($tag, ['script', 'style', 'noscript', 'iframe'], true)) {
            return '';
        }

        if (preg_match('/^h([1-6])$/', $tag, $matches) === 1) {
            return "\n\n" . str_repeat('#', (int) $matches[1]) . ' ' . $content . "\n\n";
        }

        switch ($tag) {
            case 'p':
                return $content === '' ? '' : "\n\n{$content}\n\n";
            case 'br':
                return "  \n";
            case 'strong':
            case 'b':
                return $content === '' ? '' : "**{$content}**";
            case 'em':
            case 'i':
                return $content === '' ? '' : "*{$content}*";
            case 'code':
                if ($node->parentNode instanceof \DOMElement && strtolower($node->parentNode->nodeName) === 'pre') {
                    return $content;
                }
                return $content === '' ? '' : '`' . str_replace('`', '\\`', $content) . '`';
            case 'pre':
                $code = trim($node->textContent ?? '');
                return $code === '' ? '' : "\n\n```\n{$code}\n```\n\n";
            case 'a':
                if (! ($node instanceof \DOMElement)) {
                    return $content;
                }
                $href = trim($node->getAttribute('href'));
                if ($href === '') {
                    return $content;
                }
                $label = $content !== '' ? $content : $href;
                return "[{$label}]({$href})";
            case 'img':
                if (! ($node instanceof \DOMElement)) {
                    return '';
                }
                $alt = trim($node->getAttribute('alt'));
                $src = trim($node->getAttribute('src'));
                if ($src === '') {
                    return '';
                }
                return "![{$alt}]({$src})";
            case 'ul':
                return self::render_list($node, false, $depth);
            case 'ol':
                return self::render_list($node, true, $depth);
            case 'blockquote':
                $lines = array_filter(array_map('trim', explode("\n", $content)), static fn($line) => $line !== '');
                if ($lines === []) {
                    return '';
                }
                return "\n\n> " . implode("\n> ", $lines) . "\n\n";
            case 'hr':
                return "\n\n---\n\n";
            case 'table':
                return "\n\n" . trim(wp_strip_all_tags($node->textContent ?? '')) . "\n\n";
            default:
                return $content;
        }
    }

    private static function render_list(\DOMNode $listNode, bool $ordered, int $depth): string
    {
        $lines = [];
        $index = 1;

        foreach ($listNode->childNodes as $child) {
            if (! ($child instanceof \DOMElement) || strtolower($child->nodeName) !== 'li') {
                continue;
            }

            $itemContent = trim(self::render_children($child, $depth + 1));
            if ($itemContent === '') {
                continue;
            }

            $prefix = $ordered ? $index . '. ' : '- ';
            $indent = str_repeat('  ', max(0, $depth));
            $lines[] = $indent . $prefix . $itemContent;
            $index++;
        }

        if ($lines === []) {
            return '';
        }

        return "\n" . implode("\n", $lines) . "\n";
    }
}
