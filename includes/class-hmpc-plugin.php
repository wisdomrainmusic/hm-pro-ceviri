<?php
if (!defined('ABSPATH')) exit;

require_once HMPC_PATH . 'includes/class-hmpc-settings.php';
require_once HMPC_PATH . 'includes/class-hmpc-lang.php';
require_once HMPC_PATH . 'includes/class-hmpc-admin.php';
require_once HMPC_PATH . 'includes/class-hmpc-shortcodes.php';

final class HMPC_Plugin {
    private static $instance = null;

    /** @var HMPC_Settings */
    public $settings;

    /** @var HMPC_Lang */
    public $lang;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        $this->settings = new HMPC_Settings();
        $this->lang     = new HMPC_Lang($this->settings);

        // Admin
        if (is_admin()) {
            $admin = new HMPC_Admin($this->settings);
            $admin->hooks();
        }

        // Public hooks
        $this->lang->hooks();

        // Shortcodes
        $shortcodes = new HMPC_Shortcodes($this->settings, $this->lang);
        $shortcodes->hooks();
    }
}
