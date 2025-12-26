<?php
if (!defined('ABSPATH')) exit;

require_once HMPC_PATH . 'includes/class-hmpc-i18n.php';
require_once HMPC_PATH . 'includes/class-hmpc-router.php';
require_once HMPC_PATH . 'includes/class-hmpc-switcher.php';
require_once HMPC_PATH . 'includes/class-hmpc-admin.php';

class HMPC {
    public function init(): void {
        (new HMPC_I18n())->init();
        (new HMPC_Router())->init();
        (new HMPC_Switcher())->init();

        if (is_admin()) {
            (new HMPC_Admin())->init();
        }

        add_action('wp_enqueue_scripts', function () {
            wp_enqueue_style('hmpc', HMPC_URL . 'assets/hmpc.css', [], HMPC_VERSION);
        });
    }
}
