<?php
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
                                                     // always modified
    header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");  
    include_once("includes/config.inc.php");
    include_once("includes/functions-required.php");
  //  include_once("includes/mail-functions.php");  
    include_once("includes/class-template.php");
    $template = new Template;
    include("html/header.html");
    $success='';
    if(isset($_REQUEST['section'])) $section=$_REQUEST['section'];
    else $section='committeeview';
    //Update fires when either committee changes (user_id=0) or user is added.
if(isset($_REQUEST['update'])){
    if(isset($_REQUEST['user_id'])){
        
        
        
        
        if($_REQUEST['user_id']<>0 && isset($_REQUEST['committee_id'])){
            $member=mysqlFetchRow('committee_members',"user_id=$_REQUEST[user_id] AND committee_id=$_REQUEST[committee_id]");
            if (!(is_array($member))) {
                $result=mysqlInsert('committee_members',array('null',$_REQUEST['committee_id'],$_REQUEST['user_id'],0));
                if($result != 1) $success= "Error inserting: $result";
                
            }
            
        }
    }
}

if(isset($_REQUEST['addcomm'])){
    if(isset($_REQUEST['name'])){
        $result=mysqlInsert('committees',array('null',$_REQUEST['name']));
        if($result != 1) $success= "Error inserting: $result";
        $section='committeeadd';
    }
}

if(isset($_REQUEST['updatecomm'])){
    if(isset($_REQUEST['committee_id'])){
        $result=mysqlUpdate('committees',array('name'=>$_REQUEST['name']),"committee_id=$_REQUEST[committee_id]");
        if ($result!=1) $success= "Error updating: $result";
        $section='committeeview';
    }
}

if(isset($_REQUEST['deletecomm'])){
    if(isset($_REQUEST['committee_id'])){
    $result=mysqlDelete("committees","committee_id=$_REQUEST[committee_id]");
    if ($result!=1) $success= "Error deleting: $result";
    $section='committeeview';
    }
}

if(isset($_REQUEST['delete'])){
    $result=mysqlDelete("committee_members","committee_id=$_REQUEST[committee_id] AND user_id=$_REQUEST[id]");
    if ($result!=1) $success= "Error deleting: $result";
    $section='members';
}

if(isset($_REQUEST['makechair'])){
    if(isset($_REQUEST['id'])) if(isset($_REQUEST['committee_id']))   {
        //remove current chair
        $result=mysqlFetchRow('committee_members',"committee_id=$_REQUEST[committee_id] AND chair=1");
        if(is_array($result)) {
            $result2=mysqlUpdate('committee_members',array('chair'=>0),"committee_member_id=$result[committee_member_id]");
        }
        else $success.='New chair - no existing one to remove. ';
        $result=mysqlUpdate('committee_members',array('chair'=>1),"user_id=$_REQUEST[id]");
        if($result !=1) $success.="Error updating new chair: $result ";
        else $success.='Updated. ';
    }
    $section='members';
    
}

if(isset($_REQUEST['addminutes'])){
    //$success.="Adding... ";
    if(isset($_FILES['file'])) if($_FILES['file']['name'] != ""){
    
        if($_FILES["file"]["error"] > 0) {
            $success.="Error uploading file-Return Code: " . $_FILES["file"]["error"] ;
            $filename="";
        }
        else {
            //create datestamp name
            $pathinfo = pathinfo($_FILES['file']['name']);
           $extension = $pathinfo['extension'];
            $filename=strtolower(unique_filename($extension));
            if(!move_uploaded_file($_FILES["file"]["tmp_name"], $minutes_file_path . $filename)) $success.="Error uploading file"; 
            else $success.="Uploaded file. ";
            $tmp_date = explode("/", $_REQUEST['meeting_date']);
            $meeting_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);
            
            
            $result=mysqlInsert("minutes",array('null',$_REQUEST['committee_id'],$meeting_date,"$filename",''));
            if($result != 1) $success.="Error inserting record: $result";
        }
    }
    else $success.="No file specified";
}

if(isset($_REQUEST['delminute'])){
    $section='minutes'; 
    if(isset($_REQUEST['minute_id'] )){
        $minute=mysqlFetchRow('minutes',"minute_id=$_REQUEST[minute_id]");
        if(is_array($minute)){
            $result=unlink($minutes_file_path . $minute['filename']);
            if(!$result) $success.='Error unlinking from file ' . $minutes_file_path . $minute['filename'];
            else {
                $result=mysqlDelete('minutes',"minute_id=$_REQUEST[minute_id]")     ;
                if($result != 1) $success.="Error removing entry: $result";
                else $success.='Deleted. ';
            }
        }
        else $success.='Minute entry not found. ';
    }
    else $success.='Minute ID not set .'   ;
}



if(isset($section)) {
    switch($section) {
    
    case 'members':
    //choose committee members - list with delete function
    //choice from dropdown rebuilds page automatically
    
        $userlist="";
        $output="";
        if (isset($_REQUEST['committee_id'])){    
        $members = mysqlFetchRows("committees left join committee_members on committees.committee_id=committee_members.committee_id left join users on committee_members.user_id=users.user_id","committees.committee_id=$_REQUEST[committee_id] AND users.user_id IS NOT NULL order by users.last_name,users.first_name");
        $output="";
        $idlist=array();

        if(is_array($members)){
            foreach($members as $member){
                if($member['chair']) $chairflag="<input  type='checkbox' name='chair' disabled checked />"; else $chairflag='';
                $output.="<tr><td bgcolor='#E09731'><a style='color:white' href='committees.php?delete&id=$member[user_id]&committee_id=$_REQUEST[committee_id]'>Delete</a></td>
                        <td bgcolor='#D7D7D9'>$member[last_name], $member[first_name]</td><td bgcolor='#D7D7D9'>$chairflag</td>
                        <td bgcolor='#E09731'><a style='color:white' href='committees.php?makechair&id=$member[user_id]&committee_id=$_REQUEST[committee_id]'>Make Chair</a></td></tr>";
                $idlist[]=$member['user_id'];
            }//foreach
        }//if isarray
        $users=mysqlFetchRows("users","1 order by last_name,first_name");

        if(is_array($users)){
            foreach($users as $user){
                if(!(in_array($user['user_id'],$idlist))) $userlist .= "<option value='$user[user_id]'>$user[last_name], $user[first_name]</option>";
            }
        }

        } //if iset committee_id
        $committees=mysqlFetchRows('committees','1 order by name');
        if(is_array($committees)){
        $commlist="";
        foreach($committees as $committee){
            $sel='';
            if (isset($_REQUEST['committee_id'])) if ($committee['committee_id']==$_REQUEST['committee_id']) $sel='selected'; 
            $commlist.="<option value=$committee[committee_id] $sel>$committee[name]</option>";
        }
        }

        $hasharray=array(    'commlist'=>$commlist,
                            'output'=>$output,
                            'userlist'=>$userlist,
                            'success'=>$success
                            );
        $filename = 'templates/template-comm_members.html';
        $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
        echo $parsed_html_file;
    
    break;
    
    case 'committeeadd':
        $committees=mysqlFetchRows('committees','1 order by name');
        if(is_array($committees)){
            $commlist="";
            foreach($committees as $committee){
                $sel='';
                if (isset($_REQUEST['committee_id'])) if ($committee['committee_id']==$_REQUEST['committee_id']) $sel='selected'; 
                $commlist.="<option value=$committee[committee_id] $sel>$committee[name]</option>";
            }
            $hasharray=array(    'commlist'=>$commlist,
                                'success'=>$success
                                );
            $filename = 'templates/template-comm_add.html';
            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;
        }
    
    break;
    
    case 'committeeupdate':
        if(isset($_REQUEST['committee_id'])){
            $comm=mysqlFetchRow('committees',"committee_id=$_REQUEST[committee_id]");
            if(is_array($comm)){
            $hasharray=array(    'committee_id'=>$_REQUEST['committee_id'],
                                'name'=>$comm['name'],
                                'success'=>$success
                                );
            $filename = 'templates/template-comm_update.html';
            
            }
            else {
                $hasharray = array('title'=>"Committees");
                $filename = 'includes/error-no_records.html';
            }
            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;
        }
    
    break;
    
    case 'committeeview':
        $values = mysqlFetchRows("committees","1 order by name");
            $output = "";
            if(is_array($values)) {
                foreach($values as $index) {
                    $output .= "
                        <tr>
                            <td bgcolor='#E09731'><a style='color:white' href='committees.php?section=committeeupdate&committee_id=$index[committee_id]'>Update</a></td>
                            <td bgcolor='#D7D7D9'>$index[name]</td>
                            <td bgcolor='FFFFFF'><button onClick='window.location=\"/committees.php?section=minutes&committee_id=$index[committee_id]\"'>Minutes</button>
                        </tr>";
                }
                $hasharray = array('success'=>$success, 'output'=>$output);
                $filename = 'templates/template-comm_view.html';
            }
            else {
                $hasharray = array('title'=>"Committees");
                $filename = 'includes/error-no_records.html';
            }
            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;
    break;
    
    case 'minutes':
        if(isset($_REQUEST['committee_id'])) {
            $comm=mysqlFetchRow('committees',"committee_id=$_REQUEST[committee_id]");
            if(is_array($comm)){
                $minutes=mysqlFetchRows('minutes',"committee_id=$_REQUEST[committee_id]");
                $output='';
                if(is_array($minutes)) foreach($minutes as $minute){
                    $date=date($iso8601_day,$minute['date']);
                    $by=mysqlFetchRow('users',"user_id=$minute[uploaded_by]");         
                    if(!is_array($by)) $name='Unknown';
                    else $name=$by['first_name'] . $by['last_name'];
                    $output.="<tr><td>$date</td><td>$name</td><td><button onClick='window.location=\"/committees.php?delminute&minute_id=$minute[minute_id]&committee_id=$_REQUEST[committee_id]\"'>Delete</button></td></tr>\n";
                }
                $committee=mysqlFetchRow('committees',"committee_id=$_REQUEST[committee_id]");
                $hasharray = array('success'=>$success, 'output'=>$output,'committee_id'=>$_REQUEST['committee_id'],'name'=>$committee['name']);
                $filename = 'templates/template-comm_minutes.html';
            }
            else {
                $success.='Committee not found';
                $hasharray = array('title'=>"Committees");
                $filename = 'templates/error-no_records.html';
            }
            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;
        }
    break;
    }
}
?>
