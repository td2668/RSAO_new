<?php
/**
* This file is used to load the annual report pages, the $_GET['page'] parameter is required.
*/
/***********************************
* INCLUDES
************************************/
require_once('includes/global.inc.php');

/***********************************
* CONFIGURATION
************************************/
$page = (isset($_GET["page"])) ? CleanString($_GET["page"]) : '';
$mrAction = (isset($_REQUEST["mr_action"])) ? CleanString($_REQUEST["mr_action"]) : '';
$cvItemId = (isset($_GET["cv_item_id"])) ? CleanString($_GET["cv_item_id"]) : false;
$reportId = (isset($_GET["report_id"])) ? CleanString($_GET["report_id"]) : false;
$getUserId = (isset($_GET["user_id"])) ? CleanString($_GET["user_id"]) : false;
$nextPageFlag = (isset($_REQUEST["next_flag"])) ? CleanString($_REQUEST["next_flag"]) : false;

$userId = GetVerifyUserId();

//PrintR($_REQUEST);

/***********************************
* MAIN
************************************/
if (sessionLoggedin() == true) {

    $userId = GetVerifyUserId();
    if ($userId == false || $userId < 1) {
        displayBlankPage("Invalid username","<h1>Security Error</h1><p>Possible hacking attempt or session error, please contact your system administrator.</p>");
        // log an error here?

        die(1);
    }

    // check for ajax calls, if there is an ajax call, we have to make sure no headers are sent
    // before this and we stop execution before the next switch
    switch($mrAction) {
        case 'ajax_save_course_comments':
            $courseTeachingId = (isset($_POST["course_teaching_id"])) ? CleanString($_POST["course_teaching_id"]) : null;
            echo json_encode(SaveCourseComments($courseTeachingId));
            exit;
            break;
        case 'ajax_set_evaluation':
            $courseTeachingId = (isset($_GET["course_teaching_id"])) ? CleanString($_GET["course_teaching_id"]) : null;
            echo json_encode(SetCourseReportFlag($courseTeachingId));
            exit;
            break;
        case 'ajax_set_report_flag':
            $cvItemId = (isset($_GET["cv_item_id"])) ? CleanString($_GET["cv_item_id"]) : null;
            echo json_encode(SetReportFlag($cvItemId));
            exit;
            break;
        case 'ajax_save_comments':
            $reportId = (isset($_POST["report_id"])) ? CleanString($_POST["report_id"]) : null;
            $comments = (isset($_POST["comments"])) ? CleanString($_POST["comments"]) : null;
            //echo "<p>{reportId}</p><p{$comments}</p>";
            echo json_encode(SaveComments($userId, $reportId, $comments));
            exit;
            break;
        case 'ajax_set_chair_flag':
            echo json_encode(SetChairFlag($userId));
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
    switch($page) {
        case "ar_information":
            $tmpl = loadPage('ar_information');
            require_once("includes/ar_information.inc.php");
            if ($mrAction == 'Save Changes' || $mrAction == 'Save and Next Page') {
                if (SaveForm($userId)) {
                    $status = 'The form has been saved.';
                    if ($nextPageFlag) header('Location: ' . $configInfo['url_root'] . '/annual_report.php?page=ar_courses_taught');
                } else {
                    $status = 'An error occured and the form was not saved.';
                } // if
                $tmpl->addVar('status_message','STATUS',$status);
            } // if
            PopulateForm($userId, $tmpl);
            $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');
            break;
        case "ar_courses_taught":
            require_once("includes/ar_courses_taught.inc.php");
            $tmpl = loadPage('ar_courses_taught');
            PopulateCourseList($userId, $tmpl);
            break;
        case "ar_scholarship":
            $tmpl = loadPage('ar_scholarship');
            require_once("includes/ar_scholarship.inc.php");
            if ($mrAction == 'Save Changes' || $mrAction == 'Save and Next Page') {
                if (SaveForm($userId)) {
                    $status = 'The form has been saved.';
                    if ($nextPageFlag) header('Location: ' . $configInfo['url_root'] . '/annual_report.php?page=ar_scholarly_activities');
                } else {
                    $status = 'An error occured and the form was not saved.';
                } // if
                $tmpl->addVar('status_message','STATUS',$status);
            } // if
            PopulateForm($userId, $tmpl);
            $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');
            break;
        case "ar_service":
            $tmpl = loadPage('ar_service');
            require_once("includes/ar_service.inc.php");
            if ($mrAction == 'Save Changes' || $mrAction == 'Save and Next Page') {
                if (SaveForm($userId)) {
                    $status = 'The form has been saved.';
                    if ($nextPageFlag) header('Location: ' . $configInfo['url_root'] . '/annual_report.php?page=ar_service_activities');
                } else {
                    $status = 'An error occured and the form was not saved.';
                } // if
                $tmpl->addVar('status_message','STATUS',$status);
            } // if
            PopulateForm($userId, $tmpl);
            $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');
            break;
        case "ar_teaching_related":
        case "ar_scholarly_activities":
        case "ar_service_activities":
            require_once("includes/ar_cv_item.inc.php");
            $pageTitle = GetPageTitle($page, $mrAction);
            switch ($mrAction) {
                case 'Save and New':
                    $tmpl = loadPage('ar_cv_items_generic_form',$pageTitle,$page);
                    SaveForm($cvItemId, $userId, $tmpl);
                    $cvItemId = null; // rest and continue to add form below
                    $_GET['cv_item_type_id'] = null; // reset and continue to add form below
                    $mrAction = 'add';
                    unset($tmpl);
                    //echo 'now add a new record';exit;
                    // no break;
                case 'edit':
                case 'add':
                case 'Add an item':
                    $tmpl = loadPage('ar_cv_items_generic_form',$pageTitle,$page);
                    AddPageVars($page, $mrAction, $tmpl);
                    GenerateEditForm($cvItemId, $page, $userId, $tmpl);
                    break;
                case 'save':
                case 'Save Changes':
                    $tmpl = loadPage('ar_cv_items_generic_form',$pageTitle,$page);
                    AddPageVars($page, $mrAction, $tmpl);
                    SaveForm($cvItemId, $userId, $tmpl);
                    GenerateEditForm($cvItemId, $page, $userId, $tmpl);
                    break;
                case 'Delete':
                case 'back_to_list':
                default:
                    $tmpl = loadPage('ar_cv_items_generic',$pageTitle,$page);
                    AddPageVars($page, $mrAction, $tmpl);
                    if ($mrAction == 'Delete') DeleteItem($cvItemId, $tmpl);
                    PopulateList($userId, $page, $tmpl);
                    break;
            } // switch
            $nextPageName = GetNextPageName($page);
            $tmpl->addVar('PAGE','NEXT_PAGE',$nextPageName);
            break;
        case "ar_review_submit":
            require_once("includes/ar_review_submit.inc.php");
            $reportUserId = (isset($_GET["report_user_id"])) ? CleanString($_GET["report_user_id"]) : false;
            switch ($mrAction) {
                case 'preview':
                    GenerateAnnualReport($reportUserId, $userId);
                    exit;
                    break;
                case 'Export My CV':
                case 'createcv':
                    $reportUserId = $userId; // just generate for current user for now
                    GenerateWordCv($reportUserId, $userId);
                    exit;
                    break;
                case 'Export My CAQC':
                case 'createcaqc':
                    $reportUserId = $userId; // just generate for current user for now
                    GenerateWordCaqc($reportUserId, $userId);
                    exit;
                    break;
                case 'submit':
                    // create new record in reports table
                    $tmpl = loadPage('ar_review_submit');
                    $tmpl->addVar('Page','USER_ID',$userId);
                    $tmpl->addVar('Page','PAGE_TITLE','Review/Submit');
                    $tmpl->addVar('tools_box', 'ENABLED','True');
                    if ($getUserId > 0) {
                        SubmitReport($getUserId, $tmpl);
                        // display a special status message?



                        
                    } else {
                        // submit for this user
                        SubmitReport($userId, $tmpl);
                    } // if
                    CreateReportList($userId, $tmpl);
                    break;
                default:
                    $tmpl = loadPage('ar_review_submit');
                    $tmpl->addVar('Page','USER_ID',$userId);
                    $tmpl->addVar('Page','PAGE_TITLE','Review/Submit');
                    $tmpl->addVar('tools_box', 'ENABLED','True');
                    CreateReportList($userId, $tmpl);
                    break;
            } // switch
            break;
        case 'ar_dean':
            require_once("includes/ar_review_submit.inc.php");
            // double-check to see if this is the dean
            $deanFLag = (isset($_SESSION['user_info']['dean_flag'])) ? $_SESSION['user_info']['dean_flag'] : false;
            $tmpl = loadPage('ar_review_submit','Dean Approval Page','ar_dean');
            $tmpl->addVar('Page', 'USER_ID', $userId);
            $tmpl->addVar('Page', 'PAGE_TITLE','Dean Approvals');
            if ($deanFLag) {
                switch ($mrAction) {
                    case 'approve':
                        ApproveReport($reportId, $userId, $tmpl);
                        CreateReportList($userId, $tmpl, array('dean_flag' => $deanFLag));
                        break;
                    case 'comment':
                        //break;
                    default:
                        CreateReportList($userId, $tmpl, array('dean_flag' => $deanFLag));
                        break;
                } // switch
            } else {
                $tmpl->addVar('status_message', 'STATUS', 'You do not appear to have permission to view this page.');
            } // if
            break;
        case 'ar_chair':
            require_once("includes/ar_review_submit.inc.php");
            // double-check to see if this is the chair
            $chairFlag = (isset($_SESSION['user_info']['chair_flag'])) ? $_SESSION['user_info']['chair_flag'] : false;
            $tmpl = loadPage('ar_review_submit','Chair Review Page','ar_chair');
            $tmpl->addVar('Page', 'PAGE_TITLE','Chair Review');
            if ($chairFlag) {
                CreateReportList($userId, $tmpl, array('chair_flag' => $chairFlag));
            } else {
                $tmpl->addVar('status_message', 'STATUS', 'You do not appear to have permission to view this page.');
            } // if
        default:
            break;
    } // switch
} else {
    // access denied, this entire section is currently secure
    $tmpl = loadPage("accessdenied","Annual Report","annual_report");
    // log / email and error here as well?
}
// load the common annual report javascript library
//$tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="js/annual_report.js"></script>');
//set
// display the template to the user
$tmpl->displayParsedTemplate();
exit;

/***********************************
* FUNCTIONS
************************************/

function GetYear($timestamp) {
    return date('Y',$timestamp);
} // function

function CleanPostForMysql($target) {
    if (get_magic_quotes_gpc()) {
        return $target;
    } else {
        return mysql_real_escape_string($target);
    } // if
} // function


/***********************************
* AJAX CALLED FUNCTIONS
************************************/

function SaveCourseComments($courseTeachingId) {

    global $db;

    $data = array();
    $data['status'] = true;

    if ($courseTeachingId > 0) {

        $comments1 = (isset($_POST["comments1"])) ? mysql_real_escape_string($_POST["comments1"]) : '';
        $comments2 = (isset($_POST["comments2"])) ? mysql_real_escape_string($_POST["comments2"]) : '';
        $sei = (isset($_POST["sei"])) ? mysql_real_escape_string($_POST["sei"]) : '';
        $q1 = (isset($_POST["q1"])) ? mysql_real_escape_string($_POST["q1"]) : '';
        $q2 = (isset($_POST["q2"])) ? mysql_real_escape_string($_POST["q2"]) : '';
        $q3 = (isset($_POST["q3"])) ? mysql_real_escape_string($_POST["q3"]) : '';
        $q4 = (isset($_POST["q4"])) ? mysql_real_escape_string($_POST["q4"]) : '';
        $q5 = (isset($_POST["q5"])) ? mysql_real_escape_string($_POST["q5"]) : '';

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
        if (!($db->Execute($sql))) $data['status'] = false;
    } else {
        // invalid id received, log an error?
        $data['status'] = false;
    } // if

    return $data;

} // function SaveCourseComments

function SetCourseReportFlag($courseTeachingId) {

    global $db;

    $data = array();
    $data['status'] = true;

    if ($courseTeachingId > 0) {
        $sql = "UPDATE course_teaching SET report_flag = IF(report_flag = 0, 1, 0) WHERE course_teaching_id = {$courseTeachingId}";
        if (!($db->Execute($sql))) $data['status'] = false;
    } else {
        // invalid id received, log an error?
        $data['status'] = false;
    } // if

    return $data;

} // function SetCourseReportFlag

function SetReportFlag($cvItemId) {

    global $db;

    $data = array();
    $data['status'] = true;

    if ($cvItemId > 0) {
        $sql = "UPDATE cv_items SET report_flag = IF(report_flag = 0, 1, 0) WHERE cv_item_id = {$cvItemId}";
        $db->Execute($sql);
    } else {
        // invalid id received, log an error?

    } // if

    return $data;

} // function SetReportFlag

function SaveComments($userId, $reportId, $comments) {

    global $db;

    $returnStatus = 1;

    // make sure this is a dean doing this

    // save the data
    if ($reportId > 0 && trim($comments) != '') {
        $sql = "UPDATE ar_reports SET comments = '{$comments}' WHERE report_id = {$reportId}";
        if ($db->Execute($sql)) {
            // worked
        } else {
            // query failed
            trigger_error("SaveComments(): query failed ({$sql})");
            $returnStatus = 0;

        } // if
        // check for error and update status
    } else {
        trigger_error("SaveComments(): invalid parameters received ({$reportId})");
        $returnStatus = 0;
    } // if

    // email user and/or chair?


    return $returnStatus;

} // function SaveComments

function SetChairFlag($userId) {

    global $db;

    $returnStatus = 1;

    if ($userId > 0) {
        $sql = "UPDATE ar_profile SET chair_duties_flag = IF(chair_duties_flag = 1,0,1) WHERE user_id = {$userId}";
        $db->Execute($sql);
    } else {
        // invalid id received, log an error?
        $returnStatus = 0;
    } // if

    return $returnStatus;

} // function SetReportFlag
?>
