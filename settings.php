<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Register settings
add_action('admin_init', 'wrms_register_settings');
function wrms_register_settings()
{
  register_setting('wrms_options_group', 'wrms_auto_sync');

  // Add more settings as needed
  // register_setting('wrms_options_group', 'wrms_another_option');
}

// Get plugin settings
function wrms_get_settings()
{
  $defaults = array(
    'auto_sync' => '0',
    // Add more default settings as needed
  );

  $settings = get_option('wrms_settings', array());
  return wp_parse_args($settings, $defaults);
}

// Update plugin settings
function wrms_update_settings($new_settings)
{
  $old_settings = wrms_get_settings();
  $updated_settings = wp_parse_args($new_settings, $old_settings);
  update_option('wrms_settings', $updated_settings);
}

// Render settings page
function wrms_render_settings_page()
{
  if (!current_user_can('manage_options')) {
    return;
  }

  $settings = wrms_get_settings();
?>
  <div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
      <?php
      settings_fields('wrms_options_group');
      do_settings_sections('wrms_options_group');
      ?>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">Auto Sync</th>
          <td>
            <label for="wrms_auto_sync">
              <input type="checkbox" id="wrms_auto_sync" name="wrms_auto_sync" value="1" <?php checked($settings['auto_sync'], '1'); ?> />
              Automatically sync product and category information to RankMath
            </label>
          </td>
        </tr>
        <?php
        // Add more settings fields as needed
        ?>
      </table>
      <?php submit_button('Save Settings'); ?>
    </form>
  </div>
<?php
}

// Add settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wrms_add_settings_link');
function wrms_add_settings_link($links)
{
  $settings_link = '<a href="' . admin_url('options-general.php?page=woocommerce-rankmath-sync') . '">' . __('Settings') . '</a>';
  array_unshift($links, $settings_link);
  return $links;
}

// Sanitize settings
function wrms_sanitize_settings($input)
{
  $sanitary_values = array();
  if (isset($input['auto_sync'])) {
    $sanitary_values['auto_sync'] = $input['auto_sync'];
  }
  // Add sanitization for other fields as needed
  return $sanitary_values;
}

// Initialize settings
function wrms_settings_init()
{
  register_setting('wrms_options_group', 'wrms_settings', 'wrms_sanitize_settings');

  add_settings_section(
    'wrms_settings_section',
    __('General Settings', 'woocommerce-rankmath-sync'),
    'wrms_settings_section_callback',
    'wrms_options_group'
  );

  add_settings_field(
    'wrms_auto_sync',
    __('Auto Sync', 'woocommerce-rankmath-sync'),
    'wrms_auto_sync_render',
    'wrms_options_group',
    'wrms_settings_section'
  );
  // Add more settings fields as needed
}

function wrms_settings_section_callback()
{
  echo __('Configure general settings for WooCommerce RankMath Sync', 'woocommerce-rankmath-sync');
}

function wrms_auto_sync_render()
{
  $options = get_option('wrms_settings');
?>
  <input type='checkbox' name='wrms_settings[auto_sync]' <?php checked($options['auto_sync'], 1); ?> value='1'>
<?php
}

// Add more render functions for additional settings as needed

add_action('admin_init', 'wrms_settings_init');
