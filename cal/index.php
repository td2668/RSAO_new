<?php
/*
	
	Copyright (c) Reece Pegues
	sitetheory.com

    Reece PHP Calendar is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or 
	any later version if you wish.

    You should have received a copy of the GNU General Public License
    along with this file; if not, write to the Free Software
    Foundation Inc, 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	
*/

// see if there is already a session, and if not create one.
if(session_id()==""){
	session_start();
}


// security bit
define("CAL_SECURITY_BIT",1);

// load required files
require('config.php');
require('sql_layer.php');
require('gatekeeper.php');
require('functions.php');


// Make the database connection.
$cal_db = new cal_database(CAL_SQL_HOST, CAL_SQL_USER, CAL_SQL_PASSWD, CAL_SQL_DATABASE, false);
if(!$cal_db->db_connect_id) die("Failed to connect to database...");

// get the database version and save as a session variable so we don't have an extra DB call every single page load.
/*
if($_SESSION['cal_version']!=true){
	$cal_version = $cal_db->sql_version();
	if($cal_version!=""){
		echo "<center><h3><span style='color: #FF0000;'>";
		echo "Your Database version must be at least $cal_version<br>Aborting Calendar Script";
		echo "</span></h3></center>";
		$_SESSION['cal_version'] = false;
		exit;
	}else{
		$_SESSION['cal_version'] = true;
	}
}
*/
$_SESSION['cal_version'] = true;




############### Set options and sid ###################
// load options from dB
cal_load_options();
// include the language file (suppress possible errors for security)
@include('languages/'.cal_option("language").".php"); 
// if user is logging in, check the password etc
if($_POST['user']!="") cal_check_user();
// set the permissions for the user
cal_load_permissions();
#######################################################




######################### Set Date info ########################################

// Set Month
if($_POST['month']!="") $_SESSION['cal_month'] = $_POST['month'];
elseif($_GET['month']!="") $_SESSION['cal_month'] = $_GET['month'];
elseif($_SESSION['cal_month']=="") $_SESSION['cal_month'] = date('n');

// Set year
if($_POST['year']!="") $_SESSION['cal_year'] = $_POST['year'];
elseif($_GET['year']!="") $_SESSION['cal_year'] = $_GET['year'];
elseif($_SESSION['cal_year']=="") $_SESSION['cal_year'] = date('Y');

// Set day
if($_POST['day']!="") $_SESSION['cal_day'] = $_POST['day'];
elseif($_GET['day']!="") $_SESSION['cal_day'] = $_GET['day'];
elseif($_SESSION['cal_day']=="") $_SESSION['cal_day'] = date('j');

// the max day can change, so we just adjust for this.
// nothing should change if the day is within the month and year's actual day ranges.
// this is also important because it removes sql injection stuff from posted dates given to the server as a bonus.
$adjust_time = mktime(0,0,0,$_SESSION['cal_month'],$_SESSION['cal_day'],$_SESSION['cal_year']);
$_SESSION['cal_year'] = date("Y",$adjust_time);
$_SESSION['cal_month'] = date("n",$adjust_time);
$_SESSION['cal_day'] = date("d",$adjust_time);

// extra year check. We have to do this since if the year goes way out of wack due to a really strange day number, 
// it will not throw mysql off and enter a time of 0 when it should be 9999 for endless repeating events, etc.
// not sure what will happen if a date is before 1000 AD, maybe nothing?  better safe than sorry.
if($_SESSION['cal_year']<1000) $_SESSION['cal_year'] = 1000;
elseif($_SESSION['cal_year']>9999) $_SESSION['cal_year'] = 9999;

##############################################################################





##################### Figure out what file to include ########################

// get the requested action.
if($_GET['action']!="") $action = $_GET['action'];
elseif($_POST['action']!="") $action = $_POST['action'];
elseif($_SESSION['cal_action']!="") $action = $_SESSION['cal_action'];
// check if the action is forced to the login screen
if($action=="login" AND $_SESSION['cal_user']!="") $action = "";
// if user is disabled, reset action to login screen
if(cal_permission("disabled")){
	$action = "login";
	cal_logout();
}
// set the session to the new action in case it changed.
$_SESSION['cal_action'] = $action;

// process the correct action
$output = cal_top();
switch($action){
	case "add":
		include("includes/event.php");
		$output .= cal_event_form('add');
		break;
	case "delete":
		include("includes/delete.php");
		include('includes/viewdate.php');
		$del_error = cal_del();
		if($del_error!="") $output .= "<center><span class='failure'>$del_error</span></center><br>";
		$output .=  cal_display();
		break;
	case "modify":
		include("includes/event.php");
		$output .=  cal_event_form('modify');
		break;
	case "viewdate":
		include("includes/viewdate.php");
		$output .=  cal_display();
		break;
	case "viewevent":
		include("includes/viewevent.php");
		$output .=  cal_display();
		break;
	case "search":
		include("includes/search.php");
		$output .=  cal_search_form();
		break;
	case "submitevent":
		include('includes/eventsub.php');
		include('includes/viewdate.php');
		$sub_error = cal_submit_event();
		if($sub_error!="") $output .= "<center><span class='failure'>$sub_error</span></center><br>";
		$output .=  cal_display();
		$_SESSION['cal_action'] = "viewdate";
		break;
	case "admin":
		include('includes/admin.php');
		$output .= cal_adminsection();
		break;
	case "login":
		$_SESSION['cal_noautologin'] = 1;
		include('includes/login.php');
		$output .=  cal_login_page();
		break;
	case "logout":
		cal_logout();
		$_SESSION['cal_noautologin'] = 1;
		cal_clear_permissions();
		cal_load_permissions();
		// if you log out and anonymous is disabled, show login page.
		if(cal_permission("disabled")){
			include('includes/login.php');
			$output .=  cal_login_page();
			break;
		}
	default: 
		include('includes/calendar.php');
		$output .= cal_calendar($_SESSION['cal_year'],$_SESSION['cal_month'],$_SESSION['cal_day']);
		break;
} // end switch
$output .=  cal_bottom();
echo $output;

############################################################################



?>
