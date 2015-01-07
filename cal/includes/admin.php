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

// this is the message that is displayed when something is saved
$cal_message = "";
// this is the error displayed if there is an error.
$cal_error = "";




// THIS IS THE MAIN SWITCH THAT CALLS THE DIFFERENT FUNCTIONS IN THE ADMIN SECTION.
// IT ALSO TAKES ERRORS FROM THE FUNCTIONS, AND DEFINES SUCCESS MESSAGES.
// THOSE MESSAGES ARE THEN DISPLAYED IN cal_options_menu()
function cal_adminsection(){
	// get the option for this module
	$op = $_POST['op'];
	// set result strings to blank
	$r = "";
	$s = "";
	// make sure the user is an admin
	if(cal_admin()){
		switch($op){
			case "calendar":
				$r = cal_set_options();
				if($r=="") $s = CAL_ADMIN_SETTINGS_SUCCESS;
				break;
			case "changepass":
				$r = cal_change_pass();
				if($r=="") $s = CAL_ADMIN_PASSWORD_SUCCESS;
				break;
			case "update_user":
				$r = cal_update_user();
				if($r=="") $s = CAL_ADMIN_USER_UPDATE_SUCCESS;
				break;
			case "add":
				$r = cal_add_user();
				if($r=="") $s = CAL_ADMIN_USER_ADD_SUCCESS;
				break;
			case "deleteuser":
				$r = cal_delete_user();
				if($r=="") $s = CAL_ADMIN_USER_DEL_SUCCESS;
				break;
			case "eventtypeupdate":
				$r = cal_update_eventtype();
				if($r=="") $s = CAL_ADMIN_TYPE_UPDATE_SUCCESS;
				break;
			case "eventtypeupdateload":
				break;
			case "eventtypedelete":
				$r = cal_delete_eventtype();
				if($r=="") $s = CAL_ADMIN_TYPE_DEL_SUCCESS;
				break;
			case "eventtypeadd":
				$r = cal_add_eventtype();
				if($r=="") $s = CAL_ADMIN_TYPE_ADD_SUCCESS;
				break;
			case "setrootpass":
				$r = cal_reset_rootpass();
				if($r=="") $s = CAL_ADMIN_ROOT_RESET_SUCCESS;
				break;
		}
		return cal_options_menu($r, $s);
	}
	// return error message if not an admin
	else{
		return "<center><br><br><span class='failure'>".CAL_HACKING_ATTEMPT."</span><br></center>";
	}
}



//   #####################################################################
//        BELOW HERE ARE THE FUNCTIONS THAT CHECK DATA FOR CORRECTNESS
//   #####################################################################


// this makes sure the string is characters only.
// used to make sure the usernames are valid.
function cal_is_alphanumeric($s){
	// acceptable characters.
	$a = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-.";
	for($i=0; $i<strlen($s); $i++){
		// Note our use of ===.  Simply == would not work as expected
		// because the position of 'A' is the 0th (first) character and 0 == false
		if(strpos($a, $s[$i]) === false) return false;
	}
	return true;
}


// this makes sure the string is characters only.
function cal_is_color($s){
	// make sure it is 6 characters (FFFFFF for instance)
	if(strlen($s)!=6) return false;
	// acceptable characters.
	$a = "0123456789abcdefABCDEF";
	// if any part of the string is not in $a, return false
	for($i=0; $i<strlen($s); $i++){
		// Note our use of ===.  Simply == would not work as expected
		// because the position of '0' is the 0th (first) character and 0 == false
		if(strpos($a, $s[$i]) === false) return false;
	}
	return true;
}




//   #####################################################################
//          BELOW HERE ARE THE FUNCTIONS THAT UPDATE THINGS
//   #####################################################################


// delete an event type
function cal_delete_eventtype(){
	global $cal_db;
	// get the event type ID to delete.
	$id = $_POST['type_id'];
	if(!is_numeric($id)) return CAL_ADMIN_INVALID_DATA;
	// delete the event type, check if success or error
	if(cal_query_delete_type($id)) return "";
	else return CAL_ADMIN_TYPE_DEL_FAILED;	
}



// delete an event type
function cal_update_eventtype(){
	global $cal_db;
	// get the posted id for the event type
	$id = $_POST['type_id'];
	if(!is_numeric($id)) return CAL_ADMIN_INVALID_DATA;
	// get the posted data
	$data = array();
	$data['name'] = $_POST['type_name'];
	$data['color'] = $_POST['type_color'];
	$data['desc'] = $_POST['type_desc'];
	// make sure the color is valid
	if(!cal_is_color($data['color'])) return CAL_ADMIN_TYPE_COLOR_ERROR;
	// call the query function
	if(cal_query_set_eventtype($data, $id)){
		// set id to blank so we don't load the same ID for editing again.
		$_POST['type_id'] = "";
		return "";
	}else return CAL_ADMIN_TYPE_UPDATE_FAILED;	
}



// add an event type
function cal_add_eventtype(){
	global $cal_db;
	// get the posted data
	$data = array();
	$data['name'] = $_POST['type_name'];
	$data['color'] = $_POST['type_color'];
	$data['desc'] = $_POST['type_desc'];
	// make sure the color is valid
	if(!cal_is_color($data['color'])) return CAL_ADMIN_TYPE_COLOR_ERROR;
	// call the query function
	if(cal_query_set_eventtype($data)){
		// set id to blank so we don't load the same ID for editing again.
		$_POST['type_id'] = "";
		return "";
	}else return CAL_ADMIN_TYPE_ADD_FAILED;	
}



// delete a user.  Notice I didn't check to make sure you're not deleting the anonymous or root users.
// That is because it's impossible to do so since they don't actually exist in the accounts table :)
function cal_delete_user(){
	global $cal_db;
	// get the user ID to delete
	$id = $_POST['user_id'];
	// security checks.
	if(!is_numeric($id)) return CAL_ADMIN_INVALID_DATA;
	// call the delete user query function
	if(cal_query_delete_user($id)) return "";
	else return CAL_ADMIN_USER_DEL_FAILED;
}




// add a new user to the system.
function cal_add_user(){
	global $cal_db;
	// get username
	$username = trim($_POST['username']);
	$tpass = $_POST['password1'];
	$tpass2 = $_POST['password2'];
	if($tpass!=$tpass2) return CAL_ADMIN_PASSWORD_NOMATCH;
	// if you're not sure why the salt is used, do some research.
	$password = md5($tpass . CAL_SQL_PASSWD_SALT);
	// check the data
	if(strtolower($username) == CAL_ANONYMOUS OR strtolower($username) == CAL_ROOT_USERNAME) 
		return CAL_ADMIN_USERNAME_EXISTS;
	if(!cal_is_alphanumeric($username)) 
		return CAL_ADMIN_USERNAME_INVALID;
	if((($i=strlen($username))>30) || (($j=strlen($username))<3)) 
		return CAL_ADMIN_USERNAME_LENGTH;
	else if(strlen($tpass)<6) 
		return CAL_ADMIN_PASSWORD_LENGTH;
	// buld the query
	$data = array();
	$data['username'] = $username;
	$data['password'] = $password;
	$code = cal_query_add_user($data); // returns 0=failed, 1=ok, 2=user already existed
	// run the query
	if($code===1) return "";
	if($code===2) return CAL_ADMIN_USERNAME_EXISTS;
	else return CAL_ADMIN_USERNAME_FAILED;
}
			

// UPDATE A USER ACCOUNT.
function cal_update_user(){
	global $cal_db;
	// get user id
	$id = $_POST['user_id'];
	// security checks.
	if(!is_numeric($id)) return CAL_ADMIN_INVALID_DATA;
	// create the data variables. pdata for permissions
	$pdata = array();
	// organize the permissions.  Note we only add 'y' because 
	// we remove all permissions before updating (done in cal_query_set_permissions), 
	// so we only add new permissions back get user options
	if(($user_write = $_POST['user_write'])=="y") $pdata['write'] = "y";
	if(($user_read = $_POST['user_read'])=="y") $pdata['read'] = "y";
	if(($user_edit = $_POST['user_edit'])=="y") $pdata['edit'] = "y";
	// get user's options concerning other's events
	if(($edit_others = $_POST['edit_others'])=="y") $pdata['editothers'] = "y";
	if(($edit_past = $_POST['edit_past'])=="y") $pdata['editpast'] = "y";
	if(($read_others = $_POST['read_others'])=="y") $pdata['readothers'] = "y";
	// get user's options about reminders
	if(($user_remind_set = $_POST['user_remind_set'])=="y") $pdata['remind_set'] = "y";
	if(($user_remind_get = $_POST['user_remind_get'])=="y") $pdata['remind_get'] = "y";
	// get user's options for thir account.
	if(($user_approval = $_POST['user_approval'])=="y") $pdata['needapproval'] = "y";
	if(($admin = $_POST['is_admin'])=="y") $pdata['admin'] = "y";
	if(($disabled = $_POST['user_disabled'])=="y") $pdata['disabled'] = "y";
	// run the query
	$r = cal_query_set_permissions($pdata, $id);
	if($r) return "";
	else return CAL_ADMIN_SETPERMISSIONS_FAILED;

}


// CHANGE A USER PASSWORD
function cal_change_pass(){
	global $cal_db;
	// get user id and password
	$id = $_POST['user_id'];
	$tpass = $_POST['newpass'];
	$password = md5($tpass . CAL_SQL_PASSWD_SALT);
	// security checks
	if(!is_numeric($id)) return CAL_ADMIN_INVALID_DATA;
	if($id==0) return CAL_ADMIN_INVALID_DATA; 
	// basic string checks.
	if((strlen($tpass)<3) || (strlen($tpass)>15)) return CAL_ADMIN_PASSWORD_LENGTH;
	// build update query and run the update
	$r = cal_query_change_pass($password, $id);
	if($r) return "";
	return CAL_ADMIN_PASSWORD_FAILED;
}



// THIS SETS THE CALENDAR DISPLAY OPTIONS
function cal_set_options(){
	global $cal_db;
	// get the options
	$data = array();
	$data['skin'] = $_POST['new_skin'];
	$data['language'] = $_POST['new_lang'];
	if($_POST['whole_day']=="y")   $data['whole_day'] ="y";
	else                           $data['whole_day'] ="n";
	if($_POST['anon_naming']=="y") $data['anon_naming'] ="y";
	else                           $data['anon_naming'] ="n";
	if($_POST['show_times']=="y")  $data['show_times'] ="y";
	else                           $data['show_times'] ="n";
	if($_POST['hours_24']=="y")    $data['hours_24'] ="y";
	else                           $data['hours_24'] ="n";
	if($_POST['start_monday']=="y")$data['start_monday'] ="y";
	else                           $data['start_monday'] ="n";
	// do checks for the timeout (make sure it's a number!)
	$time = $_POST['timeout'];
	if(!is_numeric($time)) $time = 5;
	elseif($time < 1) $time = 1;
	elseif($time > 999) $time = 999;
	$data['timeout'] = $time;
	// run the option updating query
	$r = cal_query_set_options($data);
	if($r) return "";
	else return CAL_ADMIN_SETTINGS_FAILED;
	
}



// THIS FUNCTION WILL RESET THE ROOT PASSWORD
function cal_reset_rootpass(){
	global $cal_db;
	// get passwords
	$old = $_POST['cal_oldrootpass'];
	$new1 = $_POST['cal_newrootpass1'];
	$new2 = $_POST['cal_newrootpass2'];
	// quick checks
	if($new1 != $new2) return CAL_ADMIN_PASSWORD_NOMATCH;
	if(strlen($new1)<5) return CAL_ADMIN_PASSWORD_LENGTH;
	// hash the password
	$newpass = md5($new1 . CAL_SQL_PASSWD_SALT);
	$data = array();
	$data['root_password'] = $newpass;
	// run the update query
	$r = cal_query_set_options($data);
	if($r) return "";
	else return CAL_ADMIN_SETTINGS_FAILED;
}






//   #####################################################################
//          BELOW HERE ARE THE FUNCTIONS THAT WRITE OUT THE HTML
//   #####################################################################



// THIS FUNCTION PRINTS OUT A BLOCK WITH THE USER PERMISSSIONS
// FOR THE "EDIT USERS" TAB.  USER ID 0 IS ANONYMOUS.
function cal_admin_userblock($uid, $uname){
	global $cal_db;
	// security check
	if(!cal_is_alphanumeric($uname)) return "";
	if(!is_numeric($uid)) return "";
	// get the permissions
	$r = cal_query_permissions($uid);
	if(!$r) return "";
	// orgnaize permissions from the DB
	$p = array();
	while($d = $cal_db->sql_fetchrow($r)){
		$p[$d['pname']] = $d['pvalue'];
	}
	if($p['read']=='y') $read = " checked ";
	if($p['write']=='y') $write = " checked ";
	if($p['edit']=='y') $edit = " checked ";
	if($p['editothers']=='y') $editothers = " checked ";
	if($p['editpast']=='y') $editpast = " checked ";
	if($p['readothers']=='y') $readothers = " checked ";
	if($p['approval']=='y') $approval = " checked ";
	if($p['remind_set']=='y') $remind_set = " checked ";
	if($p['remind_get']=='y') $remind_get = " checked ";
	if($p['admin']=='y') $admin = " checked ";
	if($p['disabled']=='y') $disabled = " checked ";
	// anonymous user special cases
	if($uid==0){
		$pass_button = "";
		$del_button = "";
		$block_class = "cal_admin_userblock_anonymous";
	}else{
		$pass_button = '<input class="cal_admin_edituser_button" type="button" onClick="javascript:var npass; if (npass=get_new_pass()) { document.user_'.$uid.'.op.value=\'changepass\'; document.user_'.$uid.'.newpass.value = npass; document.user_'.$uid.'.submit();}" value="'.CAL_ADMIN_CHANGE_PASSWORD.'">';
		$del_button = '<input class="cal_admin_edituser_button" type="button" onClick="javascript:var ds; if(ds=del(\''.$uname.'\')){javascript:document.user_'.$uid.'.op.value=\'deleteuser\'; document.user_'.$uid.'.submit();}" value="'.CAL_ADMIN_DELETE_USER.'">';
		$block_class = "cal_admin_userblock";
	}
	// print out the table.
	$output = '<form action="'.cal_getlink(CAL_URL_FILENAME."?action=admin").'" method="post" name="user_'.$uid.'" id="user_'.$uid.'">
	  	  <table class="'.$block_class.'">
			  <tr align="left" width="100%"> 
				<td width="150">
				  <span class="cal_admin_usernames">'.$uname.'</span><br>
				  <table cellpadding="0" cellspacing="0"><tr><td>
				  	<input name="is_admin" type="checkbox" id="is_admin" value="y"'.$admin.'>
				  </td><td>
				  	'.CAL_ADMIN_ADMINISTRATOR.'
				  </td></tr></table>
				  <table cellpadding="0" cellspacing="0"><tr><td>
				  	<input name="user_disabled" type="checkbox" id="user_disabled" value="y"'.$disabled.'>
				  </td><td>
				  	'.CAL_ADMIN_DISABLE_ACCOUNT.'
				  </td></tr></table>
				</td>
				<td width="150"> 
				  <input name="user_read" type="checkbox" id="user_read" value="y"'.$read.'>
				  '.CAL_ADMIN_VIEW_OWN_EVENTS.'<br>
				  <input name="user_write" type="checkbox" id="user_write" value="y"'.$write.'>
				  '.CAL_ADMIN_ADD_EVENTS.'<br>
				  <input name="user_edit" type="checkbox" id="user_edit" value="y"'.$edit.'>
				  '.CAL_ADMIN_EDIT_OWN_EVENTS.'<br>
				</td><td width="150"> 
				  <input name="edit_others" type="checkbox" id="edit_others" value="y"'.$editothers.'>
				  '.CAL_ADMIN_EDIT_OTHERS.'<br>
				  <input name="edit_past" type="checkbox" id="edit_past" value="y"'.$editpast.'>
				  '.CAL_ADMIN_EDIT_PAST.'<br>
				  <input name="read_others" type="checkbox" id="read_others" value="y"'.$readothers.'>
				  '.CAL_ADMIN_VIEW_OTHERS.'
				</td><td width="1"> 
				  <input name="op" type="hidden" id="op" value="update_user"> 
				  <input type="hidden" name="action" value="admin">
				  <input type="hidden" name="user_id" value="'.$uid.'">
				  <input name="username" type="hidden" id="username" value="'.$uname.'"> 
				  <input name="newpass" type="hidden" id="newpass">
				  <input class="cal_admin_edituser_button" type="submit" name="Submit" value="'.CAL_ADMIN_SET_OPTIONS.'">
				  '.$pass_button.'
				  '.$del_button.'
				</td>
			</tr>
		</table>
		</form>
		';
	return $output;
}




// THIS IS THE BIG MAIN FUNCTION.  
// THIS FUNCTION PRINTS OUT THE TABS AND DIFFERENT SECTIONS WITH ALL THE OPTIONS.
function cal_options_menu($cal_error, $cal_message){
	global $cal_db, $cal_options;
	// print jaascript that will be used by the sections below.
	$output = '
		<script type="text/Javascript">
		function get_new_pass(){
			var password1 = "", password2 = "";
			do{
				while (password1 == ""){ password1 = prompt("'.CAL_ADMIN_ENTER_NEWPASS.'","");}
				if (password1 == null) return;
				while (password2 == ""){ password2 = prompt("'.CAL_ADMIN_REENTER_NEWPASS.'","");}
				if (password2 == null) return;
				if (password1 != password2){
					alert("Passwords do not match!");
					password1 = "";
					password2 = "";
				}
			} while (password1 != password2 || password1 == "")
			if (password1 != null && password2 != null) return password1;
			else return 0;
		}
		function del(s){
			return confirm("'.CAL_CONFIRM_DELETEUSER.'\n'.CAL_USERNAME.': "+s);
		}
		</script>
		<br>';
	// print the javascript used by the tabs.
	$output .= "
		<script type='text/javascript'>
			function cal_showtab(id){
				document.getElementById('cal_admin_options').style.display = 'none';
				document.getElementById('cal_admin_users').style.display = 'none';
				document.getElementById('cal_admin_adduser').style.display = 'none';
				document.getElementById('cal_admin_eventtypes').style.display = 'none';
				document.getElementById(id).style.display = 'block';
				
				document.getElementById('cal_admin_options_tab').className = 'cal_tab';
				document.getElementById('cal_admin_users_tab').className = 'cal_tab';
				document.getElementById('cal_admin_adduser_tab').className = 'cal_tab';
				document.getElementById('cal_admin_eventtypes_tab').className = 'cal_tab';
				document.getElementById(id+'_tab').className = 'cal_tab2';
			}
			function cal_colorselector(){
				window.open ('".CAL_INCLUDE_PATH_URL."libs/colorselector/color.htm', 'cal_colorselector', config='height=235,width=235, toolbar=no, menubar=no, scrollbars=no, resizable=no,location=no, directories=no, status=no')
			}
		</script>
		";
	// print the navigation menu
	$output .= cal_navmenu();
	// main box surrounding everything.
	$output .= "<table width='100%' class='box'><tr><td><br><center>";
	// configure the tabs and sections
	$op = $_POST['op'];
	$class1 = "cal_tab";
	$class2 = "cal_tab";
	$class3 = "cal_tab";
	$class4 = "cal_tab";
	if($op=="deleteuser" OR $op=="changepass" OR $op=="update_user"){
		$show1 = " display: block; ";
		$class1 = "cal_tab2";
	}elseif($op=="add"){
		$show2 = " display: block; ";
		$class2 = "cal_tab2";
	}elseif($op=="eventype" OR $op=="eventtypedelete" OR $op=="eventtypeupdateload" OR $op=="eventtypeupdate" OR $op=="eventtypeadd"){
		$show3 = " display: block; ";
		$class3 = "cal_tab2";
	}else{
		$show4 = " display: block; ";
		$class4 = "cal_tab2";
	}
	// print any messages
	if($cal_error!="") $output .= "<center><span class='failure'>$cal_error</span></center><br>";
	elseif($cal_message!="") $output .= "<center><span class='success'>$cal_message</span></center><br>";
	// print the tabs
	$output .= "<span class='$class4' id='cal_admin_options_tab' onClick=\"cal_showtab('cal_admin_options');\">".CAL_ADMIN_TAB_GENERAL."</span> ";
	$output .= "<span class='$class1' id='cal_admin_users_tab' onClick=\"cal_showtab('cal_admin_users');\">".CAL_ADMIN_TAB_EDITUSERS."</span> ";
	$output .= "<span class='$class2' id='cal_admin_adduser_tab' onClick=\"cal_showtab('cal_admin_adduser');\">".CAL_ADMIN_TAB_ADDUSER."</span> ";
	$output .= "<span class='$class3' id='cal_admin_eventtypes_tab' onClick=\"cal_showtab('cal_admin_eventtypes');\">".CAL_ADMIN_TAB_TYPES."</span> ";
	// #######################################
	// BEGIN PRINTING THE USER EDITING SECTION
	$output .= '<div class="cal_tabbody" id="cal_admin_users" style="text-align: left; display: none; '.$show1.'">';
	$output .= "<br>";
	$output .= "<span class='spanlink' onClick=\"cal_toggle('userhelp')\">Show/Hide Help</span><br><br>";
	$output .= "<div id='userhelp' style='display: none; text-align: left;'>".CAL_ADMIN_PERMISSIONS_EXPLAIN."</div>"; 
				
	// print out anonymous user options
	$output .= cal_admin_userblock(0, CAL_ANONYMOUS);
	// get all users from accounts table.
	$result = cal_query_getallusers();
	if(!$result) echo "<span class='failure'>".CAL_ADMIN_GETUSERS_FAILED."</span>";
	else{
		while($row = $cal_db->sql_fetchrow($result)){
			$output .= cal_admin_userblock($row['id'], $row['user']);
		}
	}
	$output .= "<br>";
	$output .= "</div>";
	// #######################################
	// BEGIN PRINTING THE ADD NEW USER MENU
	$output .= '
		<div class="cal_tabbody" id="cal_admin_adduser" style="display: none; '.$show2.'">
        <form action="'.cal_getlink(CAL_URL_FILENAME."?action=admin").'" method="post" name="add_user" id="add_user">
		<br>
		<table width="400" border="0" cellpadding="3" cellspacing="0">
			<tr>
				<td style="text-align: right;"><span class="cal_tab_subtitle">'.CAL_USERNAME.'</span></td>
				<td><input name="username" type="text" id="username" size="20" maxlength="20"></td>
			</tr><tr>
				<td style="text-align: right;"><span class="cal_tab_subtitle">'.CAL_ADMIN_ENTER_PASSWORD.'</span></td>
				<td><input name="password1" type="password" id="password" size="20" maxlength="15"></td>
			</tr><tr> 
				<td style="text-align: right;"><span class="cal_tab_subtitle">'.CAL_ADMIN_ENTER_PASSWORD_AGAIN.'</span></td>
				<td><input name="password2" type="password" id="password" size="20" maxlength="15"></td>
			</tr><tr>
				<td colspan="2">
				<input type="hidden" name="action" value="admin">
				<input name="op" type="hidden" id="op" value="add">
				<center><input name="submit" type="submit" id="submit" value="'.CAL_ADMIN_CREATE_USER.'"></center>
				</td>
			</tr>
		</table>
		<br>
		</form>
		</div>
		';
	// #######################################
	// BEGIN PRINTING THE CALENDAR OPTIONS TAB
	// first we have to reload the options again.
	// This is because they were originally loaded BEFORE the changes were committed to the database
	// so in order to get the correct changed values, we need to load them again here.
	cal_load_options();
	// get all the skins for the system
	$skinlist = cal_getskins();
	$skinoptions = "";
	foreach($skinlist as $skin) {
		$skin2 = htmlspecialchars($skin);
		if( $skin=="" ) continue;
		elseif( cal_option("skin")==$skin ) $skinoptions.= "<option value=\"".$skin2."\" selected>".$skin2."</options>\n";
		else $skinoptions .= "<option value=\"".$skin2."\">".$skin2."</option>\n";
	}
	// set up the skin select drop down 
	if($skinoptions=="") $skinmenu = CAL_ADMIN_NO_SKINS;
	else $skinmenu = '<select name="new_skin">'.$skinoptions.'</select>';
	// get all the languages for the system
	$langlist = cal_getlangs();
	$langoptions = "";
	foreach($langlist as $lang) {
		$lang2 = htmlspecialchars($lang);
		if( $lang=="" ) continue;
		elseif( cal_option("language")==$lang ) $langoptions.= "<option value=\"".$lang."\" selected>".$lang."</options>\n";
		else $langoptions .= "<option value=\"".$lang2."\">".$lang2."</option>\n";
	}
	// set up the skin select drop down 
	if($langoptions=="") $langmenu = CAl_ADMIN_NO_LANGS;
	else $langmenu = '<select name="new_lang">'.$langoptions.'</select>';
	// get the other options from the system (loaded already above)
	if(cal_option("anon_naming")) $naming_yes = ' checked ';
	else $naming_no = ' checked ';
	if(cal_option("hours_24")) $hours_yes = ' checked ';
	else $hours_no = ' checked ';
	if(cal_option("start_monday")) $monday_yes = ' checked ';
	else $monday_no = ' checked ';
	if(cal_option("show_times")) $times_yes = ' checked ';
	else $times_no = ' checked ';
	$timeout = cal_option("timeout");
	// output the options menu html
    $output .= '
		<div class="cal_tabbody" id="cal_admin_options" style="display: none; '.$show4.'">
        <form action="'.cal_getlink(CAL_URL_FILENAME."?action=admin").'" method="post" name="cal_options" id="cal_options">
		<br>
		<fieldset style="width: 90%; text-align: left;">
			<legend><span class="cal_tab_subtitle">Calendar Options</span></legend>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<tr> 
					<td width="60%" style="text-align: right;">'.CAL_ADMIN_SKIN_INSTRUCT.'</td>
					<td width="40%" align="left">'.$skinmenu.'</td>
				</tr>
				<tr> 
					<td width="60%" style="text-align: right;">'.CAL_ADMIN_LANG_INSTRUCT.'</td>
					<td width="40%" align="left">'.$langmenu.'</td>
				</tr>
				<tr> 
					<td style="text-align: right;">'.CAL_ADMIN_TIMES_INSTRUCT.'</td>
					<td align="left">
						<input type="radio" name="show_times" value="y"'.$times_yes.'>
						'.CAL_ADMIN_YES.'<br>
						<input type="radio" name="show_times" value="n"'.$times_no.'>
						'.CAL_ADMIN_NO.'
					</td>
				</tr>
				<tr> 
					<td style="text-align: right;">'.CAL_ADMIN_CLOCK_INSTRUCT.'</td>
					<td align="left">
						<input type="radio" name="hours_24" value="n"'.$hours_no.'>
						12 '.CAL_ADMIN_HOUR_CLOCK.'<br>
						<input type="radio" name="hours_24" value="y"'.$hours_yes.'>
						24 '.CAL_ADMIN_HOUR_CLOCK.'
					</td>
				</tr>
				<tr> 
					<td style="text-align: right;">'.CAL_ADMIN_STARTDAY_INSTRUCT.'</td>
					<td align="left"> 
						<input type="radio" name="start_monday" value="y"'.$monday_yes.'>
						'.CAL_MONDAY.'<br> 
						<input type="radio" name="start_monday" value="n"'.$monday_no.'>
						'.CAL_SUNDAY.' 
					</td>
				</tr>
				<tr> 
					<td style="text-align: right;">'.CAL_ADMIN_ALIAS_INSTRUCT.'</td>
					<td align="left"> 
						<input type="radio" name="anon_naming" value="y"'.$naming_yes.'>
						'.CAL_ADMIN_YES.'<br> 
						<input type="radio" name="anon_naming" value="n"'.$naming_no.'>
						'.CAL_ADMIN_NO.'
					</td>
				</tr>
				<tr> 
					<td height="10" colspan="2">
						<center>
							<input name="op" type="hidden" id="op" value="calendar">
							<input name="action" type="hidden" id="action" value="admin">
							<br>
							<table width="300" border="0" cellpadding="0" cellspacing="0">
							<tr> 
								<td><input name="submit" type="submit" id="submit" value="'.CAL_ADMIN_SUBMIT_OPTIONS.'"></td>
								<td><input name="reset" type="reset" id="reset" value="'.CAL_ADMIN_RESET_OPTIONS.'"></td>
							</tr>
							</table>
							<br>
						</center>
					</td>
				</tr>
			</table>
		</fieldset>
		</form>
        <form action="'.cal_getlink(CAL_URL_FILENAME."?action=admin").'" method="post" name="cal_rootpass" id="cal_rootpass">
		';
	if(cal_root()){
		$output .= '
			<br>
			<fieldset style="width: 90%; text-align: left;">
			<legend><span class="cal_tab_subtitle">'.CAL_ADMIN_RESET_ROOT_PASSWORD.'</span></legend>
			<table align="center" width="400" border="0" cellpadding="6" cellspacing="0">
				<br>
				<tr><td style="text-align: right;">
					'.CAL_ADMIN_ENTER_PASSWORD.':
				</td><td>
					<input name="cal_newrootpass1" type="password" size="30" maxlength="30">
				</td></tr><tr><td style="text-align: right;">
					'.CAL_ADMIN_ENTER_PASSWORD_AGAIN.':
				</td><td>
					<input name="cal_newrootpass2" type="password" size="30" maxlength="30">
				</td></tr><tr><td colspan="2">
					<input type="hidden" name="op" value="setrootpass">
					<center><input type="submit" value="'.CAL_ADMIN_SUBMIT_ROOTPASS.'"></center>		
				</td></tr>
			</table>
			</fieldset>
			</form>';
	}
	$output .= '
		<br><br>
		</div>';
	// #######################################
	// START THE EVENT TYPE TAB
	$eventtype_op = "eventtypeadd";
	$title = CAL_ADMIN_ADD_EVENTTYPE;
	if(is_numeric($_POST['type_id'])){
		$q = "SELECT * FROM ".CAL_SQL_PREFIX."eventtypes where id=".$_POST['type_id'];
		$r = $cal_db->sql_query($q);
		if($r){
			$data = $cal_db->sql_fetchrow($r);
			$eventtype_id = $_POST['type_id'];
			$eventtype_name = $data['typename'];
			$eventtype_desc = $data['typedesc'];
			$eventtype_color = $data['typecolor'];
			$eventtype_op = "eventtypeupdate";
			$title = CAL_ADMIN_EDIT_EVENTTYPE . ": ".$_POST['type_id'];
		}else $eventtypemodify = "";
	}
	$output .= '
	<div class="cal_tabbody" id="cal_admin_eventtypes" style="display: none; '.$show3.'">
		<br><center>
		<fieldset style="width: 90%; text-align: left;">
			<legend><span class="cal_tab_subtitle">'.$title.'</span></legend>
			<table width="500" align="center"><tr><td style="text-align: left;">
				<form action="'.cal_getlink(CAL_URL_FILENAME."?action=admin").'" method="post">
				'.CAL_ADMIN_EVENTTYPE_NAME.':<br><input value="'.$eventtype_name.'" name="type_name" type="text" size="55" maxlength="50"><br><br>
				'.CAL_ADMIN_EVENTTYPE_COLOR.':<br><input id="type_color_preview" type="text" disabled size="1" style="background-color: #'.$eventtype_color.';"><input value="'.$eventtype_color.'" name="type_color" id="type_color" type="text" size="10" maxlength="6"><input type="button" value="'.CAL_ADMIN_COLORSELECTOR.'" onClick="cal_colorselector();"><br><br>
				'.CAL_ADMIN_EVENTTYPE_DESC.':<br>
				<textarea name="type_desc" rows="2" cols="45">'.$eventtype_desc.'</textarea>
				<br><br>
				<input type="hidden" name="op" value="'.$eventtype_op.'">
				<input type="hidden" name="type_id" value="'.$eventtype_id.'">
				<input type="submit" value="'.CAL_ADMIN_SUBMIT_EVENTTYPE.'">
				</form>
			</td></tr></table>
		</fieldset>
		<br><br>
		<fieldset style="width: 90%; text-align: left;">
			<legend><span class="cal_tab_subtitle">'.CAL_ADMIN_EDIT_EVENTTYPES.'</span></legend>
	';
	// get the event types
	$r = cal_query_get_eventtypes();
	$output .= '<table width="95%" align="center" cellspacing="0" cellpadding="0">';
	$start = 0;
	// check returned db variable
	if(!$r) $output.= "<span class='failure'>".CAL_ADMIN_TYPE_GET_FAILED."</span>";
	// loop through all types, displaying their options and modify/delete buttons
	while($d = $cal_db->sql_fetchrow($r)){
		// if the first one, don't put the dashed spacer before it.
		if($start==1){
			$output .= "<tr><td colspan='4'><div style='height: 1px; border-bottom: 1px dashed #778899;'><!-- spacer --></div></td></tr>";
		}else $start = 1;
		// print out the html for the current event type in the loop
		$output .= '
			<tr>
			<td width="1"><table width="20" height="20"><tr><td style="border: 1px solid #333333; background-color: #'.$d['typecolor'].';">&nbsp;</td></tr></table>
			</td>
			<td style="text-align: left; padding: 3px;" width="*">
				<span style="color: #8899CC; font-size: 14px; font-weight: bold; font-family: arial;">'.$d['typename'].'</span>
				<br>
				<span style="font-size: 10px; font-weight: bold; font-family: arial;">'.$d['typedesc'].'</span>
			</td><td style="padding: 10px;" width="100">
				<form action="'.cal_getlink(CAL_URL_FILENAME."?action=admin").'" method="post">
						<input type="hidden" name="op" value="eventtypeupdateload">
						<input type="hidden" name="type_id" value="'.$d['id'].'">
						<input type="submit" value="'.CAL_MODIFY.'">
				</form>
			</td><td style="padding: 3px;" width="100">
				<form id="cal_type_delete_form_'.$d['id'].'" action="'.cal_getlink(CAL_URL_FILENAME."?action=admin").'" method="post">
					<input type="hidden" name="op" value="eventtypedelete">
					<input type="hidden" name="type_id" value="'.$d['id'].'">
					<input type="button" value="'.CAL_DELETE.'" onClick="if(confirm(\''.str_replace("'","\\'",CAL_CONFIRM_DELETE_EVENTTYPE."\\n".CAL_CONFIRM_DELETE_EVENTTYPE_EXTRA).'\')){ document.getElementById(\'cal_type_delete_form_'.$d['id'].'\').submit();}">
				</form>
			</td></tr>
			';
	}
	$output .= '</table>';
	// end the list of event types
	$output .='
		</fieldset>
		<br><br>
		</center>
		</div>
		';
	// end the main table around the tabs.
	$output .= "<br></center></td></tr></table>";
	return $output;
	
} // END THE OPTIONS MENU FUNCTION





?>