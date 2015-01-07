<?php
/**
 * Manage the student research table(s)
 */

//error_reporting(E_ALL);
require_once('../includes/global.inc.php');

$tmpl=loadPage("student_research_edit", 'Student Research Days');

$success='';

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
    if($_REQUEST['section'] == "edit") {
        return edit();
    }
    if($_REQUEST['section'] == "view") {
        return view();
    }

    return view();
}

/**
 * Add a new project
 *
 */
function add() {
    $sql = "INSERT into student_research_projects VALUES();";
    global $db;
    if($db->Execute($sql) === false)
        throw new Exception('SQL error.  Unable to add a new project.');

    $_REQUEST['id']=mysql_insert_id();

    edit();
}

/**
 * Delete a project
 *
 */
function delete() {
    if (isset($_REQUEST['id'])) {
        $sql = "DELETE from student_research_projects WHERE id={$_REQUEST['id']}";
        global $db;
        if ($db->Execute($sql) === false) {
            throw new Exception("Error deleting project " . $_REQUEST['id']);
        }
        // we need to delete the project entries in the link table too.
        $sql = "DELETE from student_research WHERE researchProjectID = {$_REQUEST['id']}";
        if ($db->Execute($sql) === false) {
            throw new Exception("Error deleting project in link table.  Project ID " . $_REQUEST['id']);
        }
        view();
    } else {
        throw new Exception("Error deleting project.  Project ID does not exist or not set.");
    }
}

/**
 * Update a project
 */
function update() {
    if(isset($_REQUEST['id'])) {
        $sql = "UPDATE student_research_projects SET
               supervisorID='". mysql_escape_string(isset($_REQUEST['supervisorID']) ? $_REQUEST['supervisorID'] : '') . "',
               departmentID='". mysql_escape_string(isset($_REQUEST['departmentID']) ? $_REQUEST['departmentID'] : '') . "',
               program='". mysql_escape_string(isset($_REQUEST['program']) ? $_REQUEST['program'] : '') . "',
               course='". mysql_escape_string(isset($_REQUEST['course']) ? $_REQUEST['course'] : '') . "',
               presentationType ='". mysql_escape_string(isset($_REQUEST['presentationType']) ? $_REQUEST['presentationType'] : '') . "',
               hrebNeedClearance='". mysql_escape_string(isset($_REQUEST['hrebNeedClearance']) ? $_REQUEST['hrebNeedClearance'] : '') . "',
               hrebHaveClearance='". mysql_escape_string(isset($_REQUEST['hrebHaveClearance']) ? $_REQUEST['hrebHaveClearance'] : '') . "',
               title='". mysql_escape_string(isset($_REQUEST['title']) ? $_REQUEST['title'] : '') . "',
               description='". mysql_escape_string(isset($_REQUEST['description']) ? $_REQUEST['description'] : '') . "',
               projectUrl='". mysql_escape_string(isset($_REQUEST['projectUrl']) ? $_REQUEST['projectUrl'] : '') . "',
               startDate='" . $_REQUEST['startDate'] . "',
               endDate='" . $_REQUEST['endDate'] . "',
               hidden='". mysql_escape_string(isset($_REQUEST['hidden']) ? $_REQUEST['hidden'] : '') . "'
               WHERE id= $_REQUEST[id];";

        global $db;
        if($db->Execute($sql) === false)
            throw new Exception('Unable to update project.');

        // update student researchers, if any
        if(array_key_exists('studentResearchers', $_REQUEST)) {
            $researcherIds = $_REQUEST['studentResearchers'];

            // remove all records in the link table for this project
            $sql = "DELETE FROM student_research WHERE researchProjectID = " . $_REQUEST['id'];
            $db->Execute($sql);

            // insert the associated student researchers into the link table
            $sql = "INSERT INTO student_research (studentResearcherID, researchProjectID) VALUES";
            $numInserts = 0;
            foreach($researcherIds as $id) {
                if(!empty($id)) {
                    $sql .= sprintf("(%s, %s),", $id, $_REQUEST['id']);
                    $numInserts++;
                }
            }

            if($numInserts > 0) {
                $sql = substr($sql, 0, -1);  // remove trailing comma
                if($db->Execute($sql) === false)
                    throw new Exception('Unable to update project researchers for project.' . $_REQUEST['id']);
            }
        }
        view();
    } else {
        throw new Exception('Unable to update project researchers.  Project ID does not exist or not set.');
    }
}

/**
 * View all the projects
 */
function view() {
    global $tmpl;
    $tmpl->setAttribute('view','visibility','visible');

    $sql="SELECT project.id, GROUP_CONCAT(student.first, ' ', student.last SEPARATOR ' , ') as name, project.title,
                 project.startDate, project.endDate,
                 dept.name as departmentName, CONCAT(users.first_name, ' ', users.last_name) as supervisor,
                 project.presentationType, project.hrebHaveClearance, project.hidden
        FROM student_research_projects AS project
        LEFT JOIN student_research AS link ON (project.id = link.researchProjectID)
        LEFT JOIN student_researchers AS student ON (student.id = link.studentResearcherID)
        LEFT JOIN departments as dept ON (project.departmentID = dept.department_id)
        LEFT JOIN users ON (project.supervisorID = users.user_id)
        GROUP BY project.id
        ORDER BY project.title";
    global $db;
    $projects = $db->getAll($sql);
    if(count($projects)>0){
        foreach($projects as $key=>$project){
            $projects[$key]['hrebHaveClearance']=($project['hrebHaveClearance']) ? "checked='checked'" : '';
            $projects[$key]['hidden']=($project['hidden']) ? "checked='checked'" : '';
            if(strlen($project['title'])>50) $projects[$key]['title']=substr($project['title'],0,50) . '...';
            if(strlen($project['name'])>30) $projects[$key]['name']=substr($project['name'],0,30) . '...';

        }//foreach
        $tmpl->addRows('mainlist',$projects);
    }//if count>0
}

/**
 * Edit a project
 * @throws Exception
 */
function edit() {
    if(isset($_REQUEST['id'])) {
        global $tmpl;
        $tmpl->setAttribute('edit','visibility','visible');
        $sql = "SELECT * FROM student_research_projects WHERE id={$_REQUEST['id']}";
        global $db;
        $project = $db->getRow($sql);
        if($project){
            $project['presentationTypePoster']=($project['presentationType'] == "Poster") ? "checked='checked'" : '';
            $project['presentationTypeOral']=($project['presentationType'] == "Oral") ? "checked='checked'" : '';
            $project['presentationTypeOther']=($project['presentationType'] == "Other") ? "checked='checked'" : '';


            $project['hrebNeedClearanceYes']=($project['hrebNeedClearance'] == true) ? "checked='checked'" : '';
            $project['hrebNeedClearanceNo']=($project['hrebNeedClearance'] == false) ? "checked='checked'" : '';

            $project['hrebHaveClearanceYes']=($project['hrebHaveClearance'] == true) ? "checked='checked'" : '';
            $project['hrebHaveClearanceNo']=($project['hrebHaveClearance'] == false) ? "checked='checked'" : '';

            $project['hiddenYes']=($project['hidden'] == true) ? "checked='checked'" : '';
            $project['hiddenNo']=($project['hidden'] == false) ? "checked='checked'" : '';

            // get student researchers
            $studentResearchers = getStudentResearchers($db, $project['id']);
            $tmpl->addRows('researchers', $studentResearchers);

            // get a drop-down for each student researcher
            $researchersDropDown = getStudentResearchersDropDown($db);
            $tmpl->addVar('edit', "researcher_options", $researchersDropDown);

            // generate the department drop-down list
            $deptDropDown = getDepartmentDropDown($db, $project['departmentID']);
            $tmpl->addRows('department_options',$deptDropDown);

            // generate the supervisor drop-down list
            $supevisorDropDown = getFacultyDropDown($db, $project['supervisorID']);
            $tmpl->addRows('supervisor_options', $supevisorDropDown);

            $tmpl->addVars('edit',$project);
        }
    }
    else {
        throw new Exception('Error editing project.  Project ID is not set or does not exist');
    }
}


/**
 * Return student researchers assoicated with a project
 *
 * @param $db - the databse object
 * @param $projectID - the project id
 * @return mixed - An array of student researchers
 */
function getStudentResearchers($db, $projectID)
{
    $sql = sprintf("SELECT student.id, CONCAT(student.last, ', ', student.first)  as name FROM student_researchers AS student
                    LEFT JOIN student_research AS link ON (student.id = link.studentResearcherID)
                    WHERE link.researchProjectID = %s
                    ORDER BY student.last", (int)$projectID);

    $students_list = $db->getAll($sql);

    return $students_list;
}

/**
 * Returns an array of student researchers for use in a drop-down list
 *
 * @param $db - the database object
 * @return string - an array of student reseacher options as a string
 */
function getStudentResearchersDropDown($db)
{
    // generate a drop-down of student researchers
    $sql = "SELECT id, first, last FROM student_researchers ORDER BY last";
    $researchers_list = $db->getAll($sql);

    $options = "<option value='' selected>-- Add Researcher --</option>";
    foreach($researchers_list as $student) {
        $options .= sprintf("<option value='%s'> %s</option>", $student['id'],$student['last'] . ", " . $student['first']);
    }

    return $options;
}

/**
 * Return a list of department names
 *
 * @param $db - the database object
 * @param $departmentID - the database ID to be selected
 * @return array - the drop down options
 */
function getDepartmentDropDown($db, $departmentID)
{
    $deptSql = "SELECT department_id, name FROM departments";
    $deptartment_list = $db->getAll($deptSql);

    $deptDropDown = array(array('value' => 'NULL', 'name' => "-- Select Department --", 'sel'=>''));
    foreach ($deptartment_list as $option) {
        if ($departmentID == $option['department_id']) $sel = 'selected'; else $sel = '';
        $deptDropDown[] = array('value' => $option['department_id'], 'name' => $option['name'], 'sel' => $sel);
    }
    return $deptDropDown;
}

/**
 * @param $db - the database object
 * @param $supervisorID - the supervisor ID that is to be selected
 * @return array - the drop down options
 */
function getFacultyDropDown($db, $supervisorID)
{
    $facultySql = "SELECT user_id, CONCAT(users.last_name, ', ', users.first_name) as name
                            FROM users
                            WHERE emp_type = 'FACL' AND user_level = 0 AND inactive_flag = 0
                            ORDER BY name";
    $faculty_list = $db->getAll($facultySql);

    $supevisorDropDown = array(array('value' => '', 'name' => '-- None --'));

    foreach ($faculty_list as $option) {
        if ($supervisorID == $option['user_id']) $sel = 'selected'; else $sel = '';
        $supevisorDropDown[] = array('value' => $option['user_id'], 'name' => $option['name'], 'sel' => $sel);
    }
    return $supevisorDropDown;
}

?>