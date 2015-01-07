<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");

$template = new Template;
include("html/header.html");
$titles=array('Chair','Dean','Associate Dean','Acting Chair','Acting Dean','Director');

if(isset($_REQUEST['add'])) {
	if(isset($_REQUEST['dept_id'])) {
		//$dept=mysqlFetchRow("departments","department_id=$_REQUEST[dept_id]");
		//if(is_array($dept)) $division_id=$dept['division_id'];
		$division_id=0;
	}
	$values=array('null',$_REQUEST['dept_id'],$_REQUEST['user_id'],$_REQUEST['title'],$division_id);
	$result = mysqlInsert("admin", $values);
	if ($result == 1) $success = " <strong>Complete</strong>";
	else $success = "Error inserting: $result\n<br>";

}
else if (isset($_REQUEST['update'])) {
	$_REQUEST['section']='view';
	$values = array('user_id'=>$_REQUEST['user_id'],'department_id'=>$_REQUEST['dept_id'],'division_id'=>$_REQUEST['division_id'],'title'=>$_REQUEST['title']);
		$result=mysqlUpdate("admin", $values, "admin_id=$_REQUEST[id]");
		if ($result == 1) $success = " <strong>Updated</strong>";
		else $success = "Error updating: $result\n<br>";
}
else if (isset($_REQUEST['delete'])) {
	if (mysqlDelete("admin", "admin_id=$_REQUEST[id]")) $success=" <strong>Administrator Deleted</strong>";
}

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "view":
			/*
            $users = mysqlFetchRows("users","1 order by last_name,first_name");
			$output = "";
			if(is_array($users)) {
				foreach($users as $user) {
					$admin = mysqlFetchRow("admin","user_id = $user[user_id]");
					if (is_array($admin)){
						$department=mysqlFetchRow("departments","department_id = $admin[department_id]");
						$faculty=mysqlFetchRow("divisions","division_id = $admin[division_id]");
						$output.="
						<tr><td bgcolor='#E09731'><a style='color:white' href='administrators.php?section=update&id=$admin[admin_id]'><b>Update</b></a></td>
						<td bgcolor='#D7D7D9'>$user[last_name]</td>
						<td bgcolor='#D7D7D9'>$user[first_name]</td>
						<td bgcolor='#D7D7D9'>$admin[title]</td>";
						if(is_array($department)) $output.="<td bgcolor='#D7D7D9'>$department[name]</td>";
						else $output.="<td bgcolor='#D7D7D9'>No Department Listed</td>";
						if(is_array($faculty)) $output.="<td bgcolor='#D7D7D9'>$faculty[name]</td>";
						else $output.="<td bgcolor='#D7D7D9'>No Faculty Listed</td>";
					} //admin
				}//foreach user
				$hasharray = array('success'=>$success, 'output'=>$output);
				$filename = 'templates/template-admin_view.html';
			}//isarray users
			else {
				$hasharray = array('title'=>"Administrators");
				$filename = 'includes/error-no_records.html';
			}
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            
            */
            
            $admins=mysqlFetchRows("admin","1 order by user_id");
            $output="";
            if(is_array($admins)){
                foreach($admins as $admin){
                    $user=mysqlFetchRow("users","user_id=$admin[user_id]");
                    if($admin['department_id'] == 0) $dept="";
                    else {
                        $department=mysqlFetchRow("departments","department_id=$admin[department_id]","name");
                        $dept=$department['name'];
                    }
                    if($admin['division_id'] == 0) $div="";
                    else {
                        $division=mysqlFetchRow("divisions","division_id=$admin[division_id]","name");
                        $div=$division['name'];
                    }
                    
                    $output.="
                        <tr><td bgcolor='#E09731'><a style='color:white' href='administrators.php?section=update&id=$admin[admin_id]'><b>Update</b></a></td>
                        <td bgcolor='#D7D7D9'>$user[last_name]</td>
                        <td bgcolor='#D7D7D9'>$user[first_name]</td>
                        <td bgcolor='#D7D7D9'>$admin[title]</td>
                        <td bgcolor='#D7D7D9'>$dept</td>
                        <td bgcolor='#D7D7D9'>$div</td></tr>";
                    
                }
                $hasharray = array('success'=>$success, 'output'=>$output);
                $filename = 'templates/template-admin_view.html';
            }
            
            else {
                $hasharray = array('title'=>"Administrators");
                $filename = 'includes/error-no_records.html';
            }
            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;
			break;
		case "add":
			$users = mysqlFetchRows("users","1 order by last_name,first_name");
			$departments = mysqlFetchRows("departments","1 order by name");
			$divisions = mysqlFetchRows("divisions","1 order by name");
			$user_list = "";$dept_list="";$division_list="";
			if(is_array($users)) {
				foreach($users as $user) {
					$user_list .= "<option value='".$user['user_id']."'>".$user['last_name'].", ".$user['first_name']."</option>";
				}
			}
			if(is_array($departments)) {
				foreach($departments as $department) {
					$dept_list .= "<option value='".$department['department_id']."'>".$department['name']."</option>";
				}
			}
			if(is_array($divisions)) {
				foreach($divisions as $division) {
					$division_list .= "<option value='".$division['division_id']."'>".$division['name']."</option>";
				}
			}
			$hasharray = array('success'=>$success, 'user_list'=>$user_list,'dept_list'=>$dept_list,'division_list'=>$division_list);
			$filename = 'templates/template-admin_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
			break;
		case "update":
			$admin = mysqlFetchRow("admin", "admin_id=$_REQUEST[id]");
			$users = mysqlFetchRows("users","1 order by last_name,first_name");
			$divisions = mysqlFetchRows("divisions","1 order by name");
			$departments = mysqlFetchRows("departments","1 order by name");
			$user_list = "";$dept_list="";$division_list="";
			if(is_array($users)) {
				foreach($users as $user) {
					if ($user['user_id']==$admin['user_id']) $user_list .= "<option selected value='".$user['user_id']."'>".$user['last_name'].", ".$user['first_name']."</option>";
					else $user_list .= "<option value='".$user['user_id']."'>".$user['last_name'].", ".$user['first_name']."</option>";
				}
			}
			if(is_array($departments)) {
				foreach($departments as $department) {
				if($department['department_id']==$admin['department_id']) $dept_list .= "<option selected value='".$department['department_id']."'>".$department['name']."</option>";
				else $dept_list .= "<option value='".$department['department_id']."'>".$department['name']."</option>";
				}
			}
			if(is_array($divisions)) {
				foreach($divisions as $division) {
				if($division['division_id']==$admin['division_id']) $division_list .= "<option selected value='".$division['division_id']."'>".$division['name']."</option>";
				else $division_list .= "<option value='".$division['division_id']."'>".$division['name']."</option>";
				}
			}
			$title_list = "";
			foreach($titles as $title){
				if($title==$admin['title']) $title_list.="<option selected value='$title'>$title</option>";
				else $title_list.="<option  value='$title'>$title</option>";
			}
			$hasharray = array('success'=>$success,'id'=>$_REQUEST['id'],'user_list'=>$user_list,'dept_list'=>$dept_list,'division_list'=>$division_list,'title_list'=>$title_list);
			$filename = 'templates/template-admin_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
			break;
	}
}
//-- Footer File
include("templates/template-footer.html");
?>