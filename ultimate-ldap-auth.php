<?php
/**
 * Plugin Name: Ultimate Ldap Authentication
 * Plugin URI: https://github.com/umichcreative/Ultimate-LDAP-Authentication/
 * Description: LDAP authentication plugin that also allows for ldap group to wordpress role management.
 * Version: 1.0.3
 * Author: U-M: Michigan Creative
 * Author URI: http://creative.umich.edu
 */

define( 'ULDAPAUTH_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

include_once ULDAPAUTH_PATH .'includes'. DIRECTORY_SEPARATOR .'ldap.php';
include_once ULDAPAUTH_PATH .'includes'. DIRECTORY_SEPARATOR .'admin.php';

$uLdapAuthSettings = get_option( 'uldapauth_settings' );
if( @$uLdapAuthSettings['enabled'] ) {
    include ULDAPAUTH_PATH .'includes'. DIRECTORY_SEPARATOR .'authentication.php';

    // DISABLES LOCAL WP AUTHENTICATION
    if( isset( $uLdapAuthSettings['wpfallback'] ) && !$uLdapAuthSettings['wpfallback'] ) {
        remove_action('authenticate', 'wp_authenticate_username_password', 20);
    }
}


function uldapauth_github_updater_init()
{

    // UPDATER SETUP
    if( !class_exists( 'WP_GitHub_Updater' ) ) {
        include_once ULDAPAUTH_PATH .'includes'. DIRECTORY_SEPARATOR .'updater.php';
    }
    //define( 'WP_GITHUB_FORCE_UPDATE', true );

    if( is_admin() ) {
        new WP_GitHub_Updater(array(
            // this is the slug of your plugin
            'slug' => plugin_basename(__FILE__),
            // this is the name of the folder your plugin lives in
            'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
            // the github API url of your github repo
            'api_url' => 'https://api.github.com/repos/umichcreative/Ultimate-LDAP-Authentication',
            // the github raw url of your github repo
            'raw_url' => 'https://raw.githubusercontent.com/umichcreative/Ultimate-LDAP-Authentication/master',
            // the github url of your github repo
            'github_url' => 'https://github.com/umichcreative/Ultimate-LDAP-Authentication',
             // the zip url of the github repo
            'zip_url' => 'https://github.com/umichcreative/Ultimate-LDAP-Authentication/zipball/master',
            // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
            'sslverify' => true,
            // which version of WordPress does your plugin require?
            'requires' => '3.0',
            // which version of WordPress is your plugin tested up to?
            'tested' => '3.9.1',
            // which file to use as the readme for the version number
            'readme' => 'README.md',
            // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
            'access_token' => '',
        ));
    }
}
add_action( 'init', 'uldapauth_github_updater_init' );


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
