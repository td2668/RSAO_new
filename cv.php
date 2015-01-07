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
$page = (isset( $_GET["page"] )) ? CleanString( $_GET["page"] ) : '';
$mrAction = (isset( $_REQUEST["mr_action"] )) ? CleanString( $_REQUEST["mr_action"] ) : '';
$cvItemId = (isset( $_GET["cv_item_id"] )) ? CleanString( $_GET["cv_item_id"] ) : false;
$reportId = (isset( $_GET["report_id"] )) ? CleanString( $_GET["report_id"] ) : false;
$getUserId = (isset( $_GET["user_id"] )) ? CleanString( $_GET["user_id"] ) : false;
$nextPageFlag = (isset( $_REQUEST["next_flag"] )) ? CleanString( $_REQUEST["next_flag"] ) : false;
$casHeadingId = (isset( $_REQUEST["cas_heading_id"] )) ? CleanString( $_REQUEST["cas_heading_id"] ) : false;
$userId = GetVerifyUserId();

//setup menu heading id based on page being sent in

/* * *********************************
 * MAIN
 * ********************************** */
if ( sessionLoggedin() == true ) {

    $userId = GetVerifyUserId();
    if ( $userId == false || $userId < 1 ) {
        displayBlankPage( "Invalid username", "<h1>Security Error</h1><p>Possible hacking attempt or session error, please contact your system administrator.</p>" );
        // log an error here?

        die( 1 );
    }

    // check for ajax calls, if there is an ajax call, we have to make sure no headers are sent
    // before this and we stop execution before the next switch
    switch ( $mrAction ) {

        case 'ajax_save_course_comments':
            $courseTeachingId = (isset( $_POST["course_teaching_id"] )) ? CleanString( $_POST["course_teaching_id"] ) : null;
            echo json_encode( SaveCourseComments( $courseTeachingId ) );
            exit;
            break;
        case 'ajax_set_evaluation':
            $courseTeachingId = (isset( $_GET["course_teaching_id"] )) ? CleanString( $_GET["course_teaching_id"] ) : null;
            echo json_encode( SetCourseReportFlag( $courseTeachingId ) );
            exit;
            break;
        case 'ajax_set_report_flag':
            $cvItemId = (isset( $_GET["cv_item_id"] )) ? CleanString( $_GET["cv_item_id"] ) : null;
            echo json_encode( SetReportFlag( $cvItemId ) );
            exit;
            break;
        case 'ajax_save_comments':
            $reportId = (isset( $_POST["report_id"] )) ? CleanString( $_POST["report_id"] ) : null;
            $comments = (isset( $_POST["comments"] )) ? mysql_real_escape_string( $_POST["comments"] ) : null;
            //echo "<p>{reportId}</p><p{$comments}</p>";
            echo json_encode( SaveComments( $userId, $reportId, $comments ) );
            exit;
            break;
        case 'ajax_set_chair_flag':
            echo json_encode( SetChairFlag( $userId ) );
            exit;
            break;
        case 'ajax_get_help':
            // perform task,  ajax data (if applicable), end execution here
            break;
        default:
            break;
    } // switch
    // check to see which page is being called, perform any actions as required, and then load the
    // appropriate template for display


    require_once("includes/cv_item.inc.php");


    switch ( $mrAction ) {
        case 'generate_help':
            require_once('includes/pdf.php');
            GenerateHelpPdf();
            exit();
            break;
        case 'generate_xref':
            require_once('includes/pdf.php');
            GenerateXRefPdf();
            exit();
            break;
        case 'Save and New':
            $tmpl = loadPage( 'cv_items_generic_form', $pageTitle, $page );
            SaveForm( $cvItemId, $userId, $tmpl );
            header('location:cv.php?mr_action=add&cas_heading_id=' . $casHeadingId);
            exit();
        //echo 'now add a new record';exit;
        // no break;
        case 'change_type':
            echo("CHANGING TYPE");
            $casTypeId = (isset( $_REQUEST["cas_type_id"] )) ? CleanString( $_REQUEST["cas_type_id"] ) : '';
            $cvItemId = (isset( $_REQUEST["cv_item_id"] )) ? CleanString( $_REQUEST["cv_item_id"] ) : '';
            $sql = "UPDATE cas_cv_items SET cas_type_id={$casTypeId} WHERE cv_item_id={$cvItemId}";
            $result = $db->Execute($sql);
        case 'edit':
        case 'add':
        case 'Add an item':
            $tmpl = loadPage( 'cv_items_generic_form', $pageTitle, $page );
            $pageTitle = GetPageTitle( $cvItemId );
            AddPageVars( $casHeadingId, $pageTitle, $mrAction, $tmpl );
            GenerateEditForm( $cvItemId, $page, $userId, $tmpl );
            break;
        case 'save':
        case 'Save Changes':

            $tmpl = loadPage( 'cv_items_generic_form', $pageTitle, $page );
            $pageTitle = GetPageTitle( $cvItemId );
            AddPageVars( $casHeadingId, $pageTitle, $mrAction, $tmpl );
            $newHeader=SaveForm( $cvItemId, $userId, $tmpl );
            if($newHeader){
                //echo("Doing a change via save changes");
                //heading ID will have changed, so need to reload it
                $item = GetCvItem( $cvItemId, $userId );
                $sql="SELECT cas_heading_id FROM cas_types WHERE cas_type_id=$item[cas_type_id]";
                $type=$db->getRow($sql);
                header('location:cv.php?mr_action=edit&cas_heading_id='.$type['cas_heading_id'].'&cv_item_id='.$cvItemId);   
            }
            GenerateEditForm( $cvItemId, $page, $userId, $tmpl );
            break;
        case 'Delete':
        case 'back_to_list':
        case 'move':
            $direction = (isset( $_REQUEST["direction"] )) ? CleanString( $_REQUEST["direction"] ) : '';
            $casTypeId = (isset( $_REQUEST["cas_type_id"] )) ? CleanString( $_REQUEST["cas_type_id"] ) : '';
            $cvItemId = (isset( $_REQUEST["cv_item_id"] )) ? CleanString( $_REQUEST["cv_item_id"] ) : '';
            if ($direction && $casTypeId && $cvItemId){
                SortCvItems($userId,$casTypeId,$cvItemId,$direction);
            }




        default:
            $tmpl = loadPage( 'cv_items_generic', $pageTitle, $page );
            $pageTitle = GetPageTitle( $cvItemId );
            AddPageVars( $casHeadingId, $pageTitle, $mrAction, $tmpl );
            if ( $mrAction == 'Delete' )
                DeleteItem( $cvItemId, $tmpl );
            PopulateList( $userId, $page, $tmpl );
            break;
    } // switch

} else {
    // access denied, this entire section is currently secure
    $tmpl = loadPage( "accessdenied", "Annual Report", "annual_report" );
    // log / email and error here as well?
}
// load the common annual report javascript library
//$tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="js/annual_report.js"></script>');
//set
// display the template to the user
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
