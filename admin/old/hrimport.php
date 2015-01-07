<?php
/** 
* Manual import of HR personell data
* 
* Provides functions to manually import and test file dumps from a custom HR Banner dump.
* Fields are as follows:
* Change_Indicator    Run_Date    Employee_ Number    Employee_ID    Last_Name    Prefered_ Name    Dept_home    Dept_name    Effective_Date    Status    Title    Change_reason    Primary_Dept    Chair    Pattern    Pattern_Begin_Date    Pattern_End_Date    Pattern_dept
* @package orsadmin
*/



include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");

$template = new Template;

include("html/header.html");

//error_reporting(E_ALL);
$flagtext='';

$debug=false;


if (isset($_REQUEST['load'])) {
    //receive uploaded file
    $uploaddir = 'mail_upload/';
    $uploadfile = $uploaddir . basename($_FILES['file']['name']);

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        
        $import=file($uploadfile,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
       // echo '<pre>';
       // var_dump($import);
        //echo '</pre>';
       $records=array();
       if(is_array($import))  
       foreach($import as $key=>$record){
           rtrim($record,'\n');
            if($key == 0){
               $keys=explode(',',$record);
               foreach($keys as $key){ rtrim($key,'\n');    rtrim($key,'\r');}    
               print_r($keys);
            }
            else {
                $temp=explode(',',$record);
                foreach($temp as $item) {
                    rtrim($item);
                   // ltrim($item);
                } 
                //$records[]=$temp;
                $records[]=array_combine($keys,$temp);
            } //if not initial record
       } //foreach
      // echo '<pre>';
      // print_r($records);
      // echo '</pre>';
      
      
      
    
    } 
    else {
        $success= "Error uploading file: ". $_FILES['userfile']['error'];
    }

   
}
    /**
    * Run the comparison with existing data and load all possible changes into the 'hrchanges' table
    * 
    * TODO: This should be an automated script function on receipt of email
    */
if (isset($_REQUEST['genchanges'])) {
		//load everything that is new
        $sql="SELECT * FROM hrimport LEFT JOIN hrdept ON (hrimport.dept_home=hrdept.dept) WHERE 1 ORDER BY run_date";
        $hrdata=$db->getAll($sql);
        $output=''; $count=0;
        $dept_fields=array(1=>'dept1',2=>'dept2',3=>'dept3',4=>'dept4',5=>'dept5');
        $_REQUEST['section']='compare';
        if( is_array($hrdata)){
            
            foreach($hrdata as $hrkey=>$hritem){
                //if this is a contract employee entry - different processing
            	if(strcmp(rtrim($hritem['title']),'Contract Employee')==0){
                	$sql="SELECT * FROM users_contract WHERE users_contract.emp_num=$hritem[employee_id]";
                	$cuser=$db->getRow($sql);
	                	if(!empty($cuser)){
	                	//////////////////////////// User Exists in Contract Table //////////////////////
	                   		//Look up the department
	                   		$sql="SELECT * from hrdept LEFT JOIN departments on(hrdept.department_id=departments.department_id) WHERE hrdept.dept=$hritem[dept_home]";
	                   		$dept=$db->getRow($sql);
	                   		// What type of update do we have?
	                        switch($hritem['change_indicator']){
	                          case "A":
	                            //if it is an ADD and the contract user exists then its probably a new dept.
	                            if(!empty($dept)){
		                            foreach($dept_fields as $df){
		                            	//$output.="Trying $df<br>";
		                            	if($cuser[$df]==$dept['department_id']){
		                            		//Something is amiss - they are already here
		                            		if($debug) $output.="<font color='red'>$cuser[last_name], $cuser[first_name]: Add requested in $dept[name] but already active.</font><br>";
		                            		break;
		                            	}
		                            	elseif($cuser[$df]==0 ){
		                            		//first empty one, so add the dept here. 
		                            		if($debug) {
		                            			echo ("UPDATE users_contract SET $df={$dept[department_id]} WHERE users_contract.emp_num=$hritem[employee_id]");
		                            			$output.="$cuser[last_name], $cuser[first_name]: Added dept $dept[name]<br>";
		                            		}
		                            		else {
		                            			$sql="UPDATE users_contract SET $df={$dept[department_id]} WHERE users_contract.emp_num=$hritem[employee_id]";
			                            		if(!$db->Execute($sql)) {
			                            			$output.="ERROR updating department: ". $db->ErrorMsg() ."<br>";
			                            			$output.=("$sql <br>");
			                            			$output.="<pre>". var_dump($dept)."</pre>";
			                            			$output.="<br>";
			                            			}
			                            		else if($debug) $output.="$cuser[last_name], $cuser[first_name]: Added dept $dept[name]<br>";
			                            	}
		                            		break;
		                            	}
		                            }//foreach
		                            
		                         } // has a dept dept.
                                 else $output.="Dept # $hritem[dept_home] is not listed in the hrdept table. Can't deal with this. <br>";
                                 deleterow($hritem);
	                       break;
	                       
	                       case "T":
	                       		$changed=false;
	                       		foreach($dept_fields as $key=>$df){
	                       			if($cuser[$df]==$dept['department_id']){
	                       				//$output.="In delete - found $dept[department_id] in space $key<br>";
	                       				for($x=$key; $x<=4; $x++){
	                       					$next=$x+1;
	                       					$depnum="dept$x";
	                       					$depnumnext="dept$next"; 
	                       					//$output.="Trying $depnumnext";
	                       					$sql="UPDATE users_contract SET $depnum=$cuser[$depnumnext] WHERE users_contract.emp_num=$hritem[employee_id]";	
	                       					if($debug) echo ("$sql <br>");
	                       					else if(!$db->Execute($sql)) $output.="ERROR updating (shift): ". $db->ErrorMsg() ."<br>";
		                            		
	                       					//$output.="$sql<br>";
	                       				}
	                       				if($debug) $output.="$cuser[last_name], $cuser[first_name]: Terminated in $dept[name]<br>";
	                       						
	                       			}
	                       			
	                       			
	                       		}
	                       		deleterow($hritem);
	                       break;
	                       
	                       case "C":
	                       		$output.= "Encountered a 'C' - not yet implemented for contract employees"; 
                                deleterow($hritem);
	                       		
	                       break;
	                       
	                       default:
	                            $output.= "Encountered new change_indicator: $hritem[change_indicator] - this will need some programming intervention.</br>";
	                            
	                   }//switch
	                }//is_array - is in db already

                else {
                    //if the user is not found it is either an ADD or an error
                    //If it is an ADD then generate a new user and provide feedback
                    switch($hritem['change_indicator']){
                       case "A":
                       		if($debug) $output.="$hritem[last_name], $hritem[prefered_name]: Adding as new contractor.<br>";
                       		
                            //First, do they exist as a regular member?
                            $sql="SELECT * FROM users LEFT JOIN users_ext on (users.user_id=users_ext.user_id) 
                                  WHERE users_ext.emp_num = $hritem[employee_id]";
                            $exists=$db->getRow($sql);
                            
                            $user_id=0;
                            if(($exists)){
                            	$user_id=$exists['user_id'];
                            	if($debug) $output.= "$hritem[last_name], $hritem[prefered_name]: Already in Main User table as $user_id. <br>";
                            	$theyexist=true;
                            	$username=$exists['username'];
                            }   
                            else $theyexist=false;                      
                            
                            //Add new user
                            
                            if(!$theyexist){
	                            //First guess at the username
	                            $username=strtolower(substr($hritem['prefered_name'],0,1)) . rtrim(strtolower($hritem['last_name']));
	                            $username=preg_replace("/[\s'\"]/", "", $username);
	                            //echo "Checking $username<br>";
	                            $usernameslash=addslashes($username);
	                            $sql="SELECT username FROM users where username='$usernameslash'";
	                            $exists=$db->getRow($sql);
	                            if((count($exists)>=1)) {
	                            	$username=$username . '1';
	                            	$output.="<font color='red'>-----------------------------<br>$hritem[last_name], $hritem[prefered_name]: Contractor username in use - PLEASE CHECK THIS ONE MANUALLY<br>-----------------------------</font><br>";
	                            	$mail.="$hritem[last_name], $hritem[prefered_name]: Contractor username in use - PLEASE CHECK THIS ONE MANUALLY\n";
	                            }
	                            else{
	                            	$sql="SELECT username FROM users_contract WHERE username='$usernameslash'";
	                            	$exists=$db->getRow($sql);
	                            	if((count($exists)>=1)) {
	                            		$username=$username . '1';
	                            		$output.="<font color='red'>-----------------------------<br>$hritem[last_name], $hritem[prefered_name]: Contractor username in use - PLEASE CHECK THIS ONE MANUALLY<br>-----------------------------<br></font>";
	                            	$mail.="$hritem[last_name], $hritem[prefered_name]: Contractor username in use - PLEASE CHECK THIS ONE MANUALLY\n";

	                            	}
	                            }
	                        } //if they are new
	                        
                            $sql="SELECT department_id FROM hrdept WHERE dept=$hritem[dept_home]";
                            $dept=$db->getRow($sql);
                            if(empty($dept)) {
                            	$department_id=0;
                            	$output.="<font color='red'>$hritem[last_name], $hritem[prefered_name]: Did not find dept $hritem[dept_home]: $hritem[dept_name]</font><br>";
                            }
                            else $department_id=$dept['department_id'];
                            $email=addslashes($username) . '@mtroyal.ca';
                            $values=array(
                            	'user_contract_id'=>'NULL',
									'user_id'=>$user_id,
									'first_name'=>rtrim(mysql_real_escape_string($hritem['prefered_name'])) ,
									'last_name'=>rtrim(mysql_real_escape_string($hritem['last_name'])) ,
									'username'=>mysql_real_escape_string($username) ,
									'password'=>'' ,
									'visits'=>0 ,
									'date'=>0 ,
									'mail_events'=>1 ,
									'mail_deadlines'=>1 ,
									'user_level'=>0 ,
									'inactive_flag'=>0 ,
									'login_count'=>0 ,
									'emp_num'=>$hritem['employee_id'] ,
									'dept1'=>$department_id,
									'dept2'=>0 ,
									'dept3'=>0 ,
									'dept4'=>0 ,
									'dept5'=>0 ,
									'email'=>$email ,
									'faculty_display_as'=>'' ,
									'dept_display_as'=>'' ,
									'title'=>'' ,
									'secondary_title'=>'' ,
									'office'=>'' ,
									'phone'=>'' ,
									'fax'=>'' ,
									'homepage'=>'' ,
									'profile_ext'=>'' ,
									'profile_short'=>'' ,
									'keywords'=>'' ,
									'description_short'=>'');
									
                            $sql="INSERT INTO `research`.`users_contract`
									(`user_contract_id`,
									`user_id`,
									`first_name`,
									`last_name`,
									`username`,
									`password`,
									`visits`,
									`date`,
									`mail_events`,
									`mail_deadlines`,
									`user_level`,
									`inactive_flag`,
									`login_count`,
									`emp_num`,
									`dept1`,
									`dept2`,
									`dept3`,
									`dept4`,
									`dept5`,
									`email`,
									`faculty_display_as` ,
									`dept_display_as` ,
									`title` ,
									`secondary_title` ,
									`office` ,
									`phone` ,
									`fax` ,
									`homepage` ,
									`profile_ext` ,
									`profile_short` ,
									`keywords` ,
									`description_short`
									)
									VALUES('";
									$sql.= implode("','",$values);
									$sql.="');";
                            		
                            		if($debug) echo("$sql<br>");
                            		else if(!$db->Execute($sql)) $output.="ERROR ($debug) inserting ". $db->ErrorMsg() . "<br>";
                            
                            //$output.= $sql;
                            //$output.= "<BR><BR>";
                            deleterow($hritem);
                            
                       break;
                       
                       case "C":
                            //something is amiss
                            
                            /////Overriding the following check while initial loads are done (all are set as 'C')
                            $output.= "Trying to do a 'C'-change: $hritem[prefered_name] $hritem[last_name] . (C on Contract Employee) <br>";
                            
                            
                       break;
                       
                       case "T":
                       		$output.= "$hritem[prefered_name] $hritem[last_name]: Request to terminate but not currently in the db.<br> ";
                       		deleterow($hritem);
                       
                       break;
                       
                       default:
                       
                            //something is amiss
                            
                            $output.= "$cuser[last_name], $cuser[first_name]: Possible error: unknown change type.<br>";
                            
                            
                       break;
                    }//switch
                    
                     //deleterow($hritem);
                    
                }//else (not in db)
               } //if a contract employee
               
               
     /////////////////////Normal Employee Section///////////////////          
               
                else{ //Normal employee
                $flagme=FALSE; 
                $sql="SELECT * FROM users LEFT JOIN users_ext ON (users.user_id=users_ext.user_id) LEFT JOIN profiles ON (users.user_id=profiles.user_id) LEFT JOIN departments ON (users.department_id=departments.department_id) WHERE users_ext.emp_num=$hritem[employee_id]";
                $user=$db->GetRow($sql);
                if (($user)){
                   if($debug) echo "Matched " . $users['last_name'] . ', ' . $users['first_name'] . "<br>";
                   
                   // What type of update do we have?
                   switch($hritem['change_indicator']){
                       case "A":
                            //if it is an ADD and the user exists then its either an error or a first run scenario
                            $output.= "Add requested (Regular Faculty) but user $user[first_name] $user[last_name] already exists<br>";
                       break;
                       
                       case "C":
                            //for a CHANGE do a point-by-point comparison of each item and output
                            $user['last_name']=rtrim($user['last_name']);
                            $hritem['last_name']=rtrim($hritem['last_name']);
                            $user['first_name']=rtrim($user['first_name']);
                            $hritem['prefered_name']=rtrim($hritem['prefered_name']);
                            $hritem['pattern']=rtrim($hritem['pattern']);
                            $hritem['status']=rtrim($hritem['status']);
                            $user['active_status']= rtrim($user['active_status']);
                            
                            //First ensure that this is not a problem item
                            if($user['last_name'] != $hritem['last_name'] && $user['first_name'] != $hritem['prefered_name']) {
                                 $flagme=TRUE; 
                            }
                            
                            // Last Name
                            if(!$flagme && $user['last_name'] != $hritem['last_name']) {
                                $sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='last_name',
                                		oldval_char='$user[last_name]', 
                                		newval_char='$hritem[last_name]',
                                		oldval_int=0,
                                		oldval_desc='',
                                		newval_int=0,
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                                
                            }
                            
                            // First Name
                            if(!$flagme && $user['first_name'] != $hritem['prefered_name']){
                            	
                                $sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='first_name',
                                		oldval_char='$user[first_name]', 
                                		newval_char='$hritem[prefered_name]',
                                		oldval_int=0,
                                		oldval_desc='',
                                		newval_int=0,
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                                
                            } 
                            
                            // Department
                            
                            if(!$flagme && $user['department_id'] != $hritem['department_id']) {
                                //if the depts don't match then check if it is Bissett or Comm Studies which don't seem to have proper depts. If so disregard
                                //This should now be fixed....
                                
                                //if(($hritem['dept'] == 2170 && ($user['department_id'] == 116 || $user['department_id'] == 113 || $user['department_id'] == 117 || $user['department_id'] == 115)) || ($hritem['dept'] == 2310 && ($user['department_id'] == 123 || $user['department_id'] == 56 || $user['department_id'] == 45 || $user['department_id'] == 111 || $user['department_id'] == 108 || $user['department_id'] == 128))); //it's OK 
                                $sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='department_id',
                                		oldval_char='', 
                                		newval_char='',
                                		oldval_int=$user[department_id],
                                		oldval_desc='$user[name]',
                                		newval_int=$hritem[department_id],
                                		newval_desc='$hritem[name]',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                                                               
                                
                            }
                            
                            // Pattern
                            if(!$flagme && $hritem['pattern']=='TSS' && !($user['tss'])) {
                            	
                            	$sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='pattern',
                                		oldval_char='TS', 
                                		newval_char='TSS',
                                		oldval_int=0,
                                		oldval_desc='',
                                		newval_int=0,
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                            } 

                            else if(!$flagme && rtrim($hritem['pattern']=='TS') && $user['tss']) {
                            	$sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='pattern',
                                		oldval_char='TSS', 
                                		newval_char='TS',
                                		oldval_int=0,
                                		oldval_desc='',
                                		newval_int=0,
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                            }
                            
                            //Status
                            if(!$flagme && $hritem['status'] != $user['active_status'] ) {
                            	
                            	$sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='active_status',
                                		oldval_char='$user[active_status]', 
                                		newval_char='$hritem[status]',
                                		oldval_int=0,
                                		oldval_desc='',
                                		newval_int=0,
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                            }
                                
                            
                            
                            // TODO: Need to flag problems for user 
                            // If ID not found during a change
                            // If first, last, dept are all different - might be wrong person
                            
                            if($flagme){
                                echo "Possible error:  $hritem[prefered_name] $hritem[last_name] did not match the database user: $user[first_name] $user[last_name]<br>";
                            }
                            
                            //Start Date
                            if(!$flagme && $user['start_date'] != $hritem['pattern_begin_date']) {
                            	
                            	$sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='start_date',
                                		oldval_char='', 
                                		newval_char='',
                                		oldval_int=$user[start_date],
                                		oldval_desc='',
                                		newval_int=$hritem[pattern_begin_date],
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                            	
                            }
                            
//Title
//Could go two ways - if its not a 'Professor' type title then the listed title might be considered the 'Secondary title' So need
 // to enter two possible changes and flag them clearly so only one is chosen
//Here's the logic I'm using: 
//If the secondary title is 'Associate P, Assistant P or P, then swap title and sec title first.
//If the title is any 'Prof' and the change is 'Instructor', ignore (legacy issue)
//If the title is any 'Prof' and the change is any 'Prof' (different), change
//If the title is any 'Prof' and the change is something else (Coordinator, etc), then compare with Sec title and change if necc.
//If the title is anything else, change if diff.
//If the secondary title is 'Associate P, Assistant P or P, then swap title and sec title first.
                            if(!$flagme && !strcasecmp($user['secondary_title'],'Assistant Professor') || !strcasecmp($user['secondary_title'],'Associate Professor') || !strcasecmp($user['secondary_title'],'Professor') ) {
                            
                            	$sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='secondary_title_swap',
                                		oldval_char='$user[secondary_title]', 
                                		newval_char='$user[title]',
                                		oldval_int=0,
                                		oldval_desc='',
                                		newval_int=0,
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                            }
                            
                            
                            //If the title is any 'Prof' and the change is 'Instructor', ignore (legacy issue)
                            if(!$flagme && (!strcasecmp($user['title'],'Assistant Professor') || !strcasecmp($user['title'],'Associate Professor') || !strcasecmp($user['title'],'Professor')) && !strcasecmp(rtrim($hritem['title']), 'Instructor')) $flagme.="Skipping title for $user[first_name] $user[last_name] (Professor to Instructor)"; 
                            //If the title is any 'Prof' and the change is any 'Prof' (different), change
                            
                            if(!$flagme && (!strcasecmp($user['title'],'Assistant Professor') || !strcasecmp($user['title'],'Associate Professor') || !strcasecmp($user['title'],'Professor')) && (!strcasecmp(rtrim($hritem['title']), 'Assistant Professor') || !strcasecmp(rtrim($hritem['title']), 'Associate Professor') || !strcasecmp(rtrim($hritem['title']), 'Professor')) && rtrim($user['title']) != rtrim($hritem['title'])) { 
                            	
                            		$sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='title',
                                		oldval_char='$user[title]', 
                                		newval_char='$hritem[title]',
                                		oldval_int=0,
                                		oldval_desc='',
                                		newval_int=0,
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                            	}
                            //If the title is any 'Prof' and the change is something else (Coordinator, etc), then compare with Sec title and change if necc.
                            if(!$flagme && (!strcasecmp($user['title'],'Assistant Professor') || !strcasecmp($user['title'],'Associate Professor') || !strcasecmp($user['title'],'Professor')) && (strcasecmp(rtrim($hritem['title']), 'Assistant Professor') && strcasecmp(rtrim($hritem['title']), 'Associate Professor') || strcasecmp(rtrim($hritem['title']), 'Professor')) && strcasecmp(rtrim($user['secondary_title']), rtrim($hritem['title'])) && rtrim($user['secondary_title']) != '' && rtrim($hritem['title']) != 'Instructor') {
                            	
                            	$sql="INSERT INTO hrchanges SET 
                                		hrchanges_id='null',
                                		employee_id=$user[emp_num], 
                                		fieldname='secondary_title',
                                		oldval_char='$user[secondary_title]', 
                                		newval_char='$hritem[title]',
                                		oldval_int=0,
                                		oldval_desc='',
                                		newval_int=0,
                                		newval_desc='',
                                		moddate=$hritem[run_date],
                                		completed=false";
                                if($debug) echo("$sql <br>");
                                else if(!$db->Execute($sql)) $success.='Error inserting into hrchanges table';
                                $count++;
                                //echo("Executed db call<br>");
                            }
                            
                            
                            //if(!$flagme && $user['title'] != rtrim($hritem['title']) && rtrim($hritem['title']) != 'Instructor')   {mysqlInsert('hrchanges',array('null',$user['emp_num'], 'title',$user['title'],rtrim($hritem['title']),0,'',0,'',$hritem['run_date'],false ));$count++;}
                            if (!$flagme) deleterow($hritem);
                            
                            
                       break;
                       
                       default:
                             $output.=("Encountered new change_indicator: $hritem[change_indicator] - this will need some programming intervention.</br>");

                            
                   }//switch
                } // is_array user
                else {
                    //if the user is not found it is either an ADD or an error
                    //If it is an ADD then generate a new user and provide feedback
                    switch($hritem['change_indicator']){
                       case "A":
                       		
                            //add the hritem as a new user
                            //$flagtext.="Adding $hritem[prefered_name] $hritem[last_name] as a new user<br>";
                            
                            //Add new user
                            
                            //First guess at the username
                            $username=strtolower(substr($hritem['prefered_name'],0,1)) . rtrim(strtolower($hritem['last_name']));
                            //echo "Checking $username<br>";
                            $usernameslash=addslashes($username);
                            $sql="SELECT * FROM users WHERE username='$usernameslash'";
                            $exists=$db->GetRow($sql);
                            if((($exists))) {
                            	$username=$username . '1';
                            	$output.=("Adding $username - as a placeholder. Need to verify<br>");
                            }
                            $hritem['prefered_name']=rtrim(mysql_escape_string($hritem['prefered_name']));
                            $hritem['last_name']=rtrim(mysql_escape_string($hritem['last_name']));
                            $username=mysql_escape_string($username);
                            $sql="INSERT INTO users SET 
                            		user_id='null',
                            		first_name='$hritem[prefered_name]',
                            		last_name='$hritem[last_name]',
                            		username='$username',
                            		password='',
                            		mail_events=1,
                            		mail_deadlines=1,
                            		department_id=$hritem[department_id],
                            		emp_type='FACL'";
                            $stopnow=false;
							if($debug) echo("$sql <br>");
                            else if(!$db->Execute($sql)) {
                            	$flagtext.="Error writing new user $hritem[prefered_name] $hritem[last_name]: $result1<br>";
                            	$stopnow=true;
                            }
                            
                            if(!$stopnow) {
                                $insert_id=$db->Insert_ID();
                                if($hritem['pattern'] == 'TSS') $tss=1; else $tss=0;
                                $start_date=date('Ymd');
                                $sql="INSERT INTO users_ext SET 
                                		emp_num=$hritem[employee_id],
                                		$user_id=$insert_id,                                		
                                		start_date=$start_date,		
                                		tss='$tss',
                                		tss_start=$hritem[pattern_begin_date],
                                		active_status=$hritem[status]";
                                if($debug) echo("$sql <br>");
                            		else if(!$db->Execute($sql)) {
                            		$flagtext.="Error writing new user_ext $hritem[prefered_name] $hritem[last_name]<br>";
                            		$stopnow=true;
                            	}
                                if(!$stopnow){
                                    $email=addslashes($username) . '@mtroyal.ca';
                                    $sql="INSERT INTO profiles SET
                                    	user_id=$insert_id,	
                                    	email='$email',
                                    	title='$hritem[title]'";
                                    if($debug) echo("$sql <br>");
                            		else if(!$db->Execute($sql)) {
                            		$flagtext.="Error writing new profile $hritem[prefered_name] $hritem[last_name]<br>";
                            		$stopnow=true;
                            	}
                            }
                            }
                                    
                            if(!$stopnow) $output.= "Added new user (confirm the following):<br>
                                                    Name: $hritem[prefered_name] $hritem[last_name] <br>
                                                    Email: $email<br>
                                                    Title: $hritem[title]<br>
                                                    Username: $username<br><br>";
                               

                            deleterow($hritem);
                            
                            
                       break;
                       
                       case "C":
                            //something is amiss
                            
                           
                            $output.= "Possible error: $hritem[prefered_name] $hritem[last_name] did not have a matching DB entry using the Employee ID as key. ID# $hritem[employee_id] <br>";
                            
                            
                       break;
                       
                       default:
                       
                            //something is amiss
                            
                            $output.= "Possible error: $hritem[prefered_name] $hritem[last_name] had an unknown change type.<br>";
                            
                            
                       break;
                    }//switch
                    
                }//else
                
	              }//not a Contract Employee
                $output.= ("Processed #$hrkey - $hritem[prefered_name] $hritem[last_name]<br>");
            }//foreach hrdata
            $success="Added $count changes to database for review..";
        } //is array
        if(isset($output)) echo ("Echoing Output:<br>" . $output);
        if(isset($mail)) mail('tdavis@mtroyal.ca', '[ROBOT] Contractor usernames', "The following errors occurred during a Contractor Import routine:\n\n$mail\n\nRegards,\nCron");
} //genchanges

if (isset($_REQUEST['ignore'])) {
    if(isset($_REQUEST['hrchanges_id'])){
    	$sql="SELECT * FROM hrchanges WHERE hrchanges_id=$_REQUEST[hrchanges_id]";
    	$item=$db->GetRow($sql);
        if(($item)){
        	
            $sql="UPDATE hrchanges SET completed=1 WHERE hrchanges_id=$item[hrchanges_id]";
            if(!$db->Execute($sql)) $success="Not entered: ".$db->ErrorMsg();
            else $success='Ignored';
        }//isarray
    }
}//ignore

if (isset($_REQUEST['replace'])) {

    if(isset($_REQUEST['hrchanges_id'])){
    $success='';
        $sql="SELECT * FROM hrchanges WHERE hrchanges_id=$_REQUEST[hrchanges_id]";
    	$item=$db->GetRow($sql);
        if(($item)){
        	
            $sql="SELECT * FROM users LEFT JOIN users_ext ON (users.user_id=users_ext.user_id) LEFT JOIN profiles ON (users.user_id=profiles.user_id) LEFT JOIN departments ON (users.department_id=departments.department_id) WHERE users_ext.emp_num=$item[employee_id]";
            $user=$db->GetRow($sql);
            if(($user) && ($item)){
                switch($item['fieldname']){
                    case 'last_name':
                    case 'first_name':
                        $sql="UPDATE users SET $item[fieldname]='$item[newval_char]' WHERE user_id=$user[user_id]";
                        if($debug) echo ("$sql<br>");
                        else if(!$db->Execute($sql)) $success="Error updating: ". $db->ErrorMsg();
                        else $success="Updated $item[fieldname] to $item[newval_char] for user $user[user_id]";
                    break;
                    
                    case 'pattern':
                        $val=false;
                        if($item['newval_char'] == 'TSS') $val=true; 
                        $sql="UPDATE users_ext' SET tss=$val WHERE user_id=$user[user_id]";
                        if($debug) echo ("$sql<br>");
                        else if(!$db->Execute($sql)) $success="Error updating: ". $db->ErrorMsg();
                        else $success="Updated $item[fieldname] to $item[newval_char] for user $user[user_id]";
                    break;
                    
                    case 'department_id':
                        $sql="UPDATE users SET $item[fieldname]=$item[newval_int] WHERE user_id=$user[user_id]";
                        if($debug) echo ("$sql<br>");
                        else if(!$db->Execute($sql)) $success="Error updating: ". $db->ErrorMsg();
                        else $success="Updated $item[fieldname] to $item[newval_int] for user $user[user_id]";
                    break;
                    
                    case 'active_status':
                    	if($item['newval_char']=='T') {
                    		$sql="UPDATE users SET user_level=2 WHERE user_id=$user[user_id]";	
                    		if($debug) echo ("$sql<br>");
                    		else if(!$db->Execute($sql)) $success="Error disabling: ". $db->ErrorMsg();
                    		else $success.="Disabled user $user[user_id]";
							$sql="INSERT INTO users_disabled SET user_id=$user[user_id]";
							if($debug) echo ("$sql<br>");
							else if(!$db->Execute($sql)) $success="Error disabling: ". $db->ErrorMsg();
                    	}
                        $sql="UPDATE users_ext SET $item[fieldname]='$item[newval_char]' WHERE user_id=$user[user_id]";
                        if(!$db->Execute($sql)) $success="Error Updating: ". $db->ErrorMsg();
                        else $success.="Updated $item[fieldname] to $item[newval_char] for user $user[user_id]";
                    break;
                    
                    case "start_date":
                         $sql="UPDATE users_ext SET $item[fieldname]='$item[newval_int]' WHERE user_id=$user[user_id]";
                         if($debug) echo ("$sql<br>");
                        else if(!$db->Execute($sql)) $success="Error updating: ". $db->ErrorMsg();
                        else $success="Updated $item[fieldname] to $item[newval_int] for user $user[user_id]"; 
                    break; 
                    
                    case "title":
                    case 'secondary_title':
                        $sql="UPDATE profiles SET $item[fieldname]='$item[newval_char]' WHERE user_id=$user[user_id]";
                        if($debug) echo ("$sql<br>");
                        else if(!$db->Execute($sql)) $success="Error updating: ". $db->ErrorMsg();
                        else $success="Updated $item[fieldname] to $item[newval_char] for user $user[user_id]";
                    break;
                    
                    case 'secondary_title_swap':
                        $sql="UPDATE profiles SET title='$item[oldval_char]' WHERE user_id=$user[user_id]";
                        if($debug) echo ("$sql<br>");
                        else if(!$db->Execute($sql)) $success="Error updating: ". $db->ErrorMsg();
                        else $success="Updated $item[fieldname] to $item[newval_char] for user $user[user_id]";
                        $sql="UPDATE profiles SET secondary_title='$item[newval_char]' WHERE user_id=$user[user_id]";
                        if($debug) echo ("$sql<br>");
                        else if(!$db->Execute($sql)) $success="Error updating: ". $db->ErrorMsg();
                        else $success.="Updated $item[fieldname] to $item[newval_char] for user $user[user_id]";
                        
                    
                }//switch
            
                if(1) {
                    $sql="UPDATE hrchanges SET completed=TRUE WHERE hrchanges_id=$item[hrchanges_id]";
                    if($debug) echo ("$sql<br>");
                    else if(!$db->Execute($sql)) $success.="<font color='red'>Not entered: ". $db->ErrorMsg();
                }
                 
            }//isarray both
        }//isarray
    }
}//replace

if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "load":
            /**
            * Load a file for entry or testing
            * 
            * Choose from a list on the local system or upload directly. 
            */
            $output='';
			
			$hasharray = array('success'=>$success, 'output'=>$output);
			$filename = 'templates/template-hrimport_load.html';
            
			
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            

            /*
            $admins=mysqlFetchRows("admin","1 order by user_id");
            $output="";
            if(is_array($admins)){
                foreach($admins as $admin){
                    $user=mysqlFetchRow("users","user_id=$admin[user_id]");
                    if($admin['department_id'] == 0) $dept="";
                    else {
                        $department=mysqlFetchRow("departments","department_id=$admin[department_id]","name");
                        $dept=$department['name'];
                    }
                    if($admin['division_id'] == 0) $div="";
                    else {
                        $division=mysqlFetchRow("divisions","division_id=$admin[division_id]","name");
                        $div=$division['name'];
                    }
                    
                    $output.="
                        <tr><td bgcolor='#E09731'><a style='color:white' href='administrators.php?section=update&id=$admin[admin_id]'><b>Update</b></a></td>
                        <td bgcolor='#D7D7D9'>$user[last_name]</td>
                        <td bgcolor='#D7D7D9'>$user[first_name]</td>
                        <td bgcolor='#D7D7D9'>$admin[title]</td>
                        <td bgcolor='#D7D7D9'>$dept</td>
                        <td bgcolor='#D7D7D9'>$div</td></tr>";
                    
                }
                $hasharray = array('success'=>$success, 'output'=>$output);
                $filename = 'templates/template-admin_view.html';
            }
            
            else {
                $hasharray = array('title'=>"Administrators");
                $filename = 'includes/error-no_records.html';
            }
            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;
            */
			break;
            
            /**
            * Load single item for decision
            * 
            * Loads single item from the comparison table and prompts for a decision - commit or ignore.
            */
		case "compare":
        	
            $sql="SELECT * FROM hrchanges WHERE completed=0";
            $item=$db->GetRow($sql);
            if(($item)){
            	$sql="SELECT * FROM users LEFT JOIN users_ext ON (users.user_id=users_ext.user_id) WHERE users_ext.emp_num=$item[employee_id]" ;
            	$user=$db->GetRow($sql);
            	
            }
            if(is_array($item) && is_array($user)){
                
                if($item['newval_char']=='') { $old="$item[oldval_int] "; if($item['oldval_desc'] != '') $old.="($item[oldval_desc])";}
                else $old=$item['oldval_char'];
                if($item['newval_char']=='') { $new="$item[newval_int] "; if($item['newval_desc'] != '') $new.="($item[newval_desc])";}
                else $new=$item['newval_char'];
                
                $username=$user['last_name'] . ', ' . $user['first_name'];
                $hasharray = array('success'=>$success, 'flagtext'=>$flagtext,'username'=>$username,'hrchanges_id'=> $item['hrchanges_id'],'field'=>$item['fieldname'] ,'oldval'=>$old,'newval'=>$new );
                $filename = 'templates/template-hrimport_compare.html';
                $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
                echo $parsed_html_file;
            }
            else {
                //echo print_r($item);
                if(is_array($item) && !is_array($user)) $success="Change record for $item[employee_id] does not have matching USER entry";
                else $success="No more records";
                $hasharray = array('title'=>"HR Import Compare",'success'=>$success);
                $filename = 'templates/error-no-records.html';
                $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
                echo $parsed_html_file;
            }
			break;
		case "update":
        
        /*
			$admin = mysqlFetchRow("admin", "admin_id=$_REQUEST[id]");
			$users = mysqlFetchRows("users","1 order by last_name,first_name");
			$divisions = mysqlFetchRows("divisions","1 order by name");
			$departments = mysqlFetchRows("departments","1 order by name");
			$user_list = "";$dept_list="";$division_list="";
			if(is_array($users)) {
				foreach($users as $user) {
					if ($user['user_id']==$admin['user_id']) $user_list .= "<option selected value='".$user['user_id']."'>".$user['last_name'].", ".$user['first_name']."</option>";
					else $user_list .= "<option value='".$user['user_id']."'>".$user['last_name'].", ".$user['first_name']."</option>";
				}
			}
			if(is_array($departments)) {
				foreach($departments as $department) {
				if($department['department_id']==$admin['department_id']) $dept_list .= "<option selected value='".$department['department_id']."'>".$department['name']."</option>";
				else $dept_list .= "<option value='".$department['department_id']."'>".$department['name']."</option>";
				}  
			}
			if(is_array($divisions)) {
				foreach($divisions as $division) {
				if($division['division_id']==$admin['division_id']) $division_list .= "<option selected value='".$division['division_id']."'>".$division['name']."</option>";
				else $division_list .= "<option value='".$division['division_id']."'>".$division['name']."</option>";
				}
			}
			$title_list = "";
			foreach($titles as $title){
				if($title==$admin['title']) $title_list.="<option selected value='$title'>$title</option>";
				else $title_list.="<option  value='$title'>$title</option>";
			}
			$hasharray = array('success'=>$success,'id'=>$_REQUEST['id'],'user_list'=>$user_list,'dept_list'=>$dept_list,'division_list'=>$division_list,'title_list'=>$title_list);
			$filename = 'templates/template-admin_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
            
            */
			break;
	}
}
//-- Footer File
include("templates/template-footer.html");

function deleterow($hritem) {
	global $db;
	$result=false;
    //global $flagtext;
    $sql="DELETE FROM hrimport WHERE hrimport_id='$hritem[hrimport_id]'";
    if(!$db->Execute($sql)) $result='Error deleting row from hrimport table';
    //$flagtext.="Deleted one row\n<br>";
    return $result;
    
}
?>
