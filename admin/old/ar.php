<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//$filepath = "/home/html_root/htdocs/";
$filepath = "../";
$template = new Template;

include("html/header.html");

$success='';
global $logfile;

//$filepath = "/opt/lampp/htdocs/";
$todays_date=mktime();

if(isset($_REQUEST['restore'])) {
    if(isset($_REQUEST['restore_id'])){
        //Swap current fields with last archive
        $sql="SELECT * FROM ar_profiles_archive WHERE archive_id=$_REQUEST[restore_id]";
        $last_archive=$db->getRow($sql);
        if(is_array($last_archive)){
        	$sql="SELECT * FROM ar_profiles WHERE user_id=$last_archive[user_id]";

            $report=$db->getRow($sql);
            if(is_array($report)){

                /*$temp=$report;
                $report['short_profile']=$last_archive['short_profile'];
                $report['teaching_philosophy'] = $last_archive['teaching_philosophy'];
                $report['top_3_achievements'] = $last_archive['top_3_achievements'];
                $report['teaching_goals'] = $last_archive['teaching_goals'];
                $report['teaching_goals_lastyear'] = $last_archive['teaching_goals_lastyear'];
                $report['activities'] = $last_archive['activities'];
                $report['scholarship_achievements'] = $last_archive['scholarship_achievements'];
                $report['scholarship_goals'] = $last_archive['scholarship_goals'];
                $report['scholarship_goals_lastyear'] = $last_archive['scholarship_goals_lastyear'];
                $report['service_goals'] = $last_archive['service_goals'];
                $report['service_goals_lastyear'] = $last_archive['service_goals_lastyear'];
                $report['service_achievements'] = $last_archive['service_achievements'];
                $report['chair_duties_flag'] = $last_archive['chair_duties_flag'];
                $report['service_chair_goals'] = $last_archive['service_chair_goals'];
                $report['service_chair_achievements'] = $last_archive['service_chair_achievements'];
                $report['service_chair_other'] = $last_archive['service_chair_other'];
                $report['degree'] = $last_archive['degree'];
                */

                $sql="UPDATE ar_profiles_archive SET
                      `short_profile`= '" . mysql_escape_string($report['short_profile'])."',
                      `teaching_philosophy`=  '" . mysql_escape_string($report['teaching_philosophy'])."',
                      `top_3_achievements`= '" . mysql_escape_string($report['top_3_achievements'])."',
                      `teaching_goals`= '" . mysql_escape_string($report['teaching_goals'])."',
                      `teaching_goals_lastyear`= '" . mysql_escape_string($report['teaching_goals_lastyear'])."',
                      `activities`= '" . mysql_escape_string($report['activities'])."',
                      `scholarship_achievements`= '" . mysql_escape_string($report['scholarship_achievements'])."',
                      `scholarship_goals`= '" . mysql_escape_string($report['scholarship_goals'])."',
                      `scholarship_goals_lastyear`= '" . mysql_escape_string($report['scholarship_goals_lastyear'])."',
                      `service_goals`= '" . mysql_escape_string($report['service_goals'])."',
                      `service_goals_lastyear`= '" . mysql_escape_string($report['service_goals_lastyear'])."',
                      `service_achievements`= '" . mysql_escape_string($report['service_achievements'])."',
                      `chair_duties_flag`= '" . $report['chair_duties_flag']."',
                      `service_chair_goals`= '" . mysql_escape_string($report['service_chair_goals'])."',
                      `service_chair_achievements`= '" . mysql_escape_string($report['service_chair_achievements'])."',
                      `service_chair_other`= '" . mysql_escape_string($report['service_chair_other'])."',
                      `degree`= '" . mysql_escape_string($report['degree'])."'
                       WHERE archive_id=$_REQUEST[restore_id]";

                if($db->Execute($sql) === false) $success.="Switch did not work: $result";
                else {
                	$sql="UPDATE ar_profiles SET
                      `short_profile`= '" . mysql_escape_string($last_archive['short_profile'])."',
                      `teaching_philosophy`=  '" . mysql_escape_string($last_archive['teaching_philosophy'])."',
                      `top_3_achievements`= '" . mysql_escape_string($last_archive['top_3_achievements'])."',
                      `teaching_goals`= '" . mysql_escape_string($last_archive['teaching_goals'])."',
                      `teaching_goals_lastyear`= '" . mysql_escape_string($last_archive['teaching_goals_lastyear'])."',
                      `activities`= '" . mysql_escape_string($last_archive['activities'])."',
                      `scholarship_achievements`= '" . mysql_escape_string($last_archive['scholarship_achievements'])."',
                      `scholarship_goals`= '" . mysql_escape_string($last_archive['scholarship_goals'])."',
                      `scholarship_goals_lastyear`= '" . mysql_escape_string($last_archive['scholarship_goals_lastyear'])."',
                      `service_goals`= '" . mysql_escape_string($last_archive['service_goals'])."',
                      `service_goals_lastyear`= '" . mysql_escape_string($last_archive['service_goals_lastyear'])."',
                      `service_achievements`= '" . mysql_escape_string($last_archive['service_achievements'])."',
                      `chair_duties_flag`= '" . $last_archive['chair_duties_flag']."',
                      `service_chair_goals`= '" . mysql_escape_string($last_archive['service_chair_goals'])."',
                      `service_chair_achievements`= '" . mysql_escape_string($last_archive['service_chair_achievements'])."',
                      `service_chair_other`= '" . mysql_escape_string($last_archive['service_chair_other'])."',
                      `degree`= '" . mysql_escape_string($last_archive['degree'])."'
                       WHERE user_id=$last_archive[user_id]";

                    if($db->Execute($sql) === false) $success.="Switch did not work: $result";
                    else {
                        //Uncheck everything
                        $sql="SELECT * FROM cas_cv_items WHERE user_id=$last_archive[user_id] AND report_flag=1";
                        $cv_items_checked=$db->getAll($sql);
                        if(is_array($cv_items_checked)) {
                            foreach($cv_items_checked as $cv_item){
                            	$sql="UPDATE cas_cv_items SET `report_flag`=0 WHERE cv_item_id=$cv_item[cv_item_id]";
                                if($db->Execute($sql) === false)  echo "Error on cv_item $cv_item[cv_item_id] for user: $user_id\n";
                            }
                            $success.= count($cv_items_checked) . ' items unchecked. ';
                        }
                        //Load archived checked items
                        $sql="SELECT * FROM cv_items_check_archive WHERE archive_id=$_REQUEST[restore_id]";
                        $archive=$db->getAll($sql);
                        if(is_array($archive)){
                               foreach($archive as $oneitem){
                               		$sql="UPDATE cas_cv_items SET `report_flag`=1 WHERE cv_item_id=$oneitem[cv_item_id]";
                                  if($db->Execute($sql)) {
                                  		$sql="DELETE FROM cv_items_check_archive WHERE cv_item_id=$oneitem[cv_item_id]";
                                  		if($db->Execute($sql) === false) echo "Error deleting archive check item";
                                  }
                               }
                               $success.= count($archive) . ' items loaded. ';
                        }//isarray archive
                        //Save checked ones in archive
                        if(is_array($cv_items_checked)) {
                            foreach($cv_items_checked as $cv_item){
                            	$sql="INSERT INTO cv_items_check_archive (`cv_item_id`,`acadyear`) VALUES($_REQUEST[restore_id],'$cv_item[cv_item_id]')";
                                if($db->Execute($sql) === false)  echo "Error saving checked item in archive;";
                            }
                        }

                    } // else - 2nd update worked





                } // else - update did work
            }// isarray report
        } // isarray lastarchive



    }
}

if(isset($_REQUEST['archive'])){
	 if(isset($_REQUEST['user_id'])) {
         if($_REQUEST['user_id']!=0){
             if (!$logfile = fopen("{$configInfo['file_root']}/mail_log.txt","a+")) die("Mail Log Is Not Writeable");
             $date=date($iso8601,$todays_date);
             fwrite($logfile,"-----------------\nDate: $date\n\n");
            $result=rollOver($_REQUEST['user_id']);
            if($result>=0) {
                $success.="Executed";

                fwrite($logfile,"Rolled over AR and updated $result items for user $_REQUEST[user_id]\n");
                fwrite($logfile,"\n");

            }
            if($result==-2) $success.='User has not changed so not archived';
            if($result==-3) $success.='No report on file for user';
            if($result==-1) $success.='Error encountered. See log';

            fclose($logfile);
         }
	 }
     $_REQUEST['section']='archive';
}

if(isset($_REQUEST['viewone'])){
       if(isset($_REQUEST['user_id'])) {
         if($_REQUEST['user_id']!=0){
            $_REQUEST['section']='viewone';
         }
     }

}

if(isset($_REQUEST['archiveall'])){
    //if(authorizeUsername($_REQUEST['username'])) {
	    $users=mysqlFetchRows('users',"emp_type='MAN' OR emp_type='FACL' order by last_name,first_name ");
        $count1=$count2=$count3=$count0=0;
        if (!$logfile = fopen("{$configInfo[file_root]}/mail_log.txt","a+")) die("Mail Log Is Not Writeable");
        $date=date($iso8601,$todays_date);
        fwrite($logfile,"-----------------\nDate: $date\n\n");
        foreach($users as $user){
            $result=rollOver($user['user_id']);
            if($result>=0){$count1++; fwrite($logfile,"Rolled over AR and updated $result items for user $user[last_name], $user[first_name] ($user[user_id])\n");}
            if($result==-2)$count2++;
            if($result==-3)$count3++;
            if($result==-1)$count0++;

        }
        fwrite($logfile,"\n");
        fclose($logfile);
        $success="$count1 archived, $count2 not changed, $count3 no report, $count0 errors (see logfile)";
    //}
    //else $success = "Not Authorized";
}


if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";

    switch($_REQUEST['section']){
	    case "status":
            //Table of each faculty and dept with # of faculty reporting, # in progress (show report and non-empty fields), # submitted, # approved.
            //Possibly drill down for details
            $output='<table border=1><tr><td>&nbsp;</td><td><b># of Faculty</b></td><td><b>In Progress</b></td><td><b>Submitted</b></td><td><b>Approved</b></td><td><b>Inactive</b></td></tr>';
            $sql="SELECT * FROM divisions ORDER BY name";
            $faculties=$db->getAll($sql);
          	//Extra one for NO Faculty
            $faculties[]=array('division_id'=>0, 'name'=>'None','dean'=>0);
            //init totals
            $facultyt=$inprogresst=$submittedt=$approvedt=0;
            foreach($faculties as $faculty){
            	$sql="SELECT * FROM departments WHERE division_id=$faculty[division_id] ORDER BY name";
            	$depts=$db->getAll($sql);
                if(is_array($depts)){
                    $output.="<tr><td><b>$faculty[name]</b></td></tr>";
                    foreach($depts as $dept){

                        $submitted=0;
                        $sql="SELECT users.* FROM users

                        	LEFT JOIN users_disabled on users.user_id=users_disabled.user_id
                        	WHERE users.emp_type='FACL'
                        	AND users.department_id=$dept[department_id]
                        	AND users_disabled.user_id IS NULL";
                        $users=$db->getALl($sql);
                        $faculty=count($users);
                        //var_dump($users);
                        $inprogress=0;
                        $approved=0;
                        $inactive='';
                        $inprogressnames='';
                        if(is_array($users)) foreach($users as $user){

                        	//print_r($user);
                            $active=FALSE;
                            $sql="SELECT * FROM cas_cv_items WHERE user_id=$user[user_id] AND report_flag=1";
                            $items=$db->getAll($sql);
                            if(count($items)> 0) $active=TRUE;



                            $sql="SELECT * FROM ar_reports
                            	left join users on ar_reports.user_id=users.user_id
                            	WHERE users.department_id=$dept[department_id]
                            	AND year(submitted_date) = year(curdate())
                            	AND ar_reports.user_id=$user[user_id]";
                            $report=$db->getRow($sql);
                            if($report){
                            	if($report['top_3_achievements']!='' ||
                                $report['teaching_goals']!='' ||
                                $report['activities']!='' ||
                                $report['scholarship_achievements']!='' ||
                                $report['scholarship_goals']!='' ||
                                $report['service_goals']!='' ||
                                $report['service_achievements']!='' ||
                                $report['service_chair_goals']!='' ||
                                $report['service_chair_achievements']!='' ||
                                $report['service_chair_other']!=''){

                                $active=TRUE;
                             }
                            	if($active==TRUE && $report['submitted_flag'] && !$report['approved_flag']) $submitted++;
                            	elseif($active==TRUE && !$report['submitted_flag']) {
                                    $inprogress++;
                                    $inprogressnames.="$user[first_name] $user[last_name]  ";
                                }
                            	elseif($active==TRUE && $report['submitted_flag'] && $report['approved_flag']) $approved++;
                            }
                            if(!$active) $inactive.="$user[first_name] $user[last_name]  ";

                        }


                        $output.="<tr><td>$dept[name]</td><td>$faculty</td><td><a title=\"$inprogressnames\" href='#'>$inprogress</a></td><td>$submitted</td><td>$approved</td><td>";
                        if($inactive!='') $output.="<a title=\"$inactive\" href='#'>List</a>";
                        $output.="</td></tr>";
                        $facultyt=$facultyt+$faculty;
                        $inprogresst=$inprogresst+$inprogress;
                        $submittedt=$submittedt+$submitted;
                        $approvedt=$approvedt+$approved;
                    }//each dept
                }//isarray depts
            }//each faculty
            $output.="<tr><td><b>TOTAL</b></td><td>$facultyt</td><td>$inprogresst</td><td>$submittedt</td><td>$approvedt</td></tr></table>";
            $today=getdate();
            $year=$today['year'];
            $hasharray = array('success'=>$success,'output'=>$output,'year'=>$year);
            $filename = 'templates/template-ar_status.html';

            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;

        break;


        case "user_status":
            $sql="SELECT CONCAT(users.last_name,', ',users.first_name) as fullname,users.username,users.user_id
            FROM users LEFT JOIN users_ext using (user_id)
            LEFT JOIN users_disabled using (user_id)
            WHERE ISNULL(users_disabled.user_id)
            AND emp_type='FACL'
            ORDER BY fullname
            ";
            $result=$db->getAll($sql);
            foreach($result as $key=>$fac){
                foreach(array('2010','2011','2012','2013','2014','2015') as $year){
                    $sql="SELECT count(*) as count FROM ar_reports WHERE user_id=$fac[user_id] AND YEAR(submitted_date)=$year";
                    $yrresult=$db->getRow($sql);
                    if($yrresult['count']>0) $result[$key][$year]=1;
                    else $result[$key][$year]=0;
                }
            }

            echo '<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">';
            echo '<table class="table table-striped"><tr><th>Full Name</th><th>Username</th><th>User ID</th><th>2010</th><th>2011</th><th>2012</th><th>2013</th><th>2014</th><th>2015</th><th>Total CV Items</th><th>Total CV Items in Current Report</th></tr>';
            foreach ($result as $user) {
                $totalCasCvItems = $db->getOne("SELECT COUNT(*) as `count` FROM cas_cv_items WHERE user_id = ?", array($user['user_id']));
                $totalCasCvItemsInReport = $db->getOne("SELECT COUNT(*) as `count` FROM cas_cv_items WHERE user_id = ? AND report_flag = 1", array($user['user_id']));

                echo '<tr>' .
                    '<td>' . $user['fullname'] . '</td>' .
                     '<td>' . $user['username'] . '</td>' .
                     '<td>' . $user['user_id'] . '</td>' .
                     '<td>' . $user['2010'] . '</td>' .
                     '<td>' . $user['2011'] . '</td>' .
                     '<td>' . $user['2012'] . '</td>' .
                     '<td>' . $user['2013'] . '</td>' .
                     '<td>' . $user['2014'] . '</td>' .
                     '<td>' . $user['2015'] . '</td>' .
                     '<td>' . $totalCasCvItems . '</td>' .
                     '<td>' . $totalCasCvItemsInReport . '</td>' .
                '</tr>';
            }
            echo '</table>';
            die();

        break;

        case "archive":
            $sql="SELECT * FROM users WHERE emp_type='MAN' OR emp_type='FACL' order by last_name,first_name ";
            $users=$db->getAll($sql);
            $user_options="";
            foreach($users as $user){
                $user_options.="<option value='$user[user_id]'>$user[last_name], $user[first_name]</option>\n";
            }
            $hasharray = array('success'=>$success,'user_options'=>$user_options);
            $filename = 'templates/template-ar_choose.html';

            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;

        break;


        case "viewone":
            $output='';

            $sql="SELECT * FROM ar_profiles WHERE user_id=$_REQUEST[user_id]";
            $report=$db->getRow($sql);

            $sql="SELECT * FROM users WHERE user_id=$_REQUEST[user_id]";
            $user=$db->getRow($sql);
            if(isset($report)){
            	$sql="SELECT * FROM ar_profiles_archive WHERE user_id=$_REQUEST[user_id] ORDER BY archive_id desc LIMIT 4";
                $lastarchive=$db->getAll($sql);
                if(is_array($lastarchive)) {
                    $numarchives=count($lastarchive);
                    $restore_id=$lastarchive[0]['archive_id'];
                }
                else {
                    $numarchives=0;
                    $restore_id=0;
                }

                //$output="<table border='1' cellpadding='3' style='border-collapse: collapse' bordercolor='#FFFFFF' cellspacing='1'>";
                $output.="<tr bgcolor='#000000'><td>&nbsp;</td><td><b style='color:#E1E1E1;font-size:10px'>Current Report</b></td>";
                for($x=0;$x<$numarchives;$x++){
                    $output.="<td><b style='color:#E1E1E1;font-size:10px'>Archive ". $lastarchive[$x]['archive_date'] . "</b></td>";
                }
                $output.="</tr>";

                $arfields=array('short_profile'=>'Short Profile',
                                'teaching_philosophy'=>'Teaching Philosophy',
                                'top_3_achievements'=>'Top 3 Achievements',
                                'teaching_goals'=>'Teaching Goals',
                                'teaching_goals_lastyear'=>'Last Year T Goals',
                                'activities'=>'Activities',
                                'scholarship_achievements'=>'Sch. Achievements',
                                'scholarship_goals'=>'Sch. Goals',
                                'scholarship_goals_lastyear'=>'Last Year',
                                'service_goals'=>'Service Goals',
                                'service_goals_lastyear'=>'Last Year',
                                'service_achievements'=>'Ser. Achievements',
                                'service_chair_goals'=>'Chair Goals',
                                'service_chair_achievements'=>'Chair Achievem.',
                                'service_chair_other'=>'Chair Other');
                foreach($arfields as $fname=>$ftitle){
                    $output.="<tr bgcolor='#CCCCCC'><td>$ftitle</td>";
                    $output.="<td>". $report[$fname]."</td>";
                    for($x=0;$x<$numarchives;$x++){
                        $output.="<td>". $lastarchive[$x][$fname]."</td>";
                    }
                    $output.='</tr>';
                }

                //$output.="</table>";
                $hasharray = array('success'=>$success,'output'=>$output,'restore_id'=>$restore_id);
                $filename = 'templates/template-ar_viewone.html';

                $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
                echo $parsed_html_file;

            }
        break;
	} //switch
} //isset $section
//-- Footer File
include("templates/template-footer.html");

function rollOver($user_id){
    //returns >=0 =success and number of items changed, -1=error, -2=already rolled over apparently, -3=no report on file to roll over

    global $logfile;
    global $db;
    $returnval=0;
    $success=FALSE;
    $sql="SELECT * FROM ar_profiles WHERE user_id=$user_id";
    $report=$db->getRow($sql);
    //print_r($report);
         if(isset($report)){
            $timestampInSeconds = $_SERVER['REQUEST_TIME'];
            //$report['archive_id']='NULL';
            $archive_date=date("Y-m-d H:i:s", $timestampInSeconds);

            //If all the fields are empty don't roll over.
            if (
            $report['top_3_achievements']=='' &&
            $report['teaching_goals']=='' &&
            $report['activities']=='' &&
            $report['scholarship_achievements']=='' &&
            $report['scholarship_goals']=='' &&
            $report['service_goals']=='' &&
            $report['service_achievements']=='' &&
            $report['service_chair_goals']=='' &&
            $report['service_chair_achievements']=='' &&
            $report['service_chair_other']=='') { $roll=FALSE;} else {$roll=TRUE;}


            if ($roll){
            	$sql="INSERT INTO ar_profiles_archive
            		(`archive_date`,
            		`user_id`,
            		`short_profile`,
            		`teaching_philosophy`,
            		`top_3_achievements`,
            		`teaching_goals`,
            		`teaching_goals_lastyear`,
            		`activities`,
            		`scholarship_achievements`,
            		`scholarship_goals`,
            		`scholarship_goals_lastyear`,
            		`service_goals`,
            		`service_goals_lastyear`,
            		`service_achievements`,
            		`chair_duties_flag`,
            		`service_chair_goals`,
            		`service_chair_achievements`,
            		`service_chair_other`,
            		`degree`,
            		`research_plans`)
            		VALUES('$archive_date',
                           $report[user_id],
                           '". mysql_escape_string($report['short_profile'])."',
                           '". mysql_escape_string($report['teaching_philosophy'])."',
                           '". mysql_escape_string($report['top_3_achievements'])."',
                           '". mysql_escape_string($report['teaching_goals'])."',
                           '". mysql_escape_string($report['teaching_goals_lastyear'])."',
                           '". mysql_escape_string($report['activities'])."',
                           '". mysql_escape_string($report['scholarship_achievements'])."',
                           '". mysql_escape_string($report['scholarship_goals'])."',
                           '". mysql_escape_string($report['scholarship_goals_lastyear'])."',
                           '". mysql_escape_string($report['service_goals'])."',
                           '". mysql_escape_string($report['service_goals_lastyear'])."',
                           '". mysql_escape_string($report['service_achievements'])."',
                           '". $report['chair_duties_flag']."',
                           '". mysql_escape_string($report['service_chair_goals'])."',
                           '". mysql_escape_string($report['service_chair_achievements'])."',
                           '". mysql_escape_string($report['service_chair_other'])."',
                           '". mysql_escape_string($report['degree'])."',
                           '". mysql_escape_string($report['research_plans'])."')";


                if($db->Execute($sql) === false) {
                    fwrite($logfile,"Error inserting into archive for user: $report[user_id]\n");
                    $returnval=-1;
                    $roll=false; // abort following operations
                }
                else {
                    $returnval=0;
                    $archive_id=mysql_insert_id();
                }

            } // if roll

            if($roll) {
                //Roll-over to next year

                //Clean out report
                $report['short_profile']=mysql_escape_string($report['short_profile']);
                $report['teaching_philosophy']=mysql_escape_string($report['teaching_philosophy']);
                $report['top_3_achievements']='';
                $report['teaching_goals_lastyear']= mysql_escape_string($report['teaching_goals']);
                $report['teaching_goals']='';
                $report['activities']='';
                $report['scholarship_achievements']='';
                $report['scholarship_goals_lastyear']=mysql_escape_string($report['scholarship_goals']);
                $report['scholarship_goals']='';
                $report['service_goals_lastyear']=mysql_escape_string($report['service_goals']);
                $report['service_goals']='';
                $report['service_achievements']='';
                $report['service_chair_goals']='';
                $report['service_chair_achievements']='';
                $report['service_chair_other']='';
                $report['degree']= mysql_escape_string($report['degree']);

                $sql="UPDATE ar_profiles SET
                `short_profile`='$report[short_profile]',
                `teaching_philosophy`='$report[teaching_philosophy]',
                `top_3_achievements`='',
                `teaching_goals_lastyear`='$report[teaching_goals_lastyear]',
                `teaching_goals`='',
                `activities`='',
                `scholarship_achievements`='',
                `scholarship_goals_lastyear`='$report[scholarship_goals_lastyear]',
                `scholarship_goals`='',
                `service_goals_lastyear`='$report[service_goals_lastyear]',
                `service_goals`='',
                `service_achievements`='',
                `service_chair_goals`='',
                `service_chair_achievements`='',
                `service_chair_other`='',
                `degree`='$report[degree]'
                WHERE user_id=$report[user_id]";


                if($db->Execute($sql) === false) {
                    fwrite($logfile,"Error updating report for user: $report[user_id]; $result\n");
                    $returnval=-1;
                    $roll=false;
                }
                else $returnval=0;
            } // if roll

             // if report is submitted but unapproved, then approve it - 11-02-2012
             if($roll) {
                 if($report["submitted_flag"] = 1) {
                     $sql = "UPDATE ar_reports SET
                             approved_flag = 1
                             WHERE user_id = $report[user_id] AND year(submitted_date) = year(curdate())";

                     if($db->Execute($sql) === false) {
                         fwrite($logfile,"Error updating report for user: $report[user_id] : Unable to set approved flag;\n");
                         $returnval=-1;
                         $roll=false;
                     }
                     else $returnval=0;
                 }
             } // if roll

            if($roll) {
                //Uncheck all the items in the current report.
                //Also archive their IDs for future restore
                $sql="SELECT * FROM cas_cv_items WHERE user_id=$user_id AND report_flag=1";
                $cv_items_checked=$db->getAll($sql);
                //get the proper academic year
                // logic is: if it's June to Dec Acad Year is lastyear-thisyear else its 2yrs ago last year
                $now=getdate();
                if($now['mon'] > 6) $acadyear=$now['year'] - 1 . '-' . $now['year'];
                else  $acadyear=$now['year'] - 2 . '-' . $now['year'] - 1;
                if(is_array($cv_items_checked)) {
                    foreach($cv_items_checked as $cv_item){
                    	$sql="INSERT INTO cv_items_check_archive VALUES($archive_id,$cv_item[cv_item_id],'$acadyear')";
                        if($db->Execute($sql) === false) echo "Error inserting in archive: $result1";

                        $sql="UPDATE cas_cv_items SET report_flag=0 WHERE cv_item_id=$cv_item[cv_item_id]";
                        if($db->Execute($sql) === false) echo "Error unchecking: $result";
                    }
                    $count=count($cv_items_checked);
                    $returnval=$count;
                }
                else $returnval=0;

            } //if roll

            if($roll==false) $returnval=-2;


         } // if there is a report
         else $returnval=-3;

         return($returnval);
}
