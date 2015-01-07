<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");

include("html/header.html");

if(isset($_REQUEST['add'])) {
	$values = array('null', $_REQUEST['name']);
	$result=mysqlInsert("topics_research", $values);
	if($result==1) $success=" <strong>Complete</strong>";
	else echo $result;
}
else if (isset($_REQUEST['update'])) {
	
	$values = array('name'=>$_REQUEST['name']); 
	if(mysqlUpdate("topics_research", $values, "topic_id=$_REQUEST[id]")) $success=" <strong>Topic Updated</strong>";
}
else if (isset($_REQUEST['delete'])) {
	if(mysqlDelete("topics_research", "topic_id=$_REQUEST[id]") )$success=" <strong>Topic Deleted</strong>";
}

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "view":
			$values = mysqlFetchRows("topics_research", "1 ORDER BY name");
			$output = "";
			if(is_array($values)) {
				foreach($values as $index) {
					$output .= "
						<tr>
							<td bgcolor='#E09731'><a style='color:white' href='topics-research.php?section=update&id=$index[topic_id]'>Update</a></td>
							<td bgcolor='#D7D7D9'>$index[name]</td>
							
						</tr>";
				}
				$hasharray = array('success'=>$success, 'output'=>$output);
				$filename = 'templates/template-topics-research_view.html';
			}
			else {
				$hasharray = array('title'=>"Research Topics");
				$filename = 'includes/error-no_records.html';
			}
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
            
            
		case "add": 
			$hasharray = array('success'=>$success);
			$filename = 'templates/template-topics-research_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
            
            
		case "update":
			$values = mysqlFetchRow("topics_research", "topic_id=$_REQUEST[id]"); 
			$hasharray = array('id'=>$values['topic_id'], 'name'=>$values['name']);
			$filename = 'templates/template-topics-research_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
	} 
}
//-- Footer File
include("templates/template-footer.html");
?>