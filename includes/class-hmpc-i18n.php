<?php
if (!defined('ABSPATH')) exit;

class HMPC_I18n {
    const OPT_KEY    = 'hmpc_settings';
    const COOKIE_KEY = 'hmpc_lang';

    public function init(): void {
        add_action('template_redirect', function () {
            $lang = get_query_var('hmpc_lang');
            if ($lang) {
                HMPC_I18n::set_lang_cookie((string) $lang);
                $GLOBALS['hmpc_current_lang'] = sanitize_key((string) $lang);
            }
        }, 0);
    }

    public static function defaults(): array {
        return [
            'default_lang'  => 'tr',
            'enabled_langs' => ['tr', 'en'],
        ];
    }

    public static function get_settings(): array {
        $saved = get_option(self::OPT_KEY, []);
        if (!is_array($saved)) $saved = [];
        return array_merge(self::defaults(), $saved);
    }

    public static function get_languages_catalog(): array {
        // İstersen bunu daha da genişletiriz (şimdilik temel + komşular).
        return [
            'tr'  => ['label' => 'Turkish',  'native' => 'Türkçe'],
            'en'  => ['label' => 'English',  'native' => 'English'],
            'de'  => ['label' => 'German',   'native' => 'Deutsch'],
            'fr'  => ['label' => 'French',   'native' => 'Français'],
            'it'  => ['label' => 'Italian',  'native' => 'Italiano'],
            'es'  => ['label' => 'Spanish',  'native' => 'Español'],
            'pt'  => ['label' => 'Portuguese','native'=> 'Português'],
            'ro'  => ['label' => 'Romanian', 'native' => 'Română'],
            'bg'  => ['label' => 'Bulgarian','native' => 'Български'],
            'el'  => ['label' => 'Greek',    'native' => 'Ελληνικά'],
            'ru'  => ['label' => 'Russian',  'native' => 'Русский'],
            'ar'  => ['label' => 'Arabic',   'native' => 'العربية'],
            'fa'  => ['label' => 'Persian',  'native' => 'فارسی'],
            'az'  => ['label' => 'Azerbaijani','native'=> 'Azərbaycan'],
            'ku'  => ['label' => 'Kurdish (Kurmanji)', 'native' => 'Kurdî (Kurmancî)'],
            'ckb' => ['label' => 'Kurdish (Sorani)',   'native' => 'کوردی (سۆرانی)'],
        ];
    }

    public static function validate_lang(string $lang): string {
        $lang = sanitize_key($lang);
        $settings = self::get_settings();
        $enabled = $settings['enabled_langs'] ?? ['tr'];
        $default = $settings['default_lang'] ?? 'tr';

        if (in_array($lang, $enabled, true)) return $lang;
        return $default;
    }

    public static function get_current_lang(): string {
        $settings = self::get_settings();
        $default = $settings['default_lang'] ?? 'tr';

        $cookie = '';
        if (!empty($_COOKIE[self::COOKIE_KEY])) {
            $cookie = sanitize_key((string) $_COOKIE[self::COOKIE_KEY]);
        }

        return self::validate_lang($cookie ?: $default);
    }

    public static function set_lang_cookie(string $lang): void {
        $lang = self::validate_lang($lang);

        // kritik: path "/" (COOKIEPATH bazen /wp/ olabiliyor, o yüzden / kullanıyoruz)
        setcookie(self::COOKIE_KEY, $lang, time() + 30 * DAY_IN_SECONDS, '/', '', is_ssl(), true);
        $_COOKIE[self::COOKIE_KEY] = $lang;
    }
}
