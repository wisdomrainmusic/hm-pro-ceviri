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
	}

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
			return $this->render_dropdown($supported, $current, $atts);
		}

		return $this->render_links($supported, $current, $atts);
	}

	private function render_links($supported, $current, $atts) {
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

	private function render_dropdown($supported, $current, $atts) {
		$id = 'hmpc-dd-' . wp_generate_uuid4();

		$options = '';
		foreach ($supported as $code) {
			$label = strtoupper($code);
			$url = $this->build_lang_url($code);
			$selected = selected($code, $current, false);
			$options .= '<option value="' . esc_attr($url) . '" ' . $selected . '>' . esc_html($label) . '</option>';
		}

		// Tiny inline script (no dependency) - safe, minimal
		$script = '<script>(function(){var s=document.getElementById("' . esc_js($id) . '");if(!s)return;s.addEventListener("change",function(){if(this.value){window.location.href=this.value;}});})();</script>';

		$html  = '<div class="' . esc_attr($atts['class']) . '">';
		$html .= '<select id="' . esc_attr($id) . '" class="hmpc-select" aria-label="Language selector">' . $options . '</select>';
		$html .= '</div>';
		$html .= $script;

		return $html;
	}

        private function build_lang_url($lang) {
                $lang = strtolower(trim((string)$lang));

                $scheme = is_ssl() ? 'https' : 'http';
                $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
                $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

                $base = $scheme . '://' . $host . $uri;

                $default = $this->settings->default_lang();

                // Default language -> clean URL (remove hmpc_lang)
                if ($lang === $default) {
                        return remove_query_arg('hmpc_lang', $base);
                }

                // Non-default -> ensure param exists
                return add_query_arg('hmpc_lang', rawurlencode($lang), $base);
        }
}
