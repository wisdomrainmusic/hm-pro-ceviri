<?php
if (!defined('ABSPATH')) exit;

class HMPC_Admin {

    public function init(): void {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function menu(): void {
        add_options_page(
            'HM Pro Çeviri',
            'HM Pro Çeviri',
            'manage_options',
            HMPC_SLUG,
            [$this, 'render']
        );
    }

    public function register_settings(): void {
        register_setting('hmpc_settings_group', HMPC_I18n::OPT_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => HMPC_I18n::defaults(),
        ]);
    }

    public function sanitize($input): array {
        $catalog = HMPC_I18n::get_languages_catalog();

        $default = isset($input['default_lang']) ? sanitize_key($input['default_lang']) : 'tr';
        if (!isset($catalog[$default])) $default = 'tr';

        $enabled = $input['enabled_langs'] ?? [$default, 'en'];
        if (!is_array($enabled)) $enabled = [$default, 'en'];
        $enabled = array_values(array_unique(array_map('sanitize_key', $enabled)));
        $enabled = array_values(array_filter($enabled, fn($c) => isset($catalog[$c])));

        if (!in_array($default, $enabled, true)) array_unshift($enabled, $default);

        return [
            'default_lang'  => $default,
            'enabled_langs' => $enabled,
        ];
    }

    public function render(): void {
        if (!current_user_can('manage_options')) return;

        $settings = HMPC_I18n::get_settings();
        $catalog  = HMPC_I18n::get_languages_catalog();
        $default  = $settings['default_lang'] ?? 'tr';
        $enabled  = $settings['enabled_langs'] ?? ['tr','en'];
        ?>
        <div class="wrap">
            <h1>HM Pro Çeviri</h1>
            <p><strong>Shortcode:</strong> <code>[hm_lang_switcher]</code> or <code>[hm_lang_switcher style="inline"]</code></p>

            <form method="post" action="options.php">
                <?php settings_fields('hmpc_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Default language</th>
                        <td>
                            <select name="<?php echo esc_attr(HMPC_I18n::OPT_KEY); ?>[default_lang]">
                                <?php foreach ($catalog as $code => $info): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($default, $code); ?>>
                                        <?php echo esc_html(($info['label'] ?? $code) . ' (' . $code . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Enabled languages</th>
                        <td>
                            <?php foreach ($catalog as $code => $info): ?>
                                <label style="display:inline-block;margin-right:14px;margin-bottom:8px;">
                                    <input type="checkbox"
                                        name="<?php echo esc_attr(HMPC_I18n::OPT_KEY); ?>[enabled_langs][]"
                                        value="<?php echo esc_attr($code); ?>"
                                        <?php checked(in_array($code, $enabled, true)); ?>>
                                    <?php echo esc_html(($info['native'] ?? $info['label'] ?? $code) . ' (' . $code . ')'); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description">URL mode: <code>/en/</code>, default language has no prefix.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save settings'); ?>
            </form>
        </div>
        <?php
    }
}
