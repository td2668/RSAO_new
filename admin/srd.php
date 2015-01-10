
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
        $sql="INSERT into srd_reg VALUES();";
        if($db->Execute($sql) === false)
        	$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
        else {
	        $success="Started";
			$_REQUEST['id']=mysql_insert_id();
			$sql="UPDATE srd_reg SET submit_date=NOW() WHERE srd_reg_id=$_REQUEST[id]";
			if($db->Execute($sql) === false)
				$success.= " <font color='red'>Error updating: ".$db->ErrorMsg()."</font>";
			$_REQUEST['section']='edit';
			}
			
        //print_r($_REQUEST);
    }
    if(isset($_REQUEST['deleteid'])) if($_REQUEST['deleteid']>0){
	    $sql="DELETE FROM srd_researchers WHERE id=$_REQUEST[deleteid]";
	    $db->Execute($sql);
	    $_REQUEST['section']='edit';
    }
    if(isset($_REQUEST['addcores'])) {
	    $sql="INSERT INTO srd_researchers SET 
	    		first='".mysql_real_escape_string($_REQUEST['newcores_first'])."', 
	    		last='".mysql_real_escape_string($_REQUEST['newcores_last'])."', 
	    		srd_reg_id=$_REQUEST[id]";
	    $db->Execute($sql);
	    $_REQUEST['section']='edit';
    }
    
    
    if(isset($_REQUEST['delete'])){
        
        if(isset($_REQUEST['id'])) {
            
            $sql="DELETE from srd_reg WHERE srd_reg_id={$_REQUEST['id']}";
            //echo $sql;
            $result=$db->Execute($sql);
            //$arr = $db->ErrorMsg();
            //print_r($arr);
        }
        
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
		        $recipient = $_SERVER['PHP_AUTH_USER'].'@mtroyal.ca';
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
      		 if(!isset($_SERVER['PHP_AUTH_USER'])) $success="EMAIL not set to send";
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
		        $recipient = $_SERVER['PHP_AUTH_USER'].'@viu.ca';
		        $recipient_name = "$_SERVER[PHP_AUTH_USER]";
		      }
		      $from_params = empty( $from_name ) ? '<' . $from . '>' : '"' . $from_name . '" <' . $from . '>';
		      $recipient_params = empty( $recipient_name ) ? '<' . $recipient . '>' : '"' . $recipient_name . '" <' . $recipient . '>';
		      $hdrs = array(
		        'From' => $from_params,
		        'To' => $recipient_params,
		        'Subject' => "Full List of Abstracts",
		        );

      		 $srd_year=GetSchoolYear(time());
      		 $sql="SELECT * FROM srd_reg 
      		 		WHERE 1
      		 		AND (
		    	    (YEAR(submit_date)=$srd_year 
		    		AND MONTH(submit_date)>=1 
		    		AND MONTH(submit_date)<6) 
		    	  OR
		    		(YEAR(submit_date)=$srd_year-1
		    		AND MONTH(submit_date)>5 
		    		AND MONTH(submit_date)<=12)
		    		)";

      		 $projs=$db->GetAll($sql);
      		 foreach($projs as $proj){
	      		 if($proj){
	      		 	$title=$proj['title'];
	      		 	$descrip=$proj['descrip'];
	      		 }
	      		 else {$title="Unknown"; $descrip="Unknown";}
			      		    
			      $message .= "
			      
NAME: $proj[firstName] $proj[lastName]
TITLE: $title
ABSTRACT: $descrip
			        ";
			}
		    $mime->setTXTBody( $message );
		
		    $body = $mime->get();
		    $hdrs = $mime->headers( $hdrs );
		
		    $queueMailId = $mail_queue->put( $from, $recipient, $hdrs, $body );
		
		    if ( $configInfo["email_send_now"] ) {
		        $send_result = $mail_queue->sendMailById( $queueMailId );
		    }
		   }  
	$success="Email Sent";
    }

    
    if(isset($_REQUEST['update']) || isset($_REQUEST['addcores'])){
        if(isset($_REQUEST['id'])){
	        
	        if(isset($_REQUEST['time'])) {
		        $timeslots=implode(',', $_REQUEST['time']);
	        }
            
            $sql="UPDATE srd_reg SET
            firstName='". mysql_real_escape_string(isset($_REQUEST['firstName']) ? $_REQUEST['firstName'] : '') . "',
            lastName='". mysql_real_escape_string(isset($_REQUEST['lastName']) ? $_REQUEST['lastName'] : '') . "',
            studentid='". mysql_real_escape_string(isset($_REQUEST['studentid']) ? $_REQUEST['studentid'] : '') . "',
            email='". mysql_real_escape_string(isset($_REQUEST['email']) ? $_REQUEST['email'] : '') . "',
            program='". mysql_real_escape_string(isset($_REQUEST['program']) ? $_REQUEST['program'] : '') . "',
            course='". mysql_real_escape_string(isset($_REQUEST['course']) ? $_REQUEST['course'] : '') . "',
            hreb='". mysql_real_escape_string(isset($_REQUEST['hreb']) ? $_REQUEST['hreb'] : '') . "',
            hreb2='". mysql_real_escape_string(isset($_REQUEST['hreb2']) ? $_REQUEST['hreb2'] : '') . "',
            title='". mysql_real_escape_string(isset($_REQUEST['title']) ? $_REQUEST['title'] : '') . "',
            descrip='". mysql_real_escape_string(isset($_REQUEST['descrip']) ? $_REQUEST['descrip'] : '') . "',
            foip='". mysql_real_escape_string(isset($_REQUEST['foip']) ? $_REQUEST['foip'] : '') . "',
            status='". mysql_real_escape_string(isset($_REQUEST['status']) ? $_REQUEST['status'] : '') . "',
            url='". mysql_real_escape_string(isset($_REQUEST['url']) ? $_REQUEST['url'] : '') . "',
            departmentId='". mysql_real_escape_string(isset($_REQUEST['department']) ? $_REQUEST['department'] : '') . "',
            mode='". mysql_real_escape_string(isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '') . "',
            slots='$timeslots',
            supervisor='".mysql_real_escape_string(isset($_REQUEST['supervisor']) ? $_REQUEST['supervisor'] : '') . "',
            supervisorId='".mysql_real_escape_string(isset($_REQUEST['supervisorid']) ? $_REQUEST['supervisorid'] : '') . "'
            WHERE srd_reg_id= $_REQUEST[id];
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
		    		)
			 		";
			 	
             $sql = $sql . $orderBy;
         	 $regs=$db->getAll($sql);
         	 echo("<pre>");
         	 	print_r($regs);
         	 	echo("</pre>");
			$prev=$srd_year-1;
			$range= "June " . $prev . ' - May ' . $srd_year;
            $tmpl->addVar('view', "COUNT", count($regs));
            $tmpl->addVar('view', "RANGE", $range);
            $tmpl->addVar('view', "YEAR_OPTIONS", $year_options);

             if(count($regs)>0){
                 foreach($regs as $key=>$reg){
	                 $regs[$key]['year']=$srd_year;
                     $sql = sprintf("SELECT COUNT(*) AS numCoresearchers FROM forms_create_coresearchers WHERE fcc_id = %s", $reg['form_create_id']);
                     $cores = $db->getRow($sql);
                     $regs[$key]['coresearchers'] = $cores['numCoresearchers'] == 0 ? '' : $cores['numCoresearchers'];
         			 $regs[$key]['submit_date']=date('Y-m-d, H:m',strtotime($reg['submit_date']));
                     switch($reg['reb_req'])
                     {
                         case '1' :
                             $regs[$key]['reb_req'] = $yesColor;
                             break;
                         case '1' :
                             $regs[$key]['reb_req'] = $ignoreColor;
                             break;
                         case '0' :
                             $regs[$key]['reb_req'] = $maybeColor;
                     }
                     if($reg['reb_req'] == 'no') {
                         $regs[$key]['reb_status'] =  $ignoreColor;
                     } else {
                         switch($reg['reb_status'])
                         {
                             case '2' :
                                 $regs[$key]['reb_status'] = $yesColor;
                                 break;
                             case '1' :
                                 $regs[$key]['reb_status'] = $noColor;
                                 break;
                             case '0' :
                                 $regs[$key]['reb_status'] = $maybeColor;
                         }
                     }
                     $regs[$key]['iagree']=($reg['iagree'] == 1) ? $yesColor : $noColor;
                     switch($reg['status'])
                     {
                         case '0' :
                             $regs[$key]['status'] = 'Submitted';
                             break;
                         case '1' :
                             $regs[$key]['status'] = 'Contacted';
                            break;
                         case '2' :
                             $regs[$key]['status'] = 'Finalized';
                     }
                     
                     if($reg['mode']>0){
                     	$sql="SELECT name,id FROM forms_create_categories WHERE cat_id=$reg[mode]";
					 	$cats=$db->GetRow($sql);
					 	$reg['mode']=$cats['name'];
                     }
                     else $reg['mode']='';
                     
                     
                     if(strlen($reg['title'])>30) $regs[$key]['title']=substr($reg['title'],0,30) . '...';
                     if(strlen($reg['descrip'])<5) $regs[$key]['title']="<font color='red'>".$regs[$key]['title']."</font>";
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
    	
		    		$sql="SELECT * FROM poster_reg WHERE studentid='$reg[studentid]' 
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
					switch($reg['st_category']){
						
					}
		    		
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
                 $reg=$db->getRow($sql);

                 if($reg){

                     // build the list of coresearchers
                     
                     $sql = sprintf("SELECT CONCAT(cores.first, ' ', cores.last) AS coresearcher, id
                              FROM srd_researchers AS cores WHERE cores.srd_reg_id = %s", $reg['srd_reg_id']);
                     $coresearchers=$db->getAll($sql);
                     
					 if(count($coresearchers)>0) {
						 $tmpl->AddRows("coresearchers",$coresearchers);
						 $tmpl->setAttribute('coresearchers','visibility','visible');
						}
					
					$users=$db->Execute("SELECT CONCAT(users.last_name, ', ', users.first_name),user_id FROM users WHERE 1 ORDER BY last_name,first_name");
					$reg['supervisor_options']=$users->getMenu2("supervisorid",$reg['user_id']);
						
                    $depts=$db->Execute("SELECT name,department_id from departments ORDER BY name");
                    $reg['dept_options']=$depts->getMenu2("department",$reg['departmentId']);
                    //echo($reg['dept_options']);
                    
                    $cats=$db->Execute("SELECT name,id from srd_categories ORDER BY sort_order");
                    $reg['cat_options']=$cats->getMenu2("mode",$reg['mode']);
                    echo($reg['cat_options']);
                    
                    $regtime=explode( ',', $reg['slots']);
                    $targyear=GetSchoolYear(time());
                    //echo($targyear);
                    $times=$db->Execute("SELECT `desc`, `slot` from srd_reg_slots WHERE year='$targyear' ORDER BY `slot` ");
                    
                    $reg['time_options']=$times->getMenu2("time",$regtime,true,true,12);
                    //echo($reg['cat_options']);
                     
                    $reg['year']= (isset($_REQUEST['year'])) ? $_REQUEST['year'] : GetSchoolYear(time());

                    $reg['submit_date']=date('M j/y',strtotime($reg['submit_date']));
                    $reg['pref1']=($reg['pref']=='poster') ? "checked='checked'" : '';
                    $reg['pref2']=($reg['pref']=='multimedia' || $reg['pref']=='oral') ? "checked='checked'" : '';
                  

                    $reg['hrebneed1']=($reg['hreb']=='yes') ? "checked='checked'" : '';
                    $reg['hrebneed2']=($reg['hreb']=='no') ? "checked='checked'" : '';
                    $reg['hrebneed3']=($reg['hreb']=='notsure') ? "checked='checked'" : '';

                    $reg['foipyes']=($reg['foip'] == 1) ? "checked='checked'" : '';
                    $reg['foipno']=($reg['foip'] == 0) ? "checked='checked'" : '';

                    $reg['hrebdone1']=($reg['hreb2']=='yes') ? "checked='checked'" : '';
                    $reg['hrebdone2']=($reg['hreb2']=='no') ? "checked='checked'" : '';
                    $reg['hrebdone3']=($reg['hreb2']=='notsure') ? "checked='checked'" : '';
                    
                    //if($reg['srd']) $reg['srd']="checked='checked'"; else $reg['srd']='';
                    //if($reg['strd']) $reg['strd']="checked='checked'"; else $reg['strd']='';
                    
                    $options_list=array('submitted','contacted','finalized');
                    $opt=array();
                    foreach($options_list as $key=>$option){
                        if($reg['status']==$key) $sel='selected'; else $sel='';
                        $opt[]=array('value'=>$key, 'text'=>$option, 'sel'=>$sel);
                    }
                    $tmpl->addRows('status_options',$opt);
                    //print_r($reg);
                    $tmpl->addVars('edit',$reg);
                    if(isset($success)) $tmpl->addVar('edit','success',$success);
                 }
             }
             $hdr->AddVar("header","title","SRD: Add/Update");
         break;
         
         
                  
         
     }
     
     if(isset($success)) $hdr->addVar('header','success',$success);

	 $hdr->displayParsedTemplate('header');
     $tmpl->displayParsedTemplate('page');
