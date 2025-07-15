<?php
// admin/settings-groups.php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_groups = $wpdb->prefix . 'sdb_field_groups_v2';

// Edit group data (if editing)
$editing_group = null;
if (isset($_GET['edit'])) {
    $group_id = absint($_GET['edit']); // absint only positve int number 
    $editing_group = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_groups WHERE id = %d", $group_id));
}

// Fetch all field groups
$groups = $wpdb->get_results("SELECT * FROM $table_groups ORDER BY group_order ASC");
?>

<!-- yaha message ayega -->
<div class="wrap">
    <h1>Smart Data Blocks - Field Groups</h1>
    <?php if (isset($_GET['added'])): ?>
        <div class="notice notice-success">
            <p>Group added successfully.</p>
        </div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="notice notice-success">
            <p>Group updated successfully.</p>
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="notice notice-warning">
            <p>Group deleted.</p>
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
        <div class="notice notice-error">
            <p>Key Slug must be unique.</p>
        </div>
    <?php endif; ?>
</div>

<div class="wrap">

    <div class="container">
        <!-- Notices --> <!-- agr edit krna hai toh edit group -->
        <h2><?= $editing_group ? 'Edit Group' : 'Add New Group'; ?></h2>

        <!-- Group Form -->
        <form method="post">
            <?php wp_nonce_field('sdb_group_action', 'sdb_group_nonce'); ?>
            <!-- yah seh action define hoga -->
            <input type="hidden" name="group_action" value="<?= $editing_group ? 'update' : 'add'; ?>">
            <?php if ($editing_group): ?>
                <input type="hidden" name="group_id" value="<?= esc_attr($editing_group->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th><label for="group_title">Title</label></th>
                    <td><input type="text" name="group_title" required value="<?= esc_attr($editing_group->title ?? '') ?>" class="regular-text"></td>
                </tr>

                <input type="hidden" name="group_key" required value="<?= esc_attr($editing_group->key_slug ?? '') ?>" class="regular-text">

                <!-- Location Rules -->
                <tr>
                    <th><label>Location Rules</label></th>
                    <td>
                        <div id="sdb-location-rules">
                            <?php
                            $rules = [];

                            // Decode existing rules if any
                            if (!empty($editing_group->location)) {
                                $rules = json_decode($editing_group->location, true);
                            }

                            // If no rules exist, show one empty rule row
                            if (!is_array($rules) || empty($rules)) {
                                $rules = [['param' => '', 'operator' => '==', 'value' => '']];
                            }

                            // List of available location rule parameters
                            $location_params = [
                                'all' => 'All',
                                'post_type'     => 'Post Type',
                                'post'          => 'Page / Post',
                                'page_template' => 'Page Template',
                                'user_role'     => 'User Role',
                                'taxonomy'      => 'Taxonomy',
                                'post_status'   => 'Post Status',
                                'post_format'   => 'Post Format',
                                'options_page'  => 'Options Page',
                            ];


                            // Render each rule row
                            foreach ($rules as $i => $rule):
                                $param = $rule['param'];
                                $selected_value = $rule['value'];
                                $options = $editing_group ? sdb_get_location_param_options($param) : [];
                            ?>
                                <div class="sdb-location-rule">
                                    <!-- Param Dropdown -->
                                    <select name="location[<?= $i ?>][param]">
                                        <option value="" disabled <?= $param === '' ? 'selected' : '' ?>>---- Select ----</option>
                                        <?php foreach ($location_params as $key => $label): ?>
                                            <option value="<?= esc_attr($key) ?>" <?= selected($param, $key, false) ?>>
                                                <?= esc_html($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <!-- Operator Dropdown -->
                                    <select name="location[<?= $i ?>][operator]">
                                        <?php
                                        $operators = ['==' => 'is equal to', '!=' => 'is not equal to'];
                                        foreach ($operators as $key => $label): ?>
                                            <option value="<?= esc_attr($key) ?>" <?= selected($rule['operator'], $key, false) ?>>
                                                <?= esc_html($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <!-- Value Field -->
                                    <select name="location[<?= $i ?>][value]" class="sdb-value-select" id="sdb-value-select-<?= $i ?>" required>
                                        <option value="" disabled <?= $selected_value === '' ? 'selected' : '' ?>>---- Select ----</option>
                                        <?php foreach ($options as $opt): ?>
                                            <option value="<?= esc_attr($opt['value']) ?>" <?= selected($selected_value, $opt['value'], false) ?>>
                                                <?= esc_html($opt['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="button remove-location-rule" id="remove-location-rule-<?= $i ?>" onclick="remove_button(this)">- Remove This Rule</button>

                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p><button type="button" class="button" id="add-location-rule">+ Add Rule</button></p>
                    </td>
                </tr>
            </table>

            <?php submit_button($editing_group ? 'Update Group' : 'Add Group'); ?>
        </form>

    </div>

    <!-- All Groups Table (New Section) -->
    <?php
    if ($groups) {
        echo sdb_get_all_groups_field($groups);
    } else {
        echo '<div class="notice notice-warning"><p>' . esc_html__('No groups found.', 'text-domain') . '</p></div>';
    }
    ?>
</div>