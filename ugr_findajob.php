<?php
require_once('includes/global.inc.php');

$tmpl=loadPage("ugr_findajob", 'Find a Job');
showMenu("ugr_intro");

/*
$sql="  select * from committee_members
        left join committees using (committee_id) 
        left join users using (user_id) 
        left join departments using (department_id) 
        left join profiles using (user_id)
        where committees.name = 'Human Research Ethics Board' 
        and committee_members.chair = TRUE
        order by users.last_name, users.first_name";
$chair=$db->GetAll($sql);


$sql="  select * from committee_members
        left join committees using (committee_id) 
        left join users using (user_id) 
        left join departments using (department_id)
        left join profiles using (user_id)  
        WHERE committees.name = 'Human Research Ethics Board'
        AND committee_members.chair = FALSE 
        order by users.last_name, users.first_name";

$members=$db->GetAll($sql);




$tmpl->addVar('page', 'chair_first', $chair[0]['first_name']);
$tmpl->addVar('page', 'chair_last', $chair[0]['last_name']);
$tmpl->addVar('page', 'chair_dept', $chair[0]['name']);
$tmpl->addVar('page', 'chair_email', strrev($chair[0]['email']));
$tmpl->addVar('page', 'chair_phone', $chair[0]['phone']);
foreach($members as $member){
    
    $sql=" SELECT pictures.file_name,pictures.picture_id
                 FROM pictures,pictures_associated
                WHERE pictures_associated.table_name=\"users\"
                  AND object_id=$member[user_id]
                  AND pictures_associated.picture_id=pictures.picture_id
                  AND pictures.feature=FALSE
                  ORDER BY RAND()
                  LIMIT 1";
        
        $pictures=$db->GetAll($sql);
        $picture=reset($pictures);
        if($picture){
            $img_url="pictures/$picture[file_name]";  
            $tmpl->addVar("members","img_url",$img_url);  
        }
        else {
            $img_url="images/blank.gif";  
            $tmpl->addVar("members","img_url",$img_url);
        }
  $tmpl->addVar('members', 'member_first', $member['first_name']);
  $tmpl->addVar('members', 'member_last', $member['last_name']);
  $tmpl->addVar('members', 'member_dept', $member['name']);
  $tmpl->addVar('members', 'member_email', strrev($member['email']));
  $tmpl->addVar('members', 'member_phone', $member['phone']); 
  $tmpl->parseTemplate('members', 'a');
}

*/

$tmpl->displayParsedTemplate('page');
?>