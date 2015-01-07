<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");

include("html/header.html");

if(isset($_REQUEST['add']) || isset($_REQUEST['update'])) {
	$topics = (isset($_REQUEST['topics']))?$_REQUEST['topics'] = implode(",", $_REQUEST['topics']): "";

	if($_REQUEST['expiry_date'] == "") {
		$tmp_date = explode("/", $_REQUEST['post_date']);
		$expiry_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0]+1,$tmp_date[2]);
		$expiry_date=strtotime("+1 year",$expiry_date);
	}
	else {
		$tmp_date = explode("/", $_REQUEST['expiry_date']);
		$expiry_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);
	}
	
	$tmp_date = explode("/", $_REQUEST['post_date']);
	$post_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);	

	$tmp_date = explode("/", $_REQUEST['event_date']);
	$event_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);
	
	if(!isset($_REQUEST['approved'])) $approved = FALSE; else $approved=TRUE;
	if(!isset($_REQUEST['feature'])) $feature = FALSE; else $feature=TRUE;
    if($_REQUEST['news_for']=='about') $news_for = FALSE; else $news_for=TRUE;
}

if(isset($_REQUEST['add'])) {
	$values = array('null', addslashes($_REQUEST['title']), $post_date, $event_date, $expiry_date, addslashes($_REQUEST['description']), addslashes($_REQUEST['synopsis']), addslashes($_REQUEST['source']), $_REQUEST['topics'], $approved, $feature,$news_for);
    $result=mysqlInsert("news", $values);
    if($result) $success=" <strong>Complete</strong>";
    else $success=" <strong><font color='red'>Error: $result</font></strong>";
}
else if (isset($_REQUEST['update'])) {
	$values = array('title'=>$_REQUEST['title'], 'post_date'=>$post_date, 'event_date'=>$event_date, 'expiry_date'=>$expiry_date, 'description'=>addslashes($_REQUEST['description']),
					'synopsis'=>addslashes($_REQUEST['synopsis']), 'source'=>addslashes($_REQUEST['source']), 'topics'=>$_REQUEST['topics'], 'approved'=>$approved, 'feature'=>$feature,'news_for'=>$news_for); 
	if(mysqlUpdate("news", $values, "news_id=$_REQUEST[id]")) $success=" <strong>News Updated</strong>";
}
else if (isset($_REQUEST['delete'])) {
	if(mysqlDelete("news", "news_id=$_REQUEST[id]")) $success=" <strong>News Deleted</strong>";
}

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "view":  
			$values = mysqlFetchRows("news", "1 order by event_date desc");
			$output = "";
			if(is_array($values)) {
                $output.="<tr><td colspan='6' bgcolor='#000000'><b style='color:#E1E1E1;font-size:10px'>News For Faculty</td></tr>";
				foreach($values as $index) if($index['news_for']){
					$index['event_date'] = date("j/n/y", $index['event_date']);
					if($index['approved']) $ap_col="#33FF33"; else $ap_col="#CCCCCC";
					if($index['feature']) $fe_col="#33FF33"; else $fe_col="#CCCCCC";
					$output .= "
						<tr>
							<td bgcolor='#E09731'><a style='color:white' href='news.php?section=update&id=$index[news_id]'>Update</a></td>
							<td bgcolor='#D7D7D9'>$index[title]</td>
							<td bgcolor='#D7D7D9'>$index[event_date]</td>
							<td bgcolor='#D7D7D9'>$index[synopsis]</td>
							<td bgcolor='$ap_col'>&nbsp;</td>
							<td bgcolor='$fe_col'>&nbsp;</td>
						</tr>";
                        
				}
                $output.="<tr><td style='font-size:10px'></td></tr><tr><td colspan='6' bgcolor='#000000'><b style='color:#E1E1E1;font-size:10px'>News About MRU</td></tr>";
                foreach($values as $index) if(!$index['news_for']){
                    $index['event_date'] = date("j/n/y", $index['event_date']);
                    if($index['approved']) $ap_col="#33FF33"; else $ap_col="#CCCCCC";
                    if($index['feature']) $fe_col="#33FF33"; else $fe_col="#CCCCCC";
                    $output .= "
                        <tr>
                            <td bgcolor='#E09731'><a style='color:white' href='news.php?section=update&id=$index[news_id]'>Update</a></td>
                            <td bgcolor='#D7D7D9'>$index[title]</td>
                            <td bgcolor='#D7D7D9'>$index[event_date]</td>
                            <td bgcolor='#D7D7D9'>$index[synopsis]</td>
                            <td bgcolor='$ap_col'>&nbsp;</td>
                            <td bgcolor='$fe_col'>&nbsp;</td>
                        </tr>";
                }
				$hasharray = array('success'=>$success, 'output'=>$output);
				$filename = 'templates/template-news_view.html';
			}
			else {
				$hasharray = array('title'=>"News");
				$filename = 'includes/error-no_records.html';
			}
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
            
           
            
		case "add": 
			$topics = mysqlFetchRows("topics_research", "1 ORDER BY name");
			$topic_options_news = ""; 			
			if(is_array($topics)) {
				foreach($topics as $topic) {
					$topic_options_news .= "<option value='$topic[topic_id]'>$topic[name]</option>";
					//?section=all&search_topic=
					
				}
}
			$static_post_date = date("j/n/Y", mktime(0,0,0));
			$hasharray = array('success'=>$success, 'topic_options'=>$topic_options_news, 'post_date'=>$static_post_date);
			$filename = 'templates/template-news_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
		case "update":
			$picture_button = (mysqlFetchRow("pictures_associated", "object_id=$_REQUEST[id] AND table_name='news'"))?
				"<br><br><button type='button' onClick=\"window.location='pictures-associate.php?section=update&id=$_REQUEST[id]&table_name=news'\">View Associated Images</button>":"";
			$values = mysqlFetchRow("news", "news_id=$_REQUEST[id]");
			//-- Selects the Topics
			$objects = explode(",", $values['topics']);
			$topics = mysqlFetchRows("topics_research", "1 ORDER BY name");
			$topic_options = ""; 			
			if(is_array($topics)) {
				foreach($topics as $topic) {
					if(in_array($topic['topic_id'], $objects)) $topic_options .= "<option value='$topic[topic_id]' selected>$topic[name]</option>";
					else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
				}
			}
			$values['post_date'] = date("j/n/Y", $values['post_date']);
			$values['event_date'] = date("j/n/Y", $values['event_date']);
			$values['expiry_date'] = date("j/n/Y", $values['expiry_date']);
			if($values['approved'])$approved_a = "checked";
			else $approved_a = "";
			if($values['feature'])$feature_a = "checked";
			else $feature_a = "";
            if($values['news_for']){$news_for_a = "checked";$news_for_b="";}
            else {$news_for_a = "";$news_for_b="checked";}
            //Finish news_for
			$hasharray = array( 'id'=>$values['news_id'], 
                                'title'=>$values['title'], 
                                'post_date'=>$values['post_date'], 
                                'event_date'=>$values['event_date'], 
			                    'expiry_date'=>$values['expiry_date'], 
                                'description'=>$values['description'], 
                                'synopsis'=>$values['synopsis'], 
							    'source'=>$values['source'], 
                                'topic_options'=>$topic_options, 
                                'approved_a'=>$approved_a, 
                                'feature_a'=>$feature_a,
                                'news_for_a'=>$news_for_a,
                                'news_for_b'=>$news_for_b,
							    'picture_button'=>$picture_button);
			$filename = 'templates/template-news_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
	} 
}
//-- Footer File
include("templates/template-footer.html");
?>