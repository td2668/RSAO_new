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
	if (isset($feature)) $featureflag = 1;
	else $featureflag = 0;
	$values = array('null', $first_name, $last_name, $description, $featureflag, $keywords);
	$result = mysqlInsert("researchers", $values);
	if ($result == 1) $success=" <strong>Complete</strong>";
	else echo("Error Adding: $result\n<br>");
	$researcher_id = mysql_insert_id();
}
else if (isset($update)) {
	$researcher_id = $id;
	if (isset($feature)) $featureflag = 1;
	else $featureflag = 0;
	if(mysqlDelete("researchers_associated", "researcher_id=$id"));
	$values = array('first_name'=>$first_name, 'last_name'=>$last_name, 'description'=>$description, 'feature'=>$featureflag); 
	if(mysqlUpdate("researchers", $values, "researcher_id=$id")) $success=" <strong>Researcher Updated</strong>";
}
else if (isset($delete)) {
	mysqlDelete("researchers_associated", "researcher_id=$id");
	if(mysqlDelete("researchers", "researcher_id=$id")) $success=" <strong>Researcher Deleted</strong>";
}
if(isset($add) || isset($update)) {

	if(isset($topics_research)) {
		$topics2 = $topics_research;
		$topics_research=NULL;
		foreach($topics2 as $cur_topic) {
			$topic_row = mysqlFetchRow("topics_research","topic_id = $cur_topic");
			if(is_array($topic_row)) {
				if($topic_row['level'] == 1) $topics_research[]=$cur_topic;
				else if(!in_array($topic_row['parent_id'],$topics2)) $topics_research[]=$cur_topic;
			}
		}
	}
	$table_list = array('topics_research', 'departments');
	foreach($table_list as $table_name) {
		if(isset(${$table_name})) {
			foreach(${$table_name} as $index){
				if(!is_array(mysqlFetchRow("researchers_associated", "researcher_id=$researcher_id AND object_id=$index AND table_name='$table_name'"))) {
					$values = array('null', $researcher_id, $index, $table_name);
					if(mysqlInsert("researchers_associated", $values));
				}
			}
		}
	}
}

if (isset($section)) {
	if(!isset($success)) $success="";
	switch($section){
		case "view":  
			$values = mysqlFetchRows("researchers", "1 order by last_name");
			$output = "";
			if(is_array($values)) {
				foreach($values as $index) {
					$objects = mysqlFetchRowsOneCol("researchers_associated", "object_id", "researcher_id=$index[researcher_id] AND table_name='topics_research'");
					if(is_array($objects)) $topicsflag="#33FF33"; else $topicsflag="#FF3333";
				
					if ($index['feature'] == 1) $featureflag = "#33FF33";
					else $featureflag = "#D7D7D9";
					$bgcolor="#D7D7D9";
					$dis=mysqlFetchRow("users_disabled left join users using(user_id) left join researchers using(researcher_id)","researchers.researcher_id=$index[researcher_id]");
					if(is_array($dis)) $bgcolor='#AAAAAA';
					$output .= "
						<tr>
							<td bgcolor='#E09731'><a style='color:white' href='researchers.php?section=update&id=$index[researcher_id]'><b>Update</b></a></td>
							<td bgcolor='$bgcolor'>$index[last_name]</td>
							<td bgcolor='$bgcolor'>$index[first_name]</td>
							
							<td bgcolor='$featureflag'>&nbsp;</td>";
					$feat_pix="#D7D7D9";$fac_pix="#D7D7D9";
					if(mysqlFetchRows("pictures_associated AS pa LEFT JOIN pictures AS p USING(picture_id)", "pa.object_id=$index[researcher_id] AND pa.table_name='researchers'")){
						$pictures = mysqlFetchRows("pictures_associated AS pa LEFT JOIN pictures AS p USING(picture_id)", "pa.object_id=$index[researcher_id] AND pa.table_name='researchers'");
						if (is_array($pictures)){
							foreach ($pictures as $picture) {
								if ($picture['feature'] == 1) $feat_pix="#33FF33";
								else $fac_pix = "#33FF33";
							}
						}
					}
					$output .= "<td bgcolor='$fac_pix'>&nbsp;</td><td bgcolor='$feat_pix'>&nbsp;";
							
					$output .="</td><td bgcolor='$topicsflag'>&nbsp;</td></tr>";
				}
				$hasharray = array('success'=>$success, 'output'=>$output);
				$filename = 'templates/template-researchers_view.html';
			}
			else {
				$hasharray = array('title'=>"Researchers");
				$filename = 'includes/error-no_records.html';
			}
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;

		case "add":
			$topics = mysqlFetchRows("topics_research", "1 ORDER BY name");
			$topic_options = "";
			if(is_array($topics)) {
				foreach($topics as $topic) {
					$topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
				}
			}
			$values = mysqlFetchRows("departments", "1 ORDER BY name");
			$department_options = "";
			if(is_array($values))foreach($values as $index) $department_options .= "<option value='$index[department_id]'>$index[name]</option>"; 
			
			$hasharray = array('success'=>$success, 'topic_options'=>$topic_options, 'department_options'=>$department_options);
			$filename = 'templates/template-researchers_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;

		case "update": 			
			$picture_button = (mysqlFetchRow("pictures_associated", "object_id=$id AND table_name='researchers'"))?
				"<br><br><button onClick=\"window.location='pictures-associate.php?section=update&id=$id&table_name=researchers'\">View Associated Images</button>":"";
			$researcher = mysqlFetchRow("researchers", "researcher_id=$id");  
			if ($researcher['feature'] == 1) $researcher_feature = "checked";
			else $researcher_feature = "";
			//-- Selects the Departments
			$objects = mysqlFetchRows("researchers_associated", "researcher_id=$id AND table_name='departments'");
			$departments = mysqlFetchRows("departments", "1 ORDER BY name" );
			$department_options = "";
			$i=0;
			if(is_array($objects))foreach($objects as $object) $ids[] = $object['object_id'];
			if(is_array($departments)) {
				foreach($departments as $department) {
					if(isset($ids) && in_array($department['department_id'], $ids)) $department_options .= "<option selected value='$department[department_id]'> $department[name]</option>"; 
					else $department_options .= "<option value='$department[department_id]'>$department[name]</option>";
					++$i;
				}
			}

			//-- Selects the Topics

			$objects = mysqlFetchRowsOneCol("researchers_associated", "object_id", "researcher_id=$id AND table_name='topics_research'");
			$topics = mysqlFetchRows("topics_research", "level=1 ORDER BY name");
			$topic_options = ""; 
			if(is_array($objects)) foreach($objects as $object) $ids[] = $object['topic_id'];			
			if(is_array($topics)) {
				foreach($topics as $topic) {
					$sub_topics = mysqlFetchRows("topics_research", "parent_id=$topic[topic_id] order by name"); 
					if(is_array($objects)) { 
					if(@in_array($topic['topic_id'], $objects)) $topic_options .= "<option value='$topic[topic_id]' selected>$topic[name]</option>";
					else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
					 }
					 else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
					if(is_array($sub_topics)) {
						foreach($sub_topics as $sub_topic) {
							if (is_array($objects)) {
							if(in_array($sub_topic['topic_id'], $objects)) $topic_options .= "<option selected value='$sub_topic[topic_id]'>&nbsp;&nbsp;&nbsp;&nbsp;$sub_topic[name]</option>"; 
							else $topic_options .= "<option value='$sub_topic[topic_id]'>&nbsp;&nbsp;&nbsp;&nbsp;$sub_topic[name]</option>";
							}
							else $topic_options .= "<option value='$sub_topic[topic_id]'>&nbsp;&nbsp;&nbsp;&nbsp;$sub_topic[name]</option>";
						}
					} 
				}
			}
			
			$hasharray = array('id'=>$researcher['researcher_id'], 'first_name'=>$researcher['first_name'], 'last_name'=>$researcher['last_name'], 
							   'description'=>$researcher['description'], 'department_options'=>$department_options, 'topic_options'=>$topic_options, 
							   'picture_button'=>$picture_button, 'researcher_feature'=>$researcher_feature, 'keywords'=>$researcher['keywords']);
			$filename = 'templates/template-researchers_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
	} 
}
//-- Footer File
include("templates/template-footer.html");

?>