<?php
/*
 * Plugin Name:             WooCommerce RankMath Sync
 * Plugin URI:              https://github.com/MrGKanev/wo-rank-math-automation/
 * Description:             Copies WooCommerce product and category information to RankMath's meta information.
 * Version:                 0.0.3
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

// Admin Page Content
function wrms_admin_page()
{
    $auto_sync = get_option('wrms_auto_sync', '0');
    $stats = wrms_get_stats();
?>
    <div class="wrap wrms-admin-page">
        <h1>WooCommerce RankMath Sync</h1>

        <div class="wrms-tabs">
            <button class="wrms-tab-link active" data-tab="sync">Sync</button>
            <button class="wrms-tab-link" data-tab="url-download">URL Download</button>
            <button class="wrms-tab-link" data-tab="settings">Settings</button>
        </div>

        <div class="wrms-tab-content">
            <div id="sync" class="wrms-tab-pane active">
                <h2>Sync or Remove RankMath Meta</h2>
                <div class="sync-buttons">
                    <button id="sync-products" class="button button-primary">Sync Products</button>
                    <button id="sync-categories" class="button button-primary">Sync Categories</button>
                </div>
                <div class="remove-buttons">
                    <button id="remove-product-meta" class="button button-secondary">Remove Product Meta</button>
                    <button id="remove-category-meta" class="button button-secondary">Remove Category Meta</button>
                </div>
                <div id="sync-status" class="wrms-status-box">
                    <img id="sync-loader" src="<?php echo admin_url('images/spinner.gif'); ?>" style="display:none;" />
                    <p id="sync-count"></p>
                    <div id="sync-log" class="sync-log"></div>
                    <div id="progress-bar">
                        <div id="progress-bar-fill"></div>
                    </div>
                </div>
            </div>

            <div id="url-download" class="wrms-tab-pane">
                <h2>Download WordPress URLs</h2>
                <p>Select the types of URLs you want to download. URLs will be downloaded in chunks of 2000.</p>
                <form id="url-download-form">
                    <label><input type="checkbox" name="url_types[]" value="product" checked> Products</label>
                    <label><input type="checkbox" name="url_types[]" value="page"> Pages</label>
                    <label><input type="checkbox" name="url_types[]" value="category"> Categories</label>
                    <label><input type="checkbox" name="url_types[]" value="tag"> Tags</label>
                    <button id="download-urls" class="button button-primary">Download URLs</button>
                </form>
                <div id="download-status" class="wrms-status-box"></div>
            </div>

            <div id="settings" class="wrms-tab-pane">
                <h2>Plugin Settings</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('wrms_options_group'); ?>
                    <label for="wrms_auto_sync">
                        <input type="checkbox" id="wrms_auto_sync" name="wrms_auto_sync" value="1" <?php checked($auto_sync, '1'); ?> />
                        Automatically sync product and category information to RankMath
                    </label>
                    <?php submit_button('Save Settings'); ?>
                </form>
            </div>
        </div>

        <div class="wrms-sidebar">
            <div class="wrms-stats-box">
                <h2>Plugin Statistics</h2>
                <p>Total Products: <span id="total-products"><?php echo $stats['total_products']; ?></span></p>
                <p>Synced Products: <span id="synced-products"><?php echo $stats['synced_count']; ?></span></p>
                <p>Unsynced Products: <span id="unsynced-products"><?php echo $stats['unsynced_count']; ?></span></p>
                <p>Sync Percentage: <span id="sync-percentage"><?php echo $stats['sync_percentage']; ?>%</span></p>
                <p>Last Updated: <span id="last-updated"><?php echo $stats['last_updated']; ?></span></p>
                <button id="update-stats" class="button button-secondary">Update Statistics</button>
            </div>
        </div>
    </div>
<?php
}

// Function to calculate and cache statistics
function wrms_calculate_and_cache_stats()
{
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

    $stats = array(
        'total_products' => $total_products,
        'synced_count' => $synced_count,
        'unsynced_count' => $unsynced_count,
        'sync_percentage' => $sync_percentage,
        'last_updated' => current_time('mysql')
    );

    update_option('wrms_stats_cache', $stats);

    return $stats;
}

// Function to get cached stats or calculate if not available
function wrms_get_stats()
{
    $stats = get_option('wrms_stats_cache');
    if (!$stats) {
        $stats = wrms_calculate_and_cache_stats();
    }
    return $stats;
}

// Enqueue scripts and styles
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
        update_post_meta($post_id, '_wrms_synced', 1);
    }
}

// Hook to save category
add_action('edited_product_cat', 'wrms_maybe_sync_category', 10, 2);
function wrms_maybe_sync_category($term_id, $tt_id)
{
    if (get_option('wrms_auto_sync', '0') !== '1') {
        return;
    }

    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        $term = get_term($term_id, 'product_cat');
        if (!$term) return;

        $title = $term->name;
        $description = $term->description;
        $seo_description = wp_trim_words($description, 30, '...');

        if (!get_term_meta($term_id, 'rank_math_title', true)) {
            update_term_meta($term_id, 'rank_math_title', $title);
        }
        if (!get_term_meta($term_id, 'rank_math_description', true)) {
            update_term_meta($term_id, 'rank_math_description', $seo_description);
        }
        if (!get_term_meta($term_id, 'rank_math_focus_keyword', true)) {
            update_term_meta($term_id, 'rank_math_focus_keyword', $title);
        }
        update_term_meta($term_id, '_wrms_synced', 1);
    }
}

// Ensure the function to check plugin is active is loaded
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
