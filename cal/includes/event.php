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
  cal_event_form()
   This function writes form for an event's submition into
   the database:  used by the add, delete, and modify php files.
###################################################################*/
function cal_event_form($action){
	global $cal_db;
	// get dates
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	$action = $_SESSION['cal_action'];
	// print out javascript functions used in this page.
	$output = '
		<script language="JavaScript" type="text/JavaScript">
		<!--
		function change(){
			cal_hide("cal_extra1");
			cal_hide("cal_extra2");
			cal_hide("cal_extra3");
			if(document.getElementById("daily").selected){
				document.getElementById("word").innerHTML = "Days";
				cal_show("cal_extra1");
				cal_show("cal_extra2");
			}
			else if(document.getElementById("weekly").selected){
				document.getElementById("word").innerHTML = "Weeks";
				cal_show("cal_extra1");
				cal_show("cal_extra2");
			}
			else if(document.getElementById("monthly").selected){
				document.getElementById("word").innerHTML = "Months";
				cal_show("cal_extra1");
				cal_show("cal_extra2");
			}
			else if(document.getElementById("yearly").selected){
				document.getElementById("word").innerHTML = "Years";
				cal_show("cal_extra1");
				cal_show("cal_extra2");
			}else if(document.getElementById("holiday").selected){
				cal_show("cal_extra3");
			}
		}
		//-->
		</script>';
	// print the navigation menu
	$output .= cal_navmenu();
	// Do this if we are MODIFYING an event.
	if($action == 'modify' AND is_numeric($_GET['id'])) {
		// get the event ID and make sure it is valid.
		$id = $_GET['id'];
		// get event info from database
		$result = cal_query_get_event($id);
		$row = $cal_db->sql_fetchrow($result);
		// security check - make sure they are allowed to edit other's events if this is not their event.
		if(!cal_permission("readothers") AND $row['user_id']!=$_SESSION['cal_userid']) return cal_error(DOESNT_EXIST);
		// If user cannot view events, make sure they arn't trying to edit one to view the info!
		if(!cal_permission("read") AND $row['user_id']==$_SESSION['cal_userid']) return cal_error(DOESNT_EXIST);
		// check private event data
		if($row['private'] AND cal_anon()) return cal_error(DOESNT_EXIST);
		// make sure user is allowed to add to the past if it's in the past
		if(!cal_permission("editpast") AND date("Y-m-d",$row['start_since_epoch']) < date("Y-m-d")) return cal_error(CAL_NO_EDITPAST_PERMISSION);
		// stop XSS here by using htmlentities() since these 3 fields are not restricted  when submitted.
		$subject =  htmlspecialchars($row['subject']);
		$desc = htmlspecialchars($row['description']);
		$name = htmlspecialchars($row['alias']);
		// get username, subject, and other data from the event
		$username = $row['username'];
		$private =  $row['private'];
		$curtype = $row['type_id'];
		$typeofevent = $row['eventtype'];
		if($typeofevent==3) $usetimeandduration = 0;
		else $usetimeandduration = 1;
		// organize repeating data for the drop down menu
		$occ = 1;
		if($row['repeat_d'] > 0){ $occ = 2; $rjump = $row['repeat_d'];}
		if($row['repeat_d'] > 0 AND $row['repeat_d']%7==0){ $occ = 3; $rjump = $row['repeat_d']/7;}
		if($row['repeat_m'] > 0){ $occ = 4; $rjump = $row['repeat_m'];}
		if($row['repeat_y'] > 0){ $occ = 5; $rjump = $row['repeat_y'];}
		if($row['repeat_h'] > 0){ $occ = 6;}
		if($row['repeat_h']==2){ $setlastweek = " checked";}
		if($row['repeat_end'] > "0000-00-00") $rend = $row['repeat_end'];
		if($row['repeat_num'] > 0) $rnum = $row['repeat_num'];
		if(!is_numeric($rjump)) $rjump = 1;
		// decide which repeat type it is
		if($rend!="" AND $rend<"9999-00-00") $rsel3 = " checked";
		elseif($rnum>0) $rsel2 = " checked";
		else $rsel1 = " checked";
		if($rend=="9999-00-00") $rend = "";
		// organize the time and date data for the html select drop downs.
		$thetime = $row['start_since_epoch'];
		$hour = date('G', $thetime);
		$minute = date('i', $thetime);
		$month = date('n', $thetime);
		$year = date('Y', $thetime);
		$day = date('j', $thetime);
		$durtime = $row['end_since_epoch'] - $thetime;
		$durmin = ($durtime / 60) % 60;     //seconds per minute
		$durhr  = ($durtime / 3600) % 24;   //seconds per hour
		$durday = floor($durtime / 86400);  //seconds per day
		// format time to 24-hour or 12-hour clock.
		if(!cal_option("hours_24")){
			if($hour >= 12){
				$pm = 1;
				$hour = $hour - 12;
			}else $pm = 0;
		}
		// output header telling the date the event is on.
		$datemessage = "<br><center><span class='box_subtitle'>Modifying event on ".cal_month_name($month)." $day, $year</span></center>";
	} 
	// BEGIN WRITING THE FORMS!
	else {
		// check if able to write new events
		if(!cal_permission("write")) return cal_error(NO_WRITE_PERMISSION);
		// output header telling the date the event is on.
		$datemessage = "<br><center><span class='box_subtitle'>".CAL_ADDING_TO." ".cal_month_name($month).' '.$day.', '.$year."</span></center>";
		// set important data to nothing.
		$username = '';
		$subject = '';
		$desc = '';
		// if adding event to today, make the time current time.  Else just make it 6PM (you can change that)
		if( "$year-$month-$day" == date("Y-m-d") ) $hour = date('G') + 1;
		else $hour = 18;
		// organize time by 24-hour or 12-hour clock.
		if(!cal_option("hours_24")) {
			if($hour >= 12) {
				$hour = $hour - 12;
				$pm = 1;
			} else $pm = 0;
		}
		// set default minute and duration times.
		$minute = 0;
		$durhr = 1;
		$durday = 0;
		$durmin = 0;
		// set other defaults
		$rjump = 1;
		// set type of event to default of 1 (nothing)
		$typeofevent = 1;
		// set default var to track if they used the advanced options or not
		$usetimeandduration = 0;
	}
	// print out the beginning of the form and main table.
	$output .= '
		<form method="post" action="'.cal_getlink(CAL_URL_FILENAME."?action=submitevent").'">
		<input type="hidden" name="action" value="submitevent">
		<input type="hidden" id="cal_usetimeandduration" name="usetimeandduration" value="'.$usetimeandduration.'">
		<table class="box" cellpadding="2" cellspacing="0" width="90%" align="center">';
	$output .= '<tr><td align="center">';
	$output .= $datemessage;
	// begin writing the form elements.
	$output .= '<br></td></tr>';	
	$output .= "<tr><td>";
	// BEGIN THE TABS
	$output .= "
				<script type='text/javascript'>
					function cal_showtab(id){
						// set option so the event will use the time and duration data
						document.getElementById('cal_usetimeandduration').value = 1;
						// display the correct tabs etc.
						document.getElementById('cal_time').style.display = 'none';
						document.getElementById('cal_repeat').style.display = 'none';
						document.getElementById('cal_extra').style.display = 'none';
						document.getElementById(id).style.display = 'block';
						document.getElementById('cal_time_tab').className = 'cal_tab';
						document.getElementById('cal_repeat_tab').className = 'cal_tab';
						document.getElementById('cal_extra_tab').className = 'cal_tab';
						document.getElementById(id+'_tab').className = 'cal_tab2';
					}
				</script>
				";
	$output .= "<table cellpadding='0' cellspacing='0' width='100%'><tr><td valign='top'>";
	$output .= "<center><br>";
	$output .= "<span class='cal_tab' id='cal_time_tab' onClick=\"cal_showtab('cal_time');\">".CAL_TIME_AND_DURATION."</span> ";
	$output .= "<span class='cal_tab' id='cal_repeat_tab' onClick=\"cal_showtab('cal_repeat');\">".CAL_REPEATING_EVENT."</span> ";
	$output .= "<span class='cal_tab' id='cal_extra_tab' onClick=\"cal_showtab('cal_extra');\">".CAL_EXTRA_OPTIONS."</span> ";
	// BEGIN PRINTING THE TIME AND DATE OPTIONS
	$output .= "<div class='cal_tabbody' id='cal_time' style='display: none;'>";
		$output .= "<table>";
		// begin printing the time options
		$output .= '<tr><td align="right">';
		$output .= CAL_TIME . "</td><td align='left'>";
		// print out the hour drop down
		$output .= "<select name=\"hour\" size=\"1\">\n";
		if(!cal_option("hours_24")) {
			for($i = 1; $i <= 12; $i++) {
				$output .= '<option value="' . $i % 12 . '"';
				if($hour == $i) $output .= ' selected="selected"';
				$output .= ">$i</option>\n";
			}
		}else{
			for($i = 0; $i < 24; $i++) {
				$output .= "<option value=\"$i\"";
				if($hour == $i) $output .= ' selected="selected"';
				$output .= '>' . $i . "</option>\n";
			}
		}
		$output .= "</select>";
		// print out the minute drop down
		$output .= " <b>:</b> <select name=\"minute\" size=\"1\">\n";
		for($i = 0; $i < 60; $i = $i + 15) {
			$output .= "<option value='$i'";
			if($minute >= $i && $i > $minute - 15) $output .= ' selected="selected"';
			$output .= sprintf(">%02d</option>\n", $i);
		}
		$output .= "</select>";
		// print out the PM/AM option (only if using 12-hour clock)
		if(!cal_option("hours_24")) {
			$output .= '<select name="pm" size="1"><option value="0"';
			if(empty($pm)) $output .= ' selected="selected"';
			$output .= '>AM</option><option value="1"';
			if($pm) $output .= ' selected="selected"';
			$output .= ">PM</option></select>\n";
		}
		$output .= '</td></tr>';
		// begin printing the duration options
		$output .= '<tr><td align="right">'.CAL_DURATION.'</td><td align="left">' . "\n";
		// print the duration hour drop down
		$output .= '<select name="durationhour" size="1">';
		for($i = 0; $i < 15; $i++) {
			$output .= "<option value='$i'";
			if($durhr == $i) $output .= ' selected="selected"';
			$output .= ">$i</option>\n";
		}
		$output .= '</select> '.CAL_HOURS;
		// print out the duration minutes drop down
		$output .= " <select name=\"durationmin\" size=\"1\">";
		for($i = 0; $i <= 59; $i = $i + 15) {
			$output .= "<option value='$i'";
			if($durmin >= $i && $i > $durmin - 15) $output .= ' selected="selected"';
			$output .= sprintf(">%02d</option>\n", $i);
		}
		$output .= '</select> '.CAL_MINUTES.'</td></tr>';
		// print extra time options
		$output .= "<tr>".'
			<td align="right">'.CAL_MORE_TIME_OPTIONS.'</td>
			<td align="left">
			<select name="typeofevent" size="1">
			<option value="1"';
		if($typeofevent == 1) $output .= ' selected="selected"';
		$output .= '></option>
			<option value="2"';
		if($typeofevent == 2) $output .= ' selected="selected"';
		$output .= '>'.CAL_FULL_DAY.'</option>
			<option value="3"';
		if($typeofevent == 3) $output .= ' selected="selected"';
		$output .= '>'.CAL_UNKNOWN_TIME.'</option>
			</select>';
			
		$output .= "</table>";
	$output .= "</div>";
	// BEGIN PRINTING THE REPEATING EVENT OPTIONS
	$output .= "<div class='cal_tabbody' id='cal_repeat' style='display: none; padding-top: 10px;'>";
		// print out the repeating options drop down menu
		$output .= '<table border="0" cellpadding="0" cellspacing="0"><tr><td align="left" valign="top">
			'.CAL_REPEAT.' <select name="occurance" onChange="change()">
			<option value="1" id="today"';
		if($occ == 1) $output .= ' selected="selected"';
		$output .= '>' . CAL_ONLY_TODAY . '</option>
			<option value="2" id="daily"';
		if($occ == 2) $output .= ' selected="selected"';
		$output .= '>' . CAL_DAILY_EVENT . '</option>
			<option value="3" id="weekly"';
		if($occ == 3) $output .= ' selected="selected"';
		$output .= '>' . CAL_WEEKLY_EVENT . '</option>
			<option value="4" id="monthly"';
		if($occ == 4) $output .= ' selected="selected"';
		$output .= '>' .  CAL_MONTHLY_EVENT . '</option>
			<option value="5" id="yearly"';
		if($occ == 5) $output .= ' selected="selected"';
		$output .= '>' .  CAL_YEARLY_EVENT . '</option>
			<option value="6" id="holiday"';
		if($occ == 6) $output .= ' selected="selected"';
		$output .= '>' .  CAL_HOLIDAY_EVENT . '</option>';
		echo '</select>';
		// calculate what is visible given the repeating options
		if($occ == 1 OR $occ=="6" OR $occ=="") $hide = "display: none;";
		if($occ != 6) $hide2 = "display: none;";
		// print out repeating options for daily/weekly/monthly/yearly repeating.
		$output .='</td><td>';
		$output .= '<div id="cal_extra1" style="'.$hide.'">';
		$output .= '&nbsp;'.CAL_EVERY.' <input type="text" size="2" maxlength="3" name="occurance_jump" value="'.$rjump.'"> <span id="word">Days/Weeks/Months/Years</span>';
		$output .= "</div></td></tr></table>";
		$output .= "<br>";
		$output .= '<div id="cal_extra2" style="width: 400px; align: center; text-align: left; '.$hide.'">';
		$output .= "<input type='radio' name='repeat_option' value='1' $rsel1>".CAL_REPEAT_FOREVER;
		$output .= "<br><br>";
		$output .= "<input type='radio' name='repeat_option' value='2' $rsel2>".CAL_REPEAT." <input type='text' name='repeat_num' size='3' maxlength='3' value='$rnum'> ".CAL_TIMES;
		$output .= "<br><br>";
		$output .= "<input type='radio' name='repeat_option' value='3' $rsel3>".CAL_REPEAT_UNTIL." <input type='text' name='repeat_end' size='12' maxlength='10' value='$rend'> (YYYY-MM-DD)";
		$output .= "<br><br>";
		$output .= '</div>';
		$output .= '<div id="cal_extra3" style="width: 300px; align: center; text-align: left; '.$hide2.'">';
		// get the week number
		$tmp = 1;
		$week = 0;
		while($week < 5 AND $tmp <= $day){
			$week++;
			$tmp += 7;
		}
		// get days in month and day name
		$daysinmonth = date("t",mktime(0,0,1,$month,$day,$year));
		$dayname = date("l",mktime(0,0,1,$month,$day,$year));
		// use week number, and days in month to calculate if it's on the last week.
		if($day > $daysinmonth - 7) $lastweek = true;
		else $lastweek = false;
		// calculate the correct number endings
		if($week==1) $weekname = "1st";
		elseif($week==2) $weekname = "2nd";
		elseif($week==3) $weekname = "3rd";
		else $weekname = $week."th";
		// print out the data for holiday repeating
		$output .= "<span style='font-family: arial; color: #444444; font-size: 14px;'>";
		$output .= CAL_HOLIDAY_EXPLAIN." $weekname $dayname ".CAL_DURING." ".cal_month_name($month)." ".CAL_EVERY_YEAR.".<br><br>";
		// if it's the last week, add option to have event repeat on LAST week every month (holiday repeating only)
		if($lastweek){
			$output .= "<input type='checkbox' name='cal_holiday_lastweek' value='1' $setlastweek> ".CAL_HOLIDAY_EXTRAOPTION." $dayname ".CAL_IN." ".cal_month_name($month)." ".CAL_EVERY_YEAR.".<br><br>";
		}
		$output .= "<span>";
		$output .= '</div>';
	$output .= "</div>";
	// BEGIN PRINTING THE EXTRA OPTIONS SUCH AS EVENT TYPE, ETC.
	$output .= "<div class='cal_tabbody' id='cal_extra' style='display: none;'>";
		$output .= "<table>";
		// anonymous alias options
		if(cal_anon() AND cal_option("anon_naming")){
			$namefield = "<input type=\"text\" name=\"alias\" value=\"$name\">";
			$output .= '<tr><td align="right">';
			$output .= CAL_ANON_ALIAS;
			$output .= "</td><td align='left'>$namefield</td></tr>";
		}
		// private event options
		if($private) $private = " checked";
		else $private = "";
		if(!cal_anon()) $output .= "<tr><td align='right'>".CAL_PRIVATE_EVENT."</td><td align='left'>
			<input type='checkbox' name='private'$private value='1'> (".CAL_PRIVATE_EVENT_EXPLAIN.")</td></tr>";
		// event type options
		$types = cal_query_get_eventtypes();
		// if some are returned, loop through them
		if($types AND $cal_db->sql_numrows($types)>0){
			$output .= '<tr><td>'.CAL_EVENT_TYPE.'</td><td><select name="eventtype"><option></option>';
			while($type = $cal_db->sql_fetchrow()){
				if($type['id']==$curtype) $output .= "<option selected value='".$type['id']."' style='background-color: #".$type['typecolor'].";'>".$type['typename']."</option>";
				else $output .= "<option value='".$type['id']."' style='background-color: #".$type['typecolor'].";'>".$type['typename']."</option>";
			}
			$output .= "</select></td></tr>";
		}
		// end table
		$output .= "</table>";
	$output .= "</div>";
	// BEGIN PRINTING THE MASTER OPTIONS.
	$output .= "<br>";
	$output .= "<div class='cal_tabbody' id='cal_mainoptions'>";
		$output .= "<table>";
		$output .= '<tr><td align="right">' .  CAL_SUBJECT .  " </td>
			<td align='left'>
				<input type=\"text\" name=\"subject\" value=\"$subject\" size=\"50\" maxlength=\"100\">
			</td></tr>
			<tr><td align='right'>" .  CAL_DESCRIPTION . "</td>
			<td align='left'>
				<textarea rows=\"5\" cols=\"40\" name=\"description\">$desc</textarea>
			</td></tr>";
		$output .= "</table>";
	$output .= "</div>";
	// END THE OPTIONS.
	$output .= "</center></td></tr></table>";
	// THIS IS HERE SO THAT THE DURATION CAN BE SET CORRECTLY ACCORDING TO THE EVENT'S ACTUAL START DATE.
	// otherwise, if you modify a repeating event, it can save the duration as a totally different date!
	$output .= "
		<input type=\"hidden\" name=\"cal_origday\" value=\"$day\">
		<input type=\"hidden\" name=\"cal_origmonth\" value=\"$month\">
		<input type=\"hidden\" name=\"cal_origyear\" value=\"$year\">
		";
	// if modirying, add a parameter to specify that, and the ID of the event we are modifying
	if($action == 'modify') {
		$output .= "<input type=\"hidden\" name=\"modify\" value=\"1\">";
		$output .= "<input type=\"hidden\" name=\"id\" value=\"$id\">";
	}
	// print submit button and end the form
	$output .= '<br><center><input type="submit" value="' .  CAL_SUBMIT_ITEM . '"></center><br>';
	$output .= "</td></tr></table>";
	$output .= '</form>';
	return $output;
} // end event_form() function


?>
