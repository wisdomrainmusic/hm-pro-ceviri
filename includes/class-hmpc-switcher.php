<?php
if (!defined('ABSPATH')) exit;

class HMPC_Switcher {

    public function init(): void {
        add_shortcode('hm_lang_switcher', [$this, 'shortcode']);
    }

    public function shortcode($atts): string {
        $atts = shortcode_atts([
            'style' => 'dropdown', // dropdown | inline
            'show_native' => '1',
        ], $atts);

        $settings = HMPC_I18n::get_settings();
        $enabled  = $settings['enabled_langs'] ?? ['tr', 'en'];
        $default  = $settings['default_lang'] ?? 'tr';
        $current  = HMPC_I18n::get_current_lang();
        $catalog  = HMPC_I18n::get_languages_catalog();

        if (count($enabled) < 2) return '';

        $base_url = $this->current_url_without_lang_prefix(); // IMPORTANT

        if ($atts['style'] === 'inline') {
            $out = '<div class="hmpc-switcher hmpc-switcher--inline">';
            foreach ($enabled as $code) {
                $label = $this->label_for($code, $catalog, $atts['show_native'] === '1');
                $url = $this->url_for_lang($base_url, $code, $default, $enabled);
                $cls = 'hmpc-link' . ($code === $current ? ' is-active' : '');
                $out .= '<a class="'.esc_attr($cls).'" href="'.esc_url($url).'">'.esc_html($label).'</a>';
            }
            $out .= '</div>';
            return $out;
        }

        // dropdown: option value = URL, onchange = location.href
        $out  = '<div class="hmpc-switcher hmpc-switcher--dropdown" role="navigation" aria-label="Language switcher">';
        $out .= '<select class="hmpc-select" onchange="if(this.value){window.location.href=this.value;}">';
        foreach ($enabled as $code) {
            $label = $this->label_for($code, $catalog, $atts['show_native'] === '1');
            $url = $this->url_for_lang($base_url, $code, $default, $enabled);
            $out .= '<option value="'.esc_url($url).'"'.selected($current, $code, false).'>'.esc_html($label).'</option>';
        }
        $out .= '</select></div>';

        return $out;
    }

    private function label_for(string $code, array $catalog, bool $native): string {
        if (!isset($catalog[$code])) return strtoupper($code);
        if ($native && !empty($catalog[$code]['native'])) return $catalog[$code]['native'];
        return $catalog[$code]['label'] ?? strtoupper($code);
    }

    private function url_for_lang(string $base_url, string $lang, string $default, array $enabled): string {
        $lang = sanitize_key($lang);
        $lang = HMPC_I18n::validate_lang($lang);

        $path = wp_parse_url($base_url, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');

        // Remove any existing lang prefix from base path
        $prefixed = array_values(array_filter($enabled, fn($c) => $c !== $default));
        foreach ($prefixed as $p) {
            $p = sanitize_key($p);
            if ($path === '/' . $p || strpos($path, '/' . $p . '/') === 0) {
                $path = substr($path, strlen('/' . $p));
                $path = $path === '' ? '/' : $path;
                break;
            }
        }

        if ($lang === $default) {
            return home_url($path);
        }

        if ($path === '/') return home_url('/' . $lang . '/');
        return home_url('/' . $lang . rtrim($path, '/'));
    }

    private function current_url_without_lang_prefix(): string {
        $scheme = is_ssl() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $url = $scheme . '://' . $host . $uri;
        $url = strtok($url, '?'); // strip query

        $settings = HMPC_I18n::get_settings();
        $default  = $settings['default_lang'] ?? 'tr';
        $enabled  = $settings['enabled_langs'] ?? [$default];

        $prefixed = array_values(array_filter($enabled, fn($c) => $c !== $default));

        $path = wp_parse_url($url, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');

        foreach ($prefixed as $p) {
            $p = sanitize_key($p);
            if ($path === '/' . $p || strpos($path, '/' . $p . '/') === 0) {
                $path = substr($path, strlen('/' . $p));
                $path = $path === '' ? '/' : $path;
                break;
            }
        }

        return home_url($path);
    }
}
