<?php
/**
 * Download tracking form files (single or all files as ZIP archive)
 * User: ischuyt
 * Date: 10/06/13
 * Time: 4:34 PM
 */
include("includes/config.inc.php");
define('FILEPATH', $configInfo['tracking_docs']);

if($_REQUEST['type'] == 'single') {
    //single file download

    $filename = urldecode($_REQUEST['filename']);
    $filename = str_replace(array('\\','/',':','*','?','"','<','>','|'),'', $filename);  //strip invalid characters
    $trackingId = $_REQUEST['form_tracking_id'];
    $userId = $_REQUEST['userid'];

    $path = FILEPATH . $userId . '/' . $trackingId . '/' . $filename;


    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false); // required for certain browsers
    header('Content-Type: application/pdf');

    header('Content-Disposition: attachment; filename="'. $filename . '";');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($path));

    readfile($path);

    exit;

} elseif($_REQUEST['type'] == 'all') {
    //zip all and download
    $trackingId = $_REQUEST['form_tracking_id'];
    $userId = $_REQUEST['userid'];
    $path = FILEPATH . $userId . '/' . $trackingId;

    $filename = $trackingId . "_files.zip";


    $cmd =  "zip -rj " . $path . "/" . $filename . " " . $path . "/";
    $result = exec($cmd);

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=" . $filename . ";" );
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . filesize($path . "/" . $filename));
    readfile($path . "/" . $filename);

    //deletes file when its done...
    unlink($path . "/" . $filename);
}

