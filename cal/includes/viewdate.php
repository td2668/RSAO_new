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
  cal_display()
   writes the events for a particular day out, with the delete/modify/add event options.
###################################################################*/
function cal_display(){
	global $cal_db;
	// get the date being requested.
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	// output the navigation menu
	$output = cal_navmenu();
	// output the column names of the event list for this day
	$output .= 
		'<table align="center" class="box"><tr><td><table border="0" cellpadding="0" width="100%" cellspacing="0">	
		<tr align="left">
		<td class="head" width="3%">&nbsp;</td>
		<td class="head" width="20%" style="border-bottom: 1px solid #888888;">&nbsp;</td>
		<td class="head" width="15%" style="border-bottom: 1px solid #888888;"><span class="box_subtitle">' . CAL_OPTIONS . '</span></td>
		<td class="head" width="12%" style="border-bottom: 1px solid #888888;"><span class="box_subtitle">' .  CAL_TIME . '</span></td>
		<td class="head" width="15%" style="border-bottom: 1px solid #888888;"><span class="box_subtitle">' .  CAL_DURATION . '</span></td>
		<tr><td colspan="5" height="10"></td></tr>';
	// get the events for the date from the database
	$result = cal_query_get_eventlist($day, $month, $year);
    // print this if there are no events
  	if( $cal_db->sql_numrows($result) < 1 OR $result==NULL){
    	$output .= '  <tr>
			<td colspan="6"><br><center><span class="box_subtitle">'.CAL_NO_EVENTS_FOUND."</span></center><br></td>
  			</tr>\n";
  	}
	// begin looping through all the events if any
	while ($row = $cal_db->sql_fetchrow($result)) {
		// organize username, subject, and description
		$name = htmlspecialchars($row['username']);
		$subject = htmlspecialchars($row['subject']);
		$desc = htmlspecialchars($row['description']);
        $desc="<br>".nl2br($desc);
		// check username to see if it's anonymous
		if($name=="") $name = CAL_ANONYMOUS;
		// organize the event type color
		if($row['typecolor']!="") $tcolor = " background-color: #".$row['typecolor'].";";
		else $tcolor = "";
		// organize other event options
		$private = $row['private'];
		$alias = $row['alias'];
		// organize event type
		$temp_time = $row['start_since_epoch'];
		if(!cal_option("hours_24")) $timeformat = 'g:i A';
		else $timeformat = 'G:i';
		$time = date($timeformat, $temp_time);
		// organize event duration
		$durtime = $row['end_since_epoch'] - $temp_time;
		$durmin = ($durtime / 60) % 60;     //minute per 60 seconds, 60 per hour
		$durhr  = ($durtime / 3600) % 24;   //hour per 3600 seconds, 24 per day
		// organize event extra time options
		$typeofevent = $row['eventtype'];
		if($typeofevent == 2) $temp_dur = CAL_FULL_DAY;
		elseif($typeofevent=="3"){
			$time = CAL_NOT_SPECIFIED;
			$temp_dur = CAL_NOT_SPECIFIED;
		}
		elseif($typeofevent==4) $temp_dur = CAL_NOT_SPECIFIED;
		else $temp_dur = "$durhr hours, $durmin min";
		// make sure the user is allowed to modify this event
		$edit=0;
		if($year < date('Y') AND !cal_permission("editpast"));
		else if($month < date('n') AND $year == date('Y') AND !cal_permission("editpast"));
		else if(($day < date('j')) AND ($month == date('n')) AND ($year == date('Y')) AND !cal_permission("editpast"));
		else if(($row['user_id'] != $_SESSION['cal_userid']) AND !cal_permission("editothers"));
		else if(($row['user_id'] == $_SESSION['cal_userid']) AND !cal_permission('edit'));
		else if(!cal_permission("editpast") AND date("Y-m-d",$row['start_since_epoch']) < date("Y-m-d"));
		else $edit=1;
		// print modify/delete links if allowed to modify the event
		if($edit){
			$modify_link = "<a class='viewdateoption' href=\"".cal_getlink(CAL_URL_FILENAME."?action=modify&amp;id=$row[id]&amp;day=$day&amp;month=$month&amp;year=$year")."\">".CAL_MODIFY."</a>";
			$del_link = "<a class='viewdateoption' href='#' onClick=\"if(confirm('Are you sure you wish to delete this event?')){ document.location.href = '".cal_getlink(CAL_URL_FILENAME."?action=delete&amp;id=$row[id]&amp;month=$month&amp;day=$day&amp;year=$year")."';}\">".CAL_DELETE."</a>";
			$del_link .= " <span style='font-size: 14px;'>-</span> ";
		}else{
			$modify_link = "";
			$del_link = "";
		}
		// print anonymous alias if enabled to do so.
		if(cal_option("anon_naming") AND $alias!="") $name= "<i>$alias</i>";
		// print event data that was organized above (for the current event in this loop anyways)
		if($subject=="") $subject = "[".CAL_NO_SUBJECT."]";
			if(!$private || !cal_anon()){
				$output .= "
					<tr align='left' valign='top'>
						<td rowspan='3' style='padding-right: 5px; padding-top: 3px;'><table class='event_block' style='$tcolor' align='right'><tr><td></td></tr></table></td>
						<td colspan='4'><a class=\"viewdatesubject\" href='".cal_getlink(CAL_URL_FILENAME."?action=viewevent&amp;id=$row[id]")."'>$subject</a>
						</td>
					</tr>
					<tr align='left' valign='top'>
						<td>by $name</td>
						<td>$del_link $modify_link</td>
						<td>$time</td>
						<td>$temp_dur</td>
					</tr>
					<tr valign='top' align='left'>
						<td colspan='4' style='padding-right: 10px;'><span class='viewdatetext'>$desc</span></td>
					</tr>
					<tr><td colspan='5'>&nbsp;</td></tr>";
		}
	} // end while loop
  	return $output . "</table><br><br></td></tr></table>";
} // end function
?>
