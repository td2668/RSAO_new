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

    $path = FILEPATH . $userId . '/' . $trackingId;
    $file= $path . '/' . $filename;

    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    } else {
        echo 'Unable to process file for download';
        die();
    }
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

