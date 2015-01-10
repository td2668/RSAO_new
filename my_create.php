<?php
require_once('includes/global.inc.php');
//require_once('includes/print_.php');
require_once('includes/pdf.php');
require_once('includes/print_irgf.php');



/**
 * The  Form follows a standard layout with a set of top headers that define each page/section
 * auto-saving wherever possible, and an initial list. 
 * It will rely on data entered on the tracking form, and so a number of things that might be redundant are
 * not included. 
 *
 * Mistakenly or not, this currently uses a single file for all sections with the print functions broken out into an include
 * It may be sensible in the future to break up into section documents for ease of maintenance.
 *
 */
 
 
$tmpl=loadPage("my_create", 'CREATE Registration');
//showMenu("my_forms");

 //print_r($_REQUEST);
if (sessionLoggedin()) {
	$tmpl->setAttribute('mainform','visibility','visible');
    $username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);

    if(is_array($user) == false or count($user) == 0) {
        displayBlankPage("Error","<h1>Error</h1>There was a problem finding your user record.");
        die;
    } 
    
    //Print as PDF
    if(isset($_REQUEST['printpdf']) && isset($_REQUEST['form_create_id'])){
        $sql="SELECT form_create_id FROM forms_create WHERE form_create_id=$_REQUEST[form_create_id]";
        $form=$db->getAll($sql);
        if(count($form)>0) printPDF($_REQUEST['form_create_id'],$user,$db);
    }
    
    // Handle all the button actions first 
    if(isset($_REQUEST['delete']) && isset($_REQUEST['form_create_id'])){
        $sql="DELETE FROM `forms_create` WHERE `form_create_id` = '{$_REQUEST['form_create_id']}'";
        $result=$db->Execute($sql);
        if(!$result) print($db->ErrorMsg());
        $_REQUEST['gotosection']='list';
        unset($_REQUEST['form_create_id']);
    }
    
    if(isset($_REQUEST['delete_coresearcher'])) {
		if($_REQUEST['delete_coresearcher']>0){
			$db->Execute("DELETE FROM forms_create_coresearchers WHERE fcc_id=$_REQUEST[delete_coresearcher]");
			}
		$message="Deleted";    
	}
	
	if(isset($_REQUEST['add_coresearcher'])) {
		$coresearcher_id=($_REQUEST['coresearcher_id']=='') ? 0 : $_REQUEST['coresearcher_id'];
       if($coresearcher_id>0) {$co_last=''; $co_first='';}
       else {
           	$co_last=mysql_real_escape_string($_REQUEST['co_last']);
           	$co_first=mysql_real_escape_string($_REQUEST['co_first']);
       }
	   $result=$db->Execute("INSERT INTO forms_create_coresearchers SET
								user_id=$coresearcher_id,
								lastname='$co_last',
								firstname='$co_first',
								fc_id=$_REQUEST[form_create_id] ");
		if(!result) print($db->ErrorMsg());
		
	}

    
    if(isset($_REQUEST['copy']) && isset($_REQUEST['form_create_id'])){
        $sql="SELECT * FROM `forms_create` WHERE `form_create_id` = '{$_REQUEST['form_create_id']}'";
        $form=$db->getRow($sql);
        if(!$result) print($db->ErrorMsg());
        //modify values
        $name=mysql_real_escape_string($form['create_name'].' Copy');
        $form['summary']= mysql_real_escape_string($form['summary']);
        
        
        $sql="INSERT INTO `research`.`forms_create` SET
        		`created`=NOW(),
        		`modified`=NOW(),
        		`user_id`=$form[user_id],
        		`create_name`='$name',
        		`summary`='$form[summary]',
        		`status`=0,
        		`iagree`=0,
        		`course`='$form[course]',
        		`supervisor_id`=$form[supervisor_id],
        		`supervisor_last`='".mysql_real_escape_string($form['supervisor_last'])."',
        		`supervisor_first`='".mysql_real_escape_string($form['supervisor_first'])."',
        		`pref`='$form[pref]',
        		`reb_req`=0,
        		`reb_status`=0
        		
        		
        		";
        //save new
        //echo $sql;
        $result=$db->Execute($sql);
        if(!result) print($db->ErrorMsg());
        
        
        
        $_REQUEST['gotosection']='list';
        unset($_REQUEST['form_create_id']);
    }
    
    
    
    
   unset ($sql); 
    // If not a new file,  save any form data that arrived
   if(isset($_REQUEST['form_create_id'])) if($_REQUEST['form_create_id'] > 0) if(isset($_REQUEST['saveme'])) if($_REQUEST['saveme']=='true' ) {
       
       if($_REQUEST['section']=='info'){
           $create_name = (isset($_REQUEST['create_name'])) ? mysql_real_escape_string($_REQUEST['create_name']) : '';
           $which_fund = (isset($_REQUEST['which_fund'])) ? mysql_real_escape_string($_REQUEST['which_fund']) : '';
           $form_create_id= (isset($_REQUEST['form_create_id']) ? $_REQUEST['form_create_id'] : 0);
           $timeslots = (isset($_REQUEST['timeslots']))?$timeslots = implode(",", $_REQUEST['timeslots']): "";
           $department_id= (isset($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0);
           
           $supervisor_id=($_REQUEST['supervisor_id']=='') ? 0 : $_REQUEST['supervisor_id'];
           if($supervisor_id>0) {$supervisor_last=''; $supervisor_first='';}
           else {
	           	$supervisor_last=mysql_real_escape_string($_REQUEST['supervisor_last']);
	           	$supervisor_first=mysql_real_escape_string($_REQUEST['supervisor_first']);
	       }
	       $course=($_REQUEST['course']=='') ? '' : mysql_real_escape_string($_REQUEST['course']);
	       //$program=($_REQUEST['program']=='') ? '' : mysql_real_escape_string($_REQUEST['program']);
           $type=($_REQUEST['type']=='') ? 0 : $_REQUEST['type'];
           if(isset($_REQUEST['slam'])) $slam=true; else $slam=0;
           if(isset($_REQUEST['nojudging'])) $nojudging=true; else $nojudging=0;
           
           //$modified=mktime();   
           //echo ("Project ID is $project_id");
           
           $sql="  UPDATE `forms_create` SET 
           `form_create_id` = '{$form_create_id}',
           `modified` = NOW(),
           `create_name` = '{$create_name}',
           `supervisor_id`=$supervisor_id,
		   `supervisor_last`='$supervisor_last',
		   `supervisor_first`='$supervisor_first',
		   timeslots='$timeslots',
		   type=$type,
		   course='".$course."',
		   department_id=$department_id,
		   slam=$slam,
		   nojudging=$nojudging
          
           WHERE `form_create_id` = '{$_REQUEST['form_create_id']}'
           ";
       }
       
       if($_REQUEST['section']=='summary'){
           $summary = (isset($_REQUEST['summary'])) ? mysql_real_escape_string($_REQUEST['summary']) : '';
           $sql="  UPDATE `forms_create` SET           
           `summary` = '{$summary}'
            WHERE `form_create_id` = '{$_REQUEST['form_create_id']}'
            ";
           
       }
       
        
        if($_REQUEST['section']=='submit'){
            //$trackoptions = (isset($_REQUEST['trackoptions'])) ? mysql_real_escape_string($_REQUEST['trackoptions']) : '';
            //$documents = (isset($_REQUEST['documents'])) ? mysql_real_escape_string($_REQUEST['documents']) : '';
            $iagree= (isset($_REQUEST['iagree'])) ? 1 : 0;
            $reb_req= (isset($_REQUEST['reb_req'])) ? $_REQUEST['reb_req'] : 0;
            $reb_status= (isset($_REQUEST['reb_status'])) ? $_REQUEST['reb_status'] : 0;
            //Process reviewer cvs
            //Need the form again
            $sql="SELECT * FROM forms_create WHERE form_create_id=$_REQUEST[form_create_id]";
            $form=$db->getRow($sql);
        
    		
    		//else $response='Doesnt seem to be set';
    		//if(isset($response)) echo $response;
    		//var_dump($_FILES);
    
            $sql=" UPDATE `forms_create` SET
                `iagree` = {$iagree},
                reb_req = {$reb_req},
                reb_status = {$reb_status}
                
                WHERE `form_create_id` = '{$_REQUEST['form_create_id']}'
            ";
            
        }
          
        
        
        if(isset($sql)){
            $sql2="SELECT * FROM forms_create WHERE `form_create_id` = '{$_REQUEST['form_create_id']}'";
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
        	if($_REQUEST['locksubmit']=='true' and isset($_REQUEST['form_create_id'])){
        	//If this fired then there is a completed tracking form and all other fields are verified.
        	//Lock the application from user editing (user can still print and copy)
        		$sql="UPDATE forms_create SET status=1 WHERE form_create_id=$_REQUEST[form_create_id]";
        		$db->Execute($sql);
        	
        	
        	//Inform the ORS and the reviewer
        	//?? Idea: send the reviewer an attached PDF - but they still have to log in to provide their comments. 
        	//         So that means I need another queue for 'reviews'
        	//         And don't forget the email reminders in the cronjob.
        	
        	
        }
        
   }    //if - save form data
   
   //Create a new record (only the name arrives with the REQUEST)
    if(isset($_REQUEST['section'])) if( $_REQUEST['section']=='new'){
        //create a new entry
        //first need to figure out what date to use. Anything from Sept X-1 to May X
        $today=getdate();
        if($today['mon']>8) $year=$today['year']+1; else $year=$today['year']; 
        $cr_name = (isset($_REQUEST['newname'])) ? mysql_real_escape_string($_REQUEST['newname']) : '';
        $cv = 1; // default is to include MRU CV
        $sql=" INSERT into `forms_create`  SET
        		`created`=NOW(),
        		`modified`=NOW(),
        		`user_id`=$user[user_id],
        		`create_name`=''   		
        		";	      
         $result=$db->Execute($sql);
         if(!$result) print($db->ErrorMsg());
         $_REQUEST['form_create_id']= mysql_insert_id();
         //echo ("Formirgf ID = $_REQUEST[form_irgf_id]");
         $_REQUEST['gotosection']='info';
    }
   
   
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
        if(isset($_REQUEST['form_create_id'])) if(isset($_REQUEST['gotosection']) && $_REQUEST['form_create_id']=='') $_REQUEST['gotosection']='list';
        
         switch($_REQUEST['gotosection']){
             
             
             case "list":
             default:
                $tmpl->addVar('savecontrol','disabled','disabled');
                $tmpl->setAttribute('chooser','visibility','hidden');

                $section='list';
                
                $item=array(); $list=array();$output='';
                
                
                
                $sql="SELECT * FROM
                		`forms_create`
                    	WHERE user_id={$user['user_id']}
                    	ORDER BY created desc";
                $forms=$db->getAll($sql);
                   
                if(is_array($forms)) if(count($forms) > 0){
	                $tmpl->setAttribute('list','visibility','visible');
	                $op=array();
               		foreach($forms as $form){
                        
                        $form['created'] = date($niceday,strtotime($form['created']));
                        $form['modified']= date($niceday,strtotime($form['modified']));
                        
                        //Check to see if it has been submitted yet or not.
                        if($form['status'] ==0)  $form['delete']= "<button class='' title='Delete' type='button' name='delete' value='delete' onClick='javascript: if(confirm(\"Really delete?\")) window.location=\"/my_create.php?delete&form_create_id=$form[form_create_id]\";'><img src='/images/icon-sm-trash.gif'></button>";
                        else $form['delete']='';
                        
                        if ($form['status']==0) $form['status']='In Progress';
                        if ($form['status']==1) $form['status']='Submitted';
                        if ($form['status']==2) $form['status']='Approved';
                        $op[]=$form;
                        
                    }//foreach form
                    $tmpl->addRows('listitems',$op);
                    $tmpl->setAttribute('listitems','visibility','visible');
                }//if count forms > 0  
                //Otherwise add a prompt
                else $tmpl->setAttribute('emptymessage','visibility','visible');
                
             break;
             
             
             
             
             
             
             
             case  'info':
                $section='info';
                 $tmpl->addVar('chooser','hilite_info','here');             
                $sql= "SELECT * FROM forms_create WHERE
                        form_create_id=$_REQUEST[form_create_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                     //if this is not owned by the user then everything on this page is disabled.
                     if($form['user_id'] != $user['user_id']) {
                         $form['disabled']='disabled';
                         $tmpl->addVar('savecontrol','disabled','disabled');
                         $delbutton='disabled';
                     }
                     else $delbutton='';
                     
                     $sql="SELECT CONCAT(last_name,', ',first_name) as name,user_id FROM `users` where 1 ORDER BY last_name, first_name";
                     $theusers=$db->Execute($sql);
                     if($theusers){
	                 	$form['supervisor_options']=$theusers->GetMenu2("supervisor_id",$form['supervisor_id'],true,false);    
	                 }
	                 $theusers->Move(0);
                     if($theusers){
	                 	$form['coresearcher_options']=$theusers->GetMenu2("coresearcher_id",'',true,false);    
	                 } 
	                 $crs=$db->Execute("SELECT 
	                 						CONCAT(fcc.lastname,', ',fcc.firstname) as name, fcc.fcc_id as id,
	                 						CONCAT(u.last_name,', ',u.first_name) as dbname, u.user_id as u_id
	                 					FROM forms_create_coresearchers as fcc 
	                 					LEFT JOIN users as u using (user_id)
	                 					WHERE fcc.fc_id=$_REQUEST[form_create_id]
	                 					ORDER BY lastname,firstname");
	                 
	                 if($crs->RecordCount()>0){
		                 //echo("Count: ".$crs->RecordCount());
		                 $colist=array();
		                 $tmpl->SetAttribute('coresearcher_list','visibility','visible');
		                 foreach($crs as $cr){
		                 	if($cr['dbname']!=''){ $cr['name']=$cr['dbname']; }
		                 	$colist[]=$cr;
		                 	
		                 }
		                 
		                 $tmpl->AddRows('coresearcher_list',$colist);
		             }       
		             $thisyear=GetSchoolYear(time());
		             $slots=explode(',',$form['timeslots']);
		             $timeslots=$db->Execute("SELECT CONCAT(type,': ',slot) as name,id FROM forms_create_timeslots WHERE year=$thisyear ORDER BY type,id");
		             if($timeslots->RecordCount()>0){
			             $form['timeslots']=$timeslots->GetMenu2("timeslots",$slots,true,true,$timeslots->RecordCount()+1);
			         }
		             if($form['slam']) $form['slam']="checked"; else $form['slam']='';
		             if($form['nojudging']) $form['nojudging']="checked"; else $form['nojudging']='';
                     $form['created']= date($niceday,strtotime($form['created']));
                     $form['modified']= date("$niceday G:i",strtotime($form['modified']));
					 
					 $depts=$db->Execute("SELECT name,department_id FROM departments ORDER BY name");
					 $form['department_options']=$depts->GetMenu2("department_id",$form['department_id']);
					 
					 $cats=$db->Execute("SELECT name,cat_id FROM forms_create_categories");
					 $form['cat_options']=$cats->GetMenu2("type",$form['type']);
					 
					 $form['course']=htmlentities($form['course']);
					 $form['program']=htmlentities($form['program']);
					 //$form['section']=$section;
                     
                    //print_r($form);
                     $tmpl->addVars('info',$form);
                 }//if isarray
                
             break;
             
             case 'summary':
             	$section="summary";
             	$tmpl->addVar('chooser','hilite_summary','here'); 
             	$sql= "SELECT * FROM forms_create WHERE
                        form_create_id=$_REQUEST[form_create_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                 	$tmpl->addVars('summary',$form);
                 }
              break;
              
             	
 
                         
             case 'submit':
                $section='submit';
                $tmpl->addVar('chooser','hilite_submit','here'); 
                $sql= "SELECT * FROM forms_create WHERE
                        form_create_id=$_REQUEST[form_create_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                     
                     if($form['iagree']) $form['checkagree']='checked';
                     
                     switch($form['reb_req']){
	                    case 1 :  $form['REB_REQ_1']="checked=''";break;
	                    case 2 :  $form['REB_REQ_2']="checked=''";break;
	                    case 3 :  $form['REB_REQ_3']="checked=''";break;
	                 }
	                 switch($form['reb_status']){
	                    case 1 :  $form['REB_STATUS_1']="checked=''";break;
	                    case 2 :  $form['REB_STATUS_2']="checked=''";break;
	                    case 3 :  $form['REB_STATUS_3']="checked=''";break;
	                 }
                     
                     $form['user_id']= $user['user_id'];
                
                     if(isset($response)){
                     	$form['response']= "<tr><td>&nbsp;</td><td class='enfasis'>$response</td></tr>";
                     }
                                         
                     //Do the checks 
                     $form['disabled']='';
                     $form['checktext']='';
                      
                     if($form['summary']=='') { $form['disabled']='disabled'; $form['checktext'].="<li>Enter at least a brief description in the 'Summary' section. You can return to finish it later.</li>";} 
                     if($form['type']==0) { $form['disabled']='disabled'; $form['checktext'].="<li>Indicate what type of presentation</li>";} 

                     
                     if($form['disabled']!='') $tmpl->setAttribute('checks','visibility','visible');
                     
                     $cats=$db->GetRow("SELECT name,cat_id FROM forms_create_categories WHERE name='Poster'");
                     if($cats && $form['type']==$cats['cat_id']) $tmpl->setAttribute('posterprint','visibility','visible');
                    $tmpl->addVars('submit',$form);
                 }
             break;
             
             
         } //switch 

     $tmpl->addVar('header','target',$_SERVER['PHP_SELF']);
     $tmpl->addVar('section','section',$section) ;
     $tmpl->addVar('mainform','section',$section) ;

	 showMenu("ugr_intro",$tmpl); 


    $tmpl->displayParsedTemplate('page');

    //print_r($_REQUEST); 

} //logged in user

else {
	if(isset($_REQUEST['registered'])) $tmpl->setAttribute('justregistered','visibility','visible');
	else $tmpl->setAttribute('loginplease','visibility','visible');
	$tmpl->addVar('header','target',$_SERVER['PHP_SELF']);
	
	$tmpl->displayParsedTemplate('page');
	
}

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