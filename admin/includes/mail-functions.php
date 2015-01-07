<?php

//require_once('htmlMimeMail5/htmlMimeMail5.php');
require_once('mail-config.php');



function reset_user (&$user, $key) {
	//reuse the visits field for a mail/nomail flag
	$user['visits']=0;
}
function check_topics (&$user, $key, $topic_list) {
	$user_topics = mysqlFetchRows("user_topics_filter","user_id=$user[user_id]");
	$research_topics = mysqlFetchRows("topics_research");
	$mail_topics = explode(",",$topic_list);
	if(is_array($user_topics)) {
		foreach($user_topics as $user_topic) {
			if(in_array($user_topic['topic_id'],$mail_topics)) {$user['visits']=1;break;}
		}
	}//isarray($topics)
	else {
		//there is no specific topic(s) for a user, so DONT mail (changed in ver 2)
		$user['visits'] = 0;
	}
}
function internal (&$user, $key) {
		//$test = stristr($user['email'],"cariboo");
		//echo("Testing $user[email] : $test\n");

		if(stristr($user['email'],"mtroyal.ca") === false) $user['visits']=0;
}

function strip_HTML($email_body)
{
	$search = array(
        "/\r/",                                  // Non-legal carriage return
        "/[\n\t]+/",                             // Newlines and tabs
        '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
        '/<!--.*-->/',                         // Comments -- which strip_tags might have problem a with
        '/<h[123][^>]*>(.+?)<\/h[123]>/ie',      // H1 - H3
        '/<h[456][^>]*>(.+?)<\/h[456]>/ie',      // H4 - H6
        '/<p[^>]*>/i',                           // <P>
        '/<br[^>]*>/i',                          // <br>
        '/<b[^>]*>(.+?)<\/b>/ie',                // <b>
        '/<i[^>]*>(.+?)<\/i>/i',                 // <i>
        '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
        '/<li[^>]*>/i',                          // <li>
        '/<a href="([^"]+)"[^>]*>/ie', // <a href="">
		"/<a href='([^']+)'[^>]*>/ie", // <a href=''>
        '/<hr[^>]*>/i',                          // <hr>
        '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
        '/<td[^>]*>(.+?)<\/td>/i',               // <td> and </td>
        '/&nbsp;/i',
        '/&quot;/i',
        '/&gt;/i',
        '/&lt;/i',
        '/&amp;/i',
        '/&copy;/i',
        '/&trade;/i'
    );
	$replace = array(
        '',                                     // Non-legal carriage return
        '',                                    // Newlines and tabs
        '',                                     // <script>s -- which strip_tags supposedly has problems with
        '',                                     // Comments -- which strip_tags might have problem a with
        "strtoupper(\"\n\n\\1\n\n\")",          // H1 - H3
        "ucwords(\"\n\n\\1\n\n\")",             // H4 - H6
        "\n\n",                                 // <P>
        "\n",                                   // <br>
        'strtoupper("\\1")',                    // <b>
        '_\\1_',                                // <i>
        "\n\n",                                 // <ul> and </ul>
        "\t*",                                  // <li>
        "\"( \\1 ) \"",                                     // <a href=>
		"\\1",                                     // <a href=>
        "\n-------------------------\n",        // <hr>
        "\n\n",                                 // <table> and </table>
        "\n",                                   // <tr> and </tr>
        "\t\t\\1\n",                            // <td> and </td>
        ' ',
        '"',
        '>',
        '<',
        '&',
        '(c)',
        '(tm)'
    );

	$textsection = preg_replace($search,$replace,$email_body);
    unset($search);
    unset($replace);
	return $textsection;

}

function remove_html_body($email_body)
{
	$search=array(
	"/<HTML>/i",
	"/<\/HTML>/i",
	"/<HEAD>.*<\/HEAD>/miU",
	"/<body[^>]*>/i",
	"/<\/body>/i"
	);
	$replace=array( "","","","","");

	$htmlsection = preg_replace($search,$replace,$email_body);
	return $htmlsection;
}

function mailout ($user, $key, $values) {
    global $db_options;
    global $mail_options;
    global $mail_file_path;

    set_time_limit(60);
	//error_reporting(E_ALL);

    $success = false;
    $sendMail = true;

    if (is_array(mysqlFetchRow("users_disabled", "user_id=$user[user_id]"))) {
        $sendMail = false;
    }

    if (($sendMail == true && $values['testmail'] == false) ||
        ($values['testmail'] && $user['sys_admin'] == 1)) {
        //Set up variables for search & replace
		

		//if(!(isset($values['from']))) $values['from']="research@mtroyal.ca";
			//$mail->setFrom($values['from']);

		//usleep(250000);
		set_time_limit(120); // to avoid a script timeout

		$values['body']=str_replace("@firstname@",$user['first_name'],$values['body']);
		$values['body']=str_replace("@lastname@",$user['last_name'],$values['body']);
		$values['body']=str_replace("@username@",$user['username'],$values['body']);
		
        $mail_queue =& new Mail_Queue($db_options, $mail_options);
        $mime =& new Mail_mime();

		//Convert the HTML laden text to a format readable by textonly email clinets.
		$textsection = htmlentities($values['body'],ENT_QUOTES) ;
		$textsection = strip_HTML($textsection);
		$textsection = strip_tags($textsection);

		//Clean out newline-space pairs?
		//$values['body'] = preg_replace("/\n[[ ]]+\n/", "\n", $values['body']);

		//Clean out > 3 CRs in a row
		//	$values['body'] = preg_replace("/[\n]{3,}/", "\n\n", $values['body']);
		$text_footer="\n--------------------------------MRU ORS Notification System\n";
		$textsection = $textsection . $text_footer;

		$mime->setTXTBody($textsection);

		 // Do the HTML Work
		$htmlsection=$values['body'];

		//Remove all head and body tags in case they are there. It looks like this is redundant as they are removed beforehand.
		//$htmlsection = remove_html_body($htmlsection);

		//Add header, body, and signature lines.<br />

        if (isset($values['from_email'])) {
            $from_email = $values['from_email'];
        }
        else {
            $from_email = 'research@mtroyal.ca';
        }
        if ($from_email == '') {
            $from_email = 'research@mtroyal.ca';
        }
        if (isset($values['from_name'])) {
            $from_name = $values['from_name'];
        }
        else {
            $from_name = 'Research Services';
        }
        if ($from_name == '') {
            $from_name = 'Research Services';
        }

        $html_footer = "<br><br><hr width='200' align='left'>";
        if ($from_name == 'Research Services') {
            $html_footer .= sprintf("
<font color='#660000'>ORS Notification System</font><br>
<div style='font-size:9px; font-family: Arial, Helvetica, sans-serif; color:#660000'>Click here to unsubscribe or change your topic preferences : <a href='http://research.mtroyal.ca/contactpreferences.php?userid=%s'>Change Contact Preferences</a></div><br>
", $user['user_id']);
        }
		
        $mime->setHTMLBody($htmlsection . $html_footer);

        //Headers


        $recipient        = $user['email'];   								//Will later be $user[email] OR 'tdavis@mtroyal.ca'
        $recipient_name   = $user['first_name'] . " " . $user['last_name'];  	//Will later be $user[first_name] $user[last_name]

        $from_params      = empty($from_name) ? '<'.$from_email.'>' : '"'.$from_name.'" <'.$from_email.'>';
        $recipient_params = empty($recipient_name) ? '<'.$recipient.'>' : '"'.$recipient_name.'" <'.$recipient.'>';
        $hdrs = array(
                         'From'    => $from_params,
                         'To'      => $recipient_params,
                         'Subject' => $values['subject'],
        );

		if(isset($values['filename'])){
			$mime->addAttachment($mail_file_path . $values["filename"]);
		}
//		if(!isset($user[file_name])){echo "There is no file";			print_r ($user);}

		$body = $mime->get();
        $hdrs = $mime->headers($hdrs);

		

  //      echo("From: $from<br>Recipient: $recipient<br>Headers:"); print_r($hdrs); echo "<br><br>Body: $body";
        $result = 'MAILQUEUE_ERROR';
        if(isset($recipient) && strlen($recipient) > 0) {  // don't send to blank email addresses
            //var_dump($recipient);
            $result = $mail_queue->put ($from_email, $recipient , $hdrs, $body );
        }
        $success = $result;
        unset($mime);
        unset($mail_queue);
        unset($body);
        unset($hdrs);



		if ($result!='MAILQUEUE_ERROR') {
			//if ($debug)	echo("(debug) Sent to $user[first_name] $user[last_name]\n");
			$date=mktime();
			$date_text = date("M j y g:i a", $date);
			$values2=array('null',addslashes($values['subject']),$user['email'],$date,$date_text);
			$result = mysqlInsert("maillog",$values2);
			if ($result != 1) echo("Error in logging: $result<br>");
		}
		else {
			$date=mktime();
			$date_text = date("M j y g:i a", $date);
			$values['subject'] .= " SENDING ERROR.";
			$values2=array('null',$values['subject'],$user['email'],$date,$date_text);
			$result = mysqlInsert("maillog",$values2);
			if ($result != 1) echo("Error in logging: $result<br>");
		}

	}

    return $success;
}

function list_users ($user, $key, &$count) {
	if ($user['visits']!=0) {
		//echo("$user[first_name] $user[last_name]<br>\n");
		$count++;
	}
}

/**
 * Get the recipients for this email based on the filtered recipient options
 *
 * @param $options - who the recipients are.
 *                       array(
 *                         'ft_faculty'      => 0/1
 *                         'pt_faculty'      => 0/1,
 *                         'management'      => 0/1,
 *                         'support'         => 0/1,
 *                         'outside'         => 0/1,
 *                         'chairs'          => 0/1,
 *                         'deans'           => 0/1,
 *                         'tss'             => 0/1,
 *                         'srd'             => 0/1,
 *					    	'strd'            => 0/1,
 *                         'topics_research' => array[topicsIds],
 *                         'divisions'       => array[divisonIds],
 *                         'userlist'        => array[userIds])
 * @param bool $override - whether to disregard their contact preferences (based on type)
 * @param int $type - the type of email being sent - news or deadline
 * @return array- $users - the recipients
 */
function recipientBuilder($options, $override = false, $type = NEWS) {

    $sql = getBaseQuery(); // initialize with the base query, we'll append the where clause in most instances

    $users = array(); // the recipients - we append to this as we go

    /*
     * Build the list of recipients based on what options were selected.
     */
    if(isset($options['ft_faculty']) && $options['ft_faculty'] == 1) {
        $sql .= "WHERE users.emp_type = 'FACL'";
        getRecipients($sql, $users, $override, $type);
    }
    if(isset($options['pt_faculty']) && $options['pt_faculty'] == 1) {
        $sql .= "WHERE users.emp_type = 'PTFAC'";
        getRecipients($sql, $users, $override, $type);
    }
    if(isset($options['management']) && $options['management'] == 1) {
        $sql .= "WHERE users.emp_type = 'MAN'";
        getRecipients($sql, $users, $override, $type);
    }
    if(isset($options['support']) && $options['support'] == 1) {
        $sql .= "WHERE users.emp_type = 'SUPP'";
        getRecipients($sql, $users, $override, $type);
    }
    if(isset($options['outside']) && $options['outside'] == 1) {
        $sql .= "WHERE profiles.email NOT LIKE  '%@mtroyal.ca%' AND profiles.email <> ''";
        getRecipients($sql, $users, $override, $type);
    }
    if(isset($options['chairs']) && $options['chairs'] == 1) {
        $sql .= "WHERE departments.chair = users.user_id";
        getRecipients($sql, $users, $override, $type);
    }
    if(isset($options['deans']) && $options['deans'] == 1) {
        $sql = "SELECT *, LOWER(profiles.email) AS email FROM divisions
                  LEFT JOIN users ON divisions.dean = users.user_id
                  LEFT JOIN profiles ON profiles.user_id = users.user_id
                  LEFT JOIN users_ext ON users_ext.user_id = users.user_id
                  WHERE  (profiles.email <> '') AND (profiles.email IS NOT NULL)";
        getRecipients($sql, $users, true);
    }
    if(isset($options['tss']) &&$options['tss'] == 1) {
        $sql .= "WHERE users_ext.tss = 1";
        getRecipients($sql, $users, $override, $type);
    }
    //Abstract flag
    if(isset($options['abstract']) && $options['abstract'] == 1) $abstract="AND LENGTH(descrip) < 2"; else $abstract='';
    // Student Research Day Participants
    $srd_year=GetSchoolYear(time());
    if(isset($options['srd']) && $options['srd'] == 1) {
        $sql = "SELECT firstName AS first_name, lastName AS last_name, LOWER(email) AS email
                FROM srd_reg
                WHERE (email <> '') AND (email IS NOT NULL)
                AND srd=1
                $abstract
                AND (
		    	    (YEAR(submit_date)=$srd_year
		    		AND MONTH(submit_date)>=1
		    		AND MONTH(submit_date)<6)
		    	  OR
		    		(YEAR(submit_date)=$srd_year-1
		    		AND MONTH(submit_date)>5
		    		AND MONTH(submit_date)<=12)
		    		)
                ORDER BY email ";
        getRecipients($sql, $users, $override, SRD);
    }
    // MM Student Research Day Participants
    if(isset($options['strd']) && $options['strd'] == 1) {
        $sql = "SELECT firstName AS first_name, lastName AS last_name, LOWER(email) AS email
                FROM srd_reg
                WHERE (email <> '') AND (email IS NOT NULL)
                AND pref='multimedia'
                $abstract
                AND (
		    	    (YEAR(submit_date)=$srd_year
		    		AND MONTH(submit_date)>=1
		    		AND MONTH(submit_date)<6)
		    	  OR
		    		(YEAR(submit_date)=$srd_year-1
		    		AND MONTH(submit_date)>5
		    		AND MONTH(submit_date)<=12)
		    		)
                ORDER BY email ";
        getRecipients($sql, $users, $override, SRD);
    }
    if(isset($options['topics_research']) && (strlen($options['topics_research']) > 0) && $options['topics_research'] != "0") {
        $topics_research = explode(",", $options['topics_research']);
        $sql = "SELECT *, LOWER(profiles.email) AS email FROM user_topics_filter
                LEFT JOIN users ON user_topics_filter.user_id = users.user_id
                LEFT JOIN profiles ON profiles.user_id = users.user_id
                LEFT JOIN users_ext ON users_ext.user_id = users.user_id
                WHERE user_topics_filter.topic_id IN (";
        foreach($topics_research as $key=>$topicId) {
            $sql .= $topicId;
            if($key < count($topics_research) - 1) $sql .= ", ";
        }
        $sql .= ")";
        getRecipients($sql, $users, $override, $type);
    }
    if(isset($options['divisions']) && (strlen($options['divisions']) > 0) && $options['divisions'] != "0") {
        $divisions = explode(",", $options['divisions']);
        $sql .= "WHERE divisions.division_id IN (";
        foreach($divisions as $key=>$divisionId) {
            $sql .= $divisionId;
            if($key < count($divisions) - 1) $sql .= ", ";
        }
        $sql .= ")";
        getRecipients($sql, $users, $override, $type);
    }
    if(isset($options['userlist']) && (strlen($options['userlist']) > 0)) {
        $userlist = explode(",", $options['userlist']);
        $sql = "SELECT *, LOWER(profiles.email) AS email FROM users
                LEFT JOIN profiles ON profiles.user_id = users.user_id
                LEFT JOIN users_ext ON users_ext.user_id = users.user_id
                LEFT JOIN departments ON departments.department_id = users.department_id
                WHERE users.user_id IN (";
        foreach($userlist as $key=>$user_id) {
            $sql .= $user_id;
            if($key < count($userlist) - 1) $sql .= ", ";
        }
        $sql .= ")";
        getRecipients($sql, $users, $override, $type);
    }

    return $users;
}

/*
 * Get uses from the database using the given sql statement
 *
 * @param $sql - the sql statement
 * @param $users - the list of users/recipients
 * @param $overridePref - disregard the users preferences for not receiving mail
 */
function getRecipients(&$sql, &$users, $overridePref = false, $type = NEWS)  {
    global $db;

    // If Student Research Day Participants - we have a special SQL statement
    if($type == SRD) {
        $existingUsers = $users;
        $newUsers = $db->getAll($sql);
    } else { // All other recipients - we append to the sql statment
        if($overridePref == false) {
            if($type == NEWS) {
                $sql .= " AND (users.mail_events = 1)";
            } elseif($type == DEADLINE) {
                $sql .= " AND (users.mail_deadlines = 1)";
            }
        }
        $sql .= " AND (profiles.email <> '') AND (profiles.email IS NOT NULL)
                  ORDER BY email ";
        $existingUsers = $users;
        $newUsers = $db->getAll($sql);
    }

    if(is_array($newUsers) && is_array($existingUsers)) {
        $users = array_merge($newUsers, $existingUsers);
    }

    $users = removeDuplicates($users); // remove duplicates

    //var_dump($sql);
    $sql = getBaseQuery(); // reset the query
}

/**
 * Compare function to be used with uSort for sorting users lists on email
 */
function cmp($a, $b) {
    if (strcmp($a['email'], $b['email']) == 0) {
        return 0;
    } else {
        return (strcmp($a['email'], $b['email']) > 0) ? 1 : -1; // reverse order
    }
}

/**
 * Remove  duplicates from the user list.  Duplicate is considered
 * having the same email address.
 *
 * @param $users
 */
function removeDuplicates($users) {
    usort($users, 'cmp');

    for ($i=1, $j=0, $n=count($users); $i<$n; ++$i) {
        if ($users[$i]['email'] == $users[$j]['email']) {
            unset($users[$i]);
        } else {
            $j = $i;
        }
    }

    return $users;
}

/*
 * The base query for getting users/recipients from the users table.
 */
function getBaseQuery()
{
    $sql = "SELECT *, LOWER(profiles.email) AS email FROM users
                LEFT JOIN profiles ON profiles.user_id = users.user_id
                LEFT JOIN users_ext ON users_ext.user_id = users.user_id
                LEFT JOIN departments ON departments.department_id = users.department_id
                LEFT JOIN divisions ON departments.division_id = divisions.division_id
                ";
    return $sql;
}


