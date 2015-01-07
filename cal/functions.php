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


/* ##################################################################
  cal_error()
   Used to print nice error messages
###################################################################*/
function cal_error($s){
	return "<br><span class='failure'>$s</span><br>";
}


/* ##################################################################
  cal_submenu()
   returns the menu of links to other parts of the calendar
   such as the admin page or search page, as well as login/logout
   (these are the links at the bottom of the page)
###################################################################*/
function cal_submenu($year, $month, $day){
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	$today = "year=".date('Y')."&month=".date("n")."&day=".date("d");
	// start output
	$output.= "<div id='navbar'>";
	$action = $_SESSION['cal_action'];
	// start the buttons
	if(!cal_anon()) $output .= " <a href=\"".cal_getlink(CAL_URL_FILENAME."?action=logout")."\">" . CAL_SUBM_LOGOUT. "</a> | ";
	else $output .= " <a href=\"".cal_getlink(CAL_URL_FILENAME."?action=login")."\">" .CAL_SUBM_LOGIN. "</a> | ";
	if(cal_admin()) $output .= " <a href=\"".cal_getlink(CAL_URL_FILENAME."?action=admin")."\">" .CAL_SUBM_ADMINPAGE. "</a> | ";
	$output .= " <a href=\"".cal_getlink(CAL_URL_FILENAME."?action=search")."\">" .CAL_SUBM_SEARCH. "</a>";
	if($_SESSION['cal_action']!="" AND $_SESSION['cal_action']!="calendar" AND $_SESSION['cal_action']!="logout") $output .= " | <a href=\"".cal_getlink(CAL_URL_FILENAME."?action=calendar&month=$month&year=$year")."\">" .  CAL_SUBM_BACK_CALENDAR . '</a>';
	if($action!="viewdate") $output .= " | <a href=\"".cal_getlink(CAL_URL_FILENAME."?action=viewdate&".$today)."\">" . CAL_SUBM_VIEW_TODAY. "</a>";
	if($action=="viewdate" AND cal_permission("write")) $output .= " | <a href=\"".cal_getlink(CAL_URL_FILENAME."action=add&day=$day&month=$month&year=$year")."\">" .CAL_SUBM_ADD. "</a></div>";
	return $output;

}



/* ##################################################################
  cal_navmenu()
   returns the navigation buttons for the day/module
###################################################################*/
function cal_navmenu(){
	// get the date being requested.
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	$a = $_SESSION['cal_action'];
	// make calculations
	if($a=="calendar" OR $a=="" OR $a=="admin" OR $a=="search"){
		$title = cal_month_name(date("m"))." ".date("Y");
	}else{
		$mtime = mktime(0, 0, 0, $month, $day, $year);
		$title = cal_month_name(date("m",$mtime))." ".date("j, Y", $mtime);
	}
	$tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
	$monthname = cal_month_name($month);
	$lasttime = mktime(0, 0, 0, $month, $day - 1, $year);
	$pd = date('j', $lasttime);
	$pm = date('n', $lasttime);
	$py = date('Y', $lasttime);
	$nexttime = mktime(0, 0, 0, $month, $day + 1, $year);
	$nd = date('j', $nexttime);
	$nm = date('n', $nexttime);
	$ny = date('Y', $nexttime);
	// return menu output
	$o = "
	<table width='90%' cellpadding='3' cellspacing='0'><tr>
    <td>
	<span class='month_title'>$title</span>
	</td><td align='right' valign='bottom'>";
	$o .= "<input type=\"button\" value=\"".CAL_MENU_BACK_CALENDAR."\" class=\"formButtons\" onClick=\"location='".CAL_URL_FILENAME."?action=calendar&year=$year&month=$month&day=1';\"> ";
	if( ($a=="viewdate" OR $a=="delete" OR $a=="submitevent") AND cal_permission("write") AND (cal_permission("editpast") OR "$year-$month-$day" >= date("Y-m-d")) ) $o .= "<input type=\"button\" value=\"".CAL_MENU_NEWEVENT."\" class=\"formButtons\" onClick=\"location='".CAL_URL_FILENAME."?action=add&year=$year&month=$month&day=$day';\"> ";
	if($a=="add" OR $a=="modify" OR $a=="viewevent") $o .= "<input type=\"button\" value=\"".CAL_MENU_BACK_EVENTS."\" class=\"formButtons\" onClick=\"location='".CAL_URL_FILENAME."?action=viewdate&year=$year&month=$month&day=$day';\">";
	if($a!="viewevent" AND $a!="search" AND $a!="search_results" AND $a!="admin" AND $a!="login"){
		$o .= "
		<input type=\"button\" value=\"<<\" class=\"formButtons\" onClick=\"location='".CAL_URL_FILENAME."?year=$py&month=$pm&day=$pd';\"> 
		<input type=\"button\" value=\">>\" class=\"formButtons\" onClick=\"location='".CAL_URL_FILENAME."?year=$ny&month=$nm&day=$nd'\"> ";
	}
	$o .= "</td></tr></table>";
	return $o;
}




/* ##################################################################
  cal_getlangs()
   looks for language files in the "languages" folder and returns a list.
###################################################################*/
function cal_getlangs(){
	$list = array();
	// make sure the skins folders exists
	if(!file_exists(CAL_INCLUDE_PATH."languages")) return "";
	// open the folder
	$handle=opendir(CAL_INCLUDE_PATH."languages");
	if(empty($handle)) return "";
	// loop through the files in the folder, create list of skins
	while ($file = readdir($handle)) {
		if ( (eregi("\.php$",$file)) ) {
			// notice skin names are seperated by two colons (::)
			$list[] =  substr($file, 0, strlen($file)-4);
		}
	}
	closedir($handle);
	return $list;
}



/* ##################################################################
  cal_getskins()
   looks for skin css files in the "skins" folder and returns a list
###################################################################*/
function cal_getskins(){
	$list = array();
	// make sure the skins folders exists
	if(!file_exists(CAL_INCLUDE_PATH."skins")) return "";
	// open the folder
	$handle = opendir(CAL_INCLUDE_PATH."skins");
	if(empty($handle)) return "";
	// loop through the files in the folder, create list of skins
	while ($file = readdir($handle)) {
		if ( (eregi("\.css$",$file)) ) {
			// notice skin names are seperated by two colons (::)
			$list[] =  substr($file, 0, strlen($file)-4);
		}
	}
	closedir($handle);
	return $list;
}




/* ##################################################################
  cal_getlink()
   returns the link with added GET querys from $link_tail.
###################################################################*/
function cal_getlink($link){
	global $cal_link_tail;
	// if we use a different filename, replace index.php with the one we use
	if(CAL_URL_FILENAME!="index.php"){
		$link = str_replace("index.php",CAL_URL_FILENAME,$link);
	}
	if($cal_link_tail=="") return $link;
	else if(eregi("\?",$link)) return $link ."&amp;". $cal_link_tail;
	else return $link ."?". $cal_link_tail;	
}


/* ##################################################################
  cal_add_to_links()
   adds GET querys to every link used on the page.
   Made to help simplify adding querys for things like
   skins, usernames, passwords, and of course for modularity.
   useage:  add_to_links("skin=".$skin_name);
   
   Basically, you can use this whole program as a module in something like
   php-nuke or postnuke by adding variables they need to every link's query.
###################################################################*/
function cal_add_to_links($add){
	global $cal_link_tail;
	if($link_tail == "") $link_tail = $add;
	else $link_tail .= "&amp;".$add;
}





/* ##################################################################
  cal_month_name()
   Returns the full month name when given a number.
   (number is modded 12)
###################################################################*/
function cal_month_name($month){
	$month = ($month - 1) % 12 + 1;
	switch($month) {
		case 1:  return CAL_JANUARY;
		case 2:  return CAL_FEBRUARY;
		case 3:  return CAL_MARCH;
		case 4:  return CAL_APRIL;
		case 5:  return CAL_MAY;
		case 6:  return CAL_JUNE;
		case 7:  return CAL_JULY;
		case 8:  return CAL_AUGUST;
		case 9:  return CAL_SEPTEMBER;
		case 10: return CAL_OCTOBER;
		case 11: return CAL_NOVEMBER;
		case 12: return CAL_DECEMBER;
	}
}



/* ##################################################################
  cal_month_short()
   same as month_name() above except it returns an abreviation.
###################################################################*/
function cal_month_short($month){
	$month = ($month - 1) % 12 + 1;
	switch($month) {
		case 1:  return substr(CAL_JANUARY,0,3);
		case 2:  return substr(CAL_FEBRUARY,0,3);
		case 3:  return substr(CAL_MARCH,0,3);
		case 4:  return substr(CAL_APRIL,0,3);
		case 5:  return substr(CAL_MAY,0,3);
		case 6:  return substr(CAL_JUNE,0,3);
		case 7:  return substr(CAL_JULY,0,3);
		case 8:  return substr(CAL_AUGUST,0,3);
		case 9:  return substr(CAL_SEPTEMBER,0,3);
		case 10: return substr(CAL_OCTOBER,0,3);
		case 11: return substr(CAL_NOVEMBER,0,3);
		case 12: return substr(CAL_DECEMBER,0,3);
	}
}



/* ##################################################################
  cal_top()
   returns the header information,
   css stylesheet file links (acording to which skin is loaded) etc
###################################################################*/
function cal_top(){
	// set date stuff
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	$output = '';
	// get all css stuff.  I do this temporarily until I work in a better stylesheet system into my site.
	$skin = cal_option("skin");
	if($skin=="") $skin_link = "default";
	else $skin_link = "skins/".$skin;
	if(!file_exists(CAL_INCLUDE_PATH.$skin_link.".css")){
		$output .= "<center><span style='color: red; font-size: 12px;'>Error Loading CSS skin from: ".CAL_INCLUDE_PATH.$skin_link.".css<br>Check your CAL_CALENDAR_PATH string in the config file.</span></center><br>";
	}
	if(CAL_STAND_ALONE){
		
		$output .= "<html><head>";
		$output .= "<title>".CAL_STAND_ALONE_TITLE."</title>";
		$output .= '<link href="'.CAL_INCLUDE_PATH.$skin_link.'.css" rel="stylesheet" type="text/css">';
		$output .= '<script type="text/javascript" src="'.CAL_INCLUDE_PATH.'js/overlib.js"><!-- overLIB (c) Erik Bosrup --></script>';
		$output .= "</head>";
		$output .= "<body>";
	}else{
		// print the css from the file directly into the page
		$css = file_get_contents(CAL_INCLUDE_PATH.$skin_link.".css");
		$output .= "
			<style type='text/css'>
			$css
			</style>
			";
		// print javascript for overlib directly ito the page
		$js = file_get_contents(CAL_INCLUDE_PATH."js/overlib.js");
		$output .= "
			<script type='text/javascript'>
			$js
			</script>
			";
		unset($css);
		unset($js);
	}
	// print the beginning of the calendar module
	$output .= '
		<script type="text/javascript">
			function cal_toggle(id){
				obj = document.getElementById(id);
				if(obj.style.display=="none"){
					obj.style.display = "block";
				}else{
					obj.style.display = "none";
				}
			}
			function cal_hide(id){
				document.getElementById(id).style.display = "none";
			}
			function cal_show(id){
				document.getElementById(id).style.display = "block";
			}
		</script>
		<!-- overlib -->
		<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
		<!-- start main calendar tables -->
		<table id="cal" class="main-outer" width="100%" border="0" cellpadding="1" cellspacing="0"><tr><td>
		<table class="main-inner" width="100%" border="0" cellpadding="5" cellspacing="0">
		<tr>
		<td align="left" valign="top">
		<table border="0" cellspacing="0" cellpadding="4" width="100%">
		<tr valign="top" align="center"><td>';
	$output .= "</td></tr></table></td></tr><tr><td align='center' valign='top'>";
	return $output;	
}




/* ##################################################################
  cal_bottom()
   ends the html file.
###################################################################*/
function cal_bottom(){
	// get date stuff
	$day = $_SESSION['cal_day'];
	$month = $_SESSION['cal_month'];
	$year = $_SESSION['cal_year'];
	// start output
	$output = cal_submenu($year, $month, $day);
	$output .= '<br></td></tr></table></td></tr></table>';
	if(CAL_STAND_ALONE){
		$output .= "</body></html>";
	}
	return $output;
}


/* ##################################################################
  cal_easter_orthodox()
	Takes any Gregorian date and returns the Gregorian
	date of Orthodox Easter for that year.
###################################################################*/
function cal_easter_orthodox($year){
	$year = date("Y", mktime(0,0,1,1,1,$year));
	$r1 = $year % 19;
	$r2 = $year % 4;
	$r3 = $year % 7;
	$ra = 19 * $r1 + 16;
	$r4 = $ra % 30;
	$rb = 2 * $r2 + 4 * $r3 + 6 * $r4;
	$r5 = $rb % 7;
	$rc = $r4 + $r5;
	//Orthodox Easter for this year will fall $rc days after April 3
	return date("Y-m-d",strtotime("3 April $year + $rc days"));
}



?>