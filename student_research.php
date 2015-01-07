<?php
/**
 * Retrieve information about student research
 */

require_once('includes/global.inc.php');

define("PAGELIMIT", 5); // items per page

$tmpl=loadPage("student_research", 'Student Research');
//showMenu("research_office");

$department = ($_GET["department"]);
$keyword = $_GET["keyword"];
$targetyear = $_GET['targetyear'];

if ($department!="") {
    // Filter by department

    // Get the department name for display
    $sql = "SELECT name
            FROM departments
            WHERE department_id = " . $department;

    $result = $db->GetAll($sql);
    $departmentName = $result[0]['name'];

    // Retrieve the projects associated with this department
    $sql = "SELECT project.id, GROUP_CONCAT(student.first, ' ', student.last ORDER BY student.aorder SEPARATOR ' / ') as name, project.title,
                   project.description, project.projectURL,
                   IF(YEAR(project.EndDate) > YEAR(CURDATE()), YEAR(CURDATE()), YEAR(project.EndDate)) as year,
                   CONCAT(users.first_name, ' ', users.last_name) as supervisor,
                   departments.name as department
            FROM student_research_projects AS project
            LEFT JOIN student_research AS link ON (project.id = link.researchProjectID)
            LEFT JOIN student_researchers AS student ON (student.id = link.studentResearcherID)
            LEFT JOIN users ON (project.supervisorID = users.user_id)
            LEFT JOIN departments ON (project.departmentID = departments.department_id)
            WHERE project.departmentID = $department AND project.hidden = 0
            GROUP BY project.id
            ORDER BY project.EndDate desc
            ";

    $projects = $db->GetAll($sql);

    $countSql = "SELECT COUNT(*) as total FROM student_research_projects as project
                 LEFT JOIN departments ON (project.departmentID = departments.department_id)
                 WHERE project.departmentID = $department AND project.hidden = 0";
    $total = $db->GetRow($countSql);

    $tmpl->addVar('page', 'filter', 'department=' . $department);

    // If department search was used, add the department name list into the "enfasis" part of the template
    $enfasis = sprintf("Listing by department : %s", $departmentName);
} elseif ($keyword!="") {
    // Filter by keyword
    $keyword = strtolower(mysql_real_escape_string($keyword));

    $sql = "SELECT project.id, GROUP_CONCAT(student.first, ' ', student.last ORDER BY student.aorder SEPARATOR ' / ') as name, project.title,
                   project.description, project.projectURL,
                   IF(YEAR(project.EndDate) > YEAR(CURDATE()), YEAR(CURDATE()), YEAR(project.EndDate)) as year,
                   CONCAT(users.first_name, ' ', users.last_name) as supervisor,
                   departments.name as department
            FROM student_research_projects AS project
            LEFT JOIN student_research AS link ON (project.id = link.researchProjectID)
            LEFT JOIN student_researchers AS student ON (student.id = link.studentResearcherID)
            LEFT JOIN users ON (project.supervisorID = users.user_id)
            LEFT JOIN departments ON (project.departmentID = departments.department_id)
            WHERE lower(project.description) like '%$keyword%'
               OR lower(student.first) like  '%$keyword%'
               OR lower(student.last) like  '%$keyword%'
               OR lower(project.title) like  '%$keyword%'
               AND project.hidden = 0
            GROUP BY project.id
            ORDER BY project.EndDate desc";

    $projects = $db->GetAll($sql);
    $total['total'] = count($projects);
    //$projects = array_slice($projects, 0, PAGELIMIT);

    $tmpl->addVar('page', 'filter', 'keyword=' . $keyword);

    // If keyword search was used, add the keyword list into the "enfasis" part of the template
    $enfasis = sprintf("Listing by keyword(s): %s", $keyword);


} elseif ($targetyear!="") {
    // Filter by year

    $sql = "SELECT project.id, GROUP_CONCAT(student.first, ' ', student.last ORDER BY student.aorder SEPARATOR ' / ') as name, project.title,
                   project.description, project.projectURL,
                   IF(YEAR(project.EndDate) > YEAR(CURDATE()), YEAR(CURDATE()), YEAR(project.EndDate)) as year,
                   CONCAT(users.first_name, ' ', users.last_name) as supervisor,
                   departments.name as department
            FROM student_research_projects AS project
            LEFT JOIN student_research AS link ON (project.id = link.researchProjectID)
            LEFT JOIN student_researchers AS student ON (student.id = link.studentResearcherID)
            LEFT JOIN users ON (project.supervisorID = users.user_id)
            LEFT JOIN departments ON (project.departmentID = departments.department_id)
            WHERE YEAR(project.EndDate) = $targetyear
               AND project.hidden = 0
            GROUP BY project.id
            ORDER BY project.title,project.EndDate desc";

    $projects = $db->GetAll($sql);
    $total['total'] = count($projects);
    
    //TD - removed the slicing routines 
   // $projects = array_slice($projects, 0, PAGELIMIT);

    $tmpl->addVar('page', 'filter', 'year=' . $targetyear);

    // If keyword search was used, add the keyword list into the "enfasis" part of the template
    $enfasis = sprintf("Listing by year: %s", $targetyear);
} 




else {
    // List all
    $sql = "SELECT project.id, GROUP_CONCAT(student.first, ' ', student.last ORDER BY student.aorder SEPARATOR ', ') as name, project.title,
                   project.description, project.projectURL,
                   IF(YEAR(project.EndDate) > YEAR(CURDATE()), YEAR(CURDATE()), YEAR(project.EndDate)) as year,
                   CONCAT(users.first_name, ' ', users.last_name) as supervisor,
                   departments.name as department,
                   IF(departments.name IS NULL, 1, 0) as isnull
            FROM student_research_projects AS project
            LEFT JOIN student_research AS link ON (project.id = link.researchProjectID)
            LEFT JOIN student_researchers AS student ON (student.id = link.studentResearcherID)
            LEFT JOIN users ON (project.supervisorID = users.user_id)
            LEFT JOIN departments ON (project.departmentID = departments.department_id)
            WHERE project.hidden = 0
            GROUP BY project.id
            ORDER BY isnull ASC, year DESC, RAND()
            LIMIT 30
            ";

    $projects = $db->GetAll($sql);

    $countSql = "SELECT COUNT(*) as total FROM student_research_projects WHERE hidden=0";
    $total = $db->GetRow($countSql);

    $tmpl->addVar('page', 'filter', 'none');
}

loadSearchTerms($db, $tmpl);
initPageTotal($total['total']);

$tmpl->addVar('page', 'totalpages', ceil((float)($total['total'] / PAGELIMIT)));


// Activate the proper filter on the template
if($_GET["department"]!="") showMenu("ctrl_department",$tmpl);
if($_GET["keyword"]!="")    showMenu("ctrl_keyword",$tmpl);
if($_GET["year"]!="")    showMenu("ctrl_targetyear",$tmpl);

// filter through projects with no Supervisor or Department
foreach($projects as $key=>$project) {
    if ($project['supervisor'] != null) {
        $projects[$key]['supervisor']  = "Supervisor : " . $project['supervisor'];
    }
    if (!empty($project['projectURL'])) {
      $projects[$key]['hasProjectLink']  = "<img src='/images/icon-sm-copy.gif' alt='project link' style='margin:5px; width:20px';/>";
      $projects[$key]['projectURL']  = "<a href =" .$project['projectURL']  ." target='_blank'>View More Details</a>";
    }
}

$tmpl->addRows('row', $projects);

if ($enfasis) {
    $tmpl->addVar('page', 'enfasis', $enfasis);
}

$tmpl->displayParsedTemplate('page');


/**
 * Add appropriate count variable to display on page
 *
 * @param $total - the total number of records
 */
function initPageTotal($total) {
    global $tmpl;
    if(PAGELIMIT > $total) {
        // we've reached the end
        $tmpl->addVar('page', 'total',  $total . " of " . $total );  // number of results showing
    } else {
        $tmpl->addVar('page', 'total',  PAGELIMIT . " of " . $total);  // number of results showing
    }
}

/**
 * Load the terms used in the SearchBy menu and add them to the given template
 *
 * @param $db - the database object
 * @param $tmpl - the template to add to
 */
function loadSearchTerms($db, $tmpl)
{
    // Obtain the list of departments
    $sql = "SELECT DISTINCT departments.department_id, departments.name
            FROM departments, student_research_projects
            WHERE departments.department_id = student_research_projects.departmentID
            ORDER BY departments.name";

    $departments = $db->GetAll($sql);

    $bydepartment = '';
    if ($departments)
        foreach ($departments as $row) {
            if (strlen($row['name']) > 0) {
                $bydepartment .= '<a href="student_research.php?action=list&department=' . urlencode($row["department_id"]) . '"
                            title="' . $row["name"] . '">' . ucwords($row["name"]) . '</a><br />';
            }
        }

    // Add departments into the template
    $tmpl->addVar('departments', "bydepartment", $bydepartment);
    
    //get year list
    
    $sql="SELECT DISTINCT YEAR(endDate) as year from student_research_projects ORDER BY year desc";
    $years=$db->GetAll($sql);
    if($years){
        foreach($years as $year){
            $byyear.= '<a href="student_research.php?action=list&targetyear=' . $year['year'] . '"
                            title="' . $year['year'] . '">'.$year['year'].'</a><br />';
        }
    }
        $tmpl->addVar('targetyear', "byyear", $byyear);

}
