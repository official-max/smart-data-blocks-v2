<?php
// admin/groups-handler.php

if (!defined('ABSPATH')) exit;

global $wpdb;
$table_groups = $wpdb->prefix . 'sdb_field_groups_v2';

// 🗑️ 1. Delete (GET based)
if (isset($_GET['delete']) && current_user_can('manage_options')) {
    $group_id = intval($_GET['delete']);

    if ($group_id > 0) {
        $wpdb->delete($table_groups, ['id' => $group_id]);
        wp_redirect(admin_url('admin.php?page=sdb_field_groups&deleted=true'));
        exit;
    }
}

// ➕🔄 2. Add or Edit (POST based)
if (
    isset($_POST['sdb_group_nonce']) &&
    wp_verify_nonce($_POST['sdb_group_nonce'], 'sdb_group_action')
) {

    $title    = sanitize_text_field($_POST['group_title']);
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;



    // $key_slug = sanitize_title($_POST['group_key']); // optional
    // ✅ Auto-generate slug if empty
    $base_slug = sanitize_title($title);
    $last_id   = (int) $wpdb->get_var("SELECT MAX(id) FROM $table_groups");
    $key_slug  = $base_slug . '_' . ($last_id + 1);

    // Agr yeah key already exist hue toh last (homepage-settings-8-1, homepage-settings-8-2)
    $i = 1;
    $original_slug = $key_slug;
    while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_groups WHERE key_slug = %s", $key_slug)) > 0) {
        $key_slug = $original_slug . '_' . $i;
        $i++;
    }

    // 📦 Prepare location JSON
    $location_rules = [];
    $sanitize_location_rules = [];

    if (isset($_POST['location'])) {
        $location_rules = array_values($_POST['location']); // Reindex
        foreach ($location_rules as $rule) {
            $sanitize_location_rules[] = array_map('sanitize_text_field', $rule);
        }
    }

    $location_json = json_encode($sanitize_location_rules);

    if (!empty($title) && !empty($key_slug)) {
        // ✅ Unique slug check
        $query = "SELECT COUNT(*) FROM $table_groups WHERE key_slug = %s";
        $args  = [$key_slug];

        if ($group_id > 0) {
            $query .= " AND id != %d";
            $args[] = $group_id;
        }

        $exists = $wpdb->get_var($wpdb->prepare($query, ...$args));

        if ($exists > 0) {
            wp_redirect(admin_url('admin.php?page=sdb_field_groups&error=duplicate'));
            exit;
        }

        if ($group_id > 0) {
            // 🔄 Update
            $wpdb->update($table_groups, [
                'title'    => $title,
                'key_slug' => $key_slug,
                'location' => $location_json,
            ], ['id' => $group_id]);

            wp_redirect(admin_url('admin.php?page=sdb_field_groups&updated=true'));
        } else {
            // ➕ Add
            $wpdb->insert($table_groups, [
                'title'    => $title,
                'key_slug' => $key_slug,
                'location' => $location_json,
            ]);

            wp_redirect(admin_url('admin.php?page=sdb_field_groups&added=true'));
        }
        exit;
    }
}
