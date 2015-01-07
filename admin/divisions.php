<?php
include("includes/config.inc.php");
include("includes/functions-required.php");

$hdr=loadPage("header",'Header');

$menuitems=array();
$menuitems[]=array('title'=>'Add','url'=>'divisions.php?section=add');
$menuitems[]=array('title'=>'List','url'=>'divisions.php?section=view');
$hdr->AddRows("list",$menuitems);


$tmpl=loadPage("divisions", 'Divisions');

$success="";


if (isset($_REQUEST['add'])) {
    if($_REQUEST['dean']=='') $dean=0; else $dean=$_REQUEST['dean'];
    if($_REQUEST['associate_dean']=='') $associate_dean=0; else $associate_dean=$_REQUEST['associate_dean'];
    if ($db->Execute("INSERT INTO divisions SET
    				name='$_REQUEST[name]',
    				associate_dean=$associate_dean,
    				dean=$dean				
    				")) {
        $success = " <strong>Complete</strong>";
    }
}
else {
    if (isset($_REQUEST['update'])) {
	    if($_REQUEST['dean']=='') $dean=0; else $dean=$_REQUEST['dean'];
		if($_REQUEST['associate_dean']=='') $associate_dean=0; else $associate_dean=$_REQUEST['associate_dean'];
        if ($db->Execute("UPDATE divisions SET
    				name='$_REQUEST[name]',
    				associate_dean=$associate_dean,
    				dean=$dean	
    				WHERE division_id=$_REQUEST[id]")) {
        $success = " <strong>Complete</strong>";
    	}
    }
    else {
        if (isset($_REQUEST['delete'])) {
            if ($db->Execute("DELETE FROM divisions WHERE division_id=$_REQUEST[id]")) {
                $success = " <strong>Division Deleted</strong>";
            }
        }
    }
}

if (isset($_REQUEST['section'])) {
    if (!isset($success)) {
        $success = "";
    }
    switch ($_REQUEST['section']) {
        case "view":
            $values = $db->GetAll("SELECT          						 
            							divisions.name as divname,
            							divisions.division_id as division_id,
            							CONCAT(d.last_name,', ',d.first_name) as dean,
            							CONCAT(ad.last_name,', ',ad.first_name) as associate_dean
            						FROM divisions 
            						LEFT JOIN users as d on (divisions.dean=d.user_id)
            						LEFT JOIN users as ad on (divisions.associate_dean=ad.user_id)           						
            						WHERE 1 order by divisions.name");

            if (is_array($values)) {
                $tmpl->AddRows("viewlist",$values);
                $tmpl->setAttribute("view","visibility","visible");
              } 
              $hdr->AddVar("header","title","Divisions: View");
            break;
        case "add":
            
            $users = $db->Execute("SELECT CONCAT(last_name,', ',first_name) as name,user_id FROM users WHERE 1 order by last_name,first_name");
            if (count($users)>0) {
                $user_options=$users->GetMenu('dean','',true,false,8);
                $users->Move(0);
                $ad_options=$users->GetMenu('associate_dean','',true,false,8);
            }
            
            
            $tmpl->AddVars("add",array('ad_options'=>$ad_options,'user_options'=>$user_options));
            $tmpl->setAttribute("add","visibility","visible");
            $hdr->AddVar("header","title","Divisions: Add");
            
            break;
        case "update":
            $values = $db->GetRow("SELECT          						 
            							divisions.name as divname,
            							divisions.division_id as division_id,
            							dean,associate_dean
            						FROM divisions              						
            						WHERE divisions.division_id=$_REQUEST[id]");

            if (is_array($values)) {
	            
	            $users = $db->Execute("SELECT CONCAT(last_name,', ',first_name) as name,user_id FROM users WHERE 1 order by last_name,first_name");
	            if (count($users)>0) {
	                $values['user_options']=$users->GetMenu2('dean',$values['dean'],true,false,8);
					$users->Move(0);
	                $values['ad_options']=$users->GetMenu2('associate_dean',$values['associate_dean'],true,false,8);
	                
            	}
				$values['id']=$_REQUEST['id'];
                $tmpl->AddVars("update",$values);
                $tmpl->setAttribute("update","visibility","visible");
              } 
              $hdr->AddVar("header","title","Divisions: Update");
            
            
            break;
    }
}

$hdr->AddVar('header','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');