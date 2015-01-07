<?php
/**
 * Retrieve student research projects
 *
 * This is used for the infinite scroll on the student research page, which loads the first set of results itself.
 * This page gets called whenever a jquery event triggers it to fetch more results.
 *
 * The queries depend upon request variable "p" which indicates the page number and is then used as a LIMIT.
 * Which SQL query is run depends on request variable "department", "keyword", or empty, which corresponds to
 * the currently applied filter
 *
 */

require_once('includes/global.inc.php');

define("LIMIT", 5); // items per 'page'

$department = ($_GET["department"]);
$keyword = $_GET["keyword"];

$tmpl=loadPage("student_research_partial", 'Student Research');

$page = $_GET["p"] == null ? 1 :  $_GET["p"];

$lower = ($page * LIMIT) - LIMIT;

if ($department!="") {
    // Filter by department

    // Get the department name for display
    $sql = "SELECT name
            FROM departments
            WHERE department_id = " . $department;

    $result = $db->GetAll($sql);
    $departmentName = $result[0]['name'];

    // Retrieve the projects associated with this department
    $sql = sprintf("SELECT project.id, GROUP_CONCAT(student.first, ' ', student.last SEPARATOR ' / ') as name, project.title,
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
            ORDER BY project.title
            LIMIT %s, %s", $lower, LIMIT);

    $projects = $db->GetAll($sql);

    $countSql = "SELECT COUNT(*) as total FROM student_research_projects as project
                 LEFT JOIN departments ON (project.departmentID = departments.department_id)
                 WHERE project.departmentID = $department AND project.hidden = 0";
    $total = $db->GetRow($countSql);

    // If department search was used, add the department name list into the "enfasis" part of the template
    $enfasis = sprintf("Listing by department : %s", $departmentName);
} elseif ($keyword!="") {
    // Filter by keyword
    $keyword = strtolower(mysql_real_escape_string($keyword));

    $sql = "SELECT project.id, GROUP_CONCAT(student.first, ' ', student.last SEPARATOR ' / ') as name, project.title,
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
            ORDER BY project.title";

    $projects = $db->GetAll($sql);
    $total['total'] = count($projects);
    $projects = array_slice($projects, $lower, LIMIT);

    // If keyword search was used, add the keyword list into the "enfasis" part of the template
    $enfasis = sprintf("Listing by keyword(s): %s", $keyword);
} else {
    $sql = sprintf("SELECT project.id, GROUP_CONCAT(student.first, ' ', student.last SEPARATOR ', ') as name, project.title,
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
                ORDER BY isnull ASC, department ASC
                LIMIT %s, %s", $lower, LIMIT);

    $projects = $db->GetAll($sql);

    $sql = "SELECT COUNT(*) as total FROM student_research_projects";
    $total = $db->GetRow($sql);
}

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
$tmpl->addVar('page', 'p', $page+1);  // the next page

if($page*LIMIT > $total['total']) {
    // we've reached the end
    $tmpl->addVar('page', 'total',  $total['total'] . " of " . $total['total'] );  // number of results showing
} else {
    $tmpl->addVar('page', 'total',  $page*LIMIT . " of " . $total['total'] );  // number of results showing
}

$tmpl->displayParsedTemplate('page');
