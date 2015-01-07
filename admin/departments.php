<?php
include("includes/config.inc.php");
include("includes/functions-required.php");

$hdr=loadPage("header",'Header');

$menuitems=array();
$menuitems[]=array('title'=>'Add','url'=>'departments.php?section=add');
$menuitems[]=array('title'=>'List','url'=>'departments.php?section=view');
$hdr->AddRows("list",$menuitems);


$tmpl=loadPage("departments", 'Departments');

$success="";


if (isset($_REQUEST['add'])) {
    if($_REQUEST['division']=='') $division=0; else $division=$_REQUEST['division'];
    if($_REQUEST['chair']=='') $chair=0; else $chair=$_REQUEST['chair'];
    if ($db->Execute("INSERT INTO departments SET
    				name='$_REQUEST[name]',
    				shortname='$_REQUEST[shortname]',
    				division_id=$division,
    				chair=$chair				
    				")) {
        $success = " <strong>Complete</strong>";
    }
}
else {
    if (isset($_REQUEST['update'])) {
	    if($_REQUEST['division']=='') $division=0; else $division=$_REQUEST['division'];
		if($_REQUEST['chair']=='') $chair=0; else $chair=$_REQUEST['chair'];
        if ($db->Execute("UPDATE departments SET
    				name='$_REQUEST[name]',
    				shortname='$_REQUEST[shortname]',
    				division_id=$division,
    				chair=$chair		
    				WHERE department_id=$_REQUEST[id]")) {
        $success = " <strong>Complete</strong>";
    	}
    }
    else {
        if (isset($_REQUEST['delete'])) {
            if ($db->Execute("DELETE FROM departments WHERE department_id=$_REQUEST[id]")) {
                $success = " <strong>Department Deleted</strong>";
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
            							departments.name as deptname,
            							departments.department_id as department_id,
            							CONCAT(users.last_name,', ',users.first_name) as chair
            						FROM departments 
            						LEFT JOIN divisions using (division_id) 
            						LEFT JOIN users on (departments.chair=users.user_id)            						
            						WHERE 1 order by departments.name");

            if (is_array($values)) {
                $tmpl->AddRows("viewlist",$values);
                $tmpl->setAttribute("view","visibility","visible");
              } 
              $hdr->AddVar("header","title","Departments: View");
            break;
        case "add":
            
            $divisions = $db->Execute("SELECT name,division_id FROM divisions WHERE 1 order by name");
            if (count($divisions)>0) {
                $division_options=$divisions->GetMenu('division','',true,false,8);
            }
            $users = $db->Execute("SELECT CONCAT(last_name,', ',first_name) as name,user_id FROM users WHERE 1 order by last_name,first_name");
            if (count($users)>0) {
                $user_options=$users->GetMenu('chair','',true,false,8);
            }
            
            $tmpl->AddVars("add",array('division_options'=>$division_options,'user_options'=>$user_options));
            $tmpl->setAttribute("add","visibility","visible");
            $hdr->AddVar("header","title","Departments: Add");
            
            
            break;
        case "update":
            $values = $db->GetRow("SELECT          						 
            							divisions.division_id as divid,
            							shortname,
            							departments.name as deptname,
            							departments.department_id as department_id,
            							users.user_id as chair_id
            						FROM departments 
            						LEFT JOIN divisions using (division_id) 
            						LEFT JOIN users on (departments.chair=users.user_id)            						
            						WHERE departments.department_id=$_REQUEST[id]");

            if (is_array($values)) {
	            $divisions = $db->Execute("SELECT name,division_id FROM divisions WHERE 1 order by name");
	            if (count($divisions)>0) {
	                $values['division_options']=$divisions->GetMenu2('division',$blank1stitem=$values['divid'],true,false,8);
	            }
	            $users = $db->Execute("SELECT CONCAT(last_name,', ',first_name) as name,user_id FROM users WHERE 1 order by last_name,first_name");
	            if (count($users)>0) {
	                $values['user_options']=$users->GetMenu2('chair',$values['chair_id'],true,false,8);
            	}
				$values['id']=$_REQUEST['id'];
                $tmpl->AddVars("update",$values);
                $tmpl->setAttribute("update","visibility","visible");
              } 
              $hdr->AddVar("header","title","Departments: Update");
            
            
            break;
    }
}

$hdr->AddVar('header','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');