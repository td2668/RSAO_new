<?php

/*error_reporting(E_ALL);
ini_set('display_errors', '1');*/

use tracking\TrackingForm;

require_once('includes/global.inc.php');
require_once('includes/print_tracking.php');

require_once('classes/tracking/TrackingForm.php');
require_once('classes/tracking/Funding.php');
require_once('classes/tracking/Approval.php');
require_once('classes/tracking/COI.php');


$trackingFormId = $_REQUEST['form_tracking_id'];

if($trackingFormId) {
    $trackingForm = new TrackingForm();
    $form = $trackingForm->retrieveForm($trackingFormId);
} else {
    $trackingForm = new TrackingForm();
    $form = $trackingForm;
}

//var_dump($trackingForm);

$tmpl=loadPage("my_tracking", 'Tracking Forms');
showMenu("my_forms");

if (sessionLoggedin()) {
    $username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);

    if(is_array($user) == false or count($user) == 0) {
        displayBlankPage("Error","<h1>Error</h1>There was a problem finding your user record.");
        die;
    }

    if(isset($_REQUEST['printpdf']) && isset($_REQUEST['form_tracking_id'])){
        $sql="SELECT form_tracking_id FROM forms_tracking WHERE form_tracking_id=$_REQUEST[form_tracking_id]";
        $form=$db->getAll($sql);
        if(count($form)>0) printPDF($_REQUEST['form_tracking_id'],$user,$db);
    }
    
    // Handle all the button actions first 
    if(isset($_REQUEST['delete']) && isset($_REQUEST['form_tracking_id'])){
        $sql="DELETE FROM `forms_tracking` WHERE `form_tracking_id` = '{$_REQUEST['form_tracking_id']}'";
        $result=$db->Execute($sql);
        if(!$result) print($db->ErrorMsg());
        $_REQUEST['gotosection']='list';
        unset($_REQUEST['form_tracking_id']);
    }
    
    if(isset($_REQUEST['deletecoresearcher']) && isset($_REQUEST['form_tracking_id']) && isset($_REQUEST['user_id'])){
        $trackingForm->deleteCoresearcher($_REQUEST['user_id']);

        $_REQUEST['gotosection']='info';
    }
    
    if(isset($_REQUEST['add_co'])) if(intval($_REQUEST['add_co']) > 0) {
        $trackingForm->addCoresearcher($_REQUEST['add_co']);
        $_REQUEST['gotosection'] = 'info';
    }

    if(isset($_REQUEST['pi_id'])) if(intval($_REQUEST['pi_id']) > 0) {
        $trackingForm->addCoresearcher($_REQUEST['pi_id'], true);
    }

    // New COI form
    if(isset($_REQUEST['newcoi']) && isset($_REQUEST['form_tracking_id'])) if($_REQUEST['newcoi']=='true'){
        $_REQUEST['form_coi_id'] = $trackingForm->addCOI();
        $gotosection = 'coi';
    }

    // Delete COI form
    if(isset($_REQUEST['deletecoi']) && isset($_REQUEST['form_coi_id'])) {
        $trackingForm->deleteCOI($_REQUEST['form_coi_id']);
        unset($_REQUEST['form_coi_id']); // it doesn't exist, so disable display default
     }
    
      if(isset($_REQUEST['copy']) && isset($_REQUEST['form_tracking_id'])){
        $sql="SELECT * FROM `forms_tracking` WHERE `form_tracking_id` = '{$_REQUEST['form_tracking_id']}'";
        $form=$db->getRow($sql);
        if(!$result) print($db->ErrorMsg());
        //modify values
        $form['tracking_name']=mysql_real_escape_string($form['tracking_name'].' Copy');
        $form['lastname']= mysql_real_escape_string($form['lastname']);
        $form['firstname']= mysql_real_escape_string($form['firstname']);
        $form['phone']= mysql_real_escape_string($form['phone']);
        $form['email']= mysql_real_escape_string($form['email']);
        $form['position']= mysql_real_escape_string($form['position']);
        $form['address1']= mysql_real_escape_string($form['address1']);
        $form['address2']= mysql_real_escape_string($form['address2']);
        $form['address3']= mysql_real_escape_string($form['address3']);
        $form['coresearchers']= mysql_real_escape_string($form['coresearchers']);
        $form['agency_name']= mysql_real_escape_string($form['agency_name']);
        $form['equipment']= mysql_real_escape_string($form['equipment']);
        $form['space']= mysql_real_escape_string($form['space']);
        $form['commitments']= mysql_real_escape_string($form['commitments']);
        $form['where']= mysql_real_escape_string($form['where']);
        $form['dean_comments']= mysql_real_escape_string($form['dean_comments']);
        $form['ors_comments']= mysql_real_escape_string($form['ors_comments']);
        $form['documents']= mysql_real_escape_string($form['documents']);
        
        $sql="INSERT INTO `research`.`forms_tracking` (`form_tracking_id`, `project_id`, `newproject`, `synopsis`,
                           `deadline`, `created`, `modified`, `user_id`, `tracking_name`, `pi`, `pi_id`, `lastname`,
                           `firstname`, `phone`, `email`, `position`, `address1`, `address2`, `address3`, `coresearchers`,
                           `costudents`, `funding`, `agency_id`, `agency_name`, `program_id`, `funding_confirmed`, `requested`,
                           `received`, `equipment_flag`, `equipment`, `space_flag`, `space`, `commitments_flag`, `commitments`,
                           `employ_flag`, `emp_students`, `emp_ras`, `emp_consultants`, `loc_mru`, `loc_canada`, `loc_international`,
                          `where`, `human_b`, `human_h`, `biohaz`, `animal`, `human_b_clearance`, `human_b_protocol`,
                          `trackoptions`, `status`, `submit_date`, `dean_sig`, `dean_id`, `dean_date`, `dean_comments`,
                          `ors_sig`, `ors_id`, `ors_date`, `ors_comments`, `documents`, `iagree`)
        VALUES (NULL, 
                '$form[project_id]', 
                '',
                '',
                NOW(),
                NOW(),
                NOW(),
                '$form[user_id]',
                '$form[tracking_name]',
                '$form[pi]',
                '$form[pi_id]',
                '$form[lastname]',
                '$form[firstname]',
                '$form[phone]',
                '$form[email]',
                '$form[position]',
                '$form[address1]',
                '$form[address2]',
                '$form[address3]',
                '$form[coresearchers]',
                '$form[costudents]',
                '$form[funding]',
                '$form[agency_id]',
                '$form[agency_name]',
                '$form[program_id]',
                '$form[funding_confirmed]',
                '$form[requested]',
                '$form[received]',
                '$form[equipment_flag]',
                '$form[equipment]',
                '$form[space_flag]',
                '$form[space]',
                '$form[commitments_flag]',
                '$form[commitments]',
                '$form[employ_flag]',
                '$form[emp_students]',
                '$form[emp_ras]',
                '$form[emp_consultants]',
                '$form[loc_mru]',
                '$form[loc_canada]',
                '$form[loc_international]',
                '$form[where]',
                '$form[human_b]',
                '$form[human_h]',
                '$form[biohaz]',
                '$form[animal]',
                '$form[human_b_clearance]',
                '$form[human_b_protocol]',
                '$form[trackoptions]',
                '0',
                NULL,
                '0',
                '0',
                NULL,
                '',
                '0',
                '0',
                NULL,
                '',
                '$form[documents]',
                '0'
                )";
        //save new
        //echo $sql;
        $result=$db->Execute($sql);
        if(!$result) print($db->ErrorMsg());
        
        $new_id=mysql_insert_id();
        
        //check for a budget entry
        $sql="SELECT * FROM `forms_tracking_budgets` WHERE `form_tracking_id` = '{$_REQUEST['form_tracking_id']}'";
        $buds=$db->getAll($sql);
        if(!$buds) print($db->ErrorMsg());
        
        if(count($buds)>0){
        $bud=$buds[0];
        $bud['others_text']=mysql_real_escape_string($bud['others_text']);
            $sql="INSERT INTO `research`.`forms_tracking_budgets` (`forms_tracking_budget_id`, `form_tracking_id`, `c_stipends`, `i_stipends`, `c_persons`, `i_persons`, `c_assist`, `i_assist`, `c_ustudents`, `i_ustudents`, `c_gstudents`, `i_gstudents`, `c_ras`, `i_ras`, `c_others`, `i_others`, `others_text`, `c_benefits`, `i_benefits`, `c_equipment`, `i_equipment`, `c_supplies`, `i_supplies`, `c_travel`, `i_travel`, `c_comp`, `i_comp`, `c_oh`, `i_oh`, `c_space`, `i_space`) 
            VALUES (    NULL, 
            $new_id,
            $bud[c_stipends],
            $bud[i_stipends],
            $bud[c_persons],
            $bud[i_persons],
            $bud[c_assist],
            $bud[i_assist],
            $bud[c_ustudents],
            $bud[i_ustudents],
            $bud[c_gstudents],
            $bud[i_gstudents],
            $bud[c_ras],
            $bud[i_ras],
            $bud[c_others],
            $bud[i_others],
            '$bud[others_text]',
            $bud[c_benefits],
            $bud[i_benefits],
            $bud[c_equipment],
            $bud[i_equipment],
            $bud[c_supplies],
            $bud[i_supplies],
            $bud[c_travel],
            $bud[i_travel],
            $bud[c_comp],
            $bud[i_comp],
            $bud[c_oh],
            $bud[i_oh],
            $bud[c_space],
            $bud[i_space]
            );";
        }
        
        if(!$db->Execute($sql)) print($db->ErrorMsg());
        
        //And for co-researchers
        $sql="SELECT * FROM forms_tracking_coresearchers WHERE form_tracking_id=$_REQUEST[form_tracking_id]";
        $cos=$db->getAll($sql);
        if(count($cos)>0){
        	foreach($cos as $co){
        		$sql="INSERT INTO forms_tracking_coresearchers
        				(`form_tracking_coresearcher_id`,`user_id`,`form_tracking_id`)
        				VALUES(NULL,'$co[user_id]','$new_id')";
        		$result=$db->Execute($sql);
         		if(!$result) print($db->ErrorMsg());
        	}
        }
        
        $_REQUEST['gotosection']='list';
        unset($_REQUEST['form_tracking_id']);
    }

    
    //Create a new record (only the name arrives with the REQUEST)
    if(isset($_REQUEST['newname']) && isset($_REQUEST['section'])) if($_REQUEST['newname'] != '' && $_REQUEST['section']=='new'){
        //create a new entry
        $tracking_name = (isset($_REQUEST['newname'])) ? mysql_real_escape_string($_REQUEST['newname']) : '';
        $sql=" INSERT into `forms_tracking` 
        (`form_tracking_id`, `created`, `modified`, `user_id`, `tracking_name`, `pi`, `deadline`)
        VALUES(NULL, NOW(), NOW(), {$user['user_id']}, '{$tracking_name}', TRUE, DATE_ADD(NOW(), INTERVAL 6 MONTH))
        ";
         
         $result=$db->Execute($sql);
         if(!$result) print($db->ErrorMsg());
         $_REQUEST['form_tracking_id'] = mysql_insert_id();
         //echo ("Formtracking ID = $_REQUEST[form_tracking_id]");
        $trackingForm = new TrackingForm();
        $form =  $trackingForm->retrieveForm($_REQUEST['form_tracking_id']);
        $_REQUEST['gotosection']='info';
    }
    
   unset ($sql);
    if (isset($_REQUEST['saveme']) && $_REQUEST['saveme'] == 'true') {
        if(isset($_REQUEST['form_tracking_id']) && $_REQUEST['form_tracking_id'] > 0) {
            if($_REQUEST['section']=='info'){
                $trackingForm->projectTitle = $_REQUEST['tracking_name'];
                $trackingForm->synopsis = $_REQUEST['synopsis'];
                $trackingForm->deadline = $_REQUEST['deadline'];
                $trackingForm->userIsPI = $_REQUEST['pi'];
                $trackingForm->principalInvestigatorId = $_REQUEST['pi_id'] ? $_REQUEST['pi_id'] : 0;
                $trackingForm->coResearcherStudents = isset($_REQUEST['costudents']) ? 1 : 0;
                $trackingForm->coResearchersExternal = $_REQUEST['coresearchers'];

                // check if we have an external PI, if so add one
                if($_REQUEST['pi'] == 0 && $_REQUEST['pi_id'] == 0) {
                    $trackingForm->addExternalPI(array(
                                                      'firstName' => $_REQUEST['firstname'],
                                                      'lastName' => $_REQUEST['lastname'],
                                                      'phone' => $_REQUEST['phone'],
                                                      'email' => $_REQUEST['email'],
                                                      'address1' => $_REQUEST['address1'],
                                                      'address2' => $_REQUEST['address2'],
                                                      'address3' => $_REQUEST['address3'],
                                                      'institution' => $_REQUEST['institution']
                                                 ));
                } else {
                    $trackingForm->removeExternalPI();
                }

                $trackingForm->saveMe();

            }
            if($_REQUEST['section']=='funding'){
                $funding = $trackingForm->funding;

                $funding->hasFunding = $_REQUEST['funding'] == 'on' ? TRUE : FALSE;
                $funding->requiresLetter = $_REQUEST['letter_required'] == '1' ? TRUE : FALSE;
                $funding->fundingDeadline = $_REQUEST['funding_deadline'];
                $funding->orsSubmits = $_REQUEST['ors_submits'] == '1' ? TRUE : FALSE;
                $funding->grantType = isset($_REQUEST['grant_type']) ? $_REQUEST['grant_type'] : 0;
                $funding->agency_id = isset($_REQUEST['agency_id']) ? $_REQUEST['agency_id'] : 0;
                $funding->agency_name = $_REQUEST['agency_name'];
                $funding->program_id = isset($_REQUEST['program_id']) ? $_REQUEST['program_id'] : 0;
                $funding->funding_confirmed = $_REQUEST['funding_confirmed'] == 'on' ? TRUE : FALSE;
                $funding->requested = $_REQUEST['requested'];
                $funding->received = $_REQUEST['received'];

                $trackingForm->saveMe();
            }
            if($_REQUEST['section']=='commitments'){
                $commitments = $trackingForm->commitments;
                $commitments->equipment = isset($_REQUEST['equipment_flag']) ? TRUE : FALSE;
                $commitments->equipmentSummary = isset($_REQUEST['equipment']) ? $_REQUEST['equipment'] : '';
                $commitments->space = isset($_REQUEST['space_flag']) ? TRUE : FALSE;
                $commitments->spaceSummary = isset($_REQUEST['space']) ? $_REQUEST['space'] : '';
                $commitments->other = isset($_REQUEST['commitments_flag']) ? TRUE : FALSE;
                $commitments->otherSummary = isset($_REQUEST['commitments']) ? $_REQUEST['commitments'] : '';
                $commitments->employed = isset($_REQUEST['employ_flag']) ? TRUE : FALSE;
                $commitments->employedStudents = isset($_REQUEST['emp_students']) ? TRUE : FALSE;
                $commitments->employedResearchAssistants = isset($_REQUEST['emp_ras']) ? TRUE : FALSE;
                $commitments->employedConsultants = isset($_REQUEST['emp_consultants']) ? TRUE : FALSE;

                $trackingForm->saveMe();
            }
            if($_REQUEST['section']=='coi'){
                $trackingForm->saveMe();
            }

            if (isset($_REQUEST['form_coi_id'])) {
                if ($_REQUEST['form_coi_id'] > 0) {
                    foreach($trackingForm->coi as $coiInstance) {
                        if($coiInstance->coiId == $_REQUEST['form_coi_id'])
                        $coi = $coiInstance;
                    }

                    $coi->name = (isset($_REQUEST['name'])) ? mysql_real_escape_string($_REQUEST['name']) : '';
                    $coi->coi_none = (isset($_REQUEST['coi_none']) ? TRUE : FALSE);
                    $coi->coi01 = (isset($_REQUEST['coi01']) ? TRUE : FALSE);
                    $coi->coi02 = (isset($_REQUEST['coi02']) ? TRUE : FALSE);
                    $coi->coi03 = (isset($_REQUEST['coi03']) ? TRUE : FALSE);
                    $coi->coi04 = (isset($_REQUEST['coi04']) ? TRUE : FALSE);
                    $coi->coi05 = (isset($_REQUEST['coi05']) ? TRUE : FALSE);
                    $coi->coi06 = (isset($_REQUEST['coi06']) ? TRUE : FALSE);
                    $coi->coi07 = (isset($_REQUEST['coi07']) ? TRUE : FALSE);
                    $coi->coi08 = (isset($_REQUEST['coi08']) ? TRUE : FALSE);
                    $coi->coi09 = (isset($_REQUEST['coi09']) ? TRUE : FALSE);
                    $coi->coi10 = (isset($_REQUEST['coi10']) ? TRUE : FALSE);
                    $coi->coi11 = (isset($_REQUEST['coi11']) ? TRUE : FALSE);
                    $coi->coi_other = (isset($_REQUEST['coi_other']) ? TRUE : FALSE);
                    $coi->financial = (isset($_REQUEST['financial']) ? TRUE : FALSE);
                    $coi->relationship = (isset($_REQUEST['relationship'])) ? mysql_real_escape_string($_REQUEST['relationship']) : '';
                    $coi->situation = (isset($_REQUEST['situation'])) ? mysql_real_escape_string($_REQUEST['situation']) : '';

                    $trackingForm->saveMe();
                }
            }
            if ($_REQUEST['section'] == 'compliance') {
                $compliance = $trackingForm->compliance;

                $compliance->locationMRU = (isset($_REQUEST['loc_mru']) ? TRUE : FALSE);
                $compliance->locationCanada = (isset($_REQUEST['loc_canada']) ? TRUE : FALSE);
                $compliance->locationInternational = (isset($_REQUEST['loc_international']) ? TRUE : FALSE);
                $compliance->humanBehavioural = (isset($_REQUEST['human_b']) ? TRUE : FALSE);
                $compliance->humanBehaviouralClearance = (isset($_REQUEST['human_b_clearance']) ? TRUE : FALSE);
                $compliance->humanBehaviouralProtocol = mysql_real_escape_string($_REQUEST['human_b_protocol']);
                $compliance->humanHealth = (isset($_REQUEST['human_h']) ? TRUE : FALSE);
                $compliance->biohazard = (isset($_REQUEST['biohaz']) ? TRUE : FALSE);
                $compliance->animalSubjects = (isset($_REQUEST['animal']) ? TRUE : FALSE);
                $compliance->locationText = (isset($_REQUEST['where'])) ? mysql_real_escape_string($_REQUEST['where']) : '';

                $trackingForm->saveMe();
            }

            if ($_REQUEST['section'] == 'files') {
                if(isset($_FILES['file_upload'])){
                    $trackingId = $trackingForm->trackingFormId;
                    $userId = $trackingForm->userId;
                    $basePath = $configInfo['tracking_docs'];

                    $errors = array();
                    $dirOK = true;

                    if (!is_dir($basePath . $userId)) {
                        $dirOK = mkdir($basePath . $userId, 0755); // if folder doesn't exist, then create it.
                    }
                    if (!is_dir($basePath . $userId . '/' . $trackingId )) {
                        $dirOK = mkdir($basePath . $userId  . '/' . $trackingId, 0755) && $dirOK;  // if folder doesn't exist, then create it.
                    }
                    $path = $basePath . $userId  . '/' . $trackingId;

                    if(!$dirOK) {
                        $errors[]= 'Error: Unable to create file directory.' . is_writable($basePath);
                    } else {
                        // we want to strip spaces and invalid characters from the filename
                        $file_name = $_FILES['file_upload']['name'];
                        $replace="_";
                        $pattern="/([[:alnum:]_\.-]*)/";
                        $file_name=str_replace(str_split(preg_replace($pattern,$replace,$file_name)),$replace,$file_name);

                        $file_size =$_FILES['file_upload']['size'];
                        $file_tmp =$_FILES['file_upload']['tmp_name'];
                        $file_type=$_FILES['file_upload']['type'];
                        $file_ext=strtolower(end(explode('.',$_FILES['file_upload']['name'])));
                        $file_description = $_POST['file_description'];
                        $extensions = array("jpeg","jpg","png", "gif", "pdf", "doc", "docx", "csv", "xls", "xlsx", "txt");
                        if(in_array($file_ext, $extensions ) === false){
                            $errors[]="Error: File extension not allowed or no file specified.";
                        }
                        if($file_size > 16777216){
                            $errors[]='Error: File size must be less than 16 MB';
                        }
                    }
                    if(empty($errors)==true){
                        $uploadSuccess = move_uploaded_file($file_tmp, $path . "/" . $file_name);
                        if($uploadSuccess == true) {
                            $fileDetails = array('trackingId' => $trackingId,
                                                 'name' => $file_name,
                                                 'extension' => $file_ext,
                                                 'path' => $path . "/" . $file_name,
                                                 'size' => $file_size,
                                                 'description' => mysql_real_escape_string($file_description));
                            $success = $trackingForm->addFile($fileDetails);
                            if($success == false) {
                                $tmpl->AddVar('files', 'errors', 'Error: File with same name already exists.');
                            }
                        } else {
                            $tmpl->AddVar('files', 'errors', 'Error uploading file to : ' . $path . "/" . $file_name);
                        }
                    } else{
                        $tmpl->AddVar('files', 'errors', array_map('strval', $errors));
                    }
                }
                $trackingForm->saveMe();
            }
        }
    }


// If not a new file,  save any form data that arrived
if (isset($_REQUEST['form_tracking_id']))
if ($_REQUEST['form_tracking_id'] > 0)
    if (isset($_REQUEST['saveme']))
        if ($_REQUEST['saveme'] == 'true') {
                    if ($_REQUEST['section'] == 'submit') {
                        //$trackoptions = (isset($_REQUEST['trackoptions'])) ? mysql_real_escape_string($_REQUEST['trackoptions']) : '';
                        $documents = (isset($_REQUEST['documents'])) ? mysql_real_escape_string($_REQUEST['documents']) : '';
                        $iagree = (isset($_REQUEST['iagree'])) ? 1 : 0;
                        if ($_REQUEST['locksubmit'] == 'false') $status = 0; else {
                            $status = 1;
                        }
                        if ($status == 1) $submit_line = "`submit_date`=NOW()"; else $submit_line = "`submit_date`=null";
                        $sql = " UPDATE `forms_tracking` SET
                                `trackoptions` = '{$trackoptions}',
                                `documents` = '{$documents}',
                                `iagree` = {$iagree},
                                `status` = {$status},
                                $submit_line
                                WHERE `form_tracking_id` = '{$_REQUEST['form_tracking_id']}'
                            ";
                        $trackingForm->saveMe();
                    }


                    if (isset($sql)) {
                        $sql2 = "SELECT * FROM forms_tracking WHERE `form_tracking_id` = '{$_REQUEST['form_tracking_id']}'";
                        $form = $db->getRow($sql2);
                        if (is_array($form)) if ($form['user_id'] == $user['user_id']) {
                            //echo ("SQL: $sql<br>");
                            $result = $db->Execute($sql);
                            if (!$result) print($db->ErrorMsg());
                        } else print("Error saving");
                    }


                    //Initial actions on form submit
                    if ($_REQUEST['section'] == 'submit') if ($_REQUEST['locksubmit'] == 'true') {
                        $trackingForm->submit();
                    } //submit actions
        } //if - save form data
   
        //default to list (there may be some redundancy here - it was patched up)
        //The idea is that the 'section' is the current one being saved, and the
        //gotosection is that target for the next load
        
        //First, if SECTION is set but not GOTOSECTION then we stay on the same page
        if(!isset($_REQUEST['section'])) $_REQUEST['section'] = '';
        if(!isset($_REQUEST['gotosection'])) $_REQUEST['gotosection']=$_REQUEST['section'];
        if($_REQUEST['gotosection']=='') $_REQUEST['gotosection']=$_REQUEST['section'];

    // we need to reload $form values, otherwise old values will still be displayed
    $trackingId =  isset($_REQUEST['form_tracking_id']) ? $_REQUEST['form_tracking_id'] : $trackingForm->trackingFormId;
    $form = $trackingForm->retrieveForm($trackingId);

     $tmpl->addVar('page_all','section',$_REQUEST['gotosection'])  ;
     $tmpl->addVar('header','additional_header_items',"<script type='text/javascript' src='js/tooltipfunctions.js'></script>");

    //in case they somehow lost their ID, drop back to the list.
    if (isset($_REQUEST['form_tracking_id'])) {
        if (isset($_REQUEST['gotosection']) && $_REQUEST['form_tracking_id'] == '') {
            $_REQUEST['gotosection'] = 'list';
        }
    }

    switch($_REQUEST['gotosection']){
             case "list":
             default:
                 $tmpl->setAttribute('savecontrol', 'visibility', 'hidden');
                 $tmpl->setAttribute('chooser', 'visibility', 'hidden');
                 $section = 'list';

                 $item = array();
                 $list = array();
                 $tracking_forms = array();

                 $pendingForms = getTrackingForms($user['user_id'], 0);
                 $submittedForms = getTrackingForms($user['user_id'], 1);

                 if(count($pendingForms) > 0) {
                    $forms = generateFormsList($pendingForms, $form, $user['user_id']);
                    $tmpl->addRows('list-pending', $forms);
                 } else {
                     $tmpl->setAttribute('list-pending', 'visibility', 'hidden');
                 }

                if(count($submittedForms > 0)) {
                 $forms = generateFormsList($submittedForms, $form, $user['user_id']);
                 $tmpl->addRows('list', $forms);
                } else {
                    $tmpl->setAttribute('list', 'visibility', 'hidden');
                }
                 break;
             case  'info':
                 $section = 'info';
                 $nextSection = 'funding';
                 $tmpl->addVar('savecontrol', 'prev_disabled', 'style="display:none;"');

                 $tmpl->addVar('chooser', 'hilite_info', 'here');

                 if ($trackingForm->status == PRESUBMITTED) {
                     //if this is not owned by the user then everything on this page is disabled.
                     if ($trackingForm->userId != $user['user_id']) {
                         $form['disabled'] = 'disabled';
                         $tmpl->addVar('savecontrol', 'disabled', 'disabled');
                         $delbutton = 'disabled';
                     } else {
                         $delbutton = '';
                     }
                     /** Principal Investigator **/
                     if ($trackingForm->isSubmitterPI()) {
                         $form['pi'] = 'checked';
                         $form['notpi'] = '';
                         $tmpl->setAttribute('pi_link', 'visibility', 'hidden');
                         $tmpl->setAttribute('pi_info', 'visibility', 'hidden');
                     } else {
                         if($form['pi_id'] == 0 && $form['pi'] == 0) {
                             $tmpl->setAttribute('pi_info', 'visibility', 'visible');
                         }
                         $form['pi'] = '';
                         $form['notpi'] = 'checked';
                     }

                     /** MRU PI */
                     if($trackingForm->hasMruPI()) {
                         $tmpl->setAttribute('pi_link', 'visibility', 'visible');
                         $tmpl->setAttribute('pi_info', 'visibility', 'hidden');
                     }


                     /** External PI */
                     if ($trackingForm->hasExternalPI()) {
                         $externalPI = $trackingForm->externalPI;

                         $PI_info['firstname'] = $externalPI->firstName;
                         $PI_info['lastname'] = $externalPI->lastName;
                         $PI_info['phone'] = $externalPI->phone;
                         $PI_info['email'] = $externalPI->email;
                         $PI_info['address1'] = $externalPI->address1;
                         $PI_info['address2'] = $externalPI->address2;
                         $PI_info['address3'] = $externalPI->address3;
                         $PI_info['institution'] = $externalPI->institution;

                         $tmpl->addVars('pi_info', $PI_info);
                     }

                     // If not PI, then get Faculty drop-down list so user can choose PI
                     require_once('includes/user_functions.php');
                     $pi_options = getFacultySelectSpecified($trackingForm->principalInvestigatorId);
                     $form['pi_id'] = $pi_options;

                     /** Co-Researchers **/
                     //Get a select list of Faculty so user can choose co-researchers
                     require_once('includes/user_functions.php');
                     $coresearcher_options = getFacultySelect();
                     $form['co_options'] = $coresearcher_options;

                     // Display the Co-researchers associated with this project
                     require_once('classes/tracking/Investigator.php');
                     $coResearchers = $trackingForm->coResearchers;
                     $coResearchersDisplay = array();
                     foreach ($coResearchers as $researcher) {
                         if($researcher->isPI == false) {
                             $coResearchersDisplay[] = array(
                                 'name'   => $researcher->getDisplayName(),
                                 'delete' => sprintf("<button type='button' %s onClick='javascript: if(confirm(\"Really delete?\")) window.location=\"/my_tracking.php?deletecoresearcher&form_tracking_id=%s&user_id=%s\";'>Delete</button>",
                                     $delbutton, $form['form_tracking_id'], $researcher->getUserId())
                             );
                         }
                     }
                     $tmpl->setAttribute('co_researchers_section', 'visibility', 'visible');
                     $tmpl->addRows('coresearchers', $coResearchersDisplay);

                     //Show intellectual property message if necessary
                     if (count($cos) > 0 || $trackingForm->coResearcherStudents || $form['coresearchers'] != '') {
                         $tmpl->setAttribute('ipinfo', 'visibility', 'visible');
                     }
                     $form['costudents'] = $trackingForm->coResearcherStudents ? 'checked' : '';
                     $form['coresearchers'] = $trackingForm->coResearchersExternal;
                     $tmpl->addVars('info', $form);
             }
             break;

        case 'funding':
            $section = 'funding';
            $nextSection = 'commitments';
            $prevSection = 'info';


            $tmpl->addVar('chooser', 'hilite_funding', 'here');
            $funding = $trackingForm->funding;

            if ($trackingForm->status == PRESUBMITTED) {
                if ($trackingForm->hasFunding() == false) {
                    $form['funding_no'] = 'CHECKED';
                } else {
                    $form['funding_yes'] = 'CHECKED';
                    if($funding->requiresLetter ==  true) {
                        $form['letter_yes'] = 'CHECKED';
                    } else {
                        $form['letter_no'] = 'CHECKED';
                    }
                    if($funding->orsSubmits ==  true) {
                        $form['ors_submits_yes'] = 'CHECKED';
                    } else {
                        $form['ors_submits_no'] = 'CHECKED';
                    }
                    if($funding->grantType ==  0) {
                        $form['grant_internal'] = 'CHECKED';
                    } else {
                        $form['grant_external'] = 'CHECKED';
                    }
                    $tmpl->setAttribute('funding_details', 'visibility', 'visible');
                    $agency_options = $funding->getAgencyOptions();
                    $program_options = $funding->getProgramOptions();

                    $form['funding_confirmed'] = $funding->funding_confirmed ? 'checked' : '';
                    $form['agency_id'] = $agency_options;
                    $form['program_id'] = $program_options;
                    $form['agency_name'] = $funding->agency_name;
                    $form['funding_deadline'] = $funding->fundingDeadline;
                    $form['requested'] = $funding->requested;
                    $form['received'] = $funding->received;
                }
                $tmpl->addVars('funding', $form);
            }
            break;

        case 'commitments':
            $section='commitments';
            $nextSection = 'coi';
            $prevSection = 'funding';


            $commitments = $trackingForm->commitments;
                $tmpl->addVar('chooser','hilite_commitments','here'); 

                if ($trackingForm->status == PRESUBMITTED) {
                    if ($commitments->equipment == FALSE) {
                        $form['equipment_flag'] = '';
                    } else {
                        $form['equipment_flag'] = 'checked';
                        $tmpl->setAttribute('equipment_details', 'visibility', 'visible');
                    }
                    if ($commitments->space == FALSE) {
                        $form['space_flag'] = '';
                    } else {
                        $form['space_flag'] = 'checked';
                        $tmpl->setAttribute('space_details', 'visibility', 'visible');
                    }
                    if ($commitments->other == FALSE) {
                        $form['commitments_flag'] = '';
                    } else {
                        $form['commitments_flag'] = 'checked';
                        $tmpl->setAttribute('commitments_details', 'visibility', 'visible');
                    }
                    if ($commitments->employed == FALSE) {
                        $form['employ_flag'] = '';
                    } else {
                        $form['employ_flag'] = 'checked';
                        $tmpl->setAttribute('employ_details', 'visibility', 'visible');

                        $form['emp_students']    = ($form['emp_students']) ? 'checked' : '';
                        $form['emp_ras']         = ($form['emp_ras']) ? 'checked' : '';
                        $form['emp_consultants'] = ($form['emp_consultants']) ? 'checked' : '';
                    }
                     
                     $tmpl->addVars('commitments',$form);
                 }
             break;
             
             case 'coi':
                $section='coi';
                 $nextSection = 'compliance';
                 $prevSection = 'commitments';

                 $tmpl->addVar('chooser','hilite_coi','here');
                     if ($trackingForm->status == PRESUBMITTED){
                         $cois = $trackingForm->coi;
                         $coi = array();
                         if (is_array($cois)) {
                             if (count($cois) > 0) {
                                 $tmpl->setAttribute('coi_none', 'visibility', 'hidden');
                                 $tmpl->setAttribute('coi_list', 'visibility', 'visible');
                                 foreach ($cois as $coiForm) {
                                     if ($coiForm->user_id == $user['user_id']) {
                                         $coi['button_name'] = 'Edit';
                                         $coi['del_disabled'] = '';
                                     } else {
                                         $coi['button_name'] = 'View';
                                         $coi['del_disabled'] = 'disabled';
                                     }
                                     if ($coiForm->name == '') {
                                         $coi['listname'] = $coiForm->last_name . ', ' . $coiForm->first_name;
                                     } else {
                                         $coi['listname'] = $coiForm->name;
                                     }
                                     //Highlight the current one
                                     if (isset($_REQUEST['form_coi_id'])) {
                                         if ($_REQUEST['form_coi_id'] == $coiForm->coiId) {
                                             $coi['class'] = 'sel';
                                             $coi['arrow'] = "<img src='/images/tinyarrow.gif'>";
                                         } else {
                                             $coi['class'] = '';
                                             $coi['arrow'] = '';
                                         }
                                     }
                                     $coi['modified'] = date($niceday, $coiForm->modified);
                                     $coi['form_coi_id'] = $coiForm->coiId;
                                     $coi_list[] = $coi;
                                 }

                                 $tmpl->addRows('coi_list', $coi_list);
                             }
                         }

                         // When $_REQUEST['form_coi_id'] is set it triggers that particular form to be displayed
                         if (isset($_REQUEST['form_coi_id'])) {
                             $tmpl->addVar('coi', 'form_coi_id', $_REQUEST['form_coi_id']);
                         }
                         $tmpl->addVars('coi', $form);

                         //Process the ID specified.
                         if(isset($_REQUEST['form_coi_id'])) if($_REQUEST['form_coi_id'] > 0) {
                             $conflictOfInterest = $trackingForm->getCoiById($_REQUEST['form_coi_id']);

                             if ($conflictOfInterest->coi_none) {
                                 $coi['coi_none'] = 'checked';
                                 $coi['name'] = $conflictOfInterest->name;
                                 $coi['modified'] = $conflictOfInterest->modified;
                             } else {
                                 $coi['name'] = $conflictOfInterest->name;
                                 $coi['coi_none'] = '';
                                 $coi['modified'] = $conflictOfInterest->modified;
                                 $coi['coi01'] = $conflictOfInterest->coi01 ? 'checked' : '';
                                 $coi['coi02'] = $conflictOfInterest->coi02 ? 'checked' : '';
                                 $coi['coi03'] = $conflictOfInterest->coi03 ? 'checked' : '';
                                 $coi['coi04'] = $conflictOfInterest->coi04 ? 'checked' : '';
                                 $coi['coi05'] = $conflictOfInterest->coi05 ? 'checked' : '';
                                 $coi['coi06'] = $conflictOfInterest->coi06 ? 'checked' : '';
                                 $coi['coi07'] = $conflictOfInterest->coi07 ? 'checked' : '';
                                 $coi['coi08'] = $conflictOfInterest->coi08 ? 'checked' : '';
                                 $coi['coi09'] = $conflictOfInterest->coi09 ? 'checked' : '';
                                 $coi['coi10'] = $conflictOfInterest->coi10 ? 'checked' : '';
                                 $coi['coi11'] = $conflictOfInterest->coi11 ? 'checked' : '';
                                 $coi['coi_other'] = $conflictOfInterest->coi_other ? 'checked' : '';
                                 $coi['financial'] = $conflictOfInterest->financial ? 'checked' : '';
                                 $coi['situation'] = $conflictOfInterest->situation;
                                 $coi['relationship'] = $conflictOfInterest->relationship;
                             }

                             if ($user['user_id'] != $conflictOfInterest->user_id && !isset($_REQUEST['newcoi'])) {
                                 $coi['disabled'] = 'disabled';
                                 $tmpl->addVar('savecontrol', 'disabled', 'disabled');
                             } else {
                                 $coi['disabled'] = '';
                                 $tmpl->addVar('savecontrol', 'disabled', '');

                             }
                             $tmpl->setAttribute('coi_detail', 'visibility', 'visible');
                             $tmpl->addVars('coi_detail',$coi);
                         }
                     }
             break;
             
             case 'compliance':
                $section='compliance';
                 $nextSection = 'files';
                 $prevSection = 'coi';

                 $compliance = $trackingForm->compliance;
                $tmpl->addVar('chooser','hilite_compliance','here');
/*                $sql= "SELECT * FROM forms_tracking WHERE
                        form_tracking_id=$_REQUEST[form_tracking_id]";
                 $form= $db->getRow($sql);*/
  /*               if(is_array($form)) if($form['status']==0){*/
                 if ($trackingForm->status == PRESUBMITTED) {
                     if($compliance->locationCanada || $compliance->locationInternational) {
                         $tmpl->setAttribute('where','visibility','visible');
                     }
                     if($compliance->humanBehavioural) {
                         $tmpl->setAttribute('human_b','visibility','visible');
                         $tmpl->setAttribute('human_b_clearance','visibility','visible');
                         if($compliance->humanBehaviouralClearance) {
                            $tmpl->setAttribute('human_b_protocol','visibility','visible');
                            $tmpl->setAttribute('human_b','visibility','hidden');
                         }
                      }
                     if($compliance->humanHealth) {
                         $tmpl->setAttribute('human_h','visibility','visible');
                     }
                     if($compliance->biohazard) {
                         $tmpl->setAttribute('biohaz','visibility','visible');
                     }
                     if($compliance->animalSubjects) {
                         $tmpl->setAttribute('animal','visibility','visible');
                     }

                     $form['loc_mru'] = $compliance->locationMRU ? 'checked' : '';
                     $form['loc_canada'] = $compliance->locationCanada ? 'checked' : '';
                     $form['loc_international'] = $compliance->locationInternational ? 'checked' : '';
                     $form['human_b'] = $compliance->humanBehavioural ? 'checked' : '';
                     $form['human_b_clearance'] = $compliance->humanBehaviouralClearance  ? 'checked' : '';
                     $form['human_b_protocol'] = $compliance->humanBehaviouralProtocol;
                     $form['human_h'] = $compliance->humanHealth ? 'checked' : '';
                     $form['biohaz'] = $compliance->biohazard ? 'checked' : '';
                     $form['animal' ]= $compliance->animalSubjects ? 'checked' : '';
                     

                     $tmpl->addVars('compliance',$form);
                     $tmpl->addVars('human_b_clearance', array('human_b_clearance' => $form['human_b_clearance']));
                     $tmpl->addVars('human_b_protocol', array('human_b_protocol' => $form['human_b_protocol']));
                 }
             break;

        case 'files':
            $section='files';
            $nextSection = 'submit';
            $prevSection = 'compliance';

            $tmpl->addVar('files', 'form_tracking_id', $trackingForm->trackingFormId);
            $tmpl->addVar('chooser','hilite_files','here');

            if(isset($_REQUEST['deletefile'])) {
                $trackingForm->deleteFile($_REQUEST['filename']);
            }

            if ($trackingForm->status == PRESUBMITTED) {
                 $tmpl->addRows('filelist', $trackingForm->files);
                 if(sizeof($trackingForm->files) > 0) {
                     $tmpl->setAttribute("existingFiles", 'visibility', 'visible');
                 }
            }
            break;

             case 'submit':
                $section='submit';
                 $prevSection = 'files';
                 $tmpl->addVar('savecontrol', 'next_disabled', 'style="display:none;"');


                 $tmpl->addVar('submit','tracking_id', $trackingForm->trackingFormId);

                $tmpl->addVar('chooser','hilite_submit','here');
                $sql= "SELECT * FROM forms_tracking WHERE
                        form_tracking_id=$_REQUEST[form_tracking_id]";
                 $form= $db->getRow($sql);
                 $form['disabled']='';
                 $approvals = $trackingForm->approvals;
                 $requiredApprovals = array();
                 foreach($approvals as $approval) {
                     if($approval->type == COMMITMENTS) {
                         $requiredApprovals[] = COMMITMENTS;
                     }
                     if($approval->type == COI) {
                         $requiredApprovals[] = COI;
                     }
                     if($approval->type== ETHICS_BEHAVIOURAL) {
                         $requiredApprovals[] = ETHICS_BEHAVIOURAL;
                     }
                     if($approval->type == ETHICS_HEALTH) {
                         $requiredApprovals[] = ETHICS_HEALTH;
                     }
                     if($approval->type == ETHICS_ANIMAL) {
                         $requiredApprovals[] = ETHICS_ANIMAL;
                     }
                     if($approval->type == ETHICS_BIOHAZARD) {
                         $requiredApprovals[] = ETHICS_BIOHAZARD;
                     }
                 }
                 if(is_array($form)) if($form['status']==0){
                 	$warning='';
                    if(in_array(ETHICS_BEHAVIOURAL, $requiredApprovals) || in_array(ETHICS_HEALTH, $requiredApprovals) ||
                       in_array(ETHICS_BIOHAZARD, $requiredApprovals) || in_array(ETHICS_ANIMAL, $requiredApprovals)) {
                        $warning .= " You still are responsible for completing compliance process(es) prior to starting the research.";
                    }

                     $form['confirmtext']="Are you sure you wish to submit your tracking form?";


                     //Does the Dean need to see this?
                     if($form['equipment_flag'] || $form['space_flag'] || in_array(COMMITMENTS, $requiredApprovals)) {
                         $tmpl->setAttribute("noapprovals",'visibility','hidden');
                         $tmpl->setAttribute("appr-commitments",'visibility','visible');         
                     }

                     $sql="SELECT funding_deadline FROM `forms_tracking`
                            WHERE `form_tracking_id` = '{$_REQUEST['form_tracking_id']}'
                            AND `funding` = 1";
                     $result=$db->getRow($sql);
                     if(count($result) > 0 && $result['funding_deadline'] == '0000-00-00'){
                         $tmpl->setAttribute("fundingdeadlinewarning","visibility","visible");
                     }
                     
                     //COI?
                     $sql="SELECT * FROM `forms_coi` 
                            WHERE `form_tracking_id` = '{$_REQUEST['form_tracking_id']}'
                            and `coi_none` = '0'";
                     $result=$db->getAll($sql);
                     if(count($result) > 0){
                        $tmpl->setAttribute("appr-coi","visibility","visible"); 
                     }
                     
                     //Check for any COIs?
                     $sql="SELECT * FROM `forms_coi` 
                            WHERE `form_tracking_id` = '{$_REQUEST['form_tracking_id']}'
                            ";
                     $result=$db->getAll($sql);
                     if(count($result) == 0){
                        $tmpl->setAttribute("coiwarning","visibility","visible"); 
                         $form['disabled']='disabled';
                     }
                      //Funding app but not in yet?
                     if($form['funding'] && !$form['funding_confirmed']){
                        $tmpl->setAttribute("appr-funding","visibility","visible");
                     
                     	if($form['iagree']) {$form['checkagree']='checked';}
                     	else {$form['checkagree']=''; $form['disabled']='disabled';}
                     }
                    $tmpl->addVars('submit',$form);
                 }
                break;
             
             
         } //switch

    $tmpl->addVar('section','section',$section) ;
    $tmpl->addVar('savecontrol','next_section', $nextSection);
    $tmpl->addVar('savecontrol','prev_section', $prevSection);


    $tmpl->displayParsedTemplate('page');

    //print_r($_REQUEST); 

} //logged in user

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function CleanPostForMysql($target) {
    if (get_magic_quotes_gpc()) {
        return $target;
    } else {
        return mysql_real_escape_string($target);
    } // if
} // function

/**
 * Get the tracking forms for a given user
 *
 * @param int $userId - the userID
 * @param int $status - the status {0, 1, or 2}
 * @return array|bool - the tracking forms, false on invalid parameters
 */
function getTrackingForms($userId, $status) {
    global $db;

    if(!isset($userId) || !isset($status)) {
        return false;
    }

    $sql = sprintf("SELECT ft.form_tracking_id FROM `forms_tracking` AS ft
                                 LEFT JOIN forms_tracking_coresearchers as ftc ON (ftc.form_tracking_id=ft.form_tracking_id)
                                 WHERE (ft.user_id=%s OR ft.pi_id= %s OR ftc.user_id= %s) AND ft.status = %s
                                 GROUP BY ft.form_tracking_id
                                 ORDER BY ft.status, ft.modified DESC",
        $userId, $userId, $userId, $status);

    $formIds = $db->getAll($sql);

    $myTrackingForms = array();

    foreach($formIds as $trackingId) {
        $formId = $trackingId['form_tracking_id'];
        $trackingForm = new TrackingForm();
        $trackingForm->retrieveForm($formId);

        $myTrackingForms[] = $trackingForm;
    }

    return $myTrackingForms;
}

/**
 * Generate a list of tracking forms for display
 *
 * @param $myTrackingForms - array[TrackingForm]
 * @param $userId - the user Id
 * @return array - the formatted forms for the view template
 */
function generateFormsList($myTrackingForms, $userId)
{
    $forms = array(); // the collection for tracking forms
    foreach ($myTrackingForms as $key => $myTrackingForm) {
        $form = array(); // this tracking form
        $form['evenodd'] = $key % 2 ? '' : 'odd';

        if ($myTrackingForm->status != PRESUBMITTED) {
            $HrebStatus = $myTrackingForm->hrebStatus();
            $HrebStatusCode = $myTrackingForm->hrebStatusCode();
            $form['hreb_text'] = $HrebStatus;

            switch ($HrebStatusCode) {
                case 2 :
                    $form['hreb_image'] = 'dot-green.gif';
                    break;
                case 1 :
                    $form['hreb_image'] = 'dot-yellow.gif';
                    break;
                case 0 :
                    $form['hreb_image'] = 'dot-yellow.gif';
                    break;
                case 4 :
                    $form['hreb_image'] = 'dot-red.gif';
                    break;
                case 3 :
                    $form['hreb_image'] = 'dot-red.gif';
                    break;
                default :
                    $form['hreb_image'] = 'blank.gif';
                    break;
            }
        }

        $deanApprovalStatus = $myTrackingForm->isDeanApproved();
        if ($deanApprovalStatus) {
            $form['approval_image'] = 'dot-green.gif';
            $form['approval_text'] = 'Approved';
        } else {
            $form['approval_image'] = 'dot-yellow.gif';
            $form['approval_text'] = 'Pending';
        }

        $OrsApprovalStatus = $myTrackingForm->isOrsApproved();
        if ($OrsApprovalStatus) {
            $form['ors_image'] = 'dot-green.gif';
            $form['ors_text'] = 'Approved';
        } else {
            $form['ors_image'] = 'dot-yellow.gif';
            $form['ors_text'] = 'Pending';
        }

        $form['tracking_id'] = $myTrackingForm->trackingFormId;
        $form['tracking_id_display'] = $myTrackingForm->status == SUBMITTED ? $myTrackingForm->trackingFormId : '';  // don't show the tracking ID for presubmitted forms
        $form['tracking_name'] = $myTrackingForm->projectTitle;
        $form['modified'] = $myTrackingForm->modifiedDate;


        if ($myTrackingForm->userIsPI($userId)) {
            $form['tracking_name'] .= ' (PI)';
        }

        // this hides elements that aren't relevant until a tracking form ahs been submitted
        if ($myTrackingForm->status != PRESUBMITTED) {
            $form['hideSubmitted'] = 'none';
            $form['showSubmitted'] = 'block';
        } else {
            $form['hideSubmitted'] = 'block';
            $form['showSubmitted'] = 'none';
        }

        $forms[] = $form;
    }
    return $forms;
}


/**
 * Send an email to notify ORS
 *
 * @param $trackingId - the tracking form ID
 */
function notifyORS($trackingId) {
    require_once('classes/Mail/MailQueue.php');
    require_once('classes/Mail/Email.php');
}

// All print functions moved to includes/print_tracking.php
