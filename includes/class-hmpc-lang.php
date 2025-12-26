<?php
if (!defined('ABSPATH')) exit;

class HMPC_Lang {

    /** @var HMPC_Settings */
    private $settings;

    /** @var string */
    private $current = '';

    public function __construct(HMPC_Settings $settings) {
        $this->settings = $settings;
    }

    public function hooks() {
        // Resolve early
        add_action('init', array($this, 'resolve_language'), 1);

        // Apply locale override
        add_filter('locale', array($this, 'filter_locale'), 20);

        // Legacy prefix cleanup (/en/... -> /...)
        add_action('template_redirect', array($this, 'maybe_redirect_legacy_prefix'), 1);
    }

    public function get_current() {
        if ($this->current) return $this->current;
        $this->current = $this->sanitize_lang($this->settings->get('default_lang'));
        return $this->current;
    }

    private function sanitize_lang($lang) {
        $lang = strtolower(trim((string)$lang));
        $lang = preg_replace('/[^a-zA-Z\-]/', '', $lang);
        return $lang ?: 'en';
    }

    public function resolve_language() {
        $allowed = $this->settings->supported_langs();
        $default = $this->sanitize_lang($this->settings->get('default_lang'));
        if (!in_array($default, $allowed, true) && !empty($allowed)) {
            $default = $allowed[0];
        }

        $chosen = '';

        // 1) Query param
        if (isset($_GET['hmpc_lang'])) {
            $q = $this->sanitize_lang(wp_unslash($_GET['hmpc_lang']));
            if ($this->settings->is_lang_allowed($q)) {
                $chosen = $q;
                $this->set_cookie($chosen);
            }
        }

        // 2) Cookie
        if (!$chosen) {
            $cname = (string) $this->settings->get('cookie_name');
            if ($cname && isset($_COOKIE[$cname])) {
                $c = $this->sanitize_lang(wp_unslash($_COOKIE[$cname]));
                if ($this->settings->is_lang_allowed($c)) {
                    $chosen = $c;
                }
            }
        }

        // 3) Auto-detect (Accept-Language)
        if (!$chosen && $this->settings->get('autodetect') === '1') {
            $detected = $this->detect_from_accept_language($allowed);
            if ($detected) $chosen = $detected;
        }

        // 4) Default
        if (!$chosen) $chosen = $default;

        $this->current = $chosen;
    }

    private function detect_from_accept_language($allowed) {
        $header = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        $header = strtolower($header);
        if (!$header) return '';

        // Parse items like: en-US,en;q=0.9,tr;q=0.8
        $items = explode(',', $header);
        $candidates = array();

        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '') continue;

            $parts = explode(';q=', $item);
            $tag = trim($parts[0]);
            $q = isset($parts[1]) ? (float) $parts[1] : 1.0;

            $tag = preg_replace('/[^a-z\-]/', '', $tag);
            if ($tag === '') continue;

            $candidates[] = array('tag' => $tag, 'q' => $q);
        }

        usort($candidates, function($a, $b) {
            if ($a['q'] === $b['q']) return 0;
            return ($a['q'] > $b['q']) ? -1 : 1;
        });

        // Match exact (en-us) or base (en)
        foreach ($candidates as $c) {
            $tag = $c['tag'];
            if (in_array($tag, $allowed, true)) return $tag;
            $base = explode('-', $tag)[0];
            if (in_array($base, $allowed, true)) return $base;
        }

        return '';
    }

    private function set_cookie($lang) {
        $cname = (string) $this->settings->get('cookie_name');
        $days  = (int) $this->settings->get('cookie_days');
        if ($days <= 0) $days = 30;

        $secure = is_ssl();
        $httponly = true;

        // Use COOKIEPATH so WP subdir installs work.
        $expires = time() + ($days * DAY_IN_SECONDS);

        // PHP 7.3+ supports array options; fallback for older.
        if (PHP_VERSION_ID >= 70300) {
            setcookie($cname, $lang, array(
                'expires'  => $expires,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => 'Lax',
            ));
        } else {
            // Best-effort legacy
            setcookie($cname, $lang, $expires, COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly);
        }

        // Ensure availability in same request
        $_COOKIE[$cname] = $lang;
    }

    public function filter_locale($locale) {
        // Map simple lang codes to WP locales
        $lang = $this->get_current();

        $map = array(
            'en' => 'en_US',
            'tr' => 'tr_TR',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
            'es' => 'es_ES',
            'ar' => 'ar',
            'ru' => 'ru_RU',
        );

        // If user uses custom codes like en-gb, try to map
        if (isset($map[$lang])) return $map[$lang];

        // If looks like xx-yy then convert to xx_YY
        if (preg_match('/^[a-z]{2}\-[a-z]{2}$/', $lang)) {
            $parts = explode('-', $lang);
            return strtolower($parts[0]) . '_' . strtoupper($parts[1]);
        }

        // Fallback: keep original
        return $locale;
    }

    public function maybe_redirect_legacy_prefix() {
        if (is_admin() || defined('DOING_AJAX') || (defined('REST_REQUEST') && REST_REQUEST)) return;
        if ($this->settings->get('legacy_prefix_redirect') !== '1') return;

        $allowed = $this->settings->supported_langs();
        if (empty($allowed)) return;

        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if (!$uri) return;

        $parsed = wp_parse_url($uri);
        $path = isset($parsed['path']) ? $parsed['path'] : '';
        if (!$path) return;

        // Match /{lang}/anything  OR /{lang}
        $trimmed = ltrim($path, '/');
        $first = $trimmed;
        $rest = '';

        if (strpos($trimmed, '/') !== false) {
            $first = substr($trimmed, 0, strpos($trimmed, '/'));
            $rest  = substr($trimmed, strpos($trimmed, '/')); // includes leading slash
        }

        $first = strtolower(preg_replace('/[^a-zA-Z\-]/', '', $first));
        if (!$first || !in_array($first, $allowed, true)) return;

        // Build new URL without prefix, keep querystring
        $new_path = $rest ?: '/';
        $query = isset($parsed['query']) ? $parsed['query'] : '';
        $new_uri = $new_path . ($query ? ('?' . $query) : '');

        // Avoid infinite loop if already same
        if ($new_uri === $uri) return;

        wp_safe_redirect($new_uri, 301);
        exit;
    }
}
