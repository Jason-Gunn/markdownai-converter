<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('mdai_settings', []);
$deleteData = is_array($settings) && ! empty($settings['delete_data_on_uninstall']);

if (! $deleteData) {
    return;
}

global $wpdb;

$tables = [
    $wpdb->prefix . 'mdai_content_cache',
    $wpdb->prefix . 'mdai_bot_events',
    $wpdb->prefix . 'mdai_daily_aggregates',
];

foreach ($tables as $tableName) {
    $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
}

delete_option('mdai_settings');
delete_option('mdai_db_version');
