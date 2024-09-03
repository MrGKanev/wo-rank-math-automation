<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Function to get all product URLs
function wrms_get_all_product_urls()
{
  $args = array(
    'post_type'      => 'product',
    'posts_per_page' => -1,
  );
  $products = get_posts($args);
  $urls = array();
  foreach ($products as $product) {
    $urls[] = get_permalink($product->ID);
  }
  return $urls;
}

// Function to get all category URLs
function wrms_get_all_category_urls()
{
  $args = array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
  );
  $categories = get_terms($args);
  $urls = array();
  foreach ($categories as $category) {
    $urls[] = get_term_link($category);
  }
  return $urls;
}

// Function to get all page URLs
function wrms_get_all_page_urls()
{
  $args = array(
    'post_type'      => 'page',
    'posts_per_page' => -1,
  );
  $pages = get_posts($args);
  $urls = array();
  foreach ($pages as $page) {
    $urls[] = get_permalink($page->ID);
  }
  return $urls;
}

// Function to get all post URLs
function wrms_get_all_post_urls()
{
  $args = array(
    'post_type'      => 'post',
    'posts_per_page' => -1,
  );
  $posts = get_posts($args);
  $urls = array();
  foreach ($posts as $post) {
    $urls[] = get_permalink($post->ID);
  }
  return $urls;
}

// Function to get all URLs
function wrms_get_all_urls()
{
  $urls = array_merge(
    wrms_get_all_product_urls(),
    wrms_get_all_category_urls(),
    wrms_get_all_page_urls(),
    wrms_get_all_post_urls()
  );
  return array_unique($urls);
}

// Function to generate sitemap
function wrms_generate_sitemap()
{
  $urls = wrms_get_all_urls();
  $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
  $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  foreach ($urls as $url) {
    $sitemap .= '<url>';
    $sitemap .= '<loc>' . esc_url($url) . '</loc>';
    $sitemap .= '<lastmod>' . date('c') . '</lastmod>';
    $sitemap .= '<changefreq>weekly</changefreq>';
    $sitemap .= '<priority>0.8</priority>';
    $sitemap .= '</url>';
  }
  $sitemap .= '</urlset>';
  return $sitemap;
}

// Function to save sitemap
function wrms_save_sitemap()
{
  $sitemap_content = wrms_generate_sitemap();
  $sitemap_path = ABSPATH . 'sitemap.xml';
  file_put_contents($sitemap_path, $sitemap_content);
}

// Function to get URL of a specific product
function wrms_get_product_url($product_id)
{
  return get_permalink($product_id);
}

// Function to get URL of a specific category
function wrms_get_category_url($category_id)
{
  return get_term_link($category_id, 'product_cat');
}

// Function to check if a URL exists in the database
function wrms_url_exists($url)
{
  global $wpdb;
  $post_id = url_to_postid($url);
  if ($post_id) {
    return true;
  }
  $sql = $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE guid = %s", $url);
  return (bool) $wpdb->get_var($sql);
}
