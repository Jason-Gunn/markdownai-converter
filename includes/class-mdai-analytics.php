<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Analytics
{
    public static function sanitize_date_range(string $fromDateRaw = '', string $toDateRaw = ''): array
    {
        $today = gmdate('Y-m-d');
        $defaultFrom = gmdate('Y-m-d', strtotime('-29 days'));

        $from = self::is_valid_date($fromDateRaw) ? $fromDateRaw : $defaultFrom;
        $to = self::is_valid_date($toDateRaw) ? $toDateRaw : $today;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from,
            'to' => $to,
            'from_datetime' => $from . ' 00:00:00',
            'to_datetime' => $to . ' 23:59:59',
        ];
    }

    public static function get_kpis(string $fromDateTime, string $toDateTime): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mdai_bot_events';
        $sql = "SELECT
            COUNT(*) AS total_hits,
            COUNT(DISTINCT bot_family) AS unique_bot_families,
            COUNT(DISTINCT post_id) AS unique_posts,
            ROUND(AVG(latency_ms)) AS avg_latency_ms
            FROM {$table}
            WHERE event_time BETWEEN %s AND %s";

        $row = $wpdb->get_row($wpdb->prepare($sql, $fromDateTime, $toDateTime), ARRAY_A);
        if (! is_array($row)) {
            return [
                'total_hits' => 0,
                'unique_bot_families' => 0,
                'unique_posts' => 0,
                'avg_latency_ms' => 0,
            ];
        }

        return [
            'total_hits' => (int) ($row['total_hits'] ?? 0),
            'unique_bot_families' => (int) ($row['unique_bot_families'] ?? 0),
            'unique_posts' => (int) ($row['unique_posts'] ?? 0),
            'avg_latency_ms' => (int) ($row['avg_latency_ms'] ?? 0),
        ];
    }

    public static function get_top_pages(string $fromDateTime, string $toDateTime, int $limit = 10): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mdai_bot_events';
        $limit = max(1, min(100, $limit));

        $sql = "SELECT post_id, COUNT(*) AS hits, COUNT(DISTINCT bot_family) AS bot_families
            FROM {$table}
            WHERE event_time BETWEEN %s AND %s
                AND post_id IS NOT NULL
                AND post_id > 0
            GROUP BY post_id
            ORDER BY hits DESC
            LIMIT %d";

        $rows = $wpdb->get_results($wpdb->prepare($sql, $fromDateTime, $toDateTime, $limit), ARRAY_A);
        if (! is_array($rows)) {
            return [];
        }

        foreach ($rows as &$row) {
            $postId = (int) ($row['post_id'] ?? 0);
            $row['post_id'] = $postId;
            $row['hits'] = (int) ($row['hits'] ?? 0);
            $row['bot_families'] = (int) ($row['bot_families'] ?? 0);
            $row['post_title'] = $postId > 0 ? get_the_title($postId) : '';
            $row['post_url'] = $postId > 0 ? get_permalink($postId) : '';
        }
        unset($row);

        return $rows;
    }

    public static function get_daily_trend(string $fromDateTime, string $toDateTime): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mdai_bot_events';
        $sql = "SELECT DATE(event_time) AS day, COUNT(*) AS hits
            FROM {$table}
            WHERE event_time BETWEEN %s AND %s
            GROUP BY DATE(event_time)
            ORDER BY day ASC";

        $rows = $wpdb->get_results($wpdb->prepare($sql, $fromDateTime, $toDateTime), ARRAY_A);
        if (! is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $day = isset($row['day']) ? (string) $row['day'] : '';
            if ($day === '') {
                continue;
            }
            $result[] = [
                'day' => $day,
                'hits' => (int) ($row['hits'] ?? 0),
            ];
        }

        return $result;
    }

    public static function get_period_comparison(array $range): array
    {
        $previousRange = self::get_previous_range($range);

        $currentKpis = self::get_kpis((string) $range['from_datetime'], (string) $range['to_datetime']);
        $previousKpis = self::get_kpis((string) $previousRange['from_datetime'], (string) $previousRange['to_datetime']);

        return [
            'previous_range' => $previousRange,
            'previous_kpis' => $previousKpis,
            'deltas' => self::get_kpi_deltas($currentKpis, $previousKpis),
        ];
    }

    private static function get_previous_range(array $range): array
    {
        $from = (string) ($range['from'] ?? gmdate('Y-m-d', strtotime('-29 days')));
        $to = (string) ($range['to'] ?? gmdate('Y-m-d'));

        $dayCount = max(1, (int) floor((strtotime($to) - strtotime($from)) / DAY_IN_SECONDS) + 1);
        $previousTo = gmdate('Y-m-d', strtotime($from . ' -1 day'));
        $previousFrom = gmdate('Y-m-d', strtotime($previousTo . ' -' . ($dayCount - 1) . ' days'));

        return [
            'from' => $previousFrom,
            'to' => $previousTo,
            'from_datetime' => $previousFrom . ' 00:00:00',
            'to_datetime' => $previousTo . ' 23:59:59',
        ];
    }

    private static function get_kpi_deltas(array $currentKpis, array $previousKpis): array
    {
        $keys = ['total_hits', 'unique_bot_families', 'unique_posts', 'avg_latency_ms'];
        $deltas = [];

        foreach ($keys as $key) {
            $current = (int) ($currentKpis[$key] ?? 0);
            $previous = (int) ($previousKpis[$key] ?? 0);
            $deltaValue = $current - $previous;
            $deltaPct = $previous !== 0 ? (($deltaValue / $previous) * 100) : null;

            $deltas[$key] = [
                'value' => $deltaValue,
                'percent' => $deltaPct,
            ];
        }

        return $deltas;
    }

    private static function is_valid_date(string $date): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year);
    }
}
