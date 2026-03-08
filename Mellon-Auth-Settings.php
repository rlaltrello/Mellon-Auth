<?php

class MellonAuthSettingsPage {
    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function add_plugin_page() {
        add_options_page(
            'Mellon-Auth Settings Admin',
            'Mellon-Auth Settings',
            'manage_options',
            'mellon-auth-setting-admin',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        $this->options = get_option('mellon_auth_option_name');
        ?>
        <div class="wrap">
            <h1>Mellon-Auth Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('mellon_auth_option_group');
                do_settings_sections('mellon-auth-setting-admin');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'mellon_auth_option_group',
            'mellon_auth_option_name',
            array($this, 'sanitize')
        );

        add_settings_section('setting_section_id', 'SSO Settings', array($this, 'print_section_info'), 'mellon-auth-setting-admin');
        add_settings_section('mellon_section_id', 'Mellon Debug Information', array($this, 'print_mellon_info'), 'mellon-auth-setting-admin');

        add_settings_field('create_new_user', 'Create new user?', array($this, 'create_new_user_callback'), 'mellon-auth-setting-admin', 'setting_section_id');
        add_settings_field('domain_names', 'Allowed Domains (comma separated)', array($this, 'domain_names_callback'), 'mellon-auth-setting-admin', 'setting_section_id');
        add_settings_field('redirect_location', 'Redirect to:', array($this, 'redirect_location_callback'), 'mellon-auth-setting-admin', 'setting_section_id');
    }

    public function sanitize($input) {
        $new_input = array();
        $new_input['create_new_user'] = isset($input['create_new_user']) ? 1 : 0;
        $new_input['domain_names'] = sanitize_text_field($input['domain_names'] ?? '');
        
        $redirect = $input['redirect_location'] ?? 'site';
        $new_input['redirect_location'] = in_array($redirect, ['site', 'admin']) ? $redirect : 'site';

        return $new_input;
    }

    public function print_mellon_info() {
        echo "<div style='border-top: 1px solid #ccc; padding-top:10px;'><strong>Detected Mellon Variables:</strong></div>";
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'MELLON_') === 0) {
                printf('<div><code>%s</code> = <code>%s</code></div>', esc_html($key), esc_html($value));
            }
        }
    }

    public function print_section_info() {
        echo 'Configure how your mod_auth_mellon environment variables map to WordPress users.';
    }

    public function create_new_user_callback() {
        $val = $this->options['create_new_user'] ?? 0;
        printf('<input type="checkbox" name="mellon_auth_option_name[create_new_user]" value="1" %s />', checked(1, $val, false));
    }

    public function domain_names_callback() {
        $val = $this->options['domain_names'] ?? '';
        printf('<input type="text" class="regular-text" name="mellon_auth_option_name[domain_names]" value="%s" />', esc_attr($val));
    }

    public function redirect_location_callback() {
        $val = $this->options['redirect_location'] ?? 'site';
        echo '<label><input type="radio" name="mellon_auth_option_name[redirect_location]" value="site"' . checked("site", $val, false) . '/> Homepage</label><br/>';
        echo '<label><input type="radio" name="mellon_auth_option_name[redirect_location]" value="admin"' . checked("admin", $val, false) . '/> Admin Dashboard</label>';
    }
}

if (is_admin()) {
    new MellonAuthSettingsPage();
}
