<?php
if (!defined('ABSPATH')) exit;

class HMPC_Rewrites {

    public function init(): void {
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('init', [$this, 'add_rules'], 5);
    }

    public function register_query_vars($vars) {
        $vars[] = 'hmpc_lang';
        $vars[] = 'hmpc_path';
        return $vars;
    }

    public function add_rules(): void {
        $settings = HMPC_I18n::get_settings();
        $default  = $settings['default_lang'] ?? 'tr';
        $enabled  = $settings['enabled_langs'] ?? [$default];

        $prefixed = array_values(array_filter($enabled, fn($c) => $c !== $default));
        if (!$prefixed) return;

        $lang_regex = implode('|', array_map('preg_quote', $prefixed));

        // /pt/test/ -> index.php?hmpc_lang=pt&hmpc_path=test
        add_rewrite_rule(
            '^(' . $lang_regex . ')/(.*)/?$',
            'index.php?hmpc_lang=$matches[1]&hmpc_path=$matches[2]',
            'top'
        );

        // /pt/ -> homepage in pt
        add_rewrite_rule(
            '^(' . $lang_regex . ')/?$',
            'index.php?hmpc_lang=$matches[1]&hmpc_path=',
            'top'
        );
    }
}
