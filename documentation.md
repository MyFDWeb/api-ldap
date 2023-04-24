# LDAP API
This is a rest api communicating with a ldap server. You can use it to perform ldap operations from a frontend 
application via http requests. It is written in PHP. Therefore, also consider the [PHP LDAP Documentation](https://www.php.net/manual/de/book.ldap.php)

## CORS
To set the proper CORS headers, provide an environment variable named "ALLOW_ORIGINS" containing a comma-seperated list
of domain names which will be returned in Access-Control-Allow-Origin header.

## Address & Authorization
The LDAP Server address is provided as ldap uri via the X-LDAP-URI header. Anonymous binds are currently not supported.
Therefore, you need to provide valid bind credentials via HTTP Basic Auth.

## Parameters
All requests have to be POST requests of type application/json. The body has to contain all parameters.

## LDAP Operations
### Add
Url: `https://ldap.api.myfdweb.de/add`  
Parameters:
* `dn` The distinguished name of the new ldap entry
* `entry` An object containing key value pairs according to schema

### Delete
Url: `https://ldap.api.myfdweb.de/delete`  
Parameters:
* `dn` The distinguished name of the ldap entry to be deleted

### Modify
Url: `https://ldap.api.myfdweb.de/modify`  
Parameters:
* `dn` The distinguished name of the ldap entry to be modified
* `entry` An object containing key value pairs according to schema

### Search
Url: `https://ldap.api.myfdweb.de/search`  
Parameters:
* `base` The ldap query base
* `filter` The ldap query filter
* `attributes` A list of attributes to be returned. If empty all attributes will be returned

Returns: A list of matching ldap entries. (Template: `{ "dn": { "attribute": "value" } }`)

### Passwd
Url: `https://ldap.api.myfdweb.de/passwd`  
Parameters:
* `user` The distinguished name of the new ldap entry
* `password` The new password to be set

### Rename
Url: `https://ldap.api.myfdweb.de/rename`  
Parameters:
* `dn` The distinguished name of the ldap entry to be renamed
* `new_dn` The new distinguished name for the ldap entry
* `new_parent` The new parent element for the ldap entry