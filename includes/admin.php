<?php

global $uldapAuthLink;

/* ADMIN PAGE LOGIC */
function uldapauth_config_admin_menu()
{
    // ADD ADMIN MENU LINK
    add_submenu_page(
        is_multisite() ? 'settings.php' : 'options-general.php',
        'Ultimate LDAP Auth',
        'Ultimate LDAP Auth',
        'administrator',
        'uldapauth',
        'uldapauth_settings'
    );

    // GET CURRENT SETTINGS
    $uLdapAuthSettings = get_option( 'uldapauth_settings' );

    if( !$uLdapAuthSettings['wpfallback'] ) {
        add_filter( 'show_password_fields', 'uldapauth_show_password_fields' );

        add_action( 'check_passwords', 'uldapauth_check_passwords', 10, 3 );
        add_action( 'user_profile_update_errors', 'uldapauth_user_profile_update_errors', 10, 3 );
    }

    // LDAP AUTH SETTING SETUP
    register_setting(
        'uldapauth',
        'uldapauth_settings',
        'uldapauth_settings_validate'
    );

    // general options
    add_settings_section(
        'uldapauth_settings_general',
        'General Settings',
        'uldapauth_settings_general_section',
        'uldapauth'
    );
    add_settings_field(
        'uldapauth_settings_enabled',
        'LDAP Authentication',
        'uldapauth_radios',
        'uldapauth',
        'uldapauth_settings_general',
        array(
            'name'  => 'uldapauth_settings[enabled]',
            'notes' => 'Enables/Disabled Ultimate LDAP Authentication plugin.',
            'value' => @$uLdapAuthSettings['enabled'] ? $uLdapAuthSettings['enabled'] : '0',
            'options' => array(
                '1' => 'Enabled',
                '0' => 'Disabled'
            ),
        )
    );
    add_settings_field(
        'uldapauth_settings_autocreate',
        'Auto-Create WP Accounts?',
        'uldapauth_radios',
        'uldapauth',
        'uldapauth_settings_general',
        array(
            'name'  => 'uldapauth_settings[autocreate]',
            'notes' => 'Create account when user authenticates against LDAP and WP account does not already exist.',
            'value' => @$uLdapAuthSettings['autocreate'] ? $uLdapAuthSettings['autocreate'] : '0',
            'options' => array(
                '1' => 'Yes',
                '0' => 'No'
            ),
        )
    );
    add_settings_field(
        'uldapauth_settings_fallback',
        'Local Authentication Fallback?',
        'uldapauth_radios',
        'uldapauth',
        'uldapauth_settings_general',
        array(
            'name'  => 'uldapauth_settings[wpfallback]',
            'notes' => 'If LDAP Authentication fails use wordpress local account authentication.  Allows local user username/password creation and usage.',
            'value' => isset( $uLdapAuthSettings['wpfallback'] ) ? $uLdapAuthSettings['wpfallback'] : '1',
            'options' => array(
                '1' => 'Yes',
                '0' => 'No'
            ),
        )
    );
    add_settings_field(
        'uldapauth_settings_nameformat',
        'Display Name Format',
        'uldapauth_select',
        'uldapauth',
        'uldapauth_settings_general',
        array(
            'name'  => 'uldapauth_settings[nameformat]',
            'notes' => 'Sets the default display name format to use for new account creations.',
            'value' => isset( $uLdapAuthSettings['nameformat'] ) ? $uLdapAuthSettings['nameformat'] : 'firstlast',
            'options' => array(
                'username'  => 'username',
                'first'     => 'firstname',
                'last'      => 'lastname',
                'firstlast' => 'firstname lastname',
                'lastfirst' => 'lastname firstname'
            ),
        )
    );
    add_settings_field(
        'uldapauth_settings_username_label',
        'Username Label',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_general',
        array(
            'name'  => 'uldapauth_settings[username_label]',
            'value' => @$uLdapAuthSettings['username_label'] ? $uLdapAuthSettings['username_label'] : '',
            'notes' => 'Rename the "Username" Label to something custom to fit your organization.  Leave blank for WP default'
        )
    );
    add_settings_field(
        'uldapauth_settings_login_message',
        'Login Message',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_general',
        array(
            'name'  => 'uldapauth_settings[login_message]',
            'value' => @$uLdapAuthSettings['login_message'] ? $uLdapAuthSettings['login_message'] : '',
            'notes' => 'Provide a message to users before they login.'
        )
    );

    // connection details
    add_settings_section(
        'uldapauth_settings_connection',
        'Connection Details',
        'uldapauth_settings_connection_section',
        'uldapauth'
    );
    add_settings_field(
        'uldapauth_settings_protocol',
        'Server Encryption',
        'uldapauth_radios',
        'uldapauth',
        'uldapauth_settings_connection',
        array(
            'name'  => 'uldapauth_settings[protocol]',
            'notes' => 'Select none to connect over ldap://, Select SSL to connect over ldaps://, Select TLS to connect using TLS encryption.',
            'value' => @$uLdapAuthSettings['protocol'] ? $uLdapAuthSettings['protocol'] : '',
            'options' => array(
                ''    => 'None',
                'ssl' => 'SSL',
                'tls' => 'TLS'
            ),
        )
    );
    add_settings_field(
        'uldapauth_settings_address',
        'Server Address',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_connection',
        array(
            'name'  => 'uldapauth_settings[address]',
            'value' => @$uLdapAuthSettings['address'] ? $uLdapAuthSettings['address'] : '',
            'notes' => 'The name or IP address of the LDAP server. The protocol should be left out. (e.g. ldap.example.com)'
        )
    );
    add_settings_field(
        'uldapauth_settings_port',
        'Server Port',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_connection',
        array(
            'name'  => 'uldapauth_settings[port]',
            'value' => @$uLdapAuthSettings['port'] ? $uLdapAuthSettings['port'] : '',
            'notes' => 'Port Number of the LDAP server. (LDAP: Linux=389, Windows=3268) (LDAPS: Linux=636, Windows=3269)'
        )
    );
    add_settings_field(
        'uldapauth_settings_searchdn',
        'Search DN',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_connection',
        array(
            'name'  => 'uldapauth_settings[searchdn]',
            'value' => @$uLdapAuthSettings['searchdn'] ? $uLdapAuthSettings['searchdn'] : '',
            'notes' => 'The base DN in which to carry out LDAP searches.'
        )
    );
    add_settings_field(
        'uldapauth_settings_searchdn_user',
        'Search User DN',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_connection',
        array(
            'name'  => 'uldapauth_settings[searchdn_user]',
            'value' => @$uLdapAuthSettings['searchdn_user'] ? $uLdapAuthSettings['searchdn_user'] : '',
            'notes' => 'Some systems do not allow anonymous searching for attributes, and so this will set the account to use when connecting for searches.'
        )
    );
    add_settings_field(
        'uldapauth_settings_searchdn_user_pass',
        'Search User Password',
        'uldapauth_password',
        'uldapauth',
        'uldapauth_settings_connection',
        array(
            'name'  => 'uldapauth_settings[searchdn_user_pass]',
            'value' => @$uLdapAuthSettings['searchdn_user_pass'] ? $uLdapAuthSettings['searchdn_user_pass'] : '',
            'notes' => 'Password for the Search User DN'
        )
    );
    add_settings_field(
        'uldapauth_settings_connectiontest',
        'Test LDAP Connection?',
        'uldapauth_radios',
        'uldapauth',
        'uldapauth_settings_connection',
        array(
            'name'  => 'uldapauth_settings[connectiontest]',
            'notes' => 'Test connection to the LDAP Server & Test User/Pass if provided.',
            'value' => '0',
            'options' => array(
                '1' => 'Yes',
                '0' => 'No'
            ),
        )
    );
    
    // attribute mapping
    add_settings_section(
        'uldapauth_settings_attributes',
        'Attribute Mapping',
        'uldapauth_settings_attributes_section',
        'uldapauth'
    );
    add_settings_field(
        'uldapauth_settings_attr_search',
        'User Search Attribute',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_attributes',
        array(
            'name'  => 'uldapauth_settings[attr_search]',
            'value' => @$uLdapAuthSettings['attr_search'] ? $uLdapAuthSettings['attr_search'] : 'uid',
            'notes' => 'The attribute of the username that should be searched against. (Linux=uid, Windows=samaccountname)'
        )
    );
    add_settings_field(
        'uldapauth_settings_attr_member',
        'Member Search Attribute',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_attributes',
        array(
            'name'  => 'uldapauth_settings[attr_member]',
            'value' => @$uLdapAuthSettings['attr_member'] ? $uLdapAuthSettings['attr_member'] : 'member',
            'notes' => 'The attribute of the username that should be searched against. (Linux=member, Windows=uniquemember)'
        )
    );
    add_settings_field(
        'uldapauth_settings_attr_email',
        'Users Email',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_attributes',
        array(
            'name'  => 'uldapauth_settings[attr_email]',
            'value' => @$uLdapAuthSettings['attr_email'] ? $uLdapAuthSettings['attr_email'] : 'mail'
        )
    );
    add_settings_field(
        'uldapauth_settings_attr_firstname',
        'Users Givenname',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_attributes',
        array(
            'name'  => 'uldapauth_settings[attr_firstname]',
            'value' => @$uLdapAuthSettings['attr_firstname'] ? $uLdapAuthSettings['attr_firstname'] : 'givenname',
            'notes' => '<small><em>First name</em></small>'
        )
    );
    add_settings_field(
        'uldapauth_settings_attr_lastname',
        'Users Surname',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_attributes',
        array(
            'name'  => 'uldapauth_settings[attr_lastname]',
            'value' => @$uLdapAuthSettings['attr_lastname'] ? $uLdapAuthSettings['attr_lastname'] : 'sn',
            'notes' => '<small><em>Last name</em></small>'
        )
    );
    add_settings_field(
        'uldapauth_settings_attr_group',
        'Group Search Attribute',
        'uldapauth_input',
        'uldapauth',
        'uldapauth_settings_attributes',
        array(
            'name'  => 'uldapauth_settings[attr_group]',
            'value' => @$uLdapAuthSettings['attr_group'] ? $uLdapAuthSettings['attr_group'] : 'cn',
            'notes' => 'The attribute of the username that should be searched against. (Linux=member, Windows=uniquemember)'
        )
    );

    // LDAP GROUP PERMISSION SETUP
    register_setting(
        'uldapauth',
        'uldapauth_permissions',
        'uldapauth_permissions_validate'
    );
    add_settings_section(
        'uldapauth_permission',
        'Group Permissions',
        'uldapauth_settings_permissions_section',
        'uldapauth'
    );
}
add_action( (is_multisite() ? 'network_' : '') .'admin_menu', 'uldapauth_config_admin_menu' );

function uldapauth_settings()
{
?>
<div class="wrap">
    <h2>Ultimate LDAP Authentication</h2>
    <form action="options.php" method="post" name="options">
        <? settings_fields( 'uldapauth' ); ?>
        <? do_settings_sections( 'uldapauth' ); ?>
        <? submit_button(); ?>
    </form>
</div>
<?php
}


function uldapauth_input( $args, $return = false )
{
    if( $return ) {
        ob_start();
    }

    echo '<input type="text" name="'. @$args['name'] .'" value="'. esc_attr( @$args['value'] ) .'" class="regular-text" />';

    if( @$args['notes'] ) {
        echo '<br/>'. @$args['notes'];
    }

    if( $return ) {
        return ob_get_clean();
    }
}

function uldapauth_password( $args, $return = false )
{
    if( $return ) {
        ob_start();
    }

    echo '<input type="password" name="'. @$args['name'] .'" value="'. esc_attr( @$args['value'] ) .'" class="regular-text" />';

    if( @$args['notes'] ) {
        echo '<br/>'. @$args['notes'];
    }

    if( $return ) {
        return ob_get_clean();
    }
}

function uldapauth_checkbox( $args, $return = false )
{
    if( $return ) {
        ob_start();
    }

    echo '<input type="checkbox" name="'. @$args['name'] .'" value="'. esc_attr( @$args['value'] ) .'"'. ( @$args['checked'] ? ' checked="checked"' : null) .' />';

    if( @$args['notes'] ) {
        echo '<br/>'. @$args['notes'];
    }

    if( $return ) {
        return ob_get_clean();
    }
}

function uldapauth_radios( $args, $return = false )
{
    if( $return ) {
        ob_start();
    }

    $i = 1;
    foreach( (array) @$args['options'] as $key => $label ) {
        $id = @$args['name'] .'-'. $i;
        $id = str_replace( ']', '', str_replace( '[', '--', $id ) );

        echo "\n";
        echo '<input type="radio" id="'. $id .'" name="'. @$args['name'] .'" value="'. $key .'"'. ( @$args['value'] == $key ? ' checked="checked"' : null) .' />';
        echo '<label for="'. $id .'">'. $label .'</label>';

        $i++;
    }

    if( @$args['notes'] ) {
        echo '<br/>'. @$args['notes'];
    }

    if( $return ) {
        return ob_get_clean();
    }
}

function uldapauth_select( $args, $return = false )
{
    if( $return ) {
        ob_start();
    }

    echo '<select name="'. @$args['name'] .'">';

    foreach( (array) @$args['options'] as $key => $label ) {
        echo "\n";
        echo '<option value="'. $key .'"'. ( @$args['value'] == $key ? ' selected="selected"' : null) .'>'. $label .'</option>';
    }

    echo '</select>';

    if( @$args['notes'] ) {
        echo '<br/>'. @$args['notes'];
    }

    if( $return ) {
        return ob_get_clean();
    }
}

function uldapauth_settings_validate( $input )
{
    global $uldapAuthLink;

    $required = array(
        'attr_search'    => 'Search Attribute',
        'attr_email'     => 'Email Attribute',
        'attr_firstname' => 'Givenname Attribute',
        'attr_lastname'  => 'Surname Attribute'
    );
    if( @$input['enabled'] ) {
        $required += array(
            'enabled'  => 'Authentication Status',
            'protocol' => 'Server Encryption',
            'address'  => 'Server Address',
            'port'     => 'Server Port'
        );
    }

    $errors = false;

    foreach( $input as $key => &$val ) {
        $val = trim( $val );

        if( !$val && isset( $required[ $key ] ) ) {
            $errors = true;

            add_settings_error(
                'uldapauth_settings_'. $key,
                'error',
                $required[ $key ] .' is required',
                'error'
            );
        }
    }

    // test the connection if enabled or connectiontest requested
    if( (@$input['enabled'] || @$input['connectiontest']) && !$errors ) {
        $protocol = 'ldap://';
        if( @$input['protocol'] == 'ssl' ) {
            $protocol = 'ldaps://';
        }

        $link = ldap_connect(
            $protocol . @$input['address'] .':'. @$input['port']
        );

        ldap_set_option( $link, LDAP_OPT_PROTOCOL_VERSION, 3 );

        if( @$input['protocol'] == 'tls' ) {
            ldap_start_tls( $link );
        }

        if( !@ldap_bind( $link ) ) {
            add_settings_error(
                'uldapauth_settings_connection',
                'error',
                'Unable to connect to LDAP server, LDAP Authentication has been disabled.',
                'error'
            );

            $input['enabled'] = 0;
        }
        else {
            $uldapAuthLink = $link;

            add_settings_error(
                'uldapauth_settings_connection',
                'connection-ok',
                'Connection to LDAP server Successful',
                'updated'
            );
        }

        // test user/pass
        if( @$input['searchdn_user'] && @$input['searchdn_user_pass'] ) {
            if( @ldap_bind( $input['searchdn_user'], $input['searchdn_user_pass'] ) ) {
                add_settings_error(
                    'uldapauth_settings_connection_auth',
                    'connection-auth-ok',
                    'LDAP Search User/Pass OK',
                    'updated'
                );
            }
            else {
                add_settings_error(
                    'uldapauth_settings_connection_auth',
                    'error',
                    'LDAP Search User/Pass Invalid',
                    'error'
                );

                $input['searchdn_user_pass'] = null;
            }
        }
    }

    unset( $input['connectiontest'] );

    return $input;
}

function uldapauth_permissions_validate( $input )
{
    global $uldapAuthLink;

    $new = array();
    $uLdapAuthSettings = get_option( 'uldapauth_settings' );

    $i = 1;
    foreach( $input as $item ) {
        $item['group'] = trim( $item['group'] );

        if( $item['group'] ) {
            if( $uldapAuthLink ) {
                $search = @ldap_search(
                    $uldapAuthLink,
                    $uLdapAuthSettings['searchdn'],
                    "( {$uLdapAuthSettings['attr_group']}={$item['group']} )",
                    array(
                        $uLdapAuthSettings['attr_member'],
                        $uLdapAuthSettings['attr_group']
                    )
                );
                $res = @ldap_get_entries( $uldapAuthLink, $search );

                if( !$res['count'] ) {
                    add_settings_error(
                        'uldapauth_permissions_'. $i,
                        'error',
                        'Unable to find the group: <em>'. $item['group'] .'</em>, group has been deactivated.',
                        'error'
                    );

                    $item['active'] = 0;
                }
            }

            $new[ $i ] = $item;
            $i++;
        }
    }

    return $new;
}

function uldapauth_settings_general_section()
{
}

function uldapauth_settings_connection_section()
{
    echo '<p>Enter the connection parameters below for your ldap server.</p>';
}

function uldapauth_settings_attributes_section()
{
    echo '<p>You can use the following to customize the ldap attributes used when searching and pulling user data into wordpress.</p>';
}

function uldapauth_settings_permissions_section()
{
    $uLdapAuthPermissions = get_option( 'uldapauth_permissions' );

    $roles = array(
        '' => 'Select Role'
    );
    foreach( get_editable_roles() as $key => $role ) {
        $roles[ $key ] = $role['name'];
    }

    $rows = (is_array( $uLdapAuthPermissions ) ? count( $uLdapAuthPermissions ) : 0) + 1;

    echo '<p>Use the following to automatically set a users permissions based on an LDAP group membership. If a user is a member of multiple groups defined then it will give them the highest level of access permitted.</p>';

    echo '
    <table class="widefat" style="width: auto;">
        <tr>
            <th>Active</th>
            <th>Group Name</th>
            <th>Wordpress Role</th>
            <th>Auto-Create User</th>
        </tr>
    ';

    for( $i = 1; $i <= $rows; $i++ ) {
        echo '
        <tr>
            <td>
            <input type="hidden" name="uldapauth_permissions['. $i .'][active]" value="0" />
            '. uldapauth_checkbox(array(
                'name'    => 'uldapauth_permissions['. $i .'][active]',
                'value'   => 1,
                'checked' => @$uLdapAuthPermissions[ $i ]['active'] ? true : false
            ), true) .'</td>
            <td>'. uldapauth_input(array(
                'name'  => 'uldapauth_permissions['. $i .'][group]',
                'value' => @$uLdapAuthPermissions[ $i ]['group']
            ), true) .'</td>
            <td>'. uldapauth_select(array(
                'name'    => 'uldapauth_permissions['. $i .'][role]',
                'value'   => @$uLdapAuthPermissions[ $i ]['role'],
                'options' => $roles
            ), true) .'</td>
            <td>'. uldapauth_radios(array(
                'name'    => 'uldapauth_permissions['. $i .'][autocreate]',
                'value'   => @$uLdapAuthPermissions[ $i ]['autocreate'] ? @$uLdapAuthPermissions[ $i ]['autocreate'] : '0',
                'options' => array(
                    '1' => 'Yes',
                    '0' => 'No'
                )
            ), true) .'</td>
        </tr>';
    }

    echo '
    </table>
    <p><em>To delete a row just delete the group name text for that row.</em></p>
    ';
}


// THESE MODIFY EXISTING WORDPRESS USER CREATION / EDIT STUFF
function uldapauth_show_password_fields()
{
    return false;
}

function uldapauth_check_passwords( $userlogin, &$pass1, &$pass2 )
{
    $pass1 = $pass2 = md5( time() );
}

function uldapauth_user_profile_update_errors( &$errors, $update, &$user )
{
    if( $update ) {
        unset( $user->user_pass );
    }
    else {
        // get settings
        $uLdapAuthSettings    = get_option( 'uldapauth_settings' );
        $uLdapAuthPermissions = get_option( 'uldapauth_permissions' );

        $results = ULdapAuth::search(
            @$uLdapAuthSettings['searchdn'],
            "( {$uLdapAuthSettings['attr_search']}={$user->user_login} )",
            array(
                @$uLdapAuthSettings['attr_email'],
                @$uLdapAuthSettings['attr_firstname'],
                @$uLdapAuthSettings['attr_lastname'],
            )
        );

        // NO USER FOUND STOP
        if( !$results['count'] ) {
            $errors->add( 'user_login', __('<strong>ERROR</strong>: User not found in LDAP', 'uldapauth' ) );
        }
        else {
            $user->email      = $results[0][ @$uLdapAuthSettings['attr_email'] ][0];
            $user->first_name = $results[0][ @$uLdapAuthSettings['attr_firstname'] ][0];
            $user->last_name  = $results[0][ @$uLdapAuthSettings['attr_lastname'] ][0];

            switch( $uLdapAuthSettings['nameformat'] ) {
                case 'username':
                    $user->display_name = $user->user_login;
                    break;

                case 'first':
                    $user->display_name = $user->first_name;
                    break;

                case 'last':
                    $user->display_name = $user->last_name;
                    break;

                case 'lastfirst':
                    $user->display_name = trim( $user->last_name .' '. $user->first_name );
                    break;

                default:
                case 'firstlast':
                    $user->display_name = trim( $user->first_name .' '. $user->last_name );
                    break;
            }
        }
    }
}
