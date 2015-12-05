#!/Applications/XAMPP/bin/php

<?php
//#!/usr/local/bin/php
//#!/cygdrive/c/php/php.exe

//$filepath = "/opt/lampp/htdocs/";

//Set up for the various servers
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
$configInfos["orsadmin.mtroyal.ca"]["picture_path"] = "/var/www/orsadmin_htdocs/admin/documents/shared/pictures/";
$configInfos["orsadmin.mtroyal.ca"]["picture_url"] = "/admin/documents/shared/pictures/";

//PREP Mount Royal
$configInfos["orsadmin-prep.mtroyal.ca"]["server_name"] = "orsadmin-prep/MRCResearch1";
$configInfos["orsadmin-prep.mtroyal.ca"]["host"] = "localhost";
$configInfos["orsadmin-prep.mtroyal.ca"]["user"] = "ors";
$configInfos["orsadmin-prep.mtroyal.ca"]["pass"] = "rilinc";
$configInfos["orsadmin-prep.mtroyal.ca"]["dbdriver"] = "mysql";
$configInfos["orsadmin-prep.mtroyal.ca"]["dbname"] = "research";
$configInfos["orsadmin-prep.mtroyal.ca"]["peardir"] = '';
$configInfos["orsadmin-prep.mtroyal.ca"]["debug"] = false;
$configInfos["orsadmin-prep.mtroyal.ca"]["url_root"] = 'http://orsadmin-prep.mtroyal.ca/';
$configInfos["orsadmin-prep.mtroyal.ca"]["file_root"] = '/var/www/orsadmin-prep_htdocs/';
$configInfos["orsadmin-prep.mtroyal.ca"]["admin"] = array('tdavis','cnakamoto');
$configInfos["orsadmin-prep.mtroyal.ca"]["picture_path"] = "/var/www/orsadmin-prep_htdocs/admin/documents/shared/pictures/";
$configInfos["orsadmin-prep.mtroyal.ca"]["picture_url"] = "/admin/documents/shared/pictures/";

$configInfos["admin.localhost"]["server_name"] = 'localhost';
$configInfos["admin.localhost"]["host"] = 'localhost';
$configInfos["admin.localhost"]["user"] = 'ors';
$configInfos["admin.localhost"]["pass"] = 'rilinc';
$configInfos["admin.localhost"]["dbdriver"] = 'mysql';
$configInfos["admin.localhost"]["dbname"] = 'research';
$configInfos["admin.localhost"]["peardir"] = '/Users/trevor/Documents/Sites/RSAO_new/admin/includes/';
$configInfos["admin.localhost"]["debug"] = false	;
$configInfos["admin.localhost"]["authmethod"] = 'usertable';
$configInfos["admin.localhost"]["url_root"] = 'http://local.orsadmin';
$configInfos["admin.localhost"]["upload_root"] = '/Users/trevor/Documents/Sites/RSAO_new/admin/documents/';
$configInfos["admin.localhost"]["upload_webroot"] = '/documents/';
$configInfos["admin.localhost"]["file_root"] = '/Users/trevor/Documents/Sites/RSAO_new/admin/';
$configInfos["admin.localhost"]["admin"] = array('tdavis','cnakamoto');
$configInfos["admin.localhost"]["irgf_docs"] = "/admin/documents/shared/irgf";
$configInfos["admin.localhost"]["logpath"] = "/Users/trevor/Documents/Sites/RSAO_new/admin";
$configInfos["admin.localhost"]["picture_path"] = "/Users/tdavis/Sites/webrepo/tags/release-4.0/admin/documents/pictures/";
$configInfos["admin.localhost"]["mail_file_path"] = '/Users/tdavis/Sites/webrepo/tags/release-4.0/admin/documents/mailfiles/';
$configInfos["admin.localhost"]["email_send_now"] = false;
$configInfos["admin.localhost"]["debug_email"] = false;
$configInfos["admin.localhost"]["email_db_options"] =
    array(
        'type'        => 'db',
        'dsn'         => 'mysql://ors:rilinc@localhost/research',
        'mail_table'  => 'mail_queue',
    );
$configInfos["admin.localhost"]['email_options'] = array(
        'driver'   => 'smtp',
        'host'     => 'localhost',
        'port'     => 25,
        'auth'     => false,
        'username' => '',
        'password' => '',
    );

/*
if (strpos($_SERVER['HTTP_HOST'],':') != 0) {
    list($server,$port)=explode(":",$_SERVER['HTTP_HOST']);
} else {
    $server = $_SERVER['HTTP_HOST'];
    $port = 80;
}
if (strstr($server,"www.")) {
    $server = substr($server,4);
}
*/


$configInfo = $configInfos["admin.localhost"];

define('MRJQUERYPATH','/js/jquery-1.3.2.min.js'); // set up jquery path
define('MRCDEBUG',$configInfo["debug"]); // set up debug mode
define('MRCAJAXLOGIN',true); // set up ajax login

if ($configInfo['debug'] || $server == 'localhost') {
    error_reporting(E_ERROR);
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

//$db->Connect('localhost','ors','rilinc','research');

$connection = mysql_connect('localhost', 'ors', 'rilinc') or die(mysql_error());
mysql_select_db('research',$connection) or die(mysql_error());


$db->Connect(
    $configInfo["host"],
    $configInfo["user"],
    $configInfo["pass"],
    $configInfo["dbname"]
);


//die('Here');

$todays_date = mktime();
$tomorrow = strtotime("+1 day");
$deadline_change_email='trevor.davis@viu.ca';
$hr_extract_email="trevor.davis@viu.ca";
$logmail_recipient='trevor.davis@viu.ca';

//standard file locations
$picture_path = "../pictures/";
$public_picture_path = "pictures/";
$docs_path = "../researchdocs/";

$hr_extract_path="/Users/trevor/Documents/Sites/extracts/";


//Standard date format.
$iso8601 = "Y-m-d G:i";
$iso8601_day = "Y-m-d";

set_include_path("{$configInfo['file_root']}includes/");

include_once("{$configInfo['file_root']}includes/functions-required.php");
include_once("{$configInfo['file_root']}includes/mail-functions.php");



//open log file
if (!$logfile = fopen("{$configInfo['file_root']}mail_log.txt","a+")) die("Mail Log Is Not Writeable");
$date=date("Y-m-d G:i",$todays_date);
fwrite($logfile,"-----------------\nDate: $date\n\n");
$logmail="-----------------\nDate: $date\n\n";



//Routine to import HR Banner Contracts extract.
/*

$loaded=$filecount=$linecount=$errcount=0;
foreach (glob($hr_extract_contract_path . '*.lis') as $fullname) {
    $errflag=FALSE;
      $filename=basename($fullname);
    //check if file has been processed
    $result=mysqlFetchRow('hrloaded',"filename='$filename'");
   // print_r ($result);
    if(!is_array($result)){
        if(!$fh=fopen($fullname,'rb')) die(mysql_error());
        //echo "Opened $filename \n";
        $filecount++;
        //load the header
        $line=fgetcsv($fh,500,';');
        //load the rest
        while(!feof($fh)){
            $line=fgetcsv($fh,500,';');
            if(is_array($line)){
               // print_r($line);
               // echo "\n"
               $linecount++;
               for($i=1;$i<count($line);$i++) $line[$i] = addslashes($line[$i]);
               //cludge becuase the mysqlInsert routine does not quote the first value in the array, assuming its a null
               $line[0]="'" . $line[0] . "'";
               //Add the ID field
               $line[]='null';
               $result=mysqlInsert('hrimport', $line);
               
               if($result != 1) {fwrite($logfile, "Error writing change: $result \n"); $errflag=TRUE; $errcount++;}
               else $loaded++;
               
            }
            //else echo "Error loading a line\n";
        }
        if(!$errflag) {
            $filenamequotes="'" . $filename . "'";
            $result=mysqlInsert('hrloaded',array($filenamequotes));
        }
        else {
            //echo "Errors encountered. File $filename will be reprocessed next time. \n";
            //Log problem and inform admin of error
            fwrite($logfile,"Error loading HR Extract File: $filename.\n\n");
        }
        fclose($fh);
        
        
        //File delete commented out during testing
        //ToDo: Re-enable file delete for hr extract (need perms change first)
        //unlink ($fullname)
    }
}//foreach

*/
//Routine to import HR Banner extract.


$loaded=$filecount=$linecount=$errcount=0;
foreach (glob($hr_extract_path . '*.csv') as $fullname) {
	echo("Processing $fullname\n");
    $errflag=FALSE;
      $filename=basename($fullname);
    //check if file has been processed
    $result=$db->GetRow("SELECT * FROM hrloaded WHERE filename='$filename'");
   //print_r ($result);
    if(count($result)<1){
        if(!$fh=fopen($fullname,'rb')) die(mysql_error());
        //echo "Opened $filename \n";
        $filecount++;
        //load the header
        $line=fgetcsv($fh);
        //load the rest
        while(!feof($fh)){
            $line=fgetcsv($fh);
            if(is_array($line)){
                //print_r($line);
                //echo "\n";
               $linecount++;
               for($i=0;$i<count($line);$i++) $line[$i] = mysql_escape_string(rtrim($line[$i]));
               $skip=FALSE;
               //check if user exists
               $tmp=$db->GetRow("SELECT * FROM hrimport WHERE emp_no=$line[0]");
               if(count($tmp) >0) {
	               reset($tmp);
	               echo ("Duplicate Entry: $line[1],$line[2] Entry 1: $line[6],$line[7]    Entry 2: $tmp[status],$tmp[dept_code]
	               ");
	               //if ($tmp['status']=='TEMP') $db->Execute("DELETE FROM hrimport WHERE hrimport_id=$tmp[hrimport_id]");
	               //else $skip=TRUE;
               }
               if(!$skip){
	               $line[1]=ucfirst(strtolower($line[1]));
	               $line[2]=ucfirst(strtolower($line[2]));
	               $line[3]=strtolower($line[1]);
	               
	               $line[5] = ($line[5]=='Y') ? '1':'0';
	               $line[9] = ($line[9]=='Y') ? '1':'0';
	               
	
	               
	               $sql="INSERT INTO hrimport SET
	    				run_date=NOW(),
	    				emp_no=$line[0],
	    				surname='$line[1]',
	    				prefrd_name='$line[2]',
	    				username='$line[3]',
	    				email='$line[4]',
	    				instructional=$line[5],
	    				status='$line[6]',
	    				dept_code='$line[7]',
	    				pos_title='$line[8]',
	    				chair=$line[9],
	    				office='$line[10]',
	    				phone='$line[11]'
	    				";
	    				
	    				//echo($sql);
	    				//die();
	
	               if ($db->Execute($sql)) {
						$loaded++;
	    			}
	               
	               else {fwrite($logfile, "Error writing change: $result \n"); $errflag=TRUE; $errcount++;}
	               
               }//skipped
            }
            //else echo "Error loading a line\n";
        }
        if(!$errflag) {
            $filenamequotes="'" . $filename . "'";
            //$result=mysqlInsert('hrloaded',array($filenamequotes));
        }
        else {
            //echo "Errors encountered. File $filename will be reprocessed next time. \n";
            //Log problem and inform admin of error
            fwrite($logfile,"Error loading HR Extract File: $filename.\n\n");
        }
        fclose($fh);
        
        
        //File delete commented out during testing
        //ToDo: Re-enable file delete for hr extract (need perms change first)
        //unlink ($fullname)
    }
}//foreach



if($filecount > 0 || $errcount > 0){
    $message="\nHR Contract Extracts Processed: $filecount files, $linecount entries, $errcount errors.\n\n";
    echo $message;
    fwrite($logfile,$message);
    mail($hr_extract_email,'[HR Imports]',$message,"From:research@mtroyal.ca");
}

fclose($logfile);





?>
