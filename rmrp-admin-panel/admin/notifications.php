<?php
if (!defined('ABSPATH')) exit;

$texts = get_option('rmrp_notification_texts', []);
$colors = get_option('rmrp_notification_colors', []);
$types = [
    'promotion_ready' => 'Повышение доступно',
    'warning_issued' => 'Выдано предупреждение',
    'reprimand_issued' => 'Выдан выговор',
    'warning_removed' => 'Предупреждение снято',
    'reprimand_removed' => 'Выговор снят',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('rmrp_update_notifications')) {
    foreach ($types as $key => $label) {
        $texts[$key] = sanitize_text_field($_POST["text_$key"] ?? '');
        $colors[$key] = sanitize_hex_color($_POST["color_$key"] ?? '');
    }
    update_option('rmrp_notification_texts', $texts);
    update_option('rmrp_notification_colors', $colors);
    echo '<div class="updated"><p>Сохранено.</p></div>';
}
?>

<div class="wrap">
    <h1>Настройка уведомлений</h1>
    <form method="post">
        <?php wp_nonce_field('rmrp_update_notifications'); ?>
        <table class="form-table">
            <?php foreach ($types as $key => $label): ?>
                <tr>
                    <th scope="row"><?php echo $label; ?></th>
                    <td>
                        <input type="text" name="text_<?php echo $key; ?>" value="<?php echo esc_attr($texts[$key] ?? ''); ?>" style="width: 60%;">
                        <input type="color" name="color_<?php echo $key; ?>" value="<?php echo esc_attr($colors[$key] ?? '#ffffff'); ?>" />
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p><input type="submit" class="button-primary" value="Сохранить"></p>
    </form>
</div>
