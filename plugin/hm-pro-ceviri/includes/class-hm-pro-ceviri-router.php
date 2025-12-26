<?php
if (!defined('ABSPATH')) exit;

class HM_Pro_Ceviri_Router {

    const QV = 'hm_lang';

    public function init() {
        add_action('init', [$this, 'add_rewrite_rules'], 5);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('parse_request', [$this, 'parse_request_lang'], 1);
        add_action('template_redirect', [$this, 'maybe_redirect_clean_url'], 1);
    }

    public function register_query_vars($vars) {
        $vars[] = self::QV;
        return $vars;
    }

    public function add_rewrite_rules() {
        $settings = HM_Pro_Ceviri_I18n::get_settings();
        $enabled = $settings['enabled_langs'] ?? ['tr', 'en'];
        $default = $settings['default_lang'] ?? 'tr';

        // TR için prefix yok; diğer enabled diller için prefix var
        $prefixed = array_values(array_filter($enabled, fn($c) => $c !== $default));

        if (empty($prefixed)) return;

        $lang_regex = implode('|', array_map('preg_quote', $prefixed));

        // Not: pagename ile her şeyi çözmek istemiyoruz.
        // WordPress'in kendi rewrite'ı devreye girecek; biz sadece lang'ı query'e eklemek istiyoruz.
        add_rewrite_rule(
            '^(' . $lang_regex . ')(?:/(.*))?$',
            'index.php?' . self::QV . '=$matches[1]&$matches[2]',
            'top'
        );
    }

    public function parse_request_lang($wp) {
        $lang = '';

        // 1) URL prefix (rewrite ile gelir)
        if (!empty($wp->query_vars[self::QV])) {
            $lang = sanitize_key($wp->query_vars[self::QV]);
        }

        // 2) Query param (fallback)
        if (!$lang && !empty($_GET[self::QV])) {
            $lang = sanitize_key((string) $_GET[self::QV]);
        }

        // validate enabled
        $settings = HM_Pro_Ceviri_I18n::get_settings();
        $enabled = $settings['enabled_langs'] ?? ['tr'];
        $default = $settings['default_lang'] ?? 'tr';

        if ($lang && in_array($lang, $enabled, true)) {
            HM_Pro_Ceviri_I18n::set_lang_cookie($lang);
        } else {
            // ensure cookie default exists (optional)
            // do not force set; leave as-is
            $lang = HM_Pro_Ceviri_I18n::get_current_lang();
        }

        // expose to global for later use if needed
        $GLOBALS['hmpc_current_lang'] = $lang ?: $default;
    }

    public function maybe_redirect_clean_url() {
        // If request uses ?hm_lang=xx but no prefix in path (and lang != default),
        // redirect to /xx/ clean URL.
        if (is_admin() || wp_doing_ajax()) return;

        if (empty($_GET[self::QV])) return;

        $requested = sanitize_key((string) $_GET[self::QV]);

        $settings = HM_Pro_Ceviri_I18n::get_settings();
        $enabled = $settings['enabled_langs'] ?? ['tr'];
        $default = $settings['default_lang'] ?? 'tr';

        if (!in_array($requested, $enabled, true)) return;

        $current_path = $_SERVER['REQUEST_URI'] ?? '/';
        $current_path = strtok($current_path, '?'); // path only

        // if already prefixed, just strip query
        $is_prefixed = preg_match('#^/' . preg_quote($requested, '#') . '(/|$)#', $current_path) === 1;

        // Build clean URL
        $target = home_url($current_path);

        // Remove hm_lang from query but keep others
        $qs = $_GET;
        unset($qs[self::QV]);

        if ($requested !== $default && !$is_prefixed) {
            // add prefix
            $target = home_url('/' . $requested . rtrim($current_path, '/'));
            if ($current_path === '/') $target = home_url('/' . $requested . '/');
        }

        if (!empty($qs)) {
            $target = add_query_arg(array_map('sanitize_text_field', $qs), $target);
        }

        wp_safe_redirect($target, 302);
        exit;
    }
}


// Not: Rewrite kısmı WordPress’te hassas; eğer 404 görürsek kuralı sadeleştiririz. Şimdilik temel amaç hm_lang’ı query vars olarak route’a taşımak.
