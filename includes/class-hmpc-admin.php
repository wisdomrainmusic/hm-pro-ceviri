<?php
class HMPC_Admin
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void
    {
        add_options_page(
            'HM Pro Çeviri',
            'HM Pro Çeviri',
            'manage_options',
            HMPC_SLUG,
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page(): void
    {
        echo '<div class="wrap"><h1>HM Pro Çeviri</h1><p>Settings coming soon.</p></div>';
    }
}
