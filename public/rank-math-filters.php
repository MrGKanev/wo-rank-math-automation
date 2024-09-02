<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Add filter to modify RankMath meta fields
add_filter('rank_math/frontend/title', 'wrms_modify_rankmath_title', 10, 2);
add_filter('rank_math/frontend/description', 'wrms_modify_rankmath_description', 10, 2);
add_filter('rank_math/frontend/canonical', 'wrms_modify_rankmath_canonical', 10, 2);

/**
 * Modify RankMath title for products
 *
 * @param string $title The current title
 * @param object $object The current object (post, term, etc.)
 * @return string The modified title
 */
function wrms_modify_rankmath_title($title, $object = null)
{
  if (is_product() && $object instanceof WP_Post) {
    $product = wc_get_product($object->ID);
    if ($product) {
      return $product->get_name();
    }
  }
  return $title;
}

/**
 * Modify RankMath description for products
 *
 * @param string $description The current description
 * @param object $object The current object (post, term, etc.)
 * @return string The modified description
 */
function wrms_modify_rankmath_description($description, $object = null)
{
  if (is_product() && $object instanceof WP_Post) {
    $product = wc_get_product($object->ID);
    if ($product) {
      $short_description = $product->get_short_description();
      if (!empty($short_description)) {
        return wp_trim_words($short_description, 30, '...');
      } else {
        return wp_trim_words($product->get_description(), 30, '...');
      }
    }
  }
  return $description;
}

/**
 * Modify RankMath canonical URL for products
 *
 * @param string $canonical The current canonical URL
 * @param object $object The current object (post, term, etc.)
 * @return string The modified canonical URL
 */
function wrms_modify_rankmath_canonical($canonical, $object = null)
{
  if (is_product() && $object instanceof WP_Post) {
    $product = wc_get_product($object->ID);
    if ($product) {
      return get_permalink($product->get_id());
    }
  }
  return $canonical;
}

/**
 * Modify RankMath focus keyword for products
 *
 * @param string $focus_keyword The current focus keyword
 * @param int $post_id The post ID
 * @return string The modified focus keyword
 */
function wrms_modify_rankmath_focus_keyword($focus_keyword, $post_id)
{
  if ('product' === get_post_type($post_id)) {
    $product = wc_get_product($post_id);
    if ($product) {
      // You can customize this to use product attributes, categories, or other data
      return $product->get_name();
    }
  }
  return $focus_keyword;
}
add_filter('rank_math/focus_keyword', 'wrms_modify_rankmath_focus_keyword', 10, 2);

/**
 * Add product data to RankMath's schema
 *
 * @param array $schema The current schema data
 * @param object $object The current object (post, term, etc.)
 * @return array The modified schema data
 */
function wrms_add_product_schema($schema, $object)
{
  if (is_product() && $object instanceof WP_Post) {
    $product = wc_get_product($object->ID);
    if ($product) {
      $schema['product'] = array(
        '@type' => 'Product',
        'name' => $product->get_name(),
        'description' => $product->get_short_description(),
        'sku' => $product->get_sku(),
        'price' => $product->get_price(),
        'priceCurrency' => get_woocommerce_currency(),
        'availability' => $product->is_in_stock() ? 'InStock' : 'OutOfStock',
      );

      // Add product image
      $image_id = $product->get_image_id();
      if ($image_id) {
        $schema['product']['image'] = wp_get_attachment_url($image_id);
      }

      // Add product brand
      $brands = wp_get_post_terms($product->get_id(), 'product_brand');
      if (!empty($brands) && !is_wp_error($brands)) {
        $schema['product']['brand'] = array(
          '@type' => 'Brand',
          'name' => $brands[0]->name,
        );
      }
    }
  }
  return $schema;
}
add_filter('rank_math/schema/product', 'wrms_add_product_schema', 10, 2);

// Ensure the function to check if plugin is active is loaded
if (!function_exists('is_plugin_active')) {
  include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
