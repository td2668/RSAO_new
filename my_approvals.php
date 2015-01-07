<?php

use tracking\TrackingForm;

require_once('includes/global.inc.php');

require_once('classes/tracking/TrackingForm.php');
require_once('classes/tracking/Funding.php');
require_once('classes/tracking/Approval.php');
require_once('classes/tracking/COI.php');

$tmpl = loadPage("my_approvals", 'My Approvals');

$mrAction = (isset($_REQUEST["mr_action"])) ? CleanString($_REQUEST["mr_action"]) : '';  // used for ajax calls

// get the logged in user details
$isDean = $_SESSION['user_info']['dean_flag'] == true;
$isAssociateDean = $_SESSION['user_info']['associate_dean_flag'] == true;

if (sessionLoggedin() && ($isDean || $isAssociateDean)) {
    $user = getLoggedInUser();
    if($isDean) {
        $divisionId = $_SESSION['user_info']['dean_division_id'];
    } elseif($isAssociateDean) {
        $divisionId = $_SESSION['user_info']['associate_dean_division_id'];;
    }
} else {
    displayBlankPage("Error","<h1>Error</h1>You must be logged in and have Dean-level access to view this page.");
    die;
}

// check for ajax calls, if there is an ajax call, we have to make sure no headers are sent
// before this and we stop execution before the next switch
switch($mrAction) {
    case 'ajax_save_comments':
        $reportId = (isset($_POST["tracking_id"])) ? CleanString($_POST["tracking_id"]) : null;
        $comments = urldecode($comments);  // decode the url parameter
        $comments = (isset($_POST["comments"])) ? mysql_real_escape_string($_POST["comments"]) : null;
        echo json_encode(SaveComments($reportId, $divisionId, $comments));
        exit;
        break;
    default:
        break;
} // switch

if(isset($_REQUEST['return'])) {
    $trackingId = $_REQUEST['form_tracking_id'];
    $trackingForm = new TrackingForm();
    $form = $trackingForm->retrieveForm($trackingId);
    $trackingForm->returnForm();
}

if(isset($_REQUEST['approve'])) {
    $trackingId = $_REQUEST['form_tracking_id'];
    $trackingForm = new TrackingForm();
    $form = $trackingForm->retrieveForm($trackingId);
    foreach($trackingForm->approvals AS $approval) {
        if($approval->type == COMMITMENTS || $approval->type == DEAN_REVIEW) {
            $approval->approve();
            $approval->save($divisionId);
        }
    }
}

$pendingTrackingForms = getPendingTrackingForms($divisionId);

    $tmpl->addRows('list', $pendingTrackingForms);
    if(count($pendingTrackingForms) <= 0) {
        $tmpl->setAttribute('list', 'visibility', 'hidden');
        $tmpl->setAttribute('noforms', 'visibility', 'visible');
    }

$approvedTrackingForms = getApprovedTrackingForms($divisionId);
$tmpl->addRows('approved_forms', $approvedTrackingForms);
if(count($approvedTrackingForms) <= 0) {
    $tmpl->setAttribute('approved_forms', 'visibility', 'hidden');
    $tmpl->setAttribute('approved_noforms', 'visibility', 'visible');
}

$tmpl->displayParsedTemplate('page');


/**
 * Get the tracking forms that have pending approvals
 */
function getPendingTrackingForms($divisionId) {
    global $db;

    // Get the tracking forms that have pending approvals for this dean's division
    $sql = sprintf("SELECT app.tracking_id, track.tracking_name, track.deadline, app.comments, track.user_id, CONCAT(users.last_name, ', ', users.first_name) AS submitter
                    FROM `forms_tracking_approvals` AS app
                    LEFT JOIN forms_tracking AS track ON app.tracking_id = track.form_tracking_id
                    LEFT JOIN `users` ON track.user_id = users.user_id
                    WHERE (app.`division_id` = %s AND app.`approved` = 0 AND app.approval_type_id IN (%s, %s))
                    GROUP BY track.deadline, app.tracking_id
                    ORDER BY (CASE WHEN track.deadline = '0000-00-00' then 1 ELSE 0 END), track.deadline ASC", $divisionId, COMMITMENTS, DEAN_REVIEW);

    $trackingForms = $db->getAll($sql);

    if (is_array($trackingForms)) {
        if (count($trackingForms) > 0) {
            // now we append the required approvals for display
            foreach ($trackingForms as $key=>$trackingForm) {
                $trackingForms[$key]['evenodd'] = $key%2 ? '' : 'odd';

                $numFiles = getNumFiles($trackingForm['tracking_id']); // number of files associated with form
                $trackingForms[$key]['hasfiles'] = $numFiles > 0 ? "block" : "none";

                $approvals = getPendingApprovals($trackingForm['tracking_id']);
                if(is_array($approvals)) {
                    foreach($approvals as $index=>$approval) {
                        if($index < count($approvals)) {
                            $trackingForms[$key][$approval['type']] = sprintf("%s<br/>", $approval['friendlyName']);
                        } else {
                            $trackingForms[$key][$approval['type']] = sprintf("%s", $approval['friendlyName']);
                        }
                    }
                }
                if($trackingForm['deadline'] == "0000-00-00") {
                    $trackingForms[$key]['deadline'] = "";
                }
            }
        }
    }

    return $trackingForms;
}

/**
 * Get the number of files associated with a tracking form
 *
 * @param $trackingId -the trackingId
 */
function getNumFiles($trackingId) {
    global $db;

    $sql = "SELECT COUNT(*) AS numFiles FROM forms_tracking_files WHERE `trackingId` = " . $trackingId;
    $numFiles = $db->getRow($sql);

    return $numFiles['numFiles'];
}

/**
 * Get the tracking forms that have already been approved
 */
function getApprovedTrackingForms($divisionId) {
    global $db;

    $dateFormat = "'%Y-%m-%d'";

    // Get the tracking forms that have pending approvals for this dean's division
    $sql = sprintf("SELECT app.tracking_id, track.tracking_name, track.deadline, DATE_FORMAT(app.date_approved, %s) AS date_approved, CONCAT(users.last_name, ', ', users.first_name) AS submitter
                    FROM `forms_tracking_approvals` AS app
                    LEFT JOIN forms_tracking AS track ON app.tracking_id = track.form_tracking_id
                    LEFT JOIN `users` ON track.user_id = users.user_id
                    WHERE app.`division_id` = %s AND app.`approved` = 1
                        AND (app.date_approved BETWEEN DATE_SUB(NOW(), INTERVAL 1 YEAR) AND NOW())
                    GROUP BY app.tracking_id
                    ORDER BY app.date_approved DESC", $dateFormat, $divisionId);

    $trackingForms = $db->getAll($sql);

    if (is_array($trackingForms)) {
        if (count($trackingForms) > 0) {
            // now we append the required approvals for display
            foreach ($trackingForms as $key=>$trackingForm) {
                $trackingForms[$key]['evenodd'] = $key%2 ? '' : 'odd';
                $approvals = getPendingApprovals($trackingForm['tracking_id']);
                if(is_array($approvals)) {
                    foreach($approvals as $approval) {
                        $trackingForms[$key][$approval['type']] = sprintf("%s<br/>", $approval['friendlyName']);
                    }
                }
                if($trackingForm['deadline'] == "0000-00-00") {
                    $trackingForms[$key]['deadline'] = "";
                }
            }
        }
    }

    return $trackingForms;
}

/**
 * Get pending approvals for a given tracking form
 */
function getPendingApprovals($trackingId) {
    global $db;

    // Get pending approvals - 'Commitments' and 'Conflict of Interest' only.
    $sql = sprintf("SELECT app.*, appType.friendlyName, appType.type
                    FROM `forms_tracking_approvals` AS app
                    LEFT JOIN `forms_approval_type` AS appType ON app.approval_type_id = appType.id
                    WHERE `tracking_id` = %s AND `approved` = 0 AND `approval_type_id` IN (%s, %s)
                    ORDER BY app.tracking_id, app.date_submitted"
                    , $trackingId, COMMITMENTS, DEAN_REVIEW);
    $pendingApprovals = $db->getAll($sql);
    return $pendingApprovals;
}

/**
 * Get the currently logged in user
 *
 * @return mixed - user details
 */
function getLoggedInUser() {
    global $db;

    $username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);

    if(is_array($user) == false or count($user) == 0) {
        displayBlankPage("Error","<h1>Error</h1>There was a problem finding your user record.");
        die;
    }

    return $user;
}

/**
 * Save the comments into the database
 *
 * @param $trackingId - the tracking form ID
 * @param $divisionId - the divisionId
 * @param $comments - the comments provided
 * @return int - status
 */
function SaveComments($trackingId, $divisionId, $comments) {
    //global $db;

    if ($trackingId > 0 ) {
        $trackingForm = new TrackingForm();
        $form = $trackingForm->retrieveForm($trackingId);
        foreach($trackingForm->approvals AS $approval) {
            if($approval->type == COMMITMENTS || $approval->type == DEAN_REVIEW) {
                $approval->comments = $comments;
                $status = $approval->save($divisionId);
            }
        }
    }

    return $status;
}

/**
 * Send an email to notify ORS
 */
function notifyORS($trackingId) {
    global $db;

    require_once('classes/Mail/MailQueue.php');
    require_once('classes/Mail/Email.php');

    $sql = "SELECT  track.tracking_name, track.deadline, app.comments, CONCAT(users.last_name, ', ', users.first_name) AS submitter
                    FROM `forms_tracking_approvals` AS app
                    LEFT JOIN forms_tracking AS track ON app.tracking_id = track.form_tracking_id
                    LEFT JOIN `users` ON track.user_id = users.user_id
                    WHERE app.tracking_id = " . $trackingId;

    $trackingForm = $db->getRow($sql);

    $recipient1 = 'jcameron@mtroyal.ca';
    $recipientName1 = 'Jerri-Lynne Cameron';
    $recipient2 = 'ischuyt@mtroyal.ca';
    $recipientName2 = 'Ian Schuyt';
    $subject = sprintf('[TID-%s] %s\'s tracking form approved by Dean', $trackingId, $trackingForm['submitter']);
    $emailBody = sprintf('A tracking form was approved online :

Tracking ID : %s
Title : "%s"
Submitter : %s
Dean Comments : %s

', $trackingId, $trackingForm['tracking_name'], $trackingForm['submitter'], strip_tags($trackingForm['comments']));

    $email1 = new Email(
        $recipient1,
        $recipientName1,
        $subject,
        $emailBody
    );
    $email2 = new Email(
        $recipient2,
        $recipientName2,
        $subject,
        $emailBody);

    $emails = array($email1, $email2);

    $mailQueue = new MailQueue($emails);
    $mailQueue->queueAllMail();
}