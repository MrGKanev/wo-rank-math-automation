<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Helper function to get product URLs
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

// Helper function to get page URLs
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

// Helper function to get category URLs
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

// Helper function to get tag URLs
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

// Helper function to get post URLs
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
