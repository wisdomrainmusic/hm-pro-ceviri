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
        return [
            'tr' => ['label' => 'Turkish', 'native' => 'Türkçe'],
            'en' => ['label' => 'English', 'native' => 'English'],
            'de' => ['label' => 'German', 'native' => 'Deutsch'],
            'fr' => ['label' => 'French', 'native' => 'Français'],
            'it' => ['label' => 'Italian', 'native' => 'Italiano'],
            'es' => ['label' => 'Spanish', 'native' => 'Español'],
            'pt' => ['label' => 'Portuguese', 'native' => 'Português'],
            'nl' => ['label' => 'Dutch', 'native' => 'Nederlands'],
            'pl' => ['label' => 'Polish', 'native' => 'Polski'],

            'ro' => ['label' => 'Romanian', 'native' => 'Română'],
            'bg' => ['label' => 'Bulgarian', 'native' => 'Български'],
            'el' => ['label' => 'Greek', 'native' => 'Ελληνικά'],
            'ru' => ['label' => 'Russian', 'native' => 'Русский'],
            'uk' => ['label' => 'Ukrainian', 'native' => 'Українська'],

            'ka' => ['label' => 'Georgian', 'native' => 'ქართული'],
            'hy' => ['label' => 'Armenian', 'native' => 'Հայերեն'],
            'az' => ['label' => 'Azerbaijani', 'native' => 'Azərbaycan'],
            'fa' => ['label' => 'Persian', 'native' => 'فارسی'],
            'ar' => ['label' => 'Arabic', 'native' => 'العربية'],
            'he' => ['label' => 'Hebrew', 'native' => 'עברית'],

            'sr' => ['label' => 'Serbian', 'native' => 'Srpski'],
            'hr' => ['label' => 'Croatian', 'native' => 'Hrvatski'],
            'bs' => ['label' => 'Bosnian', 'native' => 'Bosanski'],
            'sq' => ['label' => 'Albanian', 'native' => 'Shqip'],
            'hu' => ['label' => 'Hungarian', 'native' => 'Magyar'],
            'cs' => ['label' => 'Czech', 'native' => 'Čeština'],
            'sk' => ['label' => 'Slovak', 'native' => 'Slovenčina'],
            'sl' => ['label' => 'Slovenian', 'native' => 'Slovenščina'],

            'sv' => ['label' => 'Swedish', 'native' => 'Svenska'],
            'no' => ['label' => 'Norwegian', 'native' => 'Norsk'],
            'da' => ['label' => 'Danish', 'native' => 'Dansk'],
            'fi' => ['label' => 'Finnish', 'native' => 'Suomi'],

            // Kurdish variants (Google codes)
            'ku'  => ['label' => 'Kurdish (Kurmanji)', 'native' => 'Kurdî (Kurmancî)'],
            'ckb' => ['label' => 'Kurdish (Sorani)', 'native' => 'کوردی (سۆرانی)'],
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

    public static function set_lang_cookie(string $lang): void {
        $lang = sanitize_key($lang);
        if (!$lang) return;

        setcookie(self::COOKIE_KEY, $lang, time() + 30 * DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
        $_COOKIE[self::COOKIE_KEY] = $lang;
    }

    public function maybe_set_language_from_request() {
        if (empty($_GET[self::QUERY_VAR])) return;

        $requested = sanitize_key((string) $_GET[self::QUERY_VAR]);
        $settings = self::get_settings();
        $enabled = $settings['enabled_langs'] ?? ['tr'];

        if (!in_array($requested, $enabled, true)) return;

        // Cookie 30 gün
        self::set_lang_cookie($requested);
    }
}
