<?php 
function authenticate($username, $password, $type='conn', $remember="") {
	if($type != 'conn') {
		$length = 60*60*24*365;
		if(mysqlFetchRow("users", "username='$username' AND password2='$password'")) {
			$user = mysqlFetchRow("users", "username='$username' AND password2='$password'");
			setcookie("user_conn[user_id]", 	  $user['user_id'],   time()+$length);
			setcookie("user_conn[user_type]", $user['user_type'], time()+$length);
			setcookie("user_conn[username]",  $user['username'],  time()+$length);
			setcookie("user_conn[password]",  $user['password2'],  time()+$length);
			if($user['sys_admin'] ) return "Administrator";
			if($user['pr_admin']) return "PR";
			else return false;
		}
		else return false;
	}
	else {
		if(mysqlFetchRow("users", "username='$username' AND password2='$password'")) return true;
		else {
			if(isset($_COOKIE['user_conn'])) unAuthenticate();
			return false;
		}
	}
}
function unAuthenticate() {
	setcookie("user_conn[user_id]",   "",  time()-1);
	setcookie("user_conn[user_type]","" , time()-1);
	setcookie("user_conn[username]", "",  time()-1);
	setcookie("user_conn[password]", "",  time()-1);	
	goTo("index.php"); 
}
$status = "";
if(isset($login)) {
	if(!isset($remember)) $remember ="";
	if(authenticate($username, md5($password), 'login', $remember) == "Administrator") goTo("index.php");
	if(authenticate($username, md5($password), 'login', $remember) == "PR") goTo("index_pr.php");
	else $status = "<span style='color:red'> Sorry your username or password was incorrect please try again.</span>";
}
if(isset($kill)) unAuthenticate();

if(isset($_COOKIE['user_conn'])) {
	if(authenticate($_COOKIE['user_conn']['username'], $_COOKIE['user_conn']['password'], 'conn') != "Administrator") {
		unAuthenticate();
		goTo("index.php");
	}
}
else {
	
	include("templates/template-header_login.html");
	$hasharray = array('status'=>$status);
	$filename = 'templates/template-login.html';
	$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
	echo $parsed_html_file;
	include("templates/template-footer.html");
	exit;
}
?> 
