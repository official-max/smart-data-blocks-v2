<?php
// File: admin/ajax.php

if (!defined('ABSPATH')) exit;


/**
 * AJAX handler for location param -> value dropdown
 */
add_action('wp_ajax_sdb_get_location_values', 'sdb_get_location_values');

function sdb_get_location_values()
{
    if (!isset($_POST['param'])) {
        wp_send_json_error(['message' => 'Missing param']);
        return;
    }

    $param = sanitize_text_field($_POST['param']);
    $results = sdb_get_location_param_options($param);

    if ($results === null) {
        wp_send_json_error(['message' => 'Invalid param']);
    } else {
        wp_send_json($results);
    }
}
