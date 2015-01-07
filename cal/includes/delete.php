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
  cal_del()
   figures out which thingys to delete, calls remove_event(),
   and prints out back buttons and such.
###################################################################*/
function cal_del(){
	global $cal_db;
	$id = $_GET[id];
	if(!is_numeric($id)) return;
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	$action = $_SESSION['cal_action'];
	if($id=="") return;
	// Make sure you have the permssions to write to the calendar.
	if(!cal_permission("write")) return CAL_NO_WRITE_PERMISSION;
	if(cal_anon() AND !cal_permission("editothers") AND $modify) return CAL_NO_EDITOTHERS_PERMISSION;
	// get event data to do permissions checking.
	$tres = cal_query_get_event($id);
	$d = $cal_db->sql_fetchrow($tres);
	// if event does not exist, do nothing since it doesn't matter, but DON'T return an error.
	if($cal_db->sql_numrows($result)==0) return;
	// if a private event and they are anonymous return error.
	if($d['private']>0 AND cal_anon()) return CAL_HACKING_ATTEMPT;
	// make sure they can edit other's events if it's not theirs
	if( $d['user_id']!=$_SESSION['user_id'] AND !cal_permission("editothers") ) return CAL_NO_EDITOTHERS_PERMISSION;
	// make sure user is allowed to add to the past if it's in the past
	if(!cal_permission("editpast") AND date("Y-m-d",$d['start_since_epoch']) < date("Y-m-d")) return cal_error(CAL_NO_EDITPAST_PERMISSION);
	// try and remove the event
	if(cal_query_remove_event($id)) return;
	else return CAL_DELETE_EVENT_FAILED;
}



?>
