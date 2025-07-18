<?php
// includes\add-metabox.php
if (!defined('ABSPATH')) exit;

// meta box mai wordpress auto args pass krta hai post 
if (is_string($post)) {
    $post = get_post();
}

// Safety: Agar post nahi mila toh return
if (! $post instanceof WP_Post) {
    return;
}

$groups = sdb_get_all_field_groups();
if (!$groups) return;

foreach ($groups as $group) {

    $location_json = $group->location; // JSON string
    $location_rules = json_decode($location_json, true);

    // Check if group show krana hai ish post mai
    if (!sdb_show_metabox($post, $location_rules)) {
        continue;
    }

    // Add metabox
    add_meta_box(
        'sdb_group_' . $group->id,
        'SDB: ' . $group->title,
        function () use ($group, $post) {
            sdb_render_group_metabox($group, $post);
        },
        $post->post_type,
        'normal',
        'default'
    );
}


function sdb_render_group_metabox($group, $post)
{
    // 1. Fetch fields for this group
    $fields = sdb_get_existing_fields($group->id);
    if (! $fields) {
        echo '<p><em>No fields found for this group.</em></p>';
        return;
    }

    // 2. Nonce for security
    wp_nonce_field('sdb_metabox_nonce', 'sdb_metabox_nonce_field');

    // Add hidden group ID field
    echo '<input type="hidden" name="sdb_active_groups[]" value="' . esc_attr($group->id) . '">';

    echo '<div class="metabox_container"><div class="sdb-fields">';

    foreach ($fields as $field) {

        // --- Build identifiers
        $field_id  = (int) $field->id;
        $meta_key  = 'sdb_' . sanitize_key($field->field_name) . '_' . $field_id;   // e.g. sdb_main_heading_12
        $value     = get_post_meta($post->ID, $meta_key, true);
        $type      = $field->field_type;

        echo '<div class="sdb-field-row" data-field-id="' . esc_attr($field_id) . '">';

        // Label
        echo '<label class="label">' . esc_html($field->field_label) . '</label>';

        // Input switch
        switch ($type) {

            case 'text':
                printf(
                    '<input type="text" name="%1$s" value="%2$s" class="regular-text" />',
                    esc_attr($meta_key),
                    esc_attr($value)
                );
                break;

            case 'textarea':
                printf(
                    '<textarea name="%1$s" rows="3" class="widefat">%2$s</textarea>',
                    esc_attr($meta_key),
                    esc_textarea($value)
                );
                break;

            /*-----------------------------------------------------
              Fallback
            -----------------------------------------------------*/
            default:
                printf(
                    '<input type="text" name="%1$s" value="%2$s" class="regular-text" />',
                    esc_attr($meta_key),
                    esc_attr($value)
                );
        }

        echo '</div>';
    }
}




// Function
function sdb_render_group_metaboxes($group, $post)
{
    // 1. Fetch fields for this group
    $fields = sdb_get_existing_fields($group->id);
    if (! $fields) {
        echo '<p><em>No fields found for this group.</em></p>';
        return;
    }

    // 2. Nonce for security
    wp_nonce_field('sdb_metabox_nonce', 'sdb_metabox_nonce_field');

    echo '<div class="metabox_container"><div class="sdb-fields">';

    foreach ($fields as $field) {

        // --- Build identifiers
        $field_id  = (int) $field->id;
        $meta_key  = 'sdb_' . sanitize_key($field->field_name) . '_' . $field_id;   // e.g. sdb_main_heading_12
        $value     = get_post_meta($post->ID, $meta_key, true);
        $type      = $field->field_type;
        // $settings  = $field->field_settings ? json_decode($field->field_settings, true) : [];

        echo '<div class="sdb-field-row" data-field-id="' . esc_attr($field_id) . '">';

        // Label
        echo '<label class="label">' . esc_html($field->field_label) . '</label>';

        // Input switch
        switch ($type) {

            /*-----------------------------------------------------
              Text
            -----------------------------------------------------*/
            case 'text':
                printf(
                    '<input type="text" name="%1$s" value="%2$s" class="regular-text" />',
                    esc_attr($meta_key),
                    esc_attr($value)
                );
                break;

            /*-----------------------------------------------------
              Textarea
            -----------------------------------------------------*/
            case 'textarea':
                printf(
                    '<textarea name="%1$s" rows="4" class="widefat">%2$s</textarea>',
                    esc_attr($meta_key),
                    esc_textarea($value)
                );
                break;

            /*-----------------------------------------------------
              Image (URL stored for now – add media‑uploader JS later)
            -----------------------------------------------------*/
            case 'image':
                $saved_value = $value ?: '';
                $image_url = '';

                if (is_numeric($saved_value)) {
                    // If value is attachment ID, get the image URL
                    $image_url = wp_get_attachment_url($saved_value);
                } else {
                    // Else assume direct URL
                    $image_url = esc_url($saved_value);
                }
?>
                <div class="sdb-image-field">
                    <input type="hidden" class="sdb-image-url" name="<?php echo esc_attr($meta_key); ?>" value="<?php echo esc_attr($saved_value); ?>">

                    <img src="<?php echo esc_url($image_url); ?>" class="sdb-preview" style="max-width:150px; <?php echo $image_url ? '' : 'display:none;'; ?>">

                    <br>
                    <button type="button" class="button sdb-upload-image">Upload</button>
                    <button type="button" class="button sdb-remove-image">Remove</button>
                </div>
            <?php
                break;


            /*-----------------------------------------------------
              Editor (WP TinyMCE)
            -----------------------------------------------------*/
            case 'editor':
                wp_editor(
                    $value,
                    'sdb_field_' . $field_id,                                        // unique ID
                    [
                        'textarea_name' => $meta_key,                               // ← IMPORTANT
                        'media_buttons' => false,
                        'textarea_rows' => 6,
                    ]
                );
                break;

            /*-----------------------------------------------------
              File (URL)
            -----------------------------------------------------*/
            case 'file':
                printf(
                    '<input type="text" name="%1$s" value="%2$s" class="regular-text" placeholder="File URL" />',
                    esc_attr($meta_key),
                    esc_attr($value)
                );
                break;

            /*-----------------------------------------------------
              Gallery & Repeater placeholders
            -----------------------------------------------------*/
            case 'gallery':
                $saved_value = $value ?: ''; // Comma-separated IDs string
                $image_ids = array_filter(explode(',', $saved_value));
            ?>
                <div class="sdb-gallery-field" data-field-id="<?php echo esc_attr($field_id); ?>">
                    <input type="hidden" class="sdb-gallery-ids" name="<?php echo esc_attr($meta_key); ?>" value="<?php echo esc_attr($saved_value); ?>">

                    <div class="sdb-gallery-preview" style="display:flex;flex-wrap:wrap;gap:10px;">
                        <?php foreach ($image_ids as $id):
                            $thumb = wp_get_attachment_image_url($id, 'thumbnail');
                            if ($thumb): ?>
                                <div class="sdb-gallery-thumb" data-id="<?php echo esc_attr($id); ?>" style="position:relative;">
                                    <img src="<?php echo esc_url($thumb); ?>" style="width:100px;height:auto;border:1px solid #ccc;">
                                    <span class="sdb-remove-gallery-image" style="position:absolute;top:-6px;right:-6px;background:#f00;color:#fff;padding:2px 5px;cursor:pointer;">×</span>
                                </div>
                        <?php endif;
                        endforeach; ?>
                    </div>

                    <br>
                    <button type="button" class="button sdb-add-gallery-images">Add Images</button>
                </div>
<?php
                break;


            case 'repeater':
                $saved_data = get_post_meta($post->ID, $meta_key, true);
                $saved_data = is_array($saved_data) ? $saved_data : [];

                echo '<div class="sdb-repeater" data-field-id="' . esc_attr($field_id) . '" data-name="' . esc_attr($meta_key) . '">';

                echo '<div class="sdb-repeater-rows">';

                if (!empty($saved_data)) {
                    foreach ($saved_data as $row_index => $row_data) {
                        echo '<div class="sdb-repeater-row" data-index="' . esc_attr($row_index) . '">';
                        echo '<p><em>Render nested fields here (row ' . esc_html($row_index + 1) . ')</em></p>';
                        echo '<button type="button" class="button sdb-remove-repeater-row">Remove</button>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="sdb-repeater-row" data-index="0">';
                    echo '<p><em>Render nested fields here (row 1)</em></p>';
                    echo '<button type="button" class="button sdb-remove-repeater-row">Remove</button>';
                    echo '</div>';
                }

                echo '</div>'; // .sdb-repeater-rows

                echo '<br><button type="button" class="button sdb-add-repeater-row">Add Row</button>';
                echo '</div>';
                break;


            /*-----------------------------------------------------
              Fallback
            -----------------------------------------------------*/
            default:
                printf(
                    '<input type="text" name="%1$s" value="%2$s" class="regular-text" />',
                    esc_attr($meta_key),
                    esc_attr($value)
                );
        }

        echo '</div><hr>';
    }

    echo '</div></div>';
}
