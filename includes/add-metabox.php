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
