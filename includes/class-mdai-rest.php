<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Rest
{
    public static function register_routes(): void
    {
        register_rest_route('mdai/v1', '/markdown/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_markdown_for_post'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => static fn($value) => absint($value) > 0,
                ],
            ],
        ]);
    }

    public static function get_markdown_for_post(\WP_REST_Request $request): \WP_REST_Response
    {
        $start = microtime(true);
        $postId = absint($request['id']);
        $post = get_post($postId);

        if (! $post instanceof \WP_Post || $post->post_status !== 'publish') {
            $response = new \WP_REST_Response(['message' => 'Not found'], 404);
            self::track_request($postId, 404, $start, '');
            return $response;
        }

        if (! is_post_type_viewable($post->post_type)) {
            $response = new \WP_REST_Response(['message' => 'Forbidden'], 403);
            self::track_request($postId, 403, $start, '');
            return $response;
        }

        $markdown = Markdown_Service::generate_for_post($postId, false);

        $payload = [
            'post_id' => $postId,
            'post_type' => $post->post_type,
            'source_url' => get_permalink($post),
            'modified_gmt' => $post->post_modified_gmt,
            'markdown' => $markdown,
        ];

        $response = new \WP_REST_Response($payload, 200);
        $response->header('Cache-Control', 'public, max-age=300, s-maxage=300');
        $response->header('X-MDAI-Version', MDAI_PLUGIN_VERSION);

        self::track_request($postId, 200, $start, $markdown);

        return $response;
    }

    private static function track_request(int $postId, int $statusCode, float $startTime, string $markdown): void
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) wp_unslash($_SERVER['HTTP_REFERER']) : '';
        $refererHost = '';

        if ($referer !== '') {
            $parsedHost = wp_parse_url($referer, PHP_URL_HOST);
            $refererHost = is_string($parsedHost) ? $parsedHost : '';
        }

        Bot_Tracker::track_event([
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : '',
            'bot_family' => Bot_Detector::detect_family(isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : ''),
            'endpoint' => home_url($requestUri),
            'post_id' => $postId,
            'status_code' => $statusCode,
            'bytes_sent' => strlen($markdown),
            'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
            'referer_host' => $refererHost,
        ]);
    }
}
