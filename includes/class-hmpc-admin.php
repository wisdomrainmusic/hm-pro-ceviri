<?php
if (!defined('ABSPATH')) exit;

class HMPC_Admin {
    /** @var HMPC_Settings */
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
    }

    public function hooks() {
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings_page() {
        add_options_page(
            __('HM Pro Çeviri', 'hm-pro-ceviri'),
            __('HM Pro Çeviri', 'hm-pro-ceviri'),
            'manage_options',
            'hmpc-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('hmpc_settings_group', HMPC_Settings::OPTION_KEY, [$this, 'sanitize_settings']);

        add_settings_section(
            'hmpc_main_section',
            __('Language Resolution', 'hm-pro-ceviri'),
            function () {
                echo '<p>' . esc_html__('Configure language resolution and behavior.', 'hm-pro-ceviri') . '</p>';
            },
            'hmpc-settings'
        );

        add_settings_field(
            'hmpc_supported_languages',
            __('Supported languages', 'hm-pro-ceviri'),
            [$this, 'render_supported_languages_field'],
            'hmpc-settings',
            'hmpc_main_section'
        );

        add_settings_field(
            'hmpc_default_language',
            __('Default language', 'hm-pro-ceviri'),
            [$this, 'render_default_language_field'],
            'hmpc-settings',
            'hmpc_main_section'
        );

        add_settings_field(
            'hmpc_enable_autodetect',
            __('Enable browser auto-detect', 'hm-pro-ceviri'),
            [$this, 'render_autodetect_field'],
            'hmpc-settings',
            'hmpc_main_section'
        );

        add_settings_field(
            'hmpc_cookie_days',
            __('Cookie lifetime (days)', 'hm-pro-ceviri'),
            [$this, 'render_cookie_days_field'],
            'hmpc-settings',
            'hmpc_main_section'
        );

        add_settings_field(
            'hmpc_cookie_name',
            __('Cookie name', 'hm-pro-ceviri'),
            [$this, 'render_cookie_name_field'],
            'hmpc-settings',
            'hmpc_main_section'
        );

        add_settings_field(
            'hmpc_enable_prefix_redirect',
            __('Clean legacy URL prefix', 'hm-pro-ceviri'),
            [$this, 'render_prefix_redirect_field'],
            'hmpc-settings',
            'hmpc_main_section'
        );
    }

    public function sanitize_settings($input) {
        return $this->settings->sanitize($input);
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('HM Pro Çeviri', 'hm-pro-ceviri'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('hmpc_settings_group');
                do_settings_sections('hmpc-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_supported_languages_field() {
        $value = (string) $this->settings->get('supported_langs');
        ?>
        <input type="text" name="<?php echo esc_attr(HMPC_Settings::OPTION_KEY); ?>[supported_langs]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Comma-separated language codes (e.g., en, tr, de).', 'hm-pro-ceviri'); ?></p>
        <?php
    }

    public function render_default_language_field() {
        ?>
        <select name="<?php echo esc_attr(HMPC_Settings::OPTION_KEY); ?>[default_lang]">
            <?php foreach ($this->settings->supported_langs() as $lang) : ?>
                <option value="<?php echo esc_attr($lang); ?>" <?php selected($this->settings->get('default_lang'), $lang); ?>><?php echo esc_html($lang); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function render_autodetect_field() {
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(HMPC_Settings::OPTION_KEY); ?>[autodetect]" value="1" <?php checked($this->settings->get('autodetect'), '1'); ?> />
            <?php esc_html_e('Auto-detect from browser Accept-Language header.', 'hm-pro-ceviri'); ?>
        </label>
        <?php
    }

    public function render_cookie_days_field() {
        ?>
        <input type="number" min="1" name="<?php echo esc_attr(HMPC_Settings::OPTION_KEY); ?>[cookie_days]" value="<?php echo esc_attr((int) $this->settings->get('cookie_days')); ?>" />
        <p class="description"><?php esc_html_e('How many days the language cookie should persist.', 'hm-pro-ceviri'); ?></p>
        <?php
    }

    public function render_cookie_name_field() {
        ?>
        <input type="text" name="<?php echo esc_attr(HMPC_Settings::OPTION_KEY); ?>[cookie_name]" value="<?php echo esc_attr($this->settings->get('cookie_name')); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Override the cookie name used to store the current language.', 'hm-pro-ceviri'); ?></p>
        <?php
    }

    public function render_prefix_redirect_field() {
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(HMPC_Settings::OPTION_KEY); ?>[legacy_prefix_redirect]" value="1" <?php checked($this->settings->get('legacy_prefix_redirect'), '1'); ?> />
            <?php esc_html_e('Redirect URLs with legacy /{lang}/ prefix to prefix-less paths.', 'hm-pro-ceviri'); ?>
        </label>
        <?php
    }
}
