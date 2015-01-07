<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");
//-- Header File
include("templates/template-header.html");

if(isset($add)) {
	if($parent_id == "") $level = 1;
	else $level = 2;
	$values = array('null', $name, $level, $parent_id);
	if(mysqlInsert("topics_general", $values)) $success=" <strong>Complete</strong>";
}
else if (isset($update)) {
	if($parent_id == "") $level = 1;
	else $level = 2;
	$values = array('name'=>$name, 'level'=>$level, 'parent_id'=>$parent_id); 
	if(mysqlUpdate("topics_general", $values, "topic_id=$id")) $success=" <strong>Topic Updated</strong>";
}
else if (isset($delete)) {
	if(mysqlDelete("topics_general", "topic_id=$id") && mysqlDelete("topics_general", "parent_id=$id"))$success=" <strong>Topic Deleted</strong>";
}

if (isset($section)) {
	if(!isset($success)) $success="";
	switch($section){
		case "view":
			$values = mysqlFetchRows("topics_general");
			$output = "";
			if(is_array($values)) {
				foreach($values as $index) {
					$parent = mysqlFetchRow("topics_general", "topic_id=$index[parent_id]"); 
					$output .= "
						<tr>
							<td bgcolor='#E09731'><a style='color:white' href='topics-general.php?section=update&id=$index[topic_id]'>Update</a></td>
							<td bgcolor='#D7D7D9'>$index[name]</td>
							<td bgcolor='#D7D7D9'>$index[level]</td>
							<td bgcolor='#D7D7D9'>$parent[name]</td>
						</tr>";
				}
				$hasharray = array('success'=>$success, 'output'=>$output);
				$filename = 'templates/template-topics-general_view.html';
			}
			else {
				$hasharray = array('title'=>"General Topics");
				$filename = 'includes/error-no_records.html';
			}
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
		case "add": 
			$values = mysqlFetchRows("topics_general", "parent_id=''");
			$parent_options = "";
			if (is_array($values)) {foreach($values as $index) $parent_options .= "<option value='$index[topic_id]'>$index[name]</option>";}
			$hasharray = array('success'=>$success, 'parent_options'=>$parent_options);
			$filename = 'templates/template-topics-general_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
		case "update":
			$values = mysqlFetchRow("topics_general", "topic_id=$id"); 
			$options = mysqlFetchRows("topics_general", "parent_id=''");
			$parent_options = "";
			foreach($options as $index) {
				if ($index['topic_id'] == $values['parent_id']) $parent_options .= "<option selected value='$index[topic_id]'>$index[name]</option>";
				else if ($index['topic_id'] == $values['topic_id']) {}
				else $parent_options .= "<option value='$index[topic_id]'>$index[name]</option>";
			}
			$hasharray = array('id'=>$values['topic_id'], 'name'=>$values['name'], 'parent_options'=>$parent_options);
			$filename = 'templates/template-topics-general_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
	} 
}
//-- Footer File
include("templates/template-footer.html");
?>