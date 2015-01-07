<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");
//$debug = 1;

$mediaUrlPath = $configInfo["url_root"] . '/media';
$tableList = array('news','projects','users');
$formAction = (isset($_REQUEST['form_action'])) ? $_REQUEST['form_action'] : '';
$mediaId = (isset($_REQUEST['media_id'])) ? mysql_real_escape_string(intval($_REQUEST['media_id'])) : '';
$associatedId = (isset($_REQUEST['associated_id'])) ? mysql_real_escape_string(intval($_REQUEST['associated_id'])) : '';

if ($formAction == 'Add' || $formAction == 'Update') {

    // clean the data
    $cleanInput = array();
    foreach ($_REQUEST AS $key => $value) {
        if (is_array($value)) {
            if (in_array($key, array('news','projects','users'))) {
                foreach($value AS $key2 => $value2) {
                    if (is_array($value2)) {
                        // skip it
                    } else {
                        // this is a value
                        $cleanInput[$key][] =  mysql_real_escape_string($value2);
                    } // if
                } // foreach
            } // if
        } else {
            // just a value
            $cleanInput[$key] = mysql_real_escape_string($value);
        } // if
    } // foreach

    // remove any associations for this media
    $sql = "DELETE FROM media_associated WHERE media_id = {$mediaId}";
    $query = mysql_query($sql);
    if ($query) {
        // add the associations for each table
        foreach ($tableList AS $tableName) {
            if (isset($cleanInput[$tableName]) && is_array($cleanInput[$tableName])) {
                foreach ($cleanInput[$tableName] AS $objectId) {

                    // get the object details
                    $objectDetails = '';
                    $cleanId = mysql_real_escape_string(intval($objectId));
                    switch($tableName) {
                        case 'news':
                            $query = mysql_query("SELECT title FROM news WHERE news_id = {$cleanId}");
                            if ($query) {
                                $data = mysql_fetch_assoc($query);
                                $objectDetails = $data['title'];
                            } // if
                            break;
                        case 'projects':
                            $query = mysql_query("SELECT name FROM projects WHERE project_id = {$cleanId}");
                            if ($query) {
                                $data = mysql_fetch_assoc($query);
                                $objectDetails = $data['name'];
                            } // if
                            break;
                        case 'users':
                            $query = mysql_query("SELECT CONCAT(first_name,' ',last_name) AS full_name FROM users WHERE user_id = {$cleanId}");
                            if ($query) {
                                $data = mysql_fetch_assoc($query);
                                $objectDetails = $data['full_name'];
                            } // if
                            break;
                    } // switch

                    // insert the association
                    $sql = "INSERT INTO media_associated SET media_id = {$mediaId}, object_id = {$objectId}, table_name = '{$tableName}', object_details = '{$objectDetails}'";
                    $query = mysql_query($sql);
                    if ($query) {
                        // success
                    } else {
                        echo 'insert failed: ' . mysql_error() . ' for sql: ' . $sql;
                        trigger_error("media-associate.php: failed to insert media association: {$sql}", E_USER_ERROR);
                    } // if

                } // foreach
            } else {

            } // if
        } // foreach
    } else {
        echo 'insert failed: ' . mysql_error() . ' for sql: ' . $sql;
    } // if
    $_REQUEST['section'] = 'view';

    // redirect to media page?
    header("Location: /media.php?section=view");
    exit;

// DELETE ASSOCIATION
} else if ($formAction == 'delete') {
    $sql = "DELETE FROM media_associated WHERE associated_id = {$associatedId}";
    $query = mysql_query($sql);
    if ($query) {
        $success = "<strong>Association deleted</strong>";
    } else {
        $success = "<strong>Association not deleted - an error occurred</strong>";
        trigger_error("media-associate.php: failed to delete association: {$sql}",E_USER_ERROR);
    } // if
    $_REQUEST['section'] = 'view';
}

//-- Header File
$additionalHeaderItems = '    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>' . "\n";
require_once("templates/template-header.html");

// VIEW ASSOCIATIONS
if (isset($_REQUEST['section'])) {
    if(!isset($success)) $success="";
    switch($_REQUEST['section']){
        case "associate":
            // get the existing associations
            $sql2 = "SELECT * FROM media_associated WHERE media_id = {$mediaId} ORDER BY table_name, object_id";
            $query2 = mysql_query($sql2);
            if ($query2) {
                $associations = array();
                $associations['news'] = array();
                $associations['projects'] = array();
                $associations['users'] = array();
                while ($associatedData = mysql_fetch_assoc($query2)) {
                    $tableName = $associatedData['table_name'];
                    if (in_array($tableName,array('news','projects','users'))) $associations[$tableName][] = $associatedData['object_id'];
                } // while
            } else {
                // error
            } // if
            //-- News
            $values = mysqlFetchRows("news", "1 ORDER BY title");
            $news = "";
            if (is_array($values)) {
                foreach($values as $index) {
                    $selected = (in_array($index['news_id'], $associations['news'])) ? 'SELECTED' : '';
                    $news .= '<option value="' . $index['news_id'] . '"' . $selected . '>' . $index['title'] . '</option>';
                } // foreach
            } // if
            //-- Projects
            $values = mysqlFetchRows("projects", "1 ORDER BY name");
            $projects = "";
            if (is_array($values)) {
                foreach($values as $index) {
                    $selected = (in_array($index['project_id'], $associations['projects'])) ? 'SELECTED' : '';
                    $projects .= '<option value="' . $index['project_id'] . '"' . $selected . '>' . $index['name'] . '</option>';
                } // foreach
            } // if
            if (is_array($values)) foreach($values as $index) $projects .= "<option value='$index[project_id]'>$index[name]</option>";
            //-- Users
            $values = mysqlFetchRows("users", "1 AND last_name != '' ORDER BY last_name,first_name");
            $users = "";
            if (is_array($values)) {
                foreach($values as $index) {
                    $selected = (in_array($index['user_id'], $associations['users'])) ? 'SELECTED' : '';
                    $users .= '<option value="' . $index['user_id'] . '"' . $selected . '>' . $index['last_name'] . ', ' . $index['first_name'] . '</option>';
                } // foreach
            } // if
            //-- Media
            /*
            $values = mysqlFetchRows("media", "1 order by date_added");
            if(is_array($values)) {
                $media = "";
                $i=0;
                foreach($values as $index) {
                    // Use the following line to hide all pictures of users already associated.
                    if($i==10) {
                        $pictures .= "</tr><tr>\r\n";
                        $i=0;
                    }
                    ++$i;
                    $thumb_file = $picture_path."thumb_".$index['file_name'];
                    $file = $picture_path.$index['file_name'];
                    $size = getimagesize($file);
                    $pictures .= "<td valign='bottom' align='center' width='10%'>\r\n
                                  <a href='javascript:openPictureWin(\"$file\", \"$size[0]\", \"$size[1]\")'>\r\n
                                  <img src='$thumb_file' bordercolor='black' border='1'></a><br>\r\n
                                <b style='font-size:9px; font-family: Verdana, Arial, Helvetica, sans-serif;'>$index[caption]</b><br><img src'/images/private/spacer.gif' width='1' height='5'><br>
                                  <input type='checkbox' name='pictures[]' value='$index[picture_id]'></td>\r\n";
                }
            }
            else $pictures = "<strong>There are currently no pictures in the database.</strong>";
            */
            $hashArray = array('action' => 'Add', 'success'=>$success, 'news'=>$news, 'projects'=>$projects, 'users'=>$users, 'media_id' => $mediaId);
            $filename = 'templates/template-media-associate_update.html';
            $parsed_html_file = $template->loadTemplate($filename,$hashArray,"HTML");
            echo $parsed_html_file;
            break;

        case "view":
        default:
            $sql = "SELECT ma.*, m.*
                FROM media_associated AS ma
                LEFT JOIN media AS m ON m.media_id = ma.media_id
                WHERE 1
                ORDER BY ma.table_name ASC, ma.object_id DESC, ma.media_id DESC
            ";
            $query = mysql_query($sql);
            if ($query) {
                $output = '';
                while ($mediaData = mysql_fetch_assoc($query)) {
                    $output .= '
                        <tr>
                            <td valign="top" bgcolor="#E09731"><a style="color:white" href="/media-associate.php?section=view&form_action=delete&associated_id=' . $mediaData['associated_id'] . '"><b>Delete</b></a></td>
                            <td valign="top" align="left"><img src="' . $mediaUrlPath . '/' . $mediaData['image_name'] . '" width="100" border="0" /></td>
                            <td valign="top" align="left">
                                Title: <strong>' . $mediaData['title'] . '</strong><br />
                                Date: ' . $mediaData['date'] . '<br />
                                Date Added: ' . $mediaData['date_added'] . '<br />
                                Synopsis:<br />' . $mediaData['synopsis'] . '<br />
                            </td>
                            <td valign="top" align="left" width="150">
                                ' . $mediaData['object_details'] . '<br />
                                Table: <strong>' . $mediaData['table_name'] . '</strong><br />
                                ID: ' . $mediaData['object_id'] . '
                            </td>
                        </tr>';
                } // while
                $hashArray = array('success'=>$success, 'output'=>$output);
                $filename = 'templates/template-media_associate_view.html';
            } else {
                // error in query
                echo "Error in query: {$sql}";
                $hashArray = array('title'=>"Media");
                $filename = 'includes/error-no_records.html';
            } // if
            $parsed_html_file = $template->loadTemplate($filename,$hashArray,"HTML");
            echo $parsed_html_file;
            break;
    } // switch
}

//-- Footer File
include("templates/template-footer.html");
?>