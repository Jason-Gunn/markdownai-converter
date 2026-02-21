<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Demo_Data
{
    public const UA_PREFIX = 'MDAI-Demo/';

    public static function seed_bot_events(int $days = 90): int
    {
        global $wpdb;

        $days = max(7, min(365, $days));
        $table = $wpdb->prefix . 'mdai_bot_events';

        $publicPostTypes = get_post_types(['public' => true], 'names');
        $postIds = get_posts([
            'post_type' => array_values($publicPostTypes),
            'post_status' => 'publish',
            'numberposts' => 200,
            'fields' => 'ids',
        ]);

        if (! is_array($postIds) || $postIds === []) {
            return 0;
        }

        $families = ['openai', 'anthropic', 'google', 'perplexity', 'microsoft', 'unknown'];
        $eventsInserted = 0;

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = gmdate('Y-m-d', strtotime('-' . $offset . ' days'));

            $dailyEvents = random_int(15, 80);
            for ($i = 0; $i < $dailyEvents; $i++) {
                $postId = (int) $postIds[array_rand($postIds)];
                $family = (string) $families[array_rand($families)];
                $statusCode = random_int(1, 100) <= 97 ? 200 : (random_int(0, 1) ? 404 : 503);
                $latencyMs = random_int(30, 900);
                $bytesSent = random_int(1200, 18000);
                $hour = str_pad((string) random_int(0, 23), 2, '0', STR_PAD_LEFT);
                $minute = str_pad((string) random_int(0, 59), 2, '0', STR_PAD_LEFT);
                $second = str_pad((string) random_int(0, 59), 2, '0', STR_PAD_LEFT);
                $eventTime = $date . ' ' . $hour . ':' . $minute . ':' . $second;

                $endpointPath = '/wp-json/mdai/v1/markdown/' . $postId;
                $endpoint = home_url($endpointPath);

                $inserted = $wpdb->insert(
                    $table,
                    [
                        'event_time' => $eventTime,
                        'bot_family' => $family,
                        'user_agent' => self::UA_PREFIX . $family,
                        'ip_hash' => hash('sha256', 'demo-ip-' . random_int(1, 60) . '|' . wp_salt('auth')),
                        'endpoint' => mb_substr($endpoint, 0, 255),
                        'post_id' => $postId,
                        'status_code' => $statusCode,
                        'bytes_sent' => $bytesSent,
                        'latency_ms' => $latencyMs,
                        'referer_host' => random_int(0, 1) ? 'demo-client.local' : 'crawler-lab.local',
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s']
                );

                if ($inserted === 1) {
                    $eventsInserted++;
                }
            }
        }

        return $eventsInserted;
    }

    public static function clear_seeded_events(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mdai_bot_events';
        $like = self::UA_PREFIX . '%';

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE user_agent LIKE %s",
                $like
            )
        );

        return is_int($deleted) ? max(0, $deleted) : 0;
    }
}
