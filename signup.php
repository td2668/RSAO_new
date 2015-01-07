<?php

/**
 * This file is used to load the annual report pages, the $_GET['page'] parameter is required.
 */
/* * *********************************
 * INCLUDES
 * ********************************** */
require_once('includes/global.inc.php');
require_once('includes/securimage/securimage.php');




/* * *********************************
 * MAIN
 * ********************************** */
if ( sessionLoggedin() == false ) {
   // $userid = GetVerifyUserId();
   // $userid=0;
   // if(!check_userid($userid)) die(1);
    //Do the GET[page] redirection. Is this the gatekeeper file? Why not an included security header??
    
    
    // The signup form - this will create a student/external user account only. Need to add checks for this new category in other routines
    $tmpl=loadPage("site_reg", 'Registration');
    
    if(isset($_REQUEST['submit'])){
	    //checks
	    $errmsg='';
	    if(!isset($_REQUEST['first_name'])
	    	|| !isset($_REQUEST['last_name'])
	    	|| !isset($_REQUEST['username'])
	    	|| !isset($_REQUEST['password'])
	    	|| !isset($_REQUEST['email'])
			) $errmsg.="Malformed request<br>";
	    else {
		 	$image = new Securimage();
		 	if ($image->check($_REQUEST['ct_captcha']) != true) $errmsg.="Wrong Captcha Code<br>";
		 	if(strlen($_REQUEST['first_name']) < 2) $errmsg.="First name?<br>";
		 	if(strlen($_REQUEST['last_name']) < 2) $errmsg.="Last name?<br>";
		 	if(strlen($_REQUEST['email']) < 2) $errmsg.="Email?<br>";
		 	if(strlen($_REQUEST['username']) < 5) $errmsg.="Student ID?<br>";
		 	if(strlen($_REQUEST['password']) < 6) $errmsg.="Password too short<br>";
		 	$sql="SELECT CONCAT(last_name,', ',first_name) as name from users WHERE username='$_REQUEST[username]'";
		 	$user=$db->getRow($sql);
		 	if(count($user) > 0) {
			 	$errmsg.="Student ID already registered ($user[name])<br>";
			 	//print_r($user);
			 }
		}
		if($errmsg!=""){
			 $_REQUEST['errmsg']=$errmsg;
			 $tmpl->addVars('form',$_REQUEST);
			 $tmpl->setAttribute('form','visibility','visible');
		}
		else { //process data
			$sql="INSERT INTO users SET
				last_name='".mysql_real_escape_string($_REQUEST['last_name'])."',
				first_name='".mysql_real_escape_string($_REQUEST['first_name'])."',
				username='".mysql_real_escape_string($_REQUEST['username'])."',
				emp_type='STUDENT',
				password2='".md5($_REQUEST['password'])."'";
			$result=$db->Execute($sql);
			if(!$result) print($db->ErrorMsg());
			else {
				$id=$db->Insert_ID();
				$result=$db->Execute("INSERT INTO profiles SET user_id=$id, email='".mysql_real_escape_string($_REQUEST['email'])."'");
				if(!$result) print($db->ErrorMsg());
			}
			if($result){
				if(isset($_REQUEST['target'])) if($_REQUEST['target'] != '') {
					echo("<script type='text/javascript'>document.location='/$_REQUEST[target]'</script>");
					}
				else $tmpl->setAttribute('msg','visibility','visible');
					
			}	
		}
			     
	}
	else{ //initial call for blank form
    
   // $captcha_html=Securimage::getCaptchaHtml();
    
    $tmpl->setAttribute('form','visibility','visible');
    if(isset($_REQUEST['target'])) $tmpl->addVar('form','target',$_REQUEST['target']);
    }

    
    
    $tmpl->displayParsedTemplate();
}
else echo("<script>window.location='/index.php'</script>");
