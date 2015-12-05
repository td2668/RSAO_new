<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//$filepath = "/home/html_root/htdocs/";
$filepath = "../";

//$template = new Template;

$hdr=loadPage("header",'Header');

$menuitems=array();
$menuitems[]=array('title'=>'Add','url'=>'users.php?section=add');
$menuitems[]=array('title'=>'List','url'=>'users.php?section=view');
$hdr->AddRows("list",$menuitems);


$tmpl=loadPage("users", 'Users');


//Do some replacements to keep quotes etc from turning into little boxes in IE6
$search=array(  '/&#8216;/',        # 0x2018 Left single quotation mark
                '/&#8217;/',        # 0x2019 Right single quotation mark
                '/&#8218;/',        # 0x201A Single low-9 quotation mark
                '/&#8219;/',        # 0x201B Single high-reversed-9 quotation mark
                '/&#8220;/',        # 0x201C Left double quotation mark
                '/&#8221;/',        # 0x201D Right double quotation mark
                '/&#8208;/',       # 0x2010 Hyphen
                '/&#8209;/',        # 0x2011 Non-breaking hyphen
                '/&#8211;/',       # 0x2013 En dash
                '/&#8212;/',       # 0x2014 Em dash
                '/&#8213;/',       # 0x2015 Horizontal bar/quotation dash
                '/&#8214;/',       # 0x2016 Double vertical line

                
                '/&#8222;/',        # 0x201E Double low-9 quotation mark
                '/&#8223;/',        # 0x201F Double high-reversed-9 quotation mark
                '/&#8226;/',      # 0x2022 Bullet
                '/&#8227;/',      # 0x2023 Triangular bullet
                '/&#8228;/',      # 0x2024 One dot leader
                '/&#8229;/',      # 0x2026 Two dot leader
                '/&#8230;/',      # 0x2026 Horizontal ellipsis
                '/&#8231;/',         # 0x2027 Hyphenation point
                '/\x91/',
                '/\x92/',
                '/\x93/',
                '/\x94/',
                '/\x95/',
                '/\x96/',
                '/\x97/'     
                );
$replace=array( "'",        # 0x2018 Left single quotation mark
                "'",        # 0x2019 Right single quotation mark
                ',',        # 0x201A Single low-9 quotation mark
                "'",        # 0x201B Single high-reversed-9 quotation mark
                '"',        # 0x201C Left double quotation mark
                '"',        # 0x201D Right double quotation mark
                '-',        # 0x2010 Hyphen
                '-',        # 0x2011 Non-breaking hyphen
                '--',       # 0x2013 En dash
                '--',       # 0x2014 Em dash
                '--',       # 0x2015 Horizontal bar/quotation dash
                '||',       # 0x2016 Double vertical line

                
                ',,',        # 0x201E Double low-9 quotation mark
                '"',        # 0x201F Double high-reversed-9 quotation mark
                '&#183;',      # 0x2022 Bullet
                '&#183;',      # 0x2023 Triangular bullet
                '&#183;',      # 0x2024 One dot leader
                '..',      # 0x2026 Two dot leader
                '...',      # 0x2026 Horizontal ellipsis
                '&#183;',   # 0x2027 Hyphenation point
                "'",
                "'",
                '"',
                '"',
                '*',
                '-',
                '--'      
                );


if(isset($_REQUEST['unhide'])){
	 if(isset($_REQUEST['user_id'])) {
        $sql="UPDATE users SET user_level=0 WHERE user_id={$_REQUEST['user_id']}";
        if(!$db->Execute($sql))  $success='Error unhiding';
        else  $success='Unhidden';
		
	 }
}
if(isset($_REQUEST['hide'])){
	 if(isset($_REQUEST['user_id'])) {
	 	$sql="UPDATE users SET user_level=1 WHERE user_id={$_REQUEST['user_id']}";
	 	if(!$db->Execute($sql))  $success='Error hiding';
        else  $success='Hidden';
	 }
}
if(isset($_REQUEST['add'])) {
	 if(isset($_REQUEST['mail_events'])) $mail_events=1; else $mail_events=0;
	 if(isset($_REQUEST['mail_deadlines'])) $mail_deadlines=1; else $mail_deadlines=0;
	 if(isset($_REQUEST['feature'])) $feature=1; else $feature=0;
	 if(isset($_REQUEST['browser'])) $browser=1; else $browser=0;
	 if(isset($_REQUEST['hidden'])) $hidden=1; else $hidden=0;
     if($_REQUEST['department']=='') $_REQUEST['department']=0;
     if($_REQUEST['department2']=='') $_REQUEST['department2']=0;
	 if(strlen($_REQUEST['profile_ext']) > 1 || strlen($_REQUEST['profile_short']) > 1) $date=mktime(); else $date=0;

	if(authorizeUsername($_REQUEST['username'])) {
		$sql="INSERT INTO users SET 
				user_id='null',
				first_name='".mysql_escape_string ($_REQUEST['first_name'])."', 
				last_name='".mysql_escape_string($_REQUEST['last_name'])."', 
				username='$_REQUEST[username]', 
				password='".mysql_escape_string($_REQUEST['password'])."', 
				date='$date', 
				mail_events=$mail_events,
				mail_deadlines=$mail_deadlines,
				password2='".md5($_REQUEST['password'])."',
				department_id=$_REQUEST[department],
				department2_id=$_REQUEST[department2],
				emp_type='$_REQUEST[emp_type]',
				feature=$feature,
				user_level=$hidden,
				browser=$browser";
		
		if(!$db->Execute($sql)) $success.='Error inserting into users table';
		else{
			$success = " <strong>Complete</strong>";
			$id=$db->Insert_ID();
			$dept_display_as=""; $faculty_display_as="";
			
			$dept=$db->GetRow("SELECT * FROM departments WHERE department_id=$_REQUEST[department]");
			if(($dept)) {
				$dept_display_as=$dept['name'];
				$fac=$db->GetRow("SELECT * FROM divisions WHERE division_id=$dept[division_id]");
				if(($fac)) $faculty_display_as=$fac['name'];
			}
            $profile_ext=addslashes(preg_replace($search,$replace,$_REQUEST['profile_ext']));
            $profile_short=addslashes(preg_replace($search,$replace,$_REQUEST['profile_short']));
            
			$sql="INSERT INTO profiles SET
							user_id= $id, 
                            email='$_REQUEST[email]', 
                            faculty_display_as='$faculty_display_as', 
                            dept_display_as='$dept_display_as', 
                            title='$_REQUEST[title]', 
                            secondary_title='$_REQUEST[secondary_title]', 
                            office='$_REQUEST[office]', 
                            phone='$_REQUEST[phone]', 
                            fax='$_REQUEST[fax]', 
                            homepage='$_REQUEST[homepage]', 
                            profile_ext='". stripslashes($profile_ext)."',
                            profile_short='".stripslashes($profile_short)."',
                            keywords='$_REQUEST[keywords]'
                            ";
			$result=$db->Execute($sql);
			if(!$result) $success.="Error inserting profile: ". $db->ErrorMsg();
			else $success.=" (profile)";
            
            if($_REQUEST['start_date']=='')$_REQUEST['start_date']=0;
            if($_REQUEST['tss_start']=='')$_REQUEST['tss_start']=0;
            if(isset($_REQUEST['tss'])) $tss=1; else $tss=0;
            if($_REQUEST['emp_num']=='')$_REQUEST['emp_num']=0;
            $sql="INSERT INTO users_ext SET
            				emp_num='$_REQUEST[emp_num]',
                            user_id=$id,
                            emp_status='$_REQUEST[emp_status]',
                            start_date='$_REQUEST[start_date]',
                            tss=$tss,
                            tss_start='$_REQUEST[tss_start]' ,
                            active_status='$_REQUEST[active_status]',
                            cv_optout=0";
            $result=$db->Execute($sql);
            if(!$result) $success.="Error inserting ext: ".$db->ErrorMsg();
            else $success.=" (ext)";
            
            
			if(isset($_REQUEST['disabled'])){
				$result=$db->Execute("INSERT INTO users_disabled SET user_id=$id");
				if(!$result) $success.="Error inserting disabled item: ".$db->ErrorMsg();
				else $success.=" (disabled)";
			}

			$result=$db->Execute("DELETE from user_topics_filter WHERE user_id=$id");

			//handle View All Topics
            if(isset($_REQUEST['topics'])) $topics=$_REQUEST['topics'];
			if(isset($topics) && $topics[0]=='0') {
				$topic_list=$db->GetAll("SELECT * FROM topics_research WHERE 1");
				if(count($topic_list)>0) foreach($topic_list as $topic_item) 
					$result=$db->Execute("INSERT INTO user_topics_filter SET topic_id=$topic_item[topic_id], user_id=$id");
			}
			elseif(isset($topics) && $topics[0] != "") {
				foreach($topics as $topic){
					if(!($db->GetRow("SELECT * FROM user_topics_filter WHERE user_id=$id AND topic_id=$topic"))) {
						if($topic != "") $result=$db->Execute("INSERT INTO user_topics_filter SET topic_id=$topic, user_id=$id");
					}
				}
			}


		
			//send welcome message if requested
			if(isset($_REQUEST['welcome_msg'])){
				$message=mysqlFetchRow("messages","message_id=$_REQUEST[which_message]");
				if(is_array($message)){
					$msg=str_replace('@firstname@',$_REQUEST['first_name'],$message['message']);
					$msg=str_replace('@username@',$_REQUEST['username'],$msg);

					$mailitems=array('subject'=>"Your new account",'body'=>$msg,'testmail'=>false);
					$user_id = mysql_insert_id();
					$newuser=mysqlFetchRow("users","user_id=$_REQUEST[user_id]");
					$newuser['visits']= 1;
					mailout($newuser,'null',$mailitems);
					if (!$logfile = fopen("{$filepath}admin/mail_log.txt","a+")) die("Mail Log Is Not Writeable");
					$date=date("j/n/y",$todays_date);
					fwrite($logfile,"-----------------\nDate: $date\n\n");
					fwrite($logfile,"New user mail sent to $_REQUEST[first_name] $_REQUEST[last_name]\n\n");
					fclose($logfile);
				}
				else $success="Error sending mail - message not loaded from database";

			}
		} //else - good insert into users
		
		
		$user_id = $db->Insert_ID();

	}
	else $success = " <strong style='color:red;'>Username is currently in use please choose another user name</strong>";
}


//Update
else if (isset($_REQUEST['update'])) {
	if(isset($_REQUEST['mail_events'])) $mail_events=1; else $mail_events=0;
	if(isset($_REQUEST['mail_deadlines'])) $mail_deadlines=1; else $mail_deadlines=0;
	if(isset($_REQUEST['feature'])) $feature=1; else $feature=0;
	if(isset($_REQUEST['browser'])) $browser=1; else $browser=0;
	if(isset($_REQUEST['hidden'])) $hidden=1; else $hidden=0;
	if(isset($_REQUEST['fac_only'])) if($_REQUEST['fac_only']=="") unset($_REQUEST['fac_only']);

    $sql="SELECT * FROM profiles WHERE user_id=$_REQUEST[id]";
    $old_profile=$db->GetRow($sql);    
    $sql="SELECT * FROM users WHERE user_id=$_REQUEST[id]";
    $user=$db->GetRow($sql);
	if(strcasecmp($_REQUEST['profile_ext'],$old_profile['profile_ext'])==0 && strcasecmp($_REQUEST['profile_short'],$old_profile['profile_short'])==0) $date=$user['date']; else $date=mktime();
    
    //This needs updating
    $profile_ext=addslashes(preg_replace($search,$replace,$_REQUEST['profile_ext']));
    $profile_short=addslashes(preg_replace($search,$replace,$_REQUEST['profile_short']));
    
	$values = array('first_name'=>addslashes($_REQUEST['first_name']),
					'last_name'=>addslashes($_REQUEST['last_name']),
					'username'=>$_REQUEST['username'],
					'mail_events'=>$mail_events,
					'mail_deadlines'=>$mail_deadlines,
					'department_id'=>$_REQUEST['department'],
					'department2_id'=>$_REQUEST['department2'],
					'emp_type'=>$_REQUEST['emp_type'],
					'feature'=>$feature,
					'user_level'=>$hidden,
					'browser'=>$browser,
					'date'=>$date);
    //Doing this to save typing in converting old code
    $str_values = "";
    foreach($values as $k => $v)
        $str_values .= $k."='".$v."', ";
    $str_values = substr($str_values, 0, -2);
    
	$values2 = array(   'title'=>addslashes($_REQUEST['title']),
						'secondary_title'=>addslashes($_REQUEST['secondary_title']),
						'office'=>$_REQUEST['office'],
						'phone'=>$_REQUEST['phone'],
						'fax'=>$_REQUEST['fax'],
						'homepage'=>addslashes($_REQUEST['homepage']),
						'email'=>$_REQUEST['email'],
						'profile_short'=>$profile_short,
						'profile_ext'=>$profile_ext,
						'keywords'=>$_REQUEST['keywords']);
    //Again saving typing
    $str_values2 = "";
    foreach($values2 as $k => $v)
        $str_values2 .= $k."='".$v."', ";
    $str_values2 = substr($str_values2, 0, -2);
    //echo($str_values . '<br>' . $str_values2);
    
    
	$ok=TRUE;
    if($_REQUEST['username'] != $user['username']) $ok=authorizeUsername($_REQUEST['username']);
    if($ok)     {
        $success="";
        $sql="UPDATE users SET $str_values WHERE user_id=$_REQUEST[id]";
		$result=$db->Execute($sql);
        if(!$result) $success.=" Error updating: " . $db->ErrorMsg();
        else $success.=" <strong>User Updated </strong>";
        if($old_profile==FALSE) {
            $sql="INSERT INTO profiles SET user_id=$_REQUEST[id]";
            $result=$db->Execute($sql);
        }
        $sql="UPDATE profiles SET $str_values2 WHERE user_id=$_REQUEST[id]";
        $result= $db->Execute($sql);
        if(!$result) $success.=" Error updating Profile: ".$db->ErrorMsg();
        else {$success.=" <strong> (profile) </strong>"; $section="view";}
        
        $sql="SELECT * FROM users_ext WHERE user_id=$_REQUEST[id]";
        $test=$db->GetRow($sql);
        
        if(!$test) $result=$db->Execute("INSERT INTO users_ext SET user_id=$_REQUEST[id]");
        
        if($_REQUEST['start_date']=='')$_REQUEST['start_date']=0;
        if($_REQUEST['tss_start']=='')$_REQUEST['tss_start']=0;
        if(isset($_REQUEST['tss'])) $tss=TRUE; else $tss=FALSE;
        $values3=array( 'emp_num'=>$_REQUEST['emp_num'],
                        'emp_status'=>$_REQUEST['emp_status'],
                        'start_date'=>$_REQUEST['start_date'],
                        'tss'=>$tss,
                        'tss_start'=>$_REQUEST['tss_start'] ,
                        'active_status'=>$_REQUEST['active_status']
                        );
        $str_values = "";
        foreach($values3 as $k => $v)
            $str_values .= $k."='".$v."', ";
        $str_values = substr($str_values, 0, -2);
        $sql="UPDATE users_ext SET $str_values WHERE user_id=$_REQUEST[id]";
        $result=$db->Execute($sql);
        if(!$result) $success.="Error updating ext: ". $db->ErrorMsg();
        else $success.=" (ext)";
            

        $db->Execute("DELETE FROM user_topics_filter WHERE user_id=$_REQUEST[id]");
		
		//handle View All Topics
        if(isset($_REQUEST['topics'])) $topics=$_REQUEST['topics'];
		if(isset($topics) && $topics[0]=='0') {
			$topic_list=$db->GetAll("SELECT * FROM topics_research WHERE 1");
			if(count($topic_list)>0) foreach($topic_list as $topic_item) $db->Execute("INSERT INTO user_topics_filter SET topic_id=$topic_item[topic_id], user_id=$_REQUEST[id]");
		}
        //doall
		elseif(isset($topics) && $topics[0] != "") {
			foreach($topics as $topic){
                
				if(count($db->getRow("SELECT * FROM user_topics_filter  WHERE user_id=".$_REQUEST['id']." AND topic_id=$topic")>0)) {
					if($topic != "") $db->Execute("INSERT INTO user_topics_filter SET topic_id= $topic, user_id=$_REQUEST[id]");
				}
			}
		}

		if(isset($_REQUEST['disabled'])){
			$dis=$db->getRow("SELECT * FROM users_disabled WHERE user_id=$_REQUEST[id]");
			if(count($dis)==0) {
				$result=$db->Execute("INSERT INTO users_disabled SET user_id=$_REQUEST[id]");
				if(!$result) $success.=" Error disabling user: ".$db->ErrorMsg();
				else $success.= " (Disabled)";
			}
		}
		else { //not disabled
			$result=$db->Execute("DELETE FROM users_disabled WHERE user_id=$_REQUEST[id]");

		}
		$oneline=mysql_escape_string($_REQUEST['oneline']);
		$sql="DELETE FROM users_oneline WHERE user_id=$_REQUEST[id]";
		$result=$db->Execute($sql);
		$sql="INSERT INTO users_oneline (`user_id`,`oneline`) VALUES($_REQUEST[id],'$oneline')";
		$result=$db->Execute($sql);
		if(!$result) $success.="Error Inserting Oneline:" . $db->ErrorMsg;


	}  //username did not authorize
	else $success = " <strong style='color:red;font-size:12px;'>Username is currently in use please choose another user name</strong>";
    
    $_REQUEST['section']='view';
}
else if (isset($_REQUEST['delete'])) {
	$db->Execute("DELETE FROM users WHERE user_id=$_REQUEST[id]");
    $db->Execute("DELETE FROM users_ext WHERE user_id=$_REQUEST[id]");
	if($db->Execute("DELETE FROM profiles WHERE user_id=$_REQUEST[id]")) $success=" <strong>User Deleted</strong>";
	$db->Execute("DELETE FROM users_disabled WHERE user_id=$_REQUEST[id]");
    $db->Execute("DELETE FROM user_topics_filter WHERE user_id=$_REQUEST[id]");
    $db->Execute("DELETE FROM users_oneline WHERE user_id=$_REQUEST[id]");
    $db->Execute("DELETE FROM cas_cv_items WHERE user_id=$_REQUEST[id]");
    $db->Execute("DELETE FROM pictures_associated WHERE object_id=$_REQUEST[id] AND table_name='users'");
    $_REQUEST['section']='view';
}

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";




switch($_REQUEST['section']){
	case "view":
		$tmpl->setAttribute("view","visibility","visible");
		//if(!UserLog(652,'fair','Test')) $success='Error Inserting';
		
		$values="";
		$header=array();
		for($x=65;$x<=90;$x++){
			$let=chr($x);
			if(isset($_REQUEST['fac_only'])) $fac_text='&fac_only'; else $fac_text='';
			$letterurl="users.php?section=view$fac_text&letter=$let";
			$header[]=array('letterurl'=>$letterurl,'letter'=>$let);
		}
		$tmpl->addRows("letter",$header);
		$output="";
		if (isset($_REQUEST['fac_only'])){
			$fac_call= "(emp_type='MAN' OR emp_type='FACL') AND ";
			$header1="Faculty and Management";
		}
		else { 
			$fac_call=""; 
			$header1="All Users"; 
		}
		
		$hdr->AddVar("header","title","Users: View $header1");
		
		//print_r($_REQUEST);
		if(isset($_REQUEST['letter']))  {
			$sql="	SELECT users_disabled.user_id as disabled,  
						users.*,
						users_ext.*,
						profiles.* 
					FROM users 
					left join profiles using (user_id) 
					left join users_ext using (user_id) 
					left join users_disabled using (user_id) 
					
					WHERE $fac_call last_name regexp \"^$_REQUEST[letter]\" 
					order by last_name";
			$values=$db->GetAll($sql);
        }
		else $letter='';
		
		$viewlist=array();
		if(is_array($values)) {
			$count=1;
			foreach($values as $index) {
				if($count % 2 == 0 ) $bgcolor="#D7D7D9";
				else $bgcolor="#E7E7E9";
				$count++;
				if($index['disabled']) $bgcolor='#EE9999';
				else if ($index['user_level']==1) $bgcolor='#CCCCAA';
				
				$sql="SELECT * FROM user_topics_filter WHERE user_id=$index[user_id]";
				$filter=$db->GetAll($sql);
				if(count($filter)>0) $index['app']="#33FF33"; else $index['app']=$bgcolor;
				$sql="SELECT count(user_id) as count from cas_cv_items WHERE user_id=$index[user_id]";
				
				$recordSet = $db->getAll($sql);
				
                $index['cv_items']=$recordSet[0]['count'];
                
                $sql="SELECT count(user_id) as count from users_history WHERE user_id=$index[user_id]";                
                
                $recordSet = $db->getAll($sql);
				
                $index['access']=$recordSet[0]['count'];
                
				if ($index['date'] != 0) $cv_out=date('j/M/y',$index['date']);
				else $cv_out="&nbsp;";
				if(stristr($index['email'],"mtroyal.ca") === false) $index['redflag']="#FF0000"; else $index['redflag']="#000000";
				//User Types
				if($index['username']==$index['password']) $index['pw']='#FF9999';else $index['pw']=$bgcolor;

				if($index['browser']) $index['brow']='#33FF33'; else $index['brow']=$bgcolor;
				if(strlen($index['profile_short']) > 10) $index['p_short']='#33FF33';else $index['p_short']=$bgcolor;
/*				if(strlen($index['oneline']) > 2) $oneline='#33FF33';else $oneline=$bgcolor;*/
				if(strlen($index['profile_ext']) > 10) $index['p_ext']='#33FF33';else $index['p_ext']=$bgcolor;
                if($index['mail_events']) $index['events']='checked'; else $index['events']='';
                if($index['mail_deadlines']) $index['deadlines']='checked'; else $index['deadlines']='';
                $index['picture'] = (mysqlFetchRow("pictures_associated", "object_id=$index[user_id] AND table_name='users'"))?
			"<img src='/images/head.gif'>":"";
                if($index['tss'] == 1) {
                  $index['tss'] = 'TSS';
                } else {
                    $index['tss'] = '';
                }
				$index['update']="users.php?section=update&id=$index[user_id]$fac_text&letter=$_REQUEST[letter]";
                $index['log']="userlog.php?section=add&user_id=$index[user_id]$fac_text&letter=$_REQUEST[letter]";
                $index['bgcolor']=$bgcolor;

				
				if(isset($fac_only)) $call1='&fac_only'; else $call1='';
				if($index['user_level']==1) $index['button1']=" <button onClick='window.location=\"/users.php?section=view$call1&letter=$_REQUEST[letter]&unhide&user_id=$index[user_id]\"'>UnHide</button>";
						else $index['button1']=" <button onClick='window.location=\"/users.php?section=view$call1&letter=$_REQUEST[letter]&hide&user_id=$index[user_id]\"'>Hide</button>";

                $index['caqc_button']= "<button onclick=\"document.location='cv_review_print.php?generate=caqc&user_id=$index[user_id]'\">CAQC CV</button>";
                $index['letter']=$_REQUEST['letter'];

				$viewlist[]=$index;
			}
			
		}

		$tmpl->AddRows('viewlist',$viewlist);

		break;


	case "add":
    
		
		 $sql="SELECT name,topic_id FROM topics_research WHERE 1 ORDER BY name";
		 $topics=$db->Execute($sql);
		 $topic_options=$topics->GetMenu('topics','',true,true,8);
		 //echo $topic_options;
		 
		 $sql="SELECT name,code FROM user_types WHERE 1";
		 $emp_types=$db->Execute($sql);
		 $emp_type_options=$emp_types->GetMenu('emp_type','Select an Employee Type');
		 
		 $sql="SELECT name,message_id FROM messages WHERE 1";
		 $messages=$db->Execute($sql);
		 $which_message=$messages->GetMenu('messages');
		 
		 $sql="SELECT name,department_id FROM departments ORDER BY name";
		 $departments=$db->Execute($sql);
         $department2_list=$departments->GetMenu('department2','None');
         $departments->MoveFirst();
		 $department_list=$departments->GetMenu('department','None');
		 
		 foreach(array(''=>'','Tenured'=>'T','Tenure-Track'=>'TN','Term-Certain'=>'TC') as $key=>$status) {
	            
	            $emp_status_options.="<option value='$status' $sel>$key</option>\n";
	        }
	
    	 
		 
		 $tmpl->AddVars('add',array(    'topic_options'=>$topic_options,
                                        'emp_type_options'=>$emp_type_options,
                                        'emp_status_options'=>$emp_status_options,
                                        'which_message'=>$which_message,
                                        'department_list'=>$department_list,
                                        'department2_list'=>$department2_list ));
         $tmpl->setAttribute("add","visibility","visible");
         $hdr->AddVar("header","title","Users: Add New");
		
		

        break;

	case "update":
        
		$sql = "	SELECT users_disabled.user_id as disabled,
					users.*,profiles.*,users_ext.*,users_oneline.*
					FROM users 
					left join profiles using (user_id) 
					left join users_ext using (user_id) 
					left join users_disabled using (user_id)
                    left join users_oneline using (user_id)
					WHERE user_id=$_REQUEST[id]";
		
		$values=$db->GetRow($sql);
		if($values){
			
			$sql="SELECT topic_id FROM user_topics_filter WHERE user_id=$_REQUEST[id]";
	        $objects=$db->GetAll($sql);
	        if(count($objects) > 0) {
	             foreach($objects as $object) $ids[] = $object['topic_id'];  
	        }
	        $sql="SELECT name,topic_id FROM topics_research WHERE 1 ORDER BY name";
	        $topics=$db->Execute($sql);
	        
	        $ids=array();
	        if(is_array($objects)) foreach($objects as $object) $ids[] = $object['topic_id'];
	        // GetMenu2 picks the 2nd parameter for the selected items
			$values['topic_options']=$topics->GetMenu2('topics',$ids);
		
			if($values['disabled']) $values['disabled']="checked"; else $values['disabled']="";
			if($values['user_level']==1) $values['hidden']="checked"; else $values['hidden']="";
			//$sql="SELECT * FROM users_oneline WHERE user_id=$_REQUEST[id]";
			//$oneline=$db->getRow($sql);
			//if($oneline) $oneline_text=$oneline['oneline']; else $oneline_text='';
			
			
			
			$sql="SELECT name,department_id FROM departments WHERE 1 ORDER BY name";
			$depts=$db->Execute($sql);
			if($depts) {
				$values['department_list']=$depts->GetMenu2('department',$values['department_id']);
				$depts->MoveFirst();
				$values['department2_list']=$depts->GetMenu2('department2',$values['department2_id']);
			}
				
			$sql="SELECT name,code FROM user_types WHERE 1";
	        $emp_types=$db->Execute($sql);
	        $values['emp_type_options']=$emp_types->GetMenu2('emp_type',$values['emp_type']);
	
			if($values['mail_events']) $values['mail_events']='checked'; else $values['mail_events']='';
			if($values['mail_deadlines']) $values['mail_deadlines']='checked'; else $values['mail_deadlines']='';
			if($values['feature']) $values['feature']='checked'; else $values['feature']='';
			if($values['browser']) $values['browser']='checked'; else $values['browser']='';
			if(!isset($_REQUEST['letter'])) $values['letter']='A'; else $values['letter']=$_REQUEST['letter'];
			if(isset($_REQUEST['fac_only'])) $values['fac_only']='&fac_only'; else $values['fac_only']='';
			$values['picture_button'] = ($db->GetRow("SELECT * FROM pictures_associated WHERE object_id=$_REQUEST[id] AND table_name='users'"))?
				"<br><br><button type='button' onClick=\"window.location='pictures-associate.php?section=update&id=$_REQUEST[id]&table_name=users'\">View Associated Images</button>":"";
	            
	        $values['emp_status_options']="";
	        foreach(array(''=>'','Tenured'=>'T','Tenure-Track'=>'TN','Term-Certain'=>'TC') as $key=>$status) {
	            if($status==$values['emp_status']) $sel='selected'; else $sel='';
	            $values['emp_status_options'].="<option value='$status' $sel>$key</option>\n";
	        }
	        if($values['tss']) $values['tss']='checked'; else $values['tss']='';
	        if($values['start_date']==0) $values['start_date']=''; else $values['start_date']=$values['start_date'];
	        if($values['tss_start']==0) $values['tss_start']=''; else $values['tss_start']=$values['tss_start'];
	        $values['profile_short']=stripslashes($values['profile_short']);
			$values['profile_ext']=stripslashes($values['profile_ext']);
            $values['id']=$_REQUEST['id'];
			
			$tmpl->AddVars('update',$values);
			
            //Activity Tracking Stuff
            
           
            $tmpl->setAttribute("viewcat","visibility","visible");
            $tmpl->AddVar("viewcat","cat","All");
            $sortby=(isset($_REQUEST['sortby'])) ? $_REQUEST['sortby'] : 'date';
            if(isset($_REQUEST['dir'])) $dir=$_REQUEST['dir'];
            if($sortby=='date' && !isset($dir)) $dir='desc'; 
            if($sortby=='name' && !isset($dir)) $dir='asc'; 
            $altdir=($dir=='asc') ? 'desc' : 'asc';
            //secondary sort needed too
            if($sortby=='date') $sortby2='name asc';
            else $sortby2='date desc';
            $tmpl->AddVar("viewcat","dateurl","users.php?section=update&id=$_REQUEST[id]&sortby=date&dir=$altdir");
             
            //if($_REQUEST['cat']=='All') $sortby="category, $sortby"; 
            $sql="SELECT users_log.category, users_log.date, users_log.log_id,
            users_log.contents  as contents, CONCAT(users.last_name,', ',users.first_name) as name FROM users_log LEFT JOIN users ON(users_log.user_id=users.user_id) WHERE  users.user_id=$_REQUEST[id] ORDER BY $sortby $dir, $sortby2";
            
            $list=$db->GetAll($sql);
           // print_r ($list);
            if(count($list)>0){
                foreach($list as $key=>$item) $list[$key]['contents']=nl2br($item['contents']);
                $tmpl->AddRows("catlist",$list);
            }
            else {
                //no entries
            }
            
            
            
			$tmpl->setAttribute("update","visibility","visible");
         	$hdr->AddVar("header","title","Users: Update $values[first_name] $values[last_name]");
		}
		break;
        
    
    
    case "log":
        
        $tmpl->setAttribute("log","visibility","visible");
        if(isset($_REQUEST['user_id'])) if($_REQUEST['user_id']>0) {
            $user=$db->getRow("SELECT * FROM users WHERE user_id=$_REQUEST[user_id]");
            if(count($user)>0) {
                
            }     
        }
        
    break;
	}
}

$hdr->AddVar('header','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');


?>