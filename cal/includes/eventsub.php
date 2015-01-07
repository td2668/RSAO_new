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
if ( !defined('CAL_SECURITY_BIT') ) die("Hacking attempt");


/* ##################################################################
  cal_submit_event()
   Add's an event to the database if required fields are
   all provided, else it produces an error.
###################################################################*/
function cal_submit_event($day = NULL, $month = NULL, $year = NULL){
	global $cal_db;
	if($_POST['modify']){
		if(!is_numeric($_POST['id'])) return CAL_MISSING_INFO;
		$id = $_POST['id'];
		$modify = 1;
	} else $modify = 0;
	// get the day
	if( $day==NULL AND is_numeric($_SESSION['cal_day'])) $day   = $_SESSION['cal_day'];
	elseif(is_numeric($_POST['day'])) $day = $_POST['day'];
	else return "You did not enter the Day: $day";
	// get month
	if($month==NULL AND is_numeric($_SESSION['cal_month'])) $month = $_SESSION['cal_month'];
	elseif($_POST['month']!="" AND is_numeric($_POST['month'])) $month = $_POST['month'];
	else return "You did not enter the month";
	// get year
	if($year==NULL AND is_numeric($_SESSION['cal_year'])) $year  = $_SESSION['cal_year'];
	elseif($_POST['year']!="" AND is_numeric($_POST['year'])) $year = $_POST['year'];
	else return "You did not enter the year";
	// get the posted times
	if($_POST['hour']!="" AND is_numeric($_POST['hour'])) $hour = $_POST['hour'];
	else return "You did not enter the Hour";
	if(($_POST['pm']) && $_POST['pm'] == 1) $hour += 12;
	if($_POST['minute']!="" AND is_numeric($_POST['minute'])) $minute = $_POST['minute'];
	else return "Your did not enter the minute";
	// make sure the date is actually valid
	// must do this here or else they could put in something crazy that could somehow bypass the editpast permission
	$redotime = mktime(0,0,1,$month, $day, $year);
	$day = date("d",$redotime);
	$month = date("m",$redotime);
	$year = date("Y",$redotime);
	// if modifying, get the event and check it's data
	if($modify){
		// get event data to do permissions checking.
		$tres = cal_query_get_event($id);
		$d = $cal_db->sql_fetchrow($tres);
		if($cal_db->sql_numrows($result)==0) return CAL_DOESNT_EXIST;
		// if a private event and they are anonymous return error.
		if($d['private']>0 AND cal_anon()) return CAL_HACKING_ATTEMPT;
		// make sure they can edit other's events if it's not theirs
		if( $d['user_id']!=$_SESSION['user_id'] AND !cal_permission("editothers") ) return CAL_NO_EDITOTHERS_PERMISSION;
		// make sure user is allowed to add to the past if it's in the past
		if(!cal_permission("editpast") AND date("Y-m-d",$d['start_since_epoch']) < date("Y-m-d")) return cal_error(CAL_NO_EDITPAST_PERMISSION);
		// check edit permission
		if($d['user_id']==$_SESSION['cal_userid'] AND !cal_permission('edit')) return CAL_NO_MODIFY;
	}
	// if not modifying, make sure they have permission to write new events
	else{
		if(!cal_permission("write")) return CAL_NO_WRITE;
		// note that I dont just compare strings like above, because I don't know if passed in variables have leading zero or not
		if($year < date('Y') AND !cal_permission("editpast")) return CAL_NO_EDITPAST_PERMISSION;
		if($month < date('n') AND $year == date('Y') AND !cal_permission("editpast")) return CAL_NO_EDITPAST_PERMISSION;
		if(($day < date('j')) AND ($month == date('n')) AND ($year == date('Y')) AND !cal_permission("editpast")) return CAL_NO_EDITPAST_PERMISSION;
	}
	// repeat defaults
	$repeat_d = 0;
	$repeat_m = 0;
	$repeat_y = 0;
	$repeat_h = 0;
	$oend = "0000-00-00";
	// get the options
	$jump = $_POST['occurance_jump'];
	if($_POST['repeat_option']==1) $forever = 1;
	elseif($_POST['repeat_option']==2) $rnum = $_POST['repeat_num'];
	elseif($_POST['repeat_option']==3) $rend = $_POST['repeat_end'];
	// verify the options above are valid
	// I made the jump and rnum values max out at 1000 for performance purposes, but you can change that.
	if($rnum !=""){
		if(!is_numeric($rnum)) return CAL_EVENT_COUNT_ERROR;
		if($rnum < 1) return CAL_EVENT_COUNT_ERROR;
		if($rnum > 1000) return CAL_EVENT_COUNT_ERROR;
	}else $rnum = 0;
	if($jump !=""){
		if(!is_numeric($jump)) return CAL_REPEAT_EVERY_ERROR;
		if($jump<1) return CAL_REPEAT_EVERY_ERROR;
		if($jump>1000) return CAL_REPEAT_EVERY_ERROR;
	}else $jump = 1;
	if($rend!=""){
		$endarray = explode("-",$rend);
		if(count($endarray)!=3) return CAL_ENDING_DATE_ERROR;
		foreach($endarray as $v){ if(!is_numeric($v)) return CAL_ENDING_DATE_ERROR;}
		$rend = date("Y-m-d",mktime(0,0,1,$endarray[1], $endarray[2], $endarray[0]));
	}
	// check for repeating options
	// 1=repeat once, 2=repeat daily, 3=weekly, 4=monthy, 5=yearly, 6=holiday repeating
	switch($_POST['occurance']){
	case "2":
		$repeat_d = $jump;
		if($forever==1) $oend = "9999-00-00";
		else $oend = $rend;
		break;
	case "3":
		$repeat_d = 7*$jump;
		if($forever==1) $oend = "9999-00-00";
		else $oend = $rend;
		break;
	case "4":
		$repeat_m = $jump;
		if($forever==1) $oend = "9999-00-00";
		else $oend = $rend;
		break;
	case "5":
		$repeat_y = $jump;
		if($forever==1) $oend = "9999-00-00";
		else $oend = $rend;
		break;
	case "6":
		$repeat_h = 1;
		if($_POST['cal_holiday_lastweek']) $repeat_h = 2;
		break;
	}
	$repeat_number = $rnum;
	// get event type
	$type = $_POST['eventtype'];
	if(!is_numeric($type)) $type = 0;
	// get description
	if($_POST['description']) {
		$description = $_POST['description'];
		if(count($description)>3000) return CAL_DESCRIPTION_ERROR;
	} else $description = '';
 	// get subject
	if($_POST['subject']) {
		$subject = $_POST['subject'];
		if(count($subject)>100) return CAL_SUBJECT_ERROR;
	} else $subject = "";
	// check if private event or not
	$private = $_POST['private'];
	if($private=="1" AND !cal_anon());
	else $private=="0";
 	// get duration
	if(isset($_POST['durationhour'])) $durationhour = $_POST['durationhour'];
	else return CAL_DURATION_ERROR;
	if(isset($_POST['durationmin'])) $durationmin = $_POST['durationmin'];
	else return CAL_DURATION_ERROR;
 	// get anonymous alias
	if($_POST['alias']) $alias = $_POST['alias'];
	else $alias = "";
	// get event type:  2=full day, 3=time/duratin not specified, 4=time not specified
	$typeofevent = $_POST['eventtype'];
	if(!is_numeric($typeofevent) OR $typeofevent!=2 OR $typeofevent!=4) $typeofevent = 0;
	if(!$_POST['usetimeandduration']){
		$typeofevent = 3; 
		$hour = 0;
		$minute = 0;
	}		
	// calculate timestamp and durationstamp
	// By putting through mktime(), we don't have to check for sql injection here and ensure the date is valid at the same time.
	$timestamp = date('Y-m-d H:i:s', mktime($hour,$minute,0,$month,$day,$year));
	$durationstamp = date('Y-m-d H:i:s', mktime($hour + $durationhour,$minute + $durationmin, 0, $_POST['cal_origmonth'], $_POST['cal_origday'], $_POST['cal_origyear']));
	// organize the data expected by the query function
	$data = array();
	$data['repeat_num'] = $rnum;
	$data['type_id'] = $type;
	$data['repeat_h'] = $repeat_h;
	$data['repeat_d'] = $repeat_d;
	$data['repeat_m'] = $repeat_m;
	$data['repeat_y'] = $repeat_y;
	$data['repeat_end'] = $oend;
	$data['alias'] = $alias;
	$data['stamp'] = $timestamp;
	$data['subject'] = $subject;
	$data['private'] = $private;
	$data['description'] = $description;
	$data['eventtype'] = $typeofevent;
	$data['duration'] = $durationstamp;
	// run the query to set the event data
	if($modify) $result = cal_query_setevent($data, $id); // if we specify the ID, it updates that ID using $data
	else $result = cal_query_setevent($data); // if we don't specify ID, it create a new event using $data
	// return an error if the SQL query failed
	if(!$result) return CAL_EVENT_UPDATE_FAILED;
	// returning NULL means it was a success (no error message)
	return NULL;
}


?>
