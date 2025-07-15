<?php
// admin/settings-fields.php
if (!defined('ABSPATH')) exit;

global $wpdb;
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

echo '<div class="wrap">';

if ($group_id > 0) {
    // ✅ Specific group data
    $group = sdb_get_field_group($group_id);

    // Get existing fields
    $fields = sdb_get_existing_fields($group_id);
    echo '<h2>Manage Fields for Group #' . (int) $group_id . ' - "' . esc_html($group->title) . '"</h2>';
    // ✅ Success Message
    if (isset($_GET['saved'])) {
        echo '<div class="notice notice-success"><p>' . esc_html__('Field saved!', 'smart-data-blocks') . '</p></div>';
    }
?>
    <form method="post" id="sdb-fields-form">
        <?php wp_nonce_field('sdb_field_action', 'sdb_field_nonce'); ?>
        <input type="hidden" name="group_id" value="<?= esc_attr($group_id); ?>" />

        <div id="sdb-fields-container">
            <?php echo sdb_render_field_creator($group_id, $fields); ?>
        </div>

        <!-- Add New Field button -->
        <p><button type="button" class="button button-primary" id="sdb-add-field"> <span class="dashicons dashicons-plus"></span> Add New Field </button></p>
        <p><input type="submit" class="button button-primary" value="Save Fields"></p>
    </form>
<?php } else {
    // ✅ All groups + their fields
    $groups = sdb_get_field_group();
    echo '<h2>All Fields from All Groups</h2>';
    if ($groups) {
        echo sdb_get_all_groups_field($groups);
    } else {
        echo '<div class="notice notice-warning"><p>No groups found.</p></div>';
        // ✅ Add New Group button
        echo '<a href="' . admin_url('admin.php?page=sdb_field_groups') . '" class="button button-primary">';
        echo '+ Add New Group';
        echo '</a>';
    }
}
echo '</div>';
