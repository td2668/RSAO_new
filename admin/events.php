<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");

include("html/header.html");

$success="";

if(isset($_REQUEST['add']) || isset($_REQUEST['update'])) {
	if($_REQUEST['expiry_date'] == "") {
		$tmp_date = explode("/", $_REQUEST['start_date']);
		$expiry_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0]+1,$tmp_date[2]);
	}
	else {
		$tmp_date = explode("/", $_REQUEST['expiry_date']);
		$expiry_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);
	}

	$tmp_date = explode("/", $_REQUEST['post_date']);
	$post_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);
	
	$tmp_date = explode("/", $_REQUEST['start_date']);
	$start_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);	
	
	if($_REQUEST['start_time'] == "hh:mm" || $_REQUEST['start_time'] == "") {
		$start_time = 0;
	}
	else {
        $start_ampm=$_REQUEST['start_ampm'];
		$tmp_time = explode(":", $_REQUEST['start_time']);
		if($tmp_time[0] == 12)
			if($start_ampm == 12) $start_ampm = 0;
			else $start_ampm = 12;
		$tmp_time[0] = $tmp_time[0]+$start_ampm;
		$start_time = mktime($tmp_time[0],$tmp_time[1]);
	}
	
	if($_REQUEST['end_time'] == "hh:mm" || $_REQUEST['end_time'] == "") {
		$end_time = 0;
	}
	else {
        $end_ampm=$_REQUEST['end_ampm'];
		$tmp_time = explode(":", $_REQUEST['end_time']);
		if($tmp_time[0] == 12)
			if($end_ampm == 12) $end_ampm = 0;
			else $end_ampm = 12;
		$tmp_time[0] = $tmp_time[0]+$end_ampm;
		$end_time = mktime($tmp_time[0],$tmp_time[1]);
	}
	
	if(!isset($_REQUEST['approved'])) $approved = "no"; else $approved='yes';
	if(!isset($_REQUEST['reminders_sent'])) $reminders_sent = "no"; else $reminders_sent='yes';
	if(!isset($_REQUEST['internal'])) $internal = "no"; else $internal='yes';
}

if(isset($_REQUEST['add'])) {
	$values = array('null', $_REQUEST['title'], $post_date, $start_date, $start_time, $end_time, $expiry_date, $_REQUEST['location'], $_REQUEST['address'], 
					$_REQUEST['contact'], $_REQUEST['synopsis'], $_REQUEST['description'], $approved, $reminders_sent, $internal);
	if(mysqlInsert("events", $values)) $success=" <strong>Complete</strong>";
    //Insert Into Calendar
    //Open the calendar
    $connection = mysql_connect("localhost", "cal", "rilinc") or die(mysql_error());
    mysql_select_db("calendar",$connection) or die(mysql_error());
    $startday=date("Y-m-d",$start_date);
    $start_time=date('H:i:s',$start_time);
    $end_time=date('H:i:s',$end_time);
    $startstamp=$startday.' '.$start_time;
    $duration=$startday.' '.$end_time;
    $desc=$_REQUEST['location'].'\n'.$_REQUEST['contact'].'\n\n'.$_REQUEST['synopsis'];
    //$modstamp = date("Y-m-d H:i:s");
    $values=array(  'null',     //id
                    'ORS',      //username
                    1,          //user_id
                    'null',     //mod_id
                    'null',     //mod_username
                    'null',     //mod_stamp
                    $startstamp,   //stamp
                    $duration,      //duration
                    0,
                    $_REQUEST['title'],
                    $desc,
                    '',
                    '',
                    '0000-00-00',
                    0,          //repeat_num
                    0,0,0,0,
                    5,          //event type
                    0,0);
      $result=mysqlInsert('cal_events',$values);
      if($result!=1) $success.=" Error inserting calendar entry: $result";              
                    
    
    //re-establish original database connection
    include("includes/config.inc.php");
}
else if (isset($_REQUEST['update'])) {
	$values = array('title'=>$_REQUEST['title'], 'post_date'=>$post_date, 'start_date'=>$start_date, 'start_time'=>$start_time, 'end_time'=>$end_time, 
					'expiry_date'=>$expiry_date, 'end_time'=>$end_time, 'location'=>$_REQUEST['location'], 'address'=>$_REQUEST['address'], 'contact'=>$_REQUEST['contact'],
					'synopsis'=>$_REQUEST['synopsis'], 'description'=>$_REQUEST['description'], 'approved'=>$approved, 'reminders_sent'=>$reminders_sent, 'internal'=>$internal); 
	if(mysqlUpdate("events", $values, "event_id=$id")) $success.=" <strong>Event Updated</strong>";
}
else if (isset($_REQUEST['delete'])) {
	if(mysqlDelete("events", "event_id=$_REQUEST[id]")) $success=" <strong>Event Deleted</strong>";
}

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "view":  
			$values = mysqlFetchRows("events", "1 order by start_date desc");
			$output = "";
			if(is_array($values)) {
				foreach($values as $index) {
					if ($index['start_date'] < $todays_date) {$index['synopsis']="(hiddden)";}
					$index['start_date'] = date("j/n/y", $index['start_date']);
					
					$output .= "
						<tr>
							<td bgcolor='#E09731'><a style='color:white' href='events.php?section=update&id=$index[event_id]'>Update</a></td>
							<td bgcolor='#D7D7D9'>$index[title]</td>
							<td bgcolor='#D7D7D9'>$index[start_date]</td>
							<td bgcolor='#D7D7D9'>$index[location]</td>
							<td bgcolor='#D7D7D9'>$index[contact]</td>
							<td bgcolor='#D7D7D9'>$index[synopsis]</td>
							<td bgcolor='#D7D7D9'>$index[approved]</td>
							<td bgcolor='#D7D7D9'>$index[internal]</td>
						</tr>";
				}
				$hasharray = array('success'=>$success, 'output'=>$output);
				$filename = 'templates/template-events_view.html';
			}
			else {
				$hasharray = array('title'=>"Events");
				$filename = 'includes/error-no_records.html';
			}
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
		case "add": 
			$static_post_date = date("j/n/Y", mktime(0,0,0));
			
			$hasharray = array('success'=>$success, 'post_date'=>$static_post_date);
			$filename = 'templates/template-events_add.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
		case "update":
			$picture_button = (mysqlFetchRow("pictures_associated", "object_id=$_REQUEST[id] AND table_name='events'"))?
				"<br><br><button onClick=\"window.location='pictures-associate.php?section=update&id=$_REQUEST[id]&table_name=events'\">View Associated Images</button>":"";
			$values = mysqlFetchRow("events", "event_id=$_REQUEST[id]");
			if (date("a", $values['start_time']) == "pm")$start_ampm_a = "selected";
			else $start_ampm_a = "";
			if (date("a", $values['end_time']) == "pm")$end_ampm_a = "selected";
			else $end_ampm_a = "";
			$values['post_date'] = date("j/n/Y", $values['post_date']);
			$values['start_date'] = date("j/n/Y", $values['start_date']);
			if($values['start_time'] == 0) $values['start_time'] = "";
			else $values['start_time'] = date("h:i", $values['start_time']);
			if($values['end_time'] == 0) $values['end_time'] = "";
			else $values['end_time'] = date("h:i", $values['end_time']);
			$values['expiry_date'] = date("j/n/Y", $values['expiry_date']);
			if($values['approved'] == "yes")$approved_a = "checked";
			else $approved_a = "";
			if($values['reminders_sent'] == "yes")$reminders_sent_a = "checked";
			else $reminders_sent_a = "";
			if($values['internal'] == "yes")$internal_a = "checked";
			else $internal_a = "";
			$hasharray = array('id'=>$values['event_id'], 'title'=>$values['title'], 'post_date'=>$values['post_date'], 'start_date'=>$values['start_date'], 
							   'start_time'=>$values['start_time'], 'start_ampm_a'=>$start_ampm_a, 'end_time'=>$values['end_time'], 'end_ampm_a'=>$end_ampm_a, 
							   'expiry_date'=>$values['expiry_date'], 'location'=>$values['location'], 'address'=>$values['address'], 'contact'=>$values['contact'], 
							   'synopsis'=>$values['synopsis'], 'description'=>$values['description'], 'approved_a'=>$approved_a, 'reminders_sent_a'=>$reminders_sent_a, 
							   'internal_a'=>$internal_a, 'picture_button'=>$picture_button);
			$filename = 'templates/template-events_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            break;
	} 
}
//-- Footer File
include("templates/template-footer.html");
?>