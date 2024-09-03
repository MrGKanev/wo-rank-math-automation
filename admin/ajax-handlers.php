<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Handler for updating auto sync setting
add_action('wp_ajax_wrms_update_auto_sync', 'wrms_update_auto_sync_handler');
function wrms_update_auto_sync_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $auto_sync = isset($_POST['auto_sync']) ? sanitize_text_field($_POST['auto_sync']) : '0';
    update_option('wrms_auto_sync', $auto_sync);
    wp_send_json_success();
}

// Handler for updating statistics
add_action('wp_ajax_wrms_update_stats', 'wrms_update_stats_handler');
function wrms_update_stats_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $stats = wrms_calculate_and_cache_stats();
    $stats['timestamp'] = time();
    wp_send_json_success($stats);
}

// Handler for getting product count
add_action('wp_ajax_wrms_get_product_count', 'wrms_get_product_count_handler');
function wrms_get_product_count_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $count = wp_count_posts('product')->publish;
    wp_send_json_success(array('count' => $count));
}

// Handler for syncing next product
function wrms_sync_next_product_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_wrms_synced',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_wrms_synced',
                'value' => '0',
                'compare' => '='
            )
        ),
        'fields' => 'ids'
    );

    $products = get_posts($args);

    if (!empty($products)) {
        $product_id = $products[0];
        $product = wc_get_product($product_id);

        if ($product) {
            wrms_maybe_sync_product($product_id, null, true);
            update_post_meta($product_id, '_wrms_synced', '1');

            wp_send_json_success(array(
                'processed' => 1,
                'product' => array(
                    'id' => $product_id,
                    'title' => $product->get_name()
                )
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to retrieve product.'));
        }
    } else {
        wp_send_json_success(array('processed' => 0));
    }
}
add_action('wp_ajax_wrms_sync_next_product', 'wrms_sync_next_product_handler');

// Handler for syncing categories
add_action('wp_ajax_wrms_sync_categories', 'wrms_sync_categories_handler');
function wrms_sync_categories_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
    $total = count($categories);
    $synced = 0;
    foreach ($categories as $category) {
        wrms_maybe_sync_category($category->term_id, $category->term_taxonomy_id);
        $synced++;
        wp_send_json_success(array(
            'processed' => 1,
            'category' => array(
                'id' => $category->term_id,
                'name' => $category->name
            ),
            'total' => $total,
            'synced' => $synced
        ));
    }
    wp_send_json_success(array('total' => $total, 'synced' => $synced));
}

// Handler for syncing pages
add_action('wp_ajax_wrms_sync_pages', 'wrms_sync_pages_handler');
function wrms_sync_pages_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $pages = get_posts(array('post_type' => 'page', 'posts_per_page' => -1));
    $total = count($pages);
    $synced = 0;
    foreach ($pages as $page) {
        wrms_maybe_sync_page($page->ID, $page, true);
        $synced++;
        wp_send_json_success(array(
            'processed' => 1,
            'page' => array(
                'id' => $page->ID,
                'title' => $page->post_title
            ),
            'total' => $total,
            'synced' => $synced
        ));
    }
    wp_send_json_success(array('total' => $total, 'synced' => $synced));
}

// Handler for syncing media
add_action('wp_ajax_wrms_sync_media', 'wrms_sync_media_handler');
function wrms_sync_media_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $args = array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'post_status' => 'inherit'
    );
    $attachments = get_posts($args);
    $total = count($attachments);
    $synced = 0;
    foreach ($attachments as $attachment) {
        wrms_maybe_sync_media($attachment->ID);
        $synced++;
        wp_send_json_success(array(
            'processed' => 1,
            'media' => array(
                'id' => $attachment->ID,
                'title' => $attachment->post_title
            ),
            'total' => $total,
            'synced' => $synced
        ));
    }
    wp_send_json_success(array('total' => $total, 'synced' => $synced));
}

// Handler for syncing posts
add_action('wp_ajax_wrms_sync_posts', 'wrms_sync_posts_handler');
function wrms_sync_posts_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $posts = get_posts(array('post_type' => 'post', 'posts_per_page' => -1));
    $total = count($posts);
    $synced = 0;
    foreach ($posts as $post) {
        wrms_maybe_sync_post($post->ID, $post, true);
        $synced++;
        wp_send_json_success(array(
            'processed' => 1,
            'post' => array(
                'id' => $post->ID,
                'title' => $post->post_title
            ),
            'total' => $total,
            'synced' => $synced
        ));
    }
    wp_send_json_success(array('total' => $total, 'synced' => $synced));
}

// Handler for removing product meta
add_action('wp_ajax_wrms_remove_product_meta', 'wrms_remove_product_meta_handler');
function wrms_remove_product_meta_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    );
    $products = get_posts($args);
    $total = count($products);
    $removed = 0;
    foreach ($products as $product) {
        delete_post_meta($product->ID, 'rank_math_title');
        delete_post_meta($product->ID, 'rank_math_description');
        delete_post_meta($product->ID, 'rank_math_focus_keyword');
        delete_post_meta($product->ID, '_wrms_synced');
        $removed++;
    }
    wp_send_json_success(array('total' => $total, 'removed' => $removed));
}

// Handler for removing category meta
add_action('wp_ajax_wrms_remove_category_meta', 'wrms_remove_category_meta_handler');
function wrms_remove_category_meta_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    ));
    $total = count($categories);
    $removed = 0;
    foreach ($categories as $category) {
        delete_term_meta($category->term_id, 'rank_math_title');
        delete_term_meta($category->term_id, 'rank_math_description');
        delete_term_meta($category->term_id, 'rank_math_focus_keyword');
        delete_term_meta($category->term_id, '_wrms_synced');
        $removed++;
    }
    wp_send_json_success(array('total' => $total, 'removed' => $removed));
}

// Handler for removing page meta
add_action('wp_ajax_wrms_remove_page_meta', 'wrms_remove_page_meta_handler');
function wrms_remove_page_meta_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $args = array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    );
    $pages = get_posts($args);
    $total = count($pages);
    $removed = 0;
    foreach ($pages as $page) {
        delete_post_meta($page->ID, 'rank_math_title');
        delete_post_meta($page->ID, 'rank_math_description');
        delete_post_meta($page->ID, 'rank_math_focus_keyword');
        delete_post_meta($page->ID, '_wrms_synced');
        $removed++;
    }
    wp_send_json_success(array('total' => $total, 'removed' => $removed));
}

// Handler for removing media meta
add_action('wp_ajax_wrms_remove_media_meta', 'wrms_remove_media_meta_handler');
function wrms_remove_media_meta_handler() {
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $args = array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'post_status' => 'inherit',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    );
    $attachments = get_posts($args);
    $total = count($attachments);
    $removed = 0;
    foreach ($attachments as $attachment) {
        delete_post_meta($attachment->ID, 'rank_math_title');
        delete_post_meta($attachment->ID, 'rank_math_description');
        delete_post_meta($attachment->ID, 'rank_math_focus_keyword');
        delete_post_meta($attachment->ID, '_wrms_synced');
        $removed++;
    }
    wp_send_json_success(array('total' => $total, 'removed' => $removed));
}

// Handler for removing post meta
add_action('wp_ajax_wrms_remove_post_meta', 'wrms_remove_post_meta_handler');
function wrms_remove_post_meta_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    );
    $posts = get_posts($args);
    $total = count($posts);
    $removed = 0;
    foreach ($posts as $post) {
        delete_post_meta($post->ID, 'rank_math_title');
        delete_post_meta($post->ID, 'rank_math_description');
        delete_post_meta($post->ID, 'rank_math_focus_keyword');
        delete_post_meta($post->ID, '_wrms_synced');
        $removed++;
    }
    wp_send_json_success(array('total' => $total, 'removed' => $removed));
}

// Handler for getting URLs
add_action('wp_ajax_wrms_get_urls', 'wrms_get_urls_handler');
function wrms_get_urls_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $chunk_size = isset($_POST['chunk_size']) ? intval($_POST['chunk_size']) : 100;
    $url_types = isset($_POST['url_types']) ? (array)$_POST['url_types'] : array();

    $urls = array();
    $total = 0;

    foreach ($url_types as $type) {
        switch ($type) {
            case 'product':
                $result = wrms_get_product_urls($offset, $chunk_size);
                $urls = array_merge($urls, $result['urls']);
                $total += $result['total'];
                break;
            case 'page':
                $result = wrms_get_page_urls($offset, $chunk_size);
                $urls = array_merge($urls, $result['urls']);
                $total += $result['total'];
                break;
            case 'category':
                $result = wrms_get_category_urls($offset, $chunk_size);
                $urls = array_merge($urls, $result['urls']);
                $total += $result['total'];
                break;
            case 'tag':
                $result = wrms_get_tag_urls($offset, $chunk_size);
                $urls = array_merge($urls, $result['urls']);
                $total += $result['total'];
                break;
            case 'post':
                $result = wrms_get_post_urls($offset, $chunk_size);
                $urls = array_merge($urls, $result['urls']);
                $total += $result['total'];
                break;
        }
    }

    wp_send_json_success(array('urls' => $urls, 'total' => $total));
}

// Handler for manual sync
add_action('wp_ajax_wrms_manual_sync', 'wrms_manual_sync_handler');
function wrms_manual_sync_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    wrms_manual_sync();
    wp_send_json_success(array('message' => 'Manual sync initiated successfully.'));
}

// Handler for generating sitemap
add_action('wp_ajax_wrms_generate_sitemap', 'wrms_generate_sitemap_handler');
function wrms_generate_sitemap_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    wrms_save_sitemap();
    wp_send_json_success(array('message' => 'Sitemap generated successfully.'));
}

// Handler for checking if a URL exists
add_action('wp_ajax_wrms_check_url', 'wrms_check_url_handler');
function wrms_check_url_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    if (empty($url)) {
        wp_send_json_error(array('message' => 'No URL provided.'));
    }

    $exists = wrms_url_exists($url);
    wp_send_json_success(array('exists' => $exists));
}

// Handler for getting last sync time
add_action('wp_ajax_wrms_get_last_sync_time', 'wrms_get_last_sync_time_handler');
function wrms_get_last_sync_time_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    $last_sync_time = wrms_get_last_sync_time();
    wp_send_json_success(array('last_sync_time' => $last_sync_time));
}

// Handler for updating last sync time
add_action('wp_ajax_wrms_update_last_sync_time', 'wrms_update_last_sync_time_handler');
function wrms_update_last_sync_time_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    wrms_update_last_sync_time();
    wp_send_json_success(array('message' => 'Last sync time updated successfully.'));
}

// Handler for getting sync progress
add_action('wp_ajax_wrms_get_sync_progress', 'wrms_get_sync_progress_handler');
function wrms_get_sync_progress_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    $stats = wrms_get_stats();
    $progress = array(
        'total_items' => $stats['total_items'],
        'total_synced' => $stats['total_synced'],
        'sync_percentage' => $stats['sync_percentage']
    );
    wp_send_json_success($progress);
}

// Handler for cancelling ongoing sync
add_action('wp_ajax_wrms_cancel_sync', 'wrms_cancel_sync_handler');
function wrms_cancel_sync_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    // Implement a method to cancel the ongoing sync process
    // This could involve setting a flag in the database that the sync process checks
    update_option('wrms_cancel_sync', true);
    wp_send_json_success(array('message' => 'Sync cancellation initiated.'));
}

// Handler for resetting plugin data
add_action('wp_ajax_wrms_reset_plugin', 'wrms_reset_plugin_handler');
function wrms_reset_plugin_handler()
{
    check_ajax_referer('wrms_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    // Implement a method to reset all plugin data
    // This could involve deleting all options and post meta related to the plugin
    delete_option('wrms_auto_sync');
    delete_option('wrms_settings');
    delete_option('wrms_stats_cache');
    delete_option('wrms_last_sync_time');

    // Remove all synced meta data
    global $wpdb;
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_wrms_synced'));
    $wpdb->delete($wpdb->termmeta, array('meta_key' => '_wrms_synced'));

    wp_send_json_success(array('message' => 'Plugin data reset successfully.'));
}