<?php
if (!defined('ABSPATH')) exit;

class HM_Pro_Ceviri_Switcher {

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_shortcode('hm_lang_switcher', [$this, 'shortcode_switcher']);
    }

    public function register_assets() {
        wp_register_style('hmpc-switcher', HMPC_URL . 'assets/switcher.css', [], HMPC_VERSION);
    }

    public function shortcode_switcher($atts): string {
        $atts = shortcode_atts([
            'style' => 'inline', // inline | dropdown
            'show_native' => '1', // 1/0
        ], $atts);

        wp_enqueue_style('hmpc-switcher');

        $settings = HM_Pro_Ceviri_I18n::get_settings();
        $enabled = $settings['enabled_langs'] ?? ['tr'];
        $current = HM_Pro_Ceviri_I18n::get_current_lang();
        $catalog = HM_Pro_Ceviri_I18n::get_languages_catalog();

        if (count($enabled) < 2) return '';

        $base_url = $this->current_url_without_lang_param();

        if ($atts['style'] === 'dropdown') {
            $out = '<form class="hmpc-switcher hmpc-switcher--dropdown" method="get" action="">';
            $out .= '<select name="' . esc_attr(HM_Pro_Ceviri_I18n::QUERY_VAR) . '" onchange="this.form.submit()">';
            foreach ($enabled as $code) {
                $label = $this->label_for($code, $catalog, $atts['show_native'] === '1');
                $out .= '<option value="' . esc_attr($code) . '"' . selected($current, $code, false) . '>' . esc_html($label) . '</option>';
            }
            $out .= '</select>';

            foreach ($_GET as $k => $v) {
                if ($k === HM_Pro_Ceviri_I18n::QUERY_VAR) continue;
                if (is_array($v)) continue;
                $out .= '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr((string) $v) . '">';
            }

            $out .= '</form>';
            return $out;
        }

        $out = '<div class="hmpc-switcher hmpc-switcher--inline" role="navigation" aria-label="Language switcher">';
        foreach ($enabled as $code) {
            $label = $this->label_for($code, $catalog, $atts['show_native'] === '1');
            $url = $this->url_for_lang($base_url, $code);
            $class = 'hmpc-switcher__link' . ($code === $current ? ' is-active' : '');
            $out .= '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        $out .= '</div>';

        return $out;
    }

    private function label_for(string $code, array $catalog, bool $show_native): string {
        if (!isset($catalog[$code])) return strtoupper($code);
        if ($show_native && !empty($catalog[$code]['native'])) return $catalog[$code]['native'];
        return $catalog[$code]['label'] ?? strtoupper($code);
    }

    private function url_for_lang(string $base_url, string $lang): string {
        $settings = HM_Pro_Ceviri_I18n::get_settings();
        $default = $settings['default_lang'] ?? 'tr';

        // base_url: current URL without hm_lang param
        $path = wp_parse_url($base_url, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');

        // Remove any existing prefix
        $enabled = $settings['enabled_langs'] ?? [$default];
        $prefixed = array_values(array_filter($enabled, fn($c) => $c !== $default));

        foreach ($prefixed as $p) {
            if (preg_match('#^/' . preg_quote($p, '#') . '(/|$)#', $path)) {
                $path = preg_replace('#^/' . preg_quote($p, '#') . '#', '', $path);
                if ($path === '') $path = '/';
                break;
            }
        }

        if ($lang === $default) {
            return home_url($path);
        }

        if ($path === '/') {
            return home_url('/' . $lang . '/');
        }

        return home_url('/' . $lang . rtrim($path, '/'));
    }

    private function current_url_without_lang_param(): string {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';

        $url = $scheme . '://' . $host . $uri;
        $url = remove_query_arg(HM_Pro_Ceviri_I18n::QUERY_VAR, $url);
        return $url;
    }
}
