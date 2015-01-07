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
    $requiredPostVars = array('service_achievements','service_goals','service_goals_lastyear','chair_duties_flag','service_chair_goals','service_chair_achievements','service_chair_other');
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
    //PrintR($cleanPost);

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
            `service_achievements` = '{$cleanPost['service_achievements']}',
            `service_goals` = '{$cleanPost['service_goals']}',
            `service_goals_lastyear` = '{$cleanPost['service_goals_lastyear']}',
            `chair_duties_flag` = '{$cleanPost['chair_duties_flag']}',
            `service_chair_goals` = '{$cleanPost['service_chair_goals']}',
            `service_chair_achievements` = '{$cleanPost['service_chair_achievements']}',
            `service_chair_other` = '{$cleanPost['service_chair_other']}'
    ";
    $sql = $sql . $whereClause;

    //echo $sql;
    $status1 = $db->Execute($sql);

    return $status1;
} // function

function PopulateForm($userId, &$tmpl) {

    global $db;
    $status = false;

    // include the jquery library for the ajax features
    $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');

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
        $tmpl->addVar('PAGE','ACHIEVEMENTS',$data['service_achievements']);
        $tmpl->addVar('PAGE','GOALS',$data['service_goals']);
        $tmpl->addVar('PAGE','GOALS_LASTYEAR',$data['service_goals_lastyear']);
        $tmpl->addVar('PAGE','CHAIR_GOALS',$data['service_chair_goals']);
        $tmpl->addVar('PAGE','CHAIR_ACHIEVEMENTS',$data['service_chair_achievements']);
        $tmpl->addVar('PAGE','CHAIR_DUTIES_FLAG',$data['chair_duties_flag']);
        $chairChecked = $data['chair_duties_flag'] ? ' checked' : '';
        $tmpl->addVar('PAGE','CHAIR_CHECKBOX',$chairChecked);
        $chairDisplay = $data['chair_duties_flag'] ? 'block' : 'none';
        $tmpl->addVar('PAGE','CHAIR_DISPLAY',$chairDisplay);
        $tmpl->addVar('PAGE','CHAIR_OTHER',$data['service_chair_other']);
    } // if

    return $status;

} // function
?>
