
<?php
  /**
  * Routines to parse the forms_approvals table and send the appropriate notifications. 
  * Intended to be run once per day
  * Builds a digest-type email for each approver to minimize traffic.
  * 
  * ToDo: Need to extract some logic for an immediate email routine back in global.inc
  */
  
  /***********************************
* INCLUDES
************************************/


require_once("../includes/global.inc.php");

$errors=array();
//Load all distinct active form numbers (split for simplicity)
$sql="SELECT DISTINCT form_table,form_id FROM forms_approvals WHERE
        (type='sign' AND viewed IS NULL AND signed IS NULL) OR
        (type='sign' AND signed IS NULL AND (DATE_ADD(`entered`,INTERVAL 1 WEEK)) < CURDATE() )
        GROUP BY form_table,form_id";

$approvals=$db->getAll($sql);
//echo("<pre>")      ;
//var_dump($approvals);
//echo("</pre>") ;

//Load and process each form separately
//First the signatures
$continue=TRUE;

foreach($approvals as $approval){
    $sql="SELECT * FROM forms_approvals WHERE
        form_table='$approval[form_table]' AND form_id='$approval[form_id]' AND
        ((type='sign' AND viewed IS NULL AND signed IS NULL) OR
        (type='sign' AND signed IS NULL AND (DATE_ADD(`entered`,INTERVAL 1 WEEK)) < CURDATE() ))
        ORDER BY queue_order";
    $formset=$db->getAll($sql);
    if(count($formset)>0) foreach($formset as $form) {
        //echo("<pre>")      ;
        //var_dump($formset);
        //echo("</pre>") ;
        //So now I have a set of potential signatures. 
        //If two have the same queue_order then they both go out together. Otherwise send only the first
        if($continue){
            if($form['form_table'] == 'forms_tracking'){
                $sql="SELECT * FROM forms_tracking WHERE form_tracking_id=$form[form_id]";
                $form_data=$db->getRow($sql);
                if(!$form_data) {$continue=TRUE;  $errors[]="Error loading from forms_table, id $form[form_id]: " . $db->ErrorMsg(); }
                //the tracking form could be done for someone.
                if(isset($form_data['pi'])) $user_id=$form_data['user_id'];
                else $user_id=$form_data['pi_id'];
                $sql="      SELECT 
                            CONCAT(u.first_name,' ',u.last_name) as pi_name,
                            u.first_name as pi_firstname,
                            CONCAT(u2.first_name,' ',u2.last_name) as chair_name,
                            u2.first_name as chair_firstname,
                            u2.user_id as chair_id,
                            CONCAT(u3.first_name,' ',u3.last_name) as director_name,
                            u3.first_name as director_firstname,
                            u3.user_id as director_id,
                            CONCAT(u4.first_name,' ',u4.last_name) as dean_name, 
                            u4.first_name as dean_firstname,
                            u4.user_id as dean_id,
                            CONCAT(u5.first_name,' ',u5.last_name) as associate_dean_name,
                            u5.first_name as associate_dean_first_name,
                            u5.user_id as associate_dean_id,
                            d.name as department_name,
                            di.name as division_name
                            FROM users as u
                            LEFT JOIN departments as d on (u.department_id=d.department_id)
                            LEFT JOIN users as u2 on (u2.user_id=d.chair)
                            LEFT JOIN users as u3 on (u3.user_id=d.director)
                            LEFT JOIN divisions as di on (d.division_id=di.division_id)
                            LEFT JOIN users as u4 on (u4.user_id=di.dean)
                            LEFT JOIN users as u5 on (u5.user_id=di.associate_dean)
                            WHERE u.user_id=$user_id";
                $info=$db->getRow($sql);
                //echo("<pre>"); var_dump($info); echo("</pre>") ;            
                if($approval['authority']=='dean'){
                     $msg="<a href='ORS Tracking Form for $info[pi_name] requires review";
                                             
                }   // if dean
            }//form_tracking
        }  // if continue
    
    }//foreach formset
    
}

//Compose the ORS email showing everything that was signed 

//for each division

    //load every entry that is not approved or viewed
    //OR if viewed, type is 'sign' and more than one week has passed.

    //for each entry

        // Is there another entry for this item higher in the queue order requiring signing? If so, skip. Not ready for me yet
        //Note: If queue numbers are the same then can have simultaneous signatures.
        
        //Otherwise, add to the division's email digest.

?>
