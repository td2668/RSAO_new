<?php
/**
 * Functions for working with projects
 */

require_once('includes/global.inc.php');

/*
 * Get the url of a project's picture, false if picture doesn't exist
 */
function getProjectPictureUrl($projectId) {
    global $db;
    global $configInfo;

    $sql=" SELECT pictures.file_name,pictures.picture_id
                 FROM pictures,pictures_associated
                WHERE pictures_associated.table_name=\"projects\"
                  AND object_id=".intval($projectId)."
                  AND pictures_associated.picture_id=pictures.picture_id
                  AND pictures.feature=FALSE
                  ORDER BY RAND()
                  LIMIT 1";

    $pictures = $db->GetAll($sql);

    $picture = reset($pictures);
    if ($picture){
        return $img_url = $configInfo['picture_url'] . $picture['file_name'];
    }

    return $picture;
}