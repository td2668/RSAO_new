#!/usr/bin/php

<?php
//#!/usr/local/bin/php
//#!/cygdrive/c/php/php.exe

//$filepath = "/opt/lampp/htdocs/";

//Set up for the various servers
$configInfos = array();

$configInfos["admin.localhost"]["server_name"] = 'localhost';
$configInfos["admin.localhost"]["host"] = 'localhost';
$configInfos["admin.localhost"]["user"] = 'ors';
$configInfos["admin.localhost"]["pass"] = 'rilinc';
$configInfos["admin.localhost"]["dbdriver"] = 'mysql';
$configInfos["admin.localhost"]["dbname"] = 'research';
$configInfos["admin.localhost"]["peardir"] = '/vagrant/vendor/conservatory/research-pear/';
$configInfos["admin.localhost"]["debug"] = true	;
$configInfos["admin.localhost"]["authmethod"] = 'usertable';
$configInfos["admin.localhost"]["url_root"] = 'http://local.orsadmin';
$configInfos["admin.localhost"]["upload_root"] = '/Users/tdavis/Sites/webrepo/research/tags/release-4.0/admin/documents/';
$configInfos["admin.localhost"]["upload_webroot"] = '/documents/';
$configInfos["admin.localhost"]["file_root"] = '/Users/tdavis/Sites/webrepo/research/tags/release-4.0/admin/';
$configInfos["admin.localhost"]["admin"] = array('tdavis','cnakamoto');
$configInfos["admin.localhost"]["irgf_docs"] = "/admin/documents/shared/irgf";
$configInfos["admin.localhost"]["logpath"] = "/Users/tdavis/Sites/webrepo/research/tags/release-4.0/admin/";
$configInfos["admin.localhost"]["picture_path"] = "/Users/tdavis/Sites/webrepo/tags/release-4.0/admin/documents/pictures/";
$configInfos["admin.localhost"]["mail_file_path"] = '/Users/tdavis/Sites/webrepo/tags/release-4.0/admin/documents/mailfiles/';
$configInfos["admin.localhost"]["email_send_now"] = false;
$configInfos["admin.localhost"]["debug_email"] = false;
$configInfos["admin.localhost"]["email_db_options"] =
    array(
        'type'        => 'db',
        'dsn'         => 'mysql://ors:rilinc@localhost/research',
        'mail_table'  => 'mail_queue',
    );
$configInfos["admin.localhost"]['email_options'] = array(
        'driver'   => 'smtp',
        'host'     => 'localhost',
        'port'     => 25,
        'auth'     => false,
        'username' => '',
        'password' => '',
    );




$configInfo = $configInfos["admin.localhost"];
//$configInfo = $configInfos["localhost"];

error_reporting(E_ALL);
set_time_limit(120);

//$filepath = "/var/www/orsadmin-prep_htdocs/";
//$mail_file_path="/var/www/orsadmin-prep_htdocs/admin/mail_upload/";
//$hr_extract_path="/var/www/secure-store/hr_extract/";
$hr_extract_path="/Users/trevor/Documents/Sites/extracts";

//include("{$filepath}admin/includes/config.inc.php"); 
//Due to changes - replaced by following
//$host = "localhost";
//$user = "ors";
//$dbpassword = "rilinc";
//$database = "research";

#The server name for email based links. Not the ors-admin server.
//$server_name = "research.mtroyal.ca";
//$connection = mysql_connect($host, $user, $dbpassword) or die(mysql_error());
//mysql_select_db($database,$connection) or die(mysql_error());

//Do the second connection using adodb5
require_once('includes/adodb5/adodb.inc.php');
// load the required pear libraries
/*if ( (include_once($configInfo["peardir"].'pat/patTemplate.php')) == false ) {
    require_once('pat/patTemplate.php');
}
if ( (include_once($configInfo["peardir"].'pat/patErrorManager.php')) == false ) {
    require_once('pat/patErrorManager.php');
}*/

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$db = ADONewConnection($configInfo["dbdriver"]); // eg. 'mysql' or 'postgres'
if ($configInfo['debug']) $db->debug = true; //MRCDEBUG;
else $db->debug=false;
$db->Connect(
    $configInfo["host"],
    $configInfo["user"],
    $configInfo["pass"],
    $configInfo["dbname"]
);

$todays_date = mktime();
$tomorrow = strtotime("+1 day");
$deadline_change_email='trevor.davis@viu.ca';
$hr_extract_email="trevor.davis@viu.ca";
$logmail_recipient='trevor.davis@viu.ca';

//standard file locations
$picture_path = "../pictures/";
$public_picture_path = "pictures/";
$docs_path = "../researchdocs/";


//Standard date format.
$iso8601 = "Y-m-d G:i";
$iso8601_day = "Y-m-d";

include_once("{$configInfo['file_root']}admin/includes/functions-required.php");
include_once("{$configInfo['file_root']}admin/includes/mail-functions.php");

//open log file
if (!$logfile = fopen("{$configInfo['file_root']}admin/mail_log.txt","a+")) {
    die("Mail Log Is Not Writable");
}
$date=date("Y-m-d G:i",$todays_date);
fwrite($logfile,"-----------------\nDate: $date\n\n");
$logmail="-----------------\nDate: $date\n\n";


// 1. Check Opportunities - send NEW ITEM to users. Will create a mail item and flag sent to avoid duplicates
/*
$values = mysqlFetchRows("opportunities", "post_date <= $todays_date");
if(is_array($values)) {
	foreach($values as $index) {
		//echo ("$index[title]\n");
		$mailitem=mysqlFetchRow("mail","assoc_id = $index[opportunity_id] AND type='opportunity'");
		if(!is_array($mailitem)) { //Mail has not been sent yet
			//Generate New Mail Item
			$subject = "[ResearchNet] " . $index['title'];
			//log here
			fwrite($logfile,"Mailout: Opportunity\n  Title: $index[title]\n");
			$body = "@firstname@:<br><br>\n\n<b>\"" . $index['title'] ."\"</b> is a new opportunity posted on the TRU Research Website matching the research subjects you have chosen. <br><br>Synopsis:<br>" . $index['synopsis'] . "<br><br>More information can be found at: <a href=\"http://$server_name/opportunities.php?section=single&id=" . $index['opportunity_id'] . "\">this link</a> on the TRU Research Site<br><br>\n\n";
			//echo ("$subject\n$body\n");
			$s_date=$todays_date;
			$topics_research=$index['topics'];
			$sent=1;
			$values = array('null', addslashes($subject), addslashes($body), $s_date, $topics_research, "", 0, 0, 0, 0, $sent, $index['opportunity_id'], "opportunity",0,0,0,0,0,0);
			$result = mysqlInsert("mail", $values);
			if($result!=1) fwrite($logfile,"\nError Updating Mail Table: $result\n");
			//Do the Send
			$mailitems = array('subject'=>$subject, 'body'=>$body,'testmail'=>false);
			$users=mysqlFetchRows("users");
			if(is_array($users)) {
				array_walk($users,'reset_user');
				array_walk($users,'check_topics',$topics_research);
				//if($index['internal'] == "yes") array_walk($users,'internal'); #remove external users
				foreach($users as $key=>$user) {if ($user['mail_opps']!=1) $users[$key]['visits']=0;}
				array_walk($users,'mailout',$mailitems);
				$total = 0;
				foreach($users as $user) if ($user['visits']==1) $total++;
				if($total >= 1) fwrite($logfile,"Mail sent to $total users\n\n");
				else fwrite($logfile,"Mail sent to no one at all\n\n");
			}
		}
	}
}
*/




// 2. Check Mail for delayed messages & send - flag sent

/*

$mailitems = mysqlFetchRows("mail", "s_date <= $todays_date AND !sent");
if(is_array($mailitems)) {
    foreach($mailitems as $mailitem) {
       //grab all users and put ids into an array ready to tick off items

        
        $users=mysqlFetchRows("users left join profiles using (user_id) left join users_ext using (user_id)","1 order by user_id");
        $total=0;

        // get the users/recipients from the database based on what flags are set
        $users = recipientBuilder(array(
                                       'ft_faculty'      => $mailitem['ft_faculty'],
                                       'pt_faculty'      => $mailitem['pt_faculty'],
                                       'management'      => $mailitem['management'],
                                       'support'         => $mailitem['support'],
                                       'outside'         => $mailitem['outside'],
                                       'chairs'          => $mailitem['deans'],
                                       'deans'           => $mailitem['deans'],
                                       'tss'             => $mailitem['tss'],
                                       'srd'             => $mailitem['srd'],
                                       'strd'            => $mailitem['strd'],
                                       'abstract'        => $mailitem['abstract'],
                                       'topics_research' => $mailitem['topics_research'],
                                       'divisions'       => $mailitem['divisions'],
                                       'userlist'        => $mailitem['single_user']
                                  ), $mailitem['single_user'], $mailitem['mail_type']);
        
        if(is_array($users)) {
            $mailitem['testmail']=false;
            //$mailitem['subject']="[ResearchNet] " . $mailitem['subject'];
            if($mailitem['prepend']!='') 
                $mailitem['subject']="$mailitem[prepend] " . $mailitem['subject'];
             
            $values = array('sent'=>1);
            $result = mysqlUpdate("mail", $values, "mail_id=$mailitem[mail_id]"); 
               
            array_walk($users,'mailout',$mailitem);
          
            //foreach($users as $key=>$user) {
            //    echo "mailing $user[email] - $user[user_id]\n";
            //    if($users[$key]['visits']==1)
            //        mailout($user,$key,$mailitem);
            }
            
            //Add new mail_history item 
            echo "Adding history item\n";
            $groups=$people=$topics='';
            if($mailitem['ft_faculty'] ) $groups.="Full-Time Faculty; ";
            if($mailitem['pt_faculty'] ) $groups.="Part-Time Faculty; ";
            if($mailitem['management'] ) $groups.="Management; "; 
            if($mailitem['support'] )    $groups.="Support Staff; ";
            if($mailitem['outside'] )    $groups.="Outside; ";
            if($mailitem['deans'] )      $groups.="Deans; ";
            if($mailitem['chairs'] )     $groups.="Chairs; ";
            if($mailitem['srd'] )        $groups.="Student Research Day Participants; ";
            if($mailitem['strd'] )       $groups.="S+T Student Research Day Participants; ";
            if($mailitem['abstract'] )   $groups.="(missing abstracts only); ";


            if($mailitem['single_user'] !="") {
                foreach($userlist as $suser) {
                    $username=mysqlFetchRows('users',"user_id=$suser");
                    $people.=$username[0]['last_name'].', '.$username[0]['first_name'].'; ';
                }   
            }
            if($mailitem['topics_research'] !=""){
                foreach($topiclist as $topic) {
                    $tp=mysqlFetchRowsOneCol('topics_research','name',"topic_id=$topic");
                    $topics.=$tp[0].'; ';
                }   
            }
            $result=mysqlInsert('mail_history',array('null',$mailitem['mail_id'],$groups,$people,$topics,$total,mktime()));
            
            fwrite($logfile,"Mailout: Delayed Send\n  Subject: $mailitem[subject]\n");
            if($total >= 1) fwrite($logfile,"Mail sent to $total users\n\n");
            else {
            	fwrite($logfile,"Mail sent to no one at all\n\n");
            	$logmail.="Mail sent to no one at all\n\n";
            }
        }//if isarray $users
    }//foreach mailitem
}//isarray
else {
	fwrite($logfile,"No delayed mail to send today.\n");
	$logmail.="No delayed mail to send today.\n";
}

*/

/*
//Commented out until dates are updated nd confirmed.Tdavis July 5/12

// 3. Check Deadlines for Early Warning Date, Close Warning - if past, look for mail item with matching ID and TYPE.
//    If not sent, send. If doesn't exist, make it and send. (Allows for repeating deadlines)
//Mail: assoc_id = ID of linked item. TYPE = opportunity, deadline-early, deadline-close

/*$deadlinetypes=array(
	"early" => array('field'=>'early_warning_date','type'=>'deadline-early','subject'=>'Warning'),
	"close" => array('field'=>'close_warning_date','type'=>'deadline-close','subject'=>'Deadline'));
foreach($deadlinetypes as $deadlinetype) {
$values=mysqlFetchRows("deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id","$deadlinetype[field] <= $todays_date and d_date > $todays_date and override != 1");
if(is_array($values)) {
	foreach($values as $index) {
		//echo ("$index[title]\n");
        $from_email='research.mtroyal.ca';
        $from_name='Research Services';
        $prepend="[ResearchNet]";
		$mailitem=mysqlFetchRow("mail","assoc_id = $index[date_id] AND type='$deadlinetype[type]'");
		if(!is_array($mailitem)) { //Mail has not been sent yet
			//Generate New Mail Item
			if($index['internal']=='yes'){
				$subject = "[Reminder] $deadlinetype[subject]: $index[title]";
				//$from="$from_name <$from_email>";
				
			}
			else {
				$subject = "$prepend $deadlinetype[subject]: $index[title]";
				//$from="$from_name <$from_email>";
			}
			
			$search=array(	"%dd%", //deadline date
							"%de%", //days in advance
							"%cde%"); //calculated days in advance
			$replace=array(	date("F j", $index['d_date']),
							$index['days_in_advance'],
							date("F j", $index['d_date']-($index['days_in_advance']*60*60*24)));
			$index['warning_message']=str_replace($search,$replace,$index['warning_message']);
			fwrite($logfile,"Mailout: $deadlinetype[subject]\n  Title: $index[title]\n");
			if($index['internal']=='yes') $body = "@firstname@:<br><br>\n\n" . $index['warning_message'] . "<br><br>Synopsis:<br>" . $index['synopsis'] . "<br><br>More information <a href=\"http://$server_name/deadlines.php?section=single&id=" . $index['deadline_id'] . "\">available here</a><br><br>\n";
			else $body = "@firstname@:<br><br>\n\n" . $index['warning_message'] . "<br><br>Synopsis:<br>" . $index['synopsis'] . "<br><br>More information at <a href=\"http://$server_name/opportunities.php?section=deadlines&deadline_id=" . $index['deadline_id'] . "&date_id=". $index['date_id'] . "\">the MR Research Site</a><br><br>\n\n";
			
			$s_date=$todays_date;
			$topics_research=$index['topics'];
			$sent=1;
			$values = array('null', 
                            addslashes($subject), 
                            addslashes($body), 
                            $s_date, 
                            $topics_research, 
                            "", //departments
                            $sent, 
                            $index['date_id'], 
                            "$deadlinetype[type]",
                            0,  //ft
                            0,  //pt
                            0,  //mgmt
                            0,  //support
                            0,  //outside
                            0,  //chairs
                            0,  //deans
                            0,  //students
                            0,  //tss
                            "", //singleuser
                            "",//filename
                            $prepend,
                            $from_email,
                            $from_name,
                            0, //overrride
                            1 // type=deadline
                            );
			$result = mysqlInsert("mail", $values);
            $mail_id=mysql_insert_id();
			if($result!=1) fwrite($logfile,"\nError Updating Mail Table: $result\n");
            
            
			//Do the Send
            $total=0;
            $mailitem = array('subject'=>$subject, 'body'=>$body,'testmail'=>false,'from'=>$from_email,'from_name'=>$from_name);

            //$users=mysqlFetchRows("users left join profiles using (user_id)");
            $users = recipientBuilder(array('topics_research' => $topics_research), false, DEADLINE);
            $topiclist=explode(",",$topics_research);

            if(is_array($users)) {
              foreach($users as $key=>$user){
                    $users[$key]['visits']=0; //flag for mail/nomail

                    //check if there is a topic match
                    if($user['visits'] !=1 && $topics_research != "" && $user['mail_deadlines']) {
                        $user_topics = mysqlFetchRowsOneCol("user_topics_filter","topic_id","user_id=$user[user_id]");

                        if(is_array($user_topics)) {
                            $topiclist=explode(",",$topics_research);
                            foreach($user_topics as $topic)
                                if(in_array($topic,$topiclist))
                            {
                                $users[$key]['visits']=1;
                            }
                        }

                    }
                    if($users[$key]['visits']==1) $total++;

                }//foreach user
            
                $mailitem['testmail']=false;                   
              
                array_walk($users,'mailout',$mailitem);
                
                //Add new mail_history item 
                $groups=$people=$topics='';

                if($topics_research !=""){
                    foreach($topiclist as $topic) {
                        $tp=mysqlFetchRowsOneCol('topics_research','name',"topic_id=$topic");
                        $topics.=$tp[0].'; ';
                    }   
                }
                $result=mysqlInsert('mail_history',array('null',$mail_id,$groups,$people,$topics,$total,mktime()));
                
                if($total >= 1) fwrite($logfile,"Mail sent to $total users\n\n");
                else fwrite($logfile,"Mail sent to no one at all\n\n");   

			} //isarray users
		} //isarray mailitem
	}  //foreach type
} //if there are deadlines
else fwrite($logfile,"No deadlines or warning to send today.\n");
}//foreach deadlinetype
*/



//4.
//    If deadline date is past, and no expiry date, move all dates up one year -- then check for messages as above and remove associated ID so that a new message will be generated next year.

/*

$values=mysqlFetchRows("deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id","expiry_date=0 AND d_date < $todays_date AND no_deadline=0");

if (is_array($values)) {
	foreach($values as $index) {
		$tmpdate=getdate($index['d_date']);
		$tmpdate['year'] += 1;
		$index['d_date'] = mktime(0,0,0,$tmpdate['mon'],$tmpdate['mday'],$tmpdate['year']);
		$tmpdate=getdate($index['close_warning_date']);
		$tmpdate['year'] += 1;
		$index['close_warning_date'] = mktime(0,0,0,$tmpdate['mon'],$tmpdate['mday'],$tmpdate['year']);
		$tmpdate=getdate($index['early_warning_date']);
		$tmpdate['year'] += 1;
		$index['early_warning_date'] = mktime(0,0,0,$tmpdate['mon'],$tmpdate['mday'],$tmpdate['year']);
		$outvals=array('d_date'=>$index['d_date'],'close_warning_date'=>$index['close_warning_date'],'early_warning_date'=>$index['early_warning_date']);
		$result=mysqlUpdate("deadline_dates",$outvals,"date_id=$index[date_id]");
		if ($result!=1) fwrite($logfile,"Error updating Deadline: $result\n\n");
        else {
            if($index['no_deadline']==0) {
            	fwrite($logfile,"Brought deadline $index[title] date forward to ".date($iso8601_day,$index['d_date'])."\n");
            	$logmail.="Brought deadline $index[title] date forward to ".date($iso8601_day,$index['d_date'])."\n";
            }
            else {
            	fwrite($logfile,"Brought deadline (warnings only) $index[title] date forward to ".date($iso8601_day,$index['d_date'])."\n");
            	$logmail.="Brought deadline (warnings only) $index[title] date forward to ".date($iso8601_day,$index['d_date'])."\n";
            }

        }
		$mailitems = mysqlFetchRows("mail","assoc_id=$index[date_id] and (type='deadline-close' or type='deadline-early')");
		if(is_array($mailitems)) {
			foreach($mailitems as $mailitem) {
				$outvals=array('assoc_id'=>'null', 'type'=>'null');
				$result=mysqlUpdate("mail",$outvals,"mail_id=$mailitem[mail_id]");
				if ($result != 1) fwrite($logfile,"Error removing SENT status from associated mail: $result\n\n");
			}
		}	
	}
}
else fwrite($logfile,"No deadlines to move up today.\n");
$logmail.="No deadlines to move up today.\n";

# Check for upcoming deadlines and generate email to responsible party to double check before they go out
# There is no direct record of this, so assuming cronjob runds daily the look-ahead is 7 days to 8 days
$values=mysqlFetchRows("deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id","no_deadline=0 AND (early_warning_date > ($todays_date+604800) AND early_warning_date < ($todays_date+691200))");
if(is_array($values)){
    $message="The following deadlines have upcoming warning messages and should be confirmed:\n";
    foreach($values as $index){
        $date=date($iso8601_day,$index['d_date']);
        $message.="http://research.mtroyal.ca/admin/deadlines.php?section=update&id=$index[deadline_id]\n $index[title] : $date\n";
    }
    //Send it off
    mail($deadline_change_email,'[Deadline Checks]',$message,"From:research@mtroyal.ca");
   // echo "mailing<br>$message";
   fwrite($logfile,"Reported " . count($values) . " upcoming deadlines to admin.\n");
   $logmail.="Reported " . count($values) . " upcoming deadlines to admin.\n";
}
else {
	fwrite($logfile,"No deadline changes to report.\n");
	$logmail.="No deadline changes to report.\n";
}




//Since users may delete cv_items after an annual report is submitted, these are achived to
//an independent table that is used for statistical reports.

#Select missing records

$missing_records= mysqlFetchRows("cas_cv_items","cas_cv_items.cv_item_id NOT IN (SELECT cas_cv_items_archive.cv_item_id FROM cas_cv_items_archive  WHERE cas_cv_items.cv_item_id= cas_cv_items_archive.cv_item_id) ");
$count=0;
$results='';
//print_r($missing_records);
//fwrite($logfile,"Found " . count($missing_records) . " missing records.\n");


if(is_array($missing_records)) foreach($missing_records as $record){
    $record2=array( $record['cv_item_id'],
                    $record['user_id'],
                    $record['cv_item_type_id'],
                    addslashes($record['f1']),
                    $record['f2'],
                    $record['f3'],
                    addslashes($record['f4']),
                    addslashes($record['f5']),
                    addslashes($record['f6']),
                    addslashes($record['f7']),
                    addslashes($record['f8']),
                    addslashes($record['f9']),
                    $record['f10'],
                    $record['f11'],
                    $record['current_par'],
                    $record['web_show'],
                    $record['report_flag'],
                    (($record['document_filename']=='') ? NULL : $record['document_filename']),
                    $record['reason_for_not_providing'],
                    $record['reminder_date'],
                    $record['reminder_sent'],
                    addslashes($record['n01']),
                    addslashes($record['n02']),
                    addslashes($record['n03']),
                    addslashes($record['n04']),
                    addslashes($record['n05']),
                    addslashes($record['n06']),
                    addslashes($record['n07']),
                    addslashes($record['n08']),
                    addslashes($record['n09']),
                    addslashes($record['n10']),
                    addslashes($record['n11']),
                    addslashes($record['n12']),
                    addslashes($record['n13']),
                    addslashes($record['n14']),
                    addslashes($record['n15']),
                    addslashes($record['n16']),
                    addslashes($record['n17']),
                    addslashes($record['n18']),
                    addslashes($record['n19']),
                    addslashes($record['n20']),
                    addslashes($record['n21']),
                    addslashes($record['n22']),
                    addslashes($record['n23']),
                    addslashes($record['n24']),
                    addslashes($record['n25']),
                    addslashes($record['n26']),
                    addslashes($record['n27']),
                    addslashes($record['n28']),
                    addslashes($record['n29']),
                    addslashes($record['n30']),
                    $record['cas_type_id'],
                    $record['converted'],
                    $record['n_teaching'],
                    $record['n_scholarship'],
                    $record['n_service'],
                    addslashes($record['details_teaching']),
                    addslashes($record['details_scholarship']),
                    addslashes($record['details_service']),
                    $record['mycv1'],
                    $record['mycv2'],
                    $record['rank'],
                    $record['boyerDiscovery'],
                    $record['boyerIntegration'],
                    $record['boyerApplication'],
                    $record['boyerTeaching'],
                    $record['boyerService']
                    );
    $result=mysqlInsert("cas_cv_items_archive",$record2);
    if($result==1)$count++;
    else $results.="$result\n";
    
}
//var_dump("{$configInfo['file_root']}admin/mail_log.txt");



if($count > 0) fwrite($logfile,"Wrote $count to CV records archive.\n\n");
if($results!='') fwrite($logfile,"Errors Encountered: $results.\n\n");

if($count > 0) $logmail.="Wrote $count to CV records archive.\n\n";




// Clean out unused items in cas_ tables.

$lists=array(
	'cas_event_organizers',
	'cas_funding_organizations',
	'cas_institution_departments',
	'cas_institutions',
	'cas_partner_organizations',
	'cas_research_journals'
);
foreach($lists as $curr_list){
	$sql="SELECT * FROM $curr_list WHERE 1  ORDER BY name";
	$list=$db->getAll($sql);
	if(count($list)>0){   
		$itemcount=0;     		
		foreach($list as $item){

			//Temp addition to add a freq count to the list
			$sql="SELECT * FROM cas_field_index WHERE sublist='$curr_list'";
			$fields_list=$db->getAll($sql);
			$fresults=array();
			$numentries=0;
			//Now grab all the cv_items where the types exist AND they are using the requested from_list item

			foreach($fields_list as $field){
				$sql="SELECT cas_type_id FROM cas_cv_items WHERE cas_type_id=$field[cas_type_id] AND $field[cas_cv_item_field]=$item[id]";
				$result=$db->getAll($sql);
				if(count($result)>0) {$numentries=1; break;}
				//$result=$db->RecordCount($sql);
				//if($reuslt>0){$numentries=1; break;}
               
               
			}//each field list
			 	if($numentries==0) {
			 		$sql="DELETE from $curr_list where id=$item[id]";
			 		$result=$db->Execute($sql);
			 		fwrite($logfile,"       Deleting \"$item[name]\" from table $curr_list.\n");
			 		$logmail.="       Deleting \"$item[name]\" from table $curr_list.\n";
			 		$itemcount++;
            }
		}//each item
		if($itemcount > 0){
			fwrite($logfile,"Tables: Deleted $itemcount entries from table \"$curr_list\".\n\n");
    		$logmail.="Tables: Deleted $itemcount entries from table \"$curr_list\".\n\n";
        }
		
	}//if the list has entries
	
	
}//foreach list

//*** Manage Tracking Form Notifications ****

$numSent = notifyDeans();
fwrite($logfile,"Dean Notifications [today]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n");
$logmail.= "Dean Notifications [today]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n";

$numSent = notifyORS();
fwrite($logfile,"ORS Notifications [today]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n");
$logmail.= "ORS Notifications [today]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n";

//******** Manage Expired HREB *************

$numUpdated = updateExpiredHREB();
fwrite($logfile,"Set expired status on $numUpdated HREB reports.\n\n");

// Sent reminders for expiring ethics
$numSent = notifyHREB(2); // expiry in 2 months
fwrite($logfile,"HREB Expired [2 months]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n");
$logmail.= "HREB Expired [2 months]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n";

$numSent = notifyHREB(1); // expiry in 1 month
fwrite($logfile,"HREB Expired [1 month]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n");
$logmail.= "HREB Expired [1 month]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n";

$numSent = notifyHREB(0); // expired
fwrite($logfile,"HREB Expired [today]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n");
$logmail.= "HREB Expired [today]: sent " . $numSent['emails'] . " notifications with " . $numSent['errors'] . " errors. \n\n";


#Clean up the big mail log
$too_old = mktime() - (60*60*24*30*2); # Currently 2 months old
$values = mysqlFetchRows("maillog","date <= $too_old");
if (is_array($values)) {
    foreach ($values as $index) mysqldelete("maillog","log_id = $index[log_id]");
    $counter = count($values);
    fwrite($logfile,"Clean up: Deleted $counter log entries.\n\n");
    $logmail.="Clean up: Deleted $counter log entries.\n\n";
}

fclose($logfile);
fclose($logfile);
mail ($logmail_recipient,"ORS Cron Log",$logmail,"From:research@mtroyal.ca");




//rebuildSiteIndex();

*/

function formatCVTable($rows, $type='') {
	$result = "";
	$rows = explode("!#$*$#!",$rows);
	$count = count(explode("!#$^$#!",$rows[0]));
	for($x=0;$x!=count($rows);$x++) $rows[$x]  = explode("!#$^$#!", $rows[$x]);	
	for($i=0;$i<$count;$i++) {
		//assume that the last item is always the URL, and the second-last is the text
		//$url_item = count($rows) - 1;
		//$text_item = $url_item -1;
		for($x=0;$x!=count($rows);$x++) $result .= $rows[$x][$i] . " ";
		if ($i<$count-1) $result .= ' | ';
	}
	return $result;
}

/**
 * Update expired HREB
 */
function updateExpiredHREB() {
    global $db;

    $sql ="SELECT * FROM `hreb` WHERE `expiryDate` = DATE_FORMAT(CURDATE(), '%Y-%c-%d')";
    $expiredHREB = $db->getAll($sql);

    foreach($expiredHREB AS $hreb) {
        // Update the status to Expired
        $sql = "UPDATE `hreb` SET `status` = 3 WHERE trackingId = " . $hreb['trackingId'];
        $result = $db->Execute($sql);
    }

    if(!$result) {
        return 0;
    }

    return count($expiredHREB);
}

/**
 * Email the Dean's a daily summary of tracking forms that were submitted or are due in 4 days
 */
function notifyDeans() {
    global $db;

    $emailsSent = 0;
    $errors = 0;

    $sql = "SELECT t.form_tracking_id, t.tracking_name, t.user_id, t.synopsis, t.submit_date, t.deadline, departments.name AS departmentName,
                   divisions.division_id AS divisionId, divisions.name AS divisionName, users.user_id AS deanUserId, users.first_name AS deanFirst, users.last_name AS deanLast, profiles.email AS deanEmail
            FROM forms_tracking AS t
            LEFT JOIN users AS u ON t.user_id = u.user_id
            LEFT JOIN departments ON departments.department_id = u.department_id
            LEFT JOIN divisions ON divisions.division_id = departments.division_id
            LEFT JOIN users ON users.user_id = divisions.dean
            LEFT JOIN profiles ON users.user_id = profiles.user_id
            WHERE DATE(t.submit_date) = DATE(NOW()) OR DATE_SUB(DATE(t.deadline), INTERVAL 4 DAY) = DATE(NOW())
            ORDER BY deanEmail";
    $result = $db->getAll($sql);


    $emails = array(); // we group the emails by recipient
    foreach($result AS $trackingForm) {
        $deanEmail = $trackingForm['deanEmail'];

        /**
         * We have placed a hard-coded override here for Health & Community studies.
         * Instead of the Dean receiving the email, Vince Salyers receives it.
         */
        if($trackingForm['divisionId'] == 18) {
            $deanEmail = 'vsalyers@mtroyal.ca';
            $user['user_id'] = 1962;
            $user['email'] = $deanEmail;
            $user['first_name'] = 'Vince';
            $user['last_name'] = 'Salyers';
        } else {
            $user['user_id'] = $trackingForm['deanUserId'];
            $user['email'] = $deanEmail;
            $user['first_name'] = $trackingForm['deanFirst'];
            $user['last_name'] = $trackingForm['deanLast'];
        }

        $emails[$deanEmail][] = array(
            'tid'       => $trackingForm['form_tracking_id'],
            'title'     => $trackingForm['tracking_name'],
            'synopsis'  => $trackingForm['synopsis'],
            'submitted' => date('Y-m-d', strtotime($trackingForm['submit_date'])),
            'deadline'  => date('Y-m-d', strtotime($trackingForm['deadline']))
        );
        $emails[$deanEmail]['user'] = $user;
    }

    foreach($emails AS $deanEmail=>$email) {
        $values['subject'] = "[ORS] Daily tracking form summary";
        $values['body'] = 'Hello ' . $email['user']['first_name'] . ',<br/><br/>

The following tracking forms were submitted today, or require approval soon :<br/><br/>

        ';
        print_r($email);
        foreach($email AS $trackingForm) {

            $values['body'] .= sprintf("

TID: %s<br/>
Title: %s<br/>
Synopsis: %s<br/>
Submitted: %s<br/>
Deadline: %s<br/><br/>

---------------------------------------------------------
        ", $trackingForm['tid'], $trackingForm['title'], $trackingForm['synopsis'], $trackingForm['submitted'], $trackingForm['deadline']);
        }

        $values['body'] .= '

You can review and approve these tracking forms by logging into the research services site at http://research.mtroyal.ca and navigating to \'My Approvals\'.<br/><br/>

Regards,<br/>
Office of Research Services';

        $success = mailout($email['user'], null, $values);

        if($success) {
            $emailsSent++;
        } else {
            $errors++;
        }
    }

    return array('emails' => $emailsSent, 'errors' => $errors);
}

/**
 * Email the ORS a daily summary of tracking forms that required action in 48hrs, 24hrs or were submitted today
 */
function notifyORS()
{
    global $db;

    $emailsSent = 0;
    $errors     = 0;

    $sql = "SELECT t.form_tracking_id,  t.funding_deadline, t.tracking_name, t.user_id, t.submit_date, t.ors_submits, t.letter_required
            FROM forms_tracking AS t
            WHERE (t.funding_deadline BETWEEN DATE(NOW()) AND DATE_ADD(NOW(), INTERVAL 3 DAY)
				   AND (t.ors_submits = 1 OR t.letter_required = 1)
				   AND (t.ors_submitted_status = 0))
            ORDER BY t.funding_deadline DESC";
    $result = $db->getAll($sql);
    $total = count($result);

    if($total > 0) {
        $user['user_id']    = 2143;
        $user['email']      = 'jcameron@mtroyal.ca';
        $user['first_name'] = 'Jerri-Lynne';
        $user['last_name']  = 'Cameron';

        $values['subject'] = "[ORS-FUNDING] Daily tracking form summary (" . $total . ")";
        $values['body']    = 'Hello ' . $user['first_name'] . ',<br/><br/>

    The following tracking forms have funding deadlines approaching:

            ';

        foreach ($result AS $key=>$trackingForm) {
            $values['body'] .= sprintf("
    <br/><br/>
    ------------------%s---------------------------<br/>
    TID: %s<br/>
    Title: %s<br/>
    Submitted On: %s<br/>
    Funding Deadline: %s<br/>
    Letter of Support Required : %s<br/>
    ---------------------------------------------------------<br/><br/>
            ",
                $key+1 . ' of ' . $total,
                $trackingForm['form_tracking_id'],
                $trackingForm['tracking_name'],
                $trackingForm['submit_date'],
                $trackingForm['funding_deadline'],
                $trackingForm['letter_required'] == 1 ? 'Yes' : 'No'
            );
        }

        $values['body'] .= '
    <br/>
    Regards,<br/>
    Office of Research Services Robot';

        $success = mailout($user, null, $values);

        if ($success) {
            $emailsSent++;
        } else {
            $errors++;
        }
    }

return array('emails' => $emailsSent, 'errors' => $errors);
}


/**
 * Send email notification to expired HREB's for researcher and select ORS staff
 *
 * @var $monthInterval - the number of months 1, or 2.  If 0, then those expiring today
 * @return array('emails'=>  number of emails sent,
 *               'errors' => num errors encountered)
 */
function notifyHREB($monthInterval) {
    global $db, $configInfo;

    /* We want to notify those who handle HREB and Finance in the ORS office too. */
    $HREBUserId = 2147; // HREB ORS user id
    $ORSFinanceUserId = 2142;  // ORS Finance user id

    // make sure the month interval is and integer and either 0, 1 or 2
    if(!is_int($monthInterval)) {
        return;
    } elseif($monthInterval > 2 OR $monthInterval < 0) {
            return;
    }

    // Get expired HREB's for 1 or 2 months
    $sql = "SELECT `hreb`.* FROM `hreb`
            WHERE `expiryDate` = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL " . $monthInterval . " MONTH), '%Y-%c-%d')";

    // if 0 months then just check for ones expiring today
    if($monthInterval == 0) {
        $sql = "SELECT `hreb`.* FROM `hreb`
                WHERE `expiryDate` = DATE_FORMAT(NOW(), '%Y-%c-%d')";
    }

    $expiredHREB = $db->getAll($sql);


    $emails = 0;
    $errors = 0;

    foreach($expiredHREB AS $hreb) {
        // Get the principal investigator's email address
        $sql = "SELECT user_id, pi, pi_id, status FROM forms_tracking WHERE `form_tracking_id` = " . $hreb['trackingId'];
        $trackingUser = $db->getRow($sql);

        // only send emails for tracking forms with a status of submitted
        $status = $trackingUser['status'];
        if($status == 1) {
            $user_id = $trackingUser['user_id'];
            if($trackingUser['pi'] != 1) {
                if($trackingUser['pi_id'] != 0) {
                    $user_id = $trackingUser['pi_id'];
                }
            }

            $sql = "SELECT profiles.`email`, users.user_id, users.first_name, users.last_name FROM profiles
                    LEFT JOIN `users` ON profiles.user_id = users.user_id
                    WHERE profiles.`user_id` = " . $user_id;
            $userDetails = $db->getRow($sql);

            $user['user_id'] = $userDetails['user_id'];
            $user['email'] = $userDetails['email'];
            $user['first_name'] = $userDetails['first_name'];
            $user['last_name'] = $userDetails['last_name'];

            $values['subject']  = '[HREB] Ethics Expiry Notification : ' . $hreb['expiryDate'];
            if($monthInterval == 0) {
            $values['body']= sprintf('Please note that the ethics clearance for your project has expired. If you plan to continue with your study a progress report is required.<br/><br/>

    If you have had your last contact with the study participants, please submit a completion report. Both forms can be accessed from: http://www.mtroyal.ca/Research/Ethics/HumanresearchHREB/ethics_forms <br/><br/>

    Please forward your report to the Research Ethics Officer at hreb@mtroyal.ca<br/><br/>

    Ethics Number: %s<br/>
    Tracking ID : %s<br/>
    ExpiryDate: %s<br/>'
    , $hreb['ethicsnum'], $hreb['trackingId'], $hreb['expiryDate']);
            } else {
            $values['body'] = sprintf('Please note that the ethics clearance for your project will be expiring soon.   Either approval is still pending approval, or if previously approved then a brief study completion report is required by %s.  If your study continues beyond the expiry date you will need to request an extension of your ethics clearance and provide a brief progress report.<br/><br/>

    If you have finished the data collection and you\'ve had your last contact with the study participants you may submit a completion report. Both forms can be accessed from: http://www.mtroyal.ca/Research/Ethics/HumanresearchHREB/ethics_forms<br/><br/>

    Please forward your report to the Research Ethics Officer at hreb@mtroyal.ca<br/><br/>

    Ethics Number: %s<br/>
    Tracking ID : %s<br/>
    ExpiryDate: %s<br/>
    ', $hreb['ethicsnum'], $hreb['expiryDate'], $hreb['trackingId'], $hreb['expiryDate']);
        }

            $success = mailout($user, null, $values);

            if($success) {
                $emails++;
            } else {
                $errors++;
            }

    /************ Notify HREB and Finance in ORS also *********************/

            $values['subject']  = '[HREB] Ethics Expiry Notification : ' . $hreb['ethicsnum'];
            $values['body']= sprintf('Please note that the ethics clearance for %s is expiring on %s.<br/><br/>
    Ethics Number: %s<br/>
    Tracking ID : %s<br/>
    ExpiryDate: %s<br/><br/>

Regards,
ORS System.
    ',$hreb['ethicsnum'],
      $hreb['expiryDate'],
      $hreb['ethicsnum'],
      $hreb['trackingId'],
      $hreb['expiryDate']);

            //get HREB recipient details
            $sql = "SELECT profiles.`email`, users.user_id, users.first_name, users.last_name FROM profiles
                    LEFT JOIN `users` ON profiles.user_id = users.user_id
                    WHERE profiles.`user_id` = " . $HREBUserId;
            $userDetails = $db->getRow($sql);

            $hrebUser['user_id'] = $userDetails['user_id'];
            $hrebUser['email'] = $userDetails['email'];
            $hrebUser['first_name'] = $userDetails['first_name'];
            $hrebUser['last_name'] = $userDetails['last_name'];

            $success = mailout($hrebUser, null, $values);

            if($success) {
                $emails++;
            } else {
                $errors++;
            }

            //get ORS Finance recipient details
            $sql = "SELECT profiles.`email`, users.user_id, users.first_name, users.last_name FROM profiles
                    LEFT JOIN `users` ON profiles.user_id = users.user_id
                    WHERE profiles.`user_id` = " . $ORSFinanceUserId;
            $userDetails = $db->getRow($sql);

            $financeUser['user_id'] = $userDetails['user_id'];
            $financeUser['email'] = $userDetails['email'];
            $financeUser['first_name'] = $userDetails['first_name'];
            $financeUser['last_name'] = $userDetails['last_name'];

            $success = mailout($financeUser, null, $values);

            if($success) {
                $emails++;
            } else {
                $errors++;
            }


        }
    }
    return array('emails' => $emails, 'errors' => $errors);
}

function GetSchoolYear($timeStamp) {

    // if the month is Jan -> Aug then the year is this year, other wise it is next year
    if (date('n',$timeStamp) < 9) {
        $schoolYear = date('Y',$timeStamp);
    } else {
        $schoolYear = date('Y',$timeStamp) + 1;
    } // if
    return $schoolYear;

} // function GetSchoolYear

?>
