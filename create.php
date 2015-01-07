<?php
require_once('includes/global.inc.php');

if(student_logged_in()){

	$tmpl=loadPage("create", 'CREATE Registration');
	//showMenu("ugr_intro");
	
	// _submit_check flag indicates the form was submitted
	if (array_key_exists('_submit_check',$_POST)) {
	    // If validate_form() returns errors, pass them to show_form()
	    if ($form_errors = validate_form()) {
	       $errorHtml = "<b>Form not submitted.  Errors were encountered.  Please fix the errors below and resubmit :</b>";
	        $errorHtml .= "<ul>";
	        foreach($form_errors as $error) {
	            $errorHtml .= "<li>$error</li>";
	        }
	        $errorHtml .= "</ul>";
	        
	        $tmpl->addVar("PAGE", 'FORM_ERRORS', $errorHtml);
	        show_form();
	    } else {
	        // The submitted data is valid, so process it
	        process_form();
	    }
	} else {
	    // The form wasn't submitted, so display
	    show_form();
	}
}
else {
	//goto login page
	$tmpl=loadPage("st_login", 'Login');
	showMenu("index");
	$tmpl->addVar('page',"target","create.php");

	$tmpl->displayParsedTemplate('page');
}	
	/*
	 * Show the form
	 */
	function show_form() {
	    global $tmpl;
	    
	    //Modified by TD to include possibility of sending some or all parameters via POST
	    //And to carry data forward from an error-submission
	    
	    if(isset($_REQUEST['primaryNameFirst'])) $tmpl->addVar("PAGE", 'primaryNameFirst', stripslashes($_REQUEST['primaryNameFirst']));
	    if(isset($_REQUEST['primaryNameLast'])) $tmpl->addVar("PAGE", 'primaryNameLast', stripslashes($_REQUEST['primaryNameLast']));
	    if(isset($_REQUEST['studentid'])) $tmpl->addVar("PAGE", 'studentid', $_REQUEST['studentid']);
	    if(isset($_REQUEST['email'])) $tmpl->addVar("PAGE", 'email', $_REQUEST['email']);
	    if(isset($_REQUEST['where'])) $tmpl->addVar("PAGE", 'where', stripslashes($_REQUEST['where']));
	    if(isset($_REQUEST['title'])) $tmpl->addVar("PAGE", 'title', stripslashes($_REQUEST['title']));
	    
	    if(isset($_REQUEST['course'])) $tmpl->addVar("PAGE", 'course', $_REQUEST['course']);
	    
	
	    $tmpl->addVar("PAGE", 'FORM', "block");
	    $tmpl->addVar("PAGE", 'SUCCESS', "none");
	    $tmpl->addVar("PAGE", 'username',$_SESSION['username']);
	
	    $departments = getDepartments($_REQUEST['department']);
	    $tmpl->addRows('DEPARTMENT_OPTIONS', $departments);
	
	    $supervisors = getSupervisors($_REQUEST['supervisor']);
	    $tmpl->addRows('SUPERVISOR_OPTIONS', $supervisors);
	
	    $tmpl->displayParsedTemplate('page');
	}
	
	/*
	 * Validate the form
	 */
	function validate_form() {
	
	    $required = array('Given Name' => 'primaryNameFirst',
	                      'Surname' => 'primaryNameLast',
	                      'Student ID' => 'studentid',
	                      'Contact Email' => 'email',
	                      'Project Title' => 'title'
	                );
	    foreach($required as $key => $field) {
	        $value = trim($_POST[$field]);
	        if (empty($value)) {
	            $errors[] = "'" . $key . "'" . " field is required";
	        }
	    }
	
	    return $errors;
	}
	
	/**
	 * Save the form to the database
	 */
	function process_form() {
	    global $tmpl, $db;
	
	    $first = mysql_escape_string($_POST['primaryNameFirst']);
	    $last = mysql_escape_string($_POST['primaryNameLast']);
	    $studentId = mysql_escape_string($_POST['studentid']);
	    $email = mysql_escape_string($_POST['email']);
	    $program = mysql_escape_string($_POST['program']);
	    $department = $_POST['department'];
	    $course = mysql_escape_string($_POST['course']);
	    $supervisor = $_POST['supervisor'];
	    $pref = $_POST['pref'];
	    $hreb = $_POST['hreb'];
	    $hreb2 = $_POST['hreb2'];
	    $title = mysql_escape_string($_POST['title']);
	    $descrip = mysql_escape_string($_POST['descrip']);
	    $foip = $_POST['foip'] == 'yes' ? 1 : 0;
	    $srd=$_POST['srd'] == 'on' ? 1 : 0;
	    $strd=$_POST['strd'] == 'on' ? 1 : 0;
	    $url=$_POST['url'] = mysql_escape_string($_POST['url']);
	    //print_r($_POST);
	
	    $query = "INSERT INTO srd_reg
	                    (firstName, lastName, studentid, email, program, departmentId, course, supervisorId,
	                     pref, hreb, hreb2, title, submit_date, foip, descrip, srd, strd, url)
	                     VALUES ('$first', '$last', '$studentId',
	                             '$email', '$program', '$department', '$course',
	                             '$supervisor', '$pref', '$hreb',
	                             '$hreb2', '$title', NOW(), $foip, '$descrip','$srd','$strd','$url')";
	
	    $db->Execute($query);
	    $srdRegId = mysql_insert_id();
	
	    // save additional researchers
	    $numAdditionalStudents = intval($_POST['numResearchers']);
	    if($numAdditionalStudents > 0) {
	        $students = array();
	        for($i = 1; $i < $numAdditionalStudents + 1; $i++) {
	            $students[] = array('first' => mysql_escape_string($_POST['namesfirst_' . $i]),
	                                'last' => mysql_escape_string($_POST['nameslast_' . $i])
	                                );
	        }
	        foreach($students as $student) {
	            $sql = sprintf("INSERT into srd_researchers (`srd_reg_id`, `first`, `last`) VALUES (%s, '%s', '%s')",
	                           $srdRegId, $student['first'], $student['last']);
	            $db->Execute($sql);
	        }
	    }
	    
	    //Notify the Sci and Tech faculty if its one of their registrations
	    if($strd){
		    notifyST(array('first' => $first,
		                    'last' => $last,
		                    'title' => $title,
		                    'format' => $pref,
		                    'descrip' => $descrip
		                    )
		    );   
	    }
	
	    // send an email to notify ORS
	    notifyORS(array('first' => $first,
	                    'last' => $last,
	                    'title' => $title,
	                    'format' => $pref,
	                    'descrip' => $descrip,
	                    'srd' => $srd,
	                    'strd' => $strd
	                    )
	    );
	
	    // send an email to notify the student
	   
	
	 notifyStudent(array('first' => $first,
	                        'last' => $last,
	                        'email' => $email,
	                        'title' => $title,
	                        'format' => $pref,
	                        'descrip' => $descrip
	              )
	    );
	
	
	
	    $tmpl->addVar("PAGE", 'FORM', "none");
	    $tmpl->addVar("PAGE", 'SUCCESS', "block");
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
            WHERE emp_type = 'FACL' AND user_level < 2
            ORDER BY name";
    $supervisors = $db->getAll($sql);
    foreach($supervisors as $key=>$supervisor){
		if($supervisor['user_id']==$existing) $supervisors[$key]['selected']='selected=\'selected\'';
		else $supervisors[$key]['selected']='';
	}

    return $supervisors;
}

/**
 * Send an email to notify ORS
 *
 * @param $details - the details of the submission
 */
function notifyORS($details) {
	//include_once('Mail.php');
	//include_once('Mail/mime.php');
    require_once('classes/Mail/MailQueue.php');

    require_once('classes/Mail/Email.php');
	
    $recipient1 = 'jcameron@mtroyal.ca';
    $recipientName1 = 'Jerri-Lynne Cameron';
    $recipient2 = 'tdavis@mtroyal.ca';
    $recipientName2 = 'Trevor Davis';
    $subject = '[SRD Registration]';
    if($details['descrip']=='') $addit="No abstract entered..";
    else $addit='Abstract included';
    if($details['srd']) $srd='SRD'; else $srd='';
    if($details['strd']) $strd="S&T RD"; else $strd='';
    $emailBody = sprintf('A student research day registration occurred:

                          First Name : %s
                          Last Name: %s
                          Title: %s
                          Format: %s
                          Which Day: %s %s
                          %s
                 ', $details['first'], $details['last'], $details['title'], $details['format'], $srd, $strd, $addit);

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
	//print_r($emails);
    $mailQueue = new MailQueue($emails);
    $mailQueue->queueAllMail();
}

/**
 * Send an email to notify ORS
 *
 * @param $details - the details of the submission
 */
function notifyST($details) {
	//include_once('Mail.php');
	//include_once('Mail/mime.php');
    require_once('classes/Mail/MailQueue.php');
    require_once('classes/Mail/Email.php');
	
    $recipient1 = 'jescott@mtroyal.ca';
    $recipientName1 = 'Jenni Scott';
    $recipient2 = 'carmstrong@mtroyal.ca';
    $recipientName2 = 'Carol Armstrong';
    $subject = '[STRD Registration]';
    if($details['descrip']=='') $addit="No abstract entered..";
    else $addit='Abstract included';
    $emailBody = sprintf('A S+T student research day registration occurred:

                          First Name : %s
                          Last Name: %s
                          Title: %s
                          Format: %s
                          %s
                 ', $details['first'], $details['last'], $details['title'], $details['format'], $addit);

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
	//print_r($emails);
    $mailQueue = new MailQueue($emails);
    $mailQueue->queueAllMail();
}

/**
 * Send an email to notify student
 *
 * @param $details - the details of the submission
 */
function notifyStudent($details) {
    require_once('classes/Mail/MailQueue.php');
    require_once('classes/Mail/Email.php');

    $recipient1 = $details['email'];
    $recipientName1 = $details['first'] . ' ' . $details['last'];
    $subject = 'Registration confirmation';
    if($details['descrip']=='') $addit="We will be in touch to request your abstract in the weeks leading up to the event.";
    else $addit='';
    $emailBody = sprintf('Thanks for registering your interest in presenting at the Research Day!

Your registration details :

                          First Name : %s
                          Last Name: %s
                          Title: %s
                          Format: %s

%s

If you have any questions, you may contact Jerri-Lynne Cameron, Manager, Research Services : jcameron@mtroyal.ca, 403-440-5081.
                 ', $details['first'], $details['last'], $details['title'], $details['format'], $addit);

    $email1 = new Email(
        $recipient1,
        $recipientName1,
        $subject,
        $emailBody
    );

    $emails = array($email1);

    $mailQueue = new MailQueue($emails);
    $mailQueue->queueAllMail();
}
