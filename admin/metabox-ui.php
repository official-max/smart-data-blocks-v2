<?php
// includes/add-metabox.php
if (!defined('ABSPATH')) exit;

// WordPress automatically passes $post as argument
if (is_string($post)) {
    $post = get_post();
}

// Safety check - if no post object, return
if (! $post instanceof WP_Post) {
    return;
}

// Get all field groups
$groups = sdb_get_all_field_groups();
if (!$groups) return;

foreach ($groups as $group) {
    $location_json = $group->location; // JSON string
    $location_rules = json_decode($location_json, true);

    // Check if this group should be shown for current post
    if (!sdb_show_metabox($post, $location_rules)) {
        continue;
    }

    // Add metabox for this group
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

// Save metabox data
// add_action('save_post', 'sdb_save_metabox_data');
function sdb_save_metabox_data($post_id)
{
    // Verify nonce
    if (
        !isset($_POST['sdb_metabox_nonce_field']) ||
        !wp_verify_nonce($_POST['sdb_metabox_nonce_field'], 'sdb_metabox_nonce')
    ) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Get all field groups
    $groups = sdb_get_all_field_groups();
    if (!$groups) return;

    foreach ($groups as $group) {
        $fields = sdb_get_existing_fields($group->id);
        if (!$fields) continue;

        foreach ($fields as $field) {
            $field_id = (int) $field->id;
            $meta_key = 'sdb_' . sanitize_key($field->field_name) . '_' . $field_id;

            // Handle different field types
            switch ($field->field_type) {
                case 'image':
                    // Save as attachment ID if possible
                    $value = isset($_POST[$meta_key]) ? $_POST[$meta_key] : '';
                    if (is_numeric($value)) {
                        update_post_meta($post_id, $meta_key, (int) $value);
                    } else {
                        update_post_meta($post_id, $meta_key, esc_url_raw($value));
                    }
                    break;

                case 'gallery':
                    // Save as comma-separated attachment IDs
                    $value = isset($_POST[$meta_key]) ? $_POST[$meta_key] : '';
                    $ids = array_filter(array_map('intval', explode(',', $value)));
                    update_post_meta($post_id, $meta_key, implode(',', $ids));
                    break;

                case 'editor':
                    // Sanitize WYSIWYG content
                    $value = isset($_POST[$meta_key]) ? wp_kses_post($_POST[$meta_key]) : '';
                    update_post_meta($post_id, $meta_key, $value);
                    break;

                case 'repeater':
                    // Handle repeater field data
                    if (isset($_POST[$meta_key]) && is_array($_POST[$meta_key])) {
                        $repeater_data = [];

                        // Sanitize each repeater row
                        foreach ($_POST[$meta_key] as $row) {
                            $sanitized_row = [];
                            foreach ($row as $key => $value) {
                                $sanitized_row[sanitize_text_field($key)] = sanitize_text_field($value);
                            }
                            $repeater_data[] = $sanitized_row;
                        }

                        update_post_meta($post_id, $meta_key, $repeater_data);
                    }
                    break;

                default:
                    // Default sanitization for text/textarea/file
                    $value = isset($_POST[$meta_key]) ? sanitize_text_field($_POST[$meta_key]) : '';
                    update_post_meta($post_id, $meta_key, $value);
            }
        }
    }
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

    echo '<div class="metabox_container"><div class="sdb-fields">';

    foreach ($fields as $field) {
        $field_id  = (int) $field->id;
        $meta_key  = 'sdb_' . sanitize_key($field->field_name) . '_' . $field_id;
        $value     = get_post_meta($post->ID, $meta_key, true);
        $type      = $field->field_type;
        $settings  = $field->field_settings ? json_decode($field->field_settings, true) : [];

        echo '<div class="sdb-field-row" data-field-id="' . esc_attr($field_id) . '">';
        echo '<label style="font-weight:bold;display:block;margin-bottom:4px;">' . esc_html($field->field_label) . '</label>';

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
                    '<textarea name="%1$s" rows="4" class="widefat">%2$s</textarea>',
                    esc_attr($meta_key),
                    esc_textarea($value)
                );
                break;

            case 'image':
                $saved_value = $value ?: '';
                $image_url = '';
                $attachment_id = is_numeric($saved_value) ? (int) $saved_value : 0;

                if ($attachment_id) {
                    $image_url = wp_get_attachment_url($attachment_id);
                } elseif ($saved_value) {
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

            case 'editor':
                wp_editor(
                    $value,
                    'sdb_field_' . $field_id,
                    [
                        'textarea_name' => $meta_key,
                        'media_buttons' => false,
                        'textarea_rows' => 6,
                    ]
                );
                break;

            case 'file':
                printf(
                    '<input type="text" name="%1$s" value="%2$s" class="regular-text" placeholder="File URL" />
                     <button type="button" class="button sdb-upload-file">Select File</button>',
                    esc_attr($meta_key),
                    esc_attr($value)
                );
                break;

            case 'gallery':
                $saved_value = $value ?: '';
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
                $sub_fields = $settings['sub_fields'] ?? [];

                echo '<div class="sdb-repeater" data-field-id="' . esc_attr($field_id) . '" data-name="' . esc_attr($meta_key) . '">';
                echo '<div class="sdb-repeater-rows">';

                if (!empty($saved_data)) {
                    foreach ($saved_data as $row_index => $row_data) {
                        echo '<div class="sdb-repeater-row" data-index="' . esc_attr($row_index) . '">';

                        foreach ($sub_fields as $sub_field) {
                            $sub_name = $meta_key . '[' . $row_index . '][' . $sub_field['name'] . ']';
                            $sub_value = $row_data[$sub_field['name']] ?? '';

                            echo '<div class="sdb-sub-field">';
                            echo '<label>' . esc_html($sub_field['label']) . '</label>';

                            switch ($sub_field['type']) {
                                case 'text':
                                    echo '<input type="text" name="' . esc_attr($sub_name) . '" value="' . esc_attr($sub_value) . '">';
                                    break;
                                case 'textarea':
                                    echo '<textarea name="' . esc_attr($sub_name) . '">' . esc_textarea($sub_value) . '</textarea>';
                                    break;
                                // Add more sub-field types as needed
                                default:
                                    echo '<input type="text" name="' . esc_attr($sub_name) . '" value="' . esc_attr($sub_value) . '">';
                            }

                            echo '</div>';
                        }

                        echo '<button type="button" class="button sdb-remove-repeater-row">Remove Row</button>';
                        echo '</div>';
                    }
                } else {
                    // Empty starter row
                    echo '<div class="sdb-repeater-row" data-index="0">';
                    foreach ($sub_fields as $sub_field) {
                        $sub_name = $meta_key . '[0][' . $sub_field['name'] . ']';

                        echo '<div class="sdb-sub-field">';
                        echo '<label>' . esc_html($sub_field['label']) . '</label>';
                        echo '<input type="text" name="' . esc_attr($sub_name) . '" value="">';
                        echo '</div>';
                    }
                    echo '<button type="button" class="button sdb-remove-repeater-row">Remove Row</button>';
                    echo '</div>';
                }

                echo '</div>';
                echo '<br><button type="button" class="button sdb-add-repeater-row">Add Row</button>';
                echo '</div>';
                break;

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

    // Enqueue media uploader script
    wp_enqueue_media();
    ?>
    <script>
        jQuery(document).ready(function($) {
            // Image upload
            $('.sdb-upload-image').click(function() {
                var button = $(this);
                var custom_uploader = wp.media({
                    title: 'Select Image',
                    library: {
                        type: 'image'
                    },
                    multiple: false
                }).on('select', function() {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    button.siblings('.sdb-image-url').val(attachment.id);
                    button.siblings('.sdb-preview').attr('src', attachment.url).show();
                }).open();
            });

            // Image remove
            $('.sdb-remove-image').click(function() {
                $(this).siblings('.sdb-image-url').val('');
                $(this).siblings('.sdb-preview').attr('src', '').hide();
            });

            // File upload
            $('.sdb-upload-file').click(function() {
                var button = $(this);
                var custom_uploader = wp.media({
                    title: 'Select File',
                    library: {
                        type: ''
                    }, // All file types
                    multiple: false
                }).on('select', function() {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    button.siblings('input[type="text"]').val(attachment.url);
                }).open();
            });

            // Gallery add images
            $('.sdb-add-gallery-images').click(function() {
                var container = $(this).closest('.sdb-gallery-field');
                var custom_uploader = wp.media({
                    title: 'Add Images to Gallery',
                    library: {
                        type: 'image'
                    },
                    multiple: true
                }).on('select', function() {
                    var ids = container.find('.sdb-gallery-ids').val();
                    var idArray = ids ? ids.split(',') : [];

                    custom_uploader.state().get('selection').each(function(attachment) {
                        idArray.push(attachment.id);
                        container.find('.sdb-gallery-preview').append(
                            '<div class="sdb-gallery-thumb" data-id="' + attachment.id + '" style="position:relative;">' +
                            '<img src="' + attachment.attributes.url + '" style="width:100px;height:auto;border:1px solid #ccc;">' +
                            '<span class="sdb-remove-gallery-image" style="position:absolute;top:-6px;right:-6px;background:#f00;color:#fff;padding:2px 5px;cursor:pointer;">×</span>' +
                            '</div>'
                        );
                    });

                    container.find('.sdb-gallery-ids').val(idArray.join(','));
                }).open();
            });

            // Gallery remove image
            $(document).on('click', '.sdb-remove-gallery-image', function() {
                var thumb = $(this).closest('.sdb-gallery-thumb');
                var container = thumb.closest('.sdb-gallery-field');
                var id = thumb.data('id');

                var ids = container.find('.sdb-gallery-ids').val().split(',');
                ids = ids.filter(function(existingId) {
                    return existingId != id;
                });
                container.find('.sdb-gallery-ids').val(ids.join(','));

                thumb.remove();
            });

            // Repeater add row
            $(document).on('click', '.sdb-add-repeater-row', function() {
                var repeater = $(this).closest('.sdb-repeater');
                var rows = repeater.find('.sdb-repeater-rows');
                var lastRow = rows.find('.sdb-repeater-row').last();
                var newIndex = lastRow.data('index') + 1;

                var newRow = lastRow.clone();
                newRow.attr('data-index', newIndex);

                // Update all input names
                newRow.find('[name]').each(function() {
                    var name = $(this).attr('name');
                    name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $(this).attr('name', name);
                    $(this).val('');
                });

                rows.append(newRow);
            });

            // Repeater remove row
            $(document).on('click', '.sdb-remove-repeater-row', function() {
                if ($(this).closest('.sdb-repeater').find('.sdb-repeater-row').length > 1) {
                    $(this).closest('.sdb-repeater-row').remove();
                } else {
                    alert('You must have at least one row');
                }
            });
        });
    </script>
<?php
}
