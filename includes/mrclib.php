<?php
define("EAS_SERV", "ldap-dmz.mtroyal.ca");
define("EAS_PORT", 389);
define("EAS_BINDDN", "uid=readonly,o=MRC");
define("EAS_BINDPW", "readingrailroad\$200");
define("EAS_BASE", "o=MRC");

function mrclib_exported_functions() {
	print "<pre>
These are functions exported by mrclib.php
mrclib_exported_functions:
  Prints all functions exported by this module.
  arguments: none
  return values: none

***********************************

mrclib_ldapauth
  Authenticates a userid and password
  arguments: uid, pass
	uid: username to authenticate to
	pass: password to authenticate with
  return values:
	true: user is authentic and this is their password
	false: user could not be authenticated or other error

NOTE: if you use this function, USE IT OVER SSL ONLY IF POSSIBLE.  these
  are the real usernames and passwords being passed over the wire and using
  clear text is not secure.

***********************************

mrclib_ldapinfo
  Returns ldap information for a userid
  arguments: uid[, lookup_array](opt)
	uid: username to search and lookup info for, wild cards are NOT allowed
	lookup_array: an optional array argument that can be passed.  Each element is
		and ldap attribute to be queried.  examples are:
			uid, givenName, sn
		default is
		uid, givenName, sn, employeeNumber
  return values:
	false: lookup failed, user does not exist or other error
	return value array: consisting of an associative array of values.


</pre>\n";
}

/*
 * print a response, one way if cmd line another if web
*/
function mrclib_prn_r($msg) {
	if(isset($_SERVER["REQUEST_METHOD"])) {
		print "<p>$msg</p>\n";
	} else {
		print "$msg\n";
	}
}

/*
 * passed a uid and password will auth the user
 *
 * Returns: true if auth is successful otherwise false (error, not auth)
*/
function mrclib_ldapauth($uid, $pass) {
	/* what to search for */
	$eas_ldap = array("dn","uid");
    error_reporting(0);

	/* check that password isn't blank, we won't allow anon connections. */
	if(strlen($pass) == 0) {
		return(false);
	}

	if(!($conn = ldap_connect(EAS_SERV, EAS_PORT))) {
		$terrmsg = ldap_error($conn);
		echo("<b>The Mount Royal Authentication server is not available. Please try again later.</b>");
		ldap_close($conn);
		return(false);
	}

	if(!($r = ldap_bind($conn, EAS_BINDDN, EAS_BINDPW))) {
		$terrmsg = ldap_error($conn);
		echo("<b>The Mount Royal Authentication server is not available. Please try again later.</b> ($terrmsg)");
		ldap_close($conn);
        die();
		return(false);
	}

	$search = "(uid=$uid)";

	if(!($sr = ldap_search($conn,EAS_BASE,$search,$eas_ldap))) {
		$terrmsg = ldap_error($conn);
		mrclib_prn_r("mrclib_ldapauth: could not search for $search: $terrmsg");
		ldap_close($conn);
		return(false);
	}


	if(ldap_count_entries($conn, $sr) == 0) {
		$terrmsg = ldap_error($conn);
		mrclib_prn_r("mrclib_ldapauth: could not get LDAP information: no matches found for $search: $terrmsg");
		ldap_close($conn);
		return(false);
	} else {
		if(($info = ldap_first_entry($conn, $sr)) == false) {
			$terrmsg = ldap_error($conn);
			mrclib_prn_r("mrclib_ldapauth: could not get LDAP info for first entry: $terrmsg");
			ldap_close($conn);
			return(false);
		}
		if(($dn = ldap_get_dn ($conn, $info)) == false) {
			$terrmsg = ldap_error($conn);
			mrclib_prn_r("mrclib_ldapauth: could not get LDAP DN for user $uid: $terrmsg");
			ldap_close($conn);
			return(false);
		}
		/* now bind as the new DN with the password */
		if(!($r = @ldap_bind($conn, $dn, $pass))) {
			$terrmsg = ldap_error($conn);
			mrclib_prn_r("mrclib_ldapauth: could not bind to ldap server as user $dn: $terrmsg");
			ldap_close($conn);
			return(false);
		}
	}
	ldap_close($conn);
	return(true);
}

/*
 * passed a uid will get info for the contents of the array
 * $ldap_return
 *
 * Returns: an associative array of values on success or false on error
*/

function mrclib_ldapinfo($uid, $ldap_return = array("uid","givenName","sn","employeeNumber","mail")) {

	/* test for wildcards in $uid 
	if(strstr($uid, "*") != false) {
		mrclib_prn_r("mrclib_ldapinfo: Wildcard NOT ALLOWED in uid string $uid");
		return(false);
	}*/

	if(!($conn = ldap_connect(EAS_SERV, EAS_PORT))) {
		$terrmsg = ldap_error($conn);
		mrclib_prn_r("mrclib_ldapinfo: could not connect to ldap: $terrmsg");
		ldap_close($conn);
		return(false);
	}

	if(!($r = ldap_bind($conn, EAS_BINDDN, EAS_BINDPW))) {
		$terrmsg = ldap_error($conn);
		mrclib_prn_r("mrclib_ldapinfo: could not bind to ldap: $terrmsg");
		ldap_close($conn);
		return(false);
	}

	$search = "(uid=$uid)";

	if(!($sr = ldap_search($conn,EAS_BASE,$search,$ldap_return))) {
		$terrmsg = ldap_error($conn);
		mrclib_prn_r("mrclib_ldapinfo: could not search for $search: $terrmsg");
		ldap_close($conn);
		return(false);
	}
   // print_r($ldap_return);echo "<br>";
    //echo ("Entries: ". ldap_count_entries($conn, $sr));
    print_r(ldap_get_entries($conn,$sr));
    echo "<br>";
    
	if(ldap_count_entries($conn, $sr) == 0) {
		$terrmsg = ldap_error($conn);
		mrclib_prn_r("mrclib_ldapinfo: could not get LDAP information: no matches found for $search: $terrmsg");
		ldap_close($conn);
		return(false);
	} else {
		if(($info = ldap_first_entry($conn, $sr)) == false) {
			$terrmsg = ldap_error($conn);
			mrclib_prn_r("mrclib_ldapinfo: could not get LDAP info for first entry: $terrmsg");
			ldap_close($conn);
			return(false);
		}

		if(($attrs=ldap_get_attributes($conn, $info)) == false) {
			$terrmsg = ldap_error($conn);
			mrclib_prn_r("mrclib_ldapinfo: could not get LDAP info for entry: $terrmsg");
			ldap_close($conn);
			return(false);
		}
		if(function_exists('array_change_key_case')) {
			$attrs = array_change_key_case($attrs, CASE_LOWER);
		}
		$retv = array();
		/* sort through array and return values */
		foreach($ldap_return as $key) {
			/* this makes it simply a single value */
			if(isset($attrs["$key"])) {
				if(is_array($attrs["$key"])) {
					$retv["$key"] = $attrs["$key"][0];
				} else {
					$retv["$key"] = $attrs["$key"];
				}
			}
		}
	}
	ldap_close($conn);
	return($retv);
}
