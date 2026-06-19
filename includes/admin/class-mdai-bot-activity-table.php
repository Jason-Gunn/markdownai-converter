<?php

namespace MDAI\Admin;

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Bot_Activity_Table extends \WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'mdai_bot_event',
            'plural' => 'mdai_bot_events',
            'ajax' => false,
        ]);
    }

    public function get_columns(): array
    {
        return [
            'event_time' => __('Timestamp (UTC)', 'markdownai-converter'),
            'bot_family' => __('Bot Family', 'markdownai-converter'),
            'search_term' => __('Search Term', 'markdownai-converter'),
            'post_id' => __('Post ID', 'markdownai-converter'),
            'status_code' => __('Status', 'markdownai-converter'),
            'latency_ms' => __('Latency (ms)', 'markdownai-converter'),
            'bytes_sent' => __('Bytes', 'markdownai-converter'),
            'endpoint' => __('Endpoint', 'markdownai-converter'),
        ];
    }

    protected function get_sortable_columns(): array
    {
        return [
            'event_time' => ['event_time', true],
            'bot_family' => ['bot_family', false],
            'post_id' => ['post_id', false],
            'status_code' => ['status_code', false],
            'latency_ms' => ['latency_ms', false],
            'bytes_sent' => ['bytes_sent', false],
        ];
    }

    protected function column_default($item, $columnName)
    {
        switch ($columnName) {
            case 'event_time':
            case 'bot_family':
            case 'search_term':
            case 'status_code':
            case 'latency_ms':
            case 'bytes_sent':
                return esc_html((string) $item[$columnName]);
            case 'post_id':
                return ! empty($item['post_id']) ? esc_html((string) $item['post_id']) : '—';
            case 'endpoint':
                $endpoint = (string) ($item['endpoint'] ?? '');
                if ($endpoint === '') {
                    return '—';
                }

                $resolvedEndpoint = self::resolve_endpoint_url($endpoint);
                return sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url($resolvedEndpoint), esc_html($endpoint));
            default:
                return '';
        }
    }

    private static function resolve_endpoint_url(string $endpoint): string
    {
        $trimmed = trim($endpoint);
        if ($trimmed === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $trimmed) === 1) {
            return $trimmed;
        }

        $normalized = ltrim($trimmed, '/');

        if (strpos($normalized, 'mdai/v1/') === 0) {
            return rest_url($normalized);
        }

        return home_url('/' . $normalized);
    }

    protected function get_views(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mdai_bot_events';

        $currentFamily = isset($_GET['bot_family']) ? sanitize_key(wp_unslash($_GET['bot_family'])) : '';
        $families = $wpdb->get_col("SELECT DISTINCT bot_family FROM {$table} ORDER BY bot_family ASC");

        $views = [];
        $baseUrl = admin_url('admin.php?page=mdai-bot-activity');
        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s</a>',
            esc_url($baseUrl),
            $currentFamily === '' ? 'current' : '',
            esc_html__('All', 'markdownai-converter')
        );

        foreach ($families as $family) {
            $family = sanitize_key((string) $family);
            if ($family === '') {
                continue;
            }
            $views[$family] = sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url(add_query_arg(['page' => 'mdai-bot-activity', 'bot_family' => $family], admin_url('admin.php'))),
                $currentFamily === $family ? 'current' : '',
                esc_html(ucfirst($family))
            );
        }

        return $views;
    }

    public function prepare_items(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mdai_bot_events';
        $perPage = 20;
        $paged = isset($_GET['paged']) ? max(1, absint(wp_unslash($_GET['paged']))) : 1;
        $offset = ($paged - 1) * $perPage;

        $sortable = $this->get_sortable_columns();
        $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'event_time';
        if (! isset($sortable[$orderby])) {
            $orderby = 'event_time';
        }

        $order = isset($_GET['order']) ? strtoupper(sanitize_text_field(wp_unslash($_GET['order']))) : 'DESC';
        $order = $order === 'ASC' ? 'ASC' : 'DESC';

        $where = 'WHERE 1=1';
        $params = [];

        $botFamily = isset($_GET['bot_family']) ? sanitize_key(wp_unslash($_GET['bot_family'])) : '';
        if ($botFamily !== '') {
            $where .= ' AND bot_family = %s';
            $params[] = $botFamily;
        }

        $fromDate = isset($_GET['from_date']) ? sanitize_text_field(wp_unslash($_GET['from_date'])) : '';
        if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) === 1) {
            $where .= ' AND event_time >= %s';
            $params[] = $fromDate . ' 00:00:00';
        }

        $toDate = isset($_GET['to_date']) ? sanitize_text_field(wp_unslash($_GET['to_date'])) : '';
        if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) === 1) {
            $where .= ' AND event_time <= %s';
            $params[] = $toDate . ' 23:59:59';
        }

        $countSql = "SELECT COUNT(*) FROM {$table} {$where}";
        $countQuery = $params !== [] ? $wpdb->prepare($countSql, ...$params) : $countSql;
        $totalItems = (int) $wpdb->get_var($countQuery);

        $dataSql = "SELECT id, event_time, bot_family, search_term, post_id, status_code, latency_ms, bytes_sent, endpoint
            FROM {$table}
            {$where}
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d";

        $dataParams = $params;
        $dataParams[] = $perPage;
        $dataParams[] = $offset;

        $dataQuery = $wpdb->prepare($dataSql, ...$dataParams);
        $items = $wpdb->get_results($dataQuery, ARRAY_A);

        $this->items = is_array($items) ? $items : [];
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'total_pages' => (int) ceil(max(1, $totalItems) / $perPage),
        ]);
    }
}
