<?php

//error_reporting(E_ALL);
//error_reporting(E_ALL);

//error_reporting(0);

//error_reporting(-1);
//ini_set('display_errors', 'On');

ini_set('zend.ze1_compatibility_mode', 'Off');
$defaultTimeZone = 'Canada/Mountain';
if (function_exists('date_default_timezone_set')) { // to avoid problems in php4
    date_default_timezone_set($defaultTimeZone);
}

$configInfos = array();

//  PRODUCTION Mount Royal Research site:
$configInfos["orsadmin.mtroyal.ca"]["server_name"] = "orsadmin/MRCResearch1";
$configInfos["orsadmin.mtroyal.ca"]["host"] = "localhost";
$configInfos["orsadmin.mtroyal.ca"]["user"] = "ors";
$configInfos["orsadmin.mtroyal.ca"]["pass"] = "rilinc";
$configInfos["orsadmin.mtroyal.ca"]["dbdriver"] = "mysql";
$configInfos["orsadmin.mtroyal.ca"]["dbname"] = "research";
$configInfos["orsadmin.mtroyal.ca"]["peardir"] = '';
$configInfos["orsadmin.mtroyal.ca"]["debug"] = false;
$configInfos["orsadmin.mtroyal.ca"]["url_root"] = 'http://orsadmin.mtroyal.ca/';
$configInfos["orsadmin.mtroyal.ca"]["file_root"] = '/var/www/orsadmin_htdocs/';
$configInfos["orsadmin.mtroyal.ca"]["upload_root"] = '/var/www/orsadmin_htdocs/admin/documents/shared/uploads/';
$configInfos["orsadmin.mtroyal.ca"]["upload_webroot"] = '/admin/documents/shared/uploads/';
$configInfos["orsadmin.mtroyal.ca"]["tracking_docs"] = '/var/www/orsadmin_htdocs/admin/documents/shared/tracking/';
$configInfos["orsadmin.mtroyal.ca"]["admin"] = array('tdavis','cnakamoto');
$configInfos["orsadmin.mtroyal.ca"]["picture_path"] = "/var/www/orsadmin_htdocs/admin/documents/shared/pictures/";
$configInfos["orsadmin.mtroyal.ca"]["picture_url"] = "/admin/documents/shared/pictures/";
$configInfos["orsadmin.mtroyal.ca"]["irgf_docs"] = "/admin/documents/shared/irgf";
$configInfos["orsadmin.mtroyal.ca"]["irgf_url"] = "http://research.mtroyal.ca/documents/uploads/irgf";
$configInfos["orsadmin.mtroyal.ca"]["email_send_now"] = false;
$configInfos["orsadmin.mtroyal.ca"]["debug_email"] = false;
$configInfos["orsadmin.mtroyal.ca"]["email_db_options"] =
    array(
        'type'        => 'db',
        'dsn'         => 'mysql://ors:rilinc@localhost/research',
        'mail_table'  => 'mail_queue',
    );
$configInfos["orsadmin.mtroyal.ca"]['email_options'] = array(
        'driver'   => 'smtp',
        'host'     => 'localhost',
        'port'     => 25,
        'auth'     => false,
        'username' => '',
        'password' => '',
    );

//PREP Mount Royal
$configInfos["scholviu.v3client.com"]["server_name"] = "scholviu.v3client.com";
$configInfos["scholviu.v3client.com"]["host"] = "localhost";
$configInfos["scholviu.v3client.com"]["user"] = "scholviu_ors";
$configInfos["scholviu.v3client.com"]["pass"] = "rilinc";
$configInfos["scholviu.v3client.com"]["dbdriver"] = "mysql";
$configInfos["scholviu.v3client.com"]["dbname"] = "scholviu_research";
$configInfos["scholviu.v3client.com"]["peardir"] = '';
$configInfos["scholviu.v3client.com"]["debug"] = false;
$configInfos["scholviu.v3client.com"]["url_root"] = 'http://scholviu.v3client.com/';
$configInfos["scholviu.v3client.com"]["file_root"] = '/home/scholviu/public_html/admin';
$configInfos["scholviu.v3client.com"]["upload_root"] = '/var/www/research-prep_htdocs/documents/uploads/';
$configInfos["scholviu.v3client.com"]["tracking_docs"] = "/var/www/orsadmin-prep_htdocs/admin/documents/shared/tracking/";
$configInfos["scholviu.v3client.com"]["admin"] = array('tdavis','cnakamoto');
$configInfos["scholviu.v3client.com"]["picture_path"] = "/var/www/orsadmin-prep_htdocs/admin/documents/shared/pictures/";
$configInfos["scholviu.v3client.com"]["picture_url"] = "/admin/documents/shared/pictures/";

$configInfos["admin.schol.viu.ca"]["server_name"] = "admin.schol.viu.ca";
$configInfos["admin.schol.viu.ca"]["host"] = "localhost";
$configInfos["admin.schol.viu.ca"]["user"] = "scholviu_ors";
$configInfos["admin.schol.viu.ca"]["pass"] = "rilinc";
$configInfos["admin.schol.viu.ca"]["dbdriver"] = "mysql";
$configInfos["admin.schol.viu.ca"]["dbname"] = "scholviu_research";
$configInfos["admin.schol.viu.ca"]["peardir"] = '/home/scholviu/php';
$configInfos["admin.schol.viu.ca"]["debug"] = true;
$configInfos["admin.schol.viu.ca"]["url_root"] = 'http://admin.schol.viu.ca/';
$configInfos["admin.schol.viu.ca"]["file_root"] = '/home/scholviu/public_html/admin';
$configInfos["admin.schol.viu.ca"]["upload_root"] = '/var/www/research-prep_htdocs/documents/uploads/';
$configInfos["admin.schol.viu.ca"]["tracking_docs"] = "/var/www/orsadmin-prep_htdocs/admin/documents/shared/tracking/";
$configInfos["admin.schol.viu.ca"]["admin"] = array('tdavis','cnakamoto');
$configInfos["admin.schol.viu.ca"]["picture_path"] = "/home/scholviu/public_html/admin/documents/shared/pictures/";
$configInfos["admin.schol.viu.ca"]["picture_url"] = "/admin/documents/shared/pictures/";



$configInfos["localhost"]["server_name"] = 'localhost';
$configInfos["localhost"]["host"] = 'localhost';
$configInfos["localhost"]["user"] = 'ors';
$configInfos["localhost"]["pass"] = 'rilinc';
$configInfos["localhost"]["dbdriver"] = 'mysql';
$configInfos["localhost"]["dbname"] = 'research';
$configInfos["localhost"]["peardir"] = '/vagrant/vendor/conservatory/research-pear/';
$configInfos["localhost"]["debug"] = false;
$configInfos["localhost"]["authmethod"] = 'usertable';
$configInfos["localhost"]["url_root"] = 'http://local.orsadmin';
$configInfos["localhost"]["upload_root"] = '/Users/tdavis/Sites/webrepo/research/tags/release-4.0/admin/documents/';
$configInfos["localhost"]["file_root"] = '/vagrant/research-admin/';
$configInfos["localhost"]["admin"] = array('tdavis','cnakamoto');
$configInfos["localhost"]["irgf_docs"] = "/admin/documents/shared/irgf";
$configInfos["localhost"]["picture_path"] = "/Users/tdavis/Sites/webrepo/tags/release-4.0/admin/documents/pictures/";

$configInfos["admin.localhost"]["server_name"] = 'localhost';
$configInfos["admin.localhost"]["host"] = 'localhost';
$configInfos["admin.localhost"]["user"] = 'ors';
$configInfos["admin.localhost"]["pass"] = 'rilinc';
$configInfos["admin.localhost"]["dbdriver"] = 'mysql';
$configInfos["admin.localhost"]["dbname"] = 'research';
$configInfos["admin.localhost"]["peardir"] = '/vagrant/vendor/conservatory/research-pear/';
$configInfos["admin.localhost"]["debug"] = true	;
$configInfos["admin.localhost"]["authmethod"] = 'usertable';
$configInfos["admin.localhost"]["url_root"] = 'http://local.orsadmin';
$configInfos["admin.localhost"]["upload_root"] = '/Users/tdavis/Sites/webrepo/research/tags/release-4.0/admin/documents/';
$configInfos["admin.localhost"]["upload_webroot"] = '/documents/';
$configInfos["admin.localhost"]["file_root"] = '/Users/tdavis/Sites/webrepo/research/tags/release-4.0/admin/';
$configInfos["admin.localhost"]["admin"] = array('tdavis','cnakamoto');
$configInfos["admin.localhost"]["irgf_docs"] = "/admin/documents/shared/irgf";
$configInfos["admin.localhost"]["logpath"] = "/Users/tdavis/Sites/webrepo/research/tags/release-4.0/admin/";
$configInfos["admin.localhost"]["picture_path"] = "/Users/tdavis/Sites/webrepo/tags/release-4.0/admin/documents/pictures/";
$configInfos["admin.localhost"]["mail_file_path"] = '/Users/tdavis/Sites/webrepo/tags/release-4.0/admin/documents/mailfiles/';

if (strpos($_SERVER['HTTP_HOST'],':') != 0) {
    list($server,$port)=explode(":",$_SERVER['HTTP_HOST']);
} else {
    $server = $_SERVER['HTTP_HOST'];
    $port = 80;
}
if (strstr($server,"www.")) {
    $server = substr($server,4);
}

if (isset($configInfos[$server])) {
    $configInfo = $configInfos[$server];
} else {
    $configInfo = $configInfos["localhost"];
}
define('MRJQUERYPATH','/js/jquery-1.3.2.min.js'); // set up jquery path
define('MRCDEBUG',$configInfo["debug"]); // set up debug mode
define('MRCAJAXLOGIN',true); // set up ajax login

if ($configInfo['debug'] || $server == 'localhost') {
    error_reporting(E_ALL);
   // ini_set('display_errors', 'On');
} else {
    error_reporting(0);
}

    //error_reporting(E_ALL);

$niceday='Y-m-d';

//  Added section with adodb5 call

//This is the host that the database is on RELATIVE TO the webserver (typically localhost)
$host = $configInfo['host'];
$user = $configInfo['user'];
$dbpassword = $configInfo['pass'];
$database = $configInfo['dbname'];

$connection = mysql_connect($host, $user, $dbpassword) or  die(mysql_error());
mysql_select_db($database,$connection) or die(mysql_error());
//echo "Connection made to $host";

if (!empty($configInfo["peardir"])) {
    set_include_path(implode(PATH_SEPARATOR, array(
        $configInfo["peardir"],
        get_include_path(),
    )));
}



//Do the second connection using adodb5
require_once('adodb5/adodb.inc.php');

// load the required pear libraries
//if ( (include_once('pat/patTemplate.php')) == false ) {

require_once('pat/patTemplate.php');
    

//if ( (include_once('pat/patErrorManager.php')) == false ) {
    require_once('pat/patErrorManager.php');



$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$db = ADONewConnection($configInfo["dbdriver"]); // eg. 'mysql' or 'postgres'
if ($configInfo['debug']) $db->debug = true; //MRCDEBUG;
else $db->debug=false;
$db->Connect(
    $configInfo["host"],
    $configInfo["user"],
    $configInfo["pass"],
    $configInfo["dbname"]
);

unset($host); unset($user); unset($dbpassword); unset($database); unset($connection);

$todays_date = mktime(0,0,0);

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


$tomorrow = strtotime("+1 day");

//standard file locations
$picture_path = "pictures/";
$public_picture_path = "/admin/pictures/";
$docs_path = "../researchdocs/";
$minutes_file_path = $configInfo["file_root"] . '/admin/documents/shared/committees/';
 // "/opt/lampp/htdocs/admin/mail_upload/";

//Standard date format.
$iso8601 = "Y-m-d G:i";
$iso8601_day = "Y-m-d";

//File location for redirects used to shorten URLs
//$redirects="/home/html_root/htdocs/r";
$redirects = $configInfo['file_root'] . '/r'; // "/opt/lampp/htdocs/r";

//Person to email when deadlines are coming up in order to check validity.
$deadline_change_email='tdavis@mtroyal.ca';

$debug = $configInfo['debug'];

$server_name = $configInfo['server_name'];


function loadPage($page, $title="") {
    //global $sitemapXML;
    $fourofour = false;

    //if ($menupage == "") $menupage = $page;    // page name to use when parsing menu (for marking selected page)
    
    $tmpl = new patTemplate();
   
    $tmpl->setRoot('html');
    //$tmpl->useTemplateCache(true); //Dont enable until all content is finished
    //if(($_GET["page"] == "" or $page == $_GET["page"])) {

    if (file_exists("html/".$page.'.html')) {
        $tmpl->readTemplatesFromInput($page.'.html');
    } else {
        $tmpl->readTemplatesFromInput('404.html');
        $fourofour=true;
    }
    //parseXMLsitemap($tmpl,$menupage);        // Parse sitemap to build navigation menu

    //$tmpl->addVar("header","title",$title);
    $tmpl->applyInputFilter('ShortModifiers');
    //if($loadCenters) getCentres($tmpl);

    return $tmpl;
}

/**
* @desc cleans up a string leaving only Alphanumeric characters, underscores and whitespace (" " only)
* @param (String) String to clean up. Will do cleanup on this string parameter. The function returns void.
* @return void
*/
function cleanUp(&$var) {
    if ($var) $var = ereg_replace("[^A-Za-z0-9 _]", "", $var);
}

/**
* @desc uses cleanUp to return the given string in a cleaned format
* @param (String) String to clean up. Will do cleanup on this string parameter. The function returns void.
* @return cleaned string
*/
function CleanString($var) {
    cleanUp($var);
    return $var;
}

/** return an array with all of the relevant user information
*
*   @param      integer     the user id of the currently logged in user
*   @return     array       all the user data or empty array if not found
*/


function GetPersonData($userId) {

    global $db;

    if ($userId > 0) {
        $sql = "
            SELECT u.first_name, u.last_name, u.department_id, u.department2_id, d1.division_id,
                CONCAT(u.first_name,' ',u.last_name) AS full_name,
                ue.emp_status AS status, ue.tss AS work_pattern,
                d1.name AS department_name, d1.shortname AS department_shortname,
                d1.name AS department2_name, d1.shortname AS department2_shortname,
                di.name AS division_name,
                p1.email, p1.title, p1.secondary_title, p1.office, p1.phone, p1.fax,
                    p1.homepage, p1.profile_ext, p1.profile_short, p1.keywords, p1.description_short,
                p2.*,
                CONCAT(dean.first_name,' ',dean.last_name) AS dean_name,
                dean.user_id AS dean_user_id,
                CONCAT(chair.first_name,' ',chair.last_name) AS chair_name,
                chair.user_id AS chair_user_id
            FROM `users` AS u
            LEFT JOIN users_ext AS ue ON ue.user_id = u.user_id
            LEFT JOIN departments AS d1 ON d1.department_id = u.department_id
            LEFT JOIN departments AS d2 ON d2.department_id = u.department2_id
            LEFT JOIN divisions AS di ON di.division_id = d1.division_id
            LEFT JOIN profiles AS p1 ON p1.user_id = u.user_id
            LEFT JOIN ar_profiles AS p2 ON p2.user_id = u.user_id
            LEFT JOIN users AS dean ON dean.user_id = di.dean
            LEFT JOIN users AS chair ON chair.user_id = d1.chair
            WHERE u.user_id = {$userId}
            LIMIT 1
        ";
       // echo $sql; printr($db);
        $personData = $db->getAll($sql);
    } // if
    $personData = (isset($personData) && is_array($personData)) ? reset($personData) : array();
    // check to see if this person is a dean or a chair
    //print_r($personData);
    $personData['dean_flag'] = false;
    $personData['chair_flag'] = false;
    $sql = "SELECT division_id, name FROM divisions WHERE divisions.dean = {$userId}";
    $deanData = $db->getAll($sql);
    if (sizeof($deanData) == 1) {
        $personData['dean_flag'] = true;
        $personData['dean_division_id'] = $deanData[0]['division_id'];
    } // if
    $sql = "SELECT department_id, name FROM departments WHERE departments.chair = {$userId}";
    $chairData = $db->getAll($sql);
    if (sizeof($chairData) == 1) {
        $personData['chair_flag'] = true;
        $personData['chair_department_id'] = $chairData[0]['department_id'];
    } // if
    return $personData;

} // function GetPersonData





/** get the current school year
*
*   @return     integer     4 digit year
*/
function GetSchoolYear($timeStamp) {

    // if the month is Jan -> Aug then the year is this year, other wise it is next year
    if (date('n',$timeStamp) < 9) {
        $schoolYear = date('Y',$timeStamp);
    } else {
        $schoolYear = date('Y',$timeStamp) + 1;
    } // if
    return $schoolYear;

} // function GetSchoolYear

function CleanFilename($filename) {
    return str_replace(array('\\', '/', '"', '\'', ' ', ','), '_', strtolower($filename));
} // function CleanFilename
?>
