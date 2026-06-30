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

$tableSuffixes = [
    'mdai_content_cache',
    'mdai_bot_events',
    'mdai_daily_aggregates',
];

$tablePrefix = preg_replace('/[^A-Za-z0-9_]/', '', (string) $wpdb->prefix);

if (is_string($tablePrefix) && $tablePrefix !== '') {
    foreach ($tableSuffixes as $tableSuffix) {
        $tableName = $tablePrefix . $tableSuffix;
        $wpdb->query("DROP TABLE IF EXISTS `{$tableName}`");
    }
}

delete_option('mdai_settings');
delete_option('mdai_db_version');
