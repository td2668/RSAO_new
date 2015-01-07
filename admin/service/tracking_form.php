<?php
/**
 * JSON requests for Tracking Form functions
 *
 * User: ischuyt
 * Date: 21/01/14
 * Time: 7:05 PM
 */

include("../includes/config.inc.php");

$action = strtolower($_REQUEST['action']);


switch($action) {
    case 'completed':
        getTrackingFormsJSON(true, false);  // return JSON of COMPLETED tracking form objects
        break;
    case 'inprep':
        getTrackingFormsJSON(false, true);  // return JSON of INPREP tracking form objects
        break;
    case 'markcomplete':
        markComplete($_POST['trackingid']); // mark the tracking form as completed by ORS
        break;
    case 'markletter':
        markLetterSent($_POST['trackingid']); // mark the support letter as sent by ORS
        break;
    case 'submitted':
        $status = 0;
        if($_GET['status'] == 'yes') {
            $status = 1;
        } else if($_GET['status'] == 'no') {
            $status = 2;
        }
        markORSSubmitted($_POST['trackingid'], $status);
        break;
    case 'approve':
         markApproved($_POST['trackingid']);
        break;
    default:
        getTrackingFormsJSON();  // return JSON of tracking form objects
        break;
}


/**
 * Fetches all tracking forms and returns the info as a JSON
 */
function getTrackingFormsJSON($completedOnly = false, $inPrepOnly = false)
{
    $trackingForms = getTrackingForms($completedOnly, $inPrepOnly);
    $json = json_encode($trackingForms);

    header("Content-type: application/json");
    echo $json;
}

/**
 * Get all tracking forms from the database (for later display in a table via JSON)
 *
 *  Note:  We loop through the results to build the 'Actions' menu, so
 *         many of the results that append HTML below can be moved out of
 *         the query and into the loop.
 *
 * @param bool $completedOnly - only return completed tracking forms
 * @param bool $inPrepOnly - only return forms in prep
 * @return array - The tracking form fields from the DB
 */
function getTrackingForms($completedOnly = false, $inPrepOnly = false) {
    global $db;

    $where = "tracking.status = 1";
    if($completedOnly == true) {
        $where = "tracking.status = 2";
    }
    if($inPrepOnly == true)
    { $where = "tracking.status = 0";
    }

    $sql = "SELECT tracking.form_tracking_id AS id, tracking.letter_required, tracking.status, tracking.ors_submitted_status, approvals_ors.approved,
                   IFNULL(CONCAT('<a href=\'/tracking.php?section=files&id=', tracking.form_tracking_id, '\' class=\'files\'>', files.numFiles , '</a>'), '---') AS files,
                   tracking.tracking_name AS title,
                   IFNULL(DATE_FORMAT(tracking.submit_date,'%Y-%m-%d'), '---') AS submitted_on,
                   CONCAT(u1.last_name, ', ', u1.first_name) AS applicant,
                   IFNULL(departments.name, '---') AS department,
                   IFNULL(hreb_status.name, '---')  AS hreb,
                   IFNULL(ors_agency.name, '---') AS agency,
                   IFNULL(tracking.agency_name, '---') AS agency2,
                   CASE approvals_dean.approved WHEN '0' THEN 'Pending' WHEN '1' THEN 'Approved' ELSE '---' END AS dean,
                   CASE tracking.ors_submits WHEN '0' THEN '---' WHEN '1' THEN 'Yes' WHEN '2' THEN CONCAT('SUBMITTED<br>', DATE_FORMAT(tracking.ors_submitted,'%Y-%m-%d')) END AS ors_submits,
                   CASE tracking.ors_submitted_status WHEN '0' THEN CONCAT('<span id=\'ors_submitted_status-', tracking.form_tracking_id, '\'>Pending</span>') WHEN '1' THEN CONCAT('SUBMITTED<br>', DATE_FORMAT(tracking.ors_submitted,'%Y-%m-%d')) WHEN '2' THEN 'NOT REQUIRED' END AS submitted,
                   CASE tracking.letter_required WHEN '0' THEN '---' WHEN '1' THEN CONCAT('<span id=\'letter_status-', tracking.form_tracking_id, '\'>Required</span>') WHEN '2' THEN CONCAT('SENT<br>', DATE_FORMAT(tracking.letter_sent,'%Y-%m-%d')) END AS letter_status,
                   CASE tracking.funding_deadline WHEN '0000-00-00' THEN '---' ELSE tracking.funding_deadline END AS funding_deadline,
                   CASE approvals_ors.approved WHEN '0' THEN CONCAT('<span id=\'ors-', tracking.form_tracking_id, '\'>Pending</span>') WHEN '1' THEN 'Approved' ELSE CONCAT('<span id=\'ors-', tracking.form_tracking_id, '\'>---</span>') END AS ors
            FROM forms_tracking AS tracking
            LEFT JOIN (SELECT trackingId, count(*) AS numFiles FROM forms_tracking_files GROUP BY forms_tracking_files.trackingId) AS files ON tracking.form_tracking_id = files.trackingId
            LEFT JOIN users u1 ON tracking.user_id = u1.user_id
            LEFT JOIN departments ON u1.department_id = departments.department_id
            LEFT JOIN ors_agency ON tracking.agency_id = ors_agency.id
            LEFT JOIN hreb ON tracking.form_tracking_id = hreb.trackingId
            LEFT JOIN hreb_status ON hreb.status = hreb_status.value
            LEFT JOIN forms_tracking_approvals AS approvals_dean ON tracking.form_tracking_id = approvals_dean.tracking_id AND approvals_dean.approval_type_id IN (1,7)
            LEFT JOIN forms_tracking_approvals AS approvals_ors ON tracking.form_tracking_id = approvals_ors.tracking_id AND approvals_ors.approval_type_id IN (2,8)
            WHERE " . $where ."
            ORDER BY submitted_on DESC";


    try {
        $result = $db->getAll($sql);
    } catch(Exception $e) {
        echo "Unable to fetch tracking forms from database : " . $e->getMessage();
        die();
    }

    // Build and insert the action menu.
    foreach($result AS $key=>$form) {
        $icons = ""; // running list of what icons we display

        // the running list of items in the action drop-down
        $actionButton = '
                <div class="btn-group">
                  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    Actions <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu" role="menu">
                    <li><a href="http://research.mtroyal.ca/tracking-view.php?tid=' . $form["id"] . '" target="_blank">View</a></li>
                    <li class="divider"></li>';

        if($form[funding_deadline] != '---') {
            if(strtotime($form[funding_deadline]) <  strtotime('+4 days') && strtotime($form[funding_deadline]) > strtotime('now)')) {
                $icons .= "<span class='glyphicon glyphicon-time' title='Funding deadline approaching!'></span>";
            } else {
            $icons .= "<span class='glyphicon glyphicon-usd' title='Funding deadline'></span>";
            }
        }

        if( $form['letter_required'] == 1) {
            $actionButton .= '<li><a href="#" class="orsletter">Mark Letter Sent</a></li>';
            $icons .= "<span class='glyphicon glyphicon-envelope' title='ORS Support Letter Required'></span>";
            $actionButton .= '<li class="divider"></li>';
        }


        $actionButton .= $form['approved'] == 0 ? '<li><a href="#" class="approve">Mark Approved</a></li>' : '';
        $actionButton .= '<li class="divider"></li>';

        if($form['ors_submitted_status'] == 0) {
            $actionButton .= '<li><a href="#" class="submission">Submitted</a></li>';
            $actionButton .= '<li><a href="#" class="nosubmission">Submission Not Required</a></li>';
        } else {
            $icons = ''; // remove existing icons
            $icons .= "<span class='glyphicon glyphicon-ok' title='Reviewed'></span>";
        }

        $actionButton .= '<li class="divider"></li>';
        $actionButton .= $form['status'] < 2 ? '<li><a href="#" class="completed">Completed</a></li>' : '';

        $actionButton .= '</ul>
                        </div>';

        $result[$key]['actions'] = $actionButton;
        $result[$key]['icons'] = $icons;

    }

    return $result;
}

/**
 * Mark a tracking form as having been submitted by ORS
 *
 * @param $tid - the tracking form id
 * @param $status
 */
function markORSSubmitted($tid, $status) {
    global $db;

    $sql = "UPDATE forms_tracking
            SET ors_submitted_status = " . $status .", ors_submitted = NOW()
            WHERE form_tracking_id = " . $tid;

    $db->Execute($sql);

}

function markComplete($tid) {
    global $db;

    $sql = "UPDATE forms_tracking
            SET status = 2
            WHERE form_tracking_id = " . $tid;

    $db->Execute($sql);
}


/**
 * Mark the support letter has having been sent by ORS
 *
 * @param $tid - the tracking form id
 */
function markLetterSent($tid) {
    global $db;

    $sql = "UPDATE forms_tracking
            SET letter_required = 2, letter_sent = NOW()
            WHERE form_tracking_id = " . $tid;

    $db->Execute($sql);
}

/**
 * Mark the tracking form as approved
 *
 * @param $tid - the tracking form id
 */
function markApproved($tid) {
    global $db;

    $sql = "UPDATE forms_tracking_approvals SET approved = 1, date_approved = NOW() WHERE tracking_id = " . $tid . " AND approval_type_id IN (2,8)";

    $db->Execute($sql);
}