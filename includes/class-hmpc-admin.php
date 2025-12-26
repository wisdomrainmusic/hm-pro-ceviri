<?php
if (!defined('ABSPATH')) exit;

class HMPC_Admin {

	/** @var HMPC_Settings */
	private $settings;

	public function __construct(HMPC_Settings $settings) {
		$this->settings = $settings;
	}

	public function hooks() {
		add_action('admin_menu', array($this, 'menu'));
		add_action('admin_init', array($this, 'handle_save'));
	}

	public function menu() {
		add_options_page(
			'HM Pro Çeviri',
			'HM Pro Çeviri',
			'manage_options',
			'hmpc-settings',
			array($this, 'render')
		);
	}

	public function handle_save() {
		if (!isset($_POST['hmpc_save'])) return;
		if (!current_user_can('manage_options')) return;

		check_admin_referer('hmpc_save_settings', 'hmpc_nonce');

		$new = array();

		$new['supported_langs'] = isset($_POST['supported_langs']) ? sanitize_text_field(wp_unslash($_POST['supported_langs'])) : '';
		$new['default_lang']    = isset($_POST['default_lang']) ? sanitize_text_field(wp_unslash($_POST['default_lang'])) : '';
		$new['autodetect']      = isset($_POST['autodetect']) ? '1' : '0';
		$new['cookie_days']     = isset($_POST['cookie_days']) ? (string) max(1, (int) $_POST['cookie_days']) : '30';
		$new['legacy_prefix_redirect'] = isset($_POST['legacy_prefix_redirect']) ? '1' : '0';

		// Keep cookie name stable unless you explicitly want it in UI later
		$new['cookie_name'] = $this->settings->get('cookie_name');

		$this->settings->update($new);

		add_settings_error('hmpc_messages', 'hmpc_saved', 'Settings saved.', 'updated');
	}

	public function render() {
		if (!current_user_can('manage_options')) return;

		$all = $this->settings->get_all();
		$supported = esc_attr($all['supported_langs']);
		$default = esc_attr($all['default_lang']);
		$autodetect = ($all['autodetect'] === '1');
		$cookie_days = (int) $all['cookie_days'];
		$legacy = ($all['legacy_prefix_redirect'] === '1');

		settings_errors('hmpc_messages');
		?>
		<div class="wrap">
			<h1>HM Pro Çeviri</h1>

			<form method="post">
				<?php wp_nonce_field('hmpc_save_settings', 'hmpc_nonce'); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="supported_langs">Supported languages</label></th>
						<td>
							<input type="text" id="supported_langs" name="supported_langs" class="regular-text" value="<?php echo $supported; ?>" />
							<p class="description">Comma-separated codes. Example: en,tr,de,fr</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="default_lang">Default language</label></th>
						<td>
							<input type="text" id="default_lang" name="default_lang" class="regular-text" value="<?php echo $default; ?>" />
							<p class="description">Must be included in Supported languages.</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Auto-detect</th>
						<td>
							<label>
								<input type="checkbox" name="autodetect" <?php checked($autodetect); ?> />
								Use browser Accept-Language when no query/cookie exists
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="cookie_days">Cookie duration (days)</label></th>
						<td>
							<input type="number" id="cookie_days" name="cookie_days" min="1" max="3650" value="<?php echo esc_attr($cookie_days); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row">Legacy prefix redirect</th>
						<td>
							<label>
								<input type="checkbox" name="legacy_prefix_redirect" <?php checked($legacy); ?> />
								Redirect /{lang}/... to /... (no prefix mode)
							</label>
							<p class="description">Example: /en/test → /test</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" name="hmpc_save" class="button button-primary">Save Settings</button>
				</p>
			</form>
		</div>
		<?php
	}
}
