<?php
if (!defined('ABSPATH')) exit;

class HM_Pro_Ceviri_I18n {

    const OPT_KEY = 'hmpc_settings';
    const COOKIE_KEY = 'hmpc_lang';
    const QUERY_VAR = 'hm_lang';

    public function init() {
        add_action('init', [$this, 'maybe_set_language_from_request'], 1);
    }

    public static function get_settings(): array {
        $defaults = [
            'default_lang' => 'tr',
            'enabled_langs' => ['tr', 'en'],
        ];
        $saved = get_option(self::OPT_KEY, []);
        if (!is_array($saved)) $saved = [];
        return array_merge($defaults, $saved);
    }

    public static function get_languages_catalog(): array {
        // MVP: küçük liste; sonra Google Translate dilleri full liste olarak genişletiriz.
        return [
            'tr' => ['label' => 'Turkish', 'native' => 'Türkçe'],
            'en' => ['label' => 'English', 'native' => 'English'],
            'de' => ['label' => 'German', 'native' => 'Deutsch'],
            'fr' => ['label' => 'French', 'native' => 'Français'],
            'ar' => ['label' => 'Arabic', 'native' => 'العربية'],
        ];
    }

    public static function get_current_lang(): string {
        $settings = self::get_settings();
        $default = $settings['default_lang'] ?? 'tr';
        $enabled = $settings['enabled_langs'] ?? ['tr'];

        $lang = '';
        if (!empty($_COOKIE[self::COOKIE_KEY])) {
            $lang = sanitize_key((string) $_COOKIE[self::COOKIE_KEY]);
        }

        if (!$lang) $lang = $default;
        if (!in_array($lang, $enabled, true)) $lang = $default;

        return $lang;
    }

    public function maybe_set_language_from_request() {
        if (empty($_GET[self::QUERY_VAR])) return;

        $requested = sanitize_key((string) $_GET[self::QUERY_VAR]);
        $settings = self::get_settings();
        $enabled = $settings['enabled_langs'] ?? ['tr'];

        if (!in_array($requested, $enabled, true)) return;

        // Cookie 30 gün
        setcookie(self::COOKIE_KEY, $requested, time() + 30 * DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
        $_COOKIE[self::COOKIE_KEY] = $requested;
    }
}
