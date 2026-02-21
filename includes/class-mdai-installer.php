<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Installer
{
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
            post_id BIGINT UNSIGNED NULL,
            status_code SMALLINT UNSIGNED NOT NULL,
            bytes_sent BIGINT UNSIGNED NOT NULL DEFAULT 0,
            latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
            referer_host VARCHAR(255) NULL,
            PRIMARY KEY (id),
            KEY event_time_idx (event_time),
            KEY bot_family_idx (bot_family),
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
}
