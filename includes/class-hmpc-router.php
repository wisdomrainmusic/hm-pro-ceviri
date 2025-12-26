<?php
if (!defined('ABSPATH')) exit;

class HMPC_Router {

    public function init(): void {
        // WP route etmeden önce prefix'i kırp
        add_action('parse_request', [$this, 'handle_lang_prefix'], 0);
        add_action('parse_request', [$this, 'apply_hmpc_path_to_wp_request'], 0);

        add_filter('redirect_canonical', [$this, 'prevent_wp_canonical_redirect_on_lang_prefix'], 10, 2);

        // İsteğe bağlı: ?hm_lang=en gelirse cookie set (geri uyumluluk)
        add_action('init', [$this, 'handle_query_lang'], 1);
    }

    public function handle_query_lang(): void {
        if (empty($_GET['hm_lang'])) return;
        HMPC_I18n::set_lang_cookie((string) $_GET['hm_lang']);
    }

    public function handle_lang_prefix(\WP $wp): void {
        $settings = HMPC_I18n::get_settings();
        $default  = $settings['default_lang'] ?? 'tr';
        $enabled  = $settings['enabled_langs'] ?? [$default];

        $prefixed = array_values(array_filter($enabled, fn($c) => $c !== $default));
        if (!$prefixed) return;

        $req = ltrim((string) $wp->request, '/'); // e.g. "en/test" or "en" or "shop/category"
        if ($req === '') return;

        foreach ($prefixed as $code) {
            $code = sanitize_key($code);

            if ($req === $code || strpos($req, $code . '/') === 0) {
                // 1) set cookie lang
                HMPC_I18n::set_lang_cookie($code);

                // 2) strip prefix so WP resolves correct page/product/category
                $new_req = substr($req, strlen($code));
                $new_req = ltrim((string) $new_req, '/'); // "" for homepage

                $wp->request = $new_req;

                // also adjust query vars if WP already set something
                if (!empty($wp->query_vars['pagename']) && is_string($wp->query_vars['pagename'])) {
                    $wp->query_vars['pagename'] = ltrim($new_req, '/');
                }

                $GLOBALS['hmpc_current_lang'] = $code;
                return;
            }
        }

        $GLOBALS['hmpc_current_lang'] = HMPC_I18n::get_current_lang();
    }

    public function apply_hmpc_path_to_wp_request(\WP $wp): void {
        $lang = get_query_var('hmpc_lang');
        if ($lang) {
            HMPC_I18n::set_lang_cookie((string)$lang);
            $GLOBALS['hmpc_current_lang'] = sanitize_key((string)$lang);
        }

        $path = get_query_var('hmpc_path');
        if ($path === null) return;

        $path = is_string($path) ? $path : '';
        $path = ltrim($path, '/'); // "test" or "shop/category" etc.

        // kritik: WP’ye gerçek request budur dedirt
        $wp->request = $path;

        // query_vars tarafında temiz dursun
        unset($wp->query_vars['hmpc_path']);
    }

    public function prevent_wp_canonical_redirect_on_lang_prefix($redirect_url, $requested_url) {
        // Admin/AJAX değilken çalışsın
        if (is_admin() || wp_doing_ajax()) return $redirect_url;

        $settings = HMPC_I18n::get_settings();
        $default  = $settings['default_lang'] ?? 'tr';
        $enabled  = $settings['enabled_langs'] ?? [$default];
        $prefixed = array_values(array_filter($enabled, fn($c) => $c !== $default));

        $req_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = strtok($req_uri, '?');

        foreach ($prefixed as $code) {
            $code = sanitize_key($code);

            if ($path === '/' . $code . '/' || strpos($path, '/' . $code . '/') === 0) {
                // WordPress'in /en/test -> /test canonical redirectini engelle
                return false;
            }
        }

        return $redirect_url;
    }
}
