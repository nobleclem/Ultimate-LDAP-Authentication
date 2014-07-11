<?php
/*
Plugin Name: Ultimate Ldap Authentication
Description: LDAP authentication plugin that also allows for ldap group to wordpress role management.
Version: 1.0
Author: Patrick Springstubbe
Author URI: http://springstubbe.us
*/


include dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'ldap.php';
include dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'admin.php';

$uLdapAuthSettings = get_option( 'uldapauth_settings' );
if( @$uLdapAuthSettings['enabled'] ) {
    include 'includes'. DIRECTORY_SEPARATOR .'authentication.php';

    // DISABLES LOCAL WP AUTHENTICATION
    if( isset( $uLdapAuthSettings['wpfallback'] ) && !$uLdapAuthSettings['wpfallback'] ) {
        remove_action('authenticate', 'wp_authenticate_username_password', 20);
    }
}



// ACTIVATION, DEACTIVATION, UNINSTALL HOOKS
// register uninstall hook
register_deactivation_hook( __FILE__, 'uldapauth_deactivate' );
register_uninstall_hook( __FILE__, 'uldapauth_uninstall' );

if( !empty( $GLOBALS['pagenow'] ) && ($GLOBALS['pagenow'] === 'plugins.php') ) {
    add_action( 'admin_notices', 'uldapauth_checkrequirements' );
}

function uldapauth_checkrequirements()
{
    $errors = array();

    // php version
    $phpVerReq = '5.2.9';

    if( version_compare( $phpVerReq, phpversion(), '>' ) ) {
        $errors[] = 'Your server is running <strong>PHP '. phpversion() .'</strong> but this plugin requires at least <strong>PHP '. $phpVerReq .'</strong>.';
    }

    // check for require extensions
    $required = array(
        'ldap'
    );

    foreach( $required as $ext ) {
        if( !extension_loaded( $ext ) ) {
            if( !function_exists( 'dl' ) || !dl( $ext .'so' ) ) {
                $errors[] = "The <strong>{$ext}</strong> extension is required.";
            }
        }
    }

    if( $errors ) {
        // Suppress "Plugin activated" notice.
        unset( $_GET['activate'] );

        // this plugin's name
        $name = get_file_data( __FILE__, array ( 'Plugin Name' ), 'plugin' );

        printf(
            '<div class="error"><ul><li>%1$s</li></ul>
            <p><i>%2$s</i> has been deactivated.</p></div>',
            join( '</li><li>', $errors ),
            $name[0]
        );

        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}


// delete settings
function uldapauth_uninstall()
{
    if( is_multisite() ) {
        $currentBlogId = get_current_blog_id();

        foreach( wp_get_sites() as $site ) {
            switch_to_blog( $site->blog_id );

            delete_option( 'uldapauth_settings' );
            delete_option( 'uldapauth_permissions' );
        }

        switch_to_blog( $currentBlogId );
    }
    else {
        delete_option( 'uldapauth_settings' );
        delete_option( 'uldapauth_permissions' );
    }
}
