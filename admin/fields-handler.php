<?php
// admin/fields-handler.php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_fields = $wpdb->prefix . 'sdb_fields_v2';

// 🔴 Delete Field
if (isset($_GET['delete_field']) && current_user_can('manage_options')) {
    $field_id = intval($_GET['delete_field']);
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

    $wpdb->delete($table_fields, ['id' => $field_id]);
    wp_redirect(admin_url('admin.php?page=sdb_manage_fields&group_id=' . $group_id . '&deleted=1'));
    exit;
}

// ✅ Save Field (Add or Update)
if (isset($_POST['sdb_field_nonce']) && wp_verify_nonce($_POST['sdb_field_nonce'], 'sdb_field_action')) {

    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

    if (isset($_POST['fields']) && is_array($_POST['fields'])) {
        foreach ($_POST['fields'] as $field) {
            // Basic field values
            $field_id       = intval($field['field_id'] ?? 0);
            $field_label = sanitize_text_field($field['label'] ?? '');

            // 🔑 NEW – make it unique before insert/updatex
            $field_name_raw = sanitize_title($field['name'] ?? '');
            $field_name = sdb_get_unique_field_name($field_name_raw, $group_id, $field_id);

            $field_type  = sanitize_text_field($field['type'] ?? 'text');
            $field_order = intval($field['field_order'] ?? 0);

            // Allowed types
            $allowed_types = ['text', 'textarea', 'image', 'gallery', 'editor', 'repeater', 'file'];
            if (!in_array($field_type, $allowed_types)) {
                wp_die('Invalid field type!');
            }

            $field_settings = [];

            // 🔁 Handle repeater-specific settings
            if ($field_type === 'repeater' && isset($field['settings'])) {

                // min / max come from settings now
                $field_settings['min_rows'] = intval($field['settings']['min_rows'] ?? 1);
                $field_settings['max_rows'] = intval($field['settings']['max_rows'] ?? 10);

                // sub-fields live inside settings[sub_fields]
                if (! empty($field['settings']['sub_fields']) && is_array($field['settings']['sub_fields'])) {
                    $field_settings['sub_fields'] = sdb_process_sub_fields($field['settings']['sub_fields']);
                }
            }


            $data = [
                'group_id'       => $group_id,
                'field_label'    => $field_label,
                'field_name'     => $field_name,
                'field_type'     => $field_type,
                'field_order'    => $field_order,
                'field_settings' => $field_settings ? wp_json_encode($field_settings) : null,
            ];

            $field_id = intval($field['field_id'] ?? 0);

            if ($field_id > 0) {
                $wpdb->update($table_fields, $data, ['id' => $field_id]);
            } else {
                $wpdb->insert($table_fields, $data);
            }
        }
    }

    wp_redirect(admin_url('admin.php?page=sdb_manage_fields&group_id=' . $group_id . '&saved=1'));
    exit;
}
