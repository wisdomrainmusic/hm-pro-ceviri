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
        // Intentionally left blank for MVP.
        // We handle /{lang}/ prefix by stripping it in parse_request.
    }

    public function parse_request_lang($wp) {
        $settings = HM_Pro_Ceviri_I18n::get_settings();
        $enabled  = $settings['enabled_langs'] ?? ['tr'];
        $default  = $settings['default_lang'] ?? 'tr';

        // Prefixed languages: all except default (TR stays without prefix)
        $prefixed = array_values(array_filter($enabled, fn($c) => $c !== $default));

        $lang = '';

        // Detect from path: $wp->request example: "en/test" or "en" or "urun/abc"
        $req = ltrim((string) ($wp->request ?? ''), '/');

        foreach ($prefixed as $code) {
            if ($req === $code || str_starts_with($req, $code . '/')) {
                $lang = $code;

                // Strip prefix from request so WP resolves correct page/product/category
                $new_req = substr($req, strlen($code));
                $new_req = ltrim($new_req, '/');
                $wp->request = $new_req; // can be "" for homepage

                break;
            }
        }

        // Fallback: query param ?hm_lang=
        if (!$lang && !empty($_GET[self::QV])) {
            $q = sanitize_key((string) $_GET[self::QV]);
            if (in_array($q, $enabled, true)) $lang = $q;
        }

        if ($lang && in_array($lang, $enabled, true)) {
            HM_Pro_Ceviri_I18n::set_lang_cookie($lang);
        }

        $GLOBALS['hmpc_current_lang'] = $lang ?: HM_Pro_Ceviri_I18n::get_current_lang();
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
