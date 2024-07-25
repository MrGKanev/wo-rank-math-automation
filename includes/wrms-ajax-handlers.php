<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_wrms_sync_next_product', 'wrms_sync_next_product');
add_action('wp_ajax_wrms_remove_next_product', 'wrms_remove_next_product');
add_action('wp_ajax_wrms_get_product_count', 'wrms_get_product_count');
add_action('wp_ajax_wrms_update_auto_sync', 'wrms_update_auto_sync');

function wrms_sync_next_product()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

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
    if (!$product_obj) {
        wp_send_json_error(array('message' => 'Product not found.'));
        return;
    }

    $title = $product_obj->get_name();
    $description = $product_obj->get_description();
    $short_description = $product_obj->get_short_description();
    $seo_description = $short_description ? $short_description : wp_trim_words($description, 30, '...');

    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        if (!get_post_meta($product_id, 'rank_math_title', true)) {
            update_post_meta($product_id, 'rank_math_title', $title);
        }
        if (!get_post_meta($product_id, 'rank_math_description', true)) {
            update_post_meta($product_id, 'rank_math_description', $seo_description);
        }
        if (!get_post_meta($product_id, 'rank_math_focus_keyword', true)) {
            update_post_meta($product_id, 'rank_math_focus_keyword', $title);
        }
        update_post_meta($product_id, '_wrms_synced', 1);
        wp_send_json_success(array('processed' => 1, 'product' => array('id' => $product_id, 'title' => $title)));
    } else {
        wp_send_json_error(array('message' => 'RankMath SEO plugin is not active.'));
    }
}

function wrms_remove_next_product()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

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
        delete_post_meta($product_id, '_wrms_synced');
        wp_send_json_success(array('processed' => 1, 'product' => array('id' => $product_id)));
    } else {
        wp_send_json_error(array('message' => 'RankMath SEO plugin is not active.'));
    }
}

function wrms_get_product_count()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    $products = get_posts($args);

    if (is_wp_error($products)) {
        wp_send_json_error(array('message' => 'Error retrieving products.'));
    } else {
        wp_send_json_success(array('count' => count($products)));
    }
}

function wrms_update_auto_sync()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $auto_sync = isset($_POST['auto_sync']) ? sanitize_text_field($_POST['auto_sync']) : '0';
    update_option('wrms_auto_sync', $auto_sync);

    wp_send_json_success(array('message' => 'Auto-sync setting updated successfully.'));
}

// Ensure the function to check plugin is active is loaded
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
