<?php
if (!defined('ABSPATH')) exit;

class HMPC_Carry {

	/** @var HMPC_Lang */
	private $lang;

	/** @var HMPC_Settings */
	private $settings;

	public function __construct(HMPC_Settings $settings, HMPC_Lang $lang) {
		$this->settings = $settings;
		$this->lang = $lang;
	}

	public function hooks() {
		// Add hmpc_lang to all generated links via core filter
		add_filter('page_link', array($this, 'add_lang_to_url'), 20);
		add_filter('post_link', array($this, 'add_lang_to_url'), 20);
		add_filter('post_type_link', array($this, 'add_lang_to_url'), 20);
		add_filter('term_link', array($this, 'add_lang_to_url'), 20);
		add_filter('attachment_link', array($this, 'add_lang_to_url'), 20);
		add_filter('author_link', array($this, 'add_lang_to_url'), 20);
		add_filter('day_link', array($this, 'add_lang_to_url'), 20);
		add_filter('month_link', array($this, 'add_lang_to_url'), 20);
		add_filter('year_link', array($this, 'add_lang_to_url'), 20);

		// Menu items
		add_filter('nav_menu_link_attributes', array($this, 'menu_link_attrs'), 20, 4);

		// Login/Register URLs
		add_filter('login_url', array($this, 'add_lang_to_url'), 20);
		add_filter('logout_url', array($this, 'add_lang_to_url'), 20);
		add_filter('register_url', array($this, 'add_lang_to_url'), 20);

		// WooCommerce links (if active)
		add_filter('woocommerce_get_cart_url', array($this, 'add_lang_to_url'), 20);
		add_filter('woocommerce_get_checkout_url', array($this, 'add_lang_to_url'), 20);
		add_filter('woocommerce_get_myaccount_page_permalink', array($this, 'add_lang_to_url'), 20);
		add_filter('woocommerce_product_add_to_cart_url', array($this, 'add_lang_to_url'), 20, 2);

		// Form actions: keep lang param on common forms
		add_filter('the_content', array($this, 'rewrite_form_actions_in_content'), 30);
	}

	private function should_carry() {
		if (is_admin()) return false;
		if (defined('DOING_AJAX') && DOING_AJAX) return false;
		if (defined('REST_REQUEST') && REST_REQUEST) return false;

		$lang = $this->lang->get_current();
		if (!$lang) return false;

		// Carry ONLY if current lang is allowed
		return $this->settings->is_lang_allowed($lang);
	}

	public function add_lang_to_url($url) {
		if (!$this->should_carry()) return $url;
		if (!is_string($url) || $url === '') return $url;

		$lang = $this->lang->get_current();

		// Do not touch mailto/tel/javascript
		if (preg_match('#^(mailto:|tel:|javascript:)#i', $url)) return $url;

		// External URLs: keep untouched
		$home = home_url('/');
		if (strpos($url, $home) !== 0 && preg_match('#^https?://#i', $url)) {
			return $url;
		}

		// Already has param
		$parsed = wp_parse_url($url);
		if (isset($parsed['query']) && strpos($parsed['query'], 'hmpc_lang=') !== false) {
			return $url;
		}

		return add_query_arg('hmpc_lang', rawurlencode($lang), $url);
	}

	public function menu_link_attrs($atts, $item, $args, $depth) {
		if (!$this->should_carry()) return $atts;

		if (!isset($atts['href']) || !$atts['href']) return $atts;
		$atts['href'] = $this->add_lang_to_url($atts['href']);
		return $atts;
	}

	public function rewrite_form_actions_in_content($content) {
		if (!$this->should_carry()) return $content;

		$lang = $this->lang->get_current();
		if (!$lang) return $content;

		// Very conservative: only forms that post to same-site URLs or relative actions.
		// Avoid heavy DOM parsing for shared hosting.
		$content = preg_replace_callback(
			'#<form([^>]*?)action=("|\')(.*?)\2([^>]*)>#i',
			function($m) use ($lang) {
				$before = $m[1];
				$quote  = $m[2];
				$action = $m[3];
				$after  = $m[4];

				$action_trim = trim($action);

				// If empty action, leave it
				if ($action_trim === '') return $m[0];

				// Skip external
				if (preg_match('#^https?://#i', $action_trim)) {
					$home = home_url('/');
					if (strpos($action_trim, $home) !== 0) return $m[0];
				}

				// Skip mailto/tel/js
				if (preg_match('#^(mailto:|tel:|javascript:)#i', $action_trim)) return $m[0];

				// Add param if missing
				if (strpos($action_trim, 'hmpc_lang=') === false) {
					$action_trim = add_query_arg('hmpc_lang', rawurlencode($lang), $action_trim);
				}

				return '<form' . $before . 'action=' . $quote . esc_attr($action_trim) . $quote . $after . '>';
			},
			$content
		);

		return $content;
	}
}
