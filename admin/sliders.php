<?php
/**
 * Modify the sliders that appear on the page
 */

include("includes/config.inc.php");
include("includes/functions-required.php");

$tmpl=loadPage("sliders", 'Feature Sliders');

if (isset($_REQUEST['section']) && $_REQUEST['section'] == 'edit') {
    edit();
} else if (isset($_REQUEST['section']) && $_REQUEST['section'] == 'update') {
    update();
} else if (isset($_REQUEST['section']) && $_REQUEST['section'] == 'delete') {
    delete();
} else if (isset($_REQUEST['section']) && $_REQUEST['section'] == 'add') {
    add();
} else {
    view();
}

$tmpl->displayParsedTemplate('page');

/**
 * Get all the sliders from the db for display
 */
function view() {
    global $tmpl;
    $tmpl->setAttribute('thelist','visibility','visible');
    $tmpl->addRows('mainlist',getSliders());
}

/**
 * Get all the sliders from the db for display
 */
function edit() {
    global $tmpl;
    $tmpl->setAttribute('list','visibility','hidden');
    $tmpl->setAttribute('edit','visibility','visible');
    $tmpl->addVars('edit', getSlider($_REQUEST['id']));
}

/** Update a slider
 * @throws Exception
 */
function update() {
    global $tmpl;
    global $db;

    if(isset($_REQUEST['id'])) {
        $sql = "UPDATE sliders SET
               page='". mysql_escape_string($_REQUEST['page'])  . "',
               description='". mysql_escape_string(isset($_REQUEST['description']) ? $_REQUEST['description'] : '') . "',
               heading='". mysql_escape_string(isset($_REQUEST['heading']) ? $_REQUEST['heading'] : '') . "',
               imageUrl='". mysql_escape_string(isset($_REQUEST['imageUrl']) ? $_REQUEST['imageUrl'] : '') . "',
               start_date ='". $_REQUEST['start_date'] . "',
               end_date='". $_REQUEST['end_date'] . "',
               shown='". mysql_escape_string(isset($_REQUEST['shown']) ? '1' : '0') . "',
               weight='". mysql_escape_string(isset($_REQUEST['weight']) ? $_REQUEST['weight'] : '50') . "',
               link='". mysql_escape_string(isset($_REQUEST['link']) ? $_REQUEST['link'] : '') . "'
               WHERE id= $_REQUEST[id];";

        global $db;
        if($db->Execute($sql) === false)
            throw new Exception('Unable to update slider.');
    } else {
        throw new Exception('Unable to update slider.  Slider ID does not exist or not set.');
    }

    header("Location: /sliders.php");
}

/**
 * Delete a slider
 * @throws Exception
 */
function delete() {
    if (isset($_REQUEST['id'])) {
        $id = $_REQUEST['id'];

        $sql = "DELETE FROM sliders WHERE id=" . $id;

        global $db;
        if ($db->Execute($sql) === false) {
            throw new Exception("Error deleting slider " . $id);
        }
    } else {
        throw new Exception("Error deleting slider.  Slider ID does not exist or not set.");
    }

    header("Location: /sliders.php");
}

/**
 * Add a new slider
 * @throws Exception
 */
function add() {
    $sql = "INSERT into sliders VALUES();";
    global $db;
    if($db->Execute($sql) === false)
        throw new Exception('SQL error.  Unable to add a new project.');

    header("Location: /sliders.php?section=edit&id=" . mysql_insert_id());
}

/**
 * Get the sliders from the database
 *
 * @return mixed - the sliders from the database
 */
function getSliders() {
    $sql = "SELECT * FROM sliders ORDER BY weight DESC";

    global $db;
    $sliders = $db->getAll($sql);

    foreach($sliders as $key=>$slider) {
        $sliders[$key]['page'] = $slider['page']  == 0 ? "front" : 'unknown';
        $sliders[$key]['shown'] = ($slider['shown'] == '1') ? "<img src='/images/check.gif' alt='check'/>" : '';
        $sliders[$key]['link'] = strlen($slider['link'])  > 0  ? "<img src='/images/check.gif' alt='check'/>" : '';
    }

    return $sliders;
}

/**
 * Get a slider from the database
 *
 * @param $id - the slider id
 * @return mixed - a sliders from the database
 */
function getSlider($id) {
    $sql = "SELECT * FROM sliders WHERE id = " . $id;

    global $db;
    $slider = $db->getRow($sql);

    $slider['shown'] = ($slider['shown'] == '1') ? "checked='checked'" : '';

    return $slider;
}