<?php
/**
 * Plugin Name: HM Pro Çeviri
 * Description: Lightweight multilingual layer + Google Translate integration (planned). Provides language registry and frontend language switcher.
 * Version: 0.1.0
 * Author: HM
 */

if (!defined('ABSPATH')) exit;

define('HMPC_VERSION', '0.1.0');
define('HMPC_PATH', plugin_dir_path(__FILE__));
define('HMPC_URL', plugin_dir_url(__FILE__));
define('HMPC_SLUG', 'hm-pro-ceviri');

require_once HMPC_PATH . 'includes/class-hm-pro-ceviri.php';

function hmpc_boot() {
    $plugin = new HM_Pro_Ceviri();
    $plugin->init();
}
add_action('plugins_loaded', 'hmpc_boot');

register_activation_hook(__FILE__, function () {
    // Ensure rewrites are registered before flushing
    require_once HMPC_PATH . 'includes/class-hm-pro-ceviri-i18n.php';
    require_once HMPC_PATH . 'includes/class-hm-pro-ceviri-router.php';
    (new HM_Pro_Ceviri_Router())->add_rewrite_rules();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
