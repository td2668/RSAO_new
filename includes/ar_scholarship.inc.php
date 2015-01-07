<?php
/**
* This file contains functions and classes for the appropriate annual report section.
*/
/***********************************
* FUNCTIONS
************************************/

function SaveForm($userId) {

    global $db;

    // validate & clean
    $requiredPostVars = array('scholarship_achievements','scholarship_goals');
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

    // save the ar_profiles data
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
            `scholarship_achievements` = '{$cleanPost['scholarship_achievements']}',
            `scholarship_goals` = '{$cleanPost['scholarship_goals']}'
    ";
    $sql = $sql . $whereClause;
    //echo $sql;
    $status1 = $db->Execute($sql);

    return $status1;
} // function

function PopulateForm($userId, &$tmpl) {

    global $db;
    $status = false;

    // get the data from the database tables
    $sql = "
        SELECT * FROM ar_profiles WHERE user_id = {$userId}";
    //echo $sql;
    $data = $db->GetRow($sql);
    if (is_array($data) == false or count($data) == 0) {
        // error getting data

    } else {
        //PrintR($data);
        $status = true;
        // populate the  template data
        $tmpl->addVar('PAGE','ACHIEVEMENTS',$data['scholarship_achievements']);
        $tmpl->addVar('PAGE','GOALS',$data['scholarship_goals']);
        $tmpl->addVar('PAGE','GOALS_LASTYEAR',$data['scholarship_goals_lastyear']);
    } // if

    return $status;

} // function
?>
