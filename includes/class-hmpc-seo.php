<?php
if (!defined('ABSPATH')) exit;

class HMPC_SEO {

	/** @var HMPC_Settings */
	private $settings;

	/** @var HMPC_Lang */
	private $lang;

	public function __construct(HMPC_Settings $settings, HMPC_Lang $lang) {
		$this->settings = $settings;
		$this->lang = $lang;
	}

	public function hooks() {
		add_action('wp_head', array($this, 'output_hreflang_links'), 1);
		add_filter('get_canonical_url', array($this, 'filter_canonical'), 20);
		add_filter('rank_math/frontend/canonical', array($this, 'filter_rankmath_canonical'), 20);
	}

	public function output_hreflang_links() {
		if (is_admin() || is_feed() || is_robots()) return;

		$supported = $this->settings->supported_langs();
		if (empty($supported)) return;

		$current_url = $this->current_full_url_no_fragment();
		$current_lang = $this->lang->get_current();

		// Emit alternates for each supported lang
		foreach ($supported as $code) {
			$url = $this->with_lang($current_url, $code);
			$hreflang = $this->to_hreflang($code);
			echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url($url) . "\" />\n";
		}

		// x-default -> default language URL
		$default = $this->settings->get('default_lang');
		$default = $default ? strtolower(trim($default)) : $current_lang;
		$default_url = $this->with_lang($current_url, $default);
		echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . "\" />\n";
	}

	public function filter_canonical($canonical) {
		// If WordPress canonical exists, ensure it includes current hmpc_lang when present
		$current = $this->current_full_url_no_fragment();
		$lang = $this->lang->get_current();

		// If request has explicit query param, canonical should keep it (query-based language mode)
		if (isset($_GET['hmpc_lang'])) {
			return $this->with_lang($current, $lang);
		}

		// Otherwise keep original canonical
		return $canonical;
	}

	public function filter_rankmath_canonical($canonical) {
		// Same logic for Rank Math
		$current = $this->current_full_url_no_fragment();
		$lang = $this->lang->get_current();

		if (isset($_GET['hmpc_lang'])) {
			return $this->with_lang($current, $lang);
		}

		return $canonical;
	}

	private function current_full_url_no_fragment() {
		$scheme = is_ssl() ? 'https' : 'http';
		$host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
		$uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

		$url = $scheme . '://' . $host . $uri;

		// Remove fragment if any (rare in REQUEST_URI)
		$parts = wp_parse_url($url);
		if (!$parts) return $url;

		$path = isset($parts['path']) ? $parts['path'] : '/';
		$query = isset($parts['query']) ? $parts['query'] : '';
		return $scheme . '://' . $host . $path . ($query ? ('?' . $query) : '');
	}

	private function with_lang($url, $lang) {
		$lang = strtolower(trim((string)$lang));
		return add_query_arg('hmpc_lang', rawurlencode($lang), $url);
	}

	private function to_hreflang($code) {
		$code = strtolower(trim((string)$code));
		// If code uses dash, hreflang should keep dash (e.g., en-gb)
		// If simple, keep as is (en, tr, de)
		return $code;
	}
}
