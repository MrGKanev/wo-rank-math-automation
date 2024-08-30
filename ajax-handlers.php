<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Hook all AJAX actions
add_action('wp_ajax_wrms_sync_products', 'wrms_sync_products');
add_action('wp_ajax_wrms_sync_categories', 'wrms_sync_categories');
add_action('wp_ajax_wrms_sync_pages', 'wrms_sync_pages');
add_action('wp_ajax_wrms_sync_media', 'wrms_sync_media');
add_action('wp_ajax_wrms_sync_posts', 'wrms_sync_posts');
add_action('wp_ajax_wrms_remove_product_meta', 'wrms_remove_product_meta');
add_action('wp_ajax_wrms_remove_category_meta', 'wrms_remove_category_meta');
add_action('wp_ajax_wrms_remove_page_meta', 'wrms_remove_page_meta');
add_action('wp_ajax_wrms_remove_media_meta', 'wrms_remove_media_meta');
add_action('wp_ajax_wrms_remove_post_meta', 'wrms_remove_post_meta');
add_action('wp_ajax_wrms_get_product_count', 'wrms_get_product_count');
add_action('wp_ajax_wrms_update_auto_sync', 'wrms_update_auto_sync');
add_action('wp_ajax_wrms_get_urls', 'wrms_ajax_get_urls');
add_action('wp_ajax_wrms_update_stats', 'wrms_ajax_update_stats');

// Sync functions

function wrms_sync_products() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
    );
    $products = get_posts($args);

    $total_products = count($products);
    $synced_count = 0;

    foreach ($products as $product) {
        $product_obj = wc_get_product($product->ID);
        if (!$product_obj) continue;

        $title = $product_obj->get_name();
        $description = $product_obj->get_description();
        $short_description = $product_obj->get_short_description();
        $seo_description = $short_description ? $short_description : wp_trim_words($description, 30, '...');

        $meta_updated = false;

        if (!get_post_meta($product->ID, 'rank_math_title', true)) {
            update_post_meta($product->ID, 'rank_math_title', $title);
            $meta_updated = true;
        }
        if (!get_post_meta($product->ID, 'rank_math_description', true)) {
            update_post_meta($product->ID, 'rank_math_description', $seo_description);
            $meta_updated = true;
        }
        if (!get_post_meta($product->ID, 'rank_math_focus_keyword', true)) {
            update_post_meta($product->ID, 'rank_math_focus_keyword', $title);
            $meta_updated = true;
        }

        if ($meta_updated) {
            update_post_meta($product->ID, '_wrms_synced', 1);
            $synced_count++;
        }
    }

    wp_send_json_success(array('synced' => $synced_count, 'total' => $total_products));
}

function wrms_sync_categories() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

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
}

function wrms_sync_pages() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

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
}

function wrms_sync_media() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

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
}

function wrms_sync_posts() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $posts = get_posts(array(
        'post_type' => 'post',
        'posts_per_page' => -1,
    ));

    $total_posts = count($posts);
    $synced_count = 0;

    foreach ($posts as $post) {
        $title = $post->post_title;
        $content = $post->post_content;
        $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post) : wp_trim_words($content, 30, '...');

        $meta_updated = false;

        if (!get_post_meta($post->ID, 'rank_math_title', true)) {
            update_post_meta($post->ID, 'rank_math_title', $title);
            $meta_updated = true;
        }
        if (!get_post_meta($post->ID, 'rank_math_description', true)) {
            update_post_meta($post->ID, 'rank_math_description', $excerpt);
            $meta_updated = true;
        }
        if (!get_post_meta($post->ID, 'rank_math_focus_keyword', true)) {
            update_post_meta($post->ID, 'rank_math_focus_keyword', $title);
            $meta_updated = true;
        }

        if ($meta_updated) {
            update_post_meta($post->ID, '_wrms_synced', 1);
            $synced_count++;
        }
    }

    wp_send_json_success(array('synced' => $synced_count, 'total' => $total_posts));
}

// Remove functions

function wrms_remove_product_meta() {
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

function wrms_remove_category_meta() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

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

function wrms_remove_page_meta() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }

    check_ajax_referer('wrms_nonce', 'nonce');

    $pages = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wrms_synced',
                'compare' => 'EXISTS'
            )
        )
    ));

    $removed_count = 0;

    foreach ($pages as $page_id) {
        delete_post_meta($page_id, 'rank_math_title');
        delete_post_meta($page_id, 'rank_math_description');
        delete_post_meta($page_id, 'rank_math_focus_keyword');
        delete_post_meta($page_id, '_wrms_synced');
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
    'fields' => 'ids',
    'meta_query' => array(
      array(
        'key' => '_wrms_synced',
        'compare' => 'EXISTS'
      )
    )
  ));

  $removed_count = 0;

  foreach ($attachments as $attachment_id) {
    delete_post_meta($attachment_id, 'rank_math_title');
    delete_post_meta($attachment_id, 'rank_math_description');
    delete_post_meta($attachment_id, 'rank_math_focus_keyword');
    delete_post_meta($attachment_id, '_wrms_synced');
    $removed_count++;
  }

  wp_send_json_success(array('removed' => $removed_count, 'total' => count($attachments)));
}

function wrms_remove_post_meta()
{
  if (!current_user_can('manage_options')) {
    wp_send_json_error(array('message' => 'Unauthorized access.'));
    return;
  }

  check_ajax_referer('wrms_nonce', 'nonce');

  $posts = get_posts(array(
    'post_type' => 'post',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => array(
      array(
        'key' => '_wrms_synced',
        'compare' => 'EXISTS'
      )
    )
  ));

  $removed_count = 0;

  foreach ($posts as $post_id) {
    delete_post_meta($post_id, 'rank_math_title');
    delete_post_meta($post_id, 'rank_math_description');
    delete_post_meta($post_id, 'rank_math_focus_keyword');
    delete_post_meta($post_id, '_wrms_synced');
    $removed_count++;
  }

  wp_send_json_success(array('removed' => $removed_count, 'total' => count($posts)));
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
  $total_items = 0;

  foreach ($url_types as $type) {
    switch ($type) {
      case 'product':
        $product_urls = wrms_get_product_urls($offset, $chunk_size);
        $urls = array_merge($urls, $product_urls['urls']);
        $total_items += $product_urls['total'];
        break;
      case 'page':
        $page_urls = wrms_get_page_urls($offset, $chunk_size);
        $urls = array_merge($urls, $page_urls['urls']);
        $total_items += $page_urls['total'];
        break;
      case 'category':
        $category_urls = wrms_get_category_urls($offset, $chunk_size);
        $urls = array_merge($urls, $category_urls['urls']);
        $total_items += $category_urls['total'];
        break;
      case 'tag':
        $tag_urls = wrms_get_tag_urls($offset, $chunk_size);
        $urls = array_merge($urls, $tag_urls['urls']);
        $total_items += $tag_urls['total'];
        break;
      case 'post':
        $post_urls = wrms_get_post_urls($offset, $chunk_size);
        $urls = array_merge($urls, $post_urls['urls']);
        $total_items += $post_urls['total'];
        break;
    }
  }

  wp_send_json_success(array('urls' => $urls, 'total' => $total_items));
}

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

// Helper functions for getting URLs
function wrms_get_product_urls($offset, $chunk_size)
{
  $args = array(
    'post_type' => 'product',
    'posts_per_page' => $chunk_size,
    'offset' => $offset,
    'fields' => 'ids'
  );

  $product_ids = get_posts($args);
  $total_products = wp_count_posts('product')->publish;
  return array(
    'urls' => array_map('get_permalink', $product_ids),
    'total' => $total_products
  );
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
  $total_pages = wp_count_posts('page')->publish;
  return array(
    'urls' => array_map('get_permalink', $page_ids),
    'total' => $total_pages
  );
}

function wrms_get_category_urls($offset, $chunk_size)
{
  $categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false,
    'offset' => $offset,
    'number' => $chunk_size,
  ));

  $total_categories = wp_count_terms('category');
  return array(
    'urls' => array_map('get_category_link', wp_list_pluck($categories, 'term_id')),
    'total' => $total_categories
  );
}

function wrms_get_tag_urls($offset, $chunk_size)
{
  $tags = get_terms(array(
    'taxonomy' => 'post_tag',
    'hide_empty' => false,
    'offset' => $offset,
    'number' => $chunk_size,
  ));

  $total_tags = wp_count_terms('post_tag');
  return array(
    'urls' => array_map('get_tag_link', wp_list_pluck($tags, 'term_id')),
    'total' => $total_tags
  );
}

function wrms_get_post_urls($offset, $chunk_size)
{
  $args = array(
    'post_type' => 'post',
    'posts_per_page' => $chunk_size,
    'offset' => $offset,
    'fields' => 'ids'
  );

  $post_ids = get_posts($args);
  $total_posts = wp_count_posts('post')->publish;
  return array(
    'urls' => array_map('get_permalink', $post_ids),
    'total' => $total_posts
  );
}

// Ensure the function to check if plugin is active is loaded
if (!function_exists('is_plugin_active')) {
  include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}