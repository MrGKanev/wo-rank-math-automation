<?php
/*
 * Plugin Name:             WooCommerce RankMath Sync
 * Plugin URI:              https://github.com/MrGKanev/StageGuard/
 * Description:             Copies WooCommerce product information to RankMath's meta information.
 * Version:                 0.0.1
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

// Add Admin Menu
add_action('admin_menu', 'wrms_add_admin_menu');
function wrms_add_admin_menu() {
    add_submenu_page(
        'tools.php', // Parent slug
        'WooCommerce RankMath Sync', // Page title
        'RankMath Sync', // Menu title
        'manage_options', // Capability
        'woocommerce-rankmath-sync', // Menu slug
        'wrms_admin_page' // Callback function
    );
}

// Admin Page Content
function wrms_admin_page() {
    ?>
    <div class="wrap">
        <h1>WooCommerce RankMath Sync</h1>
        <button id="sync-products" class="button button-primary" style="margin-right: 10px; margin-bottom: 20px;">Sync Products</button>
        <button id="remove-rankmath-meta" class="button button-secondary" style="margin-bottom: 20px;">Remove RankMath Meta</button>
        <div id="sync-status" style="margin-top: 20px;">
            <img id="sync-loader" src="<?php echo admin_url('images/spinner.gif'); ?>" style="display:none; margin-right: 10px;"/>
            <p id="sync-count"></p>
            <div id="sync-log" style="margin-top: 20px; height: 200px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px;"></div>
        </div>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#sync-products').on('click', function() {
            syncProducts();
        });

        $('#remove-rankmath-meta').on('click', function() {
            removeMeta();
        });

        function syncProducts() {
            var totalProducts = 0;
            var processedProducts = 0;

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wrms_get_product_count'
                },
                success: function(response) {
                    totalProducts = response.data.count;
                    $('#sync-count').text('Processing 0 of ' + totalProducts + ' products');
                    $('#sync-loader').show();
                    $('#sync-log').html(''); // Clear log area

                    processNextProduct();
                }
            });

            function processNextProduct() {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'wrms_sync_next_product'
                    },
                    success: function(response) {
                        if (response.success && response.data.processed > 0) {
                            processedProducts += response.data.processed;
                            $('#sync-count').text('Processing ' + processedProducts + ' of ' + totalProducts + ' products');

                            // Update log area
                            $('#sync-log').append('<p>Processed product ' + processedProducts + ': ' + response.data.product.title + ' (ID: ' + response.data.product.id + ')</p>');

                            if (processedProducts < totalProducts) {
                                processNextProduct();
                            } else {
                                $('#sync-loader').hide();
                                $('#sync-status').append('<p>Products synced successfully!</p>');
                            }
                        } else {
                            $('#sync-loader').hide();
                            $('#sync-status').append('<p>All products are already synced or an error occurred.</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>An error occurred.</p>');
                    }
                });
            }
        }

        function removeMeta() {
            var totalProducts = 0;
            var processedProducts = 0;

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wrms_get_product_count'
                },
                success: function(response) {
                    totalProducts = response.data.count;
                    $('#sync-count').text('Processing 0 of ' + totalProducts + ' products');
                    $('#sync-loader').show();
                    $('#sync-log').html(''); // Clear log area

                    processNextProduct();
                }
            });

            function processNextProduct() {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'wrms_remove_next_product'
                    },
                    success: function(response) {
                        if (response.success && response.data.processed > 0) {
                            processedProducts += response.data.processed;
                            $('#sync-count').text('Processing ' + processedProducts + ' of ' + totalProducts + ' products');

                            // Update log area
                            $('#sync-log').append('<p>Removed meta from product ' + processedProducts + ': ' + response.data.product.id + '</p>');

                            if (processedProducts < totalProducts) {
                                processNextProduct();
                            } else {
                                $('#sync-loader').hide();
                                $('#sync-status').append('<p>RankMath meta information removed from all products!</p>');
                            }
                        } else {
                            $('#sync-loader').hide();
                            $('#sync-status').append('<p>All products have already had their meta removed or an error occurred.</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#sync-loader').hide();
                        $('#sync-status').append('<p>An error occurred.</p>');
                    }
                });
            }
        }
    });
    </script>
    <?php
}

// Register AJAX actions
add_action('wp_ajax_wrms_sync_next_product', 'wrms_sync_next_product');
add_action('wp_ajax_wrms_remove_next_product', 'wrms_remove_next_product');
add_action('wp_ajax_wrms_get_product_count', 'wrms_get_product_count');

function wrms_sync_next_product() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'NOT EXISTS'
            )
        )
    );
    $products = get_posts($args);

    if (empty($products)) {
        wp_send_json_success(array('processed' => 0));
        return;
    }

    $product_id = $products[0];
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
        update_post_meta($product_id, '_wrms_synced', 1); // Mark as synced
        wp_send_json_success(array('processed' => 1, 'product' => array('id' => $product_id, 'title' => $title)));
    } else {
        wp_send_json_error(array('processed' => 0));
    }
}

function wrms_remove_next_product() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    );
    $products = get_posts($args);

    if (empty($products)) {
        wp_send_json_success(array('processed' => 0));
        return;
    }

    $product_id = $products[0];
    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        delete_post_meta($product_id, 'rank_math_title');
        delete_post_meta($product_id, 'rank_math_description');
        delete_post_meta($product_id, 'rank_math_focus_keyword');
        delete_post_meta($product_id, '_wrms_synced'); // Unmark as synced
        wp_send_json_success(array('processed' => 1, 'product' => array('id' => $product_id)));
    } else {
        wp_send_json_error(array('processed' => 0));
    }
}

function wrms_get_product_count()
{
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    $products = get_posts($args);

    error_log('wrms_get_product_count: count: ' . count($products)); // Debug information

    wp_send_json_success(array('count' => count($products)));
}

// Ensure the function to check plugin is active is loaded
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
?>