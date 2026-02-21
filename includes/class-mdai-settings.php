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
        ];
    }

    public static function sanitize(array $input): array
    {
        $defaults = self::defaults();

        $enableTracking = isset($input['enable_tracking']) ? 1 : 0;
        $retentionDays = isset($input['retention_days']) ? absint($input['retention_days']) : $defaults['retention_days'];
        $retentionDays = max(7, min(3650, $retentionDays));
        $deleteDataOnUninstall = isset($input['delete_data_on_uninstall']) ? 1 : 0;

        return [
            'enable_tracking' => $enableTracking,
            'retention_days' => $retentionDays,
            'delete_data_on_uninstall' => $deleteDataOnUninstall,
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
}
