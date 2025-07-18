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
    $charset_collate = $wpdb->get_charset_collate();
    $table_groups = $wpdb->prefix . 'sdb_field_groups_v2';
    $table_fields = $wpdb->prefix . 'sdb_fields_v2';

    // Step 1: Create tables without foreign key
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
            UNIQUE KEY unique_field_per_group (group_id, field_name)
        ) ENGINE=InnoDB $charset_collate;
    ";

    // Step 2: Run dbDelta to create tables
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Step 3: Add foreign key constraint safely (if not exists)
    $fk_exists = $wpdb->get_var("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = '{$wpdb->prefix}sdb_fields_v2' 
          AND CONSTRAINT_NAME = 'fk_group_id'
          AND TABLE_SCHEMA = DATABASE()
    ");

    if (!$fk_exists) {
        $wpdb->query("
            ALTER TABLE $table_fields
            ADD CONSTRAINT fk_group_id
            FOREIGN KEY (group_id)
            REFERENCES $table_groups(id)
            ON DELETE CASCADE
        ");
    }
}
