<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-installer.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-settings.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-markdown-service.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-analytics.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-report.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-pdf.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-demo-data.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-suggestions.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-bot-detector.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-bot-tracker.php';
require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-rest.php';
require_once MDAI_PLUGIN_DIR . 'includes/admin/class-mdai-admin.php';

class Plugin
{
    public const OPTION_SETTINGS = 'mdai_settings';
    public const OPTION_DB_VERSION = 'mdai_db_version';
    public const DB_VERSION = '2';
    public const CRON_HOOK_DAILY_AGGREGATION = 'mdai_daily_aggregation_job';
    public const CRON_HOOK_WEEKLY_REPORT_EMAIL = 'mdai_weekly_report_email_job';

    public static function boot(): void
    {
        add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);
        add_action('plugins_loaded', [Installer::class, 'maybe_upgrade_schema'], 20);
        add_filter('cron_schedules', [__CLASS__, 'register_cron_schedules']);
        add_action('admin_init', [Settings::class, 'register']);
        add_action('admin_menu', [Admin\Admin::class, 'register_menu']);
        add_action('rest_api_init', [Rest::class, 'register_routes']);
        add_action('admin_post_mdai_export_bot_events', [Admin\Admin::class, 'export_bot_events_csv']);
        add_action('admin_post_mdai_generate_report', [Admin\Admin::class, 'generate_client_report']);
        add_action('admin_post_mdai_generate_report_pdf', [Admin\Admin::class, 'generate_client_report_pdf']);
        add_action('admin_post_mdai_send_test_weekly_report', [Admin\Admin::class, 'send_test_weekly_report']);
        add_action('admin_post_mdai_seed_demo_data', [Admin\Admin::class, 'seed_demo_data']);
        add_action('admin_post_mdai_clear_demo_data', [Admin\Admin::class, 'clear_demo_data']);
        add_action(self::CRON_HOOK_DAILY_AGGREGATION, [Installer::class, 'run_daily_aggregation_job']);
        add_action(self::CRON_HOOK_WEEKLY_REPORT_EMAIL, [Installer::class, 'run_weekly_report_email_job']);
    }

    public static function activate(): void
    {
        Installer::install_or_upgrade();
        Settings::maybe_seed_defaults();

        if (! wp_next_scheduled(self::CRON_HOOK_DAILY_AGGREGATION)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK_DAILY_AGGREGATION);
        }

        if (! wp_next_scheduled(self::CRON_HOOK_WEEKLY_REPORT_EMAIL)) {
            wp_schedule_event(time() + (2 * HOUR_IN_SECONDS), 'mdai_weekly', self::CRON_HOOK_WEEKLY_REPORT_EMAIL);
        }
    }

    public static function deactivate(): void
    {
        $nextTimestamp = wp_next_scheduled(self::CRON_HOOK_DAILY_AGGREGATION);
        if ($nextTimestamp !== false) {
            wp_unschedule_event($nextTimestamp, self::CRON_HOOK_DAILY_AGGREGATION);
        }

        $nextWeeklyTimestamp = wp_next_scheduled(self::CRON_HOOK_WEEKLY_REPORT_EMAIL);
        if ($nextWeeklyTimestamp !== false) {
            wp_unschedule_event($nextWeeklyTimestamp, self::CRON_HOOK_WEEKLY_REPORT_EMAIL);
        }
    }

    public static function register_cron_schedules(array $schedules): array
    {
        if (! isset($schedules['mdai_weekly'])) {
            $schedules['mdai_weekly'] = [
                'interval' => WEEK_IN_SECONDS,
                'display' => __('Once Weekly (MarkdownAI)', 'markdownai-converter'),
            ];
        }

        return $schedules;
    }

    public static function load_textdomain(): void
    {
        load_plugin_textdomain('markdownai-converter', false, dirname(MDAI_PLUGIN_BASENAME) . '/languages');
    }
}
