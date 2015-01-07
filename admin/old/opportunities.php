<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");
//-- Header File
include("templates/template-header.html");

if(isset($add) || isset($update)) {
	$i=0;
//	if (!isset($topics)) $topics[0]=0;
	
if (isset($health) || isset($nserc) || isset($sshrc) || isset($indust)) { 
$topics=""; $topics[0]=0;	
if(isset($health)) {
	//set $topics from the big list
	$topics_research=mysqlFetchRows("topics_research");
	if(is_array($topics_research)){
		foreach($topics_research as $topic_research) {
			if ($topic_research['health'] == 1 && !in_array($topic_research['topic_id'],$topics)) {$topics[$i]=$topic_research['topic_id']; $i++;}
		}
	}
}

if(isset($nserc)) {
	//set $topics from the big list
	$topics_research=mysqlFetchRows("topics_research");
	if(is_array($topics_research)){
		foreach($topics_research as $topic_research) {
			if ($topic_research['nserc'] == 1 && !in_array($topic_research['topic_id'],$topics) ) {$topics[$i]=$topic_research['topic_id']; $i++;}
		}
	}
}

if(isset($sshrc)) {
	//set $topics from the big list

	$topics_research=mysqlFetchRows("topics_research");
	if(is_array($topics_research)){
		foreach($topics_research as $topic_research) {
			if ($topic_research['sshrc'] == 1 && !in_array($topic_research['topic_id'],$topics)) {$topics[$i]=$topic_research['topic_id']; $i++;}
		}
	}
}

if(isset($indust)) {
	//set $topics from the big list

	$topics_research=mysqlFetchRows("topics_research");
	if(is_array($topics_research)){
		foreach($topics_research as $topic_research) {
			if ($topic_research['indust'] == 1 && !in_array($topic_research['topic_id'],$topics)) {$topics[$i]=$topic_research['topic_id']; $i++;}
		}
	}
}
}

else {  // buttons not set, so stick with old set

}

	$topics = (isset($topics))?$topics = implode(",", $topics): "";
	
	$tmp_date = explode("/", $post_date);
	$post_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);	

	$tmp_date = explode("/", $due_date);
	$due_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);
	
	if ($expiry_date != "") {
		$tmp_date2 = explode("/", $expiry_date);
		if (checkdate($tmp_date2[1],$tmp_date2[0],$tmp_date2[2])) $expiry_date = mktime(0,0,0,$tmp_date2[1],$tmp_date2[0],$tmp_date2[2]);
		else $expiry_date= mktime(0,0,0,$tmp_date[1],$tmp_date[0]+2,$tmp_date[2]);
	}
	else $expiry_date= mktime(0,0,0,$tmp_date[1],$tmp_date[0]+2,$tmp_date[2]);
	
		
	
	if(!isset($approved)) $approved = "no";
	if(!isset($annual)) $annual = "no";
	if(!isset($internal)) $internal = "no";
}

if(isset($add)) {
	$values = array('null', $title, $post_date, $due_date, $expiry_date, $agency, $description, $synopsis, $topics, $approved, $annual, $url, $internal);
	if(mysqlInsert("opportunities", $values)) $success=" <strong>Complete</strong>";
}
else if (isset($update)) {
	$values = array('title'=>$title, 'post_date'=>$post_date, 'due_date'=>$due_date, 'expiry_date'=>$expiry_date, 'agency'=>$agency, 'description'=>$description,'synopsis'=>$synopsis, 'topics'=>$topics, 'approved'=>$approved, 'annual'=>$annual, 'url'=>$url, 'internal'=>$internal); 
	if(mysqlUpdate("opportunities", $values, "opportunity_id=$id")) $success=" <strong>Opportunity Updated</strong>";
	
}
else if (isset($delete)) {
	if(mysqlDelete("opportunities", "opportunity_id=$id")) $success=" <strong>Opportunity Deleted</strong>";
}
if (isset($section)) {
	if(!isset($success)) $success="";
	switch($section){
		case "view":  
			$values = mysqlFetchRows("opportunities", "expiry_date >= $todays_date order by due_date desc");
			$output = "";
			if(is_array($values)) {
				foreach($values as $index) {
					$tmpdate=getdate($index['due_date']);
					if ($tmpdate['year'] > 2030) $index['due_date']='None';
					else $index['due_date'] = date("j/n/y", $index['due_date']);
					if($index['annual']=="yes") $ann="<font color='#AA0000'><br>(Annual)</font>"; else $ann="";
					if($index['post_date'] > $todays_date) $post="<font color='#AA0000'><br>(Future Post)</font>"; else $post="";
					
					$output .= "
						<tr>
							<td bgcolor='#E09731'><a style='color:white' href='opportunities.php?section=update&id=$index[opportunity_id]'>Update</a></td>
							<td bgcolor='#D7D7D9'>$index[title]</td>
							<td bgcolor='#D7D7D9'>$index[due_date]$ann</td>
							<td bgcolor='#D7D7D9'>$index[agency]</td>
							<td bgcolor='#D7D7D9'>$index[synopsis]</td>
							<td bgcolor='#D7D7D9'>$index[approved]$post</td>
						</tr>";
				}
				$values = mysqlFetchRows("opportunities", "expiry_date < $todays_date order by due_date desc");
				foreach($values as $index) {
					$tmpdate=getdate($index['due_date']);
					if ($tmpdate['year'] > 2030) $index['due_date']='None';
					else $index['due_date'] = date("j/n/y", $index['due_date']);
					
					
					$output .= "
						<tr>
							<td bgcolor='#E09731'><a style='color:white' href='opportunities.php?section=update&id=$index[opportunity_id]'>Update</a></td>
							<td bgcolor='#D7D7D9'>$index[title]</td>
							<td bgcolor='#D7D7D9'>$index[due_date] <font color='#AA0000'>(expired)</font></td>
							<td bgcolor='#D7D7D9'>$index[agency]</td>
							<td bgcolor='#D7D7D9'>$index[synopsis]</td>
							<td bgcolor='#D7D7D9'>$index[approved]</td>
						</tr>";
				}
				$hasharray = array('success'=>$success, 'output'=>$output);
				$filename = 'templates/template-opportunities_view.html';
			}
			else {
				$hasharray = array('title'=>"Opportunities");
				$filename = 'includes/error-no_records.html';
			}
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
			
			
			
			
		case "add": 
			$topics = mysqlFetchRows("topics_research", "1 ORDER BY name");
			$topic_options = "";
			if(is_array($topics)) {
				foreach($topics as $topic) 	$topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
			}
			$hasharray = array('success'=>$success, 'topic_options'=>$topic_options);
			$filename = 'templates/template-opportunities_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
			
			
			
			
		case "update": 
			$picture_button = (mysqlFetchRow("pictures_associated", "object_id=$id AND table_name='opportunities'"))?
				"<br><br><button onClick=\"window.location='pictures-associate.php?section=update&id=$id&table_name=opportunities'\">View Associated Images</button>":"";
			$values = mysqlFetchRow("opportunities", "opportunity_id=$id");
			//-- Selects the Topics
			$objects = explode(",", $values['topics']);
			$topics = mysqlFetchRows("topics_research", "level=1 ORDER BY name");
			$topic_options = ""; 
			if(is_array($objects) && $objects[0] != "" ) foreach($objects as $object) $ids[] = $object['topic_id'];			
			if(is_array($topics)) {
				foreach($topics as $topic) {
					$sub_topics = mysqlFetchRows("topics_research", "parent_id=$topic[topic_id] order by name"); 
					if(in_array($topic['topic_id'], $objects)) $topic_options .= "<option value='$topic[topic_id]' selected>$topic[name]</option>";
					else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
					 
					if(is_array($sub_topics)) {
						foreach($sub_topics as $sub_topic) {
							if(in_array($sub_topic['topic_id'], $objects)) $topic_options .= "<option selected value='$sub_topic[topic_id]'>&nbsp;&nbsp;&nbsp;&nbsp;$sub_topic[name]</option>"; 
							else $topic_options .= "<option value='$sub_topic[topic_id]'>&nbsp;&nbsp;&nbsp;&nbsp;$sub_topic[name]</option>";
						}
					} 
				}
			}
			$values['post_date'] = date("j/n/Y", $values['post_date']);
			$values['due_date'] = date("j/n/Y", $values['due_date']);
			$values['expiry_date'] = date("j/n/Y", $values['expiry_date']);
			if($values['approved'] == "yes")$approved_a = "checked";
			else $approved_a = "";
			if($values['annual'] == "yes")$annual_a = "checked";
			else $annual_a = "";
			if($values['internal'] == "yes")$internal_a = "checked";
			else $internal_a = "";
			$hasharray = array('id'=>$values['opportunity_id'], 'title'=>$values['title'], 'post_date'=>$values['post_date'], 'due_date'=>$values['due_date'], 
							   'expiry_date'=>$values['expiry_date'], 'agency'=>$values['agency'], 'description'=>$values['description'], 
							   'synopsis'=>$values['synopsis'], 'topic_options'=>$topic_options, 'approved_a'=>$approved_a, 'annual_a'=>$annual_a,
							   'picture_button'=>$picture_button, 'url'=>$values['url'], 'internal_a'=>$internal_a);
			$filename = 'templates/template-opportunities_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
	} 
}
//-- Footer File
include("templates/template-footer.html");
?>