<?php
require_once('includes/global.inc.php');
//require_once('includes/print_.php');
require_once('includes/pdf.php');



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
 
 
$tmpl=loadPage("my_reviews", 'Internal Grant Reviews');
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
    
    

    
   unset ($sql); 
    // If not a new file,  save any form data that arrived
   if(isset($_REQUEST['form_irgf_id'])) if($_REQUEST['form_irgf_id'] > 0) if(isset($_REQUEST['saveme'])) if($_REQUEST['saveme']=='true' ) {
      ///To Do: Ensure this is a valid reviewer 
      
       
       if($_REQUEST['section']=='review'){
           $originality_exp = (isset($_REQUEST['originality_exp'])) ? mysql_real_escape_string($_REQUEST['originality_exp']) : '';
           $developed_exp = (isset($_REQUEST['developed_exp'])) ? mysql_real_escape_string($_REQUEST['developed_exp']) : '';
           $methodology_exp = (isset($_REQUEST['methodology_exp'])) ? mysql_real_escape_string($_REQUEST['methodology_exp']) : '';
           $experience = (isset($_REQUEST['experience'])) ? mysql_real_escape_string($_REQUEST['experience']) : '';
           $weaknesses = (isset($_REQUEST['weaknesses'])) ? mysql_real_escape_string($_REQUEST['weaknesses']) : '';
           $sql="  UPDATE `forms_irgf_reviews` SET           
           `originality_exp` = '{$originality_exp}',
           `originality` = '{$_REQUEST['originality']}',
           `developed_exp` = '{$developed_exp}',
           `developed` = '{$_REQUEST['developed']}',
           `methodology_exp` = '{$methodology_exp}',
           `methodology` = '{$_REQUEST['methodology']}',
           `experience` = '{$experience}',
           `weaknesses` = '{$weaknesses}'
           
            WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'
            ";
           
       }
          
        
        
        if(isset($sql)){
            $sql2="SELECT * FROM forms_irgf WHERE `form_irgf_id` = '{$_REQUEST['form_irgf_id']}'";
            $form=$db->getRow($sql2);
            if(is_array($form)) if($form['reviewer_id']==$user['user_id']){
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
        		$sql="UPDATE forms_irgf_reviews SET status=1 WHERE form_irgf_id=$_REQUEST[form_irgf_id]";
        		$db->Execute($sql);
        	
        	
        	//Inform the ORS and the applicant

        	
        	
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
                    	WHERE reviewer_id={$user['user_id']}
                    	AND status=1
                    	ORDER BY created desc";
                $forms=$db->getAll($sql);
                
                
                if(is_array($forms)) if(count($forms) > 0){
                $output.="<tr>
                		<td width='100'><u>PI</u></td>
                		<td width='*'><u>Title</u></td>
                		<td width='90' align='center'><u>Submitted</u></td>
                		<td width='90' align='center'><u>Reviewed</u></td>
                		<td width='50' align='center'><u>View App</u></td>
                		<td width='50' align='center'><u>View CV</u></td>
                		
                		</tr>\n";
               		foreach($forms as $form){
               			//grab the associated review. If it is missing, create it.
               			//note: reviews are stored separately as they may change independent of the main form, and visa-versa
               			$sql="SELECT * from forms_irgf_reviews 
               					WHERE form_irgf_id=$form[form_irgf_id]
               					";
               			$review=$db->getRow($sql);
               			if(count($review == 0))
               			{
               				$sql="INSERT INTO `research`.`forms_irgf_reviews` (`form_irgf_id`, `originality`, `originality_exp`, `developed`, `developed_exp`, `methodology`, `methodology_exp`, `experience`, `weaknesses`, `status`, `submit_date`) 
               				VALUES ($form[form_irgf_id], 
               				0, 
               				'',
               				0,
               				'',
               				0, 
               				'', 
               				'', 
               				'', 
               				0, 
               				NOW());";
               				$db->Execute($sql);		
               				$sql="SELECT * from forms_irgf_reviews 
               					WHERE form_irgf_id=$form[form_irgf_id]";
               				$review=$db->getRow($sql);
               			}
               			if($review['status']==0){
                        	$form_name= "<a href='/my_reviews.php?gotosection=info&form_irgf_id=$form[form_irgf_id]'>Review of " . $form['irgf_name']." (#".$form['form_irgf_id'].")</a>";
                        	$rev_date='';
                        }
                        else {
                        	$form_name="Review of " . $form['irgf_name']." (#".$form['form_irgf_id'].")";
                        	$rev_date=date($niceday,strtotime($review['submit_date']));
                        }
                        	
                        $modified= date($niceday,strtotime($form['modified']));

                         //check if document can be viewed in PDF yet.
                        if(1) $pdf= "<button title='Print PDF' type='button' value='viewpdf' onClick='javascript: window.location=\"/my_irgf.php?printpdf&form_irgf_id=$form[form_irgf_id]\";'><img src='/images/icon-sm-pdf2.gif'></button>";
						$sql="SELECT * FROM users WHERE user_id=$form[user_id]";
						$pi=$db->getRow($sql);
						if($pi) $username=$pi['first_name'].' '.$pi['last_name'];
						else $username='';
						
						$cv="<button title='View CV' type='button' onClick=\"DoPrint('mycv$form[cv]')\"><img src='/images/icon-sm-pdf2.gif'></button>";
                        
                        $output.="<tr>
                        		<td valign='top'>$username</td>
                        		<td valign='top'>$form_name</td>
                        		<td valign='top' nowrap align='center'>$modified</td>
                        		<td valign='top' nowrap align='center'>$rev_date</td>
                        		<td valign='top' align='center'>$pdf</td>
                        		<td valign='top' align='center'>$cv</td>
                        		</tr>\n";
                        
                    }//foreach form
                }//if count forms > 0  
                $tmpl->addVar('list','output',$output);
                
             break;
             
             
             
             
             
             
             case  'info':
                $section='info';
                 $tmpl->addVar('chooser','hilite_info','here');             
                $sql= "SELECT * FROM forms_irgf LEFT JOIN users on (forms_irgf.user_id=users.user_id) WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                     $form['name']=$form['first_name'].' '.$form['last_name'];         
                     $form['link']= "<button title='Print PDF' type='button' value='viewpdf' onClick='javascript: window.location=\"/my_irgf.php?printpdf&form_irgf_id=$form[form_irgf_id]\";'><img src='/images/icon-sm-pdf2.gif'></button>  ";
					                  
                     
                     $tmpl->addVars('info',$form);
                 }//if isarray
                
             break;
             
             case 'review':
             	$section="review";
             	$tmpl->addVar('chooser','hilite_review','here'); 
             	$sql= "SELECT * FROM forms_irgf_reviews WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
                 	switch ($form['originality']){
                 	case '1': $form['originality1']='checked'; break;
                 	case '2': $form['originality2']='checked'; break;
                 	case '3': $form['originality3']='checked'; break;
                 	case '4': $form['originality4']='checked'; break;
                 	}
                 	switch ($form['developed']){
                 	case '1': $form['developed1']='checked'; break;
                 	case '2': $form['developed2']='checked'; break;
                 	case '3': $form['developed3']='checked'; break;
                 	case '4': $form['developed4']='checked'; break;
                 	}
                 	switch ($form['methodology']){
                 	case '1': $form['methodology1']='checked'; break;
                 	case '2': $form['methodology2']='checked'; break;
                 	case '3': $form['methodology3']='checked'; break;
                 	case '4': $form['methodology4']='checked'; break;
                 	}
                 
                 	$tmpl->addVars('review',$form);
                 }
              break;
              
         
          
             
             case 'submit':
                $section='submit';
                $tmpl->addVar('chooser','hilite_submit','here'); 
                $sql= "SELECT * FROM forms_irgf_reviews WHERE
                        form_irgf_id=$_REQUEST[form_irgf_id]";
                 $form= $db->getRow($sql);
                 if(is_array($form)){
					$form['disabled']='';
					if(	$form['originality']==0 ||
						$form['originality_exp']=='' ||
						$form['developed']==0 ||
						$form['developed_exp']=='' ||
						$form['methodology']==0 ||
						$form['methodology_exp']=='' ||
						$form['experience']=='' ||
						$form['weaknesses']==''
						) {
						$form['disabled']='disabled';
						$form['checktext']="Please complete all items on the 'Review' page";
					}
					
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