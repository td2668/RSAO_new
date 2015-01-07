<?php
  require_once('includes/global.inc.php');
    //error_reporting(E_ALL );
    //ini_set('display_errors', 'On');
    
    $configInfo["email_send_now"] =  true;

  $tmpl=loadPage("srdregresponse",'SRD Registration');

  if(isset($_REQUEST['email'])) {
      //echo filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL);
      //if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) $response="The Email entered is not valid";
      if(!validEmail($_REQUEST['email'])) {
          $response= "<font color='red'>The email you entered was not valid - and since it's our main contact method it's the only mandatory field. Please use your browser's 'back' button to revise.</font>";
          
      }
  }
  
  //This is a check for a hidden field that should be blank - otherwise a bot got here first.
  if($_REQUEST['name']=='' && !isset($response)) {
   
      //Save the data
      $sql="INSERT into srd_reg SET
            studentname='". mysql_escape_string(isset($_REQUEST['studentname']) ? $_REQUEST['studentname'] : '') . "',
            studentid='". mysql_escape_string(isset($_REQUEST['studentid']) ? $_REQUEST['studentid'] : '') . "',
            email='". mysql_escape_string(isset($_REQUEST['email']) ? $_REQUEST['email'] : '') . "',
            program='". mysql_escape_string(isset($_REQUEST['program']) ? $_REQUEST['program'] : '') . "',
            department='". mysql_escape_string(isset($_REQUEST['department']) ? $_REQUEST['department'] : '') . "',
            course='". mysql_escape_string(isset($_REQUEST['course']) ? $_REQUEST['course'] : '') . "',
            supervisor='". mysql_escape_string(isset($_REQUEST['supervisor']) ? $_REQUEST['supervisor'] : '') . "',
            pref='". mysql_escape_string(isset($_REQUEST['pref']) ? $_REQUEST['pref'] : '') . "',
            other='". mysql_escape_string(isset($_REQUEST['other']) ? $_REQUEST['other'] : '') . "',
            hreb='". mysql_escape_string(isset($_REQUEST['hreb']) ? $_REQUEST['hreb'] : '') . "',
            hreb2='". mysql_escape_string(isset($_REQUEST['hreb2']) ? $_REQUEST['hreb2'] : '') . "',
            title='". mysql_escape_string(isset($_REQUEST['title']) ? $_REQUEST['title'] : '') . "',
            descrip='". mysql_escape_string(isset($_REQUEST['descrip']) ? $_REQUEST['descrip'] : '') . "',
            url='". mysql_escape_string(isset($_REQUEST['url']) ? $_REQUEST['url'] : '') . "',
            submit_date=NOW(),
            status='submitted';
            ";
      $result=$db->Execute($sql);
      //fire a message to the site admin to notify
      
      require_once "Mail/Queue.php";
      $mail_queue = new Mail_Queue( $configInfo['email_db_options'], $configInfo['email_options'] );
      $mime = new Mail_mime();

      $from = 'research@mtroyal.ca';
      $from_name = 'SRD Bot';


      if ( $configInfo["debug_email"] ) {
            $recipient = $configInfo["debug_email"];
            $recipient_name = $configInfo["debug_email_name"];
      } else {
        $recipient = 'research@mtroyal.ca';
        $recipient_name = 'ORS';
      }
      $from_params = empty( $from_name ) ? '<' . $from . '>' : '"' . $from_name . '" <' . $from . '>';
      $recipient_params = empty( $recipient_name ) ? '<' . $recipient . '>' : '"' . $recipient_name . '" <' . $recipient . '>';
      $hdrs = array(
        'From' => $from_params,
        'To' => $recipient_params,
        'Subject' => "Student Registered for SRD",
        );
    
      $message = "
Hi,

An SRD registration was submitted from email $_REQUEST[email] and ID $_REQUEST[studentid]

Your friendly SRD Bot.
        ";
    $mime->setTXTBody( $message );

    $body = $mime->get();
    $hdrs = $mime->headers( $hdrs );

    $queueMailId = $mail_queue->put( $from, $recipient, $hdrs, $body );

    if ( $configInfo["email_send_now"] ) {
        $send_result = $mail_queue->sendMailById( $queueMailId );
    }
      
      
  }
  if(!isset($response))$response="Thanks for submitting. <br><br>Now that we have your email we'll send updates directly. You can also follow us on Twitter at @MountRoyalSRD.";
  $tmpl->addVar('page','response',$response);
  $tmpl->displayParsedTemplate('page');
  
/**
Validate an email address.
Provide email address (raw input)
Returns true if the email address has the email 
address format and the domain exists.
*/
function validEmail($email)
{
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if
(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || 
 checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}


?>
