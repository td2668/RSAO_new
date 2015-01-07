<?php
require_once('includes/global.inc.php');
//require_once('includes/print_.php');
require_once('includes/pdf.php');
require_once('includes/print_irgf.php');



/**
 * The IRGF Form follows a standard layout with a set of top headers that define each page/section
 * auto-saving wherever possible, and an initial list. 
 * It will rely on data entered on the tracking form, and so a number of things that might be redundant are
 * not included. 
 *
 * Mistakenly or not, this currently uses a single file for all sections with the print functions broken out into an include
 * It may be sensible in the future to break up into section documents for ease of maintenance.
 *
 */
 
 
$tmpl=loadPage("my_irgf", 'Internal Grant Forms');
showMenu("my_forms");
 //print_r($_REQUEST);
if (sessionLoggedin()) {
    $username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);

    if(is_array($user) == false or count($user) == 0) {
        displayBlankPage("Error","<h1>Error</h1>There was a problem finding your user record.");
        die;
    } 
    
    //Print as PDF
    if(isset($_REQUEST['printpdf']) && isset($_REQUEST['form_irgf_id'])){
        $sql="SELECT form_irgf_id FROM forms_irgf WHERE form_irgf_id=$_REQUEST[form_irgf_id]";
        $form=$db->getAll($sql);
        if(count($form)>0) printPDF($_REQUEST['form_irgf_id'],$user,$db);
    }
    
    // Handle all the button actions first 
    if(isset($_REQUEST['delete']) && isset($_REQUEST['form_irgf_id'])){
        $sql="DELETE FROM `forms_irgf` WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'";
        $result=$db->Execute($sql);
        if(!$result) print($db->ErrorMsg());
        $sql="DELETE FROM `forms_irgf_budgets` WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'";
        $result=$db->Execute($sql);
        if(!$result) print($db->ErrorMsg());
        $_REQUEST['gotosection']='list';
        unset($_REQUEST['form_irgf_id']);
    }
    
    if(isset($_REQUEST['copy']) && isset($_REQUEST['form_irgf_id'])){
        $sql="SELECT * FROM `forms_irgf` WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'";
        $form=$db->getRow($sql);
        if(!$result) print($db->ErrorMsg());
        //modify values
        $name=mysql_real_escape_string($form['irgf_name'].' Copy');
        $form['summary']= mysql_real_escape_string($form['summary']);
        $form['dissemination']=mysql_real_escape_string($form['dissemination']); 
        $form['funding']=mysql_real_escape_string($form['funding']); 
        $form['rationale']=mysql_real_escape_string($form['rationale']); 
      	$form['filename']=mysql_real_escape_string($form['filename']) ;
      	$form['dean_comments']=mysql_real_escape_string($form['dean_comments']); 
        $form['ors_comments']=mysql_real_escape_string($form['ors_comments']); 
       	$form['documents']=mysql_real_escape_string($form['documents']);
        
        $sql="INSERT INTO `research`.`forms_irgf` 
        (`form_irgf_id`, `form_tracking_id`, `created`, `modified`, `user_id`, `irgf_name`, `start_date`, `end_date`, `summary`, `dissemination`, `funding`, `rationale`, `reviewer_id`, `reviewer`, `cv`, `filename`, `status`, `dean_sig`, `dean_id`, `dean_date`, `dean_comments`, `ors_sig`, `ors_id`, `ors_date`, `ors_comments`, `documents`, `iagree`) 
        VALUES (NULL, 
        		'0', 
        		NOW(), 
        		NOW(), 
        		$form[user_id], 
        		'$name', 
        		'$form[start_date]', 
        		'$form[end_date]', 
        		'$form[summary]', 
        		'$form[dissemination]', 
        		'$form[funding]', 
        		'$form[rationale]', 
        		'$form[reviewer_id]', 
        		'$form[reviewer]', 
        		$form[cv],
        		'$form[filename]', 
        		0, 
        		$form[dean_sig], 
        		$form[dean_id], 
        		'$form[dean_date]', 
        		'$form[dean_comments]', 
        		$form[ors_sig], 
        		$form[ors_id], 
        		'$form[ors_date]', 
        		'$form[ors_comments]', 
        		'$form[documents]',
        		$form[iagree]);
        		";
        //save new
        echo $sql;
        $result=$db->Execute($sql);
        if(!result) print($db->ErrorMsg());
        
        //check for a budget entry
        $sql="SELECT * FROM `forms_irgf_budgets` WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'";
        $buds=$db->getAll($sql);
        if(!$buds) print($db->ErrorMsg());
        
        if(count($buds)>0){
        $bud=$buds[0];
        $bud['others_text']=mysql_real_escape_string($bud['others_text']);
        	$sql="INSERT INTO `research`.`forms_irgf_budgets` (`form_irgf_budget_id`, `form_irgf_id`, `c_stipends`, `i_stipends`, `c_persons`, `i_persons`, `c_assist`, `i_assist`, `c_ustudents`, `i_ustudents`, `c_gstudents`, `i_gstudents`, `c_ras`, `i_ras`, `c_others`, `i_others`, `others_text`, `c_benefits`, `i_benefits`, `c_equipment`, `i_equipment`, `c_supplies`, `i_supplies`, `c_travel`, `i_travel`, `c_comp`, `i_comp`, `c_oh`, `i_oh`, `c_space`, `i_space`) VALUES (	NULL, 
        	$bud[form_irgf_id],
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
        $result=$db->Execute($sql);
        
        $_REQUEST['gotosection']='list';
        unset($_REQUEST['form_irgf_id']);
    }
    
    //User asked for a new tracking form. Oblige her.
    if(isset($_REQUEST['newtf']) && isset($_REQUEST['form_irgf_id'])){
    	//Create form and then link to existing one
    	//Do the active year thing
    	$today=getdate();
        if($today['mon']>8) $year=$today['year']+1; else $year=$today['year'];
        $tracking_name = "IRGF $year Application";
        $sql=" INSERT into `forms_tracking`
        (`form_tracking_id`, `created`, `modified`, `user_id`, `tracking_name`, `pi`)
        VALUES(NULL, NOW(), NOW(), {$user['user_id']}, '{$tracking_name}', TRUE)
        ";
         
         $result=$db->Execute($sql);
         if(!$result) print($db->ErrorMsg());
         $_REQUEST['form_tracking_id']= mysql_insert_id();
         //this is saved later
         
    	
    }
    
    
    //Create a new record (only the name arrives with the REQUEST)
    if(isset($_REQUEST['section'])) if( $_REQUEST['section']=='new'){
        //create a new entry
        //first need to figure out what date to use. Anything from Sept X-1 to May X
        $today=getdate();
        if($today['mon']>8) $year=$today['year']+1; else $year=$today['year']; 
        $irgf_name = (isset($_REQUEST['newname'])) ? mysql_real_escape_string($_REQUEST['newname']) : '';
        $cv = 1; // default is to include MRU CV
        $sql=" INSERT into `forms_irgf` 
        (`form_irgf_id`, `created`, `modified`, `user_id`, `irgf_name`,`which_fund`, `cv`)
        VALUES(NULL, NOW(), NOW(), {$user['user_id']}, 'IRGF $year Application','Regular Faculty', $cv)
        ";
         
         $result=$db->Execute($sql);
         if(!$result) print($db->ErrorMsg());
         $_REQUEST['form_irgf_id']= mysql_insert_id();
         //echo ("Formirgf ID = $_REQUEST[form_irgf_id]");
         $_REQUEST['gotosection']='info';
    }
    
   unset ($sql); 
    // If not a new file,  save any form data that arrived
   if(isset($_REQUEST['form_irgf_id'])) if($_REQUEST['form_irgf_id'] > 0) if(isset($_REQUEST['saveme'])) if($_REQUEST['saveme']=='true' ) {
       
       if($_REQUEST['section']=='info'){
           $irgf_name = (isset($_REQUEST['irgf_name'])) ? mysql_real_escape_string($_REQUEST['irgf_name']) : '';
           $which_fund = (isset($_REQUEST['which_fund'])) ? mysql_real_escape_string($_REQUEST['which_fund']) : '';
           $form_tracking_id= (isset($_REQUEST['form_tracking_id']) ? $_REQUEST['form_tracking_id'] : 0);
           
           if(isset($_REQUEST['start_date'])) if($_REQUEST['start_date']!='') $start_date=(date($iso8601,strtotime($_REQUEST['start_date'])));
           else $start_date='';
           else $start_date='';
           
			if(isset($_REQUEST['end_date'])) if($_REQUEST['end_date']!='') $end_date=(date($iso8601,strtotime($_REQUEST['end_date'])));
           else $end_date='';
           else $end_date='';
           
           //$modified=mktime();   
           //echo ("Project ID is $project_id");
           
           $sql="  UPDATE `forms_irgf` SET 
           `form_tracking_id` = '{$form_tracking_id}',
           `start_date` = '{$start_date}',
           `end_date` = '{$end_date}',
           `modified` = NOW(),
           `irgf_name` = '{$irgf_name}',
           `which_fund`= '{$which_fund}'
          
           WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'
           ";
       }
       
       if($_REQUEST['section']=='summary'){
           $summary = (isset($_REQUEST['summary'])) ? mysql_real_escape_string($_REQUEST['summary']) : '';
           $sql="  UPDATE `forms_irgf` SET           
           `summary` = '{$summary}'
            WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'
            ";
           
       }
       
       if($_REQUEST['section']=='dissemination'){
           $dissemination = (isset($_REQUEST['dissemination'])) ? mysql_real_escape_string($_REQUEST['dissemination']) : '';
           $sql="  UPDATE `forms_irgf` SET           
           `dissemination` = '{$dissemination}'
            WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'
            ";
           
       }
       
       if($_REQUEST['section']=='funding'){
           $funding = (isset($_REQUEST['funding'])) ? mysql_real_escape_string($_REQUEST['funding']) : '';
           $rationale = (isset($_REQUEST['rationale'])) ? mysql_real_escape_string($_REQUEST['rationale']) : '';
           
           
           $sql="  UPDATE `forms_irgf` SET           
           `funding` = '{$funding}',
           `rationale`= '{$rationale}'
            WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'
            ";
            
            //separate funding table, so process right here
            
            //if the first field is not set, then skip the whole update so as not to blank existing data if the user toggled this section on/off
            if(isset($_REQUEST['c_stipends'])) {
                $c_stipends = (isset($_REQUEST['c_stipends']) ? ((is_numeric($_REQUEST['c_stipends'])) ? $_REQUEST['c_stipends'] : 0) : 0);
                $i_stipends = (isset($_REQUEST['i_stipends']) ? ((is_numeric($_REQUEST['i_stipends'])) ? $_REQUEST['i_stipends'] : 0) : 0);
                $c_persons = (isset($_REQUEST['c_persons']) ? ((is_numeric($_REQUEST['c_persons'])) ? $_REQUEST['c_persons'] : 0) : 0);
                $i_persons = (isset($_REQUEST['i_persons']) ? ((is_numeric($_REQUEST['i_persons'])) ? $_REQUEST['i_persons'] : 0) : 0);
                $c_assist = (isset($_REQUEST['c_assist']) ? ((is_numeric($_REQUEST['c_assist'])) ? $_REQUEST['c_assist'] : 0) : 0);
                $i_assist = (isset($_REQUEST['i_assist']) ? ((is_numeric($_REQUEST['i_assist'])) ? $_REQUEST['i_assist'] : 0) : 0);
                $c_ustudents = (isset($_REQUEST['c_ustudents']) ? ((is_numeric($_REQUEST['c_ustudents'])) ? $_REQUEST['c_ustudents'] : 0) : 0);
                $i_ustudents = (isset($_REQUEST['i_ustudents']) ? ((is_numeric($_REQUEST['i_ustudents'])) ? $_REQUEST['i_ustudents'] : 0) : 0);
                $c_gstudents = (isset($_REQUEST['c_gstudents']) ? ((is_numeric($_REQUEST['c_gstudents'])) ? $_REQUEST['c_gstudents'] : 0) : 0);
                $i_gstudents = (isset($_REQUEST['i_gstudents']) ? ((is_numeric($_REQUEST['i_gstudents'])) ? $_REQUEST['i_gstudents'] : 0) : 0);
                $c_ras = (isset($_REQUEST['c_ras']) ? ((is_numeric($_REQUEST['c_ras'])) ? $_REQUEST['c_ras'] : 0) : 0);
                $i_ras = (isset($_REQUEST['i_ras']) ? ((is_numeric($_REQUEST['i_ras'])) ? $_REQUEST['i_ras'] : 0) : 0);
                $c_others = (isset($_REQUEST['c_others']) ? ((is_numeric($_REQUEST['c_others'])) ? $_REQUEST['c_others'] : 0) : 0);
                $i_others = (isset($_REQUEST['i_others']) ? ((is_numeric($_REQUEST['i_others'])) ? $_REQUEST['i_others'] : 0) : 0);
                $c_benefits = (isset($_REQUEST['c_benefits']) ? ((is_numeric($_REQUEST['c_benefits'])) ? $_REQUEST['c_benefits'] : 0) : 0);
                $i_benefits = (isset($_REQUEST['i_benefits']) ? ((is_numeric($_REQUEST['i_benefits'])) ? $_REQUEST['i_benefits'] : 0) : 0);
                $c_equipment = (isset($_REQUEST['c_equipment']) ? ((is_numeric($_REQUEST['c_equipment'])) ? $_REQUEST['c_equipment'] : 0) : 0);
                $i_equipment = (isset($_REQUEST['i_equipment']) ? ((is_numeric($_REQUEST['i_equipment'])) ? $_REQUEST['i_equipment'] : 0) : 0);
                $c_supplies = (isset($_REQUEST['c_supplies']) ? ((is_numeric($_REQUEST['c_supplies'])) ? $_REQUEST['c_supplies'] : 0) : 0);
                $i_supplies = (isset($_REQUEST['i_supplies']) ? ((is_numeric($_REQUEST['i_supplies'])) ? $_REQUEST['i_supplies'] : 0) : 0);
                $c_travel = (isset($_REQUEST['c_travel']) ? ((is_numeric($_REQUEST['c_travel'])) ? $_REQUEST['c_travel'] : 0) : 0);
                $i_travel = (isset($_REQUEST['i_travel']) ? ((is_numeric($_REQUEST['i_travel'])) ? $_REQUEST['i_travel'] : 0) : 0);
                $c_comp = (isset($_REQUEST['c_comp']) ? ((is_numeric($_REQUEST['c_comp'])) ? $_REQUEST['c_comp'] : 0) : 0);
                $i_comp = (isset($_REQUEST['i_comp']) ? ((is_numeric($_REQUEST['i_comp'])) ? $_REQUEST['i_comp'] : 0) : 0);
                $c_oh = (isset($_REQUEST['c_oh']) ? ((is_numeric($_REQUEST['c_oh'])) ? $_REQUEST['c_oh'] : 0) : 0);
                $i_oh = (isset($_REQUEST['i_oh']) ? ((is_numeric($_REQUEST['i_oh'])) ? $_REQUEST['i_oh'] : 0) : 0);
                $c_space = (isset($_REQUEST['c_space']) ? ((is_numeric($_REQUEST['c_space'])) ? $_REQUEST['c_space'] : 0) : 0);
                $i_space = (isset($_REQUEST['i_space']) ? ((is_numeric($_REQUEST['i_space'])) ? $_REQUEST['i_space'] : 0) : 0);
                $others_text = (isset($_REQUEST['others_text'])) ? mysql_real_escape_string($_REQUEST['others_text']) : '';
                
                $sql2="UPDATE `forms_irgf_budgets` SET
                `c_stipends`= '{$c_stipends}' ,
                `i_stipends`= '{$i_stipends}' ,
                `c_persons`= '{$c_persons}' ,
                `i_persons`= '{$i_persons}' ,
                `c_assist`= '{$c_assist}' ,
                `i_assist`= '{$i_assist}' ,
                `c_ustudents`= '{$c_ustudents}' ,
                `i_ustudents`= '{$i_ustudents}' ,
                `c_gstudents`= '{$c_gstudents}' ,
                `i_gstudents`= '{$i_gstudents}' ,
                `c_ras`= '{$c_ras}' ,
                `i_ras`= '{$i_ras}' ,
                `c_others`= '{$c_others}' ,
                `i_others`= '{$i_others}' ,
                `c_benefits`= '{$c_benefits}' ,
                `i_benefits`= '{$i_benefits}' ,
                `c_equipment`= '{$c_equipment}' ,
                `i_equipment`= '{$i_equipment}' ,
                `c_supplies`= '{$c_supplies}' ,
                `i_supplies`= '{$i_supplies}' ,
                `c_travel`= '{$c_travel}' ,
                `i_travel`= '{$i_travel}' ,
                `c_comp`= '{$c_comp}' ,
                `i_comp`= '{$i_comp}' ,
                `c_oh`= '{$c_oh}' ,
                `i_oh`= '{$i_oh}' ,
                `c_space`= '{$c_space}' ,
                `i_space`= '{$i_space}' ,
                `others_text`= '{$others_text}' 
     
                
                WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'
                ";
                //`c_stipends`, `i_stipends`, `c_persons`, `i_persons`, `c_assist`, `i_assist`, `c_ustudents`, `i_ustudents`, `c_gstudents`, `i_gstudents`, `c_ras`, `i_ras`, `c_others`, `i_others`, `others_text`, `c_benefits`, `i_benefits`, `c_equipment`, `i_equipment`, `c_supplies`, `i_supplies`, `c_travel`, `i_travel`, `c_comp`, `i_comp`, `c_oh`, `i_oh`, `c_space`, `i_space` 
                
                $result=$db->Execute($sql2);
                if(!$result) print($db->ErrorMsg());
            }
       }
       
        
        if($_REQUEST['section']=='submit'){
            //$trackoptions = (isset($_REQUEST['trackoptions'])) ? mysql_real_escape_string($_REQUEST['trackoptions']) : '';
            //$documents = (isset($_REQUEST['documents'])) ? mysql_real_escape_string($_REQUEST['documents']) : '';
            $iagree= (isset($_REQUEST['iagree'])) ? 1 : 0;
            $reviewer_id= (isset($_REQUEST['reviewer_id'])) ? $_REQUEST['reviewer_id'] : 0;
            $cv=(isset($_REQUEST['cv'])) ? $_REQUEST['cv'] : 0;
            
            //Process reviewer cvs
            //Need the form again
            $sql="SELECT * FROM forms_irgf WHERE form_irgf_id=$_REQUEST[form_irgf_id]";
            $form=$db->getRow($sql);
            if($form) if($form['form_tracking_id']!=0){
             	$sql="	SELECT * FROM forms_tracking_coresearchers 
             			LEFT JOIN users using (user_id)
             			WHERE form_tracking_id=$form[form_tracking_id]";
             	$tfs=$db->getAll($sql);
             	if(count($tfs)>0){
             		foreach($tfs as $tf){
             			//$id=$tf['user_id'];
             			$cvid=$_REQUEST["cv_$tf[user_id]"];
             			$sql="UPDATE forms_tracking_coresearchers
             					SET cv='$cvid'
             					WHERE form_tracking_id=$form[form_tracking_id]
             					AND user_id=$tf[user_id]";
             			
                		if(!$db->Execute($sql)) print($db->ErrorMsg());
             		}
             	}
             }	
            
             //process file here
		    $filename=''; $filesave='';
		    if(is_uploaded_file($_FILES['file1']['tmp_name'])){
		        if($_FILES["file1"]["error"] > 0) {
		            $response="Error uploading file-Return Code: " . $_FILES["file1"]["error"] ;
		        }
		        else {
		        	//Rather than use a unique file name we'll just create a dir for the user
		        	if(!is_dir($configInfo['upload_root'] . 'irgf/'. $user['user_id'])) mkdir($configInfo['upload_root'] . 'irgf/'. $user['user_id'],0777);
		            copy($_FILES["file1"]["tmp_name"], $configInfo['upload_root'] . 'irgf/'. $user['user_id'].'/'.$_FILES["file1"]["name"]);
		        
		            $filename=$_FILES["file1"]["name"]; 
		            $response="File uploaded successfully. ";  
		            $filesave="`filename`='{$filename}',";       
		        }
    		}
    		
    		//else $response='Doesnt seem to be set';
    		//if(isset($response)) echo $response;
    		//var_dump($_FILES);
    
            $sql=" UPDATE `forms_irgf` SET
                `cv` = '{$cv}',
                `iagree` = {$iagree},
                $filesave
                `reviewer_id`='{$reviewer_id}'
                
                WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'
            ";
            
        }
          
        
        
        if(isset($sql)){
            $sql2="SELECT * FROM forms_irgf WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'";
            $form=$db->getRow($sql2);
            if(is_array($form)) if($form['user_id']==$user['user_id']){
            //echo ("SQL: $sql<br>");
                $result=$db->Execute($sql);
                if(!$result) print($db->ErrorMsg());
            }
            else print("Error saving");
        }
        
        
        //Initial actions on form submit
        if($_REQUEST['section']=='submit') 
        	if($_REQUEST['locksubmit']=='true' and isset($_REQUEST['form_irgf_id'])){
        	//If this fired then there is a completed tracking form and all other fields are verified.
        	//Lock the application from user editing (user can still print and copy)
        		$sql="UPDATE forms_irgf SET status=1 WHERE form_irgf_id=$_REQUEST[form_irgf_id]";
        		$db->Execute($sql);
        	
        	
        	//Inform the ORS and the reviewer
        	//?? Idea: send the reviewer an attached PDF - but they still have to log in to provide their comments. 
        	//         So that means I need another queue for 'reviews'
        	//         And don't forget the email reminders in the cronjob.
        	
        	
        }
        
   }    //if - save form data
   
   
        //default to list (there may be some redundancy here - it was patched up)
        //The idea is that the 'section' is the current one being saved, and the
        //gotosection is that target for the next load
        
        //First, if SECTION is set but not GOTOSECTION then we stay on the same page
        if(!isset($_REQUEST['section'])) $_REQUEST['section'] = '';
        if(!isset($_REQUEST['gotosection'])) $_REQUEST['gotosection']=$_REQUEST['section'];
        if($_REQUEST['gotosection']=='') $_REQUEST['gotosection']=$_REQUEST['section'];

     $tmpl->addVar('page_all','section',$_REQUEST['gotosection'])  ;
     $tmpl->addVar('header','additional_header_items',"<script type='text/javascript' src='js/tooltipfunctions.js'></script>");
        
        //in case they somehow lost their ID, drop back to the list.
        if(isset($_REQUEST['form_irgf_id'])) if(isset($_REQUEST['gotosection']) && $_REQUEST['form_irgf_id']=='') $_REQUEST['gotosection']='list';
        
         switch($_REQUEST['gotosection']){
             
             
             case "list":
             default:
                $tmpl->addVar('savecontrol','disabled','disabled');
                $tmpl->setAttribute('chooser','visibility','hidden');

                $section='list';
                
                $item=array(); $list=array();$output='';
                
                
                
                $sql="SELECT * FROM
                		`forms_irgf`
                    	WHERE user_id={$user['user_id']}
                    	ORDER BY created desc";
                $forms=$db->getAll($sql);
                   
                if(is_array($forms)) if(count($forms) > 0){
                $output.="<tr><td><u>Form</u></td><td><u>Status</u></td><td><u>Modified</u></td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
               		foreach($forms as $form){
                        if($form['status'] > 0) $form_name= $form['irgf_name']." (#".$form['form_irgf_id'].")";
                        else $form_name= "<a href='/my_irgf.php?gotosection=info&form_irgf_id=$form[form_irgf_id]'>" . $form['irgf_name']." (#".$form['form_irgf_id'].")</a>";
                        $created = date($niceday,strtotime($form['created']));
                        $modified= date($niceday,strtotime($form['modified']));
                        
                        //Check to see if it has been submitted yet or not.
                        if($form['status'] ==0)  $delete= "<button class='' title='Delete' type='button' name='delete' value='delete' onClick='javascript: if(confirm(\"Really delete?\")) window.location=\"/my_irgf.php?delete&form_irgf_id=$form[form_irgf_id]\";'><img src='/images/icon-sm-trash.gif'></button>";
                        else $delete='';
                        
                         //check if document can be viewed in PDF yet.
                        if(1) $pdf= "<button title='Print PDF' type='button' value='viewpdf' onClick='javascript: window.location=\"/my_irgf.php?printpdf&form_irgf_id=$form[form_irgf_id]\";'><img src='/images/icon-sm-pdf2.gif'></button>";
                        else $delete='';
                        
                        if ($form['status']==0) $status='In Progress';
                        if ($form['status']==1) $status='Submitted';
                        if ($form['status']==2) $status='Approved';
                        $copy="<button class='' type='button' title='Copy to New' value='copy' onClick='javascript: window.location=\"/my_irgf.php?copy&form_irgf_id=$form[form_irgf_id]\";'><img src='/images/icon-sm-copy.gif'></button>";
                        
                        $output.="<tr><td>$form_name</td><td valign='top' nowrap>$status</td><td valign='top' nowrap>$modified</td><td valign='top'>$copy</td><td valign='top'>$delete</td><td valign='top'>$pdf</td></tr>\n";
                        
                    }//foreach form
                }//if count forms > 0  
                $tmpl->addVar('list','output',$output);
                
             break;
             
             
             
             
             
             
             
             case  'info':
                $section='info';
                 $tmpl->addVar('chooser','hilite_info','here');             
                $sql= "SELECT * FROM forms_irgf WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                     //if this is not owned by the user then everything on this page is disabled.
                     if($form['user_id'] != $user['user_id']) {
                         $form['disabled']='disabled';
                         $tmpl->addVar('savecontrol','disabled','disabled');
                         $delbutton='disabled';
                     }
                     else $delbutton='';
                     
                     
                     /*
                     //get all projects
                     $form['project_options']='';
                     $sql="SELECT * FROM `ors_project` where `pi_user_id`={$user['user_id']}";
                     $projects=$db->getAll($sql);
                     if(is_array($projects)) foreach($projects as $project)  {
                         $selected = ($project['id']==$form['project_id']) ? 'selected' : '';
                         $project['name']= substr($project['name'],0,60);
                         $project['name'].=" (id:$project[id])";
                         $form['project_options'].="<option value='$project[id]' $selected>$project[name]</option>\n";
                     }  
                     if($form['project_id']==0 || $form['newproject']!= '') $tmpl->setAttribute('newproject','visibility','visible') ;  
                     */
                     
                     
                     //get all tracking forms
                     $form['forms_tracking_options']='';
                     $sql="SELECT * FROM `forms_tracking` where `user_id`={$user['user_id']}";
                     $forms_tracking=$db->getAll($sql);
                     if(is_array($forms_tracking)) foreach($forms_tracking as $one)  {
                         $selected = ($one['form_tracking_id']==$form['form_tracking_id']) ? 'selected' : '';
                         $one['name']= substr($one['tracking_name'],0,60);
                         $one['name'].=" (id:$one[form_tracking_id])";
                         $form['forms_tracking_options'].="<option value='$one[form_tracking_id]' $selected>$one[tracking_name]</option>\n";
                     }  
                     if($form['form_tracking_id']!=0) $tmpl->setAttribute('newtf','visibility','hidden');
                                 
                     $form['created']= date($niceday,strtotime($form['created']));
                     $form['modified']= date("$niceday G:i",strtotime($form['modified']));
                     $form['start_date']= ($form['start_date']!='0000-00-00') ? ($form['start_date']) : '';
					 $form['end_date']= ($form['end_date']!='0000-00-00') ? ($form['end_date']) : '';    
					                  
                     //If there's a linked project use that for a default title
                     if($form['form_tracking_id']!=0 && $form['irgf_name']==''){
                     	$sql="	SELECT ors_project.name 
                     			FROM forms_tracking  
                     			LEFT JOIN ors_project on(forms_tracking.project_id=ors_project.id)
                     			WHERE form_tracking_id=$form[form_tracking_id]
                     			AND project_id IS NOT NULL";
                     	//echo $sql;
                     	$proj=$db->getRow($sql);
                     	if(!$proj) print($db->ErrorMsg());
                     	//print_r($proj);
                     	if(count($proj)>0) $form['irgf_name']= $proj['name'];
                     	
                     }
                     
                     if($form['which_fund']=='new_applicant') $form['new_applicant']='selected';
                     else $form['regular_faculty']='selected';
                     
                    //print_r($form);
                     $tmpl->addVars('info',$form);
                 }//if isarray
                
             break;
             
             case 'summary':
             	$section="summary";
             	$tmpl->addVar('chooser','hilite_summary','here'); 
             	$sql= "SELECT * FROM forms_irgf WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                 	$tmpl->addVars('summary',$form);
                 }
              break;
              
              case 'dissemination':
             	$section="dissemination";
             	$tmpl->addVar('chooser','hilite_dissemination','here'); 
             	$sql= "SELECT * FROM forms_irgf WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                 	$tmpl->addVars('dissemination',$form);
                 }
              break;
             	
             
             case 'funding':
                $section='funding';
                $tmpl->addVar('chooser','hilite_funding','here'); 
                $sql= "SELECT * FROM forms_irgf WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                         
                     $sql="SELECT * FROM forms_irgf_budgets WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                     $budget=$db->getRow($sql);
                     
                     if(!is_array($budget) || sizeof($budget)==0){
                         //create a new entry
                         $sql2="INSERT INTO `research`.`forms_irgf_budgets` 
                         (`form_irgf_budget_id`, `form_irgf_id`, `c_stipends`, `i_stipends`, `c_persons`, `i_persons`, `c_assist`, `i_assist`, `c_ustudents`, `i_ustudents`, `c_gstudents`, `i_gstudents`, `c_ras`, `i_ras`, `c_others`, `i_others`, `others_text`, `c_benefits`, `i_benefits`, `c_equipment`, `i_equipment`, `c_supplies`, `i_supplies`, `c_travel`, `i_travel`, `c_comp`, `i_comp`, `c_oh`, `i_oh`, `c_space`, `i_space`) 
                         VALUES (NULL, '{$_REQUEST['form_irgf_id']}', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', ' ', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0')";
                         $result=$db->Execute($sql2);
                        
                        $sql="SELECT * FROM forms_irgf_budgets WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                        $budget=$db->getRow($sql);
                     }
                      $tmpl->addVars('funding_details',$budget);
                     
                     $tmpl->addVars('funding',$form);
                     
                 }
             break; 
             
           
             
             case 'submit':
                $section='submit';
                $tmpl->addVar('chooser','hilite_submit','here'); 
                $sql= "SELECT * FROM forms_irgf WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                 	$form['reviewer_id_options']='';
                     $sql="SELECT * FROM `users` where 1 ORDER BY last_name, first_name";
                     $theusers=$db->getAll($sql);
                     if(is_array($theusers)) foreach($theusers as $oneuser)  {
                         if($oneuser['last_name']=='') continue;
                         $selected = ($oneuser['user_id']==$form['reviewer_id']) ? 'selected' : '';
                         $oneuser['name']= $oneuser['last_name'].', '.$oneuser['first_name'];
                         $form['reviewer_id_options'].="<option value='$oneuser[user_id]' $selected>$oneuser[name]</option>\n";
                     }  
                     if($form['iagree']) $form['checkagree']='checked';
                     if($form['cv']==1) $form['cv1']='checked';
                     if($form['cv']==2) $form['cv2']='checked';
                     $form['user_id']= $user['user_id'];
                     if($form['filename']!='') $tmpl->setAttribute('submit_file', 'visibility','visible');
                     $form['filepath']=$configInfo['url_root'] . '/documents/uploads/irgf/'. $user['user_id'];
                     if(isset($response)){
                     	$form['response']= "<tr><td>&nbsp;</td><td class='enfasis'>$response</td></tr>";
                     }
                     
                     if($form['form_tracking_id']!=0){
                     	$sql="	SELECT * FROM forms_tracking_coresearchers 
                     			LEFT JOIN users using (user_id)
                     			WHERE form_tracking_id=$form[form_tracking_id]";
                     	$tfs=$db->getAll($sql);
                     	if(count($tfs)>0){
                     		$co=array();
                     		$tmpl->setAttribute('cos','visibility','visible');
                     		if(count($tfs)>1) $plural='coresearchers'; else $plural='coresearcher';
                     		foreach($tfs as $tf){
                     			$cv0=$cv1=$cv2='';
                     			if($tf['cv']==0) $cv0='checked';
                     			if($tf['cv']==1) $cv1='checked';
                     			if($tf['cv']==2) $cv2='checked';
                     			
                     			$co[]=array('name'=>$tf['first_name'].' '.$tf['last_name'],
                     						'user_id'=>$tf['user_id'],
                     						'cv0'=>$cv0,
                     						'cv1'=>$cv1,
                     						'cv2'=>$cv2);
                     		}
                     		$tmpl->addRows('cos_list',$co);
                     	}
                     }
                     
                     //Do the checks 
                     $form['disabled']='';
                     $form['checktext']='';
                     if(!$form['iagree']) { $form['disabled']='disabled'; $form['checktext'].="<li>Check the 'administer the funds' box just above</li>";} 
                     if($form['filename']=='') { $form['disabled']='disabled'; $form['checktext'].="<li>Upload your attachment above</li>";} 
                     if($form['form_tracking_id']==0) { $form['disabled']='disabled'; $form['checktext'].="<li>Link the form to a tracking form <a href='#' onClick=\"javascript: document.form1.saveme.value='true'; document.form1.gotosection.value='info';submitform()\">here</a></li>";} 
                     else {
                     	$sql="SELECT * FROM forms_tracking WHERE form_tracking_id=$form[form_tracking_id]";
                     	$tf=$db->getRow($sql);
                     	if($tf){
                     		if($tf['status']==0) { $form['disabled']='disabled'; $form['checktext'].="<li>Complete and <b>submit</b> the linked tracking form</li>";} 
                     	}
                     }
                     if($form['summary']=='') { $form['disabled']='disabled'; $form['checktext'].="<li>Complete the 'Summary' section</li>";} 
                     if($form['dissemination']=='') { $form['disabled']='disabled'; $form['checktext'].="<li>Complete the 'Dissemination' section</li>";} 
                     //if($form['funding']=='') { $form['disabled']='disabled'; $form['checktext'].="<li>Complete the 'Funding' section</li>";}
                     //if($form['rationale']=='') { $form['disabled']='disabled'; $form['checktext'].="<li>Complete the 'Funding Rationale' section</li>";} 
                     if($form['reviewer_id']==0 && $form['reviewer']=='') { $form['disabled']='disabled'; $form['checktext'].="<li>Choose an internal reviewer</li>";} 
                     if($form['cv']==0) { $form['disabled']='disabled'; $form['checktext'].="<li>Choose which CV to use.</li>";} 
                     
                     if($form['disabled']!='') $tmpl->setAttribute('checks','visibility','visible');
                    $tmpl->addVars('submit',$form);
                 }
             break;
             
             
         } //switch 

     
     $tmpl->addVar('section','section',$section) ;

    $tmpl->displayParsedTemplate('page');

    print_r($_REQUEST); 

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
 * is_currency function.
 *
 * Returns a cleaned currency string as a number
 * 
 * @access public
 * @param mixed $v The dirty number
 * @param int $round # of digits after the decimal point
 * @return void
 */
function is_currency( $v,$round=0 )
{
$v = preg_replace("/[^0-9.]+/","",$v);
return round($v,$round);
}


// All print functions moved to includes/print_irgf.php
?>