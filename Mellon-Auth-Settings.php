<?php
class MellonAuthSettingsPage
{
     // Holds the values to be used in the fields callbacks
    private $options;

    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Mellon-Auth Settings Admin', 
            'Mellon-Auth Settings', 
            'manage_options', 
            'mellon-auth-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'mellon_auth_option_name' );
        ?>
        <div class="wrap">
            <h1>Mellon-Auth Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'mellon_auth_option_group' );
                do_settings_sections( 'mellon-auth-setting-admin' );
                submit_button();
		  

            ?>
            </form>
        </div>
        <?php
    }

    public function page_init()
    {        
        register_setting(
            'mellon_auth_option_group', // Option group
            'mellon_auth_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Single Sign-on Authentication Settings', // Name
            array( $this, 'print_section_info' ), // Callback
            'mellon-auth-setting-admin' // Page
        );  

        add_settings_section(
            'mellon_section_id', // ID
            'Mellon Information', // Name
            array( $this, 'print_mellon_info' ), // Callback
            'mellon-auth-setting-admin' // Page
        );  

        add_settings_field(
            'create_new_user', // ID
            'Create new user upon initial login?', // Name 
            array( $this, 'create_new_user_callback' ), // Callback
            'mellon-auth-setting-admin', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'domain_names', //ID
            'Allowed Domain Names (comma separated list - or leave blank to allow all SSO domains)', // Name 
            array( $this, 'domain_names_callback' ), // Callback
            'mellon-auth-setting-admin', // Page
            'setting_section_id' // Section
        );      
    }

     // Sanitize each setting field as needed
     //
     // @param array $input Contains all settings fields as array keys
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['create_new_user'] ) )
            $new_input['create_new_user'] = $input['create_new_user'];

        if( isset( $input['domain_names'] ) )
            $new_input['domain_names'] = sanitize_text_field( $input['domain_names'] );

        return $new_input;
    }

    public function print_mellon_info()
    {
		print "<div style='border-top: 2px solid black;padding-bottom:20px;'><b>Mellon Variables:</b></div>";
		//spit out the MELLON variable for fun and profit!
		foreach($_SERVER as $key=>$value) {
                    if(substr($key, 0, 7) == 'MELLON_') {
                     print"<div><span>". $key . '</span><span style="padding-left:20px;padding-right:20px;"> = </span><span>' . $value . "</span></div>";
                    }
                 }
		print "<div style='border-top: 2px solid black;padding-bottom:20px;'><div style='text-align:right;'><i>Mellon-Auth Plugin cobbled together by Rob Laltrello - 2024</i></div></div>";

    }

    public function print_section_info()
    {
        print 'Only authenicated users with an email address domain listed in the \'Allowed Domain Names\' list below will be allowed into the site. (e.g highlands.edu)';
    }

    public function create_new_user_callback()
    {
        echo '<input type="checkbox" id="create_new_user" name="mellon_auth_option_name[create_new_user]" value="1"' . checked( 1, @$this->options['create_new_user'],false ). '/>';
    }

    public function domain_names_callback()
    {
        printf(
            '<input type="text" id="domain_names" name="mellon_auth_option_name[domain_names]" value="%s" />',
            isset( $this->options['domain_names'] ) ? esc_attr( $this->options['domain_names']) : ''
        );
    }
}

if( is_admin() )
	$mellon_auth_settings_page = new MellonAuthSettingsPage();
?>
