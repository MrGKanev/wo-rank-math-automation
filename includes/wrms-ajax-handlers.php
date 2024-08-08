<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_wrms_sync_next_product', 'wrms_sync_next_product');
add_action('wp_ajax_wrms_remove_next_product', 'wrms_remove_next_product');
add_action('wp_ajax_wrms_get_product_count', 'wrms_get_product_count');
add_action('wp_ajax_wrms_update_auto_sync', 'wrms_update_auto_sync');
add_action('wp_ajax_wrms_sync_categories', 'wrms_sync_categories');
add_action('wp_ajax_wrms_get_urls', 'wrms_ajax_get_urls');

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

add_action('wp_ajax_wrms_remove_product_meta', 'wrms_remove_product_meta');
add_action('wp_ajax_wrms_remove_category_meta', 'wrms_remove_category_meta');

function wrms_remove_product_meta()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    );
    $products = get_posts($args);

    $removed_count = 0;

    foreach ($products as $product_id) {
        delete_post_meta($product_id, 'rank_math_title');
        delete_post_meta($product_id, 'rank_math_description');
        delete_post_meta($product_id, 'rank_math_focus_keyword');
        delete_post_meta($product_id, '_wrms_synced');
        $removed_count++;
    }

    wp_send_json_success(array('removed' => $removed_count, 'total' => count($products)));
}


add_action('wp_ajax_wrms_update_stats', 'wrms_ajax_update_stats');

function wrms_ajax_update_stats()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $stats = wrms_calculate_and_cache_stats();
    wp_send_json_success($stats);
}

function wrms_remove_category_meta()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));

    $removed_count = 0;

    foreach ($categories as $category) {
        delete_term_meta($category->term_id, 'rank_math_title');
        delete_term_meta($category->term_id, 'rank_math_description');
        delete_term_meta($category->term_id, 'rank_math_focus_keyword');
        delete_term_meta($category->term_id, '_wrms_synced');
        $removed_count++;
    }

    wp_send_json_success(array('removed' => $removed_count, 'total' => count($categories)));
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

function wrms_sync_categories()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        $total_categories = count($categories);
        $synced_count = 0;

        foreach ($categories as $category) {
            $title = $category->name;
            $description = $category->description;
            $seo_description = wp_trim_words($description, 30, '...');

            $meta_updated = false;

            if (!get_term_meta($category->term_id, 'rank_math_title', true)) {
                update_term_meta($category->term_id, 'rank_math_title', $title);
                $meta_updated = true;
            }
            if (!get_term_meta($category->term_id, 'rank_math_description', true)) {
                update_term_meta($category->term_id, 'rank_math_description', $seo_description);
                $meta_updated = true;
            }
            if (!get_term_meta($category->term_id, 'rank_math_focus_keyword', true)) {
                update_term_meta($category->term_id, 'rank_math_focus_keyword', $title);
                $meta_updated = true;
            }

            if ($meta_updated) {
                update_term_meta($category->term_id, '_wrms_synced', 1);
                $synced_count++;
            }
        }

        wp_send_json_success(array('synced' => $synced_count, 'total' => $total_categories));
    } else {
        wp_send_json_error(array('message' => 'RankMath SEO plugin is not active.'));
    }
}

add_action('wp_ajax_wrms_sync_pages', 'wrms_sync_pages');
add_action('wp_ajax_wrms_sync_media', 'wrms_sync_media');
add_action('wp_ajax_wrms_remove_page_meta', 'wrms_remove_page_meta');
add_action('wp_ajax_wrms_remove_media_meta', 'wrms_remove_media_meta');

function wrms_sync_pages()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        $pages = get_posts(array(
            'post_type' => 'page',
            'posts_per_page' => -1,
        ));

        $total_pages = count($pages);
        $synced_count = 0;

        foreach ($pages as $page) {
            $title = $page->post_title;
            $content = $page->post_content;
            $excerpt = has_excerpt($page->ID) ? get_the_excerpt($page) : wp_trim_words($content, 30, '...');

            $meta_updated = false;

            if (!get_post_meta($page->ID, 'rank_math_title', true)) {
                update_post_meta($page->ID, 'rank_math_title', $title);
                $meta_updated = true;
            }
            if (!get_post_meta($page->ID, 'rank_math_description', true)) {
                update_post_meta($page->ID, 'rank_math_description', $excerpt);
                $meta_updated = true;
            }
            if (!get_post_meta($page->ID, 'rank_math_focus_keyword', true)) {
                update_post_meta($page->ID, 'rank_math_focus_keyword', $title);
                $meta_updated = true;
            }

            if ($meta_updated) {
                update_post_meta($page->ID, '_wrms_synced', 1);
                $synced_count++;
            }
        }

        wp_send_json_success(array('synced' => $synced_count, 'total' => $total_pages));
    } else {
        wp_send_json_error(array('message' => 'RankMath SEO plugin is not active.'));
    }
}

function wrms_sync_media()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
        ));

        $total_attachments = count($attachments);
        $synced_count = 0;

        foreach ($attachments as $attachment) {
            $title = $attachment->post_title;
            $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
            $description = wp_get_attachment_caption($attachment->ID);

            $meta_updated = false;

            if (!get_post_meta($attachment->ID, 'rank_math_title', true)) {
                update_post_meta($attachment->ID, 'rank_math_title', $title);
                $meta_updated = true;
            }
            if (!get_post_meta($attachment->ID, 'rank_math_description', true)) {
                update_post_meta($attachment->ID, 'rank_math_description', $description ? $description : $alt_text);
                $meta_updated = true;
            }
            if (!get_post_meta($attachment->ID, 'rank_math_focus_keyword', true)) {
                update_post_meta($attachment->ID, 'rank_math_focus_keyword', $title);
                $meta_updated = true;
            }

            if ($meta_updated) {
                update_post_meta($attachment->ID, '_wrms_synced', 1);
                $synced_count++;
            }
        }

        wp_send_json_success(array('synced' => $synced_count, 'total' => $total_attachments));
    } else {
        wp_send_json_error(array('message' => 'RankMath SEO plugin is not active.'));
    }
}

function wrms_remove_page_meta()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $pages = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    ));

    $removed_count = 0;

    foreach ($pages as $page) {
        delete_post_meta($page->ID, 'rank_math_title');
        delete_post_meta($page->ID, 'rank_math_description');
        delete_post_meta($page->ID, 'rank_math_focus_keyword');
        delete_post_meta($page->ID, '_wrms_synced');
        $removed_count++;
    }

    wp_send_json_success(array('removed' => $removed_count, 'total' => count($pages)));
}

function wrms_remove_media_meta()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    ));

    $removed_count = 0;

    foreach ($attachments as $attachment) {
        delete_post_meta($attachment->ID, 'rank_math_title');
        delete_post_meta($attachment->ID, 'rank_math_description');
        delete_post_meta($attachment->ID, 'rank_math_focus_keyword');
        delete_post_meta($attachment->ID, '_wrms_synced');
        $removed_count++;
    }

    wp_send_json_success(array('removed' => $removed_count, 'total' => count($attachments)));
}


function wrms_ajax_get_urls()
{
    check_ajax_referer('wrms_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $chunk_size = isset($_POST['chunk_size']) ? intval($_POST['chunk_size']) : 2000;
    $url_types = isset($_POST['url_types']) ? $_POST['url_types'] : array();

    $urls = array();

    foreach ($url_types as $type) {
        switch ($type) {
            case 'product':
                $urls = array_merge($urls, wrms_get_product_urls($offset, $chunk_size));
                break;
            case 'page':
                $urls = array_merge($urls, wrms_get_page_urls($offset, $chunk_size));
                break;
            case 'category':
                $urls = array_merge($urls, wrms_get_category_urls($offset, $chunk_size));
                break;
            case 'tag':
                $urls = array_merge($urls, wrms_get_tag_urls($offset, $chunk_size));
                break;
        }
    }

    wp_send_json_success(array('urls' => $urls));
}

function wrms_get_product_urls($offset, $chunk_size)
{
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $chunk_size,
        'offset' => $offset,
        'fields' => 'ids'
    );

    $product_ids = get_posts($args);
    return array_map('get_permalink', $product_ids);
}

function wrms_get_page_urls($offset, $chunk_size)
{
    $args = array(
        'post_type' => 'page',
        'posts_per_page' => $chunk_size,
        'offset' => $offset,
        'fields' => 'ids'
    );

    $page_ids = get_posts($args);
    return array_map('get_permalink', $page_ids);
}

function wrms_get_category_urls($offset, $chunk_size)
{
    $categories = get_terms(array(
        'taxonomy' => 'category',
        'hide_empty' => false,
        'offset' => $offset,
        'number' => $chunk_size,
    ));

    return array_map('get_category_link', wp_list_pluck($categories, 'term_id'));
}

function wrms_get_tag_urls($offset, $chunk_size)
{
    $tags = get_terms(array(
        'taxonomy' => 'post_tag',
        'hide_empty' => false,
        'offset' => $offset,
        'number' => $chunk_size,
    ));

    return array_map('get_tag_link', wp_list_pluck($tags, 'term_id'));
}

// Ensure the function to check plugin is active is loaded
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
