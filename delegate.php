<?php

require_once('includes/global.inc.php');
require_once('includes/user_functions.php');

$isDean = $_SESSION['user_info']['dean_flag'] == true;
$isAssociateDean = $_SESSION['user_info']['associate_dean_flag'] == true;

//get the current user_id
$username = $_SESSION['loggeduser'];
$sql = "SELECT * FROM users WHERE username = '" . $username . "'";
$result = $db->getRow($sql);
if(is_array($result) == false or count($result) == 0) {
    displayBlankPage("Error","<h1>Error</h1>There was a problem finding your user record.");
    die;
}
$deanId = $result['user_id'];

if (sessionLoggedin() && ($isDean || $isAssociateDean)) {
    if($isDean) {
        $divisionId = $_SESSION['user_info']['dean_division_id'];
    } elseif($isAssociateDean) {
        $divisionId = $_SESSION['user_info']['associate_dean_division_id'];;
    }
} else {
    displayBlankPage("Error","<h1>Error</h1>You must be logged in and have Dean-level access to view this page.");
    die;
}

if(isset($_POST['delegateId'])) {
    if(isset($deanId)) {
        $sql="update divisions set dean=" . $_POST['delegateId'] . " where dean=" . $deanId;
        $result = $db->Execute($sql);
        if(!$result) {
            echo ('Update failed.  Unable to apply delegate access');
        }
    } else {
        echo "Unable to apply delegate access.  Cannot determine user ID";
    }
  }

$tmpl = loadPage("delegate", 'Assign Delegate');

$select = getFacultySelectSpecified(0);
$tmpl->addVar('delegate', 'select', $select);

$tmpl->displayParsedTemplate('page');