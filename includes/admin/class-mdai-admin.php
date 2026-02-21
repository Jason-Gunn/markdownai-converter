<?php

namespace MDAI\Admin;

use MDAI\Analytics;
use MDAI\Demo_Data;
use MDAI\Markdown_Service;
use MDAI\Plugin;
use MDAI\Report;
use MDAI\Suggestions;

if (! defined('ABSPATH')) {
    exit;
}

class Admin
{
    public static function register_menu(): void
    {
        $capability = 'manage_options';

        add_menu_page(
            __('MarkdownAI Converter', 'markdownai-converter'),
            __('MarkdownAI Converter', 'markdownai-converter'),
            $capability,
            'mdai-overview',
            [__CLASS__, 'render_overview_page'],
            'dashicons-media-code',
            58
        );

        add_submenu_page(
            'mdai-overview',
            __('Overview', 'markdownai-converter'),
            __('Overview', 'markdownai-converter'),
            $capability,
            'mdai-overview',
            [__CLASS__, 'render_overview_page']
        );

        add_submenu_page(
            'mdai-overview',
            __('Content Preview', 'markdownai-converter'),
            __('Content Preview', 'markdownai-converter'),
            $capability,
            'mdai-content-preview',
            [__CLASS__, 'render_content_preview_page']
        );

        add_submenu_page(
            'mdai-overview',
            __('Bot Activity', 'markdownai-converter'),
            __('Bot Activity', 'markdownai-converter'),
            $capability,
            'mdai-bot-activity',
            [__CLASS__, 'render_bot_activity_page']
        );

        add_submenu_page(
            'mdai-overview',
            __('Suggestions', 'markdownai-converter'),
            __('Suggestions', 'markdownai-converter'),
            $capability,
            'mdai-suggestions',
            [__CLASS__, 'render_suggestions_page']
        );

        add_submenu_page(
            'mdai-overview',
            __('Export & Reports', 'markdownai-converter'),
            __('Export & Reports', 'markdownai-converter'),
            $capability,
            'mdai-export-reports',
            [__CLASS__, 'render_export_reports_page']
        );

        add_submenu_page(
            'mdai-overview',
            __('Settings', 'markdownai-converter'),
            __('Settings', 'markdownai-converter'),
            $capability,
            'mdai-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function render_overview_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'markdownai-converter'));
        }

        $quickRange = isset($_GET['range']) ? sanitize_key(wp_unslash($_GET['range'])) : '30d';
        $fromInput = isset($_GET['from_date']) ? sanitize_text_field(wp_unslash($_GET['from_date'])) : '';
        $toInput = isset($_GET['to_date']) ? sanitize_text_field(wp_unslash($_GET['to_date'])) : '';

        if ($quickRange === '7d') {
            $fromInput = gmdate('Y-m-d', strtotime('-6 days'));
            $toInput = gmdate('Y-m-d');
        } elseif ($quickRange === '30d') {
            $fromInput = gmdate('Y-m-d', strtotime('-29 days'));
            $toInput = gmdate('Y-m-d');
        }

        $range = Analytics::sanitize_date_range($fromInput, $toInput);
        $kpis = Analytics::get_kpis($range['from_datetime'], $range['to_datetime']);
        $topPages = Analytics::get_top_pages($range['from_datetime'], $range['to_datetime'], 10);
        $trend = Analytics::get_daily_trend($range['from_datetime'], $range['to_datetime']);
        $noticeAction = isset($_GET['mdai_notice']) ? sanitize_key(wp_unslash($_GET['mdai_notice'])) : '';
        $noticeCount = isset($_GET['mdai_count']) ? absint(wp_unslash($_GET['mdai_count'])) : 0;

        $svg = self::build_trend_svg($trend);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Overview', 'markdownai-converter'); ?></h1>
            <p><?php esc_html_e('Track how AI bots crawl your Markdown endpoints over time.', 'markdownai-converter'); ?></p>

            <?php if ($noticeAction === 'seeded') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf(__('Seeded %d demo bot events.', 'markdownai-converter'), $noticeCount)); ?></p></div>
            <?php elseif ($noticeAction === 'cleared') : ?>
                <div class="notice notice-warning is-dismissible"><p><?php echo esc_html(sprintf(__('Removed %d demo bot events.', 'markdownai-converter'), $noticeCount)); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 12px; display: inline-block; margin-right: 8px;">
                <input type="hidden" name="action" value="mdai_seed_demo_data" />
                <input type="hidden" name="days" value="90" />
                <?php wp_nonce_field('mdai_seed_demo_data'); ?>
                <?php submit_button(__('Seed 90 Days Demo Data', 'markdownai-converter'), 'primary', '', false); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 12px; display: inline-block;">
                <input type="hidden" name="action" value="mdai_clear_demo_data" />
                <?php wp_nonce_field('mdai_clear_demo_data'); ?>
                <?php submit_button(__('Clear Demo Data', 'markdownai-converter'), 'secondary', '', false); ?>
            </form>

            <form method="get" action="" style="margin-bottom: 16px; display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="mdai-overview" />
                <div>
                    <label for="mdai-range"><strong><?php esc_html_e('Quick Range', 'markdownai-converter'); ?></strong></label><br />
                    <select id="mdai-range" name="range">
                        <option value="7d" <?php selected($quickRange, '7d'); ?>><?php esc_html_e('Last 7 days', 'markdownai-converter'); ?></option>
                        <option value="30d" <?php selected($quickRange, '30d'); ?>><?php esc_html_e('Last 30 days', 'markdownai-converter'); ?></option>
                        <option value="custom" <?php selected($quickRange, 'custom'); ?>><?php esc_html_e('Custom', 'markdownai-converter'); ?></option>
                    </select>
                </div>
                <div>
                    <label for="mdai-overview-from"><strong><?php esc_html_e('From', 'markdownai-converter'); ?></strong></label><br />
                    <input id="mdai-overview-from" type="date" name="from_date" value="<?php echo esc_attr($range['from']); ?>" />
                </div>
                <div>
                    <label for="mdai-overview-to"><strong><?php esc_html_e('To', 'markdownai-converter'); ?></strong></label><br />
                    <input id="mdai-overview-to" type="date" name="to_date" value="<?php echo esc_attr($range['to']); ?>" />
                </div>
                <div>
                    <?php submit_button(__('Apply', 'markdownai-converter'), 'secondary', '', false); ?>
                </div>
            </form>

            <table class="widefat striped" style="max-width: 920px; margin-bottom: 16px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Total Bot Hits', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Unique Bot Families', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Unique Crawled Pages', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Avg Latency (ms)', 'markdownai-converter'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php echo esc_html(number_format_i18n($kpis['total_hits'])); ?></strong></td>
                        <td><strong><?php echo esc_html(number_format_i18n($kpis['unique_bot_families'])); ?></strong></td>
                        <td><strong><?php echo esc_html(number_format_i18n($kpis['unique_posts'])); ?></strong></td>
                        <td><strong><?php echo esc_html(number_format_i18n($kpis['avg_latency_ms'])); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e('Daily Crawl Trend', 'markdownai-converter'); ?></h2>
            <?php if ($svg !== '') : ?>
                <div style="background: #fff; border: 1px solid #c3c4c7; padding: 12px; max-width: 920px;">
                    <?php echo wp_kses($svg, [
                        'svg' => ['viewBox' => true, 'width' => true, 'height' => true, 'xmlns' => true],
                        'polyline' => ['fill' => true, 'stroke' => true, 'stroke-width' => true, 'points' => true],
                        'line' => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true],
                        'text' => ['x' => true, 'y' => true, 'font-size' => true, 'fill' => true],
                        'circle' => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true],
                    ]); ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e('No trend data for the selected period yet.', 'markdownai-converter'); ?></p>
            <?php endif; ?>

            <h2><?php esc_html_e('Top Crawled Pages', 'markdownai-converter'); ?></h2>
            <table class="widefat striped" style="max-width: 920px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Post ID', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Title', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Hits', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Bot Families', 'markdownai-converter'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($topPages === []) : ?>
                        <tr><td colspan="4"><?php esc_html_e('No crawled pages yet for this period.', 'markdownai-converter'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($topPages as $row) : ?>
                            <tr>
                                <td><?php echo esc_html((string) $row['post_id']); ?></td>
                                <td>
                                    <?php if (! empty($row['post_url'])) : ?>
                                        <a href="<?php echo esc_url((string) $row['post_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html((string) ($row['post_title'] ?: __('(no title)', 'markdownai-converter'))); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html((string) ($row['post_title'] ?: __('(deleted content)', 'markdownai-converter'))); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(number_format_i18n((int) $row['hits'])); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $row['bot_families'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_content_preview_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'markdownai-converter'));
        }

        $selectedPostId = isset($_GET['post_id']) ? absint(wp_unslash($_GET['post_id'])) : 0;
        $forceRegenerate = isset($_GET['regenerate']) && wp_unslash($_GET['regenerate']) === '1';

        if ($forceRegenerate) {
            check_admin_referer('mdai_preview_regenerate_' . $selectedPostId);
        }

        $publicTypes = get_post_types(['public' => true], 'names');
        $posts = get_posts([
            'post_type' => array_values($publicTypes),
            'post_status' => 'publish',
            'numberposts' => 100,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        $selectedPost = $selectedPostId > 0 ? get_post($selectedPostId) : null;
        $markdownOutput = '';

        if ($selectedPost instanceof \WP_Post) {
            $markdownOutput = Markdown_Service::generate_for_post($selectedPost->ID, $forceRegenerate);
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Content Preview', 'markdownai-converter'); ?></h1>
            <p><?php esc_html_e('Select a published post or page to preview the generated Markdown output.', 'markdownai-converter'); ?></p>

            <form method="get" action="">
                <input type="hidden" name="page" value="mdai-content-preview" />
                <label for="mdai-post-id" class="screen-reader-text"><?php esc_html_e('Select content', 'markdownai-converter'); ?></label>
                <select id="mdai-post-id" name="post_id" style="min-width: 360px;">
                    <option value="0"><?php esc_html_e('Select content…', 'markdownai-converter'); ?></option>
                    <?php foreach ($posts as $postItem) : ?>
                        <option value="<?php echo esc_attr((string) $postItem->ID); ?>" <?php selected($selectedPostId, (int) $postItem->ID); ?>>
                            <?php echo esc_html(sprintf('#%d — %s (%s)', (int) $postItem->ID, get_the_title($postItem), $postItem->post_type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button(__('Preview Markdown', 'markdownai-converter'), 'secondary', '', false); ?>
            </form>

            <?php if ($selectedPost instanceof \WP_Post) : ?>
                <hr />
                <h2><?php echo esc_html(get_the_title($selectedPost)); ?></h2>
                <p>
                    <strong><?php esc_html_e('Post Type:', 'markdownai-converter'); ?></strong>
                    <?php echo esc_html($selectedPost->post_type); ?>
                    &nbsp;|&nbsp;
                    <strong><?php esc_html_e('Last Modified (GMT):', 'markdownai-converter'); ?></strong>
                    <?php echo esc_html($selectedPost->post_modified_gmt); ?>
                </p>

                <p>
                    <a class="button" href="<?php echo esc_url(wp_nonce_url(add_query_arg([
                        'page' => 'mdai-content-preview',
                        'post_id' => (int) $selectedPost->ID,
                        'regenerate' => '1',
                    ], admin_url('admin.php')), 'mdai_preview_regenerate_' . (int) $selectedPost->ID)); ?>">
                        <?php esc_html_e('Regenerate Markdown', 'markdownai-converter'); ?>
                    </a>
                </p>

                <h3><?php esc_html_e('Rendered HTML Excerpt', 'markdownai-converter'); ?></h3>
                <div style="background: #fff; border: 1px solid #c3c4c7; padding: 12px; max-height: 180px; overflow: auto;">
                    <?php echo wp_kses_post(wpautop(wp_trim_words(wp_strip_all_tags((string) $selectedPost->post_content), 120))); ?>
                </div>

                <h3><?php esc_html_e('Generated Markdown', 'markdownai-converter'); ?></h3>
                <textarea readonly rows="18" style="width: 100%; font-family: monospace;"><?php echo esc_textarea($markdownOutput); ?></textarea>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_bot_activity_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'markdownai-converter'));
        }

        if (! class_exists(Bot_Activity_Table::class)) {
            require_once MDAI_PLUGIN_DIR . 'includes/admin/class-mdai-bot-activity-table.php';
        }

        $table = new Bot_Activity_Table();
        $table->prepare_items();

        $fromDate = isset($_GET['from_date']) ? sanitize_text_field(wp_unslash($_GET['from_date'])) : '';
        $toDate = isset($_GET['to_date']) ? sanitize_text_field(wp_unslash($_GET['to_date'])) : '';
        $botFamily = isset($_GET['bot_family']) ? sanitize_key(wp_unslash($_GET['bot_family'])) : '';

        $csvUrl = wp_nonce_url(
            add_query_arg(
                [
                    'action' => 'mdai_export_bot_events',
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'bot_family' => $botFamily,
                ],
                admin_url('admin-post.php')
            ),
            'mdai_export_bot_events'
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Bot Activity', 'markdownai-converter'); ?></h1>

            <form method="get" action="" style="margin-bottom: 12px; display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="mdai-bot-activity" />
                <div>
                    <label for="mdai-from-date"><strong><?php esc_html_e('From', 'markdownai-converter'); ?></strong></label><br />
                    <input id="mdai-from-date" type="date" name="from_date" value="<?php echo esc_attr($fromDate); ?>" />
                </div>
                <div>
                    <label for="mdai-to-date"><strong><?php esc_html_e('To', 'markdownai-converter'); ?></strong></label><br />
                    <input id="mdai-to-date" type="date" name="to_date" value="<?php echo esc_attr($toDate); ?>" />
                </div>
                <div>
                    <?php submit_button(__('Apply Filters', 'markdownai-converter'), 'secondary', '', false); ?>
                </div>
                <div>
                    <a class="button" href="<?php echo esc_url($csvUrl); ?>"><?php esc_html_e('Export CSV', 'markdownai-converter'); ?></a>
                </div>
            </form>

            <?php $table->views(); ?>
            <form method="get">
                <input type="hidden" name="page" value="mdai-bot-activity" />
                <?php if ($fromDate !== '') : ?>
                    <input type="hidden" name="from_date" value="<?php echo esc_attr($fromDate); ?>" />
                <?php endif; ?>
                <?php if ($toDate !== '') : ?>
                    <input type="hidden" name="to_date" value="<?php echo esc_attr($toDate); ?>" />
                <?php endif; ?>
                <?php if ($botFamily !== '') : ?>
                    <input type="hidden" name="bot_family" value="<?php echo esc_attr($botFamily); ?>" />
                <?php endif; ?>
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

    public static function export_bot_events_csv(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to export bot activity.', 'markdownai-converter'));
        }

        check_admin_referer('mdai_export_bot_events');

        global $wpdb;
        $table = $wpdb->prefix . 'mdai_bot_events';

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

        $sql = "SELECT event_time, bot_family, user_agent, post_id, status_code, latency_ms, bytes_sent, endpoint, referer_host
            FROM {$table}
            {$where}
            ORDER BY event_time DESC
            LIMIT 20000";

        $query = $params !== [] ? $wpdb->prepare($sql, ...$params) : $sql;
        $rows = $wpdb->get_results($query, ARRAY_A);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="mdai-bot-events-' . gmdate('Ymd-His') . '.csv"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Unable to create CSV output.', 'markdownai-converter'));
        }

        fputcsv($output, ['event_time', 'bot_family', 'user_agent', 'post_id', 'status_code', 'latency_ms', 'bytes_sent', 'endpoint', 'referer_host']);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                fputcsv($output, [
                    (string) ($row['event_time'] ?? ''),
                    (string) ($row['bot_family'] ?? ''),
                    (string) ($row['user_agent'] ?? ''),
                    (string) ($row['post_id'] ?? ''),
                    (string) ($row['status_code'] ?? ''),
                    (string) ($row['latency_ms'] ?? ''),
                    (string) ($row['bytes_sent'] ?? ''),
                    (string) ($row['endpoint'] ?? ''),
                    (string) ($row['referer_host'] ?? ''),
                ]);
            }
        }

        fclose($output);
        exit;
    }

    public static function seed_demo_data(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to seed demo data.', 'markdownai-converter'));
        }

        check_admin_referer('mdai_seed_demo_data');

        $days = isset($_POST['days']) ? absint(wp_unslash($_POST['days'])) : 90;
        $inserted = Demo_Data::seed_bot_events($days);

        wp_safe_redirect(add_query_arg([
            'page' => 'mdai-overview',
            'mdai_notice' => 'seeded',
            'mdai_count' => $inserted,
            'range' => '30d',
        ], admin_url('admin.php')));
        exit;
    }

    public static function clear_demo_data(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to clear demo data.', 'markdownai-converter'));
        }

        check_admin_referer('mdai_clear_demo_data');

        $deleted = Demo_Data::clear_seeded_events();

        wp_safe_redirect(add_query_arg([
            'page' => 'mdai-overview',
            'mdai_notice' => 'cleared',
            'mdai_count' => $deleted,
            'range' => '30d',
        ], admin_url('admin.php')));
        exit;
    }

    public static function render_suggestions_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'markdownai-converter'));
        }

        $selectedPostId = isset($_GET['post_id']) ? absint(wp_unslash($_GET['post_id'])) : 0;
        $analysis = $selectedPostId > 0 ? Suggestions::analyze_post($selectedPostId) : null;
        $recentAnalyses = Suggestions::analyze_recent_posts(25);

        $publicPostTypes = get_post_types(['public' => true], 'names');
        $posts = get_posts([
            'post_type' => array_values($publicPostTypes),
            'post_status' => 'publish',
            'numberposts' => 150,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Suggestions', 'markdownai-converter'); ?></h1>
            <p><?php esc_html_e('Run rule-based content checks to improve AI crawlability and extraction quality.', 'markdownai-converter'); ?></p>

            <form method="get" action="" style="margin-bottom: 16px; display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="mdai-suggestions" />
                <div>
                    <label for="mdai-suggestions-post" class="screen-reader-text"><?php esc_html_e('Select content', 'markdownai-converter'); ?></label>
                    <select id="mdai-suggestions-post" name="post_id" style="min-width: 420px;">
                        <option value="0"><?php esc_html_e('Select content to analyze…', 'markdownai-converter'); ?></option>
                        <?php foreach ($posts as $postItem) : ?>
                            <option value="<?php echo esc_attr((string) $postItem->ID); ?>" <?php selected($selectedPostId, (int) $postItem->ID); ?>>
                                <?php echo esc_html(sprintf('#%d — %s (%s)', (int) $postItem->ID, get_the_title($postItem), $postItem->post_type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <?php submit_button(__('Analyze Content', 'markdownai-converter'), 'primary', '', false); ?>
                </div>
            </form>

            <?php if (is_array($analysis) && (int) ($analysis['post_id'] ?? 0) > 0) : ?>
                <h2><?php esc_html_e('Page Analysis', 'markdownai-converter'); ?></h2>
                <table class="widefat striped" style="max-width: 920px; margin-bottom: 12px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Score', 'markdownai-converter'); ?></th>
                            <th><?php esc_html_e('Word Count', 'markdownai-converter'); ?></th>
                            <th><?php esc_html_e('Internal Links', 'markdownai-converter'); ?></th>
                            <th><?php esc_html_e('Images Missing Alt', 'markdownai-converter'); ?></th>
                            <th><?php esc_html_e('FAQ Section', 'markdownai-converter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php echo esc_html((string) $analysis['score']); ?>/100</strong></td>
                            <td><?php echo esc_html(number_format_i18n((int) $analysis['word_count'])); ?></td>
                            <td><?php echo esc_html(number_format_i18n((int) $analysis['internal_links'])); ?></td>
                            <td><?php echo esc_html(number_format_i18n((int) $analysis['images_missing_alt'])); ?></td>
                            <td><?php echo ! empty($analysis['faq_section_present']) ? esc_html__('Yes', 'markdownai-converter') : esc_html__('No', 'markdownai-converter'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <h3><?php esc_html_e('Recommendations', 'markdownai-converter'); ?></h3>
                <?php if (empty($analysis['suggestions'])) : ?>
                    <p><?php esc_html_e('No major issues detected. This page is in good shape for AI crawl extraction.', 'markdownai-converter'); ?></p>
                <?php else : ?>
                    <ol>
                        <?php foreach ((array) $analysis['suggestions'] as $suggestion) : ?>
                            <li style="margin-bottom: 8px;">
                                <strong><?php echo esc_html((string) ($suggestion['title'] ?? '')); ?></strong>
                                (<?php echo esc_html(ucfirst((string) ($suggestion['severity'] ?? 'low'))); ?>)
                                <br />
                                <?php echo esc_html((string) ($suggestion['detail'] ?? '')); ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            <?php endif; ?>

            <h2><?php esc_html_e('Recent Content Health (Lowest Score First)', 'markdownai-converter'); ?></h2>
            <table class="widefat striped" style="max-width: 920px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Post ID', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Title', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Score', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Word Count', 'markdownai-converter'); ?></th>
                        <th><?php esc_html_e('Priority Issues', 'markdownai-converter'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentAnalyses === []) : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No published content found to analyze.', 'markdownai-converter'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($recentAnalyses as $item) : ?>
                            <tr>
                                <td><?php echo esc_html((string) ($item['post_id'] ?? 0)); ?></td>
                                <td>
                                    <?php if (! empty($item['url'])) : ?>
                                        <a href="<?php echo esc_url((string) $item['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html((string) (($item['title'] ?? '') ?: __('(no title)', 'markdownai-converter'))); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html((string) (($item['title'] ?? '') ?: __('(no title)', 'markdownai-converter'))); ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html((string) ($item['score'] ?? 0)); ?>/100</strong></td>
                                <td><?php echo esc_html(number_format_i18n((int) ($item['word_count'] ?? 0))); ?></td>
                                <td><?php echo esc_html(number_format_i18n(count((array) ($item['suggestions'] ?? [])))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_export_reports_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'markdownai-converter'));
        }

        $fromDate = isset($_GET['from_date']) ? sanitize_text_field(wp_unslash($_GET['from_date'])) : '';
        $toDate = isset($_GET['to_date']) ? sanitize_text_field(wp_unslash($_GET['to_date'])) : '';
        $range = Analytics::sanitize_date_range($fromDate, $toDate);

        $csvUrl = wp_nonce_url(
            add_query_arg(
                [
                    'action' => 'mdai_export_bot_events',
                    'from_date' => $range['from'],
                    'to_date' => $range['to'],
                ],
                admin_url('admin-post.php')
            ),
            'mdai_export_bot_events'
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Export & Reports', 'markdownai-converter'); ?></h1>
            <p><?php esc_html_e('Export crawl data or generate a printable client performance report.', 'markdownai-converter'); ?></p>

            <form method="get" action="" style="margin-bottom: 16px; display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="mdai-export-reports" />
                <div>
                    <label for="mdai-report-from"><strong><?php esc_html_e('From', 'markdownai-converter'); ?></strong></label><br />
                    <input id="mdai-report-from" type="date" name="from_date" value="<?php echo esc_attr($range['from']); ?>" />
                </div>
                <div>
                    <label for="mdai-report-to"><strong><?php esc_html_e('To', 'markdownai-converter'); ?></strong></label><br />
                    <input id="mdai-report-to" type="date" name="to_date" value="<?php echo esc_attr($range['to']); ?>" />
                </div>
                <div>
                    <?php submit_button(__('Apply Range', 'markdownai-converter'), 'secondary', '', false); ?>
                </div>
            </form>

            <p>
                <a class="button" href="<?php echo esc_url($csvUrl); ?>"><?php esc_html_e('Export Bot Activity (CSV)', 'markdownai-converter'); ?></a>
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="mdai_generate_report" />
                <input type="hidden" name="from_date" value="<?php echo esc_attr($range['from']); ?>" />
                <input type="hidden" name="to_date" value="<?php echo esc_attr($range['to']); ?>" />
                <?php wp_nonce_field('mdai_generate_report'); ?>
                <?php submit_button(__('Generate Printable Report (PDF-ready)', 'markdownai-converter'), 'primary', '', false); ?>
            </form>

            <p class="description" style="margin-top: 8px; max-width: 880px;">
                <?php esc_html_e('The generated report opens in a print-optimized page. Use your browser\'s Print dialog and select “Save as PDF” to produce a client-ready file.', 'markdownai-converter'); ?>
            </p>
        </div>
        <?php
    }

    public static function generate_client_report(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to generate reports.', 'markdownai-converter'));
        }

        check_admin_referer('mdai_generate_report');

        $fromDate = isset($_POST['from_date']) ? sanitize_text_field(wp_unslash($_POST['from_date'])) : '';
        $toDate = isset($_POST['to_date']) ? sanitize_text_field(wp_unslash($_POST['to_date'])) : '';

        $report = Report::build_report_data($fromDate, $toDate);
        $trendSvg = self::build_trend_svg((array) ($report['trend'] ?? []));

        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');

        $siteName = (string) ($report['site_name'] ?? '');
        $siteUrl = (string) ($report['site_url'] ?? '');
        $rangeFrom = (string) ($report['range']['from'] ?? '');
        $rangeTo = (string) ($report['range']['to'] ?? '');
        $generatedAt = (string) ($report['generated_at'] ?? '');
        $kpis = (array) ($report['kpis'] ?? []);
        $topPages = (array) ($report['top_pages'] ?? []);
        $topIssues = (array) ($report['top_issues'] ?? []);

        echo '<!doctype html><html><head><meta charset="utf-8" />';
        echo '<title>' . esc_html__('MarkdownAI Converter Report', 'markdownai-converter') . '</title>';
        echo '<style>
            body{font-family:Arial,Helvetica,sans-serif;color:#1d2327;margin:20px;}
            h1,h2,h3{margin:0 0 10px 0;}
            .meta{margin-bottom:16px;font-size:13px;color:#50575e;}
            .card-wrap{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0;}
            .card{border:1px solid #c3c4c7;border-radius:6px;padding:10px;min-width:180px;background:#fff;}
            .label{font-size:12px;color:#50575e;}
            .value{font-size:20px;font-weight:700;}
            table{border-collapse:collapse;width:100%;margin-top:10px;}
            th,td{border:1px solid #dcdcde;padding:8px;font-size:13px;text-align:left;}
            th{background:#f6f7f7;}
            .chart{border:1px solid #dcdcde;padding:8px;background:#fff;display:inline-block;}
            @media print{.no-print{display:none;} body{margin:12px;}}
        </style>';
        echo '</head><body>';
        echo '<p class="no-print"><button onclick="window.print();">' . esc_html__('Print / Save as PDF', 'markdownai-converter') . '</button></p>';
        echo '<h1>' . esc_html__('MarkdownAI Performance Report', 'markdownai-converter') . '</h1>';
        echo '<div class="meta">';
        echo '<div><strong>' . esc_html__('Site:', 'markdownai-converter') . '</strong> ' . esc_html($siteName) . ' (' . esc_html($siteUrl) . ')</div>';
        echo '<div><strong>' . esc_html__('Period:', 'markdownai-converter') . '</strong> ' . esc_html($rangeFrom) . ' → ' . esc_html($rangeTo) . '</div>';
        echo '<div><strong>' . esc_html__('Generated:', 'markdownai-converter') . '</strong> ' . esc_html($generatedAt) . '</div>';
        echo '</div>';

        echo '<h2>' . esc_html__('Key Metrics', 'markdownai-converter') . '</h2>';
        echo '<div class="card-wrap">';
        echo '<div class="card"><div class="label">' . esc_html__('Total Bot Hits', 'markdownai-converter') . '</div><div class="value">' . esc_html(number_format_i18n((int) ($kpis['total_hits'] ?? 0))) . '</div></div>';
        echo '<div class="card"><div class="label">' . esc_html__('Unique Bot Families', 'markdownai-converter') . '</div><div class="value">' . esc_html(number_format_i18n((int) ($kpis['unique_bot_families'] ?? 0))) . '</div></div>';
        echo '<div class="card"><div class="label">' . esc_html__('Unique Crawled Pages', 'markdownai-converter') . '</div><div class="value">' . esc_html(number_format_i18n((int) ($kpis['unique_posts'] ?? 0))) . '</div></div>';
        echo '<div class="card"><div class="label">' . esc_html__('Avg Latency (ms)', 'markdownai-converter') . '</div><div class="value">' . esc_html(number_format_i18n((int) ($kpis['avg_latency_ms'] ?? 0))) . '</div></div>';
        echo '</div>';

        echo '<h2>' . esc_html__('Daily Crawl Trend', 'markdownai-converter') . '</h2>';
        if ($trendSvg !== '') {
            echo '<div class="chart">' . wp_kses($trendSvg, [
                'svg' => ['viewBox' => true, 'width' => true, 'height' => true, 'xmlns' => true],
                'polyline' => ['fill' => true, 'stroke' => true, 'stroke-width' => true, 'points' => true],
                'line' => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true],
                'text' => ['x' => true, 'y' => true, 'font-size' => true, 'fill' => true],
                'circle' => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true],
            ]) . '</div>';
        } else {
            echo '<p>' . esc_html__('No trend data available for this period.', 'markdownai-converter') . '</p>';
        }

        echo '<h2>' . esc_html__('Top Crawled Pages', 'markdownai-converter') . '</h2>';
        echo '<table><thead><tr><th>' . esc_html__('Post ID', 'markdownai-converter') . '</th><th>' . esc_html__('Title', 'markdownai-converter') . '</th><th>' . esc_html__('Hits', 'markdownai-converter') . '</th><th>' . esc_html__('Bot Families', 'markdownai-converter') . '</th></tr></thead><tbody>';
        if ($topPages === []) {
            echo '<tr><td colspan="4">' . esc_html__('No page activity in selected period.', 'markdownai-converter') . '</td></tr>';
        } else {
            foreach ($topPages as $row) {
                echo '<tr>';
                echo '<td>' . esc_html((string) ($row['post_id'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) (($row['post_title'] ?? '') !== '' ? $row['post_title'] : __('(no title)', 'markdownai-converter'))) . '</td>';
                echo '<td>' . esc_html(number_format_i18n((int) ($row['hits'] ?? 0))) . '</td>';
                echo '<td>' . esc_html(number_format_i18n((int) ($row['bot_families'] ?? 0))) . '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';

        echo '<h2>' . esc_html__('Top Content Opportunities', 'markdownai-converter') . '</h2>';
        echo '<table><thead><tr><th>' . esc_html__('Issue', 'markdownai-converter') . '</th><th>' . esc_html__('Affected Pages', 'markdownai-converter') . '</th></tr></thead><tbody>';
        foreach ($topIssues as $issueKey => $count) {
            if ((int) $count <= 0) {
                continue;
            }
            $label = match ($issueKey) {
                'thin_content' => __('Thin content', 'markdownai-converter'),
                'long_paragraphs' => __('Long paragraphs', 'markdownai-converter'),
                'missing_alt' => __('Images missing alt text', 'markdownai-converter'),
                'low_internal_links' => __('Low internal linking', 'markdownai-converter'),
                'no_faq' => __('No FAQ section', 'markdownai-converter'),
                default => (string) $issueKey,
            };
            echo '<tr><td>' . esc_html($label) . '</td><td>' . esc_html(number_format_i18n((int) $count)) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '</body></html>';
        exit;
    }

    public static function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'markdownai-converter'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('MarkdownAI Converter Settings', 'markdownai-converter'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('mdai_settings_group');
                do_settings_sections('mdai-settings');
                submit_button(__('Save Settings', 'markdownai-converter'));
                ?>
            </form>
            <hr />
            <p>
                <strong><?php esc_html_e('Plugin Version:', 'markdownai-converter'); ?></strong>
                <?php echo esc_html(MDAI_PLUGIN_VERSION); ?>
            </p>
            <p>
                <strong><?php esc_html_e('DB Schema Version:', 'markdownai-converter'); ?></strong>
                <?php echo esc_html((string) get_option(Plugin::OPTION_DB_VERSION, '0')); ?>
            </p>
        </div>
        <?php
    }

    private static function render_page_shell(string $title, string $description): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'markdownai-converter'));
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html($title); ?></h1>
            <p><?php echo esc_html($description); ?></p>
        </div>
        <?php
    }

    private static function build_trend_svg(array $trend): string
    {
        if ($trend === []) {
            return '';
        }

        $width = 880;
        $height = 240;
        $paddingLeft = 42;
        $paddingRight = 16;
        $paddingTop = 16;
        $paddingBottom = 30;

        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;

        $maxHits = 1;
        foreach ($trend as $point) {
            $maxHits = max($maxHits, (int) ($point['hits'] ?? 0));
        }

        $count = count($trend);
        $stepX = $count > 1 ? $plotWidth / ($count - 1) : 0;

        $points = [];
        $dots = [];
        foreach ($trend as $index => $point) {
            $hits = (int) ($point['hits'] ?? 0);
            $x = $paddingLeft + ($index * $stepX);
            $y = $paddingTop + ($plotHeight - (($hits / $maxHits) * $plotHeight));

            $points[] = round($x, 2) . ',' . round($y, 2);
            $dots[] = '<circle cx="' . esc_attr((string) round($x, 2)) . '" cy="' . esc_attr((string) round($y, 2)) . '" r="2.5" fill="#2271b1" />';
        }

        $firstDay = esc_html((string) ($trend[0]['day'] ?? ''));
        $lastDay = esc_html((string) ($trend[$count - 1]['day'] ?? ''));

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d">'
                . '<line x1="%3$d" y1="%4$d" x2="%5$d" y2="%4$d" stroke="#dcdcde" stroke-width="1" />'
                . '<line x1="%3$d" y1="%6$d" x2="%5$d" y2="%6$d" stroke="#dcdcde" stroke-width="1" />'
                . '<line x1="%3$d" y1="%4$d" x2="%3$d" y2="%6$d" stroke="#dcdcde" stroke-width="1" />'
                . '<polyline fill="none" stroke="#2271b1" stroke-width="2" points="%7$s" />'
                . '%8$s'
                . '<text x="%3$d" y="%9$d" font-size="11" fill="#50575e">%10$s</text>'
                . '<text x="%11$d" y="%9$d" font-size="11" fill="#50575e">%12$s</text>'
                . '<text x="8" y="%4$d" font-size="11" fill="#50575e">%13$d</text>'
                . '<text x="8" y="%6$d" font-size="11" fill="#50575e">0</text>'
            . '</svg>',
            $width,
            $height,
            $paddingLeft,
            $paddingTop,
            $width - $paddingRight,
            $height - $paddingBottom,
            esc_attr(implode(' ', $points)),
            implode('', $dots),
            $height - 8,
            $firstDay,
            $width - 90,
            $lastDay,
            $maxHits
        );
    }
}
