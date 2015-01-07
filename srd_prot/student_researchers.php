<?php
/**
 * Manage the student reserachers
 */

include("includes/config.inc.php");
include("includes/functions-required.php");
$tmpl=loadPage("student_researchers", 'Student Researchers');

try {
    processCurrentRequest();
} catch (Exception $e) {
    echo '<p style="color:red; font-size: 17pt">Caught exception: ',  $e->getMessage(), "</p>";
}

$tmpl->displayParsedTemplate('page');

/**
 * Controls the page flow depending upon the REQUEST variable.
 *
 * @return string|void
 */
function processCurrentRequest() {
    if(!isset($_REQUEST)) {
        return view();
    }
    if(isset($_REQUEST['add'])) {
        return add();
    }
    if(isset($_REQUEST['delete'])){
        return delete();
    }
    if(isset($_REQUEST['update'])){
        return update();
    }
    if(!isset($_REQUEST['section'])){
        return view();
    }
    if($_REQUEST['section'] = "edit") {
        return edit();
    }
    if($_REQUEST['section'] = "view") {
        return view();
    }

    return view();
}

/**
 * Add a new researcher
 *
 */
function add() {
    $sql = "INSERT into student_researchers VALUES();";
    global $db;
    if($db->Execute($sql) === false) {
        throw new Exception('SQL error.  Unable to add a new researcher.');
    }
    $_REQUEST['id']=mysql_insert_id();

    edit();
}

/**
 * Delete a researcher
 *
 */
function delete() {
    if (isset($_REQUEST['id'])) {
        $sql="DELETE from student_researchers WHERE id={$_REQUEST['id']}";
        global $db;
        if($db->Execute($sql) === false)
            throw new Exception("Error deleting researcher ". $_REQUEST['id']);
        view();
    } else {
        throw new Exception("Error deleting researcjer.  Researcher ID does not exist or is not set.");
    }

}

/**
 * Update a researcher
 */
function update() {
    if (isset($_REQUEST['id'])) {
        if (is_numeric($_REQUEST['studentID']) || trim($_REQUEST['studentID']) == '' ) {

            // if the student ID is left blank then we want to insert NULL into the database.
            if ($_REQUEST['studentID'] == '') {
                $_REQUEST['studentID'] = 'NULL';
            } else {
                $_REQUEST['studentID'] = "'" . mysql_escape_string($_REQUEST['studentID']) . "'";
            }

            $sql = "UPDATE student_researchers SET
                   first='". mysql_escape_string(isset($_REQUEST['first']) ? $_REQUEST['first'] : '') . "',
                   last='". mysql_escape_string(isset($_REQUEST['last']) ? $_REQUEST['last'] : '') . "',
                   email='". mysql_escape_string(isset($_REQUEST['email']) ? $_REQUEST['email'] : '') . "',
                   studentID=" . $_REQUEST['studentID'] . "
                   WHERE id= $_REQUEST[id];";
            global $db;
            if($db->Execute($sql) === false) {
                throw new Exception('Unable to update researcher.');
            }
            view();
        } else {
            global $tmpl;
            $tmpl->addVar('edit','error','Student ID must be an integer.');
            edit();
        }
    } else {
        throw new Exception('Unable to update project researchers.  Project ID does not exist or not set.');
    }
}

/**
 * View all the researchers
 */
function view() {
    global $tmpl;
    $tmpl->setAttribute('list','visibility','visible');

    $sql="SELECT *
        FROM student_researchers
        ORDER BY last";
    global $db;
    $researchers = $db->getAll($sql);

    // determine how many projects are associated with this resarcher
    foreach($researchers as $key=>$researcher) {
        $sql = "SELECT COUNT(*) AS numprojects FROM student_research WHERE studentResearcherID = " . $researcher['id'];
        $numProjects = $db->getRow($sql);
        $researchers[$key]['numprojects'] = $numProjects['numprojects'];
    }

    if(count($researchers)>0){
        $tmpl->addRows('mainlist',$researchers);
    }
}

/**
 * Edit a researcher
 */
function edit() {
    if(isset($_REQUEST['id'])) {
        global $tmpl;
        $tmpl->setAttribute('edit','visibility','visible');
        $sql = "SELECT * FROM student_researchers WHERE id={$_REQUEST['id']}";

        global $db;
        $researcher = $db->getRow($sql);

        if($researcher){
            $tmpl->addVars('edit',$researcher);

            // get the associated research projects
            $projects = getProjectsForResearcher($_REQUEST['id']);
            $tmpl->addRows('projects', $projects);
        }
    }
    else {
        throw new Exception('Error editing project.  Project ID is not set or does not exist');
    }
}

/**
 * get research projects assoicated with a researcher
 *
 * @param $researcherId - the researcher ID
 * @return mixed - the projects associated with this reseacher
 */
function getProjectsForResearcher($researcherId) {

    $sql= sprintf("SELECT project.id, project.title,
                           dept.name as departmentName, CONCAT(users.first_name, ' ', users.last_name) as supervisor,
                           project.presentationType
                   FROM student_research_projects as project
                   LEFT JOIN student_research AS link ON (link.researchProjectID = project.id)
                   LEFT JOIN departments as dept ON (project.departmentID = dept.department_id)
                   LEFT JOIN users ON (project.supervisorID = users.user_id)
                   WHERE link.studentResearcherID = %s
                   GROUP BY project.id", $researcherId);

    global $db;
    $projects = $db->getAll($sql);

    if(count($projects)>0){
        foreach($projects as $key=>$project){
            if(strlen($project['title'])>50) $projects[$key]['title']=substr($project['title'],0,50) . '...';
        }
    }

    return $projects;
}