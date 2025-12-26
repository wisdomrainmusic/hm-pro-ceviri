<?php
/**
 * Plugin Name: HM Pro Çeviri
 * Description: URL-based multilingual layer (e.g. /en/). Clean switcher + cookie persistence. Google Translate integration planned.
 * Version: 0.2.0
 * Author: HM
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HMPC_VERSION', '0.2.0');
define('HMPC_PATH', plugin_dir_path(__FILE__));
define('HMPC_URL', plugin_dir_url(__FILE__));
define('HMPC_SLUG', 'hm-pro-ceviri');

require_once HMPC_PATH . 'includes/class-hmpc.php';

add_action('plugins_loaded', function () {
    (new HMPC())->init();
});

register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
