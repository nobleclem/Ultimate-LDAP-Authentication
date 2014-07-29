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

## To Do List
- Refactor code so its easier to maintain, use classes instead of function hell
- Add proper admin validation vs sanitization logic (not something WP makes easy with Settings API)
  -- return previous values and store submitted values to be used to repopulate form with errors (transient data?)
- Add MU support
  -- Manage network admin access VIA LDAP groups (require slightly different interface for this section)
  -- Allow blog admins to set ldap group access
