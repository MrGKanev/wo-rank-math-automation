<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Schedule cron job
function wrms_schedule_sync()
{
  if (!wp_next_scheduled('wrms_daily_sync')) {
    wp_schedule_event(time(), 'daily', 'wrms_daily_sync');
  }
}
add_action('wp', 'wrms_schedule_sync');

// Cron job to perform daily sync
add_action('wrms_daily_sync', 'wrms_perform_daily_sync');

function wrms_perform_daily_sync()
{
  if (get_option('wrms_auto_sync', '0') === '1') {
    wrms_sync_all_products();
    wrms_sync_all_categories();
    wrms_sync_all_pages();
    wrms_sync_all_media();
    wrms_sync_all_posts();
  }
}

// Function to manually trigger sync
function wrms_manual_sync()
{
  if (!wp_next_scheduled('wrms_manual_sync')) {
    wp_schedule_single_event(time(), 'wrms_manual_sync');
  }
}

// Hook for manual sync
add_action('wrms_manual_sync', 'wrms_perform_daily_sync');

// Clean up scheduled events on plugin deactivation
function wrms_deactivation()
{
  $timestamp = wp_next_scheduled('wrms_daily_sync');
  wp_unschedule_event($timestamp, 'wrms_daily_sync');
}
register_deactivation_hook(__FILE__, 'wrms_deactivation');

// Add custom cron schedules
function wrms_add_cron_interval($schedules)
{
  $schedules['weekly'] = array(
    'interval' => 604800,
    'display'  => __('Once Weekly')
  );
  return $schedules;
}
add_filter('cron_schedules', 'wrms_add_cron_interval');

// Function to check last sync time
function wrms_get_last_sync_time()
{
  return get_option('wrms_last_sync_time', 0);
}

// Function to update last sync time
function wrms_update_last_sync_time()
{
  update_option('wrms_last_sync_time', current_time('timestamp'));
}

// Add action to update last sync time after daily sync
add_action('wrms_daily_sync', 'wrms_update_last_sync_time');
