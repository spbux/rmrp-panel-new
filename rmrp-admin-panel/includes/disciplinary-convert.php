<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (
        !is_user_logged_in() ||
        !isset($_POST['rmrp_disciplinary_type'], $_POST['rmrp_disciplinary_target']) ||
        $_POST['rmrp_disciplinary_type'] !== 'warning'
    ) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'rmrp_disciplinary_actions';
    $target_id = (int) $_POST['rmrp_disciplinary_target'];

    // Получаем 2 активных предупреждения
    $warnings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table 
         WHERE user_id = %d AND type = 'warning' AND removed = 0 
         ORDER BY created_at ASC 
         LIMIT 2",
        $target_id
    ));

    if (count($warnings) < 2) return;

    $now = current_time('mysql');
    $system_id = 0; // ID системы

    // Снимаем оба предупреждения
    foreach ($warnings as $warn) {
        $wpdb->update($table, [
            'removed'        => 1,
            'removed_at'     => $now,
            'removal_reason' => 'Автоматическая замена 2-х предупреждений выговором',
        ], ['id' => $warn->id]);
    }

    // Добавляем выговор
    $wpdb->insert($table, [
        'user_id'    => $target_id,
        'issued_by'  => $system_id,
        'type'       => 'reprimand',
        'reason'     => 'Автоматическая замена 2-х предупреждений выговором',
        'created_at' => $now,
        'removed'    => 0,
    ]);

    // Уведомление о выговоре
    bp_notifications_add_notification([
        'user_id'           => $target_id,
        'item_id'           => 1,
        'secondary_item_id' => $system_id,
        'component_name'    => 'activity',
        'component_action'  => 'reprimand_issued',
        'date_notified'     => bp_core_current_time(),
        'is_new'            => 1,
    ]);
});
