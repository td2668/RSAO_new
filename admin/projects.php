<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
//include("includes/class-template.php");
$hdr=loadPage("header",'Header');
$tmpl=loadPage("projects", 'Projects');

$menuitems=array();
$menuitems[]=array('title'=>'Add','url'=>'projects.php?section=add');
$menuitems[]=array('title'=>'List','url'=>'projects.php?section=view');
$hdr->AddRows("list",$menuitems);


if(isset($_REQUEST['project_id']) && isset($_REQUEST['country_code']) && isset($_REQUEST['state_code']))
if(($_REQUEST['project_id']) > 0 && ($_REQUEST['country_code']) != '0' && ($_REQUEST['state_code']) != '0'){
    if($_REQUEST['country_code']=='CA') $_REQUEST['country_code']=$_REQUEST['state_code'];
    $sql="UPDATE projects SET country_code='{$_REQUEST['country_code']}', state_code='{$_REQUEST['state_code']}' WHERE project_id={$_REQUEST['project_id']}";
    $result = $db->Execute($sql);
    if($result) $success=" <strong>Complete</strong>";
    else $success=" Error: $result";
    
}

/*
if(isset($_REQUEST['delete_loc'])){
    if (mysqlDelete('projects_where',"project_where_id=$_REQUEST[loc_id]")) $success='Deleted';
    else $success='Error deleting';
    $_REQUEST['section']='where';
}
*/
if(isset($_POST['add'])) {
	if (isset($_POST['feature'])) $featureflag = 1;
	else $featureflag = 0;
	if (isset($_POST['studentflag'])) $studentflag = 1;
	else $studentflag = 0;
    if (isset($_POST['approved'])) $approved = 1;
    else $approved = 0;
    if (isset($_POST['internal_grant'])) $internal_grant = 1;
    else $internal_grant = 0;
    if(is_numeric($_POST['grant_amount']))$ga=$_POST['grant_amount']; else $ga=0;
    
    
	$tmp_date = explode("-", $_POST['enddate']);
	if(checkdate($tmp_date[1], $tmp_date[2], $tmp_date[0]))
		$enddate = mktime(0,0,0,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
	else $enddate=0;
	//$approved=1;
    $mod=date('Y-m-d');
    $_POST['name']=mysql_real_escape_string($_POST['name']);
    $_POST['synopsis']=mysql_real_escape_string($_POST['synopsis']);
    $_POST['description']=mysql_real_escape_string($_POST['description']);
    $_POST['keywords']=mysql_real_escape_string($_POST['keywords']);
    $_POST['students']=mysql_real_escape_string($_POST['students']);       
    //Figure out the next ObjectID for auto increment
    //$sql="SELECT MAX(ObjectID)as max from projects where 1";
    //$max=$db->GetRow($sql);
    //$objectid=$max['max']+1;
    $sql = "INSERT INTO projects SET 
            name='$_POST[name]',
            synopsis='$_POST[synopsis]',
            description='$_POST[description]',
            feature=$featureflag,
            keywords='$_POST[keywords]',
            studentproj=$studentflag,
            student_names='$_POST[students]',
            end_date=$enddate,
            approved=$approved,
            doll_per_yr=0,
            status='',
            modified=$mod,
            who_modified=0,
            boyerDiscovery=0,
            boyerIntegration=0,
            boyerApplication=0,
            boyerTeaching=0,
            boyerService=0,
            internal_grant=$internal_grant,
            grant_year=$_POST[grant_year],
            end_year=$_POST[end_year],
            grant_amount=$ga";
    $result = $db->Execute($sql);
    if (!$result) {
        $success.='Unable to insert new project into database';
    }
	$project_id = mysql_insert_id();
	if($result) $success=" <strong>Complete</strong>";
}



else if (isset($_POST['update'])) {
	$project_id = $_GET['id'];
	if (isset($_POST['feature'])) $featureflag = 1;
	else $featureflag = 0;
	if (isset($_POST['studentflag'])) $studentflag = 1;
	else $studentflag = 0;
	if (isset($_POST['approved'])) $approved=1; else $approved = 0;
	if (isset($_POST['internal_grant'])) $internal_grant=1; else $internal_grant = 0;
	if(is_numeric($_POST['grant_amount']))$ga=$_POST['grant_amount']; else $ga=0;

	$tmp_date = explode("-", $_POST['enddate']);
	if(checkdate($tmp_date[1], $tmp_date[2], $tmp_date[0]))
		$enddate = mktime(0,0,0,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
	else $enddate=0;
    $mod=date('Y-m-d');
    //get the existing one
    $sql="SELECT * FROM projects WHERE project_id=$project_id";
    $proj=$db->GetRow($sql);
    if($proj){
        /*if($proj['ObjectID']==0){
            //Not set yet so need to generate
            $sql="SELECT MAX(ObjectID)as max from projects where 1";
            $max=$db->GetRow($sql);
            $objectid=$max['max']+1;
        }*/
        //else $objectid=$proj['ObjectID'];
	    $db->Execute("DELETE FROM projects_associated WHERE project_id=$project_id");
	    $sql="UPDATE projects SET
	          name='".mysql_real_escape_string($_POST['name'])."', 
	          end_date=$enddate, 
	          synopsis='".mysql_real_escape_string($_POST['synopsis'])."', 
	          description='".mysql_real_escape_string($_POST['description'])."', 
	          feature=$featureflag, 
	          keywords='".mysql_real_escape_string($_POST['keywords'])."', 
	          studentproj=$studentflag, 
	          student_names='".mysql_real_escape_string($_POST['students'])."', 
	          approved=$approved,
	          studentproj=$studentflag,
	          modified=$mod,
	          internal_grant=$internal_grant,
	          grant_year=$_POST[grant_year],
	          end_year=$_POST[end_year], 
	          grant_amount=$_POST[grant_amount]
	          WHERE project_id=$project_id"; 
	    if($db->Execute($sql)) $success.=" <strong>Project Updated</strong>";
	    else $success.="Error updating project";
    }
}
else if (isset($_GET['delete'])) {
	$db->Execute("DELETE FROM projects_associated WHERE project_id=$_GET[id]");
	if($db->Execute("DELETE FROM projects WHERE project_id=$_GET[id]")) $success=" <strong>Project Deleted</strong>";
	else $success.="Error Deleting";
}
if(isset($_POST['add']) || isset($_POST['update'])) {
	#deal with problem where user may have selected subtopics AND parent. Causes multiple hits later
	#eliminate any subtopics IF the parent is selected
    /*
	if(isset($_POST['topics_research'])) {
		$topics2 = $_POST['topics_research'];
		$topics_research=NULL;
		foreach($topics2 as $cur_topic) {
			$topic_row = mysqlFetchRow("topics_research","topic_id = $cur_topic");
			if(is_array($topic_row)) {
				if($topic_row['level'] == 1) $topics_research[]=$cur_topic;
				else if(!in_array($topic_row['parent_id'],$topics2)) $topics_research[]=$cur_topic;
			}
		}
	}
    */
	//print_r($topics2);print_r($topics_research);
	$table_list = array('researchers','topics_research', 'departments');
	foreach($table_list as $table_name) {
		if(isset($_POST[$table_name])) {
			foreach($_POST[$table_name] as $index){
				$rez=$db->GetRow("SELECT * FROM projects_associated WHERE project_id=$project_id AND object_id=$index AND table_name='$table_name'");
				//echo("<pre>");
				//print_r($rez);
				//echo("</pre");
				if(count($db->GetRow("SELECT * FROM projects_associated WHERE project_id=$project_id AND object_id=$index AND table_name='$table_name'"))==0) {
					if($index != "") {
						$values = array('null', $project_id, $index, $table_name);
						$db->Execute("INSERT INTO projects_associated VALUES('null', $project_id, $index, '$table_name')");
					}
				}
			}
		}
	}
}
if (!isset($_REQUEST['section'])) $_REQUEST['section']='view';
if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "view":  
            if(isset($_REQUEST['sort'])) $sort=$_REQUEST['sort'];
             else $sort='approved';
             if(isset($_REQUEST['dir'])) {
             if($_REQUEST['dir']=='ASC') $dir='ASC';
                else $dir='DESC';
             }
             else $dir='ASC'; //default
    
			
				$values = $db->GetAll("SELECT * FROM projects WHERE 1 ORDER BY $sort $dir");		
				if(is_array($values)) {			
					//$output.="<tr><td bgcolor='#000000'  style='font-size=12px; font-weight=bold; color:white;' colspan=4> $uc_name[fullname]</td></tr>";
					//Load associated researchers into an extra element - just the first one - for sorting
					$projectlist=array();
					foreach($values as $index) {
						if ($index['feature'] == 1) $index['featureflag'] = "Y";
						else $index['featureflag'] = "N";
						if ($index['approved']) $index['acolor=']='#D7D7D9'; else $index['acolor']='#FF6666';
						//if($index['internal_grant']) $acolor='#6666FF'; else $acolor='#D7D7D9';
						$objects = $db->GetAll("SELECT object_id from projects_associated WHERE project_id=$index[project_id] AND table_name='topics_research'");
						if(count($objects)>0) $topics_set = "#33FF33"; else $topics_set = "#FF3333";
	                    $sql="SELECT CONCAT(users.last_name,',&nbsp',users.first_name,'<br>') as uname FROM projects_associated as pa LEFT JOIN users on(users.user_id=pa.object_id) WHERE pa.project_id=$index[project_id] and pa.table_name = 'researchers' ";
                        //echo $sql;
                        $peoplelist='';
                        $people=$db->getAll($sql);
                        if(count($people)>0){
                            foreach($people as $person){
                                $peoplelist.=$person['uname'];
                            }
                        }
                        $index['peoplelist']=$peoplelist;
						
						$projectlist[]=$index;
						//print_r($index);
                            
					} #foreach
				} #if isarray
			
			if($dir=='ASC')$dir='DESC';
            else $dir='ASC';
            $tmpl->AddRows('projectlist',$projectlist);
			$tmpl->setAttribute("view","visibility","visible");
			$hdr->AddVar("header","title","Projects: List");
            break;
			
			
			
		case "add":  
            $sql="SELECT name,topic_id FROM topics_research WHERE 1 ORDER BY name";
			$topics=$db->Execute($sql);
			
			$topic_options=$topics->GetMenu('topics[]','',true,true,8);
			
			     
			$values = $db->GetAll("SELECT * FROM users WHERE 1 ORDER BY last_name,first_name");
			$researcher_options = "";
			if(is_array($values)) foreach($values as $index) $researcher_options .= "<option value='$index[user_id]'>$index[last_name], $index[first_name]</option>"; 
			
			
			$sql="SELECT name,department_id FROM departments WHERE 1 ORDER BY name";
			$departments=$db->Execute($sql);
			$department_options=$departments->GetMenu('departments[]','',true,true,8);
			
			//grant Year 
            $grant_year_options='<option value=0></option>\n';
            for($year=2011;$year<=2020;$year++){
                
                $grant_year_options.="<option value='$year'>$year</option>/n";
            }
            $end_year_options='<option value=0></option>\n';
            for($year=2011;$year<=2020;$year++){
                
                $end_year_options.="<option value='$year'>$year</option>/n";
            }
			
			
			$tmpl->AddVars('add',array('topic_options'=>$topic_options,
										'department_options'=>$department_options,
										'researcher_options'=>$researcher_options,
										'grant_year_options'=>$grant_year_options,
										'end_year_options'=>$end_year_options));
			$tmpl->setAttribute("add","visibility","visible");
			$hdr->AddVar("header","title","Deadlines: Add New");
				
			
        break;
            
            
            
		case "update":
			
			$picture_button = (count($db->GetAll("SELECT * FROM pictures_associated WHERE object_id=$_GET[id] AND table_name='projects'"))>0) ?
				"<br><br><button type='button' onClick=\"window.location='pictures-associate.php?section=update&id=$_GET[id]&table_name=projects'\">View Associated Images</button>":"";
			$project = $db->GetRow("SELECT * FROM projects WHERE project_id=$_GET[id]"); 
			//print_r($project); 
			
			
			if($project['end_date']!=0 ) $project['end_date'] = date("Y-n-j", $project['end_date']); else $project['end_date']='';
			//-- Selects the Departments
			$objects = $db->GetAll("SELECT * FROM projects_associated WHERE project_id=$_GET[id] AND table_name='departments'");
			$departments = $db->GetAll("SELECT * FROM departments WHERE 1 ORDER BY name");
			$department_options = "";
			$i=0;
			if(count($objects)>0) foreach($objects as $object) $ids[] = $object['object_id'];
			if(count($departments)>0) {
				foreach($departments as $department) {
					if(isset($ids) && in_array($department['department_id'], $ids)) $department_options .= "<option selected value='$department[department_id]'> $department[name]</option>"; 
					else $department_options .= "<option value='$department[department_id]'>$department[name]</option>";
					++$i;
				}
			}
            
            //grant Year 
            $grant_year_options='<option value=0></option>\n';
            for($year=2011;$year<=2017;$year++){
                if($project['grant_year']==$year) $selected='selected'; else $selected='';
                
                $grant_year_options.="<option value='$year' $selected>$year</option>/n";
            }
            $end_year_options='<option value=0></option>\n';
            for($year=2011;$year<=2017;$year++){
                if($project['end_year']==$year) $selected='selected'; else $selected='';
                
                $end_year_options.="<option value='$year' $selected>$year</option>/n";
            }
            
			//-- Selects the Researchers
			$objects = $db->GetAll("SELECT * FROM projects_associated WHERE project_id=$_GET[id] AND table_name='researchers'");
			$researchers = $db->GetAll("SELECT * FROM users WHERE 1 ORDER BY last_name,first_name");
			$researcher_options = "";
			unset($ids); $i=0;
			if(count($objects)>0)foreach($objects as $object) $ids[] = $object['object_id'];
			if(count($researchers)>0) {
				foreach($researchers as $researcher) {
					if(isset($ids) && in_array($researcher['user_id'], $ids)) $researcher_options .= "<option selected value='$researcher[user_id]'>$researcher[last_name], $researcher[first_name] </option>"; 
					else $researcher_options .= "<option value='$researcher[user_id]'>$researcher[last_name], $researcher[first_name] </option>"; 
					++$i;
				}
			} 
			//-- Selects the Topics
			$ids=array();
			$objects = $db->GetAll("SELECT object_id FROM projects_associated WHERE project_id=$_GET[id] AND table_name='topics_research'");
			$topics = $db->GetAll("SELECT * FROM topics_research WHERE 1 ORDER BY name");
			$topic_options = ""; 
			if(count($objects)>0) foreach($objects as $object) $ids[] = $object['object_id'];
			else $ids=array("xxx"); //in case no topics associated	
				
			if(is_array($topics)) {
				foreach($topics as $topic) {
					if(in_array($topic['topic_id'], $ids)) $topic_options .= "<option value='$topic[topic_id]' selected>$topic[name]</option>";
					else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
					 
					 
				}
			}	
            
            
			
			#set the UC Name
			
			if($project['approved'] ==1) $approved="checked"; else $approved="";
            if($project['internal_grant'] ==1) $internal_grant="checked"; else $internal_grant="";
            if ($project['feature'] == 1) $project_feature = "checked";
			else $project_feature = "";
			if ($project['studentproj'] == 1) $studentflag = "checked";
			else $studentflag = "";
			
			$tmpl->AddVars('update',array('id'=>$project['project_id'], 
                                    'name'=>$project['name'], 
                                    'enddate'=>$project['end_date'], 
                                    'synopsis'=>$project['synopsis'], 
                                    'description'=>$project['description'],
							        'department_options'=>$department_options, 
                                    'topic_options'=>$topic_options, 'researcher_options'=>$researcher_options, 
							        'picture_button'=>$picture_button, 
                                    'project_feature'=>$project_feature, 
                                    'studentflag'=>$studentflag, 
                                    'students'=>$project['student_names'], 
                                    'keywords'=>$project['keywords'], 
                                    'approved'=>$approved,
                                    'internal_grant'=>$internal_grant,
                                    'grant_year_options'=>$grant_year_options,
                                    'end_year_options'=>$end_year_options,
                                    'grant_amount'=>$project['grant_amount']
                                    ));
			
			$tmpl->setAttribute("update","visibility","visible");
            $hdr->AddVar("header","title","Projects: Update");
            
            break;
            
            
            case "where":
                $table=$country_options=$state_options='';
                //Build a list of projects to choose from
                $projects= $db->GetAll("SELECT * FROM projects WHERE 1 ORDER BY name");
                $project_options='';
                if(count($projects)>0) foreach($projects as $project){
                    $selected='';
                    if(isset($_REQUEST['project_id'])) if($_REQUEST['project_id']== $project['project_id']) $selected='selected'; 
                    if($project['country_code']!='' || $project['state_code']!='') $project_options.="<option value='$project[project_id]' $selected>*** $project[name]</option>\n";
                    else $project_options.="<option value='$project[project_id]' $selected>$project[name]</option>\n";
                }
                
                if(isset($_REQUEST['project_id']))
                {
                    $sql="SELECT projects.*, cas_countries.name as country, cas_provinces_states.name as state_name FROM projects LEFT JOIN cas_countries on LEFT(projects.country_code,2)=cas_countries.country_code LEFT JOIN cas_provinces_states on projects.state_code=cas_provinces_states.state_code WHERE project_id=$_REQUEST[project_id]";
                    
                    
                    $project_locs = $db->GetAll($sql);
                     
                    $table='';
                    if(is_array($project_locs)) {
                        //display a table of projects and locations with an option to delete
                        $table="<table border='1'>\n";
                        foreach($project_locs as $project_loc){
                            $table.="<tr><td>$project_loc[country]</td><td>$project_loc[state_name]</td></tr>\n";
                        }
                        $table.="</table >";
                    }
                    //add section
                    $country_options='';
                    $countries=$db->GetAll('SELECT * FROM cas_countries WHERE 1 order by name');
                    foreach($countries as $country){
                        $selected='';
                        if(isset($_REQUEST['country_code'])) if($country['country_code']== $_REQUEST['country_code']) $selected='selected';
                        $country_options.="<option value='$country[country_code]' $selected>$country[name]</option>\n";
                    }
                    
                    $state_options='';
                    //echo ($_REQUEST['country_code']);
                    if(isset($_REQUEST['country_code'])) if($_REQUEST['country_code'] != '0'){
                        $states=mysqlFetchRows('cas_provinces_states',"country_code='$_REQUEST[country_code]' order by name");
                        if(is_array($states)) foreach($states as $state){
                            $state_options.="<option value='$state[state_code]' >$state[name]</option>\n";
                        }
                    }
                    
                    
                }//if isset project_id
                $tmpl->AddVars('where',array(     'project_options'=>$project_options,
                                        'table'=>$table,
                                        'country_options'=>$country_options,
                                        'state_options'=>$state_options,
                                        'success'=>$success));
                 $tmpl->setAttribute("where","visibility","visible");
				 $hdr->AddVar("header","title","Projects: Where");                      
                
            break;
            
	} 
}
//-- Footer File
$hdr->AddVar('header','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');
?>
