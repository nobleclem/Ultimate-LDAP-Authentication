<?php

/* @NOTES: http://ben.lobaugh.net/blog/7175/wordpress-replace-built-in-user-authentication#Creating_a_simple_web_based_authentication_service */

// DO LDAP AUTHENTICATION
function uldapauth_authenticate( $user, $username, $password )
{

    if( empty( $username ) || empty( $password ) ) {
        if( is_wp_error( $user ) ) {
            return $user;
        }

        return new WP_Error();
    }

    // user already found by a higher priority filter
    if ( is_a( $user, 'WP_User' ) ) {
        return $user;
    }

    // get settings
    $uLdapAuthSettings    = get_option( 'uldapauth_settings' );
    $uLdapAuthPermissions = get_option( 'uldapauth_permissions' );

    $results = ULdapAuth::search(
        @$uLdapAuthSettings['searchdn'],
        "( {$uLdapAuthSettings['attr_search']}={$username} )",
        array(
            @$uLdapAuthSettings['attr_email'],
            @$uLdapAuthSettings['attr_firstname'],
            @$uLdapAuthSettings['attr_lastname'],
        )
    );

    // NO USER FOUND STOP
    if( !$results['count'] ) {
        return $user ? $user : new WP_Error(
            'invalid_username',
            __( '<strong>ERROR</strong>: Invalid username', 'uLdapAuth' )
        );
    }

    // normalize attributes
    $ldapUser = array(
        'email' => $results[0][ @$uLdapAuthSettings['attr_email'] ][0],
        'first' => $results[0][ @$uLdapAuthSettings['attr_firstname'] ][0],
        'last'  => $results[0][ @$uLdapAuthSettings['attr_lastname'] ][0],
        'dn'    => $results[0]['dn'],
    );


    // STOP AUTHENTICATION FAILED
    if( !ULdapAuth::bind( $ldapUser['dn'], $password ) ) {
        return $user ? $user : new WP_Error(
            'incorrect_password',
            sprintf( __( '<strong>ERROR</strong>: The password you entered for the username <strong>%1$s</strong> is incorrect.', 'uLdapAuth' ), $username )
        );
    }

    // GET WP ROLE BASED ON GROUP PERMISSIONS
    $wpRole = false; // default to leave alone/default role
    if( $uLdapAuthPermissions ) {
        // prepare groups for ldap query
        $permGroups = array();
        foreach( $uLdapAuthPermissions as $perm ) {
            if( $perm['active'] ) {
                $permGroups[] = "{$uLdapAuthSettings['attr_group']}={$perm['group']}";
            }
        }

        // get groups in ldap
        $results = ULdapAuth::search(
            @$uLdapAuthSettings['searchdn'],
            "(&({$uLdapAuthSettings['attr_member']}={$ldapUser['dn']})(|(". implode( ')(', $permGroups ) .")))",
            array(
                @$uLdapAuthSettings['attr_member'],
                @$uLdapAuthSettings['attr_group'],
            )
        );

        // filter out roles that users isn't a member of
        foreach( $uLdapAuthPermissions as $key => $perm ) {
            foreach( $results as $res ) {
                // found keep in list move to next permission
                if( preg_grep( '/^'. preg_quote( $perm['group'] ) .'$/i', (array) $res[ @$uLdapAuthSettings['attr_group'] ] ) ) {
                    // if a member of a group that has autocreate on, override global autocreate status
                    if( !@$uLdapAuthSettings['autocreate'] && $perm['autocreate'] ) {
                        @$uLdapAuthSettings['autocreate'] = 1;
                    }

                    continue 2;
                }
            }

            // not found remove permission
            unset( $uLdapAuthPermissions[ $key ] );
        }

        // get first WP role group has access to
        $wp_roles = new WP_Roles();
        foreach( $wp_roles->get_names() as $wpRoleKey => $wpRoleName ) {
            foreach( $uLdapAuthPermissions as $perm ) {
                // found stop checking more roles
                if( $perm['role'] == $wpRoleKey ) {
                    // no role found yet OR this role has more capabilities
                    if( !$wpRole || (count( $wp_roles->get_role( $wpRoleKey )->capabilities ) > count( $wp_roles->get_role( $wpRole )->capabilities )) ) {
                        $wpRole = $perm['role'];
                    }
                }
            }
        }
    }

    // prep ldap group data
    $newLdapGroups = array();
    foreach( $uLdapAuthPermissions as $perm ) {
        $newLdapGroups[] = $perm['group'];
    }
    sort( $newLdapGroups );
    $newLdapGroups = $newLdapGroups ? $newLdapGroups : '';


    // Check if users exists
    $wpUser = new WP_User();
    // try by login then email if login lookup fails
    if( !($wpUser = $wpUser->get_data_by( 'login', $username )) ) {
        $wpUser = new WP_User();
        $wpUser = $wpUser->get_data_by( 'email', $ldapUser['email'] );
    }
    $wpUser = new WP_User( $wpUser->ID );

    // STOP: user doesn't exist and Autocreate is off
    if( !$wpUser->ID && !@$uLdapAuthSettings['autocreate'] ) {
        return $user ? $user : new WP_Error(
            'invalid_username',
            __( '<strong>ERROR</strong>: Invalid username', 'uLdapAuth' )
        );
    }
    // user doesn't exist, create it
    else if( !$wpUser->ID ) {
        switch( $uLdapAuthSettings['nameformat'] ) {
            case 'username':
                $displayName = $username;
                break;

            case 'first':
                $displayName = $ldapUser['first'];
                break;

            case 'last':
                $displayName = $ldapUser['last'];
                break;

            case 'lastfirst':
                $displayName = trim( $ldapUser['last'] .' '. $ldapUser['first'] );
                break;

            default:
            case 'firstlast':
                $displayName = trim( $ldapUser['first'] .' '. $ldapUser['last'] );
                break;
        }

        $wpUser = wp_insert_user(array(
            'user_email'   => $ldapUser['email'],
            'user_login'   => $username,
            'first_name'   => $ldapUser['first'],
            'last_name'    => $ldapUser['last'],
            'user_pass'    => $password,
            'role'         => (string) $wpRole,
            'display_name' => $displayName
        ));

        $user = new WP_User( $wpUser );

        update_user_meta( $user->ID, 'uLdapAuth_groups', $newLdapGroups );
    }
    // user exists update their info and log them in
    else {
        $user = $wpUser;

        $ldapGroups = get_user_meta( $user->ID, 'uLdapAuth_groups', true );

        $updateData = array(
            'ID'         => $user->ID,
            'first_name' => $ldapUser['first'],
            'last_name'  => $ldapUser['last'],
            'user_pass'  => $password
        );

        // check users group role permissions
        // if current and stored ldap group has changed then update role
        // else leave it as is
        if( $ldapGroups !== $newLdapGroups ) {
            $updateData['role'] = (string) $wpRole;
        }

        update_user_meta( $user->ID, 'uLdapAuth_groups', $newLdapGroups );

        // disable WP password change email during this login event
        add_filter( 'send_password_change_email', create_function( '$val', 'return false;' ) );

        // update user info
        wp_update_user( $updateData );
    }

    return $user;
}
add_filter( 'authenticate', 'uldapauth_authenticate', 10, 3 );


function uldapauth_authenticate_trans( $trans, $text = null, $domain = null )
{
    // get settings
    $uLdapAuthSettings = get_option( 'uldapauth_settings' );

    switch( $trans ) {
        case 'Username':
            if( $uLdapAuthSettings['username_label'] ) {
                $trans = $uLdapAuthSettings['username_label'];
            }
            break;
    }

    return $trans;
}
add_filter( 'gettext', 'uldapauth_authenticate_trans', 10, 3 );


function uldapauth_login_message( $message )
{
    // get settings
    $uLdapAuthSettings = get_option( 'uldapauth_settings' );

    if( $uLdapAuthSettings['login_message'] ) {
        return '<p class="message">'. $uLdapAuthSettings['login_message'] .'</p><br/>';
    }

    return $message;
}
add_filter( 'login_message', 'uldapauth_login_message' );
