<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Bot_Tracker
{
    public static function track_event(array $payload): void
    {
        $settings = Settings::get_all();
        if (empty($settings['enable_tracking'])) {
            return;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'mdai_bot_events';

        $userAgent = isset($payload['user_agent']) ? sanitize_text_field((string) $payload['user_agent']) : '';
        $botFamily = isset($payload['bot_family']) ? sanitize_key((string) $payload['bot_family']) : Bot_Detector::detect_family($userAgent);
        $endpoint = isset($payload['endpoint']) ? esc_url_raw((string) $payload['endpoint']) : '';
        $postId = isset($payload['post_id']) ? absint($payload['post_id']) : null;
        $statusCode = isset($payload['status_code']) ? absint($payload['status_code']) : 200;
        $bytesSent = isset($payload['bytes_sent']) ? max(0, (int) $payload['bytes_sent']) : 0;
        $latencyMs = isset($payload['latency_ms']) ? max(0, (int) $payload['latency_ms']) : 0;
        $refererHost = isset($payload['referer_host']) ? sanitize_text_field((string) $payload['referer_host']) : null;
        $searchTerm = isset($payload['search_term']) ? sanitize_text_field((string) $payload['search_term']) : null;

        $ipHash = self::hash_ip_address(self::resolve_remote_ip());

        $wpdb->insert(
            $table,
            [
                'event_time' => current_time('mysql', true),
                'bot_family' => $botFamily,
                'user_agent' => mb_substr($userAgent, 0, 512),
                'ip_hash' => $ipHash,
                'endpoint' => mb_substr($endpoint, 0, 255),
                'search_term' => $searchTerm ? mb_substr($searchTerm, 0, 255) : null,
                'post_id' => $postId > 0 ? $postId : null,
                'status_code' => max(100, min(599, $statusCode)),
                'bytes_sent' => $bytesSent,
                'latency_ms' => $latencyMs,
                'referer_host' => $refererHost ? mb_substr($refererHost, 0, 255) : null,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
            ]
        );
    }

    private static function resolve_remote_ip(): string
    {
        $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        return sanitize_text_field($remoteAddr);
    }

    private static function hash_ip_address(string $ip): string
    {
        if ($ip === '') {
            return hash('sha256', 'missing-ip|' . wp_salt('auth'));
        }

        return hash('sha256', $ip . '|' . wp_salt('auth'));
    }
}
