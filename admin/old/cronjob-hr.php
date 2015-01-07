#!/usr/bin/php

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

// CLAERO LOCAL DEVELOPMENT
// 20090224 CSN added configuration for Claero Systems local development
$configInfos["localhost"]["server_name"] = 'localhost';
$configInfos["localhost"]["host"] = 'localhost';
$configInfos["localhost"]["user"] = 'ors';
$configInfos["localhost"]["pass"] = 'rilinc';
$configInfos["localhost"]["dbdriver"] = 'mysql';
$configInfos["localhost"]["dbname"] = 'research';
$configInfos["localhost"]["peardir"] = '/usr/lib/php/';
$configInfos["localhost"]["debug"] = true;
//$configInfos["localhost"]["authmethod"] = 'usertable';
$configInfos["localhost"]["url_root"] = 'http://localhost';
$configInfos["localhost"]["file_root"] = '/Applications/XAMPP/htdocs';
//$configInfos["localhost"]["adodb_root"] = 

/*if (strpos($_SERVER['HTTP_HOST'],':') != 0) {
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
}*/



$configInfo = $configInfos["orsadmin.mtroyal.ca"];


error_reporting(E_ALL);
set_time_limit(120);

//$filepath = "/var/www/orsadmin-prep_htdocs/";
//$mail_file_path="/var/www/orsadmin-prep_htdocs/admin/mail_upload/";
$hr_extract_path="/var/www/secure-store/hr_extract/";
$hr_extract_contract_path="/var/www/secure-store/hr_extract_contract/";
//include("{$filepath}admin/includes/config.inc.php"); 
//Due to changes - replaced by following
//$host = "localhost";
//$user = "ors";
//$dbpassword = "rilinc";
//$database = "research";

#The server name for email based links. Not the ors-admin server.
//$server_name = "research.mtroyal.ca";
//$connection = mysql_connect($host, $user, $dbpassword) or die(mysql_error());
//mysql_select_db($database,$connection) or die(mysql_error());

//Do the second connection using adodb5
require_once('includes/adodb5/adodb.inc.php');
// load the required pear libraries
/*if ( (include_once($configInfo["peardir"].'pat/patTemplate.php')) == false ) {
    require_once('pat/patTemplate.php');
}
if ( (include_once($configInfo["peardir"].'pat/patErrorManager.php')) == false ) {
    require_once('pat/patErrorManager.php');
}*/

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

//die('Here');

$todays_date = mktime();
$tomorrow = strtotime("+1 day");
$deadline_change_email='tdavis@mtroyal.ca, jcameron@mtroyal.ca, project-1876272-666b25425609eb8bd6c83d7e@basecamp.com';
$hr_extract_email="ischuyt@mtroyal.ca,tdavis@mtroyal.ca";
$logmail_recipient='tdavis@mtroyal.ca, jcameron@mtroyal.ca, ischuyt@mtroyal.ca';

//standard file locations
$picture_path = "../pictures/";
$public_picture_path = "pictures/";
$docs_path = "../researchdocs/";


//Standard date format.
$iso8601 = "Y-m-d G:i";
$iso8601_day = "Y-m-d";

include_once("{$configInfo['file_root']}admin/includes/functions-required.php");
include_once("{$configInfo['file_root']}admin/includes/mail-functions.php");

//open log file
if (!$logfile = fopen("{$configInfo['file_root']}admin/mail_log.txt","a+")) die("Mail Log Is Not Writeable");
$date=date("Y-m-d G:i",$todays_date);
fwrite($logfile,"-----------------\nDate: $date\n\n");
$logmail="-----------------\nDate: $date\n\n";



//Routine to import HR Banner Contracts extract.


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


//Routine to import HR Banner extract.


$loaded=$filecount=$linecount=$errcount=0;
foreach (glob($hr_extract_path . '*.lis') as $fullname) {
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



if($filecount > 0 || $errcount > 0){
    $message="\nHR Contract Extracts Processed: $filecount files, $linecount entries, $errcount errors.\n\n";
    fwrite($logfile,$message);
    mail($hr_extract_email,'[HR Imports]',$message,"From:research@mtroyal.ca");
}

fclose($logfile);





?>
