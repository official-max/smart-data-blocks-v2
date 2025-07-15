<?php
/* includes/functions.php */

/**
 * Helper: Get all post types
 */
function give_post_type()
{
    $results = [];
    $post_types = get_post_types(['public' => true], 'objects');
    foreach ($post_types as $type) {
        $results[] = [
            'value' => 'post_type:' . $type->name,
            'label' => 'Post Type: ' . $type->labels->singular_name
        ];
    }
    return $results;
}

/**
 * Helper: Get all posts
 */
function give_posts()
{
    $results = [];
    $posts = get_posts([
        'post_type' => 'any',
        'numberposts' => -1,
        'post_status' => 'publish'
    ]);
    foreach ($posts as $post) {
        $results[] = [
            'value' => 'post:' . $post->ID,
            'label' => 'Post: ' . $post->post_title
        ];
    }
    return $results;
}

/**
 * Helper: Get all user roles
 */
function give_user_roles()
{
    $results = [];
    global $wp_roles;
    foreach ($wp_roles->roles as $role_key => $role) {
        $results[] = [
            'value' => 'user_role:' . $role_key,
            'label' => 'User Role: ' . $role['name']
        ];
    }
    return $results;
}

/**
 * Helper: Get all page templates
 */
function give_page_templates()
{
    $results = [];
    $templates = get_page_templates();
    foreach ($templates as $label => $filename) {
        $results[] = [
            'value' => 'page_template:' . $filename,
            'label' => 'Page Template: ' . $label
        ];
    }
    return $results;
}

/**
 * Helper: Get all post statuses
 */
function give_post_statuses()
{
    $results = [];
    $statuses = get_post_stati([], 'objects');
    foreach ($statuses as $status) {
        $results[] = [
            'value' => 'post_status:' . $status->name,
            'label' => 'Post Status: ' . $status->label
        ];
    }
    return $results;
}

/**
 * Helper: Get all taxonomies
 */
function give_taxonomies()
{
    $results = [];
    $taxonomies = get_taxonomies([], 'objects');
    foreach ($taxonomies as $tax) {
        $results[] = [
            'value' => 'taxonomy:' . $tax->name,
            'label' => 'Taxonomy: ' . $tax->label
        ];
    }
    return $results;
}

/**
 * Helper: Get all post formats
 */
function give_post_formats()
{
    $results = [];
    $formats = get_post_format_strings();
    foreach ($formats as $slug => $label) {
        $results[] = [
            'value' => 'post_format:' . $slug,
            'label' => 'Post Format: ' . $label
        ];
    }
    return $results;
}

function sdb_get_location_param_options($param = 'all')
{
    $results = [];

    switch ($param) {
        case 'all':
            $results = array_merge(
                give_post_type(),
                give_posts(),
                give_user_roles(),
                give_page_templates(),
                give_post_statuses(),
                give_taxonomies(),
                give_post_formats()
            );
            break;

        case 'post_type':
            $results = give_post_type();
            break;

        case 'post':
            $results = give_posts();
            break;

        case 'user_role':
            $results = give_user_roles();
            break;

        case 'page_template':
            $results = give_page_templates();
            break;

        case 'post_status':
            $results = give_post_statuses();
            break;

        case 'taxonomy':
            $results = give_taxonomies();
            break;

        case 'post_format':
            $results = give_post_formats();
            break;

        default:
            return null;
    }

    return $results;
}



function sdb_format_location_value($location_json)
{
    $locations = json_decode($location_json, true);

    if (empty($locations) || empty($locations[0]['value'])) {
        return '—';
    }

    // Type labels
    $type_labels = [
        'all'           => 'All',
        'post_type'     => 'Post Type',
        'post'          => 'Page / Post',
        'page_template' => 'Page Template',
        'user_role'     => 'User Role',
        'taxonomy'      => 'Taxonomy',
        'post_status'   => 'Post Status',
        'post_format'   => 'Post Format',
        'options_page'  => 'Options Page',
    ];

    $operator_symbols = [
        '==' => '→',
        '!=' => '≠',
    ];

    $output = [];

    foreach ($locations as $rule) {
        if (empty($rule['param']) || empty($rule['operator']) || empty($rule['value'])) {
            continue;
        }

        $param_type = $rule['param'];
        $operator = $rule['operator'];
        $value = $rule['value'];


        $type_label = $type_labels[$param_type] ?? ucfirst(str_replace('_', ' ', $param_type));
        $display_value = '';


        // Support "type:value" format (e.g., post:42)
        if (strpos($value, ':') !== false) {
            list($type, $param) = explode(':', $value, 2); // explode(separator, string, limit); limit (3rd argument): Maximum kitne parts banane hain.
        } else {
            $type = $param_type;
            $param = $value;
        }


        switch ($type) {
            case 'post':
            case 'page':
                $post = get_post((int)$param);
                $display_value = $post ? $post->post_title : 'Post not found';
                break;

            case 'page_template':
                $templates = wp_get_theme()->get_page_templates();
                $display_value = $templates[$param] ?? $param;
                break;

            case 'user_role':
                global $wp_roles;
                $display_value = $wp_roles->roles[$param]['name'] ?? $param;
                break;

            case 'taxonomy':
                $taxonomy = get_taxonomy($param);
                $display_value = $taxonomy ? $taxonomy->labels->singular_name : $param;
                break;

            case 'post_status':
                $statuses = get_post_statuses();
                $display_value = $statuses[$param] ?? $param;
                break;

            case 'post_format':
                $format = get_post_format_string($param);
                $display_value = $format ?: $param;
                break;

            case 'options_page':
                $display_value = $param;
                break;

            case 'post_type':
                $post_type_obj = get_post_type_object($param);
                $display_value = $post_type_obj ? $post_type_obj->labels->singular_name : $param;
                break;

            default:
                $display_value = $param;
                break;
        }

        $symbol = $operator_symbols[$operator] ?? $operator;
        $output[] = esc_html("{$type_label} {$symbol} {$display_value}");
    }

    return implode('<br>', $output);
}



/**
 * Displays all field groups
 */

function sdb_get_all_groups_field($groups, $per_page = 20, $current_page = 1, $total_items = 0)
{
    ob_start();
?>

    <hr class="wp-header-end">

    <div class="container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-primary"><?php esc_html_e('ID', 'text-domain'); ?></th>
                    <th scope="col"><?php esc_html_e('Title', 'text-domain'); ?></th>
                    <th scope="col"><?php esc_html_e('Key', 'text-domain'); ?></th>
                    <th scope="col"><?php esc_html_e('Location', 'text-domain'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'text-domain'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($groups)) : ?>
                    <?php foreach ($groups as $group) : ?>
                        <tr>
                            <td class="column-primary" data-colname="<?php esc_attr_e('ID', 'text-domain'); ?>">
                                <?php echo (int) $group->id; ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Title', 'text-domain'); ?>">
                                <strong><?php echo esc_html($group->title); ?></strong>
                            </td>
                            <td data-colname="<?php esc_attr_e('Key', 'text-domain'); ?>">
                                <code><?php echo esc_html($group->key_slug); ?></code>
                            </td>
                            <td data-colname="<?php esc_attr_e('Location', 'text-domain'); ?>">
                                <?php echo sdb_format_location_value($group->location); ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Actions', 'text-domain'); ?>">
                                <div class="row-actions">
                                    <!-- Edit Button -->
                                    <span class="edit">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=sdb_field_groups&edit=' . $group->id)); ?>" class="button">
                                            <?php esc_html_e('Edit', 'text-domain'); ?>
                                        </a>
                                    </span>

                                    <!-- Manage Fields Button -->
                                    <span class="manage">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=sdb_manage_fields&group_id=' . $group->id)); ?>" class="button">
                                            <?php esc_html_e('Manage Fields', 'text-domain'); ?>
                                        </a>
                                    </span>

                                    <!-- Delete Button -->
                                    <span class="delete">
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=sdb_field_groups&delete=' . $group->id), 'delete_group_' . $group->id)); ?>"
                                            class="button button-link-delete"
                                            onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this group?', 'text-domain'); ?>')">
                                            <?php esc_html_e('Delete', 'text-domain'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No groups found.', 'text-domain'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_items > $per_page) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $total_pages = ceil($total_items / $per_page);
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous'),
                        'next_text' => __('Next &raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>


<?php
    return ob_get_clean();
}


/**
 * Displays all fields of a group in a table format // I think not use ----
 */
function sdb_get_all_fields($fields)
{
    ob_start();
?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="column-primary"><?php esc_html_e('Label', 'text-domain'); ?></th>
                <th scope="col"><?php esc_html_e('Name', 'text-domain'); ?></th>
                <th scope="col"><?php esc_html_e('Type', 'text-domain'); ?></th>
                <th scope="col"><?php esc_html_e('Order', 'text-domain'); ?></th>
                <th scope="col"><?php esc_html_e('Actions', 'text-domain'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($fields)) : ?>
                <?php foreach ($fields as $field) :
                    $config = !empty($field->field_settings) ? json_decode($field->field_settings, true) : [];
                ?>
                    <tr>
                        <td class="column-primary" data-colname="<?php esc_attr_e('Label', 'text-domain'); ?>">
                            <strong><?php echo esc_html($field->field_label); ?></strong>
                        </td>
                        <td data-colname="<?php esc_attr_e('Name', 'text-domain'); ?>">
                            <code><?php echo esc_html($field->field_name); ?></code>
                        </td>
                        <td data-colname="<?php esc_attr_e('Type', 'text-domain'); ?>">
                            <?php echo esc_html($field->field_type); ?>
                        </td>
                        <td data-colname="<?php esc_attr_e('Order', 'text-domain'); ?>">
                            <?php echo (int) $field->field_order; ?>
                        </td>
                        <td data-colname="<?php esc_attr_e('Actions', 'text-domain'); ?>">
                            <div class="row-actions">
                                <!-- Edit Button -->
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=sdb_manage_fields&group_id=' . $field->group_id . '&edit_field=' . $field->id)); ?>" class="button">
                                        <?php esc_html_e('Edit', 'text-domain'); ?>
                                    </a>
                                </span>

                                <!-- Delete Button -->
                                <span class="delete">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=sdb_manage_fields&group_id=' . $field->group_id . '&delete_field=' . $field->id), 'delete_field_' . $field->id)); ?>"
                                        class="button button-link-delete"
                                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this field?', 'text-domain'); ?>')">
                                        <?php esc_html_e('Delete', 'text-domain'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No fields found.', 'text-domain'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php
    return ob_get_clean();
}


/**
 * Get field group(s).
 * - Pass ID → returns single group
 * - No ID → returns all groups (ordered)
 */
function sdb_get_field_group($group_id = null)
{
    global $wpdb;
    $table_groups = SDB_TABLE_GROUPS;

    if ($group_id) {
        // Fetch a single group by ID
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_groups WHERE id = %d",
                $group_id
            )
        );
    } else {
        // Fetch all groups if no ID is provided
        return $wpdb->get_results(
            "SELECT * FROM $table_groups ORDER BY group_order ASC"
        );
    }
}

/**
 * Get fields by group ID
 * - Pass group ID → returns all fields (ordered)
 */
function sdb_get_existing_fields($group_id)
{
    global $wpdb;
    $table_fields = SDB_TABLE_FIELDS;

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_fields WHERE group_id = %d ORDER BY field_order ASC",
            $group_id
        )
    );
}


/**
 * Renders the field creator form
 */
/*
function sdb_render_field_creator($group_id, $fields = [])
{
    ob_start();
?>
    <div class="sdb-field-creator">
        <?php if (!empty($fields)) : ?>
            <?php foreach ($fields as $index => $field) : ?>
                <div class="sdb-field-row" data-index="<?= $index; ?>" data-group-id="<?= $group_id; ?>">
                    <input type="hidden" name="fields[<?= $index ?>][field_id]" value="<?= esc_attr($field->id); ?>">

                    <div>
                        <input type="text"
                            name="fields[<?= $index ?>][field_label]"
                            value="<?= esc_attr($field->field_label); ?>"
                            placeholder="Field Label"
                            required>
                    </div>

                    <div>
                        <input type="text"
                            name="fields[<?= $index ?>][field_name]"
                            value="<?= esc_attr($field->field_name); ?>"
                            placeholder="Field Name"
                            required>
                    </div>

                    <div>
                        <select name="fields[<?= $index ?>][field_type]" required>
                            <option value="text" <?php selected($field->field_type, 'text'); ?>>Text</option>
                            <option value="textarea" <?php selected($field->field_type, 'textarea'); ?>>Textarea</option>
                            <option value="image" <?php selected($field->field_type, 'image'); ?>>Image</option>
                            <option value="gallery" <?php selected($field->field_type, 'gallery'); ?>>Gallery</option>
                            <option value="editor" <?php selected($field->field_type, 'editor'); ?>>Editor</option>
                            <option value="repeater" <?php selected($field->field_type, 'repeater'); ?>>Repeater</option>
                        </select>
                    </div>

                    <div>
                        <a class="button button-danger"
                            href="<?php echo esc_url(admin_url('admin.php?page=sdb_manage_fields&group_id=' . intval($group_id) . '&delete_field=' . intval($field->id))); ?>"
                            onclick="return confirm('Are you sure you want to delete this field?');">
                            <span class="dashicons dashicons-trash"></span> Delete
                        </a>
                    </div>

                    <?php if ($field->field_type === 'repeater') :
                        $field_settings = json_decode($field->field_settings, true);
                        $sub_fields = $field_settings['sub_fields'] ?? [];
                    ?>
                        <div class="sdb-repeater-settings" data-path="[0,2]">
                            <div class="sdb-repeater-header">
                                <h4><span class="dashicons dashicons-admin-generic"></span> Repeater Settings</h4>
                            </div>

                            <div class="sdb-repeater-options">
                                <div class="sdb-option-row">
                                    <label>Minimum Rows:</label>
                                    <input type="number" name="fields[<?= $index ?>][repeater_min]"
                                        value="<?= esc_attr($field_settings['min_rows'] ?? 1) ?>" min="1">
                                </div>

                                <div class="sdb-option-row">
                                    <label>Maximum Rows:</label>
                                    <input type="number" name="fields[<?= $index ?>][repeater_max]"
                                        value="<?= esc_attr($field_settings['max_rows'] ?? 10) ?>" min="1">
                                </div>
                            </div>

                            <div class="sdb-sub-fields-container" data-depth="0">
                                <h5>Sub Fields</h5>
                                <div class="sdb-sub-fields-list">
                                    <?php foreach ($sub_fields as $sub_index => $sub) : ?>
                                        <div class="sdb-sub-field sdb-sub-field-row" data-parent-index="<?php echo $index; ?>">
                                            <input type="text"
                                                name="fields[<?= $index ?>][repeater_sub_fields][<?= $sub_index ?>][label]"
                                                value="<?= esc_attr($sub['label'] ?? '') ?>"
                                                placeholder="Label">

                                            <input type="text"
                                                name="fields[<?= $index ?>][repeater_sub_fields][<?= $sub_index ?>][name]"
                                                value="<?= esc_attr($sub['name'] ?? '') ?>"
                                                placeholder="Name">

                                            <select name="fields[<?= $index ?>][repeater_sub_fields][<?= $sub_index ?>][type]">
                                                <option value="text" <?php selected($sub['type'] ?? '', 'text'); ?>>Text</option>
                                                <option value="textarea" <?php selected($sub['type'] ?? '', 'textarea'); ?>>Textarea</option>
                                                <option value="image" <?php selected($sub['type'] ?? '', 'image'); ?>>Image</option>
                                                <option value="editor" <?php selected($sub['type'] ?? '', 'editor'); ?>>Editor</option>
                                                <option value="gallery" <?php selected($sub['type'] ?? '', 'gallery'); ?>>Gallery</option>
                                                <option value="repeater" <?php selected($sub['type'] ?? '', 'repeater'); ?>>Repeater</option>
                                            </select>

                                            <button type="button" class="button sdb-remove-sub-field">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="button" class="button button-primary sdb-add-sub-field">
                                    <span class="dashicons dashicons-plus"></span> Add Sub Field
                                </button>
                            </div>

                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <!-- Default empty field -->
            <div class="sdb-field-row">
                <div>
                    <input type="text" name="fields[0][field_label]" placeholder="Field Label" required>
                </div>

                <div>
                    <input type="text" name="fields[0][field_name]" placeholder="Field Name" required>
                </div>

                <div>
                    <select name="fields[0][field_type]" required>
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="image">Image</option>
                        <option value="repeater">Repeater</option>
                    </select>
                </div>

                <div>
                    <button type="button" class="button remove-field">
                        <span class="dashicons dashicons-trash"></span> Remove
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}
    */


function sdb_render_field_creator($group_id, $fields = [])
{
    ob_start();
?>
    <div class="sdb-field-creator">
        <?php if (!empty($fields)) : ?>

            <?php foreach ($fields as $index => $field_obj) :
                $field_array = [
                    'id' => $field_obj->id,
                    'label' => $field_obj->field_label,
                    'name' => $field_obj->field_name,
                    'type' => $field_obj->field_type,
                    'settings' => json_decode($field_obj->field_settings ?? '{}', true),
                ];

                echo sdb_render_field($field_array, "fields[{$index}]", $index, false, 0, $group_id);
            endforeach; ?>

        <?php else : ?>
            <?= sdb_render_field([], 'fields[0]', 0); ?>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}



function sdb_render_field($field, $name_prefix, $index = 0, $is_sub = false, $depth = 0, $group_id = 0)
{
    ob_start();

    $field_type     = $field['type'] ?? 'text';
    $field_label    = $field['label'] ?? '';
    $field_name     = $field['name'] ?? '';
    $field_id       = $field['id'] ?? '';
    $field_settings = $field['settings'] ?? [];
    $sub_fields     = $field_settings['sub_fields'] ?? [];

    $row_class = $is_sub ? 'sdb-sub-field-row' : 'sdb-field-row';
?>
    <div class="<?= esc_attr($row_class); ?>"
        data-index="<?= esc_attr($index); ?>"
        data-depth="<?= esc_attr($depth); ?>"
        data-path="<?= esc_attr($name_prefix); ?>">

        <?php if (!$is_sub && $field_id): ?>
            <input type="hidden" name="<?= $name_prefix; ?>[field_id]" value="<?= esc_attr($field_id); ?>">
        <?php endif; ?>
        <input type="hidden" name="<?= $name_prefix; ?>[field_order]" value="<?= esc_attr($index); ?>">

        <input type="text" name="<?= $name_prefix; ?>[label]" value="<?= esc_attr($field_label); ?>" placeholder="Label" required>
        <input type="text" name="<?= $name_prefix; ?>[name]" value="<?= esc_attr($field_name); ?>" placeholder="Name" required>

        <select name="<?= $name_prefix; ?>[type]" class="sdb-field-type">
            <option value="text" <?php selected($field_type, 'text'); ?>>Text</option>
            <option value="textarea" <?php selected($field_type, 'textarea'); ?>>Textarea</option>
            <option value="image" <?php selected($field_type, 'image'); ?>>Image</option>
            <option value="gallery" <?php selected($field_type, 'gallery'); ?>>Gallery</option>
            <option value="editor" <?php selected($field_type, 'editor'); ?>>Editor</option>
            <option value="repeater" <?php selected($field_type, 'repeater'); ?>>Repeater</option>
            <option value="file" <?php selected($field_type, 'file'); ?>>File</option>
        </select>

        <?php if ($field_type === 'repeater'): ?>
            <div class="sdb-repeater-settings">
                <div class="sdb-repeater-options">
                    <div class="sdb-option-row">
                        <label>Min Rows:</label>
                        <input type="number" name="<?= $name_prefix; ?>[settings][min_rows]" value="<?= esc_attr($field_settings['min_rows'] ?? 1); ?>" min="0">
                    </div>
                    <div class="sdb-option-row">
                        <label>Max Rows:</label>
                        <input type="number" name="<?= $name_prefix; ?>[settings][max_rows]" value="<?= esc_attr($field_settings['max_rows'] ?? 10); ?>" min="1">
                    </div>
                </div>

                <div class="sdb-sub-fields-container">
                    <h5>Sub Fields</h5>
                    <div class="sdb-sub-fields-list">
                        <?php foreach ($sub_fields as $sub_index => $sub_field):
                            $sub_prefix = "{$name_prefix}[settings][sub_fields][{$sub_index}]";
                            echo sdb_render_field($sub_field, $sub_prefix, $sub_index, true, $depth + 1);
                        endforeach; ?>
                    </div>

                    <button type="button" class="button button-primary sdb-add-sub-field" data-depth="<?= esc_attr($depth + 1); ?>">
                        <span class="dashicons dashicons-plus"></span> Add Sub Field
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Only allow delete if it's not the first root-level field -->
        <?php if ($is_sub || $depth > 0): ?>
            <button type="button" class="button sdb-remove-sub-field">
                <span class="dashicons dashicons-trash"></span>
            </button>
        <?php elseif (!empty($field_id)): ?>
            <a href="admin.php?page=sdb_manage_fields&delete_field=<?= intval($field_id); ?>&group_id=<?= intval($group_id); ?>"
                class="button button-danger">
                <span class="dashicons dashicons-trash"></span> Delete
            </a>
        <?php else: ?>
            <button type="button" class="button remove-field button-danger">
                <span class="dashicons dashicons-trash"></span> Delete
            </button>
        <?php endif; ?>

    </div>
<?php

    return ob_get_clean();
}




/**
 * Recursive sub-field processor for nested repeater fields
 */
/*
function sdb_process_sub_fields($sub_fields_raw)
{
    $sub_fields = [];

    foreach ($sub_fields_raw as $sub) {
        $sanitized = [
            'label' => sanitize_text_field($sub['label'] ?? ''),
            'name'  => sanitize_title($sub['name'] ?? ''),
            'type'  => sanitize_text_field($sub['type'] ?? 'text'),
        ];

        // If this sub-field is a repeater, process its settings and sub-sub-fields
        if ($sanitized['type'] === 'repeater' && isset($sub['settings']['sub_fields'])) {
            $sanitized['settings'] = [
                'min_rows' => intval($sub['settings']['min_rows'] ?? 1),
                'max_rows' => intval($sub['settings']['max_rows'] ?? 10),
                'sub_fields' => sdb_process_sub_fields($sub['settings']['sub_fields']),
                
            ];
        }

        $sub_fields[] = $sanitized;
    }

    return $sub_fields;
}*/

/**
 * Recursive sub-field processor for nested repeater fields
 * Adds `field_order` based on the current DOM / POST order.
 */
function sdb_process_sub_fields($sub_fields_raw)
{
    $processed = [];
    $taken = []; // ✅ define empty array for tracking used names

    foreach (array_values($sub_fields_raw) as $order => $sub) {
        $base   = sanitize_title($sub['name'] ?? '');
        $unique = sdb_unique_name($base ?: 'field', $taken);  // ensure unique

        $sanitized = [
            'label'       => sanitize_text_field($sub['label'] ?? ''),
            'name'        => $unique,
            'type'        => sanitize_text_field($sub['type'] ?? 'text'),
            'field_order' => $order,
        ];

        // 🔁 Nested repeater inside repeater
        if ($sanitized['type'] === 'repeater' && !empty($sub['settings']['sub_fields'])) {
            $sanitized['settings'] = [
                'min_rows'   => intval($sub['settings']['min_rows'] ?? 1),
                'max_rows'   => intval($sub['settings']['max_rows'] ?? 10),
                'sub_fields' => sdb_process_sub_fields($sub['settings']['sub_fields'])
            ];
        }

        $taken[] = $unique; // ✅ track name so future ones don't conflict
        $processed[] = $sanitized;
    }

    return $processed;
}




/**
 * Ensure field_name is unique within a group.
 *
 * @param string $base_name  Sanitized slug from label.
 * @param int    $group_id   Current group ID.
 * @param int    $exclude_id Current field ID (0 for new).
 * @return string Unique slug (my_field, my_field_2, my_field_3 …)
 */
function sdb_get_unique_field_name($base_name, $group_id, $exclude_id = 0)
{
    global $wpdb;
    $table_fields = $wpdb->prefix . 'sdb_fields_v2';

    $name = $base_name;
    $suffix = 2;

    // Loop until we find a name that doesn’t exist (or exists only for the same $exclude_id)
    while (
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_fields
                 WHERE group_id = %d AND field_name = %s AND id <> %d",
                $group_id,
                $name,
                $exclude_id
            )
        )
    ) {
        $name = $base_name . '_' . $suffix;
        $suffix++;
    }

    return $name;
}



/**
 * Unique‑ify a name inside a flat array of names [for reapter]
 */
function sdb_unique_name($base, &$taken)
{
    $name  = $base;
    $count = 2;
    while (in_array($name, $taken, true)) {
        $name = $base . '_' . $count;
        $count++;
    }
    $taken[] = $name;
    return $name;
}


/*************************************************************
 **********************Metabox Function**********************
 *************************************************************/

function sdb_get_all_field_groups()
{
    global $wpdb;
    $table = $wpdb->prefix . 'sdb_field_groups_v2';

    return $wpdb->get_results("SELECT * FROM $table ORDER BY group_order ASC");
}


function sdb_show_metabox($post, $rules)
{
    if (!$post || empty($rules)) return false;

    foreach ($rules as $rule) {
        $param    = $rule['param'] ?? '';
        $operator = $rule['operator'] ?? '==';
        $value    = $rule['value'] ?? '';

        // "type:value" split (helpers always save like that)
        if (strpos($value, ':') !== false) {
            [$type, $check] = explode(':', $value, 2);
        } else {
            $type  = $param;   // fallback, though UI always gives type:value
            $check = $value;
        }

        // Figure out the actual property of current post/user
        switch ($type) {
            case 'post_type':
                $actual = $post->post_type;
                break;

            case 'post':
                $actual = (int) $post->ID;
                break;

            case 'user_role':
                $user   = wp_get_current_user();
                $actual = $user->roles[0] ?? '';
                break;

            case 'page_template':
                $actual = get_page_template_slug($post->ID);
                break;

            case 'post_status':
                $actual = $post->post_status;
                break;

            case 'taxonomy':
                // Check if post has ANY term of that taxonomy
                $actual = wp_get_object_terms($post->ID, $check) ? $check : '';
                break;

            case 'post_format':
                $actual = get_post_format($post->ID) ?: 'standard';
                break;

            default:
                $actual = null; // unknown rule -> ignore
        }

        // Evaluate operator
        $match = ($operator === '!=')
            ? ($actual != $check)
            : ($actual == $check);

        // If any rule fails (AND logic), metabox nahi dikhega
        if (!$match) return false;
    }

    return true; // sab rules pass → show metabox
}



function sdb_render_group_metabox($group, $post)
{
    $fields = sdb_get_existing_fields($group->id);

    echo '<div class="sdb-fields">';
    foreach ($fields as $field) {
        // Render field input based on type
        // Example:
        echo '<p><label>' . esc_html($field->field_label) . '</label>';
        echo '<input type="text" name="sdb_fields[' . esc_attr($field->id) . ']" value="' . esc_attr(get_post_meta($post->ID, 'sdb_field_' . $field->id, true)) . '"></p>';
    }
    echo '</div>';
}
