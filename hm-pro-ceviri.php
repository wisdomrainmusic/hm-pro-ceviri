<?php
/**
 * Plugin Name: HM Pro Çeviri
 * Description: No URL prefix translation helper. Resolves language via ?hmpc_lang + cookie + browser auto-detect (Accept-Language). Includes admin settings and a language switcher shortcode.
 * Version: 0.1.0
 * Author: HM
 * Text Domain: hm-pro-ceviri
 */

if (!defined('ABSPATH')) exit;

define('HMPC_VERSION', '0.1.0');
define('HMPC_PATH', plugin_dir_path(__FILE__));
define('HMPC_URL', plugin_dir_url(__FILE__));

require_once HMPC_PATH . 'includes/class-hmpc-plugin.php';

function hmpc_bootstrap() {
    $plugin = HMPC_Plugin::instance();
    $plugin->init();
}
add_action('plugins_loaded', 'hmpc_bootstrap');
