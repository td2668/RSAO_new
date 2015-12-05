
<?php
	/**
	 * List and manipulate CREATE registrations
	 *
	 * 
	 *
	 * PHP version 5
	 *
	 * LICENSE: This source file is subject to version 3.01 of the PHP license
	 * that is available through the world-wide-web at the following URI:
	 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
	 * the PHP License and are unable to obtain it through the web, please
	 * send a note to license@php.net so we can mail you a copy immediately.
	 *
	 * @package    orsadmin
	 * @author     Trevor Davis
	 * @author     
	 * @copyright  2010-2015 TDavis
	 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
	 * @version    SVN: $Id$

	 */
	
    require("includes/config.inc.php");
    require("includes/functions-required.php");
    
    $hdr=loadPage("header",'Header');

	$menuitems=array();
	$menuitems[]=array('title'=>'Add','url'=>'srd.php?add');
	$menuitems[]=array('title'=>'List','url'=>'srd.php?section=view');
	$menuitems[]=array('title'=>'Timeslots','url'=>'srd.php?section=timeslots');
	$hdr->AddRows("list",$menuitems);
    
    $tmpl=loadPage("srd", 'CREATE Registration');
    //print_r($_REQUEST);
    //Manage the SRD table(s)

    //colors for table
    
    $yesColor = "lightgreen";
    $noColor = "orange";
    $maybeColor = "#FFF380";
    $ignoreColor = "";

    $sort = 'name';  // default sort
    if(isset($_REQUEST['sort'])) {
        $sort = $_REQUEST['sort'];
    }

    $success='';
    
    if(isset($_REQUEST['add'])){
        $sql="INSERT into forms_create set modified=NOW(), created=NOW();";
        if($db->Execute($sql) === false)
        	$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
        else {
	        $success="Started";
			$_REQUEST['id']=mysql_insert_id();
			$_REQUEST['section']='edit';
			}
			
        //print_r($_REQUEST);
    }
    
    if(isset($_REQUEST['add_cores'])) {
		$coresearcher_id=($_REQUEST['cores_add']=='') ? 0 : $_REQUEST['cores_add'];
       if($coresearcher_id>0) {$co_last=''; $co_first='';}
       else {
           	$co_last=mysql_real_escape_string($_REQUEST['co_last']);
           	$co_first=mysql_real_escape_string($_REQUEST['co_first']);
       }
	   $result=$db->Execute("INSERT INTO forms_create_coresearchers SET
								user_id=$coresearcher_id,
								lastname='$co_last',
								firstname='$co_first',
								fc_id=$_REQUEST[id] ");
		if(!result) print($db->ErrorMsg());
		
	}
    
    
    if(isset($_REQUEST['delete'])){
        
        if(isset($_REQUEST['id'])) {
            
            $sql="DELETE from forms_create WHERE form_create_id={$_REQUEST['id']}";
            //echo $sql;
            if($db->Execute($sql) === false)
			$success= "<font color='red'>Error deleting: ".$db->ErrorMsg()."</font>";
			else $success="Deleted";
			$db->Execute("DELETE FROM forms_create_coresearchers WHERE fc_id={$_REQUEST['id']}");
			//DElete File
			
        }
        
    }
    if(isset($_REQUEST['deleteid'])) if($_REQUEST['deleteid'] != '') {
	    $sql="DELETE FROM forms_create_coresearchers WHERE fcc_id=$_REQUEST[deleteid]";
	    if($db->Execute($sql) === false)
        $success= "<font color='red'>Error deleting: ".$db->ErrorMsg()."</font>";
      else $success="Deleted";
    }
    
if(isset($_REQUEST['move'])){

        $debug=FALSE;
        
        if(isset($_REQUEST['id'])) {
        	$success='';
        	
        	$sql="SELECT * FROM srd_reg WHERE srd_reg_id=$_REQUEST[id]";
        	$srd=$db->GetRow($sql);
        	if($srd){
        		$srd['title']=mysql_real_escape_string($srd['title']);
        		$srd['descrip']=mysql_real_escape_string($srd['descrip']);
        		$srd['program']=mysql_real_escape_string($srd['program']);
        		$srd['url']=mysql_real_escape_string($srd['url']);
        		if($srd['hreb']=='yes')$srd['hreb']=1; else $srd['hreb']=0;
        		if($srd['hreb2']=='yes')$srd['hreb2']=1; else $srd['hreb2']=0;
  //       Insert dates of projects
  				$now=getdate();
  				$startdate=$now['year']-1 . '-' . $now['mon'] . '-' . $now['mday'];
  				$enddate=$now['year'] . '-' . $now['mon'] . '-' . $now['mday'];

  
        		$sql="INSERT INTO student_research_projects (
            		supervisorID,
            		departmentID,
            		program,
            		course,
            		presentationType,
            		hrebNeedClearance,
            		hrebHaveClearance,
            		title,
            		description,
            		startDate,
            		endDate,
            		projectUrl
            		) 
            		VALUES(
            		$srd[supervisorId],
            		$srd[departmentId],
            		'$srd[program]',
            		'$srd[course]',
            		'$srd[pref]',
            		$srd[hreb],
            		$srd[hreb2],
            		'$srd[title]',
            		'$srd[descrip]',
            		'$startdate',
            		'$enddate',
            		'$srd[url]'
            		)";
            	//echo($sql);
				if(!$debug){
					if($db->Execute($sql) === false)
	        			$success.= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
      			}
      			else echo($sql.'<br>');
//TODO      			//Check if they exist already 
      			
      			
      			$order=1;
      			$first=mysql_real_escape_string($srd['firstName']);
      			$last=mysql_real_escape_string($srd['lastName']);
      			$id=mysql_insert_id();
      			$sql="INSERT INTO student_researchers (
      					first,
      					last,
      					email,
      					studentID,
      					lastModified,
      					aorder) 
      					VALUES(
      					'$first',
      					'$last',
      					'$srd[email]',
      					'$srd[studentid]',
      					NOW(),
      					$order
      					)";
      			//echo($sql);
      			if(!$debug){
		  			if($db->Execute($sql) === false)
		    			$success.= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
		    		else $lastid=mysql_insert_id();
      			}
      			else echo($sql.'<br>');
      			
      			if(isset($lastid)){
	      			$sql="INSERT INTO student_research (studentResearcherID,researchProjectID) 
	      					VALUES($lastid,$id)";
	      			
	      			if($db->Execute($sql) === false)
	        			$success.= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
	      		}	
      			else echo("Didn't insert xref<br>");
      			
      			$sql = "SELECT * FROM srd_researchers WHERE srd_reg_id = $srd[srd_reg_id]";
                $cores = $db->getAll($sql);
                if(count($cores)>0) {
                	foreach($cores as $core){
 //TODO               	//Do they exist already?
                		$order++;
                		$first=mysql_real_escape_string($core['first']);
      					$last=mysql_real_escape_string($core['last']);
                		$sql="INSERT INTO student_researchers (
                				first,
                				last,
                				lastModified,
                				aorder)
                				VALUES(
                				'$first',
                				'$last',
                				NOW(),
                				$order
                				)";
                	if(!$debug){
	                	if($db->Execute($sql) === false)
	        				$success.= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
	        			else $lastsid=mysql_insert_id();
      				}
      				else echo($sql.'<br>');
      				
      				if(isset($lastsid)){
	      				$sql="INSERT INTO student_research (studentResearcherID,researchProjectID) 
	      					VALUES($lastsid,$id)";
	      				if($db->Execute($sql) === false)
	        				$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
	        			}
	        			else echo("Did not insert student xref: $sql<br>");
      				
                	}
                }
                if($success==''){
                	if(!$debug){
	                	$sql="UPDATE srd_reg SET moved='1' WHERE srd_reg_id=$_REQUEST[id]";
	                	if($db->Execute($sql) === false)
	        				$success.= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
	        		}
                }
                else $success.="Errors encountered. The move was only partially successful.";
         		//$regs[$key]['submit_date']=date('Y-m-d, H:m',strtotime($reg['submit_date']));
        	}
  //***** Section changed to the new Project Checker one.
      		
      		//$_REQUEST['section']='project';
      		
      		
            
            //$arr = $db->ErrorMsg();
            //print_r($arr);
        }   
    }
    
    if(isset($_REQUEST['extract'])){
    	if(isset($_REQUEST['id'])){
    		//fire a message to the site admin to notify
    		//print_r($_SERVER);
      		 if(!isset($_SERVER['PHP_AUTH_USER'])) $success="EMAIL not set to send";
      		 else {
      		 
      		 $sql="SELECT * FROM srd_reg WHERE srd_reg_id=$_REQUEST[id]";
      		 $proj=$db->GetRow($sql);
      		 if($proj){
      		 	$title=$proj['title'];
      		 	$descrip=$proj['descrip'];
      		 }
      		 else {$title="Unknown"; $descrip="Unknown";}
      		 //error_reporting(E_ALL);
		      include_once("includes/mail-functions.php");
              
		      $mail_queue = new Mail_Queue( $configInfo['email_db_options'], $configInfo['email_options'] );
              
		      $mime = new Mail_mime();
		      
		      $from = 'research@mtroyal.ca';
		      $from_name = 'SRD Bot';
		
		
		      if ( $configInfo["debug_email"] ) {
		            $recipient = $configInfo["debug_email"];
		            $recipient_name = $configInfo["debug_email_name"];
		      } else {
		        $recipient = 'trevor.davis@viu.ca';
		        $recipient_name = "$_SERVER[PHP_AUTH_USER]";
		      }
		      $from_params = empty( $from_name ) ? '<' . $from . '>' : '"' . $from_name . '" <' . $from . '>';
		      $recipient_params = empty( $recipient_name ) ? '<' . $recipient . '>' : '"' . $recipient_name . '" <' . $recipient . '>';
		      $hdrs = array(
		        'From' => $from_params,
		        'To' => $recipient_params,
		        'Subject' => "Abstract for $proj[firstName] $proj[lastName]",
		        );
		    
		      $message = "
NAME: $proj[firstName] $proj[lastName]
TITLE: $title
ABSTRACT: $descrip
		        ";
		    $mime->setTXTBody( $message );
		
		    $body = $mime->get();
		    $hdrs = $mime->headers( $hdrs );
		    
		    $result = $mail_queue->put( $from, $recipient, $hdrs, $body );
		    
		    if ( $configInfo["email_send_now"] ) {
                echo "ready to send";
		        $send_result = $mail_queue->sendMailById( $queueMailId );
                
		    }
		   }  
    	}
    	$success="Email Sent";
    }
    
    if(isset($_REQUEST['extractall'])){
    	//fire a message to the site admin to notify
    		//print_r($_SERVER);
    		$message='';
    		
      		 if(0);
      		 else {
      		 
      		 require_once "Mail/Queue.php";
      		 
		     $mail_queue = new Mail_Queue( $configInfo['email_db_options'], $configInfo['email_options'] );
		      
		     $mime = new Mail_mime();
		
		      $from = 'research@viu.ca';
		      $from_name = 'SRD Bot';
		
		
		      if ( $configInfo["debug_email"] ) {
		            $recipient = $configInfo["debug_email"];
		            $recipient_name = $configInfo["debug_email_name"];
		      } else {
		        $recipient = 'kathryn.jepson@viu.ca';
		        $recipient_name = "Kathryn";
		      }
		      $from_params = empty( $from_name ) ? '<' . $from . '>' : '"' . $from_name . '" <' . $from . '>';
		      $recipient_params = empty( $recipient_name ) ? '<' . $recipient . '>' : '"' . $recipient_name . '" <' . $recipient . '>';
		      $hdrs = array(
		        'From' => $from_params,
		        'To' => $recipient_params,
		        'Subject' => "Full List of Student Presentations",
		        );

      		 $srd_year=GetSchoolYear(time());
      		 $sql="SELECT fc.*, dep.name as departmentName, CONCAT(users.first_name, ' ', users.last_name) AS pi, profiles.email as email
			 		FROM forms_create as fc
			 		LEFT JOIN departments AS dep ON fc.department_id = dep.department_id
			 		LEFT JOIN users ON fc.user_id = users.user_id
			 		LEFT JOIN profiles ON users.user_id=profiles.user_id
			 		WHERE 1
			 		AND (
		    	    (YEAR(submit_date)=$srd_year 
		    		AND MONTH(submit_date)>=1 
		    		AND MONTH(submit_date)<6) 
		    	  OR
		    		(YEAR(submit_date)=$srd_year-1
		    		AND MONTH(submit_date)>5 
		    		AND MONTH(submit_date)<=12)
		    	  OR
		    	    submit_date='000-00-00'
		    		)
		    	  
			 		";

      		 $projs=$db->GetAll($sql);
      		 $message="ID,PIName,Email,Modified,Title,Summary,DeptName,Course,Supervisor,Type,CoNames\r\n";
      		 foreach($projs as $proj){
	      		 if($proj){
		      		 if($proj['supervisor_id'] != 0) {
			      		 $super=$db->getRow("SELECT CONCAT(users.first_name, ' ', users.last_name) as super FROM users where user_id=$proj[supervisor_id]");
			      		 $supervisor=$super['super'];
			  		 }
			  		 else $supervisor=$proj['supervisor_first'].' '.$proj['supervisor_last'];
	      		 	//build list of topics
	      		 	$proj['create_name']=addslashes($proj['create_name']);
	      		 	$proj['summary']=str_replace(array("\r", "\n"), '', addslashes($proj['summary']));
	      		 	if($proj['type']>0){
                     	$sql="SELECT name,cat_id FROM forms_create_categories WHERE cat_id=$proj[type]";
					 	$cats=$db->GetRow($sql);
					 	$proj['type']=$cats['name'];
                     }
                     $conames="";
                     $cos=$db->getAll("SELECT fcc.*, CONCAT(users.first_name, ' ', users.last_name) as name 
                                        FROM forms_create_coresearchers as fcc 
                                        LEFT JOIN users on (fcc.user_id = users.user_id)
                                        WHERE fcc.fc_id=$proj[form_create_id]");
                     if(count($cos)>0){
	                     foreach($cos as $co) {
		                 	if($co['user_id'] != 0) $conames.=$co['name'].'; ';
		                 	else $conames.="$co[firstname]  $co[lastname]; ";
		                 }
	                 }
	      		 	
	      		 }
			      		    
			      $message .= "\"$proj[form_create_id]\",\"$proj[pi]\",\"$proj[email]\",\"$proj[modified]\",\"$proj[create_name]\",\"$proj[summary]\",\"$proj[departmentName]\",\"$proj[course]\",\"$supervisor\",\"$proj[type]\",\"$conames\"\r\n";
			}

		    $mime->setTXTBody( $message );
			if(!$mime->addAttachment($message, 'text/plain','create.csv',false)) echo "ERROR ATTACHING";
		    $body = $mime->get();
		    $hdrs = $mime->headers( $hdrs );
		    
		
		    $queueMailId = $mail_queue->put( $from, $recipient, $hdrs, $body );
		    echo("Mail queue ID = $queueMailId <br>");
		
		    if ( $configInfo["email_send_now"] ) {
		        $send_result = $mail_queue->sendMailById( $queueMailId );
		    }
		    //if(mail("trevor.davis@viu.ca",'CREATE CSV',"hi","From:trevor.davis@viu.ca")) $success="Email Sent";
		    //else $success="Error sending mail";
		   }  
	$success="Email Sent";
    }

    //print_r($_REQUEST);
    if(isset($_REQUEST['update']) || isset($_REQUEST['add_cores']) || isset($_REQUEST['deleteid'])){
        if(isset($_REQUEST['id'])){
	       $user_id= (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0);
	       $create_name = (isset($_REQUEST['create_name'])) ? mysql_real_escape_string($_REQUEST['create_name']) : '';
           $which_fund = (isset($_REQUEST['which_fund'])) ? mysql_real_escape_string($_REQUEST['which_fund']) : '';
           $form_create_id= (isset($_REQUEST['form_create_id']) ? $_REQUEST['form_create_id'] : 0);
           $timeslots = (isset($_REQUEST['timeslots']))?$timeslots = implode(",", $_REQUEST['timeslots']): "";
           $department_id= (isset($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0);
           if($department_id=='') $department_id=0;
           
           $supervisor_id=($_REQUEST['supervisor_id']=='') ? 0 : $_REQUEST['supervisor_id'];
           if($supervisor_id>0) {$supervisor_last=''; $supervisor_first='';}
           else {
	           	$supervisor_last=mysql_real_escape_string($_REQUEST['supervisor_last']);
	           	$supervisor_first=mysql_real_escape_string($_REQUEST['supervisor_first']);
	       }
	       $course=($_REQUEST['course']=='') ? '' : mysql_real_escape_string($_REQUEST['course']);
	       $program=($_REQUEST['program']=='') ? '' : mysql_real_escape_string($_REQUEST['program']);
           $type=($_REQUEST['type']=='') ? 0 : $_REQUEST['type'];
           if(isset($_REQUEST['slam'])) $slam=true; else $slam=0;
           if(isset($_REQUEST['nojudging'])) $nojudging=true; else $nojudging=0;
	       $summary = (isset($_REQUEST['summary'])) ? mysql_real_escape_string($_REQUEST['summary']) : '';
	       $iagree= (isset($_REQUEST['iagree'])) ? 1 : 0;
            $reb_req= (isset($_REQUEST['reb_req'])) ? $_REQUEST['reb_req'] : 0;
            $reb_status= (isset($_REQUEST['reb_status'])) ? $_REQUEST['reb_status'] : 0;
	       
	        
	        
	        $sql="  UPDATE `forms_create` SET 
	        user_id={$user_id},
	        create_name='{$create_name}',
           created='{$_REQUEST['created']}',
           `modified` = NOW(),
           `create_name` = '{$create_name}',
           `supervisor_id`=$supervisor_id,
		   `supervisor_last`='$supervisor_last',
		   `supervisor_first`='$supervisor_first',
		   timeslots='$timeslots',
		   type=$type,
		   course='".$course."',
		   program='{$program}',
		   department_id=$department_id,
		   slam=$slam,
		   nojudging=$nojudging,
		   `summary` = '{$summary}',
		   `iagree` = {$iagree},
            reb_req = {$reb_req},
            reb_status = {$reb_status}
          
           WHERE `form_create_id` = '{$_REQUEST['id']}'
           ";
	        
	        
	        
	        
	        
	        
	        
	    if($db->Execute($sql) === false)
        $success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
      else $success="Saved";
      }
      $_REQUEST['section']="edit";
    }
    
    if(!isset($_REQUEST['section'])) $_REQUEST['section']="view";
    
     switch($_REQUEST['section']){
        
         case 'view':
         	
         	$tmpl->setAttribute('view','visibility','visible');
            $tmpl->addVar('view', "YESCOLOR", $yesColor);
            $tmpl->addVar('view', "NOCOLOR", $noColor);
            $tmpl->addVar('view', "MAYBECOLOR", $maybeColor);

             // determine sorting for query
             $orderBy = "ORDER BY users.last_name, users.first_name ASC";
             if(isset($sort)) {
                if($sort == 'name') {
                    $tmpl->addVar('view', "NAMESORTCLASS", "class='arrow-down'");
                    $tmpl->addVar('view', "DATESORTCLASS", "");
                    $orderBy = "ORDER BY users.last_name, users.first_name";
                } elseif($sort == 'date') {
                    $tmpl->addVar('view', "DATESORTCLASS", "class='arrow-down'");
                    $tmpl->addVar('view', "NAMESORTCLASS", "");
                    $orderBy = "ORDER BY fc.submit_date DESC";
                }
             }


             $year_options='';
             if(isset($_REQUEST['year'])) 
             	{if($_REQUEST['year']=='') $srd_year = GetSchoolYear(time()); else $srd_year=$_REQUEST['year'];}
             	else $srd_year=GetSchoolYear(time());
             //echo ("SET SRD YEAR AS $srd_year ----------------------------");	

             //$srd_year=(isset($_REQUEST['year'])) ? $_REQUEST['year'] : GetSchoolYear(time());
             //while I'm here set up the menu for the year request
             for ($year=2012; $year<=GetSchoolYear(time())+1; $year++){
	         	if($year==$srd_year) $sel="selected"; else $sel='';
	         	$year_options.="<option value=$year $sel>$year</option>\n";   
	         }
	             
			 
			 $sql="SELECT fc.*, dep.name as departmentName, CONCAT(users.first_name, ' ', users.last_name) AS pi
			 		FROM forms_create as fc
			 		LEFT JOIN departments AS dep ON fc.department_id = dep.department_id
			 		LEFT JOIN users ON fc.user_id = users.user_id
			 		WHERE 1
			 		AND (
		    	    (YEAR(submit_date)=$srd_year 
		    		AND MONTH(submit_date)>=1 
		    		AND MONTH(submit_date)<6) 
		    	  OR
		    		(YEAR(submit_date)=$srd_year-1
		    		AND MONTH(submit_date)>5 
		    		AND MONTH(submit_date)<=12)
		    	  OR
		    	    submit_date='000-00-00'
		    		)
		    	  
			 		";
			 	
             $sql = $sql . $orderBy;
         	 $regs=$db->getAll($sql);
         	 
		 	 

			$prev=$srd_year-1;
			$range= "June " . $prev . ' - May ' . $srd_year;
            $tmpl->addVar('view', "COUNT", count($regs));
            $tmpl->addVar('view', "RANGE", $range);
            $tmpl->addVar('view', "YEAR_OPTIONS", $year_options);

             if(count($regs)>0){
                 foreach($regs as $key=>$reg){
	                 $regs[$key]['year']=$srd_year;
                     $sql = sprintf("SELECT COUNT(*) AS numCoresearchers FROM forms_create_coresearchers WHERE fc_id = %s", $reg['form_create_id']);
                     $cores = $db->getRow($sql);
                     $regs[$key]['coresearchers'] = $cores['numCoresearchers'] == 0 ? '' : $cores['numCoresearchers'];
         			 $regs[$key]['submit_date']= ($reg['submit_date']=='0000-00-00') ? '' :  date('Y-m-d, H:m',strtotime($reg['submit_date']));
                     switch($reg['reb_req'])
                     {
                         case '1' :
                             $regs[$key]['reb_req'] = $yesColor;
                             break;
                         case '2' :
                             $regs[$key]['reb_req'] = $ignoreColor;
                             break;
                         case '3' :
                             $regs[$key]['reb_req'] = $maybeColor;
                     }
                     if($reg['reb_req'] == '2') {
                         $regs[$key]['reb_status'] =  $ignoreColor;
                     } else {
                         switch($reg['reb_status'])
                         {
                             case '1' :
                                 $regs[$key]['reb_status'] = $yesColor;
                                 break;
                             case '2' :
                                 $regs[$key]['reb_status'] = $noColor;
                                 break;
                             case '3' :
                                 $regs[$key]['reb_status'] = $maybeColor;
                         }
                     }
                     $regs[$key]['iagree']=($reg['iagree'] == 1) ? $yesColor : $noColor;
                     switch($reg['status'])
                     {
                         case '0' :
                             $regs[$key]['status'] = 'Pre-submit';
                             break;
                         case '1' :
                             $regs[$key]['status'] = 'Submitted';
                            break;
                         case '2' :
                             $regs[$key]['status'] = 'Contacted';
                            break;
                         case '3' :
                             $regs[$key]['status'] = 'Finalized';
                     }
                     
                     if($reg['type']>0){
                     	$sql="SELECT name,cat_id FROM forms_create_categories WHERE cat_id=$reg[type]";
					 	$cats=$db->GetRow($sql);
					 	$regs[$key]['type']=$cats['name'];
                     }
                     else $reg['mode']='';
                     
                     
                     if(strlen($reg['create_name'])>30) $regs[$key]['create_name']=substr($reg['create_name'],0,30) . '...';
                     if(strlen($reg['summary'])<5) $regs[$key]['create_name']="<font color='red'>".$regs[$key]['create_name']."</font>";
                     if($reg['moved']==TRUE) {
                     	$regs[$key]['dis']="disabled='disabled'"; 
                     	$regs[$key]['mname']='-Moved-';
                     	$regs[$key]['mcolour']='#333333';
                     }
                     else {
                     	$regs[$key]['dis']='';
                     	$regs[$key]['mname']='Move';
                     }
                     
                     //$srd_year=GetSchoolYear(time());
    	
		    		$sql="SELECT * FROM poster_reg WHERE studentid='$reg[user_id]' 
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
		    		if(count($prev)>0) $regs[$key]['posters']=count($prev);
		    		else $regs[$key]['posters']='';
		    		//if($reg['srd']) $regs[$key]['main']="checked='checked'"; else $regs[$key]['main']='';
		    		//if($reg['strd']) $regs[$key]['st']="checked='checked'"; else $regs[$key]['st']='';
		    		/*
if($reg['pref']=='poster')
		    			{ $regs[$key]['p']="checked='checked'"; $regs[$key]['m']='';}
		    		else {$regs[$key]['m']="checked='checked'"; $regs[$key]['p']='';}
*/
					
		    		
                }//foreach
                $tmpl->addRows('mainlist',$regs);
                if(isset($success)) $tmpl->addVar('view','success',$success);
         	}//if count>0
         	$hdr->AddVar("header","title","SRD: View");
         	
         	
         break;
         
         
         
         case 'edit':
             if(isset($_REQUEST['id'])){
                 $tmpl->setAttribute('edit','visibility','visible');
                 $sql="SELECT srd.*, departments.name AS department, user_id
                       FROM srd_reg AS srd
                       LEFT JOIN departments ON srd.departmentId = departments.department_id
                       LEFT JOIN users ON srd.supervisorId = users.user_id
                       WHERE srd_reg_id={$_REQUEST['id']}";
                       
                 $sql="SELECT fc.*
			 		FROM forms_create as fc
			 		WHERE form_create_id={$_REQUEST['id']}";
			 		
                 $reg=$db->getRow($sql);

                 if($reg){
				 		
				 	//lead student
				 	$sql="SELECT CONCAT(last_name,', ',first_name) as name,user_id FROM users WHERE emp_type='STUDENT' ORDER BY last_name,first_name";
				 	$users=$db->Execute($sql);
				 	if($users){
					 	$reg['user_options']=$users->GetMenu2('user_id',$reg['user_id'],$blank1stItem="0:");
					 	$users->MoveFirst();
					 	$reg['coresearcher_add']=$users->GetMenu2('cores_add');
				 	}	
				 		
                     // build the list of coresearchers
                     
                     $sql = "SELECT CONCAT(fcc.firstname, ' ', fcc.lastname) AS coresearcher, fcc.fcc_id, fcc.user_id,
                     		  CONCAT (u.first_name,' ',u.last_name) AS coname
                              FROM forms_create_coresearchers AS fcc
                              LEFT JOIN users as u ON u.user_id=fcc.user_id
                              WHERE fcc.fc_id={$reg['form_create_id']}";
                     $coresearchers=$db->getAll($sql);
                     
                     $colist=array();
					 if(count($coresearchers)>0) {
						 foreach($coresearchers as $coresearcher) {
						 	if($coresearcher['user_id']==0) $name=$coresearcher['coresearcher'];
						 	else $name=$coresearcher['coname'];
						 	$colist[]=array('coresearcher'=>$name,'id'=>$coresearcher['fcc_id']);
							}
						$tmpl->setAttribute('coresearchers','visibility','visible');
					}
					//print_r($colist);
					
					$tmpl->AddRows("coresearchers",$colist);
					$reg['created']= date($niceday,strtotime($reg['created']));
                    $reg['modified']= date("$niceday G:i",strtotime($reg['modified']));
					
					$users=$db->Execute("SELECT CONCAT(users.last_name, ', ', users.first_name),user_id FROM users WHERE 1 ORDER BY last_name,first_name");
					$reg['supervisor_options']=$users->getMenu2("supervisor_id",$reg['supervisor_id']);
					
					$thisyear=GetSchoolYear(time());
		             $slots=explode(',',$reg['timeslots']);
		             $timeslots=$db->Execute("SELECT CONCAT(type,': ',slot) as name,id FROM forms_create_timeslots WHERE year=$thisyear ORDER BY type,id");
		             if($timeslots->RecordCount()>0){
			             $reg['timeslots']=$timeslots->GetMenu2("timeslots",$slots,true,true,$timeslots->RecordCount()+1);
			         }
		             if($reg['slam']) $reg['slam']="checked"; else $reg['slam']='';
		             if($reg['nojudging']) $reg['nojudging']="checked"; else $reg['nojudging']='';
		             
		             $depts=$db->Execute("SELECT name,department_id FROM departments ORDER BY name");
					 $reg['dept_options']=$depts->GetMenu2("department_id",$reg['department_id']);
					 
					 $cats=$db->Execute("SELECT name,cat_id FROM forms_create_categories");
					 $reg['cat_options']=$cats->GetMenu2("type",$reg['type']);
					 
					 $reg['course']=htmlentities($reg['course']);
					 $reg['program']=htmlentities($reg['program']);
					 
					 if($reg['iagree']) $reg['checkagree']='checked';
                     
                     switch($reg['reb_req']){
	                    case 1 :  $reg['REB_REQ_1']="checked=''";break;
	                    case 2 :  $reg['REB_REQ_2']="checked=''";break;
	                    case 3 :  $reg['REB_REQ_3']="checked=''";break;
	                 }
	                 switch($reg['reb_status']){
	                    case 1 :  $reg['REB_STATUS_1']="checked=''";break;
	                    case 2 :  $reg['REB_STATUS_2']="checked=''";break;
	                    case 3 :  $reg['REB_STATUS_3']="checked=''";break;
	                 }
					
                    
                    
                    $options_list=array(0=>'pre-submit',1=>'submitted',2=>'contacted',3=>'finalized');
                    $opt=array();
                    foreach($options_list as $key=>$option){
                        if($reg['status']==$key) $sel="selected=''"; else $sel='';
                        $opt[]=array('value'=>$key, 'text'=>$option, 'sel'=>$sel);
                    }
                    $tmpl->addRows('status_options',$opt);
                    
                    $tmpl->addVars('edit',$reg);
                    if(isset($success)) $tmpl->addVar('edit','success',$success);
                 }
             }
             $hdr->AddVar("header","title","SRD: Add/Update");
         break;
         
         case "timeslots":
         	$tmpl->setAttribute('timeslots','visibility','visible');
         	$srd_year=GetSchoolYear(time());
         	$sql="SELECT fc.*, CONCAT(users.first_name, ' ', users.last_name) AS pi
			 		FROM forms_create as fc
			 		LEFT JOIN users ON fc.user_id = users.user_id
			 		WHERE 1
			 		AND (
		    	    (YEAR(submit_date)=$srd_year 
		    		AND MONTH(submit_date)>=1 
		    		AND MONTH(submit_date)<6) 
		    	  OR
		    		(YEAR(submit_date)=$srd_year-1
		    		AND MONTH(submit_date)>5 
		    		AND MONTH(submit_date)<=12)
		    		)
			 		";
			 	
         	 $regs=$db->getAll($sql);
         	 
         	 $sql="SELECT * from forms_create_timeslots WHERE year=$srd_year";
         	 $slots=$db->getAll($sql);
         	 
         	 if(count($slots)>0){
	         	 foreach($slots as $slot){
		         	//print_r($slot);
		         	$tmpl->addVars('oneslot',$slot);
		         	$namelist=array();
		         	foreach($regs as $reg){
			         	$regslots=explode(',',$reg['timeslots']);
			         	//print_r($regslots);
			         	//echo($slot['id']);
			         	//echo("<br>");
			         	if(in_array($slot['id'],$regslots)) {
				         	$namelist[]=$reg;	
				        }
			        }
			        $tmpl->addRows('participants',$namelist);
		         	
		         	$tmpl->parseTemplate('oneslot','a');
		         	$tmpl->clearTemplate('participants');
		         	
		         }
	         }
	         $hdr->AddVar("header","title","SRD: Timeslots");
         	
         break;
         
         
                  
         
     }
     
     if(isset($success)) $hdr->addVar('header','success',$success);

	 $hdr->displayParsedTemplate('header');
     $tmpl->displayParsedTemplate('page');
