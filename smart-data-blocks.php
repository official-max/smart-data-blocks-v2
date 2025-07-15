<?php

/**
 * Plugin Name: Smart Data Blocks
 * Description: Create and manage custom field groups with repeater support.
 * Version: 1.1.0
 * Author: Jatin Pal
 */

// 🔐 Direct access se rokne ke liye
if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

// 📌 Constants define kar rahe hain (reuse ke liye)
define('SDB_VERSION', '1.1.0');
define('SDB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SDB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SDB_TABLE_GROUPS', $wpdb->prefix . 'sdb_field_groups_v2');
define('SDB_TABLE_FIELDS', $wpdb->prefix . 'sdb_fields_v2');


// 📂 DB create file include kar rahe hain
require_once SDB_PLUGIN_DIR . 'includes/db_install.php';

// 🔁 DB tables create on plugin activation
register_activation_hook(__FILE__, 'sdb_v2_activate_plugin');
function sdb_v2_activate_plugin()
{
    sdb_v2_create_tables();
}


// 🛠️ Only load AJAX logic during AJAX requests
if (defined('DOING_AJAX') && DOING_AJAX) {
    require_once SDB_PLUGIN_DIR . 'includes/ajax.php';
}



// 🔄 Plugin initialization
add_action('plugins_loaded', function () {
    require_once SDB_PLUGIN_DIR . 'includes/class-metaboxes.php';
    if (is_admin()) {
        require_once SDB_PLUGIN_DIR . 'admin/class-admin.php';
        new SDB_Admin();
    }

    // TODO: Frontend logic here (shortcodes, template tags etc.)
});
