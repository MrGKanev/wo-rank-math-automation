<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

function wrms_display_statistics()
{
  $stats = wrms_get_stats();
?>
  <div class="wrms-stats-box">
    <h2>Plugin Statistics</h2>
    <p>Total Products: <span id="total-products"><?php echo esc_html($stats['total_products']); ?></span></p>
    <p>Synced Products: <span id="synced-products"><?php echo esc_html($stats['synced_products']); ?></span></p>
    <p>Total Pages: <span id="total-pages"><?php echo esc_html($stats['total_pages']); ?></span></p>
    <p>Synced Pages: <span id="synced-pages"><?php echo esc_html($stats['synced_pages']); ?></span></p>
    <p>Total Media: <span id="total-media"><?php echo esc_html($stats['total_media']); ?></span></p>
    <p>Synced Media: <span id="synced-media"><?php echo esc_html($stats['synced_media']); ?></span></p>
    <p>Total Categories: <span id="total-categories"><?php echo esc_html($stats['total_categories']); ?></span></p>
    <p>Synced Categories: <span id="synced-categories"><?php echo esc_html($stats['synced_categories']); ?></span></p>
    <p>Total Posts: <span id="total-posts"><?php echo esc_html($stats['total_posts']); ?></span></p>
    <p>Synced Posts: <span id="synced-posts"><?php echo esc_html($stats['synced_posts']); ?></span></p>
    <p>Total Items: <span id="total-items"><?php echo esc_html($stats['total_items']); ?></span></p>
    <p>Total Synced: <span id="total-synced"><?php echo esc_html($stats['total_synced']); ?></span></p>
    <p>Sync Percentage: <span id="sync-percentage"><?php echo esc_html($stats['sync_percentage']); ?>%</span></p>
    <p>Last Updated: <span id="last-updated"><?php echo esc_html($stats['last_updated']); ?></span></p>
    <button id="update-stats" class="button button-secondary">Update Statistics</button>
  </div>
<?php
}
