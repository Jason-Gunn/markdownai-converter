<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Installer
{
    public static function maybe_upgrade_schema(): void
    {
        $currentDbVersion = (string) get_option(Plugin::OPTION_DB_VERSION, '0');
        if (version_compare($currentDbVersion, Plugin::DB_VERSION, '<')) {
            self::install_or_upgrade();
        }
    }

    public static function install_or_upgrade(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();
        $contentCacheTable = $wpdb->prefix . 'mdai_content_cache';
        $botEventsTable = $wpdb->prefix . 'mdai_bot_events';
        $dailyAggregatesTable = $wpdb->prefix . 'mdai_daily_aggregates';

        $sqlContentCache = "CREATE TABLE {$contentCacheTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            post_modified_gmt DATETIME NOT NULL,
            markdown_blob LONGTEXT NOT NULL,
            checksum CHAR(64) NOT NULL,
            generated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY post_id_unique (post_id),
            KEY modified_idx (post_modified_gmt)
        ) {$charsetCollate};";

        $sqlBotEvents = "CREATE TABLE {$botEventsTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_time DATETIME NOT NULL,
            bot_family VARCHAR(64) NOT NULL,
            user_agent VARCHAR(512) NOT NULL,
            ip_hash CHAR(64) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            search_term VARCHAR(255) NULL,
            post_id BIGINT UNSIGNED NULL,
            status_code SMALLINT UNSIGNED NOT NULL,
            bytes_sent BIGINT UNSIGNED NOT NULL DEFAULT 0,
            latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
            referer_host VARCHAR(255) NULL,
            PRIMARY KEY (id),
            KEY event_time_idx (event_time),
            KEY bot_family_idx (bot_family),
            KEY search_term_idx (search_term),
            KEY post_id_idx (post_id)
        ) {$charsetCollate};";

        $sqlDailyAggregates = "CREATE TABLE {$dailyAggregatesTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_date DATE NOT NULL,
            bot_family VARCHAR(64) NOT NULL,
            post_id BIGINT UNSIGNED NULL,
            hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
            unique_signatures BIGINT UNSIGNED NOT NULL DEFAULT 0,
            avg_latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY day_bot_post_unique (event_date, bot_family, post_id),
            KEY event_date_idx (event_date)
        ) {$charsetCollate};";

        dbDelta($sqlContentCache);
        dbDelta($sqlBotEvents);
        dbDelta($sqlDailyAggregates);

        update_option(Plugin::OPTION_DB_VERSION, Plugin::DB_VERSION, false);
    }

    public static function run_daily_aggregation_job(): void
    {
        global $wpdb;

        $eventsTable = $wpdb->prefix . 'mdai_bot_events';
        $aggregatesTable = $wpdb->prefix . 'mdai_daily_aggregates';

        $targetDate = gmdate('Y-m-d', strtotime('-1 day'));

        $aggregateSql = "INSERT INTO {$aggregatesTable}
            (event_date, bot_family, post_id, hits, unique_signatures, avg_latency_ms)
            SELECT
                DATE(event_time) AS event_date,
                bot_family,
                post_id,
                COUNT(*) AS hits,
                COUNT(DISTINCT CONCAT(ip_hash, ':', user_agent)) AS unique_signatures,
                ROUND(AVG(latency_ms)) AS avg_latency_ms
            FROM {$eventsTable}
            WHERE DATE(event_time) = %s
            GROUP BY DATE(event_time), bot_family, post_id
            ON DUPLICATE KEY UPDATE
                hits = VALUES(hits),
                unique_signatures = VALUES(unique_signatures),
                avg_latency_ms = VALUES(avg_latency_ms)";

        $wpdb->query($wpdb->prepare($aggregateSql, $targetDate));

        $settings = Settings::get_all();
        $retentionDays = isset($settings['retention_days']) ? absint($settings['retention_days']) : 90;
        $retentionDays = max(7, min(3650, $retentionDays));

        $cutoffDateTime = gmdate('Y-m-d H:i:s', strtotime('-' . $retentionDays . ' days'));
        $cutoffDate = gmdate('Y-m-d', strtotime('-' . $retentionDays . ' days'));

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$eventsTable} WHERE event_time < %s",
                $cutoffDateTime
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$aggregatesTable} WHERE event_date < %s",
                $cutoffDate
            )
        );
    }

    public static function run_weekly_report_email_job(): void
    {
        self::send_weekly_report_email(false);
    }

    public static function send_weekly_report_email(bool $force = false): bool
    {
        $settings = Settings::get_all();
        if (! $force && empty($settings['enable_weekly_reports'])) {
            return false;
        }

        $recipient = sanitize_email((string) ($settings['report_email'] ?? ''));
        if (! is_email($recipient)) {
            return false;
        }

        $fromDate = gmdate('Y-m-d', strtotime('-6 days'));
        $toDate = gmdate('Y-m-d');
        $report = Report::build_report_data($fromDate, $toDate);

        $kpis = (array) ($report['kpis'] ?? []);
        $topPages = array_slice((array) ($report['top_pages'] ?? []), 0, 5);

        $subject = sprintf(
            __('[%1$s] Weekly MarkdownAI report (%2$s to %3$s)', 'markdownai-converter'),
            (string) ($report['site_name'] ?? get_bloginfo('name')),
            (string) ($report['range']['from'] ?? $fromDate),
            (string) ($report['range']['to'] ?? $toDate)
        );

        $lines = [];
        $lines[] = __('MarkdownAI Converter — Weekly Performance Summary', 'markdownai-converter');
        $lines[] = str_repeat('=', 54);
        $lines[] = sprintf(__('Site: %s', 'markdownai-converter'), (string) ($report['site_name'] ?? get_bloginfo('name')));
        $lines[] = sprintf(__('URL: %s', 'markdownai-converter'), (string) ($report['site_url'] ?? home_url('/')));
        $lines[] = sprintf(__('Range: %s to %s', 'markdownai-converter'), (string) ($report['range']['from'] ?? $fromDate), (string) ($report['range']['to'] ?? $toDate));
        $lines[] = '';
        $lines[] = __('KPIs', 'markdownai-converter');
        $lines[] = sprintf(__('Total Bot Hits: %s', 'markdownai-converter'), number_format_i18n((int) ($kpis['total_hits'] ?? 0)));
        $lines[] = sprintf(__('Unique Bot Families: %s', 'markdownai-converter'), number_format_i18n((int) ($kpis['unique_bot_families'] ?? 0)));
        $lines[] = sprintf(__('Unique Crawled Pages: %s', 'markdownai-converter'), number_format_i18n((int) ($kpis['unique_posts'] ?? 0)));
        $lines[] = sprintf(__('Average Latency (ms): %s', 'markdownai-converter'), number_format_i18n((int) ($kpis['avg_latency_ms'] ?? 0)));
        $lines[] = '';
        $lines[] = __('Top Crawled Pages', 'markdownai-converter');

        if ($topPages === []) {
            $lines[] = __('- No page activity for this period.', 'markdownai-converter');
        } else {
            foreach ($topPages as $row) {
                $lines[] = sprintf(
                    '- #%1$d %2$s | %3$s %4$s',
                    (int) ($row['post_id'] ?? 0),
                    (string) (($row['post_title'] ?? '') !== '' ? $row['post_title'] : __('(no title)', 'markdownai-converter')),
                    number_format_i18n((int) ($row['hits'] ?? 0)),
                    __('hits', 'markdownai-converter')
                );
            }
        }

        $lines[] = '';
        $lines[] = sprintf(
            __('View full dashboard: %s', 'markdownai-converter'),
            admin_url('admin.php?page=mdai-overview')
        );

        return (bool) wp_mail(
            $recipient,
            $subject,
            implode("\n", $lines),
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }
}
