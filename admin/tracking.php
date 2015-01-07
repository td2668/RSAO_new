<?php
//error_reporting(E_ALL);
include("includes/config.inc.php");
include("includes/functions-required.php");

$hdr=loadPage("header",'Header');
$tmpl=loadPage("tracking", 'Tracking Form Management');

define('FILEPATH', $configInfo['tracking_docs']);


$success='';

if(isset($_REQUEST['printpdf']) && isset($_REQUEST['form_tracking_id'])){
    include_once("includes/print_tracking.php");
    $sql="SELECT form_tracking_id,user_id FROM forms_tracking WHERE form_tracking_id=$_REQUEST[form_tracking_id]";
    $form=$db->getRow($sql);
    if(count($form)>0) printPDF($_REQUEST['form_tracking_id'],$form['user_id'],$db);
}

if(isset($_REQUEST['saveagency'])) if($_REQUEST['saveagency']==true){
	//Add the agency and program listed, and make them the current selections

	if($_REQUEST['agency_name']!=''){
		$sql="SELECT * FROM ors_agency WHERE name='". mysql_real_escape_string($_REQUEST['agency_name']) ."'";
		$agency=$db->getRow($sql);
		if($agency) $agency_id=$agency['id'];
		else {
			$sql="INSERT INTO ors_agency (`name`) VALUES('". mysql_real_escape_string($_REQUEST['agency_name']) ."')";
			if($db->Execute($sql) === false)
				$success.= " <font color='red'>Error inserting Agency: ".$db->ErrorMsg()."</font>";
			else $success.=" Inserted Agency ";
			$agency_id=mysql_insert_id();
			$_REQUEST['agency_id']=$agency_id;
			$_REQUEST['program_id']=0;
		}
	}//if agency
	else {
		//No agency listed, so add a program only if an agency_id is already listed
		if($_REQUEST['agency_id']!=0) $agency_id=$_REQUEST['agency_id'];
		else $agency_id=0;
	}
	//Finally do the program
	if($_REQUEST['program_name'] && $agency_id){
		$sql="INSERT INTO ors_program (`name`,`ors_agency_id`) VALUES('". mysql_real_escape_string($_REQUEST['program_name']) ."',$agency_id)";
		echo $sql;
		if($db->Execute($sql) === false)
			$success.= " <font color='red'>Error inserting Program: ".$db->ErrorMsg()."</font>";
		else $success.=" Inserted Program";
		$_REQUEST['program_id']=mysql_insert_id();
	}
	$_REQUEST['section']='edit';	
	
}

if(isset($_REQUEST['add'])){
	$success='';
	$sql="INSERT into forms_tracking VALUES();";
	if($db->Execute($sql) === false)
		$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
	else $success="Started";
	$_REQUEST['id']=mysql_insert_id();
	$sql="INSERT into forms_tracking_budgets (`form_tracking_id`) VALUES($_REQUEST[id]);";
	if($db->Execute($sql) === false)
		$success.= " <font color='red'>Error inserting budget: ".$db->ErrorMsg()."</font>";
	else $success.=" ";

	$_REQUEST['section']='edit';
}


if(isset($_REQUEST['delete'])){
	if(isset($_REQUEST['id'])) {
		$sql="DELETE from forms_tracking WHERE form_tracking_id={$_REQUEST['id']}";
		$result=$db->Execute($sql);
		$sql="DELETE from forms_tracking_budgets WHERE form_tracking_id={$_REQUEST['id']}";
		$result=$db->Execute($sql);
		$sql="DELETE from forms_tracking_coresearchers WHERE form_tracking_id={$_REQUEST['id']}";
		$result=$db->Execute($sql);
		$success='Deleted';
		$_REQUEST['section']='list';
	}
}

if(isset($_REQUEST['deletecoresearcher']) && isset($_REQUEST['id']) && isset($_REQUEST['user_id'])){
	$sql="DELETE FROM `forms_tracking_coresearchers` WHERE `form_tracking_id` = '{$_REQUEST['id']}' AND `user_id`= '{$_REQUEST['user_id']}'";
	$result=$db->Execute($sql);
	//echo $sql;
	if(!$result) print($db->ErrorMsg());
	$_REQUEST['section']='edit';
}

if(isset($_REQUEST['add_co'])) if(intval($_REQUEST['add_co']) > 0){
		$sql=" INSERT INTO `forms_tracking_coresearchers`
                (`form_tracking_coresearcher_id`,`user_id`,`form_tracking_id`)
                VALUES(NULL, {$_REQUEST['add_co']}, {$_REQUEST['id']})";
		$result=$db->Execute($sql);
		if(!$result) print($db->ErrorMsg());

		$_REQUEST['section']='edit';

	}

if(isset($_REQUEST['update']) || $_REQUEST['saveme']){
	$_REQUEST['section']='edit';
	if(isset($_REQUEST['id'])){
		$tracking_name = (isset($_REQUEST['tracking_name'])) ? mysql_real_escape_string($_REQUEST['tracking_name']) : '(no name)';
		$pi= (isset($_REQUEST['pi']) ? TRUE : FALSE);
		$newproject = (isset($_REQUEST['newproject'])) ? mysql_real_escape_string($_REQUEST['newproject']) : '';
		$synopsis = (isset($_REQUEST['synopsis'])) ? mysql_real_escape_string($_REQUEST['synopsis']) : '';
		$pi_id= (isset($_REQUEST['pi_id']) ? $_REQUEST['pi_id'] : 0);
		$user_id= (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0);
		$project_id= (isset($_REQUEST['project_id']) ? $_REQUEST['project_id'] : 0);
		$costudents= (isset($_REQUEST['costudents']) ? TRUE : FALSE);
		$firstname = (isset($_REQUEST['firstname'])) ? mysql_real_escape_string($_REQUEST['firstname']) : '';
		$lastname = (isset($_REQUEST['lastname'])) ? mysql_real_escape_string($_REQUEST['lastname']) : '';
		$phone = (isset($_REQUEST['phone'])) ? mysql_real_escape_string($_REQUEST['phone']) : '';
		$email = (isset($_REQUEST['email'])) ? mysql_real_escape_string($_REQUEST['email']) : '';
		$position = (isset($_REQUEST['position'])) ? mysql_real_escape_string($_REQUEST['position']) : '';
		$address1 = (isset($_REQUEST['address1'])) ? mysql_real_escape_string($_REQUEST['address1']) : '';
		$address2 = (isset($_REQUEST['address2'])) ? mysql_real_escape_string($_REQUEST['address2']) : '';
		$address3 = (isset($_REQUEST['address3'])) ? mysql_real_escape_string($_REQUEST['address3']) : '';
		$coresearchers = (isset($_REQUEST['coresearchers'])) ? mysql_real_escape_string($_REQUEST['coresearchers']) : '';
		$modified=mktime();
		$funding= (isset($_REQUEST['funding']) ? TRUE : FALSE);
		$agency_id= (isset($_REQUEST['agency_id']) ? $_REQUEST['agency_id'] : 0);
		$agency_name = (isset($_REQUEST['agency_name'])) ? mysql_real_escape_string($_REQUEST['agency_name']) : '';
		$program_id= (isset($_REQUEST['program_id']) ? $_REQUEST['program_id'] : 0);
		$funding_confirmed= (isset($_REQUEST['funding_confirmed']) ? TRUE : FALSE);
		$requested = (isset($_REQUEST['requested'])) ? mysql_real_escape_string($_REQUEST['requested']) : '';
		$received = (isset($_REQUEST['received'])) ? mysql_real_escape_string($_REQUEST['received']) : '';
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
		$equipment_flag= (isset($_REQUEST['equipment_flag']) ? TRUE : FALSE);
		$equipment = (isset($_REQUEST['equipment'])) ? mysql_real_escape_string($_REQUEST['equipment']) : '';
		$space_flag= (isset($_REQUEST['space_flag']) ? TRUE : FALSE);
		$space = (isset($_REQUEST['space'])) ? mysql_real_escape_string($_REQUEST['space']) : '';
		$commitments_flag= (isset($_REQUEST['commitments_flag']) ? TRUE : FALSE);
		$commitments = (isset($_REQUEST['commitments'])) ? mysql_real_escape_string($_REQUEST['commitments']) : '';
		$employ_flag= (isset($_REQUEST['employ_flag']) ? TRUE : FALSE);
		$emp_students= (isset($_REQUEST['emp_students']) ? TRUE : FALSE);
		$emp_ras= (isset($_REQUEST['emp_ras']) ? TRUE : FALSE);
		$emp_consultants= (isset($_REQUEST['emp_consultants']) ? TRUE : FALSE);
		$loc_mru= (isset($_REQUEST['loc_mru']) ? TRUE : FALSE);
		$loc_canada= (isset($_REQUEST['loc_canada']) ? TRUE : FALSE);
		$loc_international= (isset($_REQUEST['loc_international']) ? TRUE : FALSE);
		$human_b= (isset($_REQUEST['human_b']) ? TRUE : FALSE);
		$human_h= (isset($_REQUEST['human_h']) ? TRUE : FALSE);
		$biohaz= (isset($_REQUEST['biohaz']) ? TRUE : FALSE);
		$animal= (isset($_REQUEST['animal']) ? TRUE : FALSE);
		$where = (isset($_REQUEST['where'])) ? mysql_real_escape_string($_REQUEST['where']) : '';
		$trackoptions = (isset($_REQUEST['trackoptions'])) ? mysql_real_escape_string($_REQUEST['trackoptions']) : '';
		$documents = (isset($_REQUEST['documents'])) ? mysql_real_escape_string($_REQUEST['documents']) : '';
		$iagree= (isset($_REQUEST['iagree'])) ? 1 : 0;
		$status= (isset($_REQUEST['status'])) ? $_REQUEST['status'] : 0;
/*		$date_received=(isset($_REQUEST['date_received'])) ? (check8601date($_REQUEST['date_received']) ? $_REQUEST['date_received'] : '0000-00-00') : '0000-00-00';*/
		$dean_date=(isset($_REQUEST['dean_date'])) ? (check8601date($_REQUEST['dean_date']) ? $_REQUEST['dean_date'] : '0000-00-00 00:00:00') : '0000-00-00 00:00:00';
		$ors_date=(isset($_REQUEST['ors_date'])) ? (check8601date($_REQUEST['ors_date']) ? $_REQUEST['ors_date'] : '0000-00-00 00:00:00') : '0000-00-00 00:00:00';
		$dean_sig=(isset($_REQUEST['dean_sig']) ? TRUE : FALSE);
		$ors_sig=(isset($_REQUEST['ors_sig']) ? TRUE : FALSE);
		$ors_id= (isset($_REQUEST['ors_id'])) ? $_REQUEST['ors_id'] : 0;
		$dean_id= (isset($_REQUEST['dean_id'])) ? $_REQUEST['dean_id'] : 0;

		$sql="UPDATE forms_tracking SET
            `project_id` = '{$project_id}',
           `newproject` = '{$newproject}',
           `synopsis` = '{$synopsis}',
           `modified` = NOW(),
           `tracking_name` = '{$tracking_name}',
           `pi` = '{$pi}',
           `pi_id` = '{$pi_id}',
           `user_id` = '{$user_id}',
           `firstname` = '{$firstname}',
           `lastname` = '{$lastname}',
           `phone` = '{$phone}',
           `email` = '{$email}',
           `position` = '{$position}',
           `address1` = '{$address1}',
           `address2` = '{$address2}',
           `address3` = '{$address3}',
           `coresearchers` = '{$coresearchers}',
           `costudents` = '{$costudents}',
           `funding` = '{$funding}',
           `agency_id` = '{$agency_id}',
           `agency_name` = '{$agency_name}',
           `program_id` = '{$program_id}',
           `funding_confirmed` = '{$funding_confirmed}',
           `requested` = '{$requested}',
           `received` = '{$received}',
           `equipment_flag` = '{$equipment_flag}',
           `equipment` = '{$equipment}',
           `space_flag` = '{$space_flag}',
           `space` = '{$space}',
           `commitments_flag` = '{$commitments_flag}',
           `commitments` = '{$commitments}',
           `employ_flag` = '{$employ_flag}',
           `emp_students` = '{$emp_students}',
           `emp_ras` = '{$emp_ras}',
           `emp_consultants` = '{$emp_consultants}',
           `loc_mru` = '{$loc_mru}',
			`loc_canada` = '{$loc_canada}',
			`loc_international` = '{$loc_international}',
			`where` = '{$where}',
			`human_b` = '{$human_b}',
			`human_h` = '{$human_h}',
			`biohaz` = '{$biohaz}',
			`animal` = '{$animal}',
			`trackoptions` = '{$trackoptions}',
			`documents` = '{$documents}',
			`iagree` = {$iagree},
			`status` = {$status},
/*
			`date_received` = '{$date_received}',
*/
			`dean_date` = '{$dean_date}',
			`ors_date` = '{$ors_date}',
			`ors_sig`='{$ors_sig}',
			`ors_id`='{$ors_id}',
			`dean_sig`='{$dean_sig}',
			`dean_id`='{$dean_id}'



            WHERE form_tracking_id= $_REQUEST[id];
            ";
		//echo $sql;
		if($db->Execute($sql) === false)
			$success= "<font color='red'>Error inserting: ".$db->ErrorMsg()."</font>";
		else $success="Saved";

		$sql2="UPDATE `forms_tracking_budgets` SET
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


                WHERE `form_tracking_id` = '{$_REQUEST['id']}'
                ";

		$result=$db->Execute($sql2);
		if(!$result) print($db->ErrorMsg());
	}
}

if(!isset($_REQUEST['section'])) $_REQUEST['section']="view";

switch($_REQUEST['section']){

case 'view':

	$tmpl->setAttribute('list','visibility','visible');
	if(isset($_REQUEST['sort'])) $sort=$_REQUEST['sort'];
	else $sort='form_tracking_id';

	if(isset($_REQUEST['dir'])) {
		if($_REQUEST['dir']=='ASC') $dir='ASC';
		else $dir='DESC';
	}
	else $dir='DESC'; //default


    $where = " forms_tracking.status != 2 ";  // default hide completed forms

    if(isset($_REQUEST['type'])) {
        if($_REQUEST['type'] == 'completed')
            $where = " forms_tracking.status = 2";
        if($_REQUEST['type'] == 'submitted')
            $where = " forms_tracking.status = 1";
        if($_REQUEST['type'] == 'presubmit')
            $where = " forms_tracking.status = 0";

        $type = $_REQUEST['type'];
    } else {
        $where = " forms_tracking.status IN (0,1) ";
        $type = 'active';
    }

	$sql="	SELECT forms_tracking.*, CONCAT(u1.last_name,u1.first_name) AS owner, ors_project.name AS project_title,
	                hreb.status AS hreb_status, approvals.approved AS dean_status, departments.name AS department_name
	                FROM forms_tracking
         			LEFT JOIN users as u1 ON(u1.user_id=forms_tracking.user_id)
         			LEFT JOIN departments ON u1.department_id = departments.department_id
         			LEFT JOIN ors_project ON(ors_project.id=forms_tracking.project_id)
         			LEFT JOIN hreb ON forms_tracking.form_tracking_id = hreb.trackingId
         			LEFT JOIN forms_tracking_approvals AS approvals ON forms_tracking.form_tracking_id = approvals.tracking_id AND approvals.approval_type_id IN (1,7)
         			WHERE" . $where ." ORDER BY $sort $dir";

	$apps=$db->getAll($sql);

    $sql = "SELECT value, name FROM hreb_status";
    $HREB_statuses = $db->getAll($sql);

	if(count($apps)>0){
		foreach($apps as $key=>$app){

            switch($app['status']) {
                case 0:
                    $apps[$key]['status'] = '<span style="color: #b8860b;">Pre-submit</span>';
                    $apps[$key]['archive'] = "<button type='button' onClick='window.location=\"tracking.php?section=archive&id=" . $app['form_tracking_id'] . "\";'>Archive</button>";
                    break;
                case 1:
                    $apps[$key]['status'] = '<span style="color: green;">Submitted</span>';
                    $apps[$key]['archive'] = "<button type='button' onClick='window.location=\"tracking.php?section=archive&id=" . $app['form_tracking_id'] . "\";'>Archive</button>";
                    break;
                case 2:
                    $apps[$key]['status'] = 'complete';
                    $apps[$key]['archive'] = '';
                    break;
                default :
                    $apps[$key]['status'] = 'unknown';
                    $apps[$key]['archive'] = '';
                    break;
            }

            // If the form has been submitted
            if($app['status'] == 1) {

                if($app['status'] != 0 && $app['human_b']) {
                    foreach($HREB_statuses AS $status) {
                        //Populate HREB Status number with corresponding string name from DB
                        if($status['value'] == $app['hreb_status']) {
                            $apps[$key]['hreb_status'] = $status['name'];
                            break;
                        }
                    }
                }
                else {
                    $apps[$key]['hreb_status'] = "";  // if we don't have HREB, then show nothing for status
                }

                // Determine the Dean's approval status
                $sql = "SELECT approved, date_approved FROM forms_tracking_approvals
                        WHERE tracking_id = " . $app['form_tracking_id'] . " AND approval_type_id IN(1,7)"; // 1 and 7 are Commitments and Dean Review
                $deanStatus = $db->getRow($sql);

                $status = "";
                if(sizeof($deanStatus) > 0) {
                    $status = $deanStatus['approved'] == 0 ? '<div style="color: #b8860b;">Pending</div>' : '<div style="color: green;">Approved</div>';
                }
                $apps[$key]['dean_status'] = $status;


                // Determine the ORS approval status
                $sql = "SELECT approved, date_approved FROM forms_tracking_approvals
                        WHERE tracking_id = " . $app['form_tracking_id'] . " AND approval_type_id IN(2,8)"; // 1 and 2 are COI and ORS Review
                $OrsStatus = $db->getRow($sql);

                $status = "";
                if(sizeof($OrsStatus) > 0) {
                    $status = $OrsStatus['approved'] == 1 ? '<div style="color: green;">Approved</div>' : "<button type='button' onClick='window.location=\"tracking.php?section=approve&id=" . $app['form_tracking_id'] . "\";'>Approve</button>";
                }
                $apps[$key]['ors_status'] = $status;
            }

            //Owner is different than PI
			$sql="SELECT user_id,last_name,first_name FROM users WHERE user_id=$app[user_id]";
			$user=$db->getRow($sql);
			if($user) $apps[$key]['owner']="$user[last_name], $user[first_name]";

			//Process PI
			if($app['pi']) $app['pi_id']=$app['user_id'];  //For now

			if($app['pi_id']!=0){
				$sql="SELECT user_id,last_name,first_name FROM users WHERE user_id=$app[pi_id]";
				$user=$db->getRow($sql);
				if($user) $apps[$key]['pi_name']="$user[last_name], $user[first_name]";
			}
			else{
                if(isset($app['last_name'])) {
				$apps[$key]['pi_name']="$app[last_name], $app[first_name]";
                }
			}
			if($app['human_b'] || $app['human_h']) $apps[$key]['hreb']="checked='checked'";
			if($app['biohaz']) $apps[$key]['biohaz']="checked='checked'";
			if($app['animal']) $apps[$key]['animal']="checked='checked'";

            $apps[$key]['modified'] = date('Y-m-d', strtotime($app['modified']));
            $apps[$key]['created'] = date('Y-m-d', strtotime($app['created']));


            if(strlen($app['tracking_name'])>40) $apps[$key]['tracking_name']=substr($app['tracking_name'],0,40) . '...';

			if($app['project_id']!=0) {
				$sql="SELECT name FROM ors_project WHERE id=$app[project_id]";
				$proj=$db->getRow($sql);
				if($proj) if(strlen($proj['name'])>40) $apps[$key]['project_title']=substr($proj['name'],0,40) . '...';
					else $apps[$key]['project_title']=$proj['name'];
			}
			
			$sql="SELECT * FROM forms_coi WHERE form_tracking_id=$app[form_tracking_id]";
			$cois=$db->getAll($sql);
			$apps[$key]['coi']=count($cois);
			$apps[$key]['coicolour']='white';
			foreach($cois as $coi) if(!$coi['coi_none']) $apps[$key]['coicolour']='#FF9999';

            // create a link to uploaded files
            $userId = $app['user_id'];
            $trackingId = $app['form_tracking_id'];
            $basePath = FILEPATH . $userId . '/' . $trackingId;
            $files = loadFiles($basePath);
            $numFiles =  count($files);
            $apps[$key]['numfiles'] = $numFiles;
            if($numFiles > 0) {
                $apps[$key]['showfiles'] ='block';
            } else {
                $apps[$key]['showfiles'] = 'none';
            }

		}//foreach
		if($dir=='ASC')$dir='DESC';
		else $dir='ASC';
		$tmpl->addVar('list','dir',$dir);
        $tmpl->addVar('list','type', $type);
        $tmpl->addRows('mainlist',$apps);
		if(isset($success)) $tmpl->addVar('list','success',$success);
	}//if count>0


	break;

case 'approve':
    if(isset($_REQUEST['id'])) {
        $sql = "UPDATE forms_tracking_approvals SET approved = 1, date_approved = NOW() WHERE tracking_id = " . $_REQUEST['id'] . " AND approval_type_id IN (2,8)";
        $success = "";
        if($db->Execute($sql) === false) {
            $success = "<font color='red'>Error approving tracking form : ".$db->ErrorMsg()."</font>";
        } else {
            $success = "Tracking form approved.";
        }
        if(isset($success)) $tmpl->addVar('list','success',$success);
        header( 'Location: tracking.php') ;
    }
        break;

case 'files':
    if(isset($_REQUEST['id'])) {
        $tmpl->setAttribute('files','visibility','visible');

        $sql="SELECT * FROM forms_tracking WHERE form_tracking_id={$_REQUEST['id']}";
        $app=$db->getRow($sql);

        $tmpl->AddVar('files', 'tracking_id', $app['form_tracking_id']);
        $tmpl->AddVar('files', 'user_id', $app['user_id']);
        $tmpl->AddVar('fileslist', 'tracking_id', $app['form_tracking_id']);
        $tmpl->AddVar('fileslist', 'user_id', $app['user_id']);
        $tmpl->AddVar('files', 'details', $app['form_tracking_id']);


        $userId = $app['user_id'];
        $trackingId = $app['form_tracking_id'];
        $basePath = FILEPATH . $userId . '/' . $trackingId;
        $files = loadFiles($basePath);

        $tmpl->AddRows('fileslist', $files);
    }
    break;

    case 'archive':
        if(isset($_REQUEST['id'])) {
            $sql="UPDATE `forms_tracking` SET `status` = 2 WHERE form_tracking_id={$_REQUEST['id']}";
            $result = $db->Execute($sql);
        }
        header( 'Location: tracking.php') ;
        break;

    case 'deletefile':
        if(isset($_REQUEST['form_tracking_id']) && isset($_REQUEST['userid']) && isset($_REQUEST['filename'])) {
            $trackingId =  $_REQUEST['form_tracking_id'];
            $userId = $_REQUEST['userid'];
            $filename = $_REQUEST['filename'];

            $filePath = FILEPATH . $userId . '/' . $trackingId . "/" . $filename;
            deleteFile($filePath);

            header( 'Location: tracking.php?section=files&id=' . $trackingId ) ;

        }
        break;

case 'edit':
	if(isset($_REQUEST['id'])){
		$tmpl->setAttribute('edit','visibility','visible');
		$sql="SELECT * FROM forms_tracking WHERE form_tracking_id={$_REQUEST['id']}";
		$app=$db->getRow($sql);
		if($app){

			//$app['modified']=date('Y-m-d',strtotime($app['date_received']));

			//$tmpl->addRows('pi_options',$users);
			//$tmpl->addRows('co_options',$cousers);


			$sql="SELECT * FROM ors_project WHERE ors_project_status_id=1 ORDER BY name";
			$projs=$db->getAll($sql);
			foreach($projs as $key=>$proj){
				$projs[$key]['sel']='';
				if($app['project_id']==$proj['id']) $projs[$key]['sel']="selected='selected'";
			}
			$tmpl->addRows('project_options',$projs);


			//in this case load the user as the PI and set the checkbox
			if($app['pi']) {
				$app['pi_id']=$app['user_id'];
				$app['pi']='selected';
			}
			else $app['pi']='';

			$sql="SELECT * FROM users
                    		LEFT JOIN users_disabled using (user_id)
                            WHERE
                            (`emp_type`='FACL' OR `emp_type`='MAN')
                            AND ISNULL(users_disabled.user_id)
                            ORDER BY last_name,first_name";
			$users=$db->getAll($sql);
			$deans=$ors=$users2=$users;
			foreach($users as $key=>$oneuser){
				$deans[$key]['sel']=$ors[$key]['sel']=$users[$key]['sel']=$users2[$key]['sel']='';
				if($oneuser['user_id']==$app['user_id']) $users[$key]['sel']="selected='selected'";
				if($oneuser['user_id']==$app['pi_id']) $users2[$key]['sel']="selected='selected'";
				if($oneuser['user_id']==$app['dean_id']) $deans[$key]['sel']="selected='selected'";
				if($oneuser['user_id']==$app['ors_id']) $ors[$key]['sel']="selected='selected'";
			}
			$tmpl->addRows('owner_options',$users);
			$tmpl->addRows('pi_options',$users2);
			$tmpl->addRows('ors_sig',$ors);
			$tmpl->addRows('dean_sig',$deans);

			if($app['pi']) $app['pi']="checked"; else $app['pi']='';
			if($app['costudents']) $app['costudents']="checked"; else $app['costudents']='';
			if($app['funding']) $app['funding']="checked"; else $app['funding']='';

			$sql="SELECT * FROM ors_agency WHERE 1 ORDER BY name";
			$agencies=$db->getAll($sql);
			foreach($agencies as $key=>$agency){
				if($app['agency_id']==$agency['id']) $agencies[$key]['sel']="selected='selected'";
			}
			if($app['agency_id']){
				$sql="SELECT * FROM ors_program WHERE ors_agency_id=$app[agency_id] ORDER BY name";
				$programs=$db->getAll($sql);
				if($programs) foreach($programs as $key=>$program)
						if($program['id']==$app['program_id']) $programs[$key]['sel']="selected='selected'";
			}
			$tmpl->addRows('agency',$agencies);
			$tmpl->addRows('program',$programs);

			//Co Reseachers
			//Generate co-researcher list
			$co_researchers=array();
			$oneline=array();
			$sql="SELECT * FROM `forms_tracking_coresearchers`
                            LEFT JOIN users using (`user_id`)
                            WHERE `form_tracking_id`='{$app['form_tracking_id']}'
                            ORDER BY users.last_name, users.first_name ";
			$cos=$db->getAll($sql);
			if(count($cos) > 0){
				reset($cos);

				foreach($cos as $co){
					$oneline['name']= "$co[last_name], $co[first_name]";
					$oneline['delete'] = "<button type='button' $delbutton name='delete' value='delete' onClick='javascript: if(confirm(\"Really delete?\")) window.location=\"/tracking.php?deletecoresearcher&id=$app[form_tracking_id]&user_id=$co[user_id]\";'>Delete</button>";
					$co_researchers[]=$oneline;
				}
				//$tmpl->setAttribute('co_researchers_section','visibility','visible');
				$tmpl->addRows('coresearchers',$co_researchers) ;
			}
			//generate list for co-researcher addons
			$sql="SELECT * FROM `users`
                            LEFT JOIN users_disabled using (user_id)
                            WHERE
                            (`emp_type`='FACL' OR `emp_type`='MAN')
                            AND ISNULL(users_disabled.user_id)
                            ORDER BY last_name, first_name ";
			$users2=$db->getAll($sql);
			if(!$users2) print($db->ErrorMsg());
			$co_options='';
			foreach($users2 as $user2) $co_options.="<option value='$user2[user_id]' $selected>$user2[last_name], $user2[first_name]</option>\n";
			$app['co_options']=$co_options;


			//funding
			$sql="SELECT * FROM forms_tracking_budgets WHERE
                            form_tracking_id=$app[form_tracking_id]";
			$budget=$db->getRow($sql);

			if(!is_array($budget) || sizeof($budget)==0){
				//create a new entry
				$sql2="INSERT INTO `research`.`forms_tracking_budgets`
                         (`forms_tracking_budget_id`, `form_tracking_id`, `c_stipends`, `i_stipends`, `c_persons`, `i_persons`, `c_assist`, `i_assist`, `c_ustudents`, `i_ustudents`, `c_gstudents`, `i_gstudents`, `c_ras`, `i_ras`, `c_others`, `i_others`, `others_text`, `c_benefits`, `i_benefits`, `c_equipment`, `i_equipment`, `c_supplies`, `i_supplies`, `c_travel`, `i_travel`, `c_comp`, `i_comp`, `c_oh`, `i_oh`, `c_space`, `i_space`)
                         VALUES (NULL, '{$_REQUEST['form_tracking_id']}', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', ' ', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0')";
				$result=$db->Execute($sql2);

				$sql="SELECT * FROM forms_tracking_budgets WHERE
                        form_tracking_id=$_REQUEST[id]";
				$budget=$db->getRow($sql);
			}
			$tmpl->addVars('edit',$budget);

			$app['funding_confirmed']= ($app['funding_confirmed']) ? 'checked' : '';
			$app['equipment_flag']= ($app['equipment_flag']) ? 'checked' : '';
			$app['space_flag']= ($app['space_flag']) ? 'checked' : '';
			$app['commitments_flag']= ($app['commitments_flag']) ? 'checked' : '';
			$app['employ_flag']= ($app['employ_flag']) ? 'checked' : '';
			$app['emp_students']= ($app['emp_students']) ? 'checked' : '';
			$app['emp_ras']= ($app['emp_ras']) ? 'checked' : '';
			$app['emp_consultants']= ($app['emp_consultants']) ? 'checked' : '';
			$app['loc_mru']= ($app['loc_mru']) ? 'checked' : '';
			$app['loc_canada']= ($app['loc_canada']) ? 'checked' : '';
			$app['loc_international']= ($app['loc_international']) ? 'checked' : '';
			$app['human_b']= ($app['human_b']) ? 'checked' : '';
			$app['human_h']= ($app['human_h']) ? 'checked' : '';
			$app['biohaz']= ($app['biohaz']) ? 'checked' : '';
			$app['animal']= ($app['animal']) ? 'checked' : '';
			$app['dean_sig']= ($app['dean_sig']) ? 'checked' : '';
			$app['ors_sig']= ($app['ors_sig']) ? 'checked' : '';
			$app['iagree']= ($app['iagree']) ? 'checked' : '';

			switch($app['trackoptions']){
			case 'a': $app['sel_a']='selected'; break;
			case 'b': $app['sel_b']='selected'; break;
			case 'c': $app['sel_c']='selected'; break;
			}

			switch($app['status']){
			case '1': $app['sel_1']='selected'; break;
			case '2': $app['sel_2']='selected'; break;
			case '3': $app['sel_3']='selected'; break;
			}


			//COIs
			$sql="SELECT * FROM `forms_coi` 
                 LEFT JOIN `users` using (`user_id`)
                 WHERE `form_tracking_id`= '{$app['form_tracking_id']}'
              ";
            $cois=$db->getAll($sql);
            $coi_list=array();
            if($cois) foreach($cois as $key=>$coi){
            	if($coi['user_id']==$app['pi_id'] || ($coi['user_id']==$app['user_id'] && $app['pi'])) $cois[$key]['pi']=" (PI)";
            	if($coi['coi_none']) $cois[$key]['decl']='None';
            	else {
            		if($coi['coi01']) $cois[$key]['decl'].='Interest in a research, business, contract or transaction<br>';
            		if($coi['coi02']) $cois[$key]['decl'].='Influencing purchase of equipment, materials or services<br>';
            		if($coi['coi03']) $cois[$key]['decl'].='Acceptance of gifts, benefits or financial favours<br>';
            		if($coi['coi04']) $cois[$key]['decl'].='Use of information<br>';
            		if($coi['coi05']) $cois[$key]['decl'].='Use of students, university personnel, resources or assets<br>';
            		if($coi['coi06']) $cois[$key]['decl'].='Involvement in personnel decisions<br>';
            		if($coi['coi07']) $cois[$key]['decl'].='Evaluation of academic work<br>';
            		if($coi['coi08']) $cois[$key]['decl'].='Academic program decisions<br>';
            		if($coi['coi09']) $cois[$key]['decl'].='Favouring outside interests for personal gain<br>';
            		if($coi['coi10']) $cois[$key]['decl'].='Relationship<br>';
            		if($coi['coi11']) $cois[$key]['decl'].='Undertaking of outside activity<br>';
            		if($coi['coi_other']) $cois[$key]['decl'].='Other<br>'; 
            		if($cois[$key]['decl']=='') $cois[$key]['decl']='Empty declaration list (not a None declaration)<br>';
            		if($coi['relationship']!='') $cois[$key]['relationship']="<tr><td colspan='2'><b>Describe the Relationship:</b><br>$coi[relationship]</td></tr>";
            		if($coi['situation']!='') $cois[$key]['situation']="<tr><td colspan='2'><b>Describe the Situation:</b><br>$coi[situation]</td></tr>";
            	}//else
            	$tmpl->setAttribute('coi','visibility','visible');
            	$tmpl->addRows('coi',$cois);
            }//if cois
            else $tmpl->setAttribute('nocoi','visibility','visible');

			$tmpl->addVars('edit',$app);
			if(isset($success)) $tmpl->addVar('edit','success',$success);
		}
	}
	break;

}

if(isset($success)) $tmpl->addVar('page','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');

// Determine what files are in this given directory
function loadFiles($dir) {
    $files = array();

    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if($file != '.' && $file != '..') {
                    $size = Size($dir . '/' . $file);
                    array_push($files, array('name'=>$file, 'urlfilename'=>urlencode($file), 'size'=>$size));
                }
            }
            closedir($dh);
        }
    }
    return $files;
}

// function for outputting the size of a file
function Size($path)
{
    $bytes = sprintf('%u', filesize($path));

    if ($bytes > 0)
    {
        $unit = intval(log($bytes, 1024));
        $units = array('B', 'KB', 'MB', 'GB');

        if (array_key_exists($unit, $units) === true)
        {
            return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
        }
    }

    return $bytes;
}

/**
 * Delete a file associated with the tracking form
 *
 * @param $filePath - the path to the file
 */
function deleteFile($filePath) {
    $filePath = realpath($filePath);
    if(is_readable($filePath)){
        unlink($filePath);
    }
}

//Determine if valid date formats - nothing fancy
function check8601date($date){
	//if there is a space then it has time too
	$duo=explode(' ',$date);
	if(count($duo)==2) {$date=$duo[0]; $time=$duo[1];}
	elseif(count($duo)!=1) { return false;}
	$set=explode( '-', $date);
	if(count($set)!=3) return false;
	if(checkdate($set[1],$set[2],$set[0]) ===false) return false;
	return true;
}
?>