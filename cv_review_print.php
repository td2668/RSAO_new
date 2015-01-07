<?php

/**
 * This file is used to load the annual report pages, the $_GET['page'] parameter is required.
 */
/* * *********************************
 * INCLUDES
 * ********************************** */
require_once('includes/global.inc.php');

/* * *********************************
 * CONFIGURATION
 * ********************************** */

$mrAction = (isset( $_REQUEST["mr_action"] )) ? CleanString( $_REQUEST["mr_action"] ) : '';
$getUserId = (isset( $_GET["user_id"] )) ? CleanString( $_GET["user_id"] ) : false;
$nextPageFlag = (isset( $_REQUEST["next_flag"] )) ? CleanString( $_REQUEST["next_flag"] ) : false;
$casHeadingId = (isset( $_REQUEST["cas_heading_id"] )) ? CleanString( $_REQUEST["cas_heading_id"] ) : false;
$citationStyle = (isset( $_REQUEST["citation_style"] )) ? CleanString( $_REQUEST["citation_style"] ) : 'apa';
$generateWhat = (isset( $_REQUEST["generate"] )) ? CleanString( $_REQUEST["generate"] ) : '';
$style = (isset( $_REQUEST["style"] )) ? CleanString( $_REQUEST["style"] ) : '';

if(isset($_REQUEST['report_user_id'])) $userId=$_REQUEST['report_user_id']; else $userId = GetVerifyUserId();
//print_r($_REQUEST);
//setup menu heading id based on page being sent in

/* * *********************************
 * MAIN
 * ********************************** */
if ( sessionLoggedin() == true ) {

    //$userId = GetVerifyUserId();
    if ( $userId == false || $userId < 1 ) {
        displayBlankPage( "Invalid username", "<h1>Security Error</h1><p>Possible hacking attempt or session error, please contact your sys administrator.</p>" );
        // log an error here?

        die( 1 );
    }


    if ($generateWhat){
        if (strtolower($generateWhat) == 'everything'){
            $generateWhat = '';
        }
        require_once('includes/pdf.php');
        GenerateCV($userId,$generateWhat,$style);
        exit();
    }else{
        $tmpl = loadPage( 'cv_review_submit', 'CV Review','this_year');

        if ($citationStyle == 'apa'){
            $tmpl->addVar( 'Page', 'APA_CHECKED', 'CHECKED="CHECKED"' );
        }else{
            $tmpl->addVar( 'Page', 'CHICAGO_CHECKED', 'CHECKED="CHECKED"' );
        }
        //AddPageVars( $casHeadingId, 'Review / Submit', $mrAction, $tmpl );
    }

}

// load the common annual report javascript library
//$tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="js/annual_report.js"></script>');
//set
// display the template to the user
$tmpl->addVar( 'HEADER', 'ADDITIONAL_HEADER_ITEMS', '    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>' );
$tmpl->addVar('Page','USER_ID',$userId);
$tmpl->displayParsedTemplate();
exit;

/* * *********************************
 * FUNCTIONS
 * ********************************** */

function GetYear ( $timestamp ) {
    return date( 'Y', $timestamp );
}

// function

function CleanPostForMysql ( $target ) {
    if ( get_magic_quotes_gpc ( ) ) {
        return $target;
    } else {
        return mysql_real_escape_string( $target );
    } // if
}

// function


/* * *********************************
 * AJAX CALLED FUNCTIONS
 * ********************************** */

function SaveCourseComments ( $courseTeachingId ) {

    global $db;

    $data = array( );
    $data['status'] = true;

    if ( $courseTeachingId > 0 ) {

        $comments1 = (isset( $_POST["comments1"] )) ? mysql_real_escape_string( $_POST["comments1"] ) : '';
        $comments2 = (isset( $_POST["comments2"] )) ? mysql_real_escape_string( $_POST["comments2"] ) : '';
        $sei = (isset( $_POST["sei"] )) ? mysql_real_escape_string( $_POST["sei"] ) : '';
        $q1 = (isset( $_POST["q1"] )) ? mysql_real_escape_string( $_POST["q1"] ) : '';
        $q2 = (isset( $_POST["q2"] )) ? mysql_real_escape_string( $_POST["q2"] ) : '';
        $q3 = (isset( $_POST["q3"] )) ? mysql_real_escape_string( $_POST["q3"] ) : '';
        $q4 = (isset( $_POST["q4"] )) ? mysql_real_escape_string( $_POST["q4"] ) : '';
        $q5 = (isset( $_POST["q5"] )) ? mysql_real_escape_string( $_POST["q5"] ) : '';

        $sql = "
            UPDATE `course_teaching`
            SET
                `comments1` = '{$comments1}',
                `comments2` = '{$comments2}',
                `sei` = '{$sei}',
                `q1` = '{$q1}',
                `q2` = '{$q2}',
                `q3` = '{$q3}',
                `q4` = '{$q4}',
                `q5` = '{$q5}'
            WHERE `course_teaching_id` = {$courseTeachingId}
        ";
        //echo $sql;
        if ( !($db->Execute( $sql )) )
            $data['status'] = false;
    } else {
        // invalid id received, log an error?
        $data['status'] = false;
    } // if

    return $data;
}

// function SaveCourseComments

function SetCourseReportFlag ( $courseTeachingId ) {

    global $db;

    $data = array( );
    $data['status'] = true;

    if ( $courseTeachingId > 0 ) {
        $sql = "UPDATE course_teaching SET report_flag = IF(report_flag = 0, 1, 0) WHERE course_teaching_id = {$courseTeachingId}";
        if ( !($db->Execute( $sql )) )
            $data['status'] = false;
    } else {
        // invalid id received, log an error?
        $data['status'] = false;
    } // if

    return $data;
}

// function SetCourseReportFlag

function SetReportFlag ( $cvItemId ) {

    global $db;

    $data = array( );
    $data['status'] = true;

    if ( $cvItemId > 0 ) {
        $sql = "UPDATE cv_items SET report_flag = IF(report_flag = 0, 1, 0) WHERE cv_item_id = {$cvItemId}";
        $db->Execute( $sql );
    } else {
        // invalid id received, log an error?
    } // if

    return $data;
}

// function SetReportFlag

function SaveComments ( $userId, $reportId, $comments ) {

    global $db;

    $returnStatus = 1;

    // make sure this is a dean doing this
    // save the data
    if ( $reportId > 0 ) {
        $sql = "UPDATE ar_reports SET comments = '{$comments}' WHERE report_id = {$reportId}";
        if ( $db->Execute( $sql ) ) {
            // worked
        } else {
            // query failed
            trigger_error( "SaveComments(): query failed ({$sql})" );
            $returnStatus = 0;
        } // if
        // check for error and update status
    } else {
        trigger_error( "SaveComments(): invalid parameters received ({$reportId})" );
        $returnStatus = 0;
    } // if
    // email user and/or chair?


    return $returnStatus;
}

// function SaveComments

function SetChairFlag ( $userId ) {

    global $db;

    $returnStatus = 1;

    if ( $userId > 0 ) {
        $sql = "UPDATE ar_profile SET chair_duties_flag = IF(chair_duties_flag = 1,0,1) WHERE user_id = {$userId}";
        $db->Execute( $sql );
    } else {
        // invalid id received, log an error?
        $returnStatus = 0;
    } // if

    return $returnStatus;
}

// function SetReportFlag
?>
