<?php
    //error_reporting(E_ALL);
	require_once('../includes/global.inc.php');
    //include("includes/functions-required.php");
    $tmpl=loadPage("srd_edit", 'Student Research Days');
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
        else $success="Started";
        $_REQUEST['id']=mysql_insert_id();
        $sql="UPDATE srd_reg SET submit_date=NOW() WHERE srd_reg_id=$_REQUEST[id]";
        if($db->Execute($sql) === false)
        $success.= " <font color='red'>Error updating: ".$db->ErrorMsg()."</font>";
        
        $_REQUEST['section']='edit';
        //print_r($_REQUEST);
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
        
        if(isset($_REQUEST['id'])) {
        	$success='';
        	$sql="SELECT * FROM srd_reg WHERE srd_reg_id=$_REQUEST[id]";
        	$srd=$db->GetRow($sql);
        	if($srd){
        		$srd['title']=mysql_escape_string($srd['title']);
        		$srd['descrip']=mysql_escape_string($srd['descrip']);
        		if($srd['hreb']=='yes')$srd['hreb']=1; else $srd['hreb']=0;
        		if($srd['hreb2']=='yes')$srd['hreb2']=1; else $srd['hreb2']=0;
        		$sql="INSERT INTO student_research_projects (
            		supervisorID,
            		departmentID,
            		program,
            		course,
            		presentationType,
            		hrebNeedClearance,
            		hrebHaveClearance,
            		title,
            		description
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
            		'$srd[descrip]'
            		)";
            	//echo($sql);

				if($db->Execute($sql) === false)
        			$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
      			
      			
      			$first=mysql_escape_string($srd['firstName']);
      			$last=mysql_escape_string($srd['lastName']);
      			$id=mysql_insert_id();
      			$sql="INSERT INTO student_researchers (
      					first,
      					last,
      					email,
      					studentID,
      					lastModified) 
      					VALUES(
      					'$first',
      					'$last',
      					'$srd[email]',
      					'$srd[studentid]',
      					NOW()
      					)";
      			//echo($sql);
      			if($db->Execute($sql) === false)
        			$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
      			
      			$lastid=mysql_insert_id();
      			$sql="INSERT INTO student_research (studentResearcherID,researchProjectID) 
      					VALUES($lastid,$id)";
      			if($db->Execute($sql) === false)
        			$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
      			
      			
      			$sql = "SELECT * FROM srd_researchers WHERE srd_reg_id = $srd[srd_reg_id]";
                $cores = $db->getAll($sql);
                if(count($cores)>0) {
                	foreach($cores as $core){
                		$first=mysql_escape_string($core['first']);
      					$last=mysql_escape_string($core['last']);
                		$sql="INSERT INTO student_researchers (
                				first,
                				last,
                				lastModified)
                				VALUES(
                				'$first',
                				'$last',
                				NOW()
                				)";
                	if($db->Execute($sql) === false)
        			$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
      				
      				$lastid=mysql_insert_id();
      				$sql="INSERT INTO student_research (studentResearcherID,researchProjectID) 
      					VALUES($lastid,$id)";
      				if($db->Execute($sql) === false)
        				$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
      				
                	}
                }
                if($success==''){
                	$sql="UPDATE srd_reg SET moved='1' WHERE srd_reg_id=$_REQUEST[id]";
                	if($db->Execute($sql) === false)
        				$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
                }
                else $success="Errors encountered. The move was only partially successful.";
         		//$regs[$key]['submit_date']=date('Y-m-d, H:m',strtotime($reg['submit_date']));
        	}
            
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
		      require_once("Mail/Queue.php");
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
		
		    $queueMailId = $mail_queue->put( $from, $recipient, $hdrs, $body );
		
		    if ( $configInfo["email_send_now"] ) {
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
		        'Subject' => "Full List of S+TSRD Abstracts",
		        );

      		 $srd_year=GetSchoolYear(time());
      		 $sql="SELECT * FROM srd_reg 
      		 		WHERE strd=1
      		 		AND (
		    	    (YEAR(submit_date)=$srd_year 
		    		AND MONTH(submit_date)>=1 
		    		AND MONTH(submit_date)<6) 
		    	  OR
		    		(YEAR(submit_date)=$srd_year-1
		    		AND MONTH(submit_date)>5 
		    		AND MONTH(submit_date)<=12)
		    		)
                    ORDER BY lastName,firstName";

      		 $projs=$db->GetAll($sql);
      		 foreach($projs as $proj){
	      		 if($proj){
	      		 	$title=$proj['title'];
	      		 	$descrip=$proj['descrip'];
	      		 }
	      		 else {$title="Unknown"; $descrip="Unknown";}
                 $name="$proj[lastName], $proj[firstName]";
			     //get the other authors
                 $sql="SELECT * FROM srd_researchers WHERE srd_reg_id=$proj[srd_reg_id]";
                 $others=$db->getAll($sql);
                 if(count($others) > 0) foreach ($others as $other) {
                    $name.="; $other[last], $other[first]";
                 }	
                 if($proj['departmentId'] != 0) {
                    $sql="SELECT * FROM departments WHERE department_id=$proj[departmentId]";
                    $dept=$db->getRow($sql);
                    if($dept) $deptname="Department of ".$dept['name'];
                 }	
                 else $deptname='';    
			      $message .= "
			      
$name
$deptname
$title
$descrip
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
    
    //Process CSV Dump and send
    if(isset($_REQUEST['csv'])){
        $message='';
               if(!isset($_SERVER['PHP_AUTH_USER'])) $success="EMAIL not set to send";
               else {
               
               require_once "Mail/Queue.php";
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
                'Subject' => "CSV Dump - FST Data",
                );

               $srd_year=GetSchoolYear(time());
               $sql="SELECT srd_reg.*, users.first_name as first,users.last_name as last, profiles.email as semail, departments.name as name FROM srd_reg
                        LEFT JOIN users on (srd_reg.supervisorId=users.user_id)
                        LEFT JOIN profiles on (srd_reg.supervisorId=profiles.user_id)
                        LEFT JOIN departments on(srd_reg.departmentId=departments.department_id)
                       WHERE strd=1
                       AND (
                    (YEAR(submit_date)=$srd_year 
                    AND MONTH(submit_date)>=1 
                    AND MONTH(submit_date)<6) 
                  OR
                    (YEAR(submit_date)=$srd_year-1
                    AND MONTH(submit_date)>5 
                    AND MONTH(submit_date)<=12)
                    )
                    ORDER BY lastName,firstName";
                    

               $result=$db->GetAll($sql);
               
               header("Content-Type: application/xls");
               header("Content-Disposition: attachment; filename=fstsrd.xls");
               header("Pragma: no-cache");
               header("Expires: 0");
               
               $sep="\t";
               print("First Name\tLast Name\tStudentID\tStudent Email\tProgram\tDepartment\tCourse\tSupervisor\tSupervisor Email\tTitle\tAbstract\tSubmit Date\tURL\tPoster\n");
               foreach($result as $item){
                   $sql="SELECT * FROM poster_reg WHERE studentid='$item[studentid]' 
                    AND (
                    (YEAR(submit_date)=$srd_year 
                    AND MONTH(submit_date)>=1 
                    AND MONTH(submit_date)<6) 
                    OR
                    (YEAR(submit_date)=$srd_year-1
                    AND MONTH(submit_date)>5 
                    AND MONTH(submit_date)<=12)
                    )";
                    $prev=$db->GetRow($sql);
                    if($prev) {
                        if($prev['orientation']=='landscape') $orient='L';
                        elseif($prev['orientation']=='portrait') $orient='P';
                        else $orient='U';
                    }
                    else $orient='--';
                   
                   
                   
                   
                   if($item['title']=='') $item['title']="NULL";
                   if($item['descrip']=='') $item['descrip']="NULL";
                    $item['descrip']=html_entity_decode($item['descrip']);
                   $schema_insert="$item[firstName]\t$item[lastName]\t$item[studentid]\t$item[email]\t$item[program]\t$item[name]\t$item[course]\t$item[first] $item[last]\t$item[semail]\t$item[title]\t$item[descrip]\t$item[submit_date]\t$item[url]\t$orient\n";
                   $schema_insert = str_replace($sep."$", "", $schema_insert);
                   $schema_insert = preg_replace("/\r\n|\n\r|\n|\r/", " ", $schema_insert);
                   
                 print(trim($schema_insert));
                 print "\n";
               }
               exit();
               
               $sep = "\t"; //tabbed character
               
               
               /*while($row = $result->FetchRow)
               {
                 $schema_insert = "";
                 for($j=0; $j<mysql_num_fields($result);$j++)
                 {
                    if(!isset($row[$j]))
                        $schema_insert .= "NULL".$sep;
                    elseif ($row[$j] != "")
                        $schema_insert .= "$row[$j]".$sep;
                    else
                        $schema_insert .= "".$sep;
                 }

                 $schema_insert = str_replace($sep."$", "", $schema_insert);
                 $schema_insert = preg_replace("/\r\n|\n\r|\n|\r/", " ", $schema_insert);
                 $schema_insert .= "\t";
                 print(trim($schema_insert));
                 print "\n";
               }*/
           }  
    $success="Done";
    $_REQUEST['section']='blank';
    }

    
    
    
    
    
    
    
    if(isset($_REQUEST['update'])){
        if(isset($_REQUEST['id'])){
            $sql="UPDATE srd_reg SET
            firstName='". mysql_escape_string(isset($_REQUEST['firstName']) ? $_REQUEST['firstName'] : '') . "',
            lastName='". mysql_escape_string(isset($_REQUEST['lastName']) ? $_REQUEST['lastName'] : '') . "',
            studentid='". mysql_escape_string(isset($_REQUEST['studentid']) ? $_REQUEST['studentid'] : '') . "',
            email='". mysql_escape_string(isset($_REQUEST['email']) ? $_REQUEST['email'] : '') . "',
            program='". mysql_escape_string(isset($_REQUEST['program']) ? $_REQUEST['program'] : '') . "',
            course='". mysql_escape_string(isset($_REQUEST['course']) ? $_REQUEST['course'] : '') . "',
            pref='". mysql_escape_string(isset($_REQUEST['pref']) ? $_REQUEST['pref'] : '') . "',
            hreb='". mysql_escape_string(isset($_REQUEST['hreb']) ? $_REQUEST['hreb'] : '') . "',
            hreb2='". mysql_escape_string(isset($_REQUEST['hreb2']) ? $_REQUEST['hreb2'] : '') . "',
            title='". mysql_escape_string(isset($_REQUEST['title']) ? $_REQUEST['title'] : '') . "',
            descrip='". mysql_escape_string(isset($_REQUEST['descrip']) ? $_REQUEST['descrip'] : '') . "',
            foip='". mysql_escape_string(isset($_REQUEST['foip']) ? $_REQUEST['foip'] : '') . "',
            status='". mysql_escape_string(isset($_REQUEST['status']) ? $_REQUEST['status'] : '') . "'
            WHERE srd_reg_id= $_REQUEST[id];
            ";
      if($db->Execute($sql) === false)
        $success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
      else $success="Saved";
      }
    }
    
    
    
    if(!isset($_REQUEST['section'])) $_REQUEST['section']="view";
    
     switch($_REQUEST['section']){
        
         case 'view':
         	
         	$tmpl->setAttribute('view','visibility','visible');
            $tmpl->addVar('view', "YESCOLOR", $yesColor);
            $tmpl->addVar('view', "NOCOLOR", $noColor);
            $tmpl->addVar('view', "MAYBECOLOR", $maybeColor);

             // determine sorting for query
             $orderBy = "ORDER BY srd.lastName, srd.firstName ASC";
             if(isset($sort)) {
                if($sort == 'name') {
                    $tmpl->addVar('view', "NAMESORTCLASS", "class='arrow-down'");
                    $tmpl->addVar('view', "DATESORTCLASS", "");
                    $orderBy = "ORDER BY srd.lastName, srd.firstName";
                } elseif($sort == 'date') {
                    $tmpl->addVar('view', "DATESORTCLASS", "class='arrow-down'");
                    $tmpl->addVar('view', "NAMESORTCLASS", "");
                    $orderBy = "ORDER BY srd.submit_date DESC";
                }
             }
			
			 $srd_year=GetSchoolYear(time());

             $sql="SELECT srd.*, dep.name AS departmentName, CONCAT(users.first_name, ' ', users.last_name) AS supervisor
                  FROM srd_reg AS srd
		          LEFT JOIN departments AS dep ON srd.departmentId = dep.department_id
		          LEFT JOIN users ON srd.supervisorId = users.user_id 
		          WHERE srd.strd=1 
		    	  AND (
		    	    (YEAR(submit_date)=$srd_year 
		    		AND MONTH(submit_date)>=1 
		    		AND MONTH(submit_date)<6) 
		    	  OR
		    		(YEAR(submit_date)=$srd_year-1
		    		AND MONTH(submit_date)>5 
		    		AND MONTH(submit_date)<=12)
		    		)";
		    		
             $sql = $sql . $orderBy;
         	 $regs=$db->getAll($sql);
         	 $prev=$srd_year-1;
			$range= "June " . $prev . ' - May ' . $srd_year;
            $tmpl->addVar('view', "COUNT", count($regs));
            $tmpl->addVar('view', "RANGE", $range);

             if(count($regs)>0){
                 foreach($regs as $key=>$reg){
                     $sql = sprintf("SELECT COUNT(*) AS numCoresearchers FROM srd_researchers WHERE srd_reg_id = %s", $reg['srd_reg_id']);
                     $cores = $db->getRow($sql);
                     $regs[$key]['coresearchers'] = $cores['numCoresearchers'] == 0 ? '' : $cores['numCoresearchers'];
         			 $regs[$key]['submit_date']=date('Y-m-d, H:m',strtotime($reg['submit_date']));
                     switch($reg['hreb'])
                     {
                         case 'yes' :
                             $regs[$key]['hreb'] = $yesColor;
                             break;
                         case 'no' :
                             $regs[$key]['hreb'] = $ignoreColor;
                             break;
                         case 'notsure' :
                             $regs[$key]['hreb'] = $maybeColor;
                     }
                     if($reg['hreb'] == 'no') {
                         $regs[$key]['hreb2'] =  $ignoreColor;
                     } else {
                         switch($reg['hreb2'])
                         {
                             case 'yes' :
                                 $regs[$key]['hreb2'] = $yesColor;
                                 break;
                             case 'no' :
                                 $regs[$key]['hreb2'] = $noColor;
                                 break;
                             case 'notsure' :
                                 $regs[$key]['hreb2'] = $maybeColor;
                         }
                     }
                     $regs[$key]['foip']=($reg['foip'] == 1) ? $yesColor : $noColor;
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
                     if(strlen($reg['title'])>30) $regs[$key]['title']=substr($reg['title'],0,30) . '...';
                     $regs[$key]['descrip']=str_word_count($reg['descrip']);
                     if($reg['moved']==TRUE) $regs[$key]['dis']="disabled='disabled'"; else $regs[$key]['dis']=';';
                     
                     
                     $srd_year=GetSchoolYear(time());
    	
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
		    		$prev=$db->GetRow($sql);
		    		if($prev) {
		    			if($prev['orientation']=='landscape') $regs[$key]['poster']='L';
		    			elseif($prev['orientation']=='portrait') $regs[$key]['poster']='P';
		    			else $regs[$key]['poster']='U';
		    		}
		    		else $regs[$key]['poster']='';
                     
                }//foreach
                $tmpl->addRows('mainlist',$regs);
                if(isset($success)) $tmpl->addVar('view','success',$success);
         	}//if count>0
         	
         	
         	
         	
         break;
         
         
         
         case 'edit':
             if(isset($_REQUEST['id'])){
                 $tmpl->setAttribute('edit','visibility','visible');
                 $sql="SELECT srd.*, departments.name AS department, CONCAT(users.first_name, ' ', users.last_name) AS supervisor
                       FROM srd_reg AS srd
                       LEFT JOIN departments ON srd.departmentId = departments.department_id
                       LEFT JOIN users ON srd.supervisorId = users.user_id
                       WHERE srd_reg_id={$_REQUEST['id']}";
                 $reg=$db->getRow($sql);

                 if($reg){

                     // build the list of coresearchers
                     foreach($reg AS $singleRes) {
                         $sql = sprintf("SELECT CONCAT(cores.first, ' ', cores.last) AS coresearcher
                                  FROM srd_researchers AS cores WHERE cores.srd_reg_id = %s", $reg['srd_reg_id']);
                         $coresearchers=$db->getAll($sql);

                         $coresearcherList = "";
                         foreach($coresearchers AS $key=>$coresearcher) {
                             $coresearcherList .= $coresearcher['coresearcher'];
                             if($key < count($coresearchers)-1) {
                                 $coresearcherList .= ", ";
                             }
                         }

                         $reg['coresearchers'] = $coresearcherList;
                     }

                    $reg['submit_date']=date('M j/y',strtotime($reg['submit_date']));
                    $reg['pref1']=($reg['pref']=='poster') ? "checked='checked'" : '';
                    $reg['pref2']=($reg['pref']=='multimedia') ? "checked='checked'" : '';
                    $reg['pref3']=($reg['pref']=='either') ? "checked='checked'" : '';

                    $reg['hrebneed1']=($reg['hreb']=='yes') ? "checked='checked'" : '';
                    $reg['hrebneed2']=($reg['hreb']=='no') ? "checked='checked'" : '';
                    $reg['hrebneed3']=($reg['hreb']=='notsure') ? "checked='checked'" : '';

                    $reg['foipyes']=($reg['foip'] == 1) ? "checked='checked'" : '';
                    $reg['foipno']=($reg['foip'] == 0) ? "checked='checked'" : '';

                    $reg['hrebdone1']=($reg['hreb2']=='yes') ? "checked='checked'" : '';
                    $reg['hrebdone2']=($reg['hreb2']=='no') ? "checked='checked'" : '';
                    $reg['hrebdone3']=($reg['hreb2']=='notsure') ? "checked='checked'" : '';
                    
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
         break;
         
     }
     
     if(isset($success)) $tmpl->addVar('page','success',$success);
     $tmpl->displayParsedTemplate('page');
