<?php
if (!defined('ABSPATH')) exit;

require_once HMPC_PATH . 'includes/class-hm-pro-ceviri-i18n.php';
require_once HMPC_PATH . 'includes/class-hm-pro-ceviri-switcher.php';
require_once HMPC_PATH . 'includes/admin/class-hm-pro-ceviri-admin.php';

class HM_Pro_Ceviri {

    public function init() {
        (new HM_Pro_Ceviri_I18n())->init();
        (new HM_Pro_Ceviri_Switcher())->init();

        if (is_admin()) {
            (new HM_Pro_Ceviri_Admin())->init();
        }
    }
}
