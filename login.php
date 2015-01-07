<?php
require_once('includes/global.inc.php');
$_GET["menu"]="login";

/* Change variables which were otherwise changed in js/MRCfunctions.js:login_logout_form_submit() */
if(isset($_POST['username2'])){
    $_POST['username'] = $_POST['username2'];
    $_POST['password'] = $_POST['password2'];
}

$before = sessionLoggedin();
$status = sessionProcessForm();
$now = sessionLoggedin();



if ($_SERVER['SERVER_PORT'] == 443) {
    $protocol="https";
} else { 
    $protocol="http";
}
if ($_SERVER['SERVER_PORT'] != 80) {
    $port = $_SERVER['SERVER_PORT'];
    $url = "$protocol://". $_SERVER['SERVER_NAME'] . ":" . $port . $_SERVER['REQUEST_URI'];
} else {
    $url = "$protocol://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
}

if ($_REQUEST["ajax"]=="yes") {
    if ($now) {
        $tmpl = loadPage("logout_page_ajax","Sign in","loginlogout");
    } else {
        $tmpl = loadPage("login_page_ajax","Sign in","loginlogout");
    }
    
    $tmpl->addVar("PAGE", "action", "login.php");	
    if ($before != $now) { // USER SUCCESFULLY EITHER LOGGED IN OR LOGGED OUT
        $url = str_replace("ajax=yes","",$url);
        //print_r($_SESSION);
        if(isset($_REQUEST['target'])) if($_REQUEST['target']!='') $target=$_REQUEST['target'];
        elseif($_SESSION['user_info']['emp_type']=="STUDENT")  $target='content.php?page=ugr_intro';
		else $target='index.php';
		
        /*$status='
            <script type="text/javascript">
            document.location=document.location+"";
            window.navigate(window.location.href);
            </script>';*/
		if ($now==true) {
			
            $status="
            <script type='text/javascript'>
            document.location='$target';
            window.navigate('$target');
            </script>";
		} else {
            $status="
            <script type='text/javascript'>
            document.location='$target';
            window.navigate('$target');
            </script>";
        }
        
	}
	//echo htmlentities($status);
    //die('HERE');
	$tmpl->addVar("PAGE","status",$status);
} else {
	//die('In the else again');
    //header("Location: http://research.mtroyal.ca/index.php");
    header("Location: {$configInfo['url_root']}/index.php");
    // $tmpl=loadPage("login_page","Sign in","loginlogout");
}

if (sessionLoggedin() == false) {
    $loginform = sessionLoginForm();
} else {
    $loginform = "<br /><b>You are logged in as: " . (sessionLoggedUser()) . "</b><br />" . sessionLogoutForm();
}
$tmpl->addVar("PAGE","more",$status.$loginform);

$tmpl->displayParsedTemplate('page');
?>