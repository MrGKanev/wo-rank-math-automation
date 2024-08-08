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
                <div class="wrms-button-grid">
                    <div class="wrms-button-group">
                        <h3>Products</h3>
                        <button id="sync-products" class="button button-primary">Sync Products</button>
                        <button id="remove-product-meta" class="button button-secondary">Remove Product Meta</button>
                    </div>
                    <div class="wrms-button-group">
                        <h3>Categories</h3>
                        <button id="sync-categories" class="button button-primary">Sync Categories</button>
                        <button id="remove-category-meta" class="button button-secondary">Remove Category Meta</button>
                    </div>
                    <div class="wrms-button-group">
                        <h3>Pages</h3>
                        <button id="sync-pages" class="button button-primary">Sync Pages</button>
                        <button id="remove-page-meta" class="button button-secondary">Remove Page Meta</button>
                    </div>
                    <div class="wrms-button-group">
                        <h3>Media</h3>
                        <button id="sync-media" class="button button-primary">Sync Media</button>
                        <button id="remove-media-meta" class="button button-secondary">Remove Media Meta</button>
                    </div>
                </div>
                <div id="sync-status" class="wrms-status-box">
                    <img id="sync-loader" src="<?php echo admin_url('images/spinner.gif'); ?>" style="display:none;" />
                    <p id="sync-count"></p>
                    <div id="sync-log" class="sync-log">
                        <?php echo esc_html__('Sync logs will appear here once you start a sync process.', 'woocommerce-rankmath-sync'); ?>
                    </div>
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
                <p>Synced Products: <span id="synced-products"><?php echo $stats['synced_products']; ?></span></p>
                <p>Total Pages: <span id="total-pages"><?php echo $stats['total_pages']; ?></span></p>
                <p>Synced Pages: <span id="synced-pages"><?php echo $stats['synced_pages']; ?></span></p>
                <p>Total Media: <span id="total-media"><?php echo $stats['total_media']; ?></span></p>
                <p>Synced Media: <span id="synced-media"><?php echo $stats['synced_media']; ?></span></p>
                <p>Total Categories: <span id="total-categories"><?php echo $stats['total_categories']; ?></span></p>
                <p>Synced Categories: <span id="synced-categories"><?php echo $stats['synced_categories']; ?></span></p>
                <p>Total Items: <span id="total-items"><?php echo $stats['total_items']; ?></span></p>
                <p>Total Synced: <span id="total-synced"><?php echo $stats['total_synced']; ?></span></p>
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
    $total_pages = wp_count_posts('page')->publish;
    $total_media = wp_count_posts('attachment')->inherit;
    $total_categories = wp_count_terms('product_cat');

    $synced_products = count(get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    )));

    $synced_pages = count(get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    )));

    $synced_media = count(get_posts(array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    )));

    $synced_categories = count(get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    )));

    $total_items = $total_products + $total_pages + $total_media + $total_categories;
    $total_synced = $synced_products + $synced_pages + $synced_media + $synced_categories;

    $sync_percentage = $total_items > 0 ? round(($total_synced / $total_items) * 100, 2) : 0;

    $stats = array(
        'total_products' => $total_products,
        'total_pages' => $total_pages,
        'total_media' => $total_media,
        'total_categories' => $total_categories,
        'synced_products' => $synced_products,
        'synced_pages' => $synced_pages,
        'synced_media' => $synced_media,
        'synced_categories' => $synced_categories,
        'total_items' => $total_items,
        'total_synced' => $total_synced,
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

// Hook to save page
add_action('save_post_page', 'wrms_maybe_sync_page', 10, 3);
function wrms_maybe_sync_page($post_id, $post, $update)
{
    if (get_option('wrms_auto_sync', '0') !== '1') {
        return;
    }

    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        $title = get_the_title($post_id);
        $content = get_post_field('post_content', $post_id);
        $excerpt = has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($content, 30, '...');

        if (!get_post_meta($post_id, 'rank_math_title', true)) {
            update_post_meta($post_id, 'rank_math_title', $title);
        }
        if (!get_post_meta($post_id, 'rank_math_description', true)) {
            update_post_meta($post_id, 'rank_math_description', $excerpt);
        }
        if (!get_post_meta($post_id, 'rank_math_focus_keyword', true)) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $title);
        }
        update_post_meta($post_id, '_wrms_synced', 1);
    }
}

// Hook to save media
add_action('add_attachment', 'wrms_maybe_sync_media');
add_action('edit_attachment', 'wrms_maybe_sync_media');
function wrms_maybe_sync_media($post_id)
{
    if (get_option('wrms_auto_sync', '0') !== '1') {
        return;
    }

    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        $title = get_the_title($post_id);
        $alt_text = get_post_meta($post_id, '_wp_attachment_image_alt', true);
        $description = wp_get_attachment_caption($post_id);

        if (!get_post_meta($post_id, 'rank_math_title', true)) {
            update_post_meta($post_id, 'rank_math_title', $title);
        }
        if (!get_post_meta($post_id, 'rank_math_description', true)) {
            update_post_meta($post_id, 'rank_math_description', $description ? $description : $alt_text);
        }
        if (!get_post_meta($post_id, 'rank_math_focus_keyword', true)) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $title);
        }
        update_post_meta($post_id, '_wrms_synced', 1);
    }
}

// Ensure the function to check plugin is active is loaded
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Add filter to modify RankMath meta fields
add_filter('rank_math/frontend/title', 'wrms_modify_rankmath_title', 10, 2);
add_filter('rank_math/frontend/description', 'wrms_modify_rankmath_description', 10, 2);
add_filter('rank_math/frontend/canonical', 'wrms_modify_rankmath_canonical', 10, 2);

function wrms_modify_rankmath_title($title, $object = null)
{
    if (is_product() && $object instanceof WP_Post) {
        $product = wc_get_product($object->ID);
        if ($product) {
            return $product->get_name();
        }
    }
    return $title;
}

function wrms_modify_rankmath_description($description, $object = null)
{
    if (is_product() && $object instanceof WP_Post) {
        $product = wc_get_product($object->ID);
        if ($product) {
            $short_description = $product->get_short_description();
            if (!empty($short_description)) {
                return wp_trim_words($short_description, 30, '...');
            } else {
                return wp_trim_words($product->get_description(), 30, '...');
            }
        }
    }
    return $description;
}

function wrms_modify_rankmath_canonical($canonical, $object = null)
{
    if (is_product() && $object instanceof WP_Post) {
        $product = wc_get_product($object->ID);
        if ($product) {
            return get_permalink($product->get_id());
        }
    }
    return $canonical;
}

// Add action to sync all products
add_action('wp_ajax_wrms_sync_all_products', 'wrms_sync_all_products');
function wrms_sync_all_products()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $products = wc_get_products(array('status' => 'publish', 'limit' => -1));
    $synced_count = 0;

    foreach ($products as $product) {
        wrms_maybe_sync_product($product->get_id(), null, true);
        $synced_count++;
    }

    wp_send_json_success(array('message' => sprintf('%d products synced successfully.', $synced_count)));
}

// Add action to sync all categories
add_action('wp_ajax_wrms_sync_all_categories', 'wrms_sync_all_categories');
function wrms_sync_all_categories()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
    $synced_count = 0;

    foreach ($categories as $category) {
        wrms_maybe_sync_category($category->term_id, $category->term_taxonomy_id);
        $synced_count++;
    }

    wp_send_json_success(array('message' => sprintf('%d categories synced successfully.', $synced_count)));
}

// Add action to sync all pages
add_action('wp_ajax_wrms_sync_all_pages', 'wrms_sync_all_pages');
function wrms_sync_all_pages()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $pages = get_pages();
    $synced_count = 0;

    foreach ($pages as $page) {
        wrms_maybe_sync_page($page->ID, $page, true);
        $synced_count++;
    }

    wp_send_json_success(array('message' => sprintf('%d pages synced successfully.', $synced_count)));
}

// Add action to sync all media
add_action('wp_ajax_wrms_sync_all_media', 'wrms_sync_all_media');
function wrms_sync_all_media()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $attachments = get_posts(array('post_type' => 'attachment', 'numberposts' => -1));
    $synced_count = 0;

    foreach ($attachments as $attachment) {
        wrms_maybe_sync_media($attachment->ID);
        $synced_count++;
    }

    wp_send_json_success(array('message' => sprintf('%d media items synced successfully.', $synced_count)));
}

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
function wrms_perform_daily_sync()
{
    if (get_option('wrms_auto_sync', '0') === '1') {
        wrms_sync_all_products();
        wrms_sync_all_categories();
        wrms_sync_all_pages();
        wrms_sync_all_media();
    }
}