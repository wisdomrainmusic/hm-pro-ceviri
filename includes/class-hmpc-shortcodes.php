<?php
if (!defined('ABSPATH')) exit;

class HMPC_Shortcodes {
    /** @var HMPC_Settings */
    private $settings;

    /** @var HMPC_Lang */
    private $lang;

    public function __construct($settings, $lang) {
        $this->settings = $settings;
        $this->lang     = $lang;
    }

    public function hooks() {
        add_shortcode('hmpc_lang_switcher', [$this, 'render_switcher_shortcode']);
    }

    public function render_switcher_shortcode($atts = []) {
        $atts = shortcode_atts(
            [
                'class' => 'hmpc-lang-switcher',
            ],
            $atts,
            'hmpc_lang_switcher'
        );

        $languages = $this->settings->supported_langs();
        if (empty($languages)) {
            return '';
        }

        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $output      = '<ul class="' . esc_attr($atts['class']) . '">';
        foreach ($languages as $lang) {
            $url     = add_query_arg('hmpc_lang', $lang, $current_url);
            $current = $this->lang->get_current_language() === $lang;
            $label   = $current ? sprintf('%s (%s)', $lang, __('current', 'hm-pro-ceviri')) : $lang;
            $output .= '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
        }
        $output .= '</ul>';

        return $output;
    }
}
