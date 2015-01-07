<?php
//error_reporting(E_ALL);
include_once("includes/config.inc.php");
include_once("includes/functions-required.php");

$dirloc="/var/www/orsadmin_htdocs/admin/documents/shared/irgf_admin";

$hdr=loadPage("header",'Header');
$tmpl=loadPage("irgf", 'IRGF Form Management');


$success='';

if(isset($_REQUEST['printpdf']) && isset($_REQUEST['form_tracking_id'])){
		include_once("includes/print_tracking.php");
        $sql="SELECT form_tracking_id,user_id FROM forms_tracking WHERE form_tracking_id=$_REQUEST[form_tracking_id]";
        $form=$db->getRow($sql);
        if(count($form)>0) printPDF($_REQUEST['form_tracking_id'],$form['user_id'],$db);
}

if(isset($_REQUEST['printpdf']) && isset($_REQUEST['form_irgf_id'])){
		include_once("includes/print_irgf.php");
        $sql="SELECT form_irgf_id,user_id FROM forms_irgf WHERE form_irgf_id=$_REQUEST[form_irgf_id]";
        $form=$db->getRow($sql);
        if(count($form)>0) printIRGF($_REQUEST['form_irgf_id'],$form['user_id'],$db);
}

if(isset($_REQUEST['savetodir']) && isset($_REQUEST['form_irgf_id'])){


        $sql="SELECT * FROM forms_irgf
        		LEFT JOIN forms_tracking as ft ON(ft.form_tracking_id = forms_irgf.form_tracking_id)
        		WHERE form_irgf_id=$_REQUEST[form_irgf_id]";
        $app=$db->getRow($sql);
        if(count($app)>0) {
        	
        	//Can I see the shared dir? If not, bail
        	if(!file_exists("$dirloc")) exit();
        	
        	//Does the main dir exist?
        	$year=GetSchoolYear($todays_date);
        	if(!file_exists("$dirloc/$year")){
        		if (!mkdir("$dirloc/$year")) {
    				die('Failed to create folder...');
				}
				chmod("$dirloc/$year",0777);
        	}
        	
        	//Does the user subdir exist?
        	if($app['pi']) $app['pi_id']=$app['user_id']; //push user_id into pi_id for now!!!!!!!!
        	if($app['pi_id']!=0){
				$sql="SELECT user_id,last_name,first_name FROM users WHERE user_id=$app[pi_id]";
				$user=$db->getRow($sql);
				if($user) $app['pi_name']="$user[last_name], $user[first_name]";
			}
			else{
                if(isset($app['last_name'])) {
				    $app['pi_name']="$app[last_name], $app[first_name]";
                }

			}
        	//echo($app['pi_id']);
        	
        	if(!file_exists("$dirloc/$year/$app[pi_name]")){
        		//echo("Making dir $dirloc/$year/$app[pi_id]");
        		if (!mkdir("$dirloc/$year/{$app[pi_name]}")) {
    				die('Failed to create folder...');
				}
				chmod("$dirloc/$year/{$app[pi_name]}",0777);
        	}
        	chmod("$dirloc/$year/{$app[pi_name]}",0777);
        	
      
	
        	
        	//Save tracking form
			include_once("includes/print_tracking.php");
        	if($app['form_tracking_id']!=0) {
        		printPDF($app['form_tracking_id'],$app['user_id'],$db,'F',"$dirloc/$year/{$app[pi_name]}");
        		//echo("printPDF($form[form_tracking_id],$form[user_id],$db,'F',$dirloc/$year/{$app[pi_id]}");
        	}
          	
        	//Save IRGF form
			include_once("includes/print_irgf.php");
        	if($app['form_irgf_id']!=0) {
        		printIRGF($app['form_irgf_id'],$app['user_id'],$db,'F',"$dirloc/$year/{$app[pi_name]}");	
        	}
        	
        	
        	
        	
        	//Copy the main attachment
        	if($app['filename']!='') {
                //$filename2=sanitize($app['filename']);
        		if(!copy("$configInfo[file_root]$configInfo[irgf_docs]/$app[user_id]/$app[filename]","$dirloc/$year/{$app[pi_name]}/Attachment-$app[filename]")) echo("Error copying $configInfo[irgf_docs]/$app[user_id]/$app[filename] TO $dirloc/$year/{$app[pi_name]}/Attachment-$app[filename]");
        	}
        	

        	
        	//echo("Copied the form <br>");
        	error_reporting(E_ALL);
        	
			require_once("includes/pdf.php");
			
			//We should at least have a user Cv
			//echo("<pre>");
			//print_r($app);
			if($app['cv']<>0){ 
				if($app['pi']) $pi=$app['user_id']; else $pi=$app['pi_id'];
				//echo ("$pi <br>");
				if($pi != 0) generateCV($pi,"mycv$app[cv]",'apa',"$dirloc/$year/{$app[pi_name]}/CV-{$app[pi_name]}");
				
				//$apps[$key]['cv_pdf']="<button class='pdf' type='button' onClick='javascript: window.location=\"cv_review_print.php?generate=mycv$app[cv]&report_user_id=$pi&style=apa\";'><img src='/admin/images/icon-sm-pdf2.gif'></button>";
			}
			
			//Pull up any co-researchers
			$sql="SELECT * FROM forms_tracking_coresearchers WHERE form_tracking_id=$app[form_tracking_id]";
			$cos=$db->getAll($sql);
			if(count($cos)> 0) foreach($cos as $co){
				$sql="SELECT user_id,last_name,first_name FROM users WHERE user_id=$co[user_id]";
				$user=$db->getRow($sql);
				if($user) $coname="$user[last_name], $user[first_name]";

				if($co['cv']<>0) generateCV($co['user_id'],"mycv$co[cv]",'apa',"$dirloc/$year/{$app[pi_name]}/CV-$coname");
				
			}
			
			//cv_review_print.php?generate=mycv$app[cv]&report_user_id=$pi&style=apa\
			
			//Now save the fact that we did this already
			$sql="UPDATE forms_irgf SET saved = '1' WHERE form_irgf_id=$_REQUEST[form_irgf_id]";
			$result=$db->Execute($sql); 
        
        }
}

/*

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

*/

function sanitize($string = '', $is_filename = FALSE)
{
 // Replace all weird characters with dashes
 $string = preg_replace('/[^\w\-'. ($is_filename ? '~_\.' : ''). ']+/u', '-', $string);

 // Only allow one dash separator at a time (and make string lowercase)
 return mb_strtolower(preg_replace('/--+/u', '-', $string), 'UTF-8');
}


if(!isset($_REQUEST['section'])) $_REQUEST['section']="view";

switch($_REQUEST['section']){

case 'view':

	$tmpl->setAttribute('list','visibility','visible');
	if(isset($_REQUEST['sort'])) $sort=$_REQUEST['sort'];
	else $sort='modified';

	if(isset($_REQUEST['dir'])) {
		if($_REQUEST['dir']=='ASC') $dir='ASC';
		else $dir='DESC';
	}
	else $dir='ASC'; //default


			
	$sql="	SELECT fi.saved, fi.user_id,fi.form_tracking_id,fi.form_irgf_id,fi.created,fi.modified,fi.which_fund, fi.reviewer_id, fi.cv, fi.filename, fi.status, fi.dean_sig, ft.pi,ft.pi_id,ft.equipment_flag,ft.space_flag,ft.commitments_flag, CONCAT(u1.last_name,u1.first_name) AS owner FROM forms_irgf as fi
         			LEFT JOIN users as u1 ON(u1.user_id=fi.user_id)
         			
         			LEFT JOIN forms_tracking as ft ON(ft.form_tracking_id = fi.form_tracking_id)
         			WHERE 1 ORDER BY $sort $dir";

	$apps=$db->getAll($sql);
	if(count($apps)>0){
		foreach($apps as $key=>$app){

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
			//Reviewer
			if($app['reviewer_id']!=0){
				$sql="SELECT user_id,last_name,first_name FROM users WHERE user_id=$app[reviewer_id]";
				$user=$db->getRow($sql);
				if($user) $apps[$key]['reviewer']="$user[last_name], $user[first_name]";
			}
			else $apps[$key]['reviewer']='';
			
			$apps[$key]['linecolour'] = ($apps[$key]['status']==0) ? "#CCCCFF" : "#FFCCCC";

            $apps[$key]['modified'] = date('Y-m-d', strtotime($app['modified']));

			//if(strlen($app['tracking_name'])>40) $apps[$key]['tracking_name']=substr($app['tracking_name'],0,40) . '...';
			
			if($app['form_irgf_id'] > 0) $apps[$key]['irgf_pdf']="<button class='pdf' type='button' onClick='javascript: window.location=\"irgf.php?printpdf&form_irgf_id=$app[form_irgf_id]\";'><img src='/images/icon-sm-pdf2.gif'></button>";

			if($app['form_tracking_id'] > 0) $apps[$key]['tracking_pdf']="<button class='pdf' type='button' onClick='javascript: window.location=\"irgf.php?printpdf&form_tracking_id=$app[form_tracking_id]\";'><img src='/images/icon-sm-pdf2.gif'></button>";

			if($app['filename']!='') $apps[$key]['attach_pdf']="<button class='pdf' type='button' onClick='javascript: window.location=\"$configInfo[irgf_docs]/$app[user_id]/$app[filename]\";'><img src='/images/icon-sm-pdf2.gif'></button>";
			
			//We should at least have a user CV
			$apps[$key]['cv_pdf']='';
			if($app['cv']<>0){ 
				if($app['pi']) $pi=$app['user_id']; else $pi=$app['pi_id'];
				
				if($pi != 0) $apps[$key]['cv_pdf']="<button class='pdf' type='button' onClick='javascript: window.location=\"cv_review_print.php?generate=mycv$app[cv]&report_user_id=$pi&style=apa\";'><img src='/images/icon-sm-pdf2.gif'></button>";
			}
			
			//Pull up any co-researchers
			$sql="SELECT * FROM forms_tracking_coresearchers WHERE form_tracking_id=$app[form_tracking_id]";
			$cos=$db->getAll($sql);
			if(count($cos)> 0) foreach($cos as $co){
				if($co['cv']<>0) $apps[$key]['cv_pdf'].="<button class='pdf' type='button' onClick='javascript: window.location=\"cv_review_print.php?generate=mycv$co[cv]&report_user_id=$co[user_id]&style=apa\";'><img src='/images/icon-sm-pdf2.gif'></button>";
			}
			
			//The save button - puts all files into the relevant directory. 
			//Unfortunately it requires doing everything again, but such is life.
			if($app['saved']) {$buttoncolour='#FFCCCC'; $text='Re-Save';} else {$buttoncolour='#CCFFCC';$text='Save';}
			$apps[$key]['file_button']="<button style='background-color:$buttoncolour' onClick='javascript: window.location=\"irgf.php?savetodir&form_irgf_id=$app[form_irgf_id]&sort=$sort&dir=$dir\";'>$text</button>";
			
			
		}//foreach
		if($dir=='ASC')$dir='DESC';
		else $dir='ASC';
		$tmpl->addVar('list','dir',$dir);
		$tmpl->addRows('mainlist',$apps);
		if(isset($success)) $tmpl->addVar('list','success',$success);
	}//if count>0


	break;

}//switch


if(isset($success)) $tmpl->addVar('page','success',$success);
$hdr->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('page');

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