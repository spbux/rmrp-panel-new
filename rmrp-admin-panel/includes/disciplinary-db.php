<?php
if (!defined('ABSPATH')) exit;

// Эта функция подключается из главного файла плагина
function rmrp_create_disciplinary_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rmrp_disciplinary_actions';
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        issued_by BIGINT UNSIGNED NOT NULL,
        type ENUM('warning', 'reprimand') NOT NULL,
        reason TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        removed TINYINT(1) DEFAULT 0,
        removed_at DATETIME NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);
}
