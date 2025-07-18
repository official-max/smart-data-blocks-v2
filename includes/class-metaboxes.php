<?php
// includes/class-metaboxes.php

if (!defined('ABSPATH')) exit;

class SDB_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_metabox']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($hook)
    {
        // Future scope: Add CSS/JS for wp_editor or repeater in metabox
    }

    public function add_metabox($post)
    {
        include SDB_PLUGIN_DIR . 'includes/add-metabox.php';
    }
    
    public function save_meta($post_id)
    {
        include SDB_PLUGIN_DIR . 'includes/save-metabox.php';
    }
}
