<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (!is_user_logged_in()) return;

    if (
        !isset($_POST['rmrp_disciplinary_type'], 
               $_POST['rmrp_disciplinary_reason'], 
               $_POST['rmrp_disciplinary_target'])
    ) return;

    $target_id = (int) $_POST['rmrp_disciplinary_target'];
    $issued_by = get_current_user_id();
    $type = sanitize_text_field($_POST['rmrp_disciplinary_type']);
    $reason = sanitize_textarea_field($_POST['rmrp_disciplinary_reason']);

    if (!in_array($type, ['warning', 'reprimand'])) return;

    global $wpdb;
    $table = $wpdb->prefix . 'rmrp_disciplinary_actions';

    $now = current_time('timestamp');

    $wpdb->insert($table, [
        'user_id'     => $target_id,
        'issued_by'   => $issued_by,
        'type'        => $type,
        'reason'      => $reason,
        'created_at'  => date('Y-m-d H:i:s', $now),
        'is_active'   => 1,
    ]);

    // Уведомление
    bp_notifications_add_notification([
        'user_id'           => $target_id,
        'item_id'           => 1,
        'secondary_item_id' => $issued_by,
        'component_name'    => 'activity',
        'component_action'  => $type === 'warning' ? 'warning_issued' : 'reprimand_issued',
        'date_notified'     => bp_core_current_time(),
        'is_new'            => 1,
    ]);

    // Автоконвертация
    if ($type === 'warning') {
        $warnings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE user_id = %d AND type = 'warning' AND is_active = 1 
             ORDER BY created_at ASC LIMIT 2",
            $target_id
        ));

        if (count($warnings) === 2) {
            foreach ($warnings as $w) {
                $wpdb->update($table, [
                    'is_active' => 2,
                ], ['id' => $w->id]);
            }

            $wpdb->insert($table, [
                'user_id'    => $target_id,
                'issued_by'  => 0,
                'type'       => 'reprimand',
                'reason'     => 'Автоматическая замена 2-х предупреждений выговором',
                'created_at' => date('Y-m-d H:i:s', $now + 1), // На секунду позже
                'is_active'  => 1,
            ]);

            bp_notifications_add_notification([
                'user_id'           => $target_id,
                'item_id'           => 1,
                'secondary_item_id' => 0,
                'component_name'    => 'activity',
                'component_action'  => 'reprimand_issued',
                'date_notified'     => bp_core_current_time(),
                'is_new'            => 1,
            ]);
        }
    }

    wp_redirect(bp_core_get_user_domain($target_id));
    exit;
});
