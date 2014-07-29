<?php

class ULdapAuth
{
    static protected $_ldapSettings;
    static protected $_link;

    // get ldap connection settings
    static public function init()
    {
        self::$_ldapSettings = get_option( 'uldapauth_settings' );
    }

    // perform a search against ldap
    static public function search( $searchDN, $filter, $attributes = array() )
    {
        // connect to ldap
        self::_link();

        // rebind to perform search
        if( @$_ldapSettings['searchdn_user'] && @$_ldapSettings['searchdn_user_pass'] ) {
            self::bind(
                @$_ldapSettings['searchdn_user'],
                @$_ldapSettings['searchdn_user_pass']
            );
        }

        // SEARCH FOR USER IN LDAP
        $search = @ldap_search(
            self::$_link,
            $searchDN,
            $filter,
            $attributes
        );

        return @ldap_get_entries( self::$_link, $search );
    }

    // bind as a user/anonymously to ldap
    static public function bind( $userDN = null, $password = null )
    {
        // connect to ldap
        self::_link();

        return @ldap_bind( self::$_link, $userDN, $password );
    }

    // establish a connection to ldap
    static private function _link()
    {
        if( self::$_link ) {
            return self::$_link;
        }

        $protocol = 'ldap://';
        if( @self::$_ldapSettings['protocol'] == 'ssl' ) {
            $protocol = 'ldaps://';
        }


        $link = ldap_connect(
            $protocol . @self::$_ldapSettings['address'] .':'. @self::$_ldapSettings['port']
        );

        ldap_set_option( $link, LDAP_OPT_PROTOCOL_VERSION, 3 );

        if( @self::$_ldapSettings['protocol'] == 'tls' ) {
            ldap_start_tls( $link );
        }

        // bind anonymously to establish the connection
        return ldap_bind( $link ) ? (self::$_link = $link) : false;
    }
}
ULdapAuth::init();
