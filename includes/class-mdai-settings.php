<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Settings
{
    public static function register(): void
    {
        register_setting(
            'mdai_settings_group',
            Plugin::OPTION_SETTINGS,
            [
                'type' => 'array',
                'sanitize_callback' => [__CLASS__, 'sanitize'],
                'default' => self::defaults(),
                'show_in_rest' => false,
            ]
        );

        add_settings_section(
            'mdai_general_section',
            __('General Settings', 'markdownai-converter'),
            '__return_empty_string',
            'mdai-settings'
        );

        add_settings_field(
            'mdai_enable_tracking',
            __('Enable bot tracking', 'markdownai-converter'),
            [__CLASS__, 'render_enable_tracking_field'],
            'mdai-settings',
            'mdai_general_section'
        );

        add_settings_field(
            'mdai_retention_days',
            __('Log retention (days)', 'markdownai-converter'),
            [__CLASS__, 'render_retention_days_field'],
            'mdai-settings',
            'mdai_general_section'
        );

        add_settings_field(
            'mdai_delete_data_on_uninstall',
            __('Delete data on uninstall', 'markdownai-converter'),
            [__CLASS__, 'render_delete_data_on_uninstall_field'],
            'mdai-settings',
            'mdai_general_section'
        );

        add_settings_field(
            'mdai_enable_weekly_reports',
            __('Enable weekly email reports', 'markdownai-converter'),
            [__CLASS__, 'render_enable_weekly_reports_field'],
            'mdai-settings',
            'mdai_general_section'
        );

        add_settings_field(
            'mdai_report_email',
            __('Weekly report recipient email', 'markdownai-converter'),
            [__CLASS__, 'render_report_email_field'],
            'mdai-settings',
            'mdai_general_section'
        );

        add_settings_field(
            'mdai_report_brand_name',
            __('Report brand name', 'markdownai-converter'),
            [__CLASS__, 'render_report_brand_name_field'],
            'mdai-settings',
            'mdai_general_section'
        );

        add_settings_field(
            'mdai_report_logo_url',
            __('Report logo URL', 'markdownai-converter'),
            [__CLASS__, 'render_report_logo_url_field'],
            'mdai-settings',
            'mdai_general_section'
        );

        add_settings_field(
            'mdai_report_accent_color',
            __('Report accent color', 'markdownai-converter'),
            [__CLASS__, 'render_report_accent_color_field'],
            'mdai-settings',
            'mdai_general_section'
        );
    }

    public static function maybe_seed_defaults(): void
    {
        if (get_option(Plugin::OPTION_SETTINGS) === false) {
            add_option(Plugin::OPTION_SETTINGS, self::defaults(), '', false);
        }
    }

    public static function defaults(): array
    {
        return [
            'enable_tracking' => 1,
            'retention_days' => 90,
            'delete_data_on_uninstall' => 0,
            'enable_weekly_reports' => 0,
            'report_email' => sanitize_email((string) get_option('admin_email', '')),
            'report_brand_name' => '',
            'report_logo_url' => '',
            'report_accent_color' => '#2271b1',
        ];
    }

    public static function sanitize(array $input): array
    {
        $defaults = self::defaults();

        $enableTracking = isset($input['enable_tracking']) ? 1 : 0;
        $retentionDays = isset($input['retention_days']) ? absint($input['retention_days']) : $defaults['retention_days'];
        $retentionDays = max(7, min(3650, $retentionDays));
        $deleteDataOnUninstall = isset($input['delete_data_on_uninstall']) ? 1 : 0;
        $enableWeeklyReports = isset($input['enable_weekly_reports']) ? 1 : 0;

        $emailInput = isset($input['report_email']) ? sanitize_email((string) $input['report_email']) : '';
        $reportEmail = is_email($emailInput) ? $emailInput : (string) $defaults['report_email'];
        $reportBrandName = isset($input['report_brand_name']) ? sanitize_text_field((string) $input['report_brand_name']) : '';
        $reportLogoUrl = isset($input['report_logo_url']) ? esc_url_raw((string) $input['report_logo_url']) : '';
        $accentColorInput = isset($input['report_accent_color']) ? sanitize_text_field((string) $input['report_accent_color']) : $defaults['report_accent_color'];
        $reportAccentColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $accentColorInput) === 1 ? strtoupper($accentColorInput) : (string) $defaults['report_accent_color'];

        return [
            'enable_tracking' => $enableTracking,
            'retention_days' => $retentionDays,
            'delete_data_on_uninstall' => $deleteDataOnUninstall,
            'enable_weekly_reports' => $enableWeeklyReports,
            'report_email' => $reportEmail,
            'report_brand_name' => $reportBrandName,
            'report_logo_url' => $reportLogoUrl,
            'report_accent_color' => $reportAccentColor,
        ];
    }

    public static function get_all(): array
    {
        $settings = get_option(Plugin::OPTION_SETTINGS, []);
        return wp_parse_args(is_array($settings) ? $settings : [], self::defaults());
    }

    public static function render_enable_tracking_field(): void
    {
        $settings = self::get_all();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(Plugin::OPTION_SETTINGS); ?>[enable_tracking]" value="1" <?php checked((int) $settings['enable_tracking'], 1); ?> />
            <?php esc_html_e('Track bot activity for MarkdownAI endpoints.', 'markdownai-converter'); ?>
        </label>
        <?php
    }

    public static function render_retention_days_field(): void
    {
        $settings = self::get_all();
        ?>
        <input type="number" min="7" max="3650" step="1" name="<?php echo esc_attr(Plugin::OPTION_SETTINGS); ?>[retention_days]" value="<?php echo esc_attr((string) $settings['retention_days']); ?>" class="small-text" />
        <p class="description"><?php esc_html_e('How long to keep bot event logs before cleanup jobs purge old records.', 'markdownai-converter'); ?></p>
        <?php
    }

    public static function render_delete_data_on_uninstall_field(): void
    {
        $settings = self::get_all();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(Plugin::OPTION_SETTINGS); ?>[delete_data_on_uninstall]" value="1" <?php checked((int) $settings['delete_data_on_uninstall'], 1); ?> />
            <?php esc_html_e('Delete plugin tables and settings when uninstalling the plugin.', 'markdownai-converter'); ?>
        </label>
        <?php
    }

    public static function render_enable_weekly_reports_field(): void
    {
        $settings = self::get_all();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(Plugin::OPTION_SETTINGS); ?>[enable_weekly_reports]" value="1" <?php checked((int) $settings['enable_weekly_reports'], 1); ?> />
            <?php esc_html_e('Email a weekly performance summary report.', 'markdownai-converter'); ?>
        </label>
        <?php
    }

    public static function render_report_email_field(): void
    {
        $settings = self::get_all();
        ?>
        <input type="email" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_SETTINGS); ?>[report_email]" value="<?php echo esc_attr((string) $settings['report_email']); ?>" />
        <p class="description"><?php esc_html_e('Recipient address for scheduled weekly reports.', 'markdownai-converter'); ?></p>
        <?php
    }

    public static function render_report_brand_name_field(): void
    {
        $settings = self::get_all();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_SETTINGS); ?>[report_brand_name]" value="<?php echo esc_attr((string) $settings['report_brand_name']); ?>" />
        <p class="description"><?php esc_html_e('Optional name shown in report header (for agency/client branding).', 'markdownai-converter'); ?></p>
        <?php
    }

    public static function render_report_logo_url_field(): void
    {
        $settings = self::get_all();
        ?>
        <input type="url" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_SETTINGS); ?>[report_logo_url]" value="<?php echo esc_attr((string) $settings['report_logo_url']); ?>" />
        <p class="description"><?php esc_html_e('Optional absolute image URL for report logo.', 'markdownai-converter'); ?></p>
        <?php
    }

    public static function render_report_accent_color_field(): void
    {
        $settings = self::get_all();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_SETTINGS); ?>[report_accent_color]" value="<?php echo esc_attr((string) $settings['report_accent_color']); ?>" placeholder="#2271B1" />
        <p class="description"><?php esc_html_e('Hex color used for report accent styling (e.g. #2271B1).', 'markdownai-converter'); ?></p>
        <?php
    }
}
