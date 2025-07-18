<?php
// includes/save-metabox.php
if (!defined('ABSPATH')) exit;

// 1. Security: Nonce check
if (!isset($_POST['sdb_metabox_nonce_field']) || !wp_verify_nonce($_POST['sdb_metabox_nonce_field'], 'sdb_metabox_nonce')) {
    return;
}

// 2. Autosave check
if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
}

// 3. Capability check
if (!current_user_can('edit_post', $post_id)) {
    return;
}

// 4. Active group IDs (set while rendering)
$active_groups = $_POST['sdb_active_groups'] ?? [];
if (!is_array($active_groups)) {
    return;
}

foreach ($active_groups as $group_id) {
    $group_id = (int) $group_id;
    if (!$group_id) continue;

    $fields = sdb_get_existing_fields($group_id);
    if (!$fields) continue;

    foreach ($fields as $field) {
        $field_id = (int) $field->id;
        $field_type = $field->field_type;
        $meta_key = 'sdb_' . sanitize_key($field->field_name) . '_' . $field_id;

        if (!isset($_POST[$meta_key])) continue;
        $raw_value = $_POST[$meta_key];

        switch ($field_type) {

            case 'text':
            case 'textarea':
                $sanitized = sanitize_text_field($raw_value);
                break;

            case 'editor':
                $sanitized = wp_kses_post($raw_value);
                break;

            case 'image':
            case 'file':
                $sanitized = esc_url_raw($raw_value); // ya numeric ID bhi ho sakta hai
                break;

            default:
                $sanitized = sanitize_text_field($raw_value);
        }

        update_post_meta($post_id, $meta_key, $sanitized);
    }
}
