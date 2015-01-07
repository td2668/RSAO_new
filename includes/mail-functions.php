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
	global $debug;
	global $server_name;
	global $redirects;
    global $db_options;
    global $mail_options;
    global $mail_file_path;
    
    set_time_limit(60);
	error_reporting(E_ALL);
		
	if (is_array(mysqlFetchRow("users_disabled", "user_id=$user[user_id]"))) $user['visits']=0;
	
	if (($user['visits'] == 1 && $values['testmail']==false) || ($values['testmail'] && $user['sys_admin']==1) ) {
		//Set up variables for search & replace
		
	
		if(!(isset($values['from']))) $values['from']="research@mtroyal.ca";
			//$mail->setFrom($values['from']);		
				
		//usleep(250000);
		set_time_limit(120); // to avoid a script timeout

		$values['body']=str_replace("@firstname@",$user['first_name'],$values['body']);
		$values['body']=str_replace("@lastname@",$user['last_name'],$values['body']);
		$values['body']=str_replace("@username@",$user['username'],$values['body']);
		
        $mail_queue =& new Mail_Queue($db_options, $mail_options); 
        $mime =& new Mail_mime();
		
		//Convert the HTML laden text to a format readable by textonly email clinets. 
		$textsection = $values['body'];
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

		$html_footer = "<br><br><hr width='200' align='left'>
<font color='#330000'>ORS Notification System</font><br>
";

        $mime->setHTMLBody($htmlsection . $html_footer);

        //Headers
        $from             = 'research@mtroyal.ca';
        $from_name        = 'Research Services';
        $recipient        = $user['email'];   								//Will later be $user[email] OR 'tdavis@mtroyal.ca'
        $recipient_name   = $user['first_name'] . " " . $user['last_name'];  	//Will later be $user[first_name] $user[last_name]

        $from_params      = empty($from_name) ? '<'.$from.'>' : '"'.$from_name.'" <'.$from.'>';
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
        
		
		
 //       echo("From: $from<br>Recipient: $recipient<br>Headers:"); print_r($hdrs); echo "<br><br>Body: $body";        
        $result = $mail_queue->put ($from, $recipient , $hdrs, $body );
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
}

function list_users ($user, $key, &$count) {
	if ($user['visits']!=0) {
		//echo("$user[first_name] $user[last_name]<br>\n");
		$count++;	
	}
}

?>
