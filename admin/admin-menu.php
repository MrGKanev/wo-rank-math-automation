<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Add Admin Menu
add_action('admin_menu', 'wrms_add_admin_menu');
function wrms_add_admin_menu()
{
  add_submenu_page(
    'tools.php',
    'WordPress RankMath Sync',
    'RankMath Sync',
    'manage_options',
    'woocommerce-rankmath-sync',
    'wrms_admin_page'
  );
}

// Admin Page Content
function wrms_admin_page()
{
  $auto_sync = get_option('wrms_auto_sync', '0');
  $stats = wrms_get_stats();
?>
  <div class="wrap wrms-admin-page">
    <h1>WordPress RankMath Sync</h1>

    <div class="wrms-tabs">
      <button class="wrms-tab-link active" data-tab="sync">Sync</button>
      <button class="wrms-tab-link" data-tab="url-download">URL Download</button>
      <button class="wrms-tab-link" data-tab="settings">Settings</button>
    </div>

    <div class="wrms-tab-content">
      <div id="sync" class="wrms-tab-pane active">
        <h2>Sync or Remove RankMath Meta</h2>
        <div class="wrms-button-grid">
          <div class="wrms-button-group">
            <h3>WooCommerce Products</h3>
            <button id="sync-products" class="button button-primary">Sync Products</button>
            <button id="remove-product-meta" class="button button-secondary">Remove Product Meta</button>
          </div>
          <div class="wrms-button-group">
            <h3>WooCommerce Categories</h3>
            <button id="sync-categories" class="button button-primary">Sync Categories</button>
            <button id="remove-category-meta" class="button button-secondary">Remove Category Meta</button>
          </div>
          <div class="wrms-button-group">
            <h3>Pages</h3>
            <button id="sync-pages" class="button button-primary">Sync Pages</button>
            <button id="remove-page-meta" class="button button-secondary">Remove Page Meta</button>
          </div>
          <div class="wrms-button-group">
            <h3>Media</h3>
            <button id="sync-media" class="button button-primary">Sync Media</button>
            <button id="remove-media-meta" class="button button-secondary">Remove Media Meta</button>
          </div>
          <div class="wrms-button-group">
            <h3>Posts</h3>
            <button id="sync-posts" class="button button-primary">Sync Posts</button>
            <button id="remove-post-meta" class="button button-secondary">Remove Post Meta</button>
          </div>
        </div>
        <div id="sync-status" class="wrms-status-box">
          <img id="sync-loader" src="<?php echo admin_url('images/spinner.gif'); ?>" style="display:none;" />
          <p id="sync-count"></p>
          <div id="sync-log" class="sync-log">
            <?php echo esc_html__('Sync logs will appear here once you start a sync process.', 'woocommerce-rankmath-sync'); ?>
          </div>
          <div id="progress-bar">
            <div id="progress-bar-fill"></div>
          </div>
        </div>
      </div>

      <div id="url-download" class="wrms-tab-pane">
        <h2>Download WordPress URLs</h2>
        <div class="wrms-url-options">
          <h3>Select URL Types</h3>
          <p>Choose the types of URLs you want to download:</p>
          <form id="url-download-form">
            <label><input type="checkbox" name="url_types[]" value="product" checked> Products</label>
            <label><input type="checkbox" name="url_types[]" value="page"> Pages</label>
            <label><input type="checkbox" name="url_types[]" value="category"> Categories</label>
            <label><input type="checkbox" name="url_types[]" value="tag"> Tags</label>
            <label><input type="checkbox" name="url_types[]" value="post"> Posts</label>
            <button id="download-urls" class="button button-primary">Download URLs</button>
          </form>
        </div>
        <div id="download-status" class="wrms-status-box">
          <img id="download-loader" src="<?php echo admin_url('images/spinner.gif'); ?>" style="display:none;" />
          <p id="download-count"></p>
          <div id="download-log" class="sync-log">
            <?php echo esc_html__('URL download logs will appear here once you start the download process.', 'woocommerce-rankmath-sync'); ?>
          </div>
          <div id="download-progress-bar" class="progress-bar">
            <div id="download-progress-bar-fill" class="progress-bar-fill"></div>
          </div>
        </div>
      </div>

      <div id="settings" class="wrms-tab-pane">
        <h2>Plugin Settings</h2>
        <form method="post" action="options.php">
          <?php settings_fields('wrms_options_group'); ?>
          <label for="wrms_auto_sync">
            <input type="checkbox" id="wrms_auto_sync" name="wrms_auto_sync" value="1" <?php checked($auto_sync, '1'); ?> />
            Automatically sync all content (products, categories, pages, media, posts) to RankMath
          </label>
          <?php submit_button('Save Settings'); ?>
        </form>
      </div>
    </div>

    <div class="wrms-sidebar">
      <div class="wrms-stats-box">
        <h2>Plugin Statistics</h2>
        <p>Total Products: <span id="total-products"><?php echo $stats['total_products']; ?></span></p>
        <p>Synced Products: <span id="synced-products"><?php echo $stats['synced_products']; ?></span></p>
        <p>Total Pages: <span id="total-pages"><?php echo $stats['total_pages']; ?></span></p>
        <p>Synced Pages: <span id="synced-pages"><?php echo $stats['synced_pages']; ?></span></p>
        <p>Total Media: <span id="total-media"><?php echo $stats['total_media']; ?></span></p>
        <p>Synced Media: <span id="synced-media"><?php echo $stats['synced_media']; ?></span></p>
        <p>Total Categories: <span id="total-categories"><?php echo $stats['total_categories']; ?></span></p>
        <p>Synced Categories: <span id="synced-categories"><?php echo $stats['synced_categories']; ?></span></p>
        <p>Total Posts: <span id="total-posts"><?php echo $stats['total_posts']; ?></span></p>
        <p>Synced Posts: <span id="synced-posts"><?php echo $stats['synced_posts']; ?></span></p>
        <p>Total Items: <span id="total-items"><?php echo $stats['total_items']; ?></span></p>
        <p>Total Synced: <span id="total-synced"><?php echo $stats['total_synced']; ?></span></p>
        <p>Sync Percentage: <span id="sync-percentage"><?php echo $stats['sync_percentage']; ?>%</span></p>
        <p>Last Updated: <span id="last-updated"><?php echo $stats['last_updated']; ?></span></p>
        <button id="update-stats" class="button button-secondary">Update Statistics</button>
      </div>
    </div>
  </div>
<?php
}
