<?php
/**
 * Plugin Name: RMRP Admin Panel
 * Description: Панель администратора RMRP с настраиваемыми уведомлениями и дисциплинарной системой.
 * Version: 1.0
 * Author: spbux
 */

if (!defined('ABSPATH')) exit;

// ✅ Регистрируем хук активации здесь
register_activation_hook(__FILE__, 'rmrp_create_disciplinary_table');

function rmrp_create_disciplinary_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'rmrp_disciplinary_actions';
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Создание таблицы (если не существует)
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        issued_by BIGINT UNSIGNED NOT NULL,
        type ENUM('warning', 'reprimand') NOT NULL,
        reason TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        removed TINYINT(1) DEFAULT 0,
        removed_at DATETIME NULL,
        removal_reason TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);

    // Добавим колонку is_active вручную (если уже есть таблица)
    $columns = $wpdb->get_col("SHOW COLUMNS FROM $table LIKE 'is_active'");
    if (empty($columns)) {
        $wpdb->query("ALTER TABLE $table ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER removed;");
    }
}


// Подключение файлов
require_once plugin_dir_path(__FILE__) . 'includes/disciplinary-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/disciplinary-save.php';
require_once plugin_dir_path(__FILE__) . 'includes/disciplinary-history.php';
require_once plugin_dir_path(__FILE__) . 'includes/disciplinary-tab.php';
require_once plugin_dir_path(__FILE__) . 'includes/profile-customization.php';

// Добавление пунктов меню
add_action('admin_menu', function () {
    add_menu_page('RMRP PANEL', 'RMRP PANEL', 'manage_options', 'rmrp-panel', function () {
        echo '<div class="wrap"><h1>Добро пожаловать в RMRP PANEL</h1></div>';
    }, 'dashicons-shield', 25);

    add_submenu_page(
        'rmrp-panel',
        'Уведомления',
        'Уведомления',
        'manage_options',
        'rmrp-notifications',
        'rmrp_render_notifications'
    );
});





// Рендер страницы уведомлений
function rmrp_render_notifications() {
    require_once plugin_dir_path(__FILE__) . 'admin/notifications.php';
}

// Фильтр текстов уведомлений
add_filter('bb_notifications_get_component_notification', function ($desc, $item_id, $secondary_item_id, $total_items, $format, $action, $component, $id, $context) {
    $custom = get_option('rmrp_notification_texts', []);
    return $custom[$action] ?? $desc;
}, 10, 9);

// Логика уведомлений по повышению
add_action('bp_init', function () {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $role = trim(xprofile_get_field_data('Должность администратора', $user_id));
    $mod_date = xprofile_get_field_data('Дата вступления', $user_id);
    $prom_date = xprofile_get_field_data('Дата последнего повышения', $user_id);

    $map = [
        'Главный администратор' => 8,
        'Заместитель главного администратора' => 7,
        'Главный куратор младшей администрации' => 6,
        'Главный куратор государственных организаций' => 6,
        'Главный куратор криминальных организаций' => 6,
        'Главный куратор медиа-администрации' => 6,
        'Помощник главного куратора младшей администрации' => 5,
        'Помощник главного куратора государственных организаций' => 5,
        'Помощник главного куратора криминальных организаций' => 5,
        'Администратор 4-го уровня' => 4,
        'Администратор 3-го уровня' => 3,
        'Администратор 2-го уровня' => 2,
        'Администратор 1-го уровня' => 1,
        'Модератор' => 0,
    ];

    $level = $map[$role] ?? null;
    if (!is_numeric($level) || $level >= 4) return;

    $base = ($level === 0) ? $mod_date : $prom_date;
    if (!preg_match('/\d{4}-\d{2}-\d{2}/', $base)) return;

    try {
        $d = new DateTime($base);
        $next = (clone $d)->add(new DateInterval('P' . ($level === 0 ? 7 : 14) . 'D'));
        $now = new DateTime('now', new DateTimeZone(wp_timezone_string()));
        $ready = $now >= $next;
    } catch (Exception $e) {
        return;
    }

    $existing = BP_Notifications_Notification::get([
        'user_id' => $user_id,
        'component_name' => 'activity',
        'component_action' => 'promotion_ready',
    ]);

    if ($ready && empty($existing)) {
        bp_notifications_add_notification([
            'user_id' => $user_id,
            'item_id' => 1,
            'component_name' => 'activity',
            'component_action' => 'promotion_ready',
            'date_notified' => bp_core_current_time(),
            'is_new' => 1,
        ]);
    }

    if (!$ready && !empty($existing)) {
        foreach ($existing as $n) {
            bp_notifications_delete_notification_by_id($n->id);
        }
    }
});

add_action('bp_enqueue_scripts', 'rmrp_enqueue_disciplinary_styles');
function rmrp_enqueue_disciplinary_styles() {
    if (!bp_is_user()) return; // только для профилей
    wp_enqueue_style('rmrp-disciplinary-css', plugin_dir_url(__FILE__) . 'assets/css/disciplinary.css');
}
