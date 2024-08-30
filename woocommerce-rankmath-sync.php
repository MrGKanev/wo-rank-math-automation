<?php
/*
 * Plugin Name:             WooCommerce RankMath Sync
 * Plugin URI:              https://github.com/MrGKanev/wo-rank-math-automation/
 * Description:             Copies WooCommerce product, category, WordPress post, page, and media information to RankMath's meta information.
 * Version:                 0.0.4
 * Author:                  Gabriel Kanev
 * Author URI:              https://gkanev.com
 * License:                 GPL-2.0 License
 * Requires Plugins:        seo-by-rank-math
 * Requires at least:       6.0
 * Requires PHP:            7.4
 * Tested up to:            6.6.1
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Include other plugin files
require_once plugin_dir_path(__FILE__) . 'admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'sync-functions.php';
require_once plugin_dir_path(__FILE__) . 'ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'helpers.php';
require_once plugin_dir_path(__FILE__) . 'settings.php';
require_once plugin_dir_path(__FILE__) . 'rank-math-filters.php';
require_once plugin_dir_path(__FILE__) . 'cron-jobs.php';
require_once plugin_dir_path(__FILE__) . 'url-functions.php';

// Activation hook
register_activation_hook(__FILE__, 'wrms_activate');
function wrms_activate()
{
    // Set default options
    add_option('wrms_auto_sync', '0');

    // Schedule cron job for daily sync
    if (!wp_next_scheduled('wrms_daily_sync')) {
        wp_schedule_event(time(), 'daily', 'wrms_daily_sync');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wrms_deactivate');
function wrms_deactivate()
{
    // Clear scheduled cron job
    wp_clear_scheduled_hook('wrms_daily_sync');
}

// Add daily sync action
add_action('wrms_daily_sync', 'wrms_perform_daily_sync');

// Ensure the function to check plugin is active is loaded
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
