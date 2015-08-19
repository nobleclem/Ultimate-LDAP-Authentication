Ultimate LDAP Authentication
============================

Wordpress plugin that enables LDAP Authentication and group role management to Wordpress.

## Features
- Auto create users on login
- LDAP Group access, provisioning, and role defaults
- Option to fallback to WP authentication
- Set users default display name
- Option to change username text
- Option to add login page message
- LDAP Attribute mapping
- Auto updates name & email from ldap on each login
- Test LDAP Connection
- Verify LDAP Groups

## Installation Instructions
1. [Download](http://nobleclem.github.io/Ultimate-LDAP-Authentication/downloads/ultimate-ldap-authentication.zip) 1.0.3 release of plugin
2. Go to Wordpress Plugins -> Add New -> Upload
3. Upload zip downloaded in step 1
4. Activate
5. Upgrade if necessary to pull down latest code from github

## To Do List
- Refactor code so its easier to maintain, use classes instead of function hell
- Add proper admin validation vs sanitization logic (not something WP makes easy with Settings API)
  * return previous values and store submitted values to be used to repopulate form with errors (transient data?)
- Add MU support
  * Manage network admin access VIA LDAP groups (require slightly different interface for this section)
  * Allow blog admins to set ldap group access
