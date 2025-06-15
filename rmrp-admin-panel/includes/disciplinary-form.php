<?php
if (!defined('ABSPATH')) exit;

function rmrp_render_disciplinary_form($user_id) {
    // Проверка прав: можно ли текущему пользователю выдать взыскание этому пользователю
    if (!current_user_can('manage_options')) return; // можно заменить кастомной проверкой позже

    $current_user_id = get_current_user_id();
    if ($user_id == $current_user_id) return;

    ?>
    <div style="margin-top:20px;">
        <button id="rmrp-show-disciplinary-form" class="button button-primary">Выдать взыскание</button>
        <form id="rmrp-disciplinary-form" style="display:none; margin-top:15px;" method="post">
            <input type="hidden" name="rmrp_disciplinary_target" value="<?php echo esc_attr($user_id); ?>">
            <label>Тип взыскания:</label><br>
            <select name="rmrp_disciplinary_type" required>
                <option value="warning">Предупреждение</option>
                <option value="reprimand">Выговор</option>
            </select><br><br>

            <label>Причина:</label><br>
            <textarea name="rmrp_disciplinary_reason" rows="4" style="width:100%;" required></textarea><br><br>

            <button type="submit" class="button button-secondary">Сохранить</button>
        </form>
    </div>

    <script>
        document.getElementById("rmrp-show-disciplinary-form").addEventListener("click", function() {
            document.getElementById("rmrp-disciplinary-form").style.display = 'block';
            this.style.display = 'none';
        });
    </script>
    <?php
}

add_action('bp_after_member_header', function () {
    if (!bp_is_user_profile()) return;
    $user_id = bp_displayed_user_id();
    rmrp_render_disciplinary_form($user_id);
});
