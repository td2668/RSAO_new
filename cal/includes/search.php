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

function cal_search_results(){
	global $cal_db;
	// get dates
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	// start output
	$output = "";
	// get search month info
	if(!is_numeric($_POST['frommonth'])) $frommonth = $month;
	else $frommonth = $_POST['frommonth'];
	if(!is_numeric($_POST['tomonth'])) $tomonth = $month;
	else $tomonth = $_POST['tomonth'];
	// get year info
	if(!is_numeric($_POST['fromyear'])) $fromyear = $year;
	else $fromyear = $_POST['fromyear'];
	if(!is_numeric($_POST['toyear'])) $toyear = $year;
	else $toyear = $_POST['toyear'];
	// get sort type
	if($_POST['sort']=="subject") $sort = "subject";
	elseif($_POST['sort']=="score") $sort = "score";
	else $sort = "stamp";
	// get order
	if($_POST['order']=="DESC") $order = "DESC";
	else $order = "";
	// get to/from date - we send it through mktime() and date() to ensure it's a valid datestamp and stop sql injection
	$fromdate = date("Y-m-d H:i:s", mktime(0,0,0,$frommonth, 1, $fromyear));
	$todate = date("Y-m-d H:i:s", mktime(23,59,59,$tomonth, 31, $toyear));
	// get search string 
	$ss = $_POST['searchstring'];
	// build params for search query
	$params = array();
	$params['sort'] = $sort;
	$params['order'] = $order;
	$params['from'] = $fromdate;
	$params['to'] = $todate;
	$params['string'] = $ss;
	// run the search query
	$result = cal_query_search($params);
	// output first part of table
	$output .= "
		<table width='100%' cellpadding='0' cellspacing='0' style='table-layout:fixed; overflow: hidden;'>
		<tr style='table-layout:fixed; overflow: hidden;'>
			<td width='15' style='border-bottom: 1px solid #999;'></td>
			<td width='150' style='border-bottom: 1px solid #999;'><span class='box_subtitle'>".CAL_SUBJECT."</span></td>
			<td width='180' style='border-bottom: 1px solid #999;'><span class='box_subtitle'>".CAL_DATE."</span></td>
			<td width='*' style='border-bottom: 1px solid #999;'><span class='box_subtitle'>".CAL_DESCRIPTION."</span></td>
		</tr>";
	// check for sql errors or if no rows are returned.
	if($cal_db->sql_error()!=""){
		$output .= "<tr><td colspan='4'><center><br>";
		$output .= "<span class='failure'>".CAL_SEARCH_ERROR."</span><br><br>";
		$output .= "</center></td></tr>";
	}elseif($result==NULL OR $cal_db->sql_numrows($result)<1) {
		$output .= "<tr><td colspan='4'><center><br><span class='box_subtitle'>".CAL_NO_EVENTS_FOUND."</span><br><br>";
		$output .= "</center></td></tr>";
	}
	if($result) $num = $cal_db->sql_numrows($result);
	else $num = 0;
	// loop through events and display them.
	while ($row = $cal_db->sql_fetchrow($result)) {
		$name = $row['username'];
		$subject = $row['subject'];
		$desc = $row['descshort'];
		$private = $row['private'];
		$thetime = $row['start_since_epoch'];
		$desclen = $row['len'];
		$id = $row['id'];
		$color = $row['typecolor'];
		if($color=="") $color = "AAEE00";
		if(!$private || !cal_anon()){
			$tmonth = date('n', $thetime);
			$tyear = date('Y', $thetime);
			$tday = date('j', $thetime);
			if(!cal_option("hours_24")) $timeformat = 'g:i A';
			else $timeformat = 'G:i';
			$time = date($timeformat, $thetime);
			if($subject=="") $subject = "[".CAL_NO_SUBJECT."]";
			if($desclen > 100) $desc .= " ... ";
			$output .= "
				<tr style='table-layout:fixed; overflow: hidden;'>
					<td style='padding-top: 1px;'><table cellpadding='0' cellspacing='0' width='10' height='12'><tr><td style='border: 1px solid #000; background-color: #".$color.";'>&nbsp;</td></tr></table></td>
					<td><a href=\"".cal_getlink(CAL_URL_FILENAME."?action=viewevent&amp;id=$id")."\">$subject</a></td>
					<td>$time - ".cal_month_name($tmonth)." $tday, $tyear</td>
					<td nowrap style='table-layout:fixed; overflow: hidden;'>$desc</td>
				</tr>\n";
		}
	} // while loop
	if($num==200){
		$output .= "<tr>
			<td width='20'></td>
			<td colspan='4' style='text-align: left;'><span class='box_subtitle'>".CAL_SEARCH_LIMIT_MESSAGE."</span></td>
			</tr>\n";
	}
	$output .= "</table>";
	// give informational message
	$output .= "<br><br><span style='color: #666;'>".CAL_SEARCH_NOTE."</span>";
	return $output;
}





function cal_search_form(){
	// get dates
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	// do calculations
	$frommonth_options = '';
	for ($i = 1; $i <= 12; $i++) {
		$nm = cal_month_name($i);
		if($i<10) $j = "0".$i;
		else $j = $i;
		if($_POST['frommonth']==$i OR ($_POST['frommonth']=="" AND $i == $month)) $frommonth_options .= "<option value=\"$j\" selected=\"selected\">$nm</option>\n";
		else $frommonth_options .= "<option value=\"$j\">$nm</option>\n";
	}
	$tomonth_options = '';
	for ($i = 1; $i <= 12; $i++) {
		$nm = cal_month_name($i);
		if($i<10) $j = "0".$i;
		else $j = $i;
		if($_POST['tomonth']==$i OR ($_POST['tomonth']=="" AND $i == $month)) $tomonth_options .= "<option value=\"$j\" selected=\"selected\">$nm</option>\n";
		else $tomonth_options .= "<option value=\"$j\">$nm</option>\n";
	}
	$toyear_options = '';
	for ($i=$year-2; $i<$year+5; $i++) {
		if ($_POST['toyear']==$i OR ($_POST['toyear']=="" AND $i==$year)) $toyear_options .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		else $toyear_options .= "<option value=\"$i\">$i</option>\n";
	}
	$fromyear_options = '';
	for ($i=$year-2; $i<$year+5; $i++) {
		if ($_POST['fromyear']==$i OR ($_POST['fromyear']=="" AND $i==$year)) $fromyear_options .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		else $fromyear_options .= "<option value=\"$i\">$i</option>\n";
	}
	$output = cal_navmenu();
	// set up ordering options
	$order_options = "<option value='DESC'>".CAL_DESCENDING."</option>";
	if($_POST['order']=="ASC") $order_options .= "<option selected value='ASC'>".CAL_ASCENDING."</option>";
	else $order_options .= "<option value='ASC'>".CAL_ASCENDING."</option>";
	// set up sort options
	$sort_options = "<option value='score'>".CAL_BEST_MATCH."</option>";
	if($_POST['sort']=="startdate") $sort_options .= "<option selected value='startdate'>".CAL_START_DATE."</option>";
	else $sort_options .= "<option value='startdate'>".CAL_START_DATE."</option>";
	if($_POST['sort']=="subject") $sort_options .= "<option selected value='subject'>".CAL_SUBJECT."</option>";
	else $sort_options .= "<option value='subject'>".CAL_SUBJECT."</option>";
	// output the form html
	$output .= "<form action=\"".cal_getlink(CAL_URL_FILENAME."?action=search")."\" method=\"post\">
		<table class=\"box\">
		<tr><td colspan='6' align='center'><br><center><span class='box_title'>".CAL_SEARCH_TITLE."</span></center><br></td></tr><tr>
		<td width='20%' align='right'>".CAL_PHRASE.":</td>
		<td width='80%' align='left' colspan='4'>
		<input type=\"text\" name=\"searchstring\" size='70' value='".htmlspecialchars($_POST['searchstring'])."'/>
		<input type='submit' value='".CAL_SUBMIT."'>
		<input type=\"hidden\" name=\"action\" value=\"".CAL_SEARCH."\" />
		<input type='hidden' name='cal_searchsubmit' value='1' />
		</td>
		</tr>
		<tr><td style='text-align: right;'>".CAL_SEARCH_FROM.": </td>
		<td style='text-align: left;' width='150'>
			<select size=\"1\" name=\"frommonth\">".$frommonth_options."</select>
			<select size=\"1\" name=\"fromyear\">".$fromyear_options."</select>
		</td><td style='text-align: right;' width='50'>".CAL_SEARCH_TO.": </td><td width='150'>
			<select size=\"1\" name=\"tomonth\">".$tomonth_options."</select>
			<select size=\"1\" name=\"toyear\">".$toyear_options."</select>
		</td></tr>
		<tr><td style='text-align: right;'>
			".CAL_SEARCH_SORT_BY.": 
		</td><td style='text-align: left;' width='150'>
			<select name=\"sort\">".$sort_options."</select>
		</td><td style='text-align: right;' width='50'>".CAL_SEARCH_ORDER.":</td><td width='150'>
			<select name=\"order\">".$order_options."</select>
		</td><td width='*'>&nbsp;</td></tr>
		<tr><td colspan='6' style='padding: 10px;'><br>";
	if($_POST['cal_searchsubmit']){
		$output .= cal_search_results();
	}
	$output .= "<br><br></td></tr></table></form>";
	return $output;
}
?>
