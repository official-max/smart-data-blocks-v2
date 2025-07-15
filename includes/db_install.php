<?php

/**
 * DB Install: Creates necessary tables for Smart Data Blocks V2
 */

if (!defined('ABSPATH')) {
    exit; // 🔒 Direct access not allowed
}

function sdb_v2_create_tables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate(); // 📌 Character set from WordPress
    $table_groups = $wpdb->prefix . 'sdb_field_groups_v2'; // 🧱 Field groups table
    $table_fields = $wpdb->prefix . 'sdb_fields_v2';       // 📦 Fields table

    // 📄 SQL to create both tables
    $sql = "
        CREATE TABLE $table_groups (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            key_slug VARCHAR(255) NOT NULL UNIQUE,
            location LONGTEXT DEFAULT NULL,
            group_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB $charset_collate;

        CREATE TABLE $table_fields (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT(20) UNSIGNED NOT NULL,
            field_label VARCHAR(255) NOT NULL,
            field_name VARCHAR(255) NOT NULL,
            field_type VARCHAR(50) NOT NULL,
            field_order INT NOT NULL DEFAULT 0,
            field_settings LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_field_per_group (group_id, field_name),
            CONSTRAINT fk_group_id FOREIGN KEY (group_id)
                REFERENCES $table_groups(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB $charset_collate;
        ";


    // 🔧 Include dbDelta helper
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // 🔄 Run SQL and update if table already exists
    dbDelta($sql);
}
