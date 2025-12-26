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
