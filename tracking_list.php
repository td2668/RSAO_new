<?php
require_once('includes/global.inc.php');
include_once("includes/functions-required.php");

$page = (isset($_GET["page"])) ? CleanString($_GET["page"]) : '';
$action = (isset($_REQUEST["action"])) ? CleanString($_REQUEST["action"]) : '';
$mrAction = (isset($_REQUEST["mr_action"])) ? CleanString($_REQUEST["mr_action"]) : '';
$cvItemId = (isset($_GET["cv_item_id"])) ? CleanString($_GET["cv_item_id"]) : false;
$reportId = (isset($_GET["report_id"])) ? CleanString($_GET["report_id"]) : false;
$getUserId = (isset($_GET["user_id"])) ? CleanString($_GET["user_id"]) : false;

$tmpl=loadPage("tracking_list", 'Tracking Forms List');
showMenu("tracking_list");

#Extra item for Comments AJAX feature
$tmpl->addVar( 'HEADER', 'ADDITIONAL_HEADER_ITEMS', '    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>' );

 //print_r($_REQUEST);
if (sessionLoggedin()) {
    $username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);

    if(is_array($user) == false or count($user) == 0) {
        displayBlankPage("Error","<h1>Error</h1>There was a problem finding your user record.");
        die;
    } 
    
    
    // check for ajax calls, if there is an ajax call, we have to make sure no headers are sent
    // before this and we stop execution before the next switch
    switch($mrAction) {
        
        case 'ajax_save_comments':
            $reportId = (isset($_POST["report_id"])) ? CleanString($_POST["report_id"]) : null;
            $comments = (isset($_POST["comments"])) ? mysql_real_escape_string($_POST["comments"]) : null;
            //echo "<p>{reportId}</p><p{$comments}</p>";
            echo json_encode(SaveComments($userId, $reportId, $comments));
            exit;
            break;
        case 'ajax_appaction':
            $reportId = (isset($_POST["report_id"])) ? CleanString($_POST["report_id"]) : null;
            $comments = (isset($_POST["comments"])) ? mysql_real_escape_string($_POST["comments"]) : null;
            //echo "<p>{reportId}</p><p{$comments}</p>";
            if(SaveComments($userId, $reportId, $comments)){
            	switch($action){
            		case 'return':
            			echo json_encode(ReturnApp($userId, $reportId));
            		break;
            		case 'approve':
            			echo json_encode(ApproveApp($userId, $reportId));
            		break;
            	}//switch
            	
            	
            }
            else echo json_encode(0); //Return error on saving comments
            exit;
            break;
        case 'ajax_get_help':
            // perform task,  ajax data (if applicable), end execution here
            break;
        default:
            break;
    } // switch
    
    
    //print_r($_SESSION);
    
    
    if(!(isset($_SESSION['user_info']['dean_flag']) || isset($_SESSION['user_info']['associate_dean_flag']) || isset($_SESSION['user_info']['chair_flag']) || isset($_SESSION['user_info']['director_flag']))){
    	displayBlankPage("Error","<h2>Error</h2>This page is for Department or Faculty listings. Your user privileges are not sufficient to view this page. (However, if you'd like to sign up as a Chair .....)",'tracking_list');
    	showMenu("tracking_list");
        die;
    }
    
    //Tracking forms can be associated with a number of types of applications (or none at all)
    // - for the IRGF we have dynamically generated PDFs of the Form, the Attachment, and 1 or more CVs. All listed on-screen
    // - for another electronic type we have a list of items that should be sent via email
    // - for paper types we have a list of paper items sent internally.
    // Note: the electronic items list is not implemented. + Need to provide a PDF to help with paper
    //     e.g. Generate page with title and instructions on how to approve on-line.
    
    
    //Handle incoming requests
    if(isset($_REQUEST['printpdf']) && isset($_REQUEST['form_tracking_id'])){
		include_once("includes/print_tracking.php");
        $sql="SELECT form_tracking_id,user_id FROM forms_tracking WHERE form_tracking_id=$_REQUEST[form_tracking_id]";
        $form=$db->getRow($sql);
        if(count($form)>0) printPDF($_REQUEST['form_tracking_id'],$form['user_id'],$db);
	}

	if(isset($_REQUEST['printpdf']) && isset($_REQUEST['form_irgf_id'])){
		include_once("includes/print_irgf.php");
        $sql="SELECT form_irgf_id,user_id FROM forms_irgf WHERE form_irgf_id=$_REQUEST[form_irgf_id]";
        $form=$db->getRow($sql);
        if(count($form)>0) printPDF($_REQUEST['form_irgf_id'],$form['user_id'],$db);
	}
	
	
	//
	$tmpl->setAttribute('list','visibility','visible');
	if(isset($_REQUEST['sort'])) $sort=$_REQUEST['sort'];
	else $sort='submit_date';

	if(isset($_REQUEST['dir'])) {
		if($_REQUEST['dir']=='ASC') $dir='ASC';
		else $dir='DESC';
	}
	else $dir='ASC'; //default
	
	//This currently calls everything and then filters out those not associated with the current users later on
	//Since there are 4 possible 'explode()'s needed and the whereClause would be messier, I've left it that way for simplicity
	//Plus later on when privileges are modified the fix will be easier
	$sql="	SELECT 	ft.user_id,
					ft.form_tracking_id,
					fi.form_irgf_id,
					fi.which_fund, 
					fi.reviewer_id, 
					fi.cv, 
					fi.filename, 
					ft.status, 
					ft.submit_date, 
					ft.pi,
					ft.pi_id,
					ft.equipment_flag,
					ft.space_flag,
					ft.commitments_flag,
					ft.tracking_name, 
					u1.department_id,
					divisions.division_id,
					CONCAT(u1.last_name,u1.first_name) AS owner 
					FROM forms_tracking as ft 
					LEFT JOIN forms_irgf as fi ON(ft.form_tracking_id = fi.form_tracking_id)
         			LEFT JOIN users as u1 ON(u1.user_id=ft.user_id)   
         			LEFT JOIN departments on(u1.department_id=departments.department_id)
         			LEFT JOIN divisions on(departments.division_id=divisions.division_id)      			  
         			WHERE ft.status=1 ORDER BY $sort $dir";

	$apps=$db->getAll($sql);
	if(count($apps)>0){
		foreach($apps as $key=>$app){
			$apps[$key]['showrow']=false;
			//Get the associated signature file. Just in case, create one if missing.
			$sql="SELECT * FROM forms_tracking_sigs WHERE form_tracking_id=$app[form_tracking_id]";
			$sigs=$db->getRow($sql);
			if(!$sigs){
				$sql="INSERT INTO `research`.`forms_tracking_sigs` (
					`form_tracking_id` ,
					`chair_sig_id` ,
					`chair_action` ,
					`chair_date` ,
					`chair_comments` ,
					`dir_sig_id` ,
					`dir_action` ,
					`dir_date` ,
					`dir_comments` ,
					`dean_sig_id` ,
					`dean_action` ,
					`dean_date` ,
					`dean_comments` ,
					`ors_sig_id` ,
					`ors_action` ,
					`ors_date` ,
					`ors_comments`
					)
					VALUES (
					$app[form_tracking_id], '0', '0', '0000-00-00', '', '0', '0', '0000-00-00', '', '0', '0', '0000-00-00', '', '0', '0', '0000-00-00', ''
					);
					";
				$db->Execute($sql);
				$sql="SELECT * FROM forms_tracking_sigs WHERE form_tracking_id=$app[form_tracking_id]";
				$sigs=$db->getRow($sql);
			}

			//Owner is different than PI
			//ToDo: Shift from Owner to PI in entire routine - but if PI is not MRU then use owner only.
			$sql="SELECT user_id,last_name,first_name FROM users WHERE user_id=$app[user_id]";
			$user=$db->getRow($sql);
			if($user) $apps[$key]['owner']="$user[last_name], $user[first_name]";

			//Process PI
			if($app['pi']) $app['pi_id']=$app['user_id'];  //For now

			if($app['pi_id']!=0){
				$sql="SELECT user_id,last_name,first_name FROM users WHERE user_id=$app[pi_id]";
				$user=$db->getRow($sql);
				if($user) $apps[$key]['pi_name']="$user[last_name], $user[first_name]";
			}
			else{
				$apps[$key]['pi_name']="$app[lastname], $app[firstname]";

			}
			if(strlen($app['tracking_name'])>30) $apps[$key]['tracking_name']=substr($app['tracking_name'],0,30) . '...';
			$apps[$key]['tracking_name_title']= $app['tracking_name'];

			
			if($app['submit_date']=='0000-00-00 00:00:00') $apps[$key]['submitted']='';
			else $apps[$key]['submitted']=date('Y-m-d',strtotime($app['submit_date']));

			//if(strlen($app['tracking_name'])>40) $apps[$key]['tracking_name']=substr($app['tracking_name'],0,40) . '...';
			
			if($app['form_irgf_id'] > 0) $apps[$key]['app_pdf']="<button type='button' title='IRGF Application' onClick='javascript: window.location=\"tracking_list.php?printpdf&form_irgf_id=$app[form_irgf_id]\";'><img src='/images/icon-sm-pdf2.gif'></button>";

			if($app['form_tracking_id'] > 0) $apps[$key]['tracking_pdf']="<button type='button' onClick='javascript: window.location=\"tracking_list.php?printpdf&form_tracking_id=$app[form_tracking_id]\";'><img src='/images/icon-sm-pdf2.gif'></button>";

			if($app['filename']!='') $apps[$key]['attach_pdf']="<button title='IRGF Attachent' type='button' onClick='javascript: window.location=\"$configInfo[irgf_docs]/$app[user_id]/$app[filename]\";'><img src='/images/icon-sm-pdf2.gif'></button>";
			
			//We should at least have a user CV
			$apps[$key]['cv_pdf']='';
			if($app['cv']<>0){ 
				if($app['pi']) $pi=$app['user_id']; else $pi=$app['pi_id'];
				$sql="SELECT CONCAT(u1.first_name,' ',u1.last_name) AS user 
					  FROM users as u1 WHERE user_id=$pi";
				$uname=$db->getRow($sql);
				if($uname) $name=$uname['user'];
				if($pi != 0) $apps[$key]['cv_pdf']="<button title='$name' type='button' onClick='javascript: window.location=\"cv_review_print.php?generate=mycv$app[cv]&report_user_id=$pi&style=apa\";'><img src='/images/icon-sm-pdf2.gif'></button>";
			}
			
			//Pull up any co-researchers
			$sql="	SELECT CONCAT(u1.first_name,' ',u1.last_name) AS user, ftc.* 
					FROM forms_tracking_coresearchers as ftc 
					LEFT JOIN users as u1 ON(u1.user_id=ftc.user_id)
					WHERE form_tracking_id=$app[form_tracking_id]";
			$cos=$db->getAll($sql);
			if(count($cos)> 0) foreach($cos as $co){
				if($co['cv']<>0) $apps[$key]['cv_pdf'].="<button title='$co[user]' type='button' onClick='javascript: window.location=\"cv_review_print.php?generate=mycv$co[cv]&report_user_id=$co[user_id]&style=apa\";'><img src='/images/icon-sm-pdf2.gif'></button>";
			}
			
			//Load  comments
			
			//First set up the read-only text for other approvers. Then use as appropriate
			
			/////ToDo:Fix jumping when comments opened/closed. Not sure why??? 
			
			$deanrow="<b>Dean:</b> "; 
			if($sigs['dean_action']==1){
				$deanrow.= ($sigs['dean_sig_id'] != 0) ? "(Signed":"(Unsigned)";
				$deanrow.= ($sigs['dean_date']!='0000-00-00') ? ' '.$sigs['dean_date'].') ':' ' ;
			}
			else {
				$deanrow.= ($sigs['dean_sig_id'] != 0) ? "(Viewed":"(Not Viewed)";
				$deanrow.= ($sigs['dean_date']!='0000-00-00') ? ' '.$sigs['dean_date'].') ':' ' ;
			}
			$deanrow.= $sigs['dean_comments'] . '<br />';
			
			$chairrow="<b>Chair:</b> "; 
			if($sigs['chair_action']==1){
				$chairrow.= ($sigs['chair_sig_id'] != 0) ? "(Signed":"(Unsigned)";
				$chairrow.= ($sigs['chair_date']!='0000-00-00') ? ' '.$sigs['chair_date'].') ':' ' ;
			}
			else {
				$chairrow.= ($sigs['chair_sig_id'] != 0) ? "(Viewed":"(Not Viewed)";
				$chairrow.= ($sigs['chair_date']!='0000-00-00') ? ' '.$sigs['chair_date'].') ':' ' ;
			}
			$chairrow.= $sigs['chair_comments'] . '<br />';
			
			if($app['director_id']!=0){
				$dirrow="<b>Director:</b> "; 
				if($sigs['dir_action']==1){
					$dirrow.= ($sigs['dir_sig_id'] != 0) ? "(Signed":"(Unsigned)";
					$dirrow.= ($sigs['dir_date']!='0000-00-00') ? ' '.$sigs['dir_date'].') ':' ' ;
				}
				else {
					$dirrow.= ($sigs['dir_sig_id'] != 0) ? "(Viewed":"(Not Viewed)";
					$dirrow.= ($sigs['dir_date']!='0000-00-00') ? ' '.$sigs['dir_date'].') ':' ' ;
				}
				$dirrow.= $sigs['dir_comments'] . '<br />';
			}
			else unset($dirrow);
			
			$orsrow="<b>ORS:</b> "; 
			if($sigs['ors_action']==1){
				$orsrow.= ($sigs['ors_sig_id'] != 0) ? "(Signed":"(Unsigned)";
				$orsrow.= ($sigs['ors_date']!='0000-00-00') ? ' '.$sigs['ors_date'].') ':' ' ;
			}
			else {
				$orsrow.= ($sigs['ors_sig_id'] != 0) ? "(Viewed":"(Not Viewed)";
				$orsrow.= ($sigs['ors_date']!='0000-00-00') ? ' '.$sigs['ors_date'].') ':' ' ;
			}
			$orsrow.= $sigs['ors_comments'] . '<br />';
		
			
			
			
			//if($sigs['chair_comments']!='') $apps[$key]['other_comments'].="<b>Chair:</b> $sigs[chair_comments]<br />";
			//if($sigs['dir_comments']!='') $apps[$key]['other_comments'].="<b>Director:</b> $sigs[dir_comments]<br />";
			//if($sigs['ors_comments']!='') $apps[$key]['other_comments'].="<b>ORS:</b> $sigs[ors_comments]<br />";
			
			//Who is this user (relative to the app - they could be in multiple roles)?
			$apps[$key]['rowcolour']='#CCCCCC';
			if(isset($_SESSION['user_info']['dean_flag'])) if($_SESSION['user_info']['dean_flag']) {
				if(in_array($app['division_id'],explode(',',$_SESSION['user_info']['dean_division_id']))){
					$apps[$key]['comments']=$sigs['dean_comments'];
					$apps[$key]['showrow']=true;
					$apps[$key]['other_comments']='';
					$apps[$key]['other_comments'].=$chairrow . "<br />";
					if(isset($dirrow)) $apps[$key]['other_comments'].=$dirrow."<br />";
					$apps[$key]['other_comments'].=$orsrow."<br />";
					
					//If the Dean approves this type of application show the buttons
					if($sigs['dean_action']) {
						$apps[$key]['rowcolour']='#CCCCCC';
						$apps[$key]['extra_buttons']="<input type='button' value='Return' name='return' onClick=\"if(confirm('Return unsigned?')) {AppAction('return','$app[form_tracking_id]')};\" />
                    	<input type='button' value='Approve' name='approve' onClick=\"AppAction('approve','$app[form_tracking_id]');\" />";
                    	//Set bold
                    	$apps[$key]['bold']='font-weight:700;';
                    	//If overdue
                    	$apps[$key]['colour']="color:red;";
					}
				}
			}
			elseif(isset($_SESSION['user_info']['chair_flag'])) if($_SESSION['user_info']['chair_flag']) {
				if(in_array($app['department_id'],explode(',',$_SESSION['user_info']['chair_department_id']))){
					$apps[$key]['comments']=$sigs['chair_comments'];
					$apps[$key]['showrow']=true;
					$apps[$key]['other_comments']='';
					if($sigs['dean_comments']!='') $apps[$key]['other_comments'].="<b>Dean:</b> $sigs[dean_comments]<br />";
					if($sigs['dir_comments']!='') $apps[$key]['other_comments'].="<b>Director:</b> $sigs[dir_comments]<br />";
					if($sigs['ors_comments']!='') $apps[$key]['other_comments'].="<b>ORS:</b> $sigs[ors_comments]<br />";
					
				}
			}
			
			
			
		}//foreach
		if($dir=='ASC')$dir='DESC';
		else $dir='ASC';
		$tmpl->addVar('list','dir',$dir);
		//echo('<pre>');
		//print_r($apps);
		//echo('</pre>');
		$out=array();
		foreach($apps as $key=>$app){
			if($app['showrow']) $out[]=$app;
		}
		
		$tmpl->addRows('mainlist',$out);
		if(isset($success)) $tmpl->addVar('list','success',$success);
	}//if count>0

    
    
}

$tmpl->displayParsedTemplate('page');




/**
 * SaveComments function.
 * Save comments from Ajax call 
 * 
 * @access public
 * @param mixed $userId
 * @param mixed $reportId
 * @param mixed $comments
 * @return void
 */
function SaveComments($userId, $reportId, $comments) {

    global $db;

    $returnStatus = 1;

    // make sure this is a dean doing this

    // save the data
    if ($reportId > 0 ) {
        $sql = "UPDATE forms_tracking_sigs SET dean_comments = '{$comments}' WHERE form_tracking_id = {$reportId}";
        if ($db->Execute($sql)) {
            // worked
        } else {
            // query failed
            trigger_error("SaveComments(): query failed ({$sql})");
            $returnStatus = 0;

        } // if
        // check for error and update status
    } else {
        trigger_error("SaveComments(): invalid parameters received ({$reportId})");
        $returnStatus = 0;
    } // if

    // email user and/or chair?


    return $returnStatus;

} // function SaveComments


function ReturnApp($userId, $reportId){
	return 1;
} //fucntion ReturnApp

?>