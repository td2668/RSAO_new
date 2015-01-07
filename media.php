<?php
/**
* This file is used to load the media page, the $_GET['page'] parameter is required.
*/
/***********************************
* INCLUDES
************************************/
require_once('includes/global.inc.php');

/***********************************
* CONFIGURATION
************************************/
$page = (isset($_GET["page"])) ? CleanString($_GET["page"]) : 'internal';

$mediaFilePath = $configInfo["file_root"] . '/media';
$mediaUrlPath = $configInfo["url_root"] . '/media';

$numListRows = 10;

$mrAction = (isset($_REQUEST["mr_action"])) ? CleanString($_REQUEST["mr_action"]) : '';
$mediaId = (isset($_GET["media_id"])) ? mysql_real_escape_string(CleanString($_GET["media_id"])) : false;
$nextPageFlag = (isset($_REQUEST["next_flag"])) ? CleanString($_REQUEST["next_flag"]) : false;
//PrintR($_REQUEST);

/***********************************
* MAIN
************************************/



switch ($mrAction) {

    case 'detail':
        $tmpl = loadPage('media');
        $tmpl->AddVar('PAGE','MAIN_HEADING','Media Detail');
        if (isset($mediaId) && $mediaId > 0) {
            $sql = "SELECT * FROM media WHERE media_id = {$mediaId} LIMIT 1";
            $query = mysql_query($sql);
            if ($query) {
                if (mysql_num_rows($query) > 0) {
                    $mediaData = mysql_fetch_assoc($query);
                    // set up the details for the template
                    $tmpl->AddVar('MEDIA_DETAIL','DISPLAY','1');
                    $tmpl->AddVar('MEDIA_DETAIL','title',$mediaData['title']);
                    $tmpl->AddVar('MEDIA_DETAIL','date',$mediaData['date']);
                    $tmpl->AddVar('MEDIA_DETAIL','date_added',$mediaData['date_added']);
                    $tmpl->AddVar('MEDIA_DETAIL','synopsis',$mediaData['synopsis']);
                    $tmpl->AddVar('MEDIA_DETAIL','description',$mediaData['description']);
                    $tmpl->AddVar('PAGE','MAIN_HEADING',$mediaData['title']);
                    // set up the media for the template
                    if ($mediaData['movie_name'] != '') {
                        // embedd the video
                        $mediaMarkup = '<div id="media_view_' . $mediaData['media_id'] . '"></div>' . "\n";
                        $mediaMarkup .= '<script type="text/javascript">$(document).ready(function() { ViewMedia(' . $mediaData['media_id'] .', \'' . $mediaData['movie_name'] . '\', 0); });</script>' . "\n";
                        $tmpl->AddVar('MEDIA_DETAIL','MEDIA_MARKUP',$mediaMarkup);
                    } else if ($mediaData['embed_code'] != '') {
                        // use the embed code
                        $tmpl->AddVar('MEDIA_DETAIL','MEDIA_MARKUP',$mediaData['embed_code']);
                    } else if ($mediaData['external_link'] != '') {
                        // add an external link
                        $tmpl->AddVar('MEDIA_DETAIL','MEDIA_MARKUP','<a target="_blank" href="' . $mediaData['external_link'] . '">View this external link: ' . $mediaData['external_link'] . '</a>');
                    } else {
                        // no media?
                        $tmpl->AddVar('MEDIA_DETAIL','MEDIA_MARKUP','No media associated with this record.');
                    } // if
                    // get the soundtrack
                    if ($mediaData['soundtrack_name'] != '') {
                        $tmpl->AddVar('MEDIA_DETAIL','SOUNDTRACK_MARKUP',$mediaData['soundtrack_name']);
                    } else {
                        $tmpl->AddVar('MEDIA_DETAIL','SOUNDTRACK_MARKUP','No soundtrack available.');
                    } // if

                    // get the associated files for the template
                    $sql = "SELECT * FROM media_attachment WHERE media_id = {$mediaId}";
                    if ($query) {
                        if (mysql_num_rows($query) > 0) {

                        } else {
                            // no attachments found
                        } // if
                    } else {
                        trigger_error("media.php: error in media attachment query: {$sql}", E_USER_ERROR);
                    } // if

                } else {
                    $tmpl->AddVar('PAGE','STATUS','The specified video could not be found.');
                } // if
            } else {
                $tmpl->AddVar('PAGE','STATUS','The specified video could not be found (an error occurred).');
                trigger_error("media.php: error in media detail query: {$sql}", E_USER_ERROR);
            } // if
        } else {
            $tmpl->AddVar('PAGE','STATUS','The specified video could not be found (an error occurred).');
            trigger_error("media.php: invalid media id received", E_USER_ERROR);
        } // if
        break;
    default:
        $tmpl = loadPage('media');
        $tmpl->AddVar('PAGE','MAIN_HEADING','Media Clips');
        // get the most recent 10 videos
        $sql = "SELECT * FROM media WHERE 1 ORDER BY date_added LIMIT {$numListRows}";
        $query = mysql_query($sql);
        if ($query) {
            if (mysql_num_rows($query) > 0) {
                $mediaList = array();
                while ($mediaData = mysql_fetch_assoc($query)) {
                    $mediaRowData = $mediaData;
                    $mediaRowData['media_markup'] = ($mediaData['movie_name'] != '') ? $mediaUrlPath . '/' . $mediaData['movie_name'] . ' <a href="javascript:ViewMedia(' . $mediaData['media_id'] . ', \'' . $mediaData['movie_name'] . '\',1);">View Media</a>' : '';
                    $mediaRowData['external_link_markup'] = ($mediaData['external_link'] != '') ? '<a href="' . $mediaData['external_link'] . '" target="_blank">' . $mediaData['external_link'] . '</a><br />' : '';
                    $mediaRowData['embed_markup'] = ($mediaData['embed_code'] != '') ?
                        '<br /><a href="javascript:ViewEmbed(' . $mediaData['media_id'] . ');">
                        View Media</a><br />
                        <div id="embed_view_' . $mediaData['media_id'] . '"></div>
                        <div id="embed_code_' . $mediaData['media_id'] . '"></div><br />' : '';
                    $mediaRowData['soundtrack_markup'] = ($mediaData['soundtrack_name'] != '') ? '<a href="' . $mediaUrlPath . '/' . $mediaData['soundtrack_name'] . '" target="_blank">Soundtrack</a><br />' : '';
                    $mediaRowData['display'] = 1;
                    $mediaRowData['media_url_path'] = $mediaUrlPath;
                    // check for no thumbnail
                    if ($mediaRowData['image_name'] == '') $mediaRowData['image_name'] = 'spacer.gif';
                    $mediaList[] = $mediaRowData;
                } // while
                $tmpl->addRows("MEDIA_LIST",$mediaList);
            } else {
                $tmpl->AddVar('PAGE','STATUS','No videos found.');
            } // if
        } else {
            $tmpl->AddVar('PAGE','STATUS','No videos found (an error occurred).');
            trigger_error("media.php: error in media list query: {$sql}", E_USER_ERROR);
        } // if
        break;
} // switch

/*
if (sessionLoggedin() == true) {

    $userId = GetVerifyUserId();
    if ($userId == false || $userId < 1) {
        displayBlankPage("Invalid username","<h1>Security Error</h1><p>Possible hacking attempt or session error, please contact your system administrator.</p>");
        // log an error here?

        die(1);
    }
    $tmpl = loadPage('media');
    // get the featured video
    $sql = "SELECT * FROM media WHERE feature_flag = 1 LIMIT 1";
    $query = mysql_query($sql);
    if ($query && mysql_num_rows($query) > 0) {
        $featuredData = mysql_fetch_assoc($query);
        $tmpl->AddVar('FEATURED','DISPLAY',1);
        $tmpl->AddVar('FEATURED','TITLE',$featuredData['title']);
        $tmpl->AddVar('FEATURED','DATE',$featuredData['date']);
        $tmpl->AddVar('FEATURED','DESCRIPTION',$featuredData['description']);
        $tmpl->AddVar('FEATURED','IMAGE_NAME',$featuredData['image_name']);
        $tmpl->AddVar('FEATURED','MEDIA_ID',$featuredData['media_id']);
        $tmpl->AddVar('FEATURED','MOVIE_NAME',$featuredData['movie_name']);
        $tmpl->AddVar('FEATURED','MOVIE_FILE_SIZE',$featuredData['movie_file_size']);
    } else {
        // no featured fouond or query failed
        echo 'no featured videos found!';
    } // if

    /*
    // check to see which page is being called, perform any actions as required, and then load the
    // appropriate template for display
    switch($page) {
        case "external":
            $tmpl = loadPage('media');
            // get the featured video
            $sql = "SELECT * FROM media WHERE feature_flag = 1 LIMIT 1";
            $query = mysql_query($sql);
            if ($query && mysql_num_rows($query) > 0) {
                $featuredData = mysql_fetch_assoc($query);
                $tmpl->AddVar('FEATURED','DISPLAY',1);
                $tmpl->AddVar('FEATURED','TITLE',$featuredData['title']);
                $tmpl->AddVar('FEATURED','DATE',$featuredData['date']);
                $tmpl->AddVar('FEATURED','DESCRIPTION',$featuredData['description']);
                $tmpl->AddVar('FEATURED','IMAGE_NAME',$featuredData['image_name']);
            } else {
                // no featured fouond or query failed
                echo 'no featured videos found!';
            } // if

            $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');
            break;
        case "internal":
        default:
            $tmpl = loadPage('media');
            break;
    } // switch

} else {
    $tmpl = loadPage('media');
}
    */

$tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>'
    . "\n" . '<script type="text/javascript" src="/includes/mediaplayer/swfobject.js"></script>'
    . "\n" . '<script type="text/javascript" src="/js/playback.js"></script>');

// display the template to the user
$tmpl->displayParsedTemplate();

exit;

/***********************************
* FUNCTIONS
************************************/

function CleanPostForMysql($target) {
    return mysql_real_escape_string($target);
} // function
?>
