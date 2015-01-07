<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/image-functions.php");
include("includes/class-template.php");
//$picture_path="/opt/lampp/htdocs/pictures/";
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");

include("html/header.html");

//echo "In header<br>";
//print_r($_REQUEST);
//error_reporting(E_ALL);

if(isset($_REQUEST['add'])) {
        //echo "Add Request<br>";
	//$success="Picturepath: $picture_path";
    //$picture_path="/opt/lampp/htdocs/pictures/";
	if(is_uploaded_file($_FILES['file_name']['tmp_name'])) {
		$ext = explode(".", $_FILES['file_name']['name']);
		$file_name_noext = "picture".mktime();
		$file_name = $file_name_noext.".".$ext[1];
		
		copy($_FILES['file_name']['tmp_name'], $configInfo['picture_path'].$file_name);
		unlink($_FILES['file_name']['tmp_name']);
		
		if (isset($_REQUEST['feature'])) $featureflag = 1;
		else $featureflag = 0;
		resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path']."thumb_".$file_name);
		$response="";
		

	    	if(isset($_REQUEST['resize']) && $_REQUEST['type'] == 'front-main') resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 200);
			if(isset($_REQUEST['resize']) && $_REQUEST['type'] == 'news') resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 140);
            if(isset($_REQUEST['resize']) && $_REQUEST['type'] == 'faculty') resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 175);
			if(isset($_REQUEST['shadow'])) shadowImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 6, 87);
			
	
		$values = array('null', $_REQUEST['caption'], $file_name, $featureflag);
		$result=mysqlInsert("pictures", $values);
        if($result==1) $success=" <strong>Complete </strong>$response ";
        else $success= " Error Saving: $result ";
	}
	else $success=" <strong>You did not specify a proper file</strong>";
}
if (isset($_REQUEST['update'])) {
	if (isset($_REQUEST['feature'])) $featureflag = 1;
	else $featureflag = 0;
	if(is_uploaded_file($_FILES['file_name']['tmp_name'])) {
		$values = mysqlFetchRow("pictures", "picture_id=$_REQUEST[id]");
		unlink($configInfo['picture_path'].$values['file_name']);
		unlink($configInfo['picture_path']."thumb_".$values['file_name']);
		
		$old_file = explode(".", $values['file_name']);
		$ext = explode(".", $_FILES['file_name']['name']);
		
		$file_name = $old_file[0].".".$ext[1];
		
		copy($_FILES['file_name']['tmp_name'], $configInfo['picture_path'].$file_name);
		unlink($_FILES['file_name']['tmp_name']);
        $response="";
		resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path']."thumb_".$file_name);
		if(isset($_REQUEST['resize']) && $_REQUEST['type'] == 'front-main') resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 200);
            if(isset($_REQUEST['resize']) && $_REQUEST['type'] == 'news') resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 140);
            if(isset($_REQUEST['resize']) && $_REQUEST['type'] == 'faculty') resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 175);
            if(isset($_REQUEST['shadow'])) shadowImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 6, 87);
		else $response = "";
		$values = array('caption'=>$_REQUEST['caption'], 'file_name'=>$file_name, 'feature'=>$featureflag);
		if(mysqlUpdate("pictures", $values, "picture_id=$_REQUEST[id]")) $success=" <strong>Picture Updated</strong> $response";
	}
    else {
		if(mysqlUpdate("pictures", array('caption'=>$_REQUEST['caption'], 'feature'=>$featureflag), "picture_id=$_REQUEST[id]")) $success=" <strong>Picture Updated</strong>";
	 
	}
}
if (isset($_REQUEST['delete'])) {
   // echo "Deleting";
	$values = mysqlFetchRow("pictures", "picture_id=$_REQUEST[id]");
	unlink($configInfo['picture_path'].$values['file_name']);
	unlink($configInfo['picture_path']."thumb_".$values['file_name']);
	mysqlDelete("pictures_associated", "picture_id=$_REQUEST[id]");
	if(mysqlDelete("pictures", "picture_id=$_REQUEST[id]")) $success=" <strong>Picture Deleted</strong>";
    else $success="Error Deleting";
}

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "view":  
			$values = mysqlFetchRows("pictures", "1 order by picture_id desc");
			$output = "";
			if(is_array($values)) {
				foreach($values as $index) {
					$thumb_file = $configInfo['picture_url']."thumb_".$index['file_name'];
					$file = $configInfo['picture_url'].$index['file_name'];
					$size = getimagesize($configInfo['picture_path'].$index['file_name']);
					if ($index['feature'] == 1) $featureflag = "Y";
					else $featureflag = "N";
					$output .= " 
						<tr>
							<td bgcolor='#E09731'><a style='color:white' href='pictures.php?section=update&id=$index[picture_id]'><b>Update</b></a></td>
							<td align='center'><a href='javascript:openPictureWin(\"$file\", \"$size[0]\", \"$size[1]\")'><img src='$thumb_file' bordercolor='black' border='1'></a></td>
							<td align='center' width='150'>$index[caption]</td>
							<td align='center'>$featureflag</td>
						</tr>";
				}			
				$hasharray = array('success'=>$success, 'output'=>$output);
				$filename = 'templates/template-pictures_view.html';
			}
			else {
				$hasharray = array('title'=>"Pictures");
				$filename = 'includes/error-no_records.html';
			}
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
		case "add":
			$hasharray = array('success'=>$success);
			$filename = 'templates/template-pictures_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
		case "update": 
			$values = mysqlFetchRow("pictures", "picture_id=$_REQUEST[id]");
			$file_name = $configInfo['picture_url'].$values['file_name'];
			if ($values['feature'] == 1) $picture_feature = "checked";
			else $picture_feature = "";
			$hasharray = array('id'=>$values['picture_id'], 'caption'=>$values['caption'], 'file_name'=>$file_name, 'picture_feature'=>$picture_feature);
			$filename = 'templates/template-pictures_update.html';

			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
	} 
}
//-- Footer File
include("templates/template-footer.html");
?>