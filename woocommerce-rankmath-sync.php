<?php
/*
 * Plugin Name:             WooCommerce RankMath Sync
 * Plugin URI:              https://github.com/MrGKanev/wo-rank-math-automation/
 * Description:             Copies WooCommerce product information to RankMath's meta information.
 * Version:                 0.0.2
 * Author:                  Gabriel Kanev
 * Author URI:              https://gkanev.com
 * License:                 MIT
 * Requires at least:       6.0
 * Requires PHP:            7.4
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Include AJAX handlers
require_once plugin_dir_path(__FILE__) . 'includes/wrms-ajax-handlers.php';

// Add Admin Menu
add_action('admin_menu', 'wrms_add_admin_menu');
function wrms_add_admin_menu()
{
    add_submenu_page(
        'tools.php', // Parent slug
        'WooCommerce RankMath Sync', // Page title
        'RankMath Sync', // Menu title
        'manage_options', // Capability
        'woocommerce-rankmath-sync', // Menu slug
        'wrms_admin_page' // Callback function
    );
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'wrms_enqueue_scripts');
function wrms_enqueue_scripts($hook)
{
    if ($hook != 'tools_page_woocommerce-rankmath-sync') {
        return;
    }
    wp_enqueue_script('wrms-script', plugin_dir_url(__FILE__) . 'js/wrms-script.js', array('jquery'), null, true);
    wp_enqueue_style('wrms-style', plugin_dir_url(__FILE__) . 'css/wrms-style.css');
    wp_localize_script('wrms-script', 'wrms_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wrms_nonce')
    ));
}

// Admin Page Content
function wrms_admin_page()
{
    $auto_sync = get_option('wrms_auto_sync', '0');

    // Fetch statistics
    $total_products = wp_count_posts('product')->publish;
    $synced_products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    ));
    $synced_count = count($synced_products);
    $unsynced_count = $total_products - $synced_count;
    $sync_percentage = $total_products > 0 ? round(($synced_count / $total_products) * 100, 2) : 0;

?>
    <div class="wrap">
        <h1>WooCommerce RankMath Sync</h1>

        <div class="wrms-stats-box">
            <h2>Plugin Statistics</h2>
            <p>Total Products: <?php echo $total_products; ?></p>
            <p>Synced Products: <?php echo $synced_count; ?></p>
            <p>Unsynced Products: <?php echo $unsynced_count; ?></p>
            <p>Sync Percentage: <?php echo $sync_percentage; ?>%</p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('wrms_options_group'); ?>
            <label for="wrms_auto_sync">
                <input type="checkbox" id="wrms_auto_sync" name="wrms_auto_sync" value="1" <?php checked($auto_sync, '1'); ?> />
                Automatically sync product information to RankMath
            </label>
            <?php submit_button('Save Settings'); ?>
        </form>
        <button id="sync-products" class="button button-primary" style="margin-right: 10px; margin-bottom: 20px;">Sync Products</button>
        <button id="remove-rankmath-meta" class="button button-secondary" style="margin-bottom: 20px;">Remove RankMath Meta</button>
        <div id="sync-status" style="margin-top: 20px;">
            <img id="sync-loader" src="<?php echo admin_url('images/spinner.gif'); ?>" style="display:none; margin-right: 10px;" />
            <p id="sync-count"></p>
            <div id="sync-log" class="sync-log"></div>
            <div id="progress-bar" style="margin-top: 20px; height: 20px; width: 100%; background-color: #ccc;">
                <div id="progress-bar-fill" style="height: 100%; width: 0%; background-color: #4caf50;"></div>
            </div>
        </div>
    </div>
<?php
}

// Register settings
add_action('admin_init', 'wrms_register_settings');
function wrms_register_settings()
{
    register_setting('wrms_options_group', 'wrms_auto_sync');
}

// Hook to save product
add_action('save_post_product', 'wrms_maybe_sync_product', 10, 3);
function wrms_maybe_sync_product($post_id, $post, $update)
{
    if (get_option('wrms_auto_sync', '0') !== '1') {
        return;
    }

    // Your existing sync logic here
    // Make sure to check if RankMath is active and only update if fields are empty
    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        $product = wc_get_product($post_id);
        if (!$product) return;

        $title = $product->get_name();
        $description = $product->get_description();
        $short_description = $product->get_short_description();
        $seo_description = $short_description ? $short_description : wp_trim_words($description, 30, '...');

        if (!get_post_meta($post_id, 'rank_math_title', true)) {
            update_post_meta($post_id, 'rank_math_title', $title);
        }
        if (!get_post_meta($post_id, 'rank_math_description', true)) {
            update_post_meta($post_id, 'rank_math_description', $seo_description);
        }
        if (!get_post_meta($post_id, 'rank_math_focus_keyword', true)) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $title);
        }
    }
}

// Ensure the function to check plugin is active is loaded
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
