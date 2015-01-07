<?php
require_once('includes/global.inc.php');

$userId = $_REQUEST['userid'];

if(!$userId) {
    echo "<p>Sorry, cannot load page as the User ID was not provided.</p>";
    die();
}

$tmpl=loadPage("mycontact_preferences", "My Contact Preferences");


if(isset($_REQUEST['update'])) {
    updateContactPreferences(array('mail_events' => $_REQUEST['mail_events'] == 'on' ? 1 : 0,
                                   'mail_deadlines' => $_REQUEST['mail_deadlines'] == 'on' ? 1 : 0,
                                   'filters' => $_REQUEST['filters']
                             ), $userId);
    $tmpl->addVar("page", "message", 'Your changes have been saved.');
}

$user = getUser($userId);  // retrieve the user from the database only after updating any new values

$tmpl->addVar("page", "userid", $user['user_id']);
$tmpl->addVar("page", "mail_events", $user['mail_events'] == 1 ? 'checked' : '');
$tmpl->addVar("page", "mail_deadlines", $user['mail_deadlines'] == 1 ? 'checked' : '');
$tmpl->addVar("page", "displayname", $user['first_name'] . " " . $user['last_name']);

$filters = getFilters($userId);
$tmpl->addRows("FILTERLIST", $filters);

$tmpl->displayParsedTemplate('page');


function getFilters($user_id) {
    global $db;

    $sql = "SELECT topic_id, name FROM topics_research";
    $filters = $db->CacheGetAll(180,$sql);  // CACHE 3 MINUTES ONLY
    if(is_array($filters))
        foreach($filters as $key=>$filter)
            $filter_ids[$key]=$filter["topic_id"];
    else $filter_ids=array();

    $sql = "SELECT topic_id FROM user_topics_filter
             WHERE user_id='" . $user_id . "'";

    $myfilters = $db->GetAll($sql);

    if(is_array($myfilters))
        foreach($myfilters as $myfilter) {
            $filter_id = $myfilter["topic_id"];
            if( ($key = array_search($filter_id, $filter_ids)) !== false)
                $filters[$key]["filter_checked"] = "checked";
        }

    return $filters;
}

function getUser($userId) {
    global $db;

    $sql = "SELECT * FROM `users`
            LEFT JOIN profiles ON profiles.user_id = users.user_id
            LEFT JOIN users_ext ON users_ext.user_id = users.user_id
            WHERE users.user_id = " . $userId;

    $user = $db->GetRow($sql);

    return $user;
}

function updateContactPreferences($pref, $userId) {
    global $db;

    $sql = sprintf("UPDATE `users` SET `mail_events` = %s, `mail_deadlines` = %s
                    WHERE `user_id` = %s", $pref['mail_events'], $pref['mail_deadlines'], $userId);

    $result = $db->Execute($sql);

    if(!$result) {
        echo "Unable to update preferences (1)";
        die();
    }

    // clear the old filters so we can refresh them
    $sql = "DELETE FROM `user_topics_filter` WHERE `user_id` = " . $userId;
    $result = $db->Execute($sql);

    if(!$result) {
        echo "Unable to update preferences (2)";
        die();
    }

    if(count($pref['filters']) > 0) {
        $sql = "INSERT INTO `user_topics_filter` (`user_topics_filter_id`, `topic_id`, `user_id`) VALUES ";

        foreach($pref['filters'] as $key=>$topicID) {
            $sql .= sprintf("(null, %s, %s) ", $topicID, $userId);
            if($key < count($pref['filters'])-1) {
                $sql .= ", ";
            }
        }

        $result = $db->Execute($sql);

        if(!$result) {
            echo "Unable to update preferences (3)";
            die();
        }
    }
}
