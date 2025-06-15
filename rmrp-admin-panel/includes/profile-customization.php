<?php
if (!defined('ABSPATH')) exit;

add_action('wp_footer', function () {
    if (!bp_is_user()) return;
    $colors = get_option('rmrp_notification_colors', []);
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var map = JSON.parse('<?php echo esc_js(wp_json_encode($colors)); ?>');
            if (!map) return;
            Object.keys(map).forEach(function(action) {
                var color = map[action];
                if (!color) return;
                document.querySelectorAll('[data-bp-notification-action="' + action + '"]').forEach(function(el) {
                    el.style.backgroundColor = color;
                });
            });
        });
    </script>
    <?php
});
?>
