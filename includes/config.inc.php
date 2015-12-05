<?php

// Set up the system and database parameters array to be used based on the server host

// Set up the system and database parameters array to be used based on the server host
//  NEW PRODUCTION Mount Royal Research site:
$configInfos["scholviu.v3client.com"]["server_name"] = "scholviu.v3client.com";
$configInfos["scholviu.v3client.com"]["host"] = "localhost";
$configInfos["scholviu.v3client.com"]["user"] = "scholviu_ors";
$configInfos["scholviu.v3client.com"]["pass"] = "rilinc";
$configInfos["scholviu.v3client.com"]["dbdriver"] = "mysql";
$configInfos["scholviu.v3client.com"]["dbname"] = "scholviu_research";
$configInfos["scholviu.v3client.com"]["peardir"] = '/usr/share/pear';
$configInfos["scholviu.v3client.com"]["debug"] = false;
$configInfos["scholviu.v3client.com"]["url_root"] = 'http://scholviu.v3client.com/';
$configInfos["scholviu.v3client.com"]["file_root"] = '/home/scholviu/public_html';
$configInfos["scholviu.v3client.com"]["upload_root"] = '/var/www/research_htdocs/documents/shared/uploads/';
$configInfos["scholviu.v3client.com"]["tracking_docs"] = '/var/www/research_htdocs/documents/shared/tracking/';
$configInfos["scholviu.v3client.com"]["picture_path"] = '/var/www/research_htdocs/documents/shared/pictures/';
$configInfos["scholviu.v3client.com"]["picture_url"] = 'documents/shared/pictures/';
$configInfos["scholviu.v3client.com"]["minutes_path"] = '/var/www/research_htdocs/documents/shared/committees/';
$configInfos["scholviu.v3client.com"]["minutes_url"] = 'documents/shared/committees/';
$configInfos["scholviu.v3client.com"]["master"] = "a603389b49d8da5b41bb4217e4ce5c1a";
$configInfos["scholviu.v3client.com"]["admin"] = array('tdavis','cnakamoto');
$configInfos["scholviu.v3client.com"]["master"] = "a603389b49d8da5b41bb4217e4ce5c1a";
$configInfos["scholviu.v3client.com"]["email_db_options"] =
    array(
        'type'        => 'db',
        'dsn'         => 'mysql://ors:rilinc@bckup-sigyn.mtroyal.ca/research',
        'mail_table'  => 'mail_queue',
    );

$configInfos["schol.viu.ca"]["server_name"] = "schol.viu.ca";
$configInfos["schol.viu.ca"]["host"] = "localhost";
$configInfos["schol.viu.ca"]["user"] = "scholviu_ors";
$configInfos["schol.viu.ca"]["pass"] = "rilinc";
$configInfos["schol.viu.ca"]["dbdriver"] = "mysql";
$configInfos["schol.viu.ca"]["dbname"] = "scholviu_research";
$configInfos["schol.viu.ca"]["peardir"] = '/home/scholviu/php';
$configInfos["schol.viu.ca"]["debug"] = false;
$configInfos["schol.viu.ca"]["authmethod"] = 'database,usertable';
$configInfos["schol.viu.ca"]["url_root"] = 'http://schol.viu.ca/';
$configInfos["schol.viu.ca"]["file_root"] = '/home/scholviu/public_html';
$configInfos["schol.viu.ca"]["upload_root"] = '/home/scholviu/public_html/documents/uploads/';
$configInfos["schol.viu.ca"]["tracking_docs"] = '/var/www/research_htdocs/documents/shared/tracking/';
$configInfos["schol.viu.ca"]["picture_path"] = '/var/www/research_htdocs/documents/shared/pictures/';
$configInfos["schol.viu.ca"]["picture_url"] = 'documents/shared/pictures/';
$configInfos["schol.viu.ca"]["minutes_path"] = '/var/www/research_htdocs/documents/shared/committees/';
$configInfos["schol.viu.ca"]["minutes_url"] = 'documents/shared/committees/';
$configInfos["schol.viu.ca"]["master"] = "a603389b49d8da5b41bb4217e4ce5c1a";
$configInfos["schol.viu.ca"]["admin"] = array('tdavis','cnakamoto');
$configInfos["schol.viu.ca"]["master"] = "a603389b49d8da5b41bb4217e4ce5c1a";
$configInfos["schol.viu.ca"]["email_send_now"] = true;
$configInfos["schol.viu.ca"]["debug_email"] = false;
$configInfos["schol.viu.ca"]["email_db_options"] =
    array(
        'type'        => 'db',
        'dsn'         => 'mysql://scholviu_ors:rilinc@localhost/scholviu_research',
        'mail_table'  => 'mail_queue',
    );
$configInfos["schol.viu.ca"]['email_options'] = array(
        'driver'   => 'smtp',
        'host'     => 'localhost',
        'port'     => 25,
        'auth'     => false,
        'username' => '',
        'password' => '',
    );



//  NEW PRODUCTION Mount Royal Research site:
$configInfos["research.mtroyal.ca"]["server_name"] = "bckup-sigyn/MRCResearch1";
$configInfos["research.mtroyal.ca"]["host"] = "bckup-sigyn.mtroyal.ca";
$configInfos["research.mtroyal.ca"]["user"] = "ors";
$configInfos["research.mtroyal.ca"]["pass"] = "rilinc";
$configInfos["research.mtroyal.ca"]["dbdriver"] = "mysql";
$configInfos["research.mtroyal.ca"]["dbname"] = "research";
$configInfos["research.mtroyal.ca"]["peardir"] = '/usr/share/pear';
$configInfos["research.mtroyal.ca"]["debug"] = false;
$configInfos["research.mtroyal.ca"]["url_root"] = 'http://research.mtroyal.ca/';
$configInfos["research.mtroyal.ca"]["file_root"] = '/var/www/research_htdocs/';
$configInfos["research.mtroyal.ca"]["upload_root"] = '/var/www/research_htdocs/documents/shared/uploads/';
$configInfos["research.mtroyal.ca"]["tracking_docs"] = '/var/www/research_htdocs/documents/shared/tracking/';
$configInfos["research.mtroyal.ca"]["picture_path"] = '/var/www/research_htdocs/documents/shared/pictures/';
$configInfos["research.mtroyal.ca"]["picture_url"] = 'documents/shared/pictures/';
$configInfos["research.mtroyal.ca"]["minutes_path"] = '/var/www/research_htdocs/documents/shared/committees/';
$configInfos["research.mtroyal.ca"]["minutes_url"] = 'documents/shared/committees/';
$configInfos["research.mtroyal.ca"]["master"] = "a603389b49d8da5b41bb4217e4ce5c1a";
$configInfos["research.mtroyal.ca"]["admin"] = array('tdavis','cnakamoto');
$configInfos["research.mtroyal.ca"]["master"] = "a603389b49d8da5b41bb4217e4ce5c1a";
$configInfos["research.mtroyal.ca"]["email_db_options"] =
    array(
        'type'        => 'db',
        'dsn'         => 'mysql://ors:rilinc@bckup-sigyn.mtroyal.ca/research',
        'mail_table'  => 'mail_queue',
    );

// Set up the system and database parameters array to be used based on the server host
//  NEW PRODUCTION Mount Royal Research site:
$configInfos["research-prep.mtroyal.ca"]["server_name"] = "orsadmin-prep/MRCResearch1";
$configInfos["research-prep.mtroyal.ca"]["host"] = "orsadmin-prep.sm.mtroyal.ca";
$configInfos["research-prep.mtroyal.ca"]["user"] = "ors";
$configInfos["research-prep.mtroyal.ca"]["pass"] = "rilinc";
$configInfos["research-prep.mtroyal.ca"]["dbdriver"] = "mysql";
$configInfos["research-prep.mtroyal.ca"]["dbname"] = "research";
$configInfos["research-prep.mtroyal.ca"]["peardir"] = '/usr/share/pear';
$configInfos["research-prep.mtroyal.ca"]["debug"] = false;
$configInfos["research-prep.mtroyal.ca"]["url_root"] = 'http://research-prep.mtroyal.ca/';
$configInfos["research-prep.mtroyal.ca"]["file_root"] = '/var/www/research-prep_htdocs/';
$configInfos["research-prep.mtroyal.ca"]["upload_root"] = '/var/www/research-prep_htdocs/documents/uploads/';
$configInfos["research-prep.mtroyal.ca"]["tracking_docs"] = '/var/www/research-prep_htdocs/documents/shared/tracking/';
$configInfos["research-prep.mtroyal.ca"]["picture_path"] = '/var/www/research-prep_htdocs/pictures/';
$configInfos["research-prep.mtroyal.ca"]["picture_url"] = '/documents/shared/pictures/';
$configInfos["research-prep.mtroyal.ca"]["minutes_path"] = '/var/www/research-prep_htdocs/documents/committees/';
$configInfos["research-prep.mtroyal.ca"]["master"] = "a603389b49d8da5b41bb4217e4ce5c1a";
$configInfos["research-prep.mtroyal.ca"]["admin"] = array('tdavis','cnakamoto');
$configInfos["research-prep.mtroyal.ca"]["master"] = "a603389b49d8da5b41bb4217e4ce5c1a";
$configInfos["research-prep.mtroyal.ca"]["email_db_options"] =
    array(
        'type'        => 'db',
        'dsn'         => 'mysql://ors:rilinc@orsadmin-prep.sm.mtroyal.ca/research',
        'mail_table'  => 'mail_queue',
    );
/*\

$configInfos["localhost"]["host"] = 'localhost';
$configInfos["localhost"]["user"] = 'ors';
$configInfos["localhost"]["pass"] = 'rilinc';
$configInfos["localhost"]["dbdriver"] = 'mysql';
$configInfos["localhost"]["dbname"] = 'research';
$configInfos["localhost"]["peardir"] = '/vagrant/vendor/conservatory/research-pear/';
$configInfos["localhost"]["debug"] = true;
$configInfos["localhost"]["authmethod"] = 'database,usertable';
$configInfos["localhost"]["url_root"] = 'http://localhost';
$configInfos["localhost"]["file_root"] = '//Users/tdavis/Sites/webrepo/research/tags/release-5.0/';
$configInfos["localhost"]["upload_root"] = $configInfos["localhost"]["file_root"] . '/documents/uploads/';
$configInfos["localhost"]["picture_path"] = $configInfos["localhost"]["file_root"] . '/documents/shared/pictures/';
$configInfos["localhost"]["picture_url"] = '/pictures/';
$configInfos["localhost"]["admin"] = array('tdavis','cnakamoto');
$configInfos["localhost"]["irgf_docs"] = "/documents/shared/irgf";
$configInfos["email_db_options"] =  array(
    'type'        => 'db',
    'dsn'         => 'mysql://ors:rilinc@localhost/research',
    'mail_table'  => 'mail_queue',
);

*/
$configInfos["localhost"]["host"] = 'localhost';
$configInfos["localhost"]["user"] = 'ors';
$configInfos["localhost"]["pass"] = 'rilinc';
$configInfos["localhost"]["dbdriver"] = 'mysql';
$configInfos["localhost"]["dbname"] = 'research';
$configInfos["localhost"]["peardir"] = '/vagrant/vendor/conservatory/research-pear/';
$configInfos["localhost"]["debug"] = false;
$configInfos["localhost"]["authmethod"] = 'database,usertable';
$configInfos["localhost"]["url_root"] = 'http://localhost';
$configInfos["localhost"]["file_root"] = '/Users/trevor/Documents/Sites/RSAO_new/';
$configInfos["localhost"]["upload_root"] = $configInfos["localhost"]["file_root"] . '/documents/uploads/';
$configInfos["localhost"]["picture_path"] = $configInfos["localhost"]["file_root"] . '/documents/shared/pictures/';
$configInfos["localhost"]["picture_url"] = '/pictures/';
$configInfos["localhost"]["admin"] = array('tdavis','cnakamoto');
$configInfos["localhost"]["irgf_docs"] = "/documents/shared/irgf";
$configInfos["email_db_options"] =  array(
    'type'        => 'db',
    'dsn'         => 'mysql://ors:rilinc@localhost/research',
    'mail_table'  => 'mail_queue',
);

$configInfos["admin.localhost"]["host"] = 'localhost';
$configInfos["admin.localhost"]["user"] = 'ors';
$configInfos["admin.localhost"]["pass"] = 'rilinc';
$configInfos["admin.localhost"]["dbdriver"] = 'mysql';
$configInfos["admin.localhost"]["dbname"] = 'research';
$configInfos["admin.localhost"]["peardir"] = '/vagrant/vendor/conservatory/research-pear/';
$configInfos["admin.localhost"]["debug"] = false;
$configInfos["admin.localhost"]["authmethod"] = 'database,usertable';
$configInfos["admin.localhost"]["url_root"] = 'http://localhost';
$configInfos["admin.localhost"]["file_root"] = '/Users/trevor/Documents/Sites/RSAO_new/';
$configInfos["admin.localhost"]["upload_root"] = $configInfos["localhost"]["file_root"] . '/documents/uploads/';
$configInfos["admin.localhost"]["picture_path"] = $configInfos["localhost"]["file_root"] . '/documents/shared/pictures/';
$configInfos["admin.localhost"]["picture_url"] = '/pictures/';
$configInfos["admin.localhost"]["admin"] = array('tdavis','cnakamoto');
$configInfos["admin.localhost"]["irgf_docs"] = "/documents/shared/irgf";
$configInfos['admin.localhost']["email_db_options"] =  array(
    'type'        => 'db',
    'dsn'         => 'mysql://ors:rilinc@localhost/research',
    'mail_table'  => 'mail_queue',
);



// Global variable $configinfo will be filled with correct info depending on the server name

//  AUTH  SESSION CONFIGURATION
$sessionConfig["sessionname"] = "mtroyalc_research";	// session name to use. Must contain at least one letter.
$sessionConfig["sessionexpire"] = 18000; 				// 1800 secs = 30mins

//  AUTH  AVAILABLE METHODS
// You can select the available Authorization methods to use in this comma separated global variable
// Available methods:
//   ldap 		: use the function mrclib_ldapauth($uid, $pass) defined in the mrclib.php library
//   usertable 	: use the above defined array containing the username md5(password) pairs
//   database   : use a function to connect to a database table to validate username md5(password)
// DATABASE is here as a PLACE HOLDER ONLY, Its CURRENTLY NOT IMPLEMENTED
$sessionConfig["authmethod"] = "database,usertable,ldap";

//  AUTH  USER TABLE CONFIGURATION
// currently the usage of database for username/password is not enabled
// will temporarilly use this table. On this table the passwords must be MD5
// To set your password you can go to http://www.onlinefunctions.com/
// DONT enter there one of your real passwords.

$sessionConfig["usertable"]["tdavis"] = "827ccb0eea8a706c4c34a16891f84e7b"; //"c3f3c0b98db003270f05b83495c5b765";
$sessionConfig["usertable"]["cnakamoto"] = "0d107d09f5bbe40cade3de5c71e9e9b7"; //"1df81faf231bd8eee171488cc088fad1";


$sessionConfig["usertable"]["vcalvert"] = "827ccb0eea8a706c4c34a16891f84e7b";
$sessionConfig["usertable"]["testuser2"] = "0d107d09f5bbe40cade3de5c71e9e9b7";
$sessionConfig["usertable"]["testuser3"] = "0d107d09f5bbe40cade3de5c71e9e9b7";
$sessionConfig["usertable"]["testuser4"] = "0d107d09f5bbe40cade3de5c71e9e9b7";
$sessionConfig["usertable"]["testuser5"] = "0d107d09f5bbe40cade3de5c71e9e9b7";
$sessionConfig["usertable"]["testuser6"] = "0d107d09f5bbe40cade3de5c71e9e9b7";
$sessionConfig["usertable"]["dean"] = "0d107d09f5bbe40cade3de5c71e9e9b7";
$sessionConfig["usertable"]["chair"] = "0d107d09f5bbe40cade3de5c71e9e9b7";


//  AUTH  DATABASE CONFIGURATION
$sessionConfig["dbtable"] = "users";                // table to use to check the user/pass
$sessionConfig["dbusernamefield"] = "username";     // field containing the username
$sessionConfig["dbpassfield"] = "password2";        // field containing the pasword (MD5 hash)
// NOTE: for security reasons all input username and password will be cleaned and will allow only the following characters
// for username: a-z A-Z 0-9 @ and _
// for password: a-z A-Z 0-9 ! @ # $ % ^ & * ( ) _ and " " (blank space)

// This is done to avoid SQL injection and other possible attacks

//  AUTH  LDAP CONFIGURATION
// ldap is externally configured in the mrclib.php file

// Rows per page for paginated listing
$rowsPerPage=20;




if (strpos($_SERVER['HTTP_HOST'],':') != 0) {
    list($server,$port)=explode(":",$_SERVER['HTTP_HOST']);
} else {
    $server = $_SERVER['HTTP_HOST'];
    $port = 80;
}
if (strstr($server,"www.")) {
    $server = substr($server,4);
}
            // 20090224 CSN is this really needed?
            /*
            if ($server == "localhost") {	// used on offline testing (localhost installs)
                $server2 = strtolower( gethostbyaddr (gethostbyname ($_SERVER["SERVER_NAME"])));
                //echo "[$server-$server2]";
                switch ($server2) {
                    case "localhost": //temp, maldito linux no me reconoce el hostname de mi lap
                    default:
                        $server="localhost";
                        break;	// hostname no reconocido
                }
            }
            */
if (isset($configInfos[$server])) {
    $configInfo = $configInfos[$server];
} else {
    $configInfo = $configInfos["localhost"];
}

// set up default settings
if(!isset($configInfo["debug"])) {
    $configInfo["debug"] .= false;
}
if(isset($configInfo['authmethod'])) {
    $sessionConfig["authmethod"] = $configInfo['authmethod'];
} else {
    $sessionConfig["authmethod"] = "database,ldap,usertable";
}

if ($configInfo["peardir"] != "") {
    $configInfo["peardir"] .= "/";
}
if(!isset($configInfo["email_db_options"])) {
    $configInfo["email_db_options"] =  array(
        'type'        => 'db',
        'dsn'         => 'mysql://ors:rilinc@orsadmin-prep.sm.mtroyal.ca/research',
        'mail_table'  => 'mail_queue',
    );
}
if(!isset($configInfo["email_send_now"])) {
    $configInfo["email_send_now"] =  false;
}
if(!isset($configInfo["email_options"])) {
    $configInfo['email_options'] = array(
        'driver'   => 'smtp',
        'host'     => 'localhost',
        'port'     => 25,
        'auth'     => false,
        'username' => '',
        'password' => '',
    );
}
if(!isset($configInfo["debug_email"])) $configInfo['debug_email']=false;

// set up other global settings that are common to all sites
$configInfo['status_option_type'] = array("TN" => "Tenure Track",'T' => "Tenured",'TC' => "Term Certain");
$configInfo['work_pattern_type'] = array(0 => "Teaching/Service",1 => "Teaching/Scholarship/Service");

define('MRJQUERYPATH','js/jquery-1.3.2.min.js'); // set up jquery path
define('MRCDEBUG',$configInfo["debug"]); // set up debug mode
define('MRCAJAXLOGIN',true); // set up ajax login

$iso8601 = "Y-m-d G:i";
$iso8601_day = "Y-m-d";
$niceday = "M. j, Y";

define ("MRUPDF_REGULAR_FONT_SIZE", 10);
define ("MRUPDF_SMALLER_FONT_SIZE", 9);
define ("MRUPDF_H1_FONT_SIZE", 12);
define ("MRUPDF_H2_FONT_SIZE", 11);
define ("MRUPDF_H3_FONT_SIZE", 10);
define ("MRUPDF_H4_FONT_SIZE", 12);

define ("MRUPDF_REGULAR_FONT_FACE", 'times');
define ("MRUPDF_SMALLER_FONT_FACE", 'times');
define ("MRUPDF_H1_FONT_FACE", 'trebuc');
define ("MRUPDF_H2_FONT_FACE", 'trebuc');
define ("MRUPDF_H3_FONT_FACE", 'trebuc');
define ("MRUPDF_H4_FONT_FACE", 'trebuc');
?>
