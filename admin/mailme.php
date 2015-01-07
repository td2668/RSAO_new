<?php

include_once("includes/config.inc.php");
include_once("includes/functions-required.php");
include_once("includes/mail-functions.php");
require_once("includes/Michelf/MarkdownInterface.php");
require_once("includes/Michelf/Markdown.php");

//echo("<BR><BR>". get_include_path());
//$path='/Users/tdavis/Sites/webrepo/research/tags/release-4.0/admin/includes';
$path='/home/scholviu/public_html/admin/includes';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

//echo("<BR><BR>". get_include_path());

use \Michelf\Markdown;

define("NEWS", 0);
define("DEADLINE", 1);
define("SRD", 2);
define("OTHER", 3);

global $db;

$hdr=loadPage("header",'Header');

$menuitems=array();
$menuitems[]=array('title'=>'Add','url'=>'mailme.php?section=add');
$menuitems[]=array('title'=>'List','url'=>'mailme.php?section=view');
$hdr->AddRows("list",$menuitems);

$tmpl=loadPage("mailme", 'Mail');


set_time_limit(120);
$success="";
//error_reporting(E_ALL);
//Prep all variables after a submit
if ( isset($_REQUEST['update']) || isset($_REQUEST['usend']) || isset($_REQUEST['utestsend'])) {
    $topics_research = (isset($_REQUEST['topics_research']))?$topics_research = implode(",", $_REQUEST['topics_research']): "";
    $divisions = (isset($_REQUEST['divisions']))?$divisions = implode(",", $_REQUEST['divisions']): "";
    $userlist= (isset($_REQUEST['single_user']))?$userlist= implode(",",$_REQUEST['single_user']):"";
    if(($_REQUEST['s_date'] == "yy-mm-dd") || $_REQUEST['s_date']=="") $s_date=mktime();
    else {
        $tmp_date = explode("-", $_REQUEST['s_date']);
        $s_date = mktime(0,0,0,$tmp_date[2],$tmp_date[1],$tmp_date[0]);
    }

    $ft_faculty=(isset($_REQUEST['ft_faculty'])) ? 1 : 0;
    $pt_faculty=(isset($_REQUEST['pt_faculty'])) ? 1 : 0;
    $management=(isset($_REQUEST['management'])) ? 1 : 0;
    $support=(isset($_REQUEST['support'])) ? 1 : 0;
    $outside=(isset($_REQUEST['outside'])) ? 1 : 0;
    $chairs=(isset($_REQUEST['chairs'])) ? 1 : 0;
    $deans=(isset($_REQUEST['deans'])) ? 1 : 0;
 
    if($_REQUEST['from_email']=='') $_REQUEST['from_email']='research@viu.ca';
    if($_REQUEST['from_name']=='') $_REQUEST['from_name']='RSAO';
    //process file here
    $filename='';
    print_r($_FILES);
    if(isset($_FILES['file'])) if($_FILES['file']['name'] != ""){
        if($_FILES["file"]["error"] > 0) {
            $success.="Error uploading file-Return Code: " . $_FILES["file"]["error"] ;
        }
        else {
            move_uploaded_file($_FILES["file"]["tmp_name"], $configInfo['mail_file_path'] . $_FILES["file"]["name"]);
            $filename=$_FILES["file"]["name"];
            $success.="Uploaded file. ";
        }
    }
    $override=(isset($_REQUEST['override'])) ? true : false;


    if(isset($_REQUEST['type'])) {
        $type = $_REQUEST['type'];
        if($type == 'news') {
            $type = NEWS;
        } elseif($type == 'deadline') {
            $type = DEADLINE;
        } else {
            $type = OTHER;
        }
    }
}

// Add new item or Add & Send
if (isset($_REQUEST['add']))  {
    
    $result = $db->Execute("INSERT INTO mail VALUES()");
    if(is_array($result)) $success.=" <strong>Complete</strong>";
    else echo ("Error Updating: $result");

    $_REQUEST['id']=$db->Insert_ID();
}

//Update existing or Update and Send
if (isset($_REQUEST['update']) || isset($_REQUEST['usend']) || isset($_REQUEST['utestsend'])){
    if(isset($_REQUEST['sent']) || isset($_REQUEST['usend'])) $sent=1; else $sent=0;

    if(!isset($filename)) $filename=$_REQUEST['old_filename'];


    $result=$db->Execute("UPDATE mail SET
    			 subject='".mysql_real_escape_string($_REQUEST['subject'])."',
                 body='".mysql_real_escape_string($_REQUEST['body'])."',
                 s_date='$s_date',
                 topics_research='$topics_research',
                 divisions='$divisions',
                 sent='$sent',
                 ft_faculty=$ft_faculty,
                 pt_faculty=$pt_faculty,
                 management=$management,
                 support=$support,
                 outside=$outside,
                 chairs=$chairs,
                 deans=$deans,
                 single_user='$userlist',
                 filename='".mysql_real_escape_string($filename)."',
                 prepend='$_REQUEST[prepend]',
                 from_email='$_REQUEST[from_email]',
                 from_name='".mysql_real_escape_string($_REQUEST['from_name'])."',
                 mail_type='$type',
                 override='$override'
                 WHERE mail_id=$_REQUEST[id]");
    if($result) $success.=" <strong>Updated</strong>";
    else $success.= "Error Updating: ". $db->ErrorMsg() . ' ';
}

$plug_msg='@firstname@:<br><br>

';
$msg_subject='';
if(isset($_REQUEST['msg_flag'])) if($_REQUEST['msg_flag']=='1'){
    $msg=$db->GetRow("SELECT * FROM messages WHERE message_id=$special_msg");
    if(count($msg)>0){
        $plug_msg=$msg['message'];
        $msg_subject=$msg['name'];
    }
}

//------------------------Send Mail--------------------------

if (isset($_REQUEST['usend']) || isset($_REQUEST['utestsend'])) {

echo ("<pre>".$_REQUEST['body']."</pre>");



$my_html = Markdown::defaultTransform($_REQUEST['body']);

echo ("<pre>".$my_html."</pre>");

        $mailitems = array('subject'    => $_REQUEST['subject'],
                           'body'       => stripslashes($_REQUEST['body']),
                           'from_email' => $_REQUEST['from_email'],
                           'from_name'  => $_REQUEST['from_name']
        );



    $users = recipientBuilder(array(
                                   'ft_faculty'      => $ft_faculty,
                                   'pt_faculty'      => $pt_faculty,
                                   'management'      => $management,
                                   'support'         => $support,
                                   'outside'         => $outside,
                                   'chairs'          => $chairs,
                                   'deans'           => $deans,
                                   'topics_research' => $topics_research,
                                   'divisions'       => $divisions,
                                   'userlist'        => $userlist
                              ), $override, $type);


	//var_dump($users);

	$total = count($users);
	//var_dump('total', $total);
	//var_dump('users', $users);

	if (isset($_REQUEST['utestsend'])) {
	    $mailitems['testmail'] = true;
	    echo("<b>Admin Mailout only</b><br><br>");
	} else {
	    $mailitems['testmail'] = false;
	}
	if($_REQUEST['prepend']!='') {
	    $mailitems['subject']=$_REQUEST['prepend'] . ' ' . $mailitems['subject'];
	}

	if (isset($filename)) {
	    if ($filename != "") {
	        $mailitems["filename"] = $filename;
	    }
	}

	foreach ($users as $key => $user) {
	    //print_r($user['email'] . ' : ' . $user['first_name'] . " " . $user['last_name'] . "<br/>");
	    mailout($user, null, $mailitems);
	}

	//array_walk($users,'mailout',$mailitems);

	if ($total >= 1) {
	    $success .= " <strong>Mail sent to $total users</strong>";
	}
	else {
	    $success .= " <strong>Mail sent to no one at all</strong>";
	}

	//Add new mail_history item
	$groups = $people = $topics = '';
	if ($ft_faculty) {
	    $groups .= "Full-Time Faculty; ";
	}
	if ($pt_faculty) {
	    $groups .= "Part-Time Faculty; ";
	}
	if ($management) {
	    $groups .= "Management; ";
	}
	if ($support) {
	    $groups .= "Support Staff; ";
	}
	if ($outside) {
	    $groups .= "Outside; ";
	}
	if ($deans) {
	    $groups .= "Deans; ";
	}
	if ($chairs) {
	    $groups .= "Chairs; ";
	}
	if ($tss) {
	    $groups .= "TSS Faculty; ";
	}
	if ($srd) {
	    $groups .= "Student Research Day Participants;";
	}
	if ($strd) {
	    $groups .= "MM Student Research Day Participants;";
	}
	if ($abstract) {
	    $groups .= "(abstract missing only);";
	}

	if (isset($_REQUEST['single_user'])) {
	    foreach ($_REQUEST['single_user'] as $suser) {
	        $username = $db->GetRow("SELECT * FROM users WHERE user_id=$suser");
	        $people .= $username['last_name'] . ', ' . $username['first_name'] . '; ';
	    }
	}
	if (isset($_REQUEST['topics_research'])) {
	    foreach ($_REQUEST['topics_research'] as $topic) {
	        $tp = $db->GetRow("SELECT name FROM topics_research WHERE topic_id=$topic");
	        $topics .= $tp['name'] . '; ';
	    }
	}

	$result = $db->Execute("INSERT INTO mail_history SET 
							mail_id=$_REQUEST[id],
	                        groups='$groups',
	                        people='$people',
	                        topics='$topics',
	                        count='$total',
	                        date='".mktime()."'"
	                                      );
	if (!$result) {
	    $success .= " Did not write history file: $result";
	}

	if ($logfile = fopen("{$configInfo['file_root']}mail_log.txt", "a+")) {
	    $date = date("Y-n-j", $todays_date);
	    fwrite($logfile, "-----------------\nDate: $date\n\n");
	    fwrite($logfile, "Immediate Mail: $mailitems[subject]\n");
	    if ($total >= 1) {
	        fwrite($logfile, "Mail sent to $total users\n\n");
	    }
	    else {
	        fwrite($logfile, "Mail sent to no one at all\n\n");
	    }
	    fclose($logfile);
	} else {
	    echo("Mail Log Is Not Writeable at " . $configInfo['file_root'] . "mail_log.txt<br>");
	}
}
//if($notsent>=1) $success.=" (not sent to $notsent off-campus)";

//replace with the following after testing

//-----------------------------------------------------------

if (isset($_REQUEST['delete'])){
    if($db->Execute("DELETE FROM mail WHERE mail_id=$_REQUEST[id]")) $success.=" <strong>Mail Deleted</strong>";
    $db->Execute("DELETE FROM mail_history WHERE mail_id=$_REQUEST[id]");
}
$section = $_REQUEST['section'];
if (isset($_REQUEST['section'])) {
    if(!isset($success)) $success="";
    switch($_REQUEST['section']){
        case "view":
        	$tmpl->setAttribute("view","visibility","visible");
            //Show mail items using fields 'subject' and 'sent_date'
            $values = $db->GetAll("SELECT * FROM mail WHERE 1 order by s_date desc");
            $output = "";
            $out=array();
            if(count($values)>0) {
                foreach($values as $index) {
                    if($index['s_date'] == 0) $index['s_date']='Manual';
                        else $index['s_date'] = date("Y-m-d", $index['s_date']);
                    if($index['sent']==1) $index['sent']='#FF3333'; else $index['sent']='#D7D7D9';
                    $linkitem="";
                    if(($index['assoc_id'])!=0) {
                        if($index['type'] == "opportunity") {
                            $item=$db->GetRow("SELECT * FROM opportunities WHERE opportunity_id=$index[assoc_id]");
                            if(count($item)>0) $linkitem="Opp: ".$item['title'];
                        }
                        else if ($index['type'] == "deadline-early") {
                            $item=$db->GetRow("SELECT * FROM deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id WHERE date_id=$index[assoc_id]");
                            if(count($item)>0) $linkitem="DL-Early: ".$item['title']." (".date("j/n/y", $item['d_date']).")";
                        }
                        else if ($index['type'] == "deadline-close") {
                            $item=$db->GetRow("SELECT * FROM deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id WHERE date_id=$index[assoc_id]");
                            if(count($item)>0) $linkitem="DL-Late: ".$item['title']." (".date("j/n/y", $item['d_date']).")";
                        }
                    }
                    //if($index['send_all']) $index['subject'] .= " (SEND ALL)";
                    //if($index['send_admin']) $index['subject'] .= " (ADMIN ONLY)";
                    
                    $index['linkitem']=$linkitem;
                    $index['rowtype']='main';
                    $out[]=$index;
                    
                    
                    /*
$output .= "
                        <tr>
                            <td bgcolor='#E09731'><a style='color:white' href='mailme.php?section=update&id=$index[mail_id]'>Update</a></td>
                            <td width='25' bgcolor='#E09731'><a style='color:white' href='mailme.php?delete&id=$index[mail_id]&section=view'>Delete</a></td>
                            <td bgcolor='#D7D7D9'>$index[subject]</td>
                            <td bgcolor='#D7D7D9'>$index[s_date]</td>
                            <td bgcolor='$index[sent]'>&nbsp;</td>
                            <td bgcolor='#D7D7D9'>$linkitem</td>
                            </tr>";
*/


                    $mailitems=$db->GetAll("SELECT * FROM mail_history WHERE mail_id=$index[mail_id]");

                    if(count($mailitems)>0) {
                        foreach($mailitems as $mailitem){
                            if($mailitem['date'] == 0) $index['date1']='Manual';
                            else $index['date1'] = date($iso8601, $mailitem['date']);
                            
                            if($mailitem['groups']<>"") {$index['type']='Groups:'; $index['item']=$mailitem['groups'];}
                            elseif($mailitem['people']<>"") {$index['type']='People:'; $index['item']=$mailitem['people'];}
                            elseif($mailitem['topics']<>"") {$index['type']='Topics:'; $index['item']=$mailitem['topics'];}
                            else {$index['type']=''; $index['item']='';}
                            $index['count']=$mailitem['count'];
                            $index['rowtype']='sub';
                            $out[]=$index;
                        }
                    }
                }
                $tmpl->AddRows("viewlist",$out);
                //$hasharray = array('success'=>$success, 'output'=>$output);
                //$filename = 'templates/template-mail_view.html';
            }
            else {
                //$hasharray = array('title'=>"Mail");
                //$filename = 'includes/error-no_records.html';
            }
            //$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            //echo $parsed_html_file;
        break;

        

        case "update":
            $values = $db->GetRow("SELECT * FROM mail WHERE mail_id=$_REQUEST[id]");
            //-- Selects the Topics
            $objects = explode(",", $values['topics_research']);
            $topics = $db->Execute("SELECT name,topic_id FROM topics_research WHERE 1 ORDER BY name");
            $values['topic_options'] = $topics->GetMenu2("topics_research",$objects,true,true,8); 


            $objects = explode(",", $values['divisions']);
            $divisions = $db->Execute("SELECT name,division_id FROM divisions WHERE 1 ORDER BY name");
            $values['division_options'] = $divisions->GetMenu2("divisions",$objects,true,true,8);
			
            /*
if(is_array($objects) && $objects[0] != "") foreach($objects as $object) $ids[] = $object['topic_id'];
            if(is_array($divisions)) {
                foreach($divisions as $division) {
                    if(in_array($division['division_id'], $objects)) $division_options .= "<option value='$division[division_id]' selected>$division[name]</option>";
                    else $division_options .= "<option value='$division[division_id]'>$division[name]</option>";

                }
            }
*/

            $objects = explode(",", $values['single_user']);
            $users=$db->Execute("SELECT CONCAT(last_name,', ',first_name) as name, user_id FROM users WHERE 1 order by last_name,first_name");
            $values['single_user_list']=$users->GetMenu2("single_user",$objects,true,true,8);
            
            
            $values['s_date']= ($values['s_date'] != "") ? date("Y-m-d", $values['s_date']) : '';
            $values['ft_faculty'] = ($values['ft_faculty'] ==1) ? 'checked' : '';     
            $values['pt_faculty'] = ($values['pt_faculty'] ==1) ? 'checked' : '';
            $values['management'] = ($values['management'] ==1) ? 'checked' : '';
            $values['support'] = ($values['support'] ==1) ? 'checked' : '';
            $values['outside'] = ($values['outside'] ==1) ? 'checked' : '';
            $values['chairs'] = ($values['chairs'] ==1) ? 'checked' : '';
            $values['deans'] = ($values['deans'] ==1) ? 'checked' : '';
            $values['sent'] = ($values['sent'] == 1) ? "checked" : '';
            $values['override'] = ($values['override'] == 1) ? "checked" : "";

            
            $values['type_news'] = ($values['mail_type'] == NEWS) ? 'checked' : '';

            $values['type_deadline'] = ($values['mail_type'] == DEADLINE) ? 'checked' : '';

            $values['type_other'] = ($values['mail_type'] == OTHER) ? 'checked' : '';

			$values['body'] = stripslashes($values['body']);
            $values['id']= $values['mail_id'];

            $tmpl->setAttribute("update","visibility","visible");
            $tmpl->addVars('update',$values);

        break;

        case "lists":
            $output="";
             $topics = mysqlFetchRows("topics_research ","1 order by name");
             $divisions=mysqlFetchRows("divisions",'1 order by name');
             foreach($topics as $topic) {
                 $output.= "<tr><td><b>$topic[name]</b></td></tr>";
                 $total=0;
                 foreach($divisions as $division){

                     $sql=" SELECT * FROM users as u
                            LEFT JOIN departments as d on (u.department_id=d.department_id)
                            LEFT JOIN divisions as di on (d.division_id=di.division_id)
                            LEFT JOIN user_topics_filter as utf on (u.user_id=utf.user_id)
                            LEFT JOIN users_disabled as ud on (u.user_id=ud.user_id)
                            WHERE
                            utf.topic_id=$topic[topic_id] AND
                            u.mail_deadlines=1 AND
                            di.division_id=$division[division_id] AND
                            ISNULL(ud.user_id); ";
                     $result=mysql_num_rows(mysql_query($sql));
                     $total+=$result;
                     $output.="<tr><td>$division[name]</td><td>$result</td></tr>\n";
                 }
                 $output.="<tr><td>TOTAL:</td><td>$total</td></tr><tr><td colspan=2><hr></td></tr>\n";


             }

            $hasharray = array('success'=>$success, 'output'=>$output);
            $filename = 'templates/template-mail_lists.html';

            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;

        break;
    } //switch
}
$hdr->AddVar('header','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');

