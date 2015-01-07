<?php
/**
 * Modify a HREB application
 * User: ischuyt
 * Date: 10/18/13
 * Time: 8:05 AM
 */

include("includes/config.inc.php");
include("includes/hreb-functions.inc.php");

/*echo "<pre>";
var_dump($_POST);*/

$action = strtolower($_REQUEST['action']); // the action for this page arrives in the REQUEST
$pageTitle = "MRU Ethics Management";

$tmpl=loadPage("hreb-modify", $pageTitle);

// Assign each post value to a variable in the view
foreach($_POST as $key=>$postValues) {
    $tmpl->addVar('page', $key, $postValues);
}


// What we do depends on what action was passed in the POST
switch($action) {
    case "new":
        $tmpl->setAttribute('edit','visibility','hidden');
        $tmpl->setAttribute('new','visibility','visible');
        $pageTitle = "New Ethics Application";
        $tmpl->addVar('page','pagetitle', $pageTitle);
        $tmpl->addVar('new','ethicsnumber',generateNewEthicsNumber());  // set the default ethics number for new applications
        $tmpl->addVar('new','received', date('Y-m-d', time()));
        $tmpl->addVar('new','expiry', date('Y-m-d', strtotime('+6 months')));   // set expiry date to 6 months from today
        $tmpl->DisplayParsedTemplate();
        break;
    case "save":
        $trackingId = $_GET['trackingId'];
        $success = saveEthics($_POST);
        if($success) {
            $msg = "Saved successfully";
            header( 'Location: hreb.php?msg=' . $msg ) ;
        } else {
            $tmpl->setAttribute('new','visibility','hidden');
            $tmpl->setAttribute('edit','visibility','hidden');
            $tmpl->addVar('page','message', "<h2 style='color:red'>Unable to save to database.</h2>");
            $tmpl->DisplayParsedTemplate();
        }
        break;
    case "edit":
        $tmpl->setAttribute('edit','visibility','visible');
        $tmpl->setAttribute('new','visibility','hidden');
        $pageTitle = "Modify Ethics Application";
        $tmpl->addVar('page','pagetitle', $pageTitle);
        $hrebObj = new hreb($_GET['ethicsnum']); // create an ethics object

        // append any modifications to the template
        $mods = $hrebObj->getModsAsArray();
        //$tmpl->addRows('mods_list', $mods);

        $ethicsVals = get_object_vars($hrebObj);  // extract the variables for the view
        foreach($ethicsVals as $key=>$val) {
            if($key != 'mods') {  // don't include the mods since we already added them to the template
                $tmpl->addVar('edit', $key, $val);
            }
        }

        $tmpl->DisplayParsedTemplate();
        break;
    case "revision":
        $hrebObj = new hreb($_GET['ethicsnum']); // create an ethics object
        $hrebObj->newRevision();
        header( 'Location: hreb.php' ) ;
        break;
    case "delete":
        $hrebObj = new hreb($_GET['ethicsnum']); // create an ethics object
        $success = $hrebObj->delete();
        if($success) {
            $msg = "Deleted successfully";
        } else {
            $msg = "Sorry, failed to remove application from the database.";
        }
        header( 'Location: hreb.php?msg=' . $msg) ;
        break;
    case "savemod": // This is a JSON request
        $json = json_decode(file_get_contents('php://input'), TRUE);
        foreach($json AS $j) {
            $modification = new Modification();
            $modification->initializeFromJSON($json[0]);
            $modification->save();
        }
        header( 'Location: hreb.php') ;
        break;
    case "removemod": // This is a JSON request
        $modId = $_REQUEST['id'];
        $modification = new Modification($modId);
        $modification->delete();
        break;
    case "gettracking": // This is a JSON request
        $trackingId = $_GET['trackingId'];
        header("Content-type: application/json");
        echo getTrackingJSON($trackingId);
        break;
    case "getstatuses": // Get the available modification statuses for HREB
        header("Content-type: application/json");
        echo json_encode(getAvailableUpdates());
        break;
    case "getexistingmods": // Get the existing modification for an HREB
        header("Content-type: application/json");
        $hrebObj = new hreb($_GET['ethicsnum']); // create an ethics object
        $mods = $hrebObj->getModsAsJSON();
        echo json_encode($mods);
        break;
    default:
        $tmpl->setAttribute('edit','visibility','hidden');
        $tmpl->setAttribute('new','visibility','hidden');
        $tmpl->addVar('page','pagetitle', $pageTitle);
        $tmpl->addVar('page','message', "<h2 style='color:red'>Cannot find page action to render.</h2>");
        $tmpl->DisplayParsedTemplate();
        break;
}
