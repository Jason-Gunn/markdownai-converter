<?php
/**
 * Plugin Name: MarkdownAI Converter
 * Description: Converts site content to Markdown and provides AI crawler analytics for publishers.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: MarkdownAI Converter
 * Text Domain: markdownai-converter
 */

if (! defined('ABSPATH')) {
    exit;
}

define('MDAI_PLUGIN_VERSION', '0.1.0');
define('MDAI_PLUGIN_FILE', __FILE__);
define('MDAI_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MDAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MDAI_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once MDAI_PLUGIN_DIR . 'includes/class-mdai-plugin.php';

register_activation_hook(MDAI_PLUGIN_FILE, ['MDAI\\Plugin', 'activate']);
register_deactivation_hook(MDAI_PLUGIN_FILE, ['MDAI\\Plugin', 'deactivate']);

MDAI\Plugin::boot();
