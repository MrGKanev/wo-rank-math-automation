<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Function to calculate and cache statistics
function wrms_calculate_and_cache_stats()
{
  $total_products = wp_count_posts('product')->publish;
  $total_pages = wp_count_posts('page')->publish;
  $total_media = wp_count_posts('attachment')->inherit;
  $total_categories = wp_count_terms('product_cat');
  $total_posts = wp_count_posts('post')->publish;

  $synced_products = count(get_posts(array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => array(
      array(
        'key' => '_wrms_synced',
        'value' => '1',
        'compare' => '='
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
        'value' => '1',
        'compare' => '='
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
        'value' => '1',
        'compare' => '='
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
        'value' => '1',
        'compare' => '='
      )
    )
  )));

  $synced_posts = count(get_posts(array(
    'post_type' => 'post',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => array(
      array(
        'key' => '_wrms_synced',
        'value' => '1',
        'compare' => '='
      )
    )
  )));

  $total_items = $total_products + $total_pages + $total_media + $total_categories + $total_posts;
  $total_synced = $synced_products + $synced_pages + $synced_media + $synced_categories + $synced_posts;

  $sync_percentage = $total_items > 0 ? round(($total_synced / $total_items) * 100, 2) : 0;

  $stats = array(
    'total_products' => $total_products,
    'total_pages' => $total_pages,
    'total_media' => $total_media,
    'total_categories' => $total_categories,
    'total_posts' => $total_posts,
    'synced_products' => $synced_products,
    'synced_pages' => $synced_pages,
    'synced_media' => $synced_media,
    'synced_categories' => $synced_categories,
    'synced_posts' => $synced_posts,
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
