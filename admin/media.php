<?php
include_once("includes/config.inc.php");
include_once("includes/functions-required.php");
include_once("includes/image-functions.php");
include_once("includes/class-template.php");

$mediaFilePath = $configInfo["file_root"] . '/media';
$mediaUrlPath = $configInfo["url_root"] . '/media';
$template = new Template;
$output = '';
$formAction = (isset($_REQUEST['form_action'])) ? $_REQUEST['form_action'] : '';
$mediaId = (isset($_GET["media_id"])) ? mysql_real_escape_string(intval($_GET["media_id"])) : false;

// check for ajax code
if (isset($_REQUEST['get_embed_code']) && isset($_REQUEST['media_id'])) {
    // this is an ajax call
    $mediaId = mysql_real_escape_string(intval($_REQUEST['media_id']));
    $query = mysql_query("SELECT `embed_code` FROM `media` WHERE media_id = {$mediaId} LIMIT 1");
    if ($query) {
        $data = mysql_fetch_assoc($query);
        echo $data['embed_code'];
    } else {
        echo 0;
    } // if
    exit;
} // if

$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxFileUploadSize = ($uploadMaxFilesize > $postMaxSize) ? $postMaxSize : $uploadMaxFilesize;
//echo "<p>max file size is {$uploadMaxFilesize} and post max size is {$postMaxSize}</p>";

//-- Header File
$additionalHeaderItems = '    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>' . "\n";
require_once("html/header.html");

//echo'<pre>';print_r($_FILES);echo'</pre>';
//echo'<pre>';print_r($_REQUEST);echo'</pre>';

//ADD MEDIA
if ($formAction == 'Add' || $formAction == 'Update') {

    // upload the media files, if present
    $fileFields = array('image_name' => array(),'movie_name' => array(),'soundtrack_name' => array());
    foreach ($fileFields AS $fileColumnName => $data) {
        //echo "<p>Processing file data:</p>";
        //echo'<pre>';print_r($_FILES['original_' . $fileColumnName]);echo'</pre>';
   	    if (is_uploaded_file($_FILES['original_' . $fileColumnName]['tmp_name'])) {
            //parse the file name and create the new filename
            $fileFields[$fileColumnName]['original_filename'] = $_FILES['original_' . $fileColumnName]['name'];
		    $fileDetail = explode(".", $_FILES['original_' . $fileColumnName]['name']);
            $fileFields[$fileColumnName]['file_size'] = filesize($_FILES['original_' . $fileColumnName]['tmp_name']);
            $fileFields[$fileColumnName]['extension'] = $fileDetail[1];
		    $fileFields[$fileColumnName]['new_fileprefix'] = $fileColumnName . "_" . time();
            $fileFields[$fileColumnName]['new_filename'] = $fileFields[$fileColumnName]['new_fileprefix'] . '.' . $fileFields[$fileColumnName]['extension'];
		    $copyStatus = move_uploaded_file($_FILES['original_' . $fileColumnName]['tmp_name'], $mediaFilePath . '/' . $fileFields[$fileColumnName]['new_filename'] ); // copy the file in to the media directory
            if ($copyStatus) {
                //echo "<p>Copied {$_FILES['original_' . $fileColumnName]['tmp_name']} to {$mediaFilePath}/{$fileFields[$fileColumnName]['new_filename']}</p>";
            } else {
                echo "<p>Failed to copy {$_FILES['original_' . $fileColumnName]['tmp_name']} to {$mediaFilePath}/{$fileFields[$fileColumnName]['new_filename']}</p>";
            } // if
        } else {
            $fileFields[$fileColumnName]['original_filename'] = 'not uploaded';
            $fileFields[$fileColumnName]['file_size'] = '';
            $fileFields[$fileColumnName]['extension'] = '';
            $fileFields[$fileColumnName]['new_fileprefix'] = '';
            $fileFields[$fileColumnName]['new_filename'] = '';
        } // if
    } // foreach

    // create 100px thumbnail for standardized viewing, but leave original file intact
    if (isset($fileFields['image_name']['new_filename']) && $fileFields['image_name']['new_filename'] != '') {
        $newCopyFilePath = $mediaFilePath . '/' . $fileFields['image_name']['new_fileprefix'] . '_100' . "." . $fileFields['image_name']['extension'];
        $copyStatus = copy($mediaFilePath . '/' . $fileFields['image_name']['new_filename'], $newCopyFilePath);
        if ($copyStatus) {
            //echo "<p>Copied {$mediaFilePath}/{$fileFields['image_name']['new_filename']} to {$newCopyFilePath}</p>";
        } else {
            echo "<p>Failed to copy {$mediaFilePath}/{$fileFields['image_name']['new_filename']} to {$newCopyFilePath}</p>";
        }
        resizeImage($newCopyFilePath, $newCopyFilePath, 100);
    } // if

    // create 300px thumbnail for standardized viewing, but leave original file intact
    if (isset($fileFields['image_name']['new_filename']) && $fileFields['image_name']['new_filename'] != '') {
        $newCopyFilePath = $mediaFilePath . '/' . $fileFields['image_name']['new_fileprefix'] . '_300' . "." . $fileFields['image_name']['extension'];
        $copyStatus = copy($mediaFilePath . '/' . $fileFields['image_name']['new_filename'], $newCopyFilePath);
        if ($copyStatus) {
            //echo "<p>Copied {$mediaFilePath}/{$fileFields['image_name']['new_filename']} to {$newCopyFilePath}</p>";
        } else {
            echo "<p>Failed to copy {$mediaFilePath}/{$fileFields['image_name']['new_filename']} to {$newCopyFilePath}</p>";
        }
        resizeImage($newCopyFilePath, $newCopyFilePath, 300);
    } // if

    // upload the additional files, if present
    // 20090611 CSN TO DO

    // clean the data
    $cleanInput = array();
    foreach ($_REQUEST AS $key => $value) {
        $cleanInput[$key] = mysql_real_escape_string($value);
    } // foreach

    // save the data (insert or update depending on case)
    $featureflag = (isset($_REQUEST['feature'])) ? '1' : '0';
    $publicflag = (isset($_REQUEST['public'])) ? '1' : '0';
    $internalflag = (isset($_REQUEST['internal'])) ? '1' : '0';
    $sql = ($formAction == 'Add') ? "INSERT INTO media SET " : "UPDATE media SET ";
    $sql .= "
        `media_type_id` = '',
        `date_added` = '" . date('Y-m-d') . "',
        `date` = '{$cleanInput['date']}',
        `title` = '{$cleanInput['title']}',
        `synopsis` = '{$cleanInput['synopsis']}',
        `description` = '{$cleanInput['description']}',
        `embed_code` = '{$cleanInput['embed_code']}',
        `external_link` = '{$cleanInput['external_link']}',
        `feature_flag` = '{$featureflag}',
        `public_flag` = '{$publicflag}',
        `internal_flag` = '{$internalflag}'
    ";
    // only update files if they have been changed
    $sql .= ($fileFields['image_name']['new_filename'] != '') ? "    ,`image_name` = '{$fileFields['image_name']['new_filename']}'" : '';
    $sql .= ($fileFields['image_name']['original_filename'] != '') ? "    ,`original_image_name` = '{$fileFields['image_name']['original_filename']}'" : '';
    $sql .= ($fileFields['movie_name']['new_filename'] != '') ? "    ,`movie_name` = '{$fileFields['movie_name']['new_filename']}'" : '';
    $sql .= ($fileFields['movie_name']['original_filename'] != '') ? "    ,`original_movie_name` = '{$fileFields['movie_name']['original_filename']}'" : '';
    $sql .= ($fileFields['movie_name']['file_size'] != '') ? "    ,`movie_file_size` = '{$fileFields['movie_name']['file_size']}'" : '';
    $sql .= ($fileFields['soundtrack_name']['new_filename'] != '') ? "    ,`soundtrack_name` = '{$fileFields['soundtrack_name']['new_filename']}'" : '';
    $sql .= ($fileFields['soundtrack_name']['original_filename'] != '') ? "    ,`original_soundtrack_name` = '{$fileFields['soundtrack_name']['original_filename']}'" : '';
    $sql .= ($formAction == 'Add') ? "" : " WHERE media_id = {$cleanInput['media_id']} ";
    $query = mysql_query($sql);
    if ($query) {
        $success = " <strong>Media saved successfully.</strong>";
        //echo 'insert worked';
    } else {
        echo 'insert failed: ' . mysql_error() . ' for sql: ' . $sql;
    } // if
    $_REQUEST['section'] = 'view';

// DELETE MEDIA
} else if ($formAction == 'delete' && $mediaId > 0) {
    // delete the media
    $success = '';
    $query = mysql_query("DELETE FROM media WHERE media_id = {$mediaId}");
    if ($query) {
        $success .= " <strong>Media deleted successfully.</strong>";
        // delete any media associations
        $query = mysql_query("DELETE FROM media_associated WHERE media_id = {$mediaId}");
        if ($query) {
            $success .= " <strong>Media associations deleted successfully.</strong>";
        } else {
            $success .= " <strong>Media associations were not deleted, an error occured.</strong>";
            trigger_error('media.php: Media associations delete failed: ' . mysql_error() . ' for id: ' . $mediaId, E_USER_ERROR);
        } // if
        // delete any media attachments
        $query = mysql_query("DELETE FROM media_attachment WHERE media_id = {$mediaId}");
        if ($query) {
            $success .= " <strong>Media media_attachments deleted successfully.</strong>";
        } else {
            $success .= " <strong>Media media_attachments were not deleted, an error occured.</strong>";
            trigger_error('media.php: Media media_attachments delete failed: ' . mysql_error() . ' for id: ' . $mediaId, E_USER_ERROR);
        } // if
    } else {
        $success .= " <strong>Media was not deleted, an error occured.</strong>";
        trigger_error('media.php: Media delete failed: ' . mysql_error() . ' for id: ' . $mediaId, E_USER_ERROR);
    } // if
    $_REQUEST['section'] = 'view';
}

// VIEW MEDIA
if (isset($_REQUEST['section'])) {
	if(!isset($success)) $success="";
	switch($_REQUEST['section']){
		case "view":
            $sql = "SELECT m.*
                FROM media AS m
                LEFT JOIN media_attachment AS ma ON ma.media_id = m.media_id
                WHERE 1
                ORDER BY media_id ASC
            ";
            $query = mysql_query($sql);
            if ($query) {
                while ($mediaData = mysql_fetch_assoc($query)) {
                    $mediaPathMarkup = ($mediaData['movie_name'] != '') ? $mediaUrlPath . '/' . $mediaData['movie_name'] . ' <a href="javascript:ViewMedia(' . $mediaData['media_id'] . ', \'' . $mediaData['movie_name'] . '\',1);">View</a>' : 'none';
                    $externalLinkMarkup = ($mediaData['external_link'] != '') ? '<a href="' . $mediaData['external_link'] . '" target="_blank">' . $mediaData['external_link'] . '</a>' : 'none';
                    $embedCodeMarkup = ($mediaData['embed_code'] != '') ?
                        '<br /><a href="javascript:ViewEmbed(' . $mediaData['media_id'] . ',1);">
                        Preview Media</a><br />
                        <div id="embed_view_' . $mediaData['media_id'] . '"></div>
                        <div id="embed_code_' . $mediaData['media_id'] . '"><code>' . str_replace('>','&gt;',str_replace('<','&lt;',$mediaData['embed_code'])) . '</code></div>' : '';
                    // get the association details
                    $sql2 = "SELECT * FROM media_associated WHERE media_id = {$mediaData['media_id']} ORDER BY table_name, object_id";
                    $query2 = mysql_query($sql2);
                    if ($query2) {
                        $associations = array();
                        while ($associatedData = mysql_fetch_assoc($query2)) {
                            $associations[] = $associatedData['table_name'] . ': ' . $associatedData['object_details'];
                        } // while
                    } else {
                        // error
                    } // if
                    $output .= '
                        <tr>
                            <td valign="top" bgcolor="#E09731">
                                <a style="color:white" href="/media.php?section=update&media_id=' . $mediaData['media_id'] . '"><b>Update</b></a>
                                <a style="color:white" href="/media-associate.php?section=associate&media_id=' . $mediaData['media_id'] . '"><b>Associate</b></a>
                                <br />
                                <a style="color:white" href="javascript:ConfirmDeleteMedia(\'/media.php?form_action=delete&section=view&media_id=' . $mediaData['media_id'] . '\');"><b>Delete</b></a>
                            </td>
                            <td valign="top" align="left"><img src="' . $mediaUrlPath . '/' . $mediaData['image_name'] . '" width="100" border="0" /></td>
                            <td valign="top" align="left">
                                Thumbnail: <a href="' . $mediaUrlPath . '/' . $mediaData['image_name'] . '" target="_blank">' . $mediaData['original_image_name'] . '</a><br />
                                Media: ' . $mediaData['original_movie_name'] . '<br />
                                Media Path: ' . $mediaPathMarkup . '<br />
                                Soundtrack: <a href="' . $mediaUrlPath . '/' . $mediaData['soundtrack_name'] . '" target="_blank">' . $mediaData['original_soundtrack_name'] . '</a><br />
                                Embed Code: ' . $embedCodeMarkup . '<br />
                                External Link: ' . $externalLinkMarkup . '<br />
                            </td>
                            <td valign="top" align="left" width="150">
                                Title: <strong>' . $mediaData['title'] . '</strong><br />
                                Date: ' . $mediaData['date'] . '<br />
                                Date Added: ' . $mediaData['date_added'] . '<br />
                                Synopsis:<br />' . $mediaData['synopsis'] . '<br />
                            </td>
                            <td valign="top" align="left">' . implode('<br />',$associations) . '</td>
                            <td valign="top" align="left">' . $mediaData['description'] . '</td>
                        </tr>
                        <tr>
                            <td><img src="/images/spacer.gif" width="1" height="1" /></td>
                            <td><img src="/images/spacer.gif" width="1" height="1" /></td>
                            <td colspan="3"><div id="media_view_' . $mediaData['media_id'] . '"></div></td>
                        </tr>';
                } // while
                $hashArray = array('success'=>$success, 'output'=>$output);
                $filename = 'templates/template-media_view.html';
            } else {
                // error in query
                echo "Error in query: {$sql}";
                $hashArray = array('title'=>"Media");
                $filename = 'includes/error-no_records.html';
            } // if
			$parsed_html_file = $template->loadTemplate($filename,$hashArray,"HTML");
			echo $parsed_html_file;
            break;
		case "add":
			$hashArray = array('action' => 'Add', 'success' => $success, 'maxfilesize' => $maxFileUploadSize);
			$filename = 'templates/template-media_edit.html';
			$parsed_html_file = $template->loadTemplate($filename,$hashArray,"HTML");
			echo $parsed_html_file;
            break;
		case "update":
            $hashArray = array('action' => 'Update', 'success' => $success, 'maxfilesize' => $maxFileUploadSize);
            if (isset($_REQUEST['media_id']) && $_REQUEST['media_id'] > 0) {
                $mediaId = mysql_real_escape_string(intval($_REQUEST['media_id']));
                // get the data
                $sql = "SELECT m.*
                    FROM media AS m
                    LEFT JOIN media_attachment AS ma ON ma.media_id = m.media_id
                    WHERE m.media_id = {$mediaId}
                    LIMIT 1
                ";
                $query = mysql_query($sql);
                if ($query) {
                    $mediaData = mysql_fetch_assoc($query);
                    foreach ($mediaData AS $key => $value) $hashArray[$key] = $value;
                    if ($hashArray['feature_flag'] == 1) {
                        $hashArray['feature_checked'] = 'checked';
                    } else {
                        $hashArray['feature_checked'] = '';
                    } // if
                    if ($hashArray['public_flag'] == 1) {
                        $hashArray['public_checked'] = 'checked';
                    } else {
                        $hashArray['public_checked'] = '';
                    } // if
                    if ($hashArray['internal_flag'] == 1) {
                        $hashArray['internal_checked'] = 'checked';
                    } else {
                        $hashArray['internal_checked'] = '';
                    } // if
                    //echo'<pre>';print_r($hashArray);echo'</pre>';
                } else {
                    echo "Media data query failed: {$sql}";
                } // if
            } else {
                // invalid id received
                echo "Invalid media id received.";
            } // if
            // load the template
			$filename = 'templates/template-media_edit.html';
			$parsed_html_file = $template->loadTemplate($filename,$hashArray,"HTML");
			echo $parsed_html_file;
            break;
	}
}
//-- Footer File
include("templates/template-footer.html");



function DisplayFilesize($filesize){

    if(is_numeric($filesize)){
    $decr = 1024; $step = 0;
    $prefix = array('Byte','KB','MB','GB','TB','PB');

    while(($filesize / $decr) > 0.9){
        $filesize = $filesize / $decr;
        $step++;
    }
    return round($filesize,2).' '.$prefix[$step];
    } else {

    return 'NaN';
    }

}
?>