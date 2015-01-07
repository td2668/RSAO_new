<?php

include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/hreb-functions.inc.php");

$tmpl=loadPage("hreb", 'Manage HREB Applications');

// ---- EDIT ---- //
if(isset($_REQUEST['section'])) {
    if($_REQUEST['section'] == 'edit') {
        if(isset($_REQUEST['ethicsnum'])) {
            $ethicsNum = mysql_real_escape_string($_REQUEST['ethicsnum']);
            if(isset($_REQUEST['status'])) {
                updateStatus($ethicsNum, $_REQUEST['status']);
            } elseif(isset($_REQUEST['date'])) {
                updateExpiry($ethicsNum, $_REQUEST['date']);
            }
        } else {
            echo "Invalid ethics ID provided.";
        }
    }
}
// --- LIST ---//

// sort by
$sort = 'hreb.ethicsnum'; // default
if (isset($_REQUEST['sort'])) {
    $sort = $_REQUEST['sort'];
}


// sort order
if(isset($_REQUEST['dir'])) {
    $dir = getSortOrder($_REQUEST['dir']);
} else {
    $dir = 'DESC';
}
$tmpl->addVar('page','dir', $dir);

if(isset($_REQUEST['msg'])) {
    $tmpl->addVar('page','message', '<h2 style="background-color: #D3D3D3; padding:2px;">' . $_REQUEST['msg'] . "</h2>");
}


// load the tracking forms

$sql = "SELECT hreb.*, tf.tracking_name, tf.form_tracking_id, DATE_FORMAT(hreb.lastModified, '%Y-%b-%d') as lastModified
        FROM hreb
        LEFT JOIN forms_tracking AS tf ON hreb.trackingId = tf.form_tracking_id
        WHERE hreb.deleted = 0
        ORDER BY $sort {$dir}";

/*$sql = "SELECT tf.form_tracking_id, tf.tracking_name, hreb.ethicsnum, hreb.status, hreb.expiryDate, hreb.lastModified FROM forms_tracking AS tf
        LEFT JOIN hreb ON tf.form_tracking_id = hreb.trackingId
        WHERE tf.`status` IN (1,2) AND tf.human_b = 1
        ORDER BY $sort $dir";*/
/*echo($sql);
die();*/
$hrebs = $db->getAll($sql);

$statuses = getStatuses(); // get available statuses from database
//$availableUpdates = getAvailableUpdates();


foreach($hrebs AS $key=>$hreb)  {
    // generate a list of options with the correct status selected for each tracking form
    $hrebs[$key]['status_select'] = getStatusSelect($hreb['status']);

}


$tmpl->addRows('trackingList', $hrebs);

//display the template
$tmpl->DisplayParsedTemplate();


/**
 * Grab a list of status from the database
 *
 * @return string - the statuses
 */
function getStatuses() {
    global $db;

    $sql = "SELECT value, name FROM hreb_status ORDER BY value Asc";
    $statuses = $db->getAll($sql);

    return $statuses;
}

function getStatusSelect($trackingStatus) {
    global $statuses;

    $options = "";
    $selected = "";

    foreach($statuses AS $stati) {
        if($trackingStatus == (int)$stati['value']) {
            $selected = "selected";
        }
        $options .= "<option value='"  . $stati['value'] . "' " . $selected . ">" . $stati['name'] . "</option>";
        $selected = "";
    }

    return $options;

}

/**
 * Update the expiry date
 *
 * @param $ethicsNum - the ethics number
 * @param $expiryDate - the expiry date
 */
function updateExpiry($ethicsNum, $expiryDate) {
    $hreb = new hreb($ethicsNum);
    $hreb->updateExpiryDate($expiryDate);
}

/**
 * Update the status
 *
 * @param $ethicsNum - the ethics number
 * @param $status - the status
 */
function updateStatus($ethicsNum, $status) {
    $hreb = new hreb($ethicsNum);
    $hreb->updateStatus($status);
}

/**
 * Determine the sort order
 *
 * @param $requestedSortDirection
 * @return string - ASC or DESC
 */
function getSortOrder($requestedSortDirection)
{
    $dir = 'DESC'; //default
    if (isset($requestedSortDirection)) {
        $dir = $requestedSortDirection == 'DESC' ? ASC : DESC;
    }

    return $dir;
}