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
		), $atts, 'hmpc_lang_switcher');

		$supported = $this->settings->supported_langs();
		if (empty($supported)) return '';

		$current = $this->lang->get_current();

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

		return '<div class="' . esc_attr($atts['class']) . '">' . implode(wp_kses_post($atts['separator']), $links) . '</div>';
	}

	private function build_lang_url($lang) {
		$lang = strtolower(trim((string)$lang));

		// Keep current URL path, overwrite hmpc_lang only
		$scheme = is_ssl() ? 'https' : 'http';
		$host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
		$uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

		$base = $scheme . '://' . $host . $uri;
		return add_query_arg('hmpc_lang', rawurlencode($lang), $base);
	}
}
