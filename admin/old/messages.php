<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");
//-- Header File
$hdr=loadPage("header",'Header');

$menuitems=array();
$menuitems[]=array('title'=>'Add','url'=>'messages.php?section=add');
$menuitems[]=array('title'=>'List','url'=>'messages.php?section=view');
$hdr->AddRows("list",$menuitems);


$tmpl=loadPage("messages", 'Messages');

if(isset($_REQUEST['add'])) {
	$subs=explode(',',$_REQUEST['substitutions']);
	$tsubs=implode(',',$subs);
	$name=mysql_real_escape_string($_REQUEST['name']);
	$message=mysql_real_escape_string($_REQUEST['message']);
	$tsubs=mysql_real_escape_string($tsubs);
	$subject=mysql_real_escape_string($_REQUEST['subject']);
	$sql="INSERT INTO messages(name,message,substitutions,subject)
			VALUES(
			'$name',
			'$message',
			'$tsubs',
			'$subject')
		";
	$result = $db->Execute($sql);
    if($result) $success=" <strong>Complete</strong>";
    else throw new Exception('Unable to insert new message into database');
	
}
else if (isset($_REQUEST['update'])) {
	$subs=explode(',',$_REQUEST['substitutions']);
	$tsubs=implode(',',$subs);
	$name=mysql_real_escape_string($_REQUEST['name']);
	$message=mysql_real_escape_string($_REQUEST['message']);
	$tsubs=mysql_real_escape_string($tsubs);
	$subject=mysql_real_escape_string($_REQUEST['subject']);
	$sql="UPDATE messages SET
			name='$name',
			message='$message',
			substitutions='$tsubs',
			subject='$subject'";
	$result = $db->Execute($sql);
    if($result) $success=" <strong>Complete</strong>";
    else throw new Exception('Unable to update');
	$section="update";
}
else if (isset($_REQUEST['copyme'])) {
	$sql="SELECT * FROM messages WHERE message_id=$_REQUEST[id]";
	$values=$db->GetRow($sql); 
	$sql="INSERT INTO messages SET 
			name= '$values[name]', 
			message='$values[message]',
			substitutions='$values[substitutions]'";
			
	$result = $db->Execute($sql);
    if($result) $success=" <strong>Complete</strong>";
    else throw new Exception('Unable to copy');
	$section="update";
	$_REQUEST['section']="view";
	
}
else if (isset($_REQUEST['delete'])) {
	$sql="DELETE FROM message WHERE message_id=$_REQUEST[id]";
	$result = $db->Execute($sql);
    if($result) $success=" <strong>Complete</strong>";
    else throw new Exception('Unable to delete');

}
if(!isset($_REQUEST['section']))$_REQUEST['section']="view";

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	if(!isset($_REQUEST['section']))$_REQUEST['section']="view";
	switch($_REQUEST['section']){
		case "view":
			$sql="SELECT * FROM messages WHERE 1";
			
			$values = $db->GetAll($sql);
			$tmpl->setAttribute("view","visibility","visible");
			$tmpl->AddVar('view','success',$success);
			if(is_array($values)) {
				
				//print_r($values);
				$tmpl->AddRows('viewlist',$values);
			}
			
            break;
			
			
		case "add": 
			$tmpl->setAttribute("add","visibility","visible");
         	$hdr->AddVar("header","title","Messages: Add New");
            break;
			
		case "update":
			$sql="SELECT * FROM messages WHERE message_id=$_REQUEST[id]";
			$tmpl->setAttribute("update","visibility","visible");
         	$hdr->AddVar("header","title","Messages: Update");		
			$values = $db->GetRow($sql);
			if(is_array($values)){
				$values['messageop']=nl2br(htmlentities($values['message']));
				$tmpl->AddVars('update',$values);
			}
            break;
	} 
}
$hdr->AddVar('success','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');

    
include("templates/template-footer.html");
?>
