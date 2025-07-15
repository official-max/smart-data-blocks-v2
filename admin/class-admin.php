<?php
// admin/class-admin.php
if (!defined('ABSPATH')) exit;

class SDB_Admin
{
    private $metaboxes;
    public function __construct()
    {
        $this->metaboxes = new SDB_Metabox();
        add_action('admin_init', [$this, 'handle_admin_form']);
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function handle_admin_form()
    {
        require_once SDB_PLUGIN_DIR . 'admin/groups-handler.php';
        require_once SDB_PLUGIN_DIR . 'admin/fields-handler.php';
    }

    public function enqueue_assets($hook)
    {
        wp_enqueue_style('sdb-admin-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css', [], '1.0');
        wp_enqueue_script('sdb-function-js', plugin_dir_url(__FILE__) . 'assets/js/function.js', [], '1.0', true);
        wp_enqueue_script('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], null, true);

        // Location rules script (Field Groups page only)
        if (strpos($hook, 'sdb_field_groups') !== false) {
            wp_enqueue_script('sdb-location-js', plugin_dir_url(__FILE__) . 'assets/js/admin-location-rules.js', [], '1.0', true);
        }

        // Manage Fields script
        if (strpos($hook, 'sdb_manage_fields') !== false) {
            wp_enqueue_script('sdb-fields-js', plugin_dir_url(__FILE__) . 'assets/js/admin-fields.js', [], '1.0', true);
        }
    }


    public function register_menus()
    {
        add_menu_page(
            'Smart Data Blocks',
            'Field Groups',
            'manage_options',
            'sdb_field_groups',
            [$this, 'sdb_render_groups_page'],
            'dashicons-feedback',
        );

        add_submenu_page(
            'sdb_field_groups',
            'Field Groups',
            'Field Groups',
            'manage_options',
            'sdb_field_groups',
            [$this, 'sdb_render_groups_page'],
        );

        add_submenu_page(
            'sdb_field_groups',
            'Manage Fields',
            'Manage Fields',
            'manage_options',
            'sdb_manage_fields',
            [$this, 'sdb_render_manage_fields_page'],
        );
    }

    public function sdb_render_groups_page()
    {
        require_once SDB_PLUGIN_DIR . 'admin/settings-groups.php';
    }

    function sdb_render_manage_fields_page()
    {
        include plugin_dir_path(__FILE__) . 'settings-fields.php';
    }
}
