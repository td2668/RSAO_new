<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");

$hdr=loadPage("header",'Header');

$menuitems=array();
$menuitems[]=array('title'=>'Add','url'=>'deadlines.php?section=add');
$menuitems[]=array('title'=>'List','url'=>'deadlines.php?section=view');
$hdr->AddRows("list",$menuitems);


$tmpl=loadPage("deadlines", 'Deadlines');

$success="";
/*
echo"<pre>";  
print_r($_POST);
print_r($_GET); 
echo"</pre>";
*/
if(isset($_REQUEST['action'])) if(isset($_REQUEST['add']) || isset($_REQUEST['update']) || $_REQUEST['action']=="add_date_add" || $_REQUEST['action']=="add_date_update") {
	
	$topics = (isset($_REQUEST['topics']))?$topics = implode(",", $_REQUEST['topics']): "";
	if(!isset($_REQUEST['approved'])) $approved = "no"; else $approved='yes';
	if(!isset($_REQUEST['internal'])) $internal = "no"; else $internal='yes';
}

if(isset($_REQUEST['action'])) if(isset($_REQUEST['add']) || $_REQUEST['action']=="add_date_add") {
	//only one date for an ADD
	//Need logic to deal with no date in the field. 
	$_REQUEST['section']='update';
	$d_date=$close_warning_date=$early_warning_date=$expiry_date=$days_in_advance=0;
	//switched to all date fields processed independently. 
	if($_REQUEST['d_date']!=''){
		$tmp_date = explode("-", $_REQUEST['d_date']);
		if(checkdate($tmp_date[1],$tmp_date[2],$tmp_date[0])) $d_date = mktime(1,1,1,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
	}
	if($_REQUEST['close_warning_date']!=''){
		$tmp_date = explode("-", $_REQUEST['close_warning_date']);
		if(checkdate($tmp_date[1],$tmp_date[2],$tmp_date[0])) $close_warning_date = mktime(1,1,1,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
	}
	if($_REQUEST['early_warning_date']!=''){
		$tmp_date = explode("-", $_REQUEST['early_warning_date']);
		if(checkdate($tmp_date[1],$tmp_date[2],$tmp_date[0])) $early_warning_date = mktime(1,1,1,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
	}
	if($_REQUEST['expiry_date']!=''){
		$tmp_date = explode("-", $_REQUEST['expiry_date']);
		if(checkdate($tmp_date[1],$tmp_date[2],$tmp_date[0])) $expiry_date = mktime(1,1,1,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
	}	
	
		
	if(isset($_REQUEST['override'])) $override=1; 
		else $override=0;// flag for don't email deadline
    if(isset($_REQUEST['no_deadline'])) $no_deadline=1; 
    	else $no_deadline=0;
    //echo ("TEST $override $no_deadline");
    
    $sql="INSERT INTO deadlines SET
    	deadline_id='null',
    	title='". mysql_real_escape_string($_REQUEST['title']) ."',
    	warning_message='". mysql_real_escape_string($_REQUEST['warning_message']) ."',
    	description='". mysql_real_escape_string($_REQUEST['description']) ."',
    	synopsis='" . mysql_real_escape_string($_REQUEST['synopsis']) ."',
    	topics= '$topics',
    	approved='$approved',
    	internal='$internal',
    	override=$override,
    	no_deadline=$no_deadline
    	";

    if(!$db->Execute($sql)) $success.='Error inserting into deadlines table';
		else{
			$success .= " <strong>Deadline Inserted;</strong>";
			$id=$db->Insert_ID();
			}
	if($_REQUEST['d_date'] !=''){
		$sql="INSERT INTO deadline_dates SET
			date_id='null',
			deadline_id=$id,
			d_date=$d_date,
			close_warning_date=$close_warning_date,
			early_warning_date=$early_warning_date,
			expiry_date=$expiry_date,
			days_in_advance=$days_in_advance
		";
		if(!$db->Execute($sql)) $success.='Error inserting deadline date into table';
			else{
				$success .= " <strong>Complete (1 date); </strong>";
				//$id=$db->Insert_ID();
				}
	}
	//add a date entry by repeating the entry (can't use zero or dates get weird)
	if($_REQUEST['action']=="add_date_add"){
		if(!$db->Execute($sql)) $success.='Error inserting new deadline date into table';
		else{
			$success .= " <strong>New entry added; </strong>";
			//$id=$db->Insert_ID();
			}

		$_REQUEST['section']='update';
	}
}
else if(isset($_REQUEST['action'])) if (isset($_REQUEST['update']) || $_REQUEST['action']=="add_date_update") {
	
	$sql="SELECT * FROM deadline_dates WHERE deadline_id=$_REQUEST[id]";
	$dates=$db->GetAll($sql);
	
	//echo("<pre>");
	//print_r($_REQUEST);
	//echo("</pre>");
	
	for($x=1;$x<= count($dates); $x++){
		
		
		
		
		$varname="d_date".$x;
		if($_REQUEST[$varname]=='') $d_date=0;
		else {
			$tmp_date = explode("-", $_REQUEST[$varname]);
			$d_date = mktime(1,1,1,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
		}
		
		$varname="close_warning_date$x";
		if($_REQUEST[$varname]=='') $close_warning_date=0;
		else {
			$tmp_date = explode("-", $_REQUEST[$varname]);
			$close_warning_date = mktime(1,1,1,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
		}
		
		
		$varname="early_warning_date$x";
		if($_REQUEST[$varname]=='') $early_warning_date=0;
		else {
			$tmp_date = explode("-", $_REQUEST[$varname]);
			$early_warning_date = mktime(1,1,1,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
		}
		
		$varname="expiry_date$x";
		if($_REQUEST[$varname]=="") $expiry_date=0;
		else {
			$tmp_date = explode("-", $_REQUEST[$varname]);
			$expiry_date = mktime(1,1,1,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
		}
		
		
		$varname="days_in_advance$x";
		if(!is_numeric($_REQUEST[$varname])) $days_in_advance=0; else $days_in_advance=$_REQUEST[$varname];
		
		$varname="date_id$x";
		$val=$_REQUEST[$varname];
		$sql="UPDATE deadline_dates SET
			deadline_id=$_REQUEST[id],
			d_date=$d_date,
			close_warning_date=$close_warning_date,
			early_warning_date=$early_warning_date,
			expiry_date=$expiry_date,
			days_in_advance=$days_in_advance
			WHERE date_id=". $_REQUEST[$varname].";";
		if(!$db->Execute($sql)) $success.=" Error updating date $x ;";
		else $success .= " <strong>Date updated; </strong>";
		
		
	} //next date entry
	if(isset($_REQUEST['override'])) $override=1; else $override=0;// flag for don't email deadline
    if(isset($_REQUEST['no_deadline'])) $no_deadline=1; else $no_deadline=0;
    if(isset($_REQUEST['approved'])) $approved='yes'; else $approved='';
    if(isset($_REQUEST['internal'])) $internal='yes'; else $internal='';
    
    $sql="UPDATE deadlines SET
		title='".addslashes($_REQUEST['title'])."', 
		warning_message='".addslashes($_REQUEST['warning_message'])."', 
		description='".addslashes($_REQUEST['description'])."', 
		synopsis='".addslashes($_REQUEST['synopsis'])."', 
		topics='".addslashes($topics)."', 
		approved='$approved', 
		internal='$internal',
		override=$override,
        no_deadline=$no_deadline
        WHERE deadline_id=".$_REQUEST['id'].";"; 
    if(!$db->Execute($sql)) $success.=" Error updating deadline ;";
	else $success .= " <strong>Deadline updated; </strong>";


	if($_REQUEST['action']=="add_date_update") {
		$sql="INSERT INTO deadline_dates SET
			date_id='null',
			deadline_id=$_REQUEST[id],
			d_date=$d_date,
			close_warning_date=$close_warning_date,
			early_warning_date=$early_warning_date,
			expiry_date=$expiry_date,
			days_in_advance=$days_in_advance
			";
		if(!$db->Execute($sql)) $success.=" Error adding date $x ;";
		else $success .= " <strong>Date added; </strong>";
		
		$_REQUEST['section']='update';
	}
	else $_REQUEST['section']='view';
	
}
if (isset($_REQUEST['delete'])) {
    $sql="DELETE FROM deadline_dates WHERE deadline_id=$_REQUEST[id]";
    if(!$db->Execute($sql)) $success.=" Error deleting date ;";
    $sql="DELETE FROM deadlines WHERE deadline_id=$_REQUEST[id]";
    if(!$db->Execute($sql)) $success.=" Error deleting deadline ;";
    else $success=" <strong>Deadline Deleted</strong>";
    

}
if(isset($_REQUEST['action'])) if($_REQUEST['action']=="drop"){
	//echo $dropid;
	$sql="DELETE FROM deadline_dates WHERE date_id=$_REQUEST[dropid]";
	if(!$db->Execute($sql)) $success.="<font color='#AA0000'>Error: $result</font>"; else $success="<strong>Dropped</strong>";
	$_REQUEST['section']="update";
}

//------------------------------------------------------------------------------------------------------
if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "view":  
			$tmpl->setAttribute("view","visibility","visible");
			$sql="SELECT d.deadline_id,d.*,dd.date_id,dd.d_date,dd.close_warning_date,dd.early_warning_date,dd.expiry_date,dd.days_in_advance
				FROM deadlines as d 
				LEFT JOIN deadline_dates as dd on d.deadline_id=dd.deadline_id
				ORDER BY dd.d_date DESC";
			$values=$db->GetAll($sql);
			//echo("<pre>");
			//print_r($values);
			//echo("</pre>");
			$viewlist=array();
			if(is_array($values)) {
				foreach($values as $index) {
					if($index['deadline_id']!=''){
						$sql="SELECT * FROM deadline_dates WHERE deadline_id=$index[deadline_id]";
						$dates=$db->GetAll($sql);
						//print_r($dates);
						if($index['d_date'] <= time()) $index['bgcolor']="#D7D7D9"; else 
							if($index['internal']=="yes") $index['bgcolor']="#CCCCFF";
							else $index['bgcolor']="#CCFFCC";
						if(count($dates)>1) $index['multi']="#FFCCCC";else $index['multi']=$index['bgcolor'];
	                    if($index['no_deadline']) {
		                    $index['bgcolor']="#6666FF";
		                    $index['d_date']='';
		                }
		                    
						if(count($dates) >0){ 
							if($index['d_date']>0) $index['d_date'] = date("Y-n-j", $index['d_date']);
							else $index['d_date']='';
						}
						
						if($index['date_id']>0){
							$sql="SELECT * FROM mail WHERE assoc_id=$index[date_id] AND type='deadline-early'";
							$mailitem_e=$db->GetRow($sql);
							if(count($mailitem_e)>0) $index['ecol']="<img src='/images/check.gif'>"; else $index['ecol']="";
							$sql="SELECT * FROM mail WHERE assoc_id=$index[date_id] AND type='deadline-close'";
							$mailitem_c=$db->GetRow($sql);				
							if(count($mailitem_c)>0) $index['ccol']="<img src='/images/check.gif'>"; else $index['ccol']="";
						}
					}
					else $index['d_date']='';
					
					$viewlist[]=$index;
				}
				
			}
			//print_r($viewlist[0]); 
			$tmpl->AddRows('viewlist',$viewlist);
			$hdr->AddVar("header","title","Deadlines: View");
            break;
		case "add":
			$sql="SELECT name,topic_id FROM topics_research WHERE 1 ORDER BY name";
			$topics=$db->Execute($sql);
			
			$topic_options=$topics->GetMenu('topics[]','',true,true,8);
			$tmpl->AddVar('add','topic_options',$topic_options);
			$tmpl->setAttribute("add","visibility","visible");
			$hdr->AddVar("header","title","Deadlines: Add New");
			
            break;
			
			
		case "update": 
			if(!isset($id)) $id =$_REQUEST['id'];
			$sql="SELECT * FROM pictures_associated WHERE object_id=$id AND table_name='deadlines'";
			$rez=$db->GetRow($sql);
			if(count($rez)>0) $picture_button = 
				"<br><br><button onClick=\"window.location='pictures-associate.php?section=update&id=$id&table_name=deadlines'\">View Associated Images</button>";
				else $picture_button='';
			//-- Selects the Record
			$values = $db->GetRow("SELECT * FROM deadlines WHERE deadline_id=$id");
			//-- Select the dates
			$dates = $db->GetAll("SELECT * FROM deadline_dates WHERE deadline_id=$id order by d_date desc");
			
			//-- Selects the Topics
			$sql="SELECT name,topic_id FROM topics_research WHERE 1 ORDER BY name";
			$topics=$db->Execute($sql);
			$objects = explode(",", $values['topics']);
			$topic_options=$topics->GetMenu2('topics',$blank1stitem=$objects,$multiple_select=true,$size=8);
			
				
					
			if($values['approved'] == "yes")$approved = "checked";
			else $approved = "";
			if($values['internal'] == "yes")$internal = "checked";
			else $internal = "";
			if($values['override']) $override="checked"; else $override="";
            if($values['no_deadline']) $no_deadline="checked"; else $no_deadline=""; 
			//Process dates
			$x=1;$datelist=array();
			foreach($dates as $date){
				if($date['d_date']<=0) $date['d_date']=''; else $date['d_date'] = date("Y-n-j", $date['d_date']);
				if($date['close_warning_date']<=0) $date['close_warning_date']=''; else $date['close_warning_date'] = date("Y-n-j", $date['close_warning_date']);
				if($date['early_warning_date']<=0) $date['early_warning_date']=''; else $date['early_warning_date'] = date("Y-n-j", $date['early_warning_date']); 
				if($date['expiry_date']<=0) $date['expiry_date']=''; else $date['expiry_date'] = date("Y-n-j", $date['expiry_date']);
				
				$date['x']=$x;
				//echo("Exp Date: $date[expiry_date]");
				$datelist[]=$date;
				$tmpl->AddRows('datelist',$datelist);
				$x++;
			}
			$htmldescription=nl2br($values['description']);
			$tmpl->AddVars('update', array(	'id'=>$values['deadline_id'],
								'title'=>$values['title'], 
								'warning_message'=>$values['warning_message'], 
							   	'description'=>$values['description'],
                                'htmldescription'=>$htmldescription, 
								'synopsis'=>$values['synopsis'], 
								'topic_options'=>$topic_options, 
							   	'approved'=>$approved, 
								'picture_button'=>$picture_button, 
								'internal'=>$internal,
								'override'=>$override,
                                'no_deadline'=>$no_deadline
								));
			$tmpl->setAttribute("update","visibility","visible");
			$hdr->AddVar("header","title","Deadlines: Update");
			
				
                       break;
	} 
}


$hdr->AddVar('header','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');

?>