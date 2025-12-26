<?php
if (!defined('ABSPATH')) exit;

class HMPC_Shortcodes {

    /** @var HMPC_Settings */
    private $settings;

    /** @var HMPC_Lang */
    private $lang;

    public function __construct(HMPC_Settings $settings, HMPC_Lang $lang) {
        $this->settings = $settings;
        $this->lang = $lang;
    }

    public function hooks() {
        add_shortcode('hmpc_lang_switcher', array($this, 'lang_switcher'));
        add_shortcode('hmpc_gtranslate', array($this, 'gtranslate_switcher'));
    }

    /**
     * Existing switcher (query-based)
     * [hmpc_lang_switcher] or [hmpc_lang_switcher style="dropdown"]
     */
    public function lang_switcher($atts) {
        $atts = shortcode_atts(array(
            'class' => 'hmpc-lang-switcher',
            'separator' => ' | ',
            'style' => 'links', // links | dropdown
        ), $atts, 'hmpc_lang_switcher');

        $supported = $this->settings->supported_langs();
        if (empty($supported)) return '';

        $current = $this->lang->get_current();

        if ($atts['style'] === 'dropdown') {
            return $this->render_dropdown_links($supported, $current, $atts);
        }

        return $this->render_link_list($supported, $current, $atts);
    }

    /**
     * NEW: Free Google Translate switcher (redirect)
     * Usage:
     *  [hmpc_gtranslate] (links)
     *  [hmpc_gtranslate style="dropdown"]
     * Optional:
     *  source="tr"  (default: settings default_lang, fallback tr)
     *  target_list="de,fr,en" (default: supported_langs)
     */
    public function gtranslate_switcher($atts) {
        $atts = shortcode_atts(array(
            'class' => 'hmpc-gtranslate',
            'separator' => ' | ',
            'style' => 'links', // links | dropdown
            'source' => '',      // if empty -> default_lang
            'target_list' => '', // if empty -> supported_langs
            'open' => 'same',    // same | new
        ), $atts, 'hmpc_gtranslate');

        $default = $this->settings->default_lang(); // you set this to tr
        $source = trim((string)$atts['source']);
        $source = $source !== '' ? strtolower(preg_replace('/[^a-zA-Z\-]/', '', $source)) : $default;
        if ($source === '') $source = 'tr';

        $targets = array();
        if (trim((string)$atts['target_list']) !== '') {
            $parts = array_filter(array_map('trim', explode(',', (string)$atts['target_list'])));
            foreach ($parts as $p) {
                $p = strtolower(preg_replace('/[^a-zA-Z\-]/', '', $p));
                if ($p !== '') $targets[] = $p;
            }
            $targets = array_values(array_unique($targets));
        } else {
            $targets = $this->settings->supported_langs();
        }

        if (empty($targets)) return '';

        // Current page URL (clean, no hmpc_lang)
        $current_url = $this->current_full_url_no_fragment();
        $current_url = remove_query_arg('hmpc_lang', $current_url);

        // Determine "active" target based on current context
        // If user is browsing in default (tr clean), show TR as current.
        $active = $this->lang->get_current();
        if (!$this->settings->is_lang_allowed($active)) $active = $default;

        $open_new = ((string)$atts['open'] === 'new');
        $target_attr = $open_new ? ' target="_blank" rel="noopener nofollow"' : ' rel="nofollow"';

        if ($atts['style'] === 'dropdown') {
            return $this->render_gtranslate_dropdown($targets, $active, $source, $current_url, $atts, $open_new);
        }

        $links = array();
        foreach ($targets as $code) {
            $label = strtoupper($code);

            // Default language = go back to clean site URL
            if ($code === $default) {
                $url = $current_url;
            } else {
                $url = $this->build_google_translate_url($source, $code, $current_url);
            }

            if ($code === $active) {
                $links[] = '<span class="hmpc-current" aria-current="true">' . esc_html($label) . '</span>';
            } else {
                $links[] = '<a href="' . esc_url($url) . '"' . $target_attr . '>' . esc_html($label) . '</a>';
            }
        }

        $sep = $atts['separator'];
        return '<div class="' . esc_attr($atts['class']) . '">' . implode(wp_kses_post($sep), $links) . '</div>';
    }

    /* ---------- RENDER HELPERS ---------- */

    private function render_link_list($supported, $current, $atts) {
        $links = array();

        foreach ($supported as $code) {
            $url = $this->build_lang_url($code);
            $label = strtoupper($code);

            if ($code === $current) {
                $links[] = '<span class="hmpc-current" aria-current="true">' . esc_html($label) . '</span>';
            } else {
                $links[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
            }
        }

        $sep = $atts['separator'];
        return '<div class="' . esc_attr($atts['class']) . '">' . implode(wp_kses_post($sep), $links) . '</div>';
    }

    private function render_dropdown_links($supported, $current, $atts) {
        $id = 'hmpc-dd-' . wp_generate_uuid4();

        $options = '';
        foreach ($supported as $code) {
            $label = strtoupper($code);
            $url = $this->build_lang_url($code);
            $selected = selected($code, $current, false);
            $options .= '<option value="' . esc_attr($url) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }

        $script = '<script>(function(){var s=document.getElementById("' . esc_js($id) . '");if(!s)return;s.addEventListener("change",function(){if(this.value){window.location.href=this.value;}});})();</script>';

        $html  = '<div class="' . esc_attr($atts['class']) . '">';
        $html .= '<select id="' . esc_attr($id) . '" class="hmpc-select" aria-label="Language selector">' . $options . '</select>';
        $html .= '</div>';
        $html .= $script;

        return $html;
    }

    private function render_gtranslate_dropdown($targets, $active, $source, $current_url, $atts, $open_new) {
        $id = 'hmpc-gt-' . wp_generate_uuid4();
        $default = $this->settings->default_lang();

        $options = '';
        foreach ($targets as $code) {
            $label = strtoupper($code);

            if ($code === $default) {
                $url = $current_url;
            } else {
                $url = $this->build_google_translate_url($source, $code, $current_url);
            }

            $selected = selected($code, $active, false);
            $options .= '<option value="' . esc_attr($url) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }

        $js = $open_new
            ? 'window.open(this.value,"_blank","noopener");'
            : 'window.location.href=this.value;';

        $script = '<script>(function(){var s=document.getElementById("' . esc_js($id) . '");if(!s)return;s.addEventListener("change",function(){if(this.value){' . $js . '}});})();</script>';

        $html  = '<div class="' . esc_attr($atts['class']) . '">';
        $html .= '<select id="' . esc_attr($id) . '" class="hmpc-select" aria-label="Translate with Google">' . $options . '</select>';
        $html .= '</div>';
        $html .= $script;

        return $html;
    }

    /* ---------- URL HELPERS ---------- */

    // Query-based (our system) default lang -> clean URL, others -> ?hmpc_lang=
    private function build_lang_url($lang) {
        $lang = strtolower(trim((string)$lang));

        $scheme = is_ssl() ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

        $base = $scheme . '://' . $host . $uri;

        $default = $this->settings->default_lang();

        if ($lang === $default) {
            return remove_query_arg('hmpc_lang', $base);
        }

        return add_query_arg('hmpc_lang', rawurlencode($lang), $base);
    }

    private function build_google_translate_url($source, $target, $url) {
        $source = strtolower(preg_replace('/[^a-zA-Z\-]/', '', (string)$source));
        $target = strtolower(preg_replace('/[^a-zA-Z\-]/', '', (string)$target));

        // Google Translate web redirect (free)
        return add_query_arg(
            array(
                'sl' => $source,
                'tl' => $target,
                'u'  => rawurlencode($url),
            ),
            'https://translate.google.com/translate'
        );
    }

    private function current_full_url_no_fragment() {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

        $url = $scheme . '://' . $host . $uri;

        $parts = wp_parse_url($url);
        if (!$parts) return $url;

        $path = isset($parts['path']) ? $parts['path'] : '/';
        $query = isset($parts['query']) ? $parts['query'] : '';
        return $scheme . '://' . $host . $path . ($query ? ('?' . $query) : '');
    }
}
