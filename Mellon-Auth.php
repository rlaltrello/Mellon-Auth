<?
/*
Plugin Name:  Mellon-Auth
Plugin URI:   https://www.laltrello.com
Description:  A plugin to utilize mod_auth_mellon for WordPress authentication
Version:      1.0.2
Author:       Rob Laltrello
Author URI:   https://www.laltrello.com/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  mellon-auth
Domain Path:  /languages
*/


<?php
/*
Plugin Name:  Mellon-Auth
... (headers remain the same)
*/

require_once 'Mellon-Auth-Settings.php';

class MellonAuth {

    public function __construct() {
        // Use the authenticate filter instead of wp_loaded
        // Priority 10, 3 arguments ($user, $username, $password)
        add_filter('authenticate', array($this, 'mellon_authenticate'), 10, 3);
    }

    public function mellon_authenticate($user, $username, $password) {
        // 1. If we are already logged in or Mellon variables are missing, bail.
        if (empty($_SERVER['MELLON_email'])) {
            return $user; 
        }

        // 2. Fetch Settings
        $options = get_option('mellon_auth_option_name');
        $create_enabled = $options['create_new_user'] ?? false;
        $allowed_domains_raw = $options['domain_names'] ?? '';
        $redirect_pref = $options['redirect_location'] ?? 'site';

        // 3. Sanitize and Validate User Info from Mellon
        $mellon_email = sanitize_email($_SERVER['MELLON_email']);
        $mellon_fname = sanitize_text_field($_SERVER['MELLON_fname'] ?? '');
        $mellon_lname = sanitize_text_field($_SERVER['MELLON_lname'] ?? '');

        // 4. Domain Whitelist Check
        $tmp = explode('@', $mellon_email);
        $user_domain = strtolower(end($tmp));
        $is_allowed = false;

        if (empty($allowed_domains_raw)) {
            $is_allowed = true; // Fail-open per your original design
        } else {
            // Trim whitespace and lowercase for strict comparison
            $domain_array = array_map('trim', explode(',', strtolower($allowed_domains_raw)));
            if (in_array($user_domain, $domain_array)) {
                $is_allowed = true;
            }
        }

        if (!$is_allowed) {
            return new WP_Error('denied_domain', "<strong>SSO Error</strong>: Your domain ($user_domain) is not authorized.");
        }

        // 5. Look for existing user
        $wp_user = get_user_by('email', $mellon_email);

        // 6. Create user if they don't exist
        if (!$wp_user && $create_enabled) {
            $user_id = wp_insert_user(array(
                'user_login'   => $mellon_email, // Using email as login for consistency
                'user_email'   => $mellon_email,
                'first_name'   => $mellon_fname,
                'last_name'    => $mellon_lname,
                'display_name' => trim("$mellon_fname $mellon_lname"),
                'role'         => 'subscriber',
                'user_pass'    => wp_generate_password() // Random pass for SSO users
            ));

            if (is_wp_error($user_id)) {
                return $user_id;
            }

            // Multisite: Only add to current blog to prevent timeouts on large networks
            if (is_multisite()) {
                add_user_to_blog(get_current_blog_id(), $user_id, 'subscriber');
            }

            $wp_user = get_user_by('id', $user_id);
        }

        // 7. Final Check & Redirect
        if ($wp_user) {
            // Set the cookies manually since we are bypassing the password form
            wp_set_auth_cookie($wp_user->ID, true);
            
            $redirect_to = ($redirect_pref === 'admin') ? admin_url() : home_url();
            wp_safe_redirect($redirect_to);
            exit;
        }

        return $user;
    }
}

new MellonAuth();


?>
