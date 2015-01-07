<?php

/*** begin our session ***/
session_start();
include_once('../includes/global.inc.php');

if(st_login($_POST['username'],$_POST['password'])) {
		echo("<script type='text/javascript'>
            document.location='$_POST[target]';
            window.navigate('$_POST[target]');
            </script>");
	}
    
 
?>

<html>
<head>
<title>Login</title>
</head>
<body>
<p><?php echo $message; ?>
</body>
</html>