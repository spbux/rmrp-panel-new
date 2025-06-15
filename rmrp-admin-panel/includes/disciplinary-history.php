<?php
if (!defined('ABSPATH')) exit;

function rmrp_get_user_disciplinary_actions($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'rmrp_disciplinary_actions';

    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC", $user_id)
    ) ?: [];
}

function rmrp_render_disciplinary_tab_content() {
    $user_id = bp_displayed_user_id();
    if (!$user_id) {
        echo '<p>Ошибка: пользователь не найден.</p>';
        return;
    }

    $actions = rmrp_get_user_disciplinary_actions($user_id);

    $warnings = array_filter($actions, fn($a) => $a->type === 'warning' && (int)$a->is_active === 1);
    $reprimands = array_filter($actions, fn($a) => $a->type === 'reprimand' && (int)$a->is_active === 1);

    echo '<div class="rmrp-disciplinary">';
    echo '<div class="rmrp-header">';
    echo '<h3 class="rmrp-title">История дисциплинарных взысканий</h3>';
    echo '<div class="rmrp-stats">';
    echo '<span class="rmrp-badge warning">Предупреждений <strong>' . count($warnings) . '/2</strong></span>';
    echo '<span class="rmrp-badge reprimand">Выговоров <strong>' . count($reprimands) . '/2</strong></span>';
    echo '</div></div>';

    foreach ($actions as $action) {
        $is_removed = (int)$action->is_active !== 1;
        $is_auto = (int)$action->is_active === 2;
        $date = date('d/m/Y', strtotime($action->created_at));
        $reason = esc_html($action->reason);
        $issuer = (int)$action->issued_by === 0 ? 'Система' : esc_html(bp_core_get_user_displayname($action->issued_by));
        $issuer_label = $is_removed ? 'Кто снял' : 'Кто выдал';

        if ($is_removed) {
            if ($is_auto) {
                $color = 'gray';
                $headline = $action->type === 'warning' ? 'Предупреждение' : 'Выговор';
            } else {
                $color = 'green';
                $headline = $action->type === 'warning' ? 'Предупреждение снято' : 'Выговор снят';
            }
        } else {
            $color = $action->type === 'warning' ? 'yellow' : 'red';
            $headline = 'У вас новое ' . ($action->type === 'warning' ? 'предупреждение' : 'выговор');
        }

        echo '<div class="rmrp-entry ' . $color . '">';
        echo '<div class="left">';
        echo '<strong>' . $headline . '</strong>';
        echo '<div class="reason">Причина: ' . $reason . '</div>';
        echo '</div>';
        echo '<div class="right">';
        echo '<div class="meta-block"><strong>' . $date . '</strong><div class="meta-label">Дата</div></div>';
        echo '<div class="meta-block"><strong>' . $issuer . '</strong><div class="meta-label">' . $issuer_label . '</div></div>';
        if ((int)$action->is_active === 1) {
            echo '<div class="remove" data-id="' . $action->id . '">✕</div>';
        }
        echo '</div></div>';
    }

    echo '</div>';
}
