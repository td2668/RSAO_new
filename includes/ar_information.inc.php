<?php
/**
* This file contains functions and classes for the appropriate annual report section.
*/
/***********************************
* FUNCTIONS
************************************/

function SaveForm($userId) {

    global $db;

    //echo "<!--";
    //PrintR($_POST);


    // validate & clean
    $requiredPostVars = array('first_name','last_name','title','department_id','secondary_department_id','status','work_pattern',
        'short_profile','teaching_philosophy','top_3_achievements','teaching_goals','activities');
    $cleanPost = array();
    $missingFields = false;
    foreach($requiredPostVars AS $postVar) {
        if (isset($_POST[$postVar])) {
            $cleanPost[$postVar] = CleanPostForMysql($_POST[$postVar]);
        } else {
            $cleanPost[$postVar] = '';
            $missingFields = true; // in case we care later
        } // if
    } // foreach
    //echo "<!--\n\n\n";PrintR($cleanPost);echo "\n\n\n-->";

    // UPDATE THE AR_PROFILES TABLE DATA *****************************************************************

    // check to see if a profile exists for this user, if not, create one
    $sql = "SELECT * FROM `ar_profiles` WHERE `user_id` = {$userId}";
    $data = $db->GetRow($sql);
    if (is_array($data) == false or count($data) == 0) {
        // not yet, create a new one
        $sql = " INSERT INTO `ar_profiles` ";
        $whereClause = '';
    } else {
        // there is already a record, use it
        $sql = " UPDATE `ar_profiles` ";
        $whereClause = " WHERE `user_id` = {$userId} ";
    }
    $sql .= "
        SET `user_id` = {$userId},
            `short_profile` = '{$cleanPost['short_profile']}',
            `teaching_philosophy` = '{$cleanPost['teaching_philosophy']}',
            `top_3_achievements` = '{$cleanPost['top_3_achievements']}',
            `teaching_goals` = '{$cleanPost['teaching_goals']}',
            `activities` = '{$cleanPost['activities']}'";
    $sql = $sql . $whereClause;
    //echo "<br />" . $sql;
    $status1 = $db->Execute($sql);

    // UPDATE THE USERS TABLE DATA *****************************************************************
    $sql = "
        UPDATE `users`
        SET `department_id` = {$cleanPost['department_id']},
            `department2_id` = {$cleanPost['secondary_department_id']},
            `first_name` = '{$cleanPost['first_name']}',
            `last_name` = '{$cleanPost['last_name']}'
        WHERE user_id = {$userId}
        ";
    //echo "<br />" . $sql;
    $status2 = $db->Execute($sql);

    // UPDATE THE USERS_EXT TABLE DATA *****************************************************************

    // check to see if a users_ext record exists for this user, if not, create one
    $sql = "SELECT * FROM `users_ext` WHERE `user_id` = {$userId}";
    $data = $db->GetRow($sql);
    if (is_array($data) == false or count($data) == 0) {
        // not yet, create a new one
        $sql = " INSERT INTO `users_ext` ";
        $whereClause = '';
    } else {
        // there is already a record, use it
        $sql = " UPDATE `users_ext` ";
        $whereClause = " WHERE `user_id` = {$userId} ";
    }
    $sql .= "
        SET `user_id` = {$userId},
            `tss` = '{$cleanPost['work_pattern']}',
            `emp_status` = '{$cleanPost['status']}'";
    $sql = $sql . $whereClause;
    //echo "<br />" . $sql;
    $status3 = $db->Execute($sql);

    // UPDATE THE PROFILE TABLE DATA *****************************************************************

    $sql = "SELECT * FROM `profiles` WHERE `user_id` = {$userId}";
    $data = $db->GetRow($sql);
    if (is_array($data) == false or count($data) == 0) {
        // not yet, create a new one
        $sql = " INSERT INTO `profiles` SET `user_id` = {$userId}, `title` = '{$cleanPost['title']}'";
    } else {
        // there is already a record, update it
        $sql = " UPDATE `profiles` SET `title` = '{$cleanPost['title']}'  WHERE `user_id` = {$userId} ";
    }
    //echo "<br />" . $sql;
    $status4 = $db->Execute($sql);

    //echo "status1:{$status1} status2:{$status2} status3:{$status3} status4:{$status4}";
    //echo "-->";

    return $status1 && $status2 && $status3 && $status4;
} // function


function PopulateForm($userId, &$tmpl) {

    global $db;
    $status = false;
    $researchDivisionId = 23; // the id in the divisions table corresponding to the research division

    // get the data to create the form
    $data = GetPersonData($userId);
    //PrintR($data);
    if (is_array($data) == false or count($data) == 0) {
        // error getting data

    } else {
        //PrintR($data);
        $status = true;
        // populate the  template text data fields
        $tmpl->addVar('PAGE','FIRST',$data['first_name']);
        $tmpl->addVar('PAGE','LAST',$data['last_name']);
        $tmpl->addVar('PAGE','TITLE',$data['title']);
        $tmpl->addVar('PAGE','PROFILE',$data['short_profile']);
        $tmpl->addVar('PAGE','PHILOSOPHY',$data['teaching_philosophy']);
        $tmpl->addVar('PAGE','TOP3',$data['top_3_achievements']);
        $tmpl->addVar('PAGE','GOALS',$data['teaching_goals']);
        $tmpl->addVar('PAGE','ACTIVITIES',$data['activities']);
        // populate the department drop-downs
        $sql = "SELECT `department_id`, `name` FROM `departments` ORDER BY `name`"; // WHERE `division_id` = {$researchDivisionId} ORDER BY `name`";
        $departmentData = $db->getAll($sql);
        $departmentList = array();
        $departmentList[] = array("department_id"=>"0", "name"=>" -- Select a Department -- ");
        $departmentList2[] = array("department_id"=>"0", "name"=>"N/A");
        if (is_array($departmentData)) {
            //PrintR($departmentData);
            foreach ($departmentData AS $department) {
                $currentDepartment = $department;
                if ($department['department_id'] == $data['department_id']) $currentDepartment['department_id_selected'] = "SELECTED";
                $departmentList[] = $currentDepartment;
                $currentDepartment2 = $department;
                if ($department['department_id'] == $data['department2_id']) $currentDepartment2['department_id_selected'] = "SELECTED";
                $departmentList2[] = $currentDepartment2;
            } // foreach
        } else {
            // couldn't get the department information

        }
        //PrintR($departmentList);
        $tmpl->addRows("department_options",$departmentList);
        $tmpl->addRows("department_options2",$departmentList2);

        // set up status menu
        // **** these values are stored in the database, so adding to them is fine,
        // but deleting or changing may require db changes as well ****
        $statusOptions = array("TN" => "Tenure Track",'T' => "Tenured",'TC' => "Term Certain");
        // **************************************************************
        $statusList = array();
        foreach ($statusOptions AS $statusCode => $statusName) {
            $statusList[] = array(
                'value' => $statusCode,
                "name" => $statusName,
                "selected" => ($data['status'] == $statusCode) ? ' SELECTED': '',
            );
        } // foreach
        $tmpl->addRows("status",$statusList);

        // set up work pattern menu
        // **** these values are stored in the database, so adding to them is fine,
        // but deleting or changing may require db changes as well ****
        $workPatternOptions = array(0 => "Teaching/Service",1 => "Teaching/Scholarship/Service");
        // **************************************************************
        $workPatternList = array();
        foreach ($workPatternOptions AS $value => $name) {
            $workPatternList[] = array(
                'value' => $value,
                "name" => $name,
                "selected" => ($data['work_pattern'] == $value) ? ' SELECTED': '',
            );
        } // foreach
        $tmpl->addRows("work_pattern",$workPatternList);
    } // if

    return $status;

} // function
?>
