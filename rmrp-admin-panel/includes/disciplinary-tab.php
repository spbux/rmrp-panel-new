<?php
if (!defined('ABSPATH')) exit;

function rmrp_add_profile_disciplinary_tab() {
    bp_core_new_nav_item([
        'name' => 'Взыскания',
        'slug' => 'disciplinary',
        'position' => 99,
        'screen_function' => 'rmrp_show_disciplinary_tab',
        'default_subnav_slug' => 'disciplinary',
        'parent_url' => bp_displayed_user_domain(),
        'parent_slug' => bp_get_profile_slug(),
    ]);
}

function rmrp_show_disciplinary_tab() {
    add_action('bp_template_content', 'rmrp_render_disciplinary_tab_content');
    bp_core_load_template('members/single/plugins');
}

add_action('bp_setup_nav', 'rmrp_add_profile_disciplinary_tab');
