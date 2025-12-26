<?php
if (!defined('ABSPATH')) exit;

class HMPC_Settings {
    const OPTION_KEY = 'hmpc_settings';

    private $options = array();

    public function __construct() {
        $this->options = $this->get_all();
    }

    public function get_defaults() {
        return array(
            // Comma-separated list: language codes
            'supported_langs' => 'en,tr,de,fr,es,ar,ru',
            'default_lang'    => 'en',
            'autodetect'      => '1',
            'cookie_days'     => '30',
            'cookie_name'     => 'hmpc_lang',
            // If someone hits /en/test, redirect to /test (no prefix mode)
            'legacy_prefix_redirect' => '1',
        );
    }

    public function get_all() {
        $defaults = $this->get_defaults();
        $stored   = get_option(self::OPTION_KEY, array());
        if (!is_array($stored)) $stored = array();
        return array_merge($defaults, $stored);
    }

    public function get($key) {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    public function update($new) {
        $defaults   = $this->get_defaults();
        $sanitized  = $this->sanitize(is_array($new) ? $new : array());
        $this->options = array_merge($defaults, $sanitized);
        update_option(self::OPTION_KEY, $this->options);
    }

    public function supported_langs() {
        $raw   = (string) $this->get('supported_langs');
        $parts = array_filter(array_map('trim', explode(',', $raw)));
        $out   = array();
        foreach ($parts as $p) {
            $p = strtolower(preg_replace('/[^a-zA-Z\-]/', '', $p));
            if ($p !== '') $out[] = $p;
        }
        $out = array_values(array_unique($out));
        return $out;
    }

    public function is_lang_allowed($lang) {
        $lang = strtolower(trim((string) $lang));
        return in_array($lang, $this->supported_langs(), true);
    }

    public function sanitize($input) {
        $sanitized = $this->get_defaults();

        $sanitized_langs = array();
        if (isset($input['supported_langs'])) {
            $parts = array_filter(array_map('trim', explode(',', (string) $input['supported_langs'])));
            foreach ($parts as $p) {
                $p = strtolower(preg_replace('/[^a-zA-Z\-]/', '', $p));
                if ($p !== '') $sanitized_langs[] = $p;
            }
            $sanitized_langs = array_values(array_unique($sanitized_langs));
            if (!empty($sanitized_langs)) {
                $sanitized['supported_langs'] = implode(',', $sanitized_langs);
            }
        }

        $allowed_for_default = !empty($sanitized_langs) ? $sanitized_langs : $this->supported_langs();

        if (isset($input['default_lang'])) {
            $default = strtolower(sanitize_text_field($input['default_lang']));
            if ($default !== '' && in_array($default, $allowed_for_default, true)) {
                $sanitized['default_lang'] = $default;
            }
        }

        $sanitized['autodetect'] = !empty($input['autodetect']) ? '1' : '0';

        if (isset($input['cookie_days'])) {
            $days = max(1, (int) $input['cookie_days']);
            $sanitized['cookie_days'] = (string) $days;
        }

        if (isset($input['cookie_name'])) {
            $cookie_name = sanitize_key($input['cookie_name']);
            if ($cookie_name !== '') {
                $sanitized['cookie_name'] = $cookie_name;
            }
        }

        $sanitized['legacy_prefix_redirect'] = !empty($input['legacy_prefix_redirect']) ? '1' : '0';

        return $sanitized;
    }

    public function default_lang() {
        $default = strtolower(trim((string) $this->get('default_lang')));
        $default = preg_replace('/[^a-zA-Z\-]/', '', $default);
        if ($default === '') $default = 'tr';
        return $default;
    }
}
