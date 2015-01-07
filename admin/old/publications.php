<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
$template = new Template;
//require("security.php");
//-- Header File
include("templates/template-header.html");

if(!isset($section)) $section='view';
$success="";

if(isset($update)) {
	if(isset($id)){
		if(!isset($year)) $year=0;
		if(!is_numeric($year) || $year=="") $year=0;
		if(isset($web_show)) $web_show=TRUE; else $web_show=FALSE;
		if(isset($submitted)) $submitted=TRUE; else $submitted=FALSE;
		if(isset($forthcoming)) $forthcoming=TRUE; else $forthcoming=FALSE;
		$values = array('user_id'=>$user_id,
						'authors'=>$authors,
						'year'=>$year,
						'title'=>$title,
						'source'=>$source,
						'publisher'=>$publisher,
						'url'=>$url,
						'keywords'=>$keywords,
						'abstract'=>$abstract,
						'web_show'=>$web_show,
						'submitted'=>$submitted,
						'paper_cat_id'=>$category,
						'forthcoming'=>$forthcoming);
		$result=mysqlUpdate("papers",$values,"paper_id=$id");
		if($result != 1) $success= "Error updating database";
		else $success="Updated"; 
	}//isset id
}

if(isset($add)) {
	if($user_id != ""){
	if(!isset($year)) $year=0;
	if(!is_numeric($year) || $year=="") $year=0;
	if(isset($web_show)) $web_show=TRUE; else $web_show=FALSE;
	if(isset($submitted)) $submitted=TRUE; else $submitted=FALSE;
	if(isset($forthcoming)) $forthcoming=TRUE; else $forthcoming=FALSE;
	$values = array('null',$user_id,$authors,$year,$title,$publisher,$source,$url,$keywords,$abstract,$category,$web_show,$submitted,$forthcoming);
	$result=mysqlInsert("papers",$values);
	if($result != 1) $success= "Error Adding";
	else $success="Added"; 
	}
	else $success="Error - no user specified";
}

if(isset($delete)) {
	if(isset($id)){
		$result=mysqlDelete("papers","paper_id=$id");
		if($result != 1) $success= "Error Deleting";
		else $success="Deleted"; 
	}
}

if(isset($section)) switch ($section){
	case"update":
		if(isset($id)){
			$paper=mysqlFetchRow("papers","paper_id=$id");
			if(is_array($paper)){
				if($paper['web_show']) $web_show="checked"; else $web_show="";
				if($paper['forthcoming']) $forthcoming="checked"; else $forthcoming="";
				if($paper['submitted']) {$submitted="checked";$yearstatus="disabled";}
				   else {$submitted="";$yearstatus="";}
				 if ($paper['year']==0) $paper['year']="";
				$cats=mysqlFetchRows("paper_cat","1 order by rank");
				$cat_options="";
				foreach($cats as $cat){
					if($paper['paper_cat_id']==$cat['paper_cat_id']) $sel="selected"; else $sel="";
					$cat_options.="<option value='$cat[paper_cat_id]' $sel>$cat[name]</option>";
				}
				$hasharray = array(	'success'=>$success,
									'yearstatus'=>$yearstatus,
									'authors'=>htmlspecialchars($paper['authors']),
									'year'=>$paper['year'],
									'title'=>htmlspecialchars($paper['title']),
									'source'=>htmlspecialchars($paper['source']),
									'publisher'=>htmlspecialchars($paper['publisher']),
									'url'=>$paper['url'],
									'keywords'=>htmlspecialchars($paper['keywords']),
									'abstract'=>htmlspecialchars($paper['abstract']),
									'web_show'=>$web_show,
									'submitted'=>$submitted,
									'cat_options'=>$cat_options,
									'id'=>$paper['paper_id'],
									'user_id'=>$paper['user_id'],
									'forthcoming'=>$forthcoming);
				$filename = 'templates/template-publications_update.html';
				$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
				echo $parsed_html_file;
			}
		}
		
	break;
	
	case "add":
		$user_options="";
		$users=mysqlFetchRows("users","1 order by last_name,first_name");
		foreach($users as $user){
			$usel="";
			if(isset($user_id)) if($user_id==$user['user_id']) $usel="selected"; 
			$user_options.="<option value='$user[user_id]' $usel>$user[last_name], $user[first_name]</option>";
		}
		$cats=mysqlFetchRows("paper_cat","1 order by rank");
		$cat_options="";
		foreach($cats as $cat) $cat_options.="<option value='$cat[paper_cat_id]'>$cat[name]</option>";
		if(isset($user_id)){
			$user=mysqlFetchRow("users","user_id=$user_id");
			$username=$user['last_name'].", ".substr($user['first_name'],0,1).".";
		}
		else $username="";
		$hasharray = array(	'success'=>$success,
							'username'=>$username,
							'user_options'=>$user_options,
							'cat_options'=>$cat_options);
		$filename = 'templates/template-publications_add.html';
		$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
		echo $parsed_html_file;
	break;
	
	case "view":
		$output="";
		if(isset($user_id)){
			$user=mysqlFetchRow("users","user_id=$user_id");
			$output.="<tr><td colspan=4>$user[last_name], $user[first_name]</td></tr>";
			$cats=mysqlFetchRows("paper_cat","1 order by rank");
			foreach($cats as $cat){
	//		echo "user_id=".$_COOKIE['user_conn']['user_id']." and paper_cat_id=$cat[paper_cat_id] order by submitted desc, year desc";
				$papers=mysqlFetchRows("papers","user_id=$user[user_id] and paper_cat_id=$cat[paper_cat_id] order by forthcoming desc, submitted desc, year desc");
				if(is_array($papers)){
					$output.="<tr><td bgcolor='#000000'  style='font-size=12px; font-weight=bold; color:white;' colspan='4'> $cat[name]</td></tr>";
					foreach ($papers as $paper){
						if($paper['web_show']) $pcol="<img src='/images/public/check.gif'>"; else $pcol="";
						$output .= "
								<tr>
									<td bgcolor='#CC6699'><a style='color:white' href='publications.php?section=update&id=$paper[paper_id]&user_id=$user[user_id]'>Update</a></td>
									<td bgcolor='#D7D7D9'>";
							if($paper['authors'] != "") $output.="$paper[authors]";
							if($paper['forthcoming']) $output.=" (forthcoming)";
							else if($paper['submitted']) $output.=" (submitted)";
							else if($paper['year'] != "") $output.=" ($paper[year])";
							if($paper['title'] !="") $output.=" <i>$paper[title]</i>";
							if($paper['source'] != "") $output.=" $paper[source]";
							if($paper['publisher'] != "") $output .=",$paper[publisher]";
							$output.="</td>								
									<td align='center' bgcolor='#D7D7D9'>$pcol</td>
								</tr>";
					}//each paper
				}
			}//foreach cat
		}
		$hasharray = array('success'=>$success,'output'=>$output);
		$filename = 'templates/template-publications_view.html';
		$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
		echo $parsed_html_file;
	break;
	case "choose_user":
		$output="";
		$users=mysqlFetchRows("users","1 order by last_name,first_name");
		$col=1;
		foreach($users as $user){
			$papers=mysql_num_rows(mysql_query("Select * from papers Where user_id=".$user['user_id']));
			if($papers>0) $name="<a href='publications.php?section=view&user_id=$user[user_id]'>$user[last_name], $user[first_name]</a>";
			else $name="$user[last_name], $user[first_name]";
			if($col==1) $output.="<tr>";
			$output.="<td bgcolor='#CCCCCC'>$name</td><td width='25' align='left' bgcolor='#CCCCCC'>$papers</td><td bgcolor='#666666' width='3'>&nbsp;</td>";
			if($col==3){$output.="</tr>\n";$col=1;}
			else $col++;
			
		}
		$hasharray = array('success'=>$success,'output'=>$output);
		$filename = 'templates/template-publications_choose_user.html';
		$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
		echo $parsed_html_file;
	break;
} //switch


//-- Footer File
include("templates/template-footer.html");
?>