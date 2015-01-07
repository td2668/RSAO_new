<?php

error_reporting(E_ALL);
//Generate xml file from db to run the external homepage Flash file
//Randomizes order each time

include_once('global.inc.php');
$filepath='./';

$sql="SELECT * FROM users_oneline LEFT JOIN users using (user_id) LEFT JOIN users_disabled USING(user_id)
		WHERE users_disabled.user_id IS NULL
		";
$users=$db->getAll($sql);
print_r($users);

//write file header
if (!$propfile = fopen("{$filepath}props.xml","w+")) die("Prop file Is Not Writeable");

fwrite($propfile,"<?xml version='1.0' encoding='UTF-8'?>\n
<component name='XMLSlideShowV3'>\n
<data>\n");

foreach($users as $user) if(trim($user['oneline'])!='') {

	$user['oneline']= addslashes($user['oneline']);
	$name=addslashes($user['first_name'] . ' ' . addslashes($user['last_name']));
	fwrite($propfile,"	<item itemEvents='true'
						contentPath='images/$user[user_id].jpg'
						url='http://research.mtroyal.ca/research.php?action=view&type=researchers&rid=$user[user_id]'
						fullName='$name'
						description='$user[oneline]'
						/>\n");
}
fwrite($propfile,"</data>\n
</component>\n");
fclose($propfile);

?>