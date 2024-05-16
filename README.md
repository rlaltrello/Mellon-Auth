A plugin to utilize mod_auth_mellon for WordPress authentication

This is a -very- rough draft.

Configure mod_auth_mellon on your apache setup (outside of this scope).

For your virtual host add:

<Location /wp-admin>
    # Mellon auth
    Include /etc/httpd/mellon/yourconfigfile.cnf
</Location>
<
<Location /wp-login.php>
    # Mellon auth 
    Include /etc/httpd/mellon/yourconfigfile.cnf
</Location>

That will force mod_auth_mellon to authenticate with your IDP and return your claims back to you in $SERVER['MELLON_xxxxxxxx'] variables when you visit those URLs. Note: you can change this 'MELLON_' prefix mod_auth_mellon config, but this is the default.

This wordpress plugin is expecting, at a minimium, MELLON_email, MELLON_fname, MELLON_lname which are email address, first name, and last name respectively.

The plug-in settings page is fairly straightforward.  There is a checkbox to create a successfully authenticated user's wordpress account (as a subscriber).  Finally, there is a field that you can populate with a comma-separated list of allowed domains.  If this field is blank, any domain that your IDP authenticated will be allowed to authenticate.  If your IDP authenticates domain1.com,domain2.com and domain3.com and you populate this box with "domain1.com,domain3.com" -- domain2.com users will not be allowed into wordpress and instead will land on a "normal" login page.

I threw this together out of frustration that there was not a plugin that that would allow me to use mellon.

Please feel free to point out any issues or improve upon what it here.  

Rob Laltrello

rob@laltrello.com