<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");
//$debug = 1;

include("html/header.html");

if(isset($_REQUEST['add'])) {
	//traverse($_POST);
	$table_list = array('deadlines','news','opportunities','projects','users');
	foreach($table_list as $table_name) {
		if ($debug) echo $table_name."::";
		if(isset($_POST[$table_name])) {
			if ($debug) echo "::table name set";
			if(is_array($_POST[$table_name])) {
				foreach($_POST[$table_name] as $index){
					if ($debug) echo "::".$index;
					if(isset($_REQUEST['pictures']) && is_array($_REQUEST['pictures'])) {
						foreach($_REQUEST['pictures'] as $picture_id){
							if ($debug) echo "::".$picture_id;
							if(!@is_array(mysqlFetchRow("pictures_associated", "picture_id=$picture_id AND object_id=$index AND table_name='$table_name'"))) {
								if ($debug) echo "::inserting<br>";
								$values = array('null', $picture_id, $index, $table_name);
								if(mysqlInsert("pictures_associated", $values)) $success=" <strong>Complete</strong>";
							}
						}
					}
				}
			}
		}
	}
}
else if (isset($_REQUEST['delete'])) {
	if(isset($_REQUEST['pictures'])) {
		foreach($_REQUEST['pictures'] as $picture_id) mysqlDelete("pictures_associated", "associated_id=$picture_id");
		$success=" <strong>Picture Deleted</strong>";
	}
}

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "add":
			//-- Deadlines
			$values = mysqlFetchRows("deadlines", "1 ORDER BY title");
			$deadlines = "";
			if(is_array($values))foreach($values as $index) $deadlines .= "<option value='$index[deadline_id]'>$index[title]</option>";

			//-- News
			$values = mysqlFetchRows("news", "1 ORDER BY title");
			$news = "";
			if(is_array($values))foreach($values as $index) $news .= "<option value='$index[news_id]'>$index[title]</option>";

			//-- Projects
			$values = mysqlFetchRows("projects", "1 ORDER BY name");
			$projects = "";
			if(is_array($values))foreach($values as $index) $projects .= "<option value='$index[project_id]'>$index[name]</option>";
			//-- Users
			$values = mysqlFetchRows("users", "1 ORDER BY last_name,first_name");
			$users = "";
			if(is_array($values))foreach($values as $index) $users .= "<option value='$index[user_id]'>$index[last_name], $index[first_name]</option>";

			$values = mysqlFetchRows("pictures", "1 order by file_name");
			if(is_array($values)) {
				$pictures = "";
				$i=0;
				foreach($values as $index) {
					// Use the following line to hide all pictures of users already associated.
					if(mysqlFetchRow("pictures_associated", "picture_id=$index[picture_id] and table_name='users'")) continue;
					if($i==10) {
						$pictures .= "</tr><tr>\r\n";
						$i=0;
					}
					++$i;
					$thumb_file = $configInfo['picture_url']."thumb_".$index['file_name'];
					$file = $configInfo['picture_url'].$index['file_name'];
					$size = getimagesize($file);
					$pictures .= "<td valign='bottom' align='center' width='10%'>\r\n
								  <a href='javascript:openPictureWin(\"$file\", \"$size[0]\", \"$size[1]\")'>\r\n
								  <img src='$thumb_file' bordercolor='black' border='1'></a><br>\r\n
								<b style='font-size:9px; font-family: Verdana, Arial, Helvetica, sans-serif;'>$index[caption]</b><br><img src'/images/private/spacer.gif' width='1' height='5'><br>
								  <input type='checkbox' name='pictures[]' value='$index[picture_id]'></td>\r\n";
				}
			}
			else $pictures = "<strong>There are currently no pictures in the database.</strong>";

			$hasharray = array('success'=>$success, 'deadlines'=>$deadlines, 'news'=>$news,
							   'projects'=>$projects, 'users'=>$users, 'pictures'=>$pictures);
			$filename = 'templates/template-pictures-associate_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
			break;
		case "update":
			if(mysqlFetchRows("pictures_associated AS pa LEFT JOIN pictures AS p USING(picture_id)", "pa.object_id=$_REQUEST[id] AND pa.table_name='$_REQUEST[table_name]'")){
				$values = mysqlFetchRows("pictures_associated AS pa LEFT JOIN pictures AS p USING(picture_id)", "pa.object_id=$_REQUEST[id] AND pa.table_name='$_REQUEST[table_name]'");
				if($_REQUEST['table_name'] == "deadlines") {$unique_id = "deadline_id"; $name_field = "title";}
				else if($_REQUEST['table_name'] == "events") {$unique_id = "event_id"; $name_field = "title";}
				else if($_REQUEST['table_name'] == "news") {$unique_id = "news_id"; $name_field = "title";}
				else if($_REQUEST['table_name'] == "projects") {$unique_id = "project_id"; $name_field = "name";}
				else if($_REQUEST['table_name'] == "users") {$unique_id = "user_id"; $name_field = "first_name";}
				$records = mysqlFetchRow("$_REQUEST[table_name]", "$unique_id=$_REQUEST[id]");
				if(is_array($values)) {
					$pictures = "";
					$i=0;
					foreach($values as $index) {
						if($i==10) {
							$pictures .= "</tr><tr>\r\n";
							$i=0;
						}
						++$i;
						$thumb_file = $configInfo['picture_url']."thumb_".$index['file_name'];
						$file = $configInfo['picture_url'].$index['file_name'];
						$size = getimagesize($file);
						if ($index['feature']) $border=3;
						else $border = 1;
						$pictures .= "<td valign='bottom' align='center'>\r\n
									  <a href='javascript:openPictureWin(\"$file\", \"$size[0]\", \"$size[1]\")'>\r\n
									  <img src='$thumb_file' bordercolor='black' border='$border'></a><br><img src'/images/private/spacer.gif' width='1' height='5'><br>\r\n
									  <input type='checkbox' name='pictures[]' value='$index[associated_id]'></td>\r\n";
					}
				}
				$hasharray = array('id'=>$_REQUEST['id'], 'name'=>$records[$name_field], 'pictures'=>$pictures, 'php_file'=>$_REQUEST['table_name']);
				$filename = 'templates/template-pictures-associate_update.html';
				$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
				echo $parsed_html_file;
			}
			else  echo "There are no more images associated with this object.<br><br>
						<button onClick=\"window.location='$_REQUEST[table_name].php?section=view'\">Back</button>";
			break;
	}
}
//-- Footer File
include("templates/template-footer.html");
?>