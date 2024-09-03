<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Hook to save product
add_action('save_post_product', 'wrms_maybe_sync_product', 10, 3);
function wrms_maybe_sync_product($post_id, $post = null, $update = false)
{
  if (get_option('wrms_auto_sync', '0') !== '1') {
    return;
  }

  if (!is_plugin_active('seo-by-rank-math/rank-math.php')) {
    return;
  }

  $product = wc_get_product($post_id);
  if (!$product) return;

  $title = $product->get_name();
  $description = $product->get_description();
  $short_description = $product->get_short_description();
  $seo_description = $short_description ? $short_description : wp_trim_words($description, 30, '...');

  update_post_meta($post_id, 'rank_math_title', $title);
  update_post_meta($post_id, 'rank_math_description', $seo_description);
  update_post_meta($post_id, 'rank_math_focus_keyword', $title);
  update_post_meta($post_id, '_wrms_synced', '1');
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

    update_term_meta($term_id, 'rank_math_title', $title);
    update_term_meta($term_id, 'rank_math_description', $seo_description);
    update_term_meta($term_id, 'rank_math_focus_keyword', $title);
    update_term_meta($term_id, '_wrms_synced', '1');
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

// Hook to save post
add_action('save_post', 'wrms_maybe_sync_post', 10, 3);
function wrms_maybe_sync_post($post_id, $post, $update)
{
  if (get_option('wrms_auto_sync', '0') !== '1' || $post->post_type !== 'post') {
    return;
  }

  if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
    $title = get_the_title($post_id);
    $content = get_post_field('post_content', $post_id);
    $excerpt = has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($content, 30, '...');

    update_post_meta($post_id, 'rank_math_title', $title);
    update_post_meta($post_id, 'rank_math_description', $excerpt);
    update_post_meta($post_id, 'rank_math_focus_keyword', $title);
    update_post_meta($post_id, '_wrms_synced', 1);
  }
}

// Function to sync all posts
function wrms_sync_all_posts()
{
  $posts = get_posts(array('post_type' => 'post', 'posts_per_page' => -1));
  foreach ($posts as $post) {
    wrms_maybe_sync_post($post->ID, $post, true);
  }
}

// Function to sync all products
function wrms_sync_all_products()
{
  $products = get_posts(array('post_type' => 'product', 'posts_per_page' => -1));
  foreach ($products as $product) {
    wrms_maybe_sync_product($product->ID, $product, true);
  }
}

// Function to sync all categories
function wrms_sync_all_categories()
{
  $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
  foreach ($categories as $category) {
    wrms_maybe_sync_category($category->term_id, $category->term_taxonomy_id);
  }
}

// Function to sync all pages
function wrms_sync_all_pages()
{
  $pages = get_posts(array('post_type' => 'page', 'posts_per_page' => -1));
  foreach ($pages as $page) {
    wrms_maybe_sync_page($page->ID, $page, true);
  }
}

// Function to sync all media
function wrms_sync_all_media()
{
  $attachments = get_posts(array('post_type' => 'attachment', 'posts_per_page' => -1));
  foreach ($attachments as $attachment) {
    wrms_maybe_sync_media($attachment->ID);
  }
}
