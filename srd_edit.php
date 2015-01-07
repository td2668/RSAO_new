<?php
    //error_reporting(E_ALL);
	require_once('includes/global.inc.php');
    //include("includes/functions-required.php");
    $tmpl=loadPage("srd_edit", 'Student Research Day Registration');
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
            //Create a new student_research entry using existing data
            
            //Then change the flag.
            //$sql="DELETE from srd_reg WHERE srd_reg_id={$_REQUEST['id']}";
            //echo $sql;
            $result=$db->Execute($sql);
            //$arr = $db->ErrorMsg();
            //print_r($arr);
        }
        
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


             $sql="SELECT srd.*, dep.name AS departmentName, CONCAT(users.first_name, ' ', users.last_name) AS supervisor
                  FROM srd_reg AS srd
		          LEFT JOIN departments AS dep ON srd.departmentId = dep.department_id
		          LEFT JOIN users ON srd.supervisorId = users.user_id ";
             $sql = $sql . $orderBy;
         	 $regs=$db->getAll($sql);

            $tmpl->addVar('view', "COUNT", count($regs));

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
                     if($reg['moved']==TRUE) $regs[$key]['dis']="disabled='disabled'"; else $regs[$key]['dis']=';';
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
                    $reg['pref2']=($reg['pref']=='oral') ? "checked='checked'" : '';
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
