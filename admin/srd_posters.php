<?php
    //error_reporting(E_ALL);
    include("includes/config.inc.php");
    include("includes/functions-required.php");
    
    $hdr=loadPage("header",'Header');

	$menuitems=array();
	$menuitems[]=array('title'=>'Add','url'=>'srd_posters.php?add');
	$menuitems[]=array('title'=>'List','url'=>'srd_posters.php?section=view');
	$hdr->AddRows("list",$menuitems);
    
    
    $tmpl=loadPage("srd_posters", 'SRD Posters');
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
    
        
    if(isset($_REQUEST['delete'])){
        
        if(isset($_REQUEST['id'])) {
            
            $sql="DELETE from poster_reg WHERE poster_reg_id={$_REQUEST['id']}";
            //echo $sql;
            $result=$db->Execute($sql);
            //$arr = $db->ErrorMsg();
            //print_r($arr);
        }
        
    }
    
    
    if(isset($_REQUEST['update'])){
        if(isset($_REQUEST['id'])){
            //print_r($_FILES);
            //file processing
            $sql="Select * FROM poster_reg WHERE poster_reg_id=$_REQUEST[id]";
            $old=$db->getRow($sql);
            if(is_uploaded_file($_FILES['filename2']['tmp_name'])){
                echo"found a file";
                $ext=explode(".",$_FILES['filename2']['name']);
                $ext_el=sizeof($ext)-1;  //in case theres another . in the filename
                $filename_noext="printfile".time();
                $filename=$filename_noext.".".$ext[$ext_el];
                
                if (!copy ($_FILES['filename2']['tmp_name'],$configInfo['upload_root'].'posters/'.$filename))
                    echo("Error Copying ".$_FILES['filename2']['tmp_name']." to ".$configInfo['upload_root'].'posters/'.$filename);
                unlink($_FILES['filename2']['tmp_name']);
            }
            
            if(isset($filename)) $fileload=",filename='$filename'"; else $fileload='';
            $sql="UPDATE poster_reg SET
            firstName='". mysql_real_escape_string(isset($_REQUEST['firstName']) ? $_REQUEST['firstName'] : '') . "',
            lastName='". mysql_real_escape_string(isset($_REQUEST['lastName']) ? $_REQUEST['lastName'] : '') . "',
            studentid='". mysql_real_escape_string(isset($_REQUEST['studentid']) ? $_REQUEST['studentid'] : '') . "',
            email='". mysql_real_escape_string(isset($_REQUEST['email']) ? $_REQUEST['email'] : '') . "',
            title='". mysql_real_escape_string(isset($_REQUEST['title']) ? $_REQUEST['title'] : '') . "',
            orientation='". mysql_real_escape_string(isset($_REQUEST['orientation']) ? $_REQUEST['orientation'] : '') . "',
            status='". mysql_real_escape_string(isset($_REQUEST['status']) ? $_REQUEST['status'] : '') . "'
            $fileload
            WHERE poster_reg_id= $_REQUEST[id];
            ";
      if($db->Execute($sql) === false)
        $success= "<font color='red'>Error updating: ".$db->ErrorMsg()."</font>";
      else {
          $success="Saved";
          if($_REQUEST['status']==2 && $old['status']!=2){  // if printed
          
              require_once "Mail/Queue.php";
              $mail_queue = new Mail_Queue( $configInfo['email_db_options'], $configInfo['email_options'] );
              $mime = new Mail_mime();
              $from = 'research@mtroyal.ca';
              $from_name = 'SRD Bot';
            
            
                  if ( $configInfo["debug_email"] ) {
                        $recipient = $configInfo["debug_email"];
                        $recipient_name = $configInfo["debug_email_name"];
                  } else {
                    $recipient = "$_REQUEST[email]";
                    $recipient_name = "$_REQUEST[firstName] $_REQUEST[lastName]";
                  }
                  $from_params = empty( $from_name ) ? '<' . $from . '>' : '"' . $from_name . '" <' . $from . '>';
                  $recipient_params = empty( $recipient_name ) ? '<' . $recipient . '>' : '"' . $recipient_name . '" <' . $recipient . '>';
                  $hdrs = array(
                    'From' => $from_params,
                    'To' => $recipient_params,
                    'Subject' => "Printing Confirmation",
                    );                              
                      $message .= "
                      
Your poster or multimedia presentation entitled \"$_REQUEST[title]\" has been printed (or the presentation verified for display). Posters will be brought to the display area on the morning of the event, approximately 1 hour prior, and it will be your responsibility to hang it up.
                        ";
                $mime->setTXTBody( $message );
            
                $body = $mime->get();
                $hdrs = $mime->headers( $hdrs );
            
                $queueMailId = $mail_queue->put( $from, $recipient, $hdrs, $body );
                $success.=" and email sent.";
                if ( $configInfo["email_send_now"] ) {
                    $send_result = $mail_queue->sendMailById( $queueMailId );
                }
              }
            }  
        }

    }
    
    if(!isset($_REQUEST['section'])) $_REQUEST['section']="view";
    
     switch($_REQUEST['section']){
        
         case 'view':
         	
         	$tmpl->setAttribute('view','visibility','visible');


             // determine sorting for query
             $orderBy = "ORDER BY poster.lastName, poster.firstName ASC";
             if(isset($sort)) {
                if($sort == 'name') {
                    $tmpl->addVar('view', "NAMESORTCLASS", "class='arrow-down'");
                    $tmpl->addVar('view', "DATESORTCLASS", "");
                    $orderBy = "ORDER BY poster.lastName, poster.firstName";
                } elseif($sort == 'date') {
                    $tmpl->addVar('view', "DATESORTCLASS", "class='arrow-down'");
                    $tmpl->addVar('view', "NAMESORTCLASS", "");
                    $orderBy = "ORDER BY poster.submit_date DESC";
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
	             


             $sql="SELECT poster.*, dep.name AS departmentName, CONCAT(users.first_name, ' ', users.last_name) AS supervisor
                  FROM poster_reg AS poster
		          LEFT JOIN departments AS dep ON poster.departmentId = dep.department_id
		          LEFT JOIN users ON poster.supervisorId = users.user_id 
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
	                 
                     $regs[$key]['submit_date']=date('Y-m-d, H:i',strtotime($reg['submit_date']));
                     switch($reg['status'])
                     {
                         case '0' :
                             $regs[$key]['status'] = "<font color='red'>Submitted</font>";
                             break;
                         case '1' :
                             $regs[$key]['status'] = 'Reviewed';
                            break;
                         case '2' :
                             $regs[$key]['status'] = 'Printed';
                            break;
                         case '3' :
                             $regs[$key]['status'] = 'DO NOT PRINT';
                     }
                     if(strlen($reg['title'])>30) $regs[$key]['title']=substr($reg['title'],0,30) . '...';
                     
		    		if($reg['orientation']=='portrait')  $regs[$key]['ori']='P';
                    elseif($reg['orientation']=='landscape')  $regs[$key]['ori']='L';
                    else $regs[$key]['ori']='U';
                }//foreach
                $tmpl->addRows('mainlist',$regs);
                if(isset($success)) $tmpl->addVar('view','success',$success);
         	}//if count>0
         	
         	
         break;
         
         
         
         case 'edit':
             if(isset($_REQUEST['id'])){
                 $tmpl->setAttribute('edit','visibility','visible');
                 $sql="SELECT srd.*, departments.name AS department, CONCAT(users.first_name, ' ', users.last_name) AS supervisor
                       FROM poster_reg AS srd
                       LEFT JOIN departments ON srd.departmentId = departments.department_id
                       LEFT JOIN users ON srd.supervisorId = users.user_id
                       WHERE poster_reg_id={$_REQUEST['id']}";
                 $reg=$db->getRow($sql);

                 if($reg){

                    $reg['submit_date']=date('M j/y',strtotime($reg['submit_date']));
                                        $options_list=array('submitted','reviewed','printed','do not print');
                    $opt=array();
                    foreach($options_list as $key=>$option){
                        if($reg['status']==$key) $sel='selected'; else $sel='';
                        $opt[]=array('value'=>$key, 'text'=>$option, 'sel'=>$sel);
                    }
                    $tmpl->addRows('status_options',$opt);
                    //print_r($reg);
                    if($reg['filename'] != '') $reg['filename']= $configInfo['upload_webroot'].'posters/'.$reg['filename'];
                    $ori=$reg['orientation'];
                    
                    $reg['portrait']=$reg['landscape']=$reg['unknown']='';
                    if($ori=='portrait') $reg['portrait']="checked='checked'";
                    elseif($ori=='landscape') $reg['landscape']="checked='checked'";
                    else $reg['unknown']="checked='checked'";
                    $reg['year']= (isset($_REQUEST['year'])) ? $_REQUEST['year'] : GetSchoolYear(time());

                    $tmpl->addVars('edit',$reg);
                    if(isset($success)) $tmpl->addVar('edit','success',$success);
                 }
             }
         break;
         
     }
     
     if(isset($success)) $hdr->addVar('header','success',$success);

	 $hdr->displayParsedTemplate('header');
     $tmpl->displayParsedTemplate('page');
