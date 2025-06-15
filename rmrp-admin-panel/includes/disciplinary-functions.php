<?php
if (!defined('ABSPATH')) exit;

function rmrp_add_disciplinary_action($user_id, $issued_by, $type, $reason) {
    global $wpdb;
    $table = $wpdb->prefix . 'rmrp_disciplinary_actions';
    $date = current_time('mysql');

    // Сохраняем новое взыскание
    $wpdb->insert($table, [
        'user_id'     => $user_id,
        'issued_by'   => $issued_by,
        'type'        => $type,
        'reason'      => sanitize_text_field($reason),
        'date_issued' => $date
    ]);

    // Отправка уведомления пользователю
    $notification_action = $type === 'warning' ? 'warning_issued' : 'reprimand_issued';
    bp_notifications_add_notification([
        'user_id'           => $user_id,
        'item_id'           => 1,
        'component_name'    => 'activity',
        'component_action'  => $notification_action,
        'date_notified'     => bp_core_current_time(),
        'is_new'            => 1,
    ]);

    // Авто-конвертация: два предупреждения -> один выговор
    if ($type === 'warning') {
        $warnings = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND type = 'warning' ORDER BY date_issued ASC",
            $user_id
        ));

        if (count($warnings) >= 2) {
            // Удаляем два предупреждения
            $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id IN (%d, %d)", $warnings[0]->id, $warnings[1]->id));

            // Добавляем выговор
            $wpdb->insert($table, [
                'user_id'     => $user_id,
                'issued_by'   => 0, // Система
                'type'        => 'reprimand',
                'reason'      => 'Автоматическое преобразование двух предупреждений',
                'date_issued' => current_time('mysql')
            ]);

            // Уведомление о конверсии
            bp_notifications_add_notification([
                'user_id'           => $user_id,
                'item_id'           => 1,
                'component_name'    => 'activity',
                'component_action'  => 'reprimand_issued',
                'date_notified'     => bp_core_current_time(),
                'is_new'            => 1,
            ]);
        }
    }
}
