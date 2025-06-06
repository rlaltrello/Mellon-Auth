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


require_once 'Mellon-Auth-Settings.php';


class MellonAuth
{

    public function on_loaded()
    {

        $username = @$_SERVER['MELLON_email'];

        $alloweddomaincheck = false;  //initially assume domain is not allowed

        $mellon_auth_options = get_option('mellon_auth_option_name'); // get array from settings page

        $createuser = $mellon_auth_options['create_new_user'] ?? null; // checkbox makes this variable "disappear" 
        // so handle it with fancy null coalescing operator

        $alloweddomains = $mellon_auth_options['domain_names'] ?? null;  // comma separated list of allowed domain names
	$redirectlocation = $mellon_auth_options['redirect_location'] ?? null; //where to land the authenticated user

        $domainarray = explode(',', $alloweddomains);  // split them into array

        $tmp = explode('@', $username);  // grab domain of authenticated mellon user's email address
        $userdomain = end($tmp);         //

        if (!isset($alloweddomains) || empty($alloweddomains)) { // no domain restrictions
            $alloweddomaincheck = true;  // allow them in
        } else { //lower case the allowed domains and check them against the user's domain
            $lowerdomainarray = array_map('strtolower', $domainarray); // lowercase array
            $alloweddomaincheck = in_array(strtolower($userdomain), $lowerdomainarray);
        }

        //$usercheck = get_userdata( $username );

        //grab the wordpress user (if it exists)
        $user = get_user_by('login', $username);

        if (is_login()) {  // check if we are on a worpress login page

            if ($createuser && !$user && $alloweddomaincheck) { //wordpress user doesn't exist, create it if allowed domain
                $user_id = wp_insert_user(
                    array(
                        'user_login' => @$_SERVER['MELLON_email'],
                        'user_email' => @$_SERVER['MELLON_email'],
                        'first_name' => @$_SERVER['MELLON_fname'],
                        'last_name' => @$_SERVER['MELLON_lname'],
                        'display_name' => @$_SERVER['MELLON_fname'] . " " . @$_SERVER['MELLON_lname'],
                        'role' => 'subscriber'
                    )
                );

                if ( is_multisite() ) {  // if it is a multisite, add them to the sites as subscriber
                    $sites = get_sites( [
                        'limit' => 0,
                        'public' => true,
                        'spam' => false,
                        'deleted' => false,
                        'archived' => false,
                        'mature' => false,
                     ] );
                
                     foreach ( $sites as $site ) {
                         $site_id = get_object_vars( $site )['blog_id'];
                         $add_user = add_user_to_blog( $site_id, $user_id, 'subscriber' );
                     }
                
                }

                $user = get_user_by('id', $user_id);
            }

	    if ($redirectlocation === "admin") {
                  $redirect_to= user_admin_url();
	    } else { //default
		  $redirect_to=site_url();
	    }

            if (!is_wp_error($user) && $user && $alloweddomaincheck) { // user exist and domain is allowed

                //log them into wp
                wp_clear_auth_cookie();
                wp_set_current_user($user->ID, $user->user_login);
                wp_set_auth_cookie($user->ID, TRUE);
                update_user_caches($user);

                // check to make sure they are logged in
                if (is_user_logged_in()) {
                    //user is logged in, send them to the appropriate admin page
                    wp_safe_redirect($redirect_to);
                    exit;
                }

            } else {  //explain to the user what could have happened
                echo ("<h1>You are seeing this local login because your SSO account was not found</h1>");
                echo ("<h1>or your domain ($userdomain) is not authorized to use SSO for this site.</h1>");
            }
        }

    }
}


$my_mellonauth = new MellonAuth();
add_action('wp_loaded', array($my_mellonauth, 'on_loaded'));


?>
