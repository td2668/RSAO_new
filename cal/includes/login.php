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
function cal_login_page(){
	// start the form.
	$output = cal_navmenu();
	$output .= '<form method="post" action="'.cal_getlink(CAL_URL_FILENAME).'">
		<table class="box" cellpadding="2"><tr><td colspan="2" align="center"><br>';
	// print out any errors that need to be displayed
	if($_SESSION['cal_loginfailed']){
		$output .= "<center><span class='failure'>".CAL_INVALID_LOGIN."</span></center><br>";
		// only display the error once
		$_SESSION['cal_loginfailed'] = 0;
	}
	// if not anonymous and account is disabled, print error.
	// I don't print for anonymous here since otherwise they would see that before having to try and login.
	if(cal_permission("disabled") AND !cal_anon()) $output .= "<center><span class='failure'>".CAL_ACCOUNT_DISABLED."</span></center><br>";
	// print out the title and the login screen.
	$output .= "<center><span class='box_title'>".CAL_LOGIN_TITLE."</span></center>";
	$output .= '</td>
		</tr>
		<tr><td>
  		<table align="center" width="400"><tr><td>
			<table width="400" border="0" align="center" cellpadding="1">
			  <tr> 
				<td valign="bottom" align="center"><center><span class="box_subtitle">'.CAL_USERNAME.'</span></center></td>
				<td valign="bottom" align="center"><center><span class="box_subtitle">'.CAL_PASSWORD.'</span></center></td>
			  </tr>
			  <tr> 
				<td align="center">
					<center><input style="text-align: center;" name="user" type="text" id="user" size="25" maxlength="20"></center>
				</td>
				<td align="center"> 
					<center><input style="text-align: center;"  name="pass" type="password" id="pass" size="25" maxlength="15"></center>
				</td>
			  </tr>
			</table>
			<br>
			<center>
			<input type="submit" value="' .  CAL_LOGIN . '" />
			<br><br>';
	$output.= '</center>
			</td></tr></table>';
	$output.= '</td></tr></table>
		<input type="hidden" name="new_login" value="1">
		</form>'; 
	return $output;
} // end function




?>
