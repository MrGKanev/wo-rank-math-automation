<?php
/*
Plugin Name: WooCommerce RankMath Sync
Description: Copies WooCommerce product information to RankMath's meta information.
Version: 1.1
Author: Your Name
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Add Admin Menu
add_action('admin_menu', 'wrms_add_admin_menu');
function wrms_add_admin_menu()
{
    add_menu_page(
        'WooCommerce RankMath Sync', // Page title
        'RankMath Sync', // Menu title
        'manage_options', // Capability
        'woocommerce-rankmath-sync', // Menu slug
        'wrms_admin_page', // Callback function
        'dashicons-update', // Icon
        20 // Position
    );
}

// Admin Page Content
function wrms_admin_page()
{
?>
    <div class="wrap">
        <h1>WooCommerce RankMath Sync</h1>
        <button id="sync-products" class="button button-primary">Sync Products</button>
        <button id="remove-rankmath-meta" class="button button-secondary">Remove RankMath Meta</button>
        <div id="sync-status">
            <img id="sync-loader" src="<?php echo admin_url('images/spinner.gif'); ?>" style="display:none;" />
        </div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#sync-products').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'wrms_bulk_sync'
                    },
                    beforeSend: function() {
                        $('#sync-loader').show();
                        $('#sync-status').html('Syncing products...');
                    },
                    success: function(response) {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>' + response.data.message + '</p>');
                    },
                    error: function(response) {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>An error occurred.</p>');
                    }
                });
            });

            $('#remove-rankmath-meta').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'wrms_bulk_remove'
                    },
                    beforeSend: function() {
                        $('#sync-loader').show();
                        $('#sync-status').html('Removing meta information...');
                    },
                    success: function(response) {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>' + response.data.message + '</p>');
                    },
                    error: function(response) {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>An error occurred.</p>');
                    }
                });
            });
        });
    </script>
<?php
}

// Register AJAX actions
add_action('wp_ajax_wrms_bulk_sync', 'wrms_bulk_sync');
add_action('wp_ajax_wrms_bulk_remove', 'wrms_bulk_remove');

function wrms_bulk_sync()
{
    // Get all products
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1
    );
    $products = get_posts($args);

    foreach ($products as $product) {
        $product_id = $product->ID;
        $product_obj = wc_get_product($product_id);
        $title = $product_obj->get_name();
        $description = $product_obj->get_description();
        $short_description = $product_obj->get_short_description();
        $seo_description = $short_description ? $short_description : wp_trim_words($description, 30, '...');

        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($product_id, 'rank_math_title', $title);
            update_post_meta($product_id, 'rank_math_description', $description);
            update_post_meta($product_id, 'rank_math_focus_keyword', $title); // Using product title as focus keyword
            update_post_meta($product_id, 'rank_math_description', $seo_description); // Adding SEO meta description
        }
    }

    wp_send_json_success(array('message' => 'Products synced successfully!'));
}

function wrms_bulk_remove()
{
    // Get all products
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1
    );
    $products = get_posts($args);

    foreach ($products as $product) {
        $product_id = $product->ID;

        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            delete_post_meta($product_id, 'rank_math_title');
            delete_post_meta($product_id, 'rank_math_description');
            delete_post_meta($product_id, 'rank_math_focus_keyword');
        }
    }

    wp_send_json_success(array('message' => 'RankMath meta information removed from all products!'));
}

// Ensure the function to check plugin is active is loaded
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
