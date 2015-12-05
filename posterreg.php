<?php
require_once('includes/global.inc.php');
ini_set("include_path", '/home/scholviu/php:' . ini_get("include_path")  );

$tmpl=loadPage("posterform", 'Poster Submission');
//showMenu("research_office");

if (sessionLoggedin()) {
	$tmpl->SetAttribute('form','visibility','visible');

	// _submit_check flag indicates the form was submitted
	if (array_key_exists('_submit_check',$_POST)) {
	    // If validate_form() returns errors, pass them to show_form()
	    if ($form_errors = validate_form()) {
	       $errorHtml = "<b>Form not submitted.  Errors were encountered.  Please fix the errors below and resubmit :</b>";
	        $errorHtml .= "<font color='red'><ul style='margin-left: 20px;'>";
	        foreach($form_errors as $error) {
	            $errorHtml .= "<li>$error</li>";
	        }
	        $errorHtml .= "</ul></font><br>";
	        $tmpl->addVar("FORM", 'FORM_ERRORS', $errorHtml);
	        show_form();
	    } else {
	        // The submitted data is valid, so process it
	        process_form();
	    }
	} else {
	    // The form wasn't submitted, so display
	    show_form();
	}
} // logged in

else {
	$tmpl->setAttribute('loginplease','visibility','visible');
	$tmpl->displayParsedTemplate('page');
}	


/*
 * Show the form
 */
function show_form() {
    global $tmpl,$db;
    
    //Recover the data already submitted
    if(isset($_POST['primaryNameFirst'])) $tmpl->addVar("FORM", 'primaryNameFirst', $_POST['primaryNameFirst']);
    if(isset($_POST['primaryNameLast'])) $tmpl->addVar("FORM", 'primaryNameLast', $_POST['primaryNameLast']);
    if(isset($_POST['studentid'])) $tmpl->addVar("FORM", 'studentid', $_POST['studentid']);
    if(isset($_POST['email'])) $tmpl->addVar("FORM", 'email', $_POST['email']);
    if(isset($_POST['where'])) $tmpl->addVar("FORM", 'where', $_POST['where']);
    if(isset($_POST['title'])) $tmpl->addVar("FORM", 'title', $_POST['title']);

    if(isset($_POST['course'])) $tmpl->addVar("FORM", 'course', $_POST['course']);
    if(is_uploaded_file($_FILES['filename']['tmp_name'])) $tmpl->addVar("FORM", 'filename', $_FILES['filename']['name']);
   

	
    $tmpl->addVar("FORM", 'FORM', "block");
    $tmpl->addVar("FORM", 'SUCCESS', "none");
    $tmpl->addVar("FORM", 'SRD', "none");

    $departments = getDepartments($_POST['department']);
    $tmpl->addRows('DEPARTMENT_OPTIONS', $departments);

    $supervisors = getSupervisors($_POST['supervisor']);
    $tmpl->addRows('SUPERVISOR_OPTIONS', $supervisors);
    
    $registrations= getRegistrations($_POST['registration']);
    $tmpl->addVar('FORM','REGISTRATIONS',$registrations);
    
    $username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);
    $tmpl->addVar('FORM','FORM_CREATE_ID',$user['user_id']);

    $tmpl->displayParsedTemplate('page');
}

/*
 * Validate the form
 */
function validate_form() {

    $required = array('Registration' => 'registration',
                      'Where it will be displayed' => 'where',
                      
                      'Item Title' => 'title',
                     
                );
    foreach($required as $key => $field) {
        $value = trim($_POST[$field]);
        
        if (empty($value)) {
            $errors[] = "'" . $key . "'" . " field is required";
            if(!empty($_FILES['filename']['tmp_name'])) $errors[] = "Please resubmit your file";
            
        }
    }
    if($_FILES['filename']['error'] == 1 || $_FILES['filename']['error'] == 2) $errors[] = 'Size limit exceeded';
    elseif($_FILES['filename']['error'] !=0) $error[]='Error uploading file';
    if(empty($_FILES['filename']['tmp_name']))
    	$errors[] = "A file is required";
       // print_r($_FILES);

    return $errors;
}

/**
 * Save the form to the database
 */
function process_form() {
    global $tmpl, $db, $configInfo;

    
    $program = mysql_escape_string($_POST['program']);
    $department = $_POST['department'];
    $course = mysql_escape_string($_POST['course']);
    $supervisor = $_POST['supervisor'];
    $pref = $_POST['pref'];
    //$hreb = $_POST['hreb'];
    //$hreb2 = $_POST['hreb2'];
    $title = mysql_escape_string($_POST['title']);
    //$foip = $_POST['foip'] == 'yes' ? 1 : 0;
    if(!isset($_POST['form_create_id'])) $id=0;
    else $id=$_POST['form_create_id'];
    
    //file processing
	if(is_uploaded_file($_FILES['filename']['tmp_name'])){
		//print_r($_FILES['filename']);
		$filesize=$_FILES['filename']['size'];
		$ext=explode(".",$_FILES['filename']['name']);
		$ext_el=sizeof($ext)-1;  //in case theres another . in the filename
		$filename_noext="printfile".mktime();
		$filename=$filename_noext.".".$ext[$ext_el];
		
		copy ($_FILES['filename']['tmp_name'],$configInfo['upload_root'].'posters/'.$filename);
		//echo("Copying ".$_FILES['filename']['tmp_name']." to ".$configInfo['upload_root'].'posters/'.$filename);
		unlink($_FILES['filename']['tmp_name']);
	}
	
	if($filesize>1000000) $filesize=round($filesize/1000000) . 'Mb';
	elseif($filesize>1000) $filesize=round($filesize/1000) . 'kb';
	else $filesize = $filesize . 'bytes';

    $query = "INSERT INTO poster_reg
                    (
                      title, submit_date, filename, form_create_id)
                     VALUES ( '$title', NOW(), '$filename',$_POST[registration])";

    $db->Execute($query);
    $srdRegId = mysql_insert_id();
	
	//get the person info
	$username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);

    // send an email to notify ORS
    notifyORS(array('first' => $user['first_name'],'last' => $user['last_name'],'title' => $title,'format' => $pref,'filesize'=>$filesize));


    $tmpl->addVar("FORM", 'FORM', "none");
    $tmpl->addVar("FORM", 'SUCCESS', "block");
    if(isset($_POST['srd'])){
    	//check if they are already registered
    	//The SRD date is the 'schoolyear' year
    	$srd_year=GetSchoolYear(time());
    	
    	$sql="SELECT * FROM srd_reg WHERE studentid='$studentId' 
    	AND (
    		(YEAR(submit_date)=$srd_year 
    		AND MONTH(submit_date)>=1 
    		AND MONTH(submit_date)<6) 
    		OR
    		(YEAR(submit_date)=$srd_year-1
    		AND MONTH(submit_date)>5 
    		AND MONTH(submit_date)<=12)
    		)";
    	$prev=$db->GetAll($sql);
    	if(count($prev)>0) $response="You already appear to have one or more posters/presentations registered under your student ID. Click below if this is a new one. ";
    	else $response='';
    	$url="studentregistration.php?primaryNameFirst=$first&primaryNameLast=$last&studentid=$studentId&email=$email&department=$department&course=$course&supervisor=$supervisor&title=$title";
    	$tmpl->addVar("FORM", 'SRD', "block");
    	$tmpl->addVar("FORM",'response',$response);
    	$tmpl->addVar("FORM",'url',$url);
    }
    $tmpl->displayParsedTemplate('page');

}

/*
 * Get a list of all departments
 */
function getDepartments($existing) {
    global $db;

    $sql = "SELECT department_id, name FROM departments WHERE division_id <> 0 ORDER BY name";
    $departments = $db->getAll($sql);
	foreach($departments as $key=>$department){
		if($department['department_id']==$existing) $departments[$key]['selected']='selected=\'selected\'';
		else $departments[$key]['selected']='';
	}
    return $departments;
}

/**
 * Get a list of potential supervisors
 *
 */
function getSupervisors($existing) {
    global $db;

    $sql = "SELECT user_id, CONCAT(last_name, ', ', first_name) AS name
            FROM users
            WHERE emp_type != 'STUDENT' 
            ORDER BY name";
    $supervisors = $db->getAll($sql);
    foreach($supervisors as $key=>$supervisor){
		if($supervisor['user_id']==$existing) $supervisors[$key]['selected']='selected=\'selected\'';
		else $supervisors[$key]['selected']='';
	}

    return $supervisors;
}

function getRegistrations($existing) {
	global $db;
	$username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);
	$regs= $db->Execute("SELECT CONCAT(LEFT(create_name,40),' (',YEAR(modified),'-',MONTH(modified),'-',DAY(modified),')') as name,form_create_id as id from forms_create WHERE user_id=$user[user_id]");
	if($regs->RecordCount()>0) return $regs->GetMenu2('registration',$existing,true,false);
	else return "<SELECT name='registration'><OPTION value='0'>No registrations on file</OPTION></SELECT>";
	
}

/**
 * Send an email to notify ORS
 *
 * @param $details - the details of the submission
 */
function notifyORS($details) {
    //require_once('classes/Mail/MailQueue.php');
    //require_once('classes/Mail/Email.php');
    global $configInfo;
    ini_set("include_path", '/home/scholviu/php:' . ini_get("include_path")  );
    //print_r( $configInfo['email_db_options']);
    require_once "Mail/Queue.php";
    
    $mail_queue = new Mail_Queue( $configInfo['email_db_options'], $configInfo['email_options'] );
	//$mime = new Mail_mime();
	
	$from = 'trevor.davis@viu.ca';
	$from_name = 'SRD Bot';

    $recipient1 = 'kathryn.jepson@viu.ca';
    $recipientName1 = 'Kathryn J';
    $recipient2 = 'trevor.davis@viu.ca';
    $recipientName2 = 'Trevor Davis';
    $subject = 'Poster Submission';
    $emailBody = sprintf('A student poster was submitted:

                          First Name : %s
                          Last Name: %s
                          Title: %s
                          Size: %s
                          
                 ', $details['first'], $details['last'], $details['title'], $details['filesize']);

	$from_params = empty( $from_name ) ? '<' . $from . '>' : '"' . $from_name . '" <' . $from . '>';
	$recipient_params = empty( $recipientName1 ) ? '<' . $recipient1 . '>' : '"' . $recipientName1 . '" <' . $recipient1 . '>';
	
	$recipient_list=array('Kathryn J <kathryn.jepson@viu.ca>', 'Trevor D <trevor.davis@viu.ca>');
	foreach($recipient_list as $recipient){
		$mime = new Mail_mime();
		$hdrs = array(
			        'From' => $from_params,
			        'To' => $recipient,
			        'Subject' => $subject,
					);
		
		
		
		
		$mime->setTXTBody( $emailBody );
		$body = $mime->get();
		$hdrs = $mime->headers( $hdrs );
			    
			
		$queueMailId = $mail_queue->put( $from, $recipient, $hdrs, $body );
			    //echo("Mail queue ID = $queueMailId <br>");
			
		if ( $configInfo["email_send_now"] ) {
			$send_result = $mail_queue->sendMailById( $queueMailId );
		}
	}//recipient loop
	
	if($send_result != 1){
		echo $send_result;
		echo("<br>");
	}





/*

    $email1 = new Email(
        $recipient1,
        $recipientName1,
        $subject,
        $emailBody
    );
    $email2 = new Email(
        $recipient2,
        $recipientName2,
        $subject,
        $emailBody);

    $emails = array($email1, $email2);

    $mailQueue = new MailQueue($emails);
    $mailQueue->queueAllMail();
*/
}

