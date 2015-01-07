<?php
/**
* Compile report for CAQC stats
* 
* Compiles a report for the CAQC using their fixed categories and via choice of faculty from list.
* Later enhancements should include pre-selecting the faculty
*/

include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
$template = new Template;

include("html/header.html");

/**
* Relate users and degrees
*/
$success='';


if(isset($_REQUEST['relate'])) {
    if(isset($_REQUEST['user_ids']) && isset($_REQUEST['degree_id'])) if($_REQUEST['degree_id']!=0){
        $err=FALSE;
        //print_r ($_REQUEST['user_ids']);
        foreach($_REQUEST['user_ids'] as $userid) {
            //$list=mysqlFetchRow('degrees_users',"user_id=$userid");
            //if(!is_array($list)){
                $result=mysqlInsert('degrees_users',array($userid,$_REQUEST['degree_id']));
                if($result!=1) { $err=TRUE; $success.="Error inserting: $result ;";}
            //}
        }
        if(!$err) $success="Inserted " . count($_REQUEST['user_ids']) .' relates';
    }
    else $success = 'No user or degree specified';
}

if(isset($_REQUEST['remove']))  {

    if(isset($_REQUEST['user_id']) && isset($_REQUEST['degree_id']))   {
        $result=mysqlDelete('degrees_users',"user_id='$_REQUEST[user_id]' AND degree_id='$_REQUEST[degree_id]'");
        if($result==1)$success='User removed';
        else $success="Error removing user: $result";
    }
    else $success='Missing information';
}

///////////////////////////////////////////////////////////////////

if (isset($_REQUEST['section'])) {

    if(!isset($success)) $success="";
    switch($_REQUEST['section']){
        case "relate":
            $users=mysqlFetchRows('users',"(emp_type='MAN' OR emp_type='FACL') order by last_name,first_name ");
            $user_options="";
            foreach($users as $user){
                $user_options.="<option value='$user[user_id]'>$user[last_name], $user[first_name]</option>\n";
            }
            $degrees=mysqlFetchRows('degrees','1 order by degree_name');
            $degree_options='';
            foreach($degrees as $degree) $degree_options.="<option value='$degree[degree_id]'>$degree[degree_name]</option>\n";
            $hasharray = array('success'=>$success,'user_options'=>$user_options,'degree_options'=>$degree_options);
            $filename = 'templates/template-degrees_choose.html';
        
            $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
            echo $parsed_html_file;
        break;
        
        case "edit":
            if(isset($_REQUEST['degree_id'])) {
                //Already chose a degree  so show list
                
                
                $users=mysqlFetchRows('degrees_users join users on degrees_users.user_id=users.user_id',"degrees_users.degree_id='$_REQUEST[degree_id]' order by last_name,first_name");
                $userlist='';
                if(is_array($users)) {
                    foreach($users as $user){
                        $userlist.="<tr><td>
                         <button onclick=\"window.location='/cv_review_print.php?generate=caqc&user_id=$user[user_id]'\">CAQC CV</button>
                        </td><td>$user[last_name], $user[first_name] </td><td><button type='button' onClick='window.location=\"/caqc.php?remove&section=edit&user_id=$user[user_id]&degree_id=$_REQUEST[degree_id]\"'>Remove</button></td></tr>\n";
                    }
                }
                else $success="No faculty linked with this degree";
                
                $degrees=mysqlFetchRows('degrees','1 order by degree_name');
                $degree_options='';
                foreach($degrees as $degree) {
                    if($degree['degree_id']==$_REQUEST['degree_id']) $sel='SELECTED'; else $sel='';
                    $degree_options.="<option $sel value='$degree[degree_id]'>$degree[degree_name]</option>\n";
                }
                $hasharray = array('success'=>$success,'degree_options'=>$degree_options,'userlist'=>$userlist);
                $filename = 'templates/template-degrees_editlist.html';
            
                $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
                echo $parsed_html_file;
            }
            else {
                //Just show degree chooser
                $degrees=mysqlFetchRows('degrees','1 order by degree_name');
                $degree_options='';
                foreach($degrees as $degree) $degree_options.="<option value='$degree[degree_id]'>$degree[degree_name]</option>\n";
                
                
                
                $hasharray = array('success'=>$success,'degree_options'=>$degree_options);
                $filename = 'templates/template-degrees_editchoose.html';
            
                $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
                echo $parsed_html_file;
            }
        break;

        case 'stats':
            $acadyear = !isset($_REQUEST['acadyear']) ? 0 : $_REQUEST['acadyear'];
            $curryear = isCurrentAcademicYear($acadyear);

            //get the base queries to gather stats, which may change based on academic year
            $queries = defineStatQueries($acadyear);

            $degreeId = $_REQUEST['degree_id'];
            $facultyId = $_REQUEST['faculty_id'];

            // determine what type of stats the user wishes to gather, or display the stats menu
            if (isset($degreeId) && $degreeId != 0) {
                $hasharray = gatherDegreeStats($degreeId, $curryear, $acadyear, $queries);

                $filename = 'templates/template-degrees_stats.html';
                $parsed_html_file = $template->loadTemplate($filename, $hasharray, "HTML");
                echo $parsed_html_file;
            } elseif (isset($facultyId) && $facultyId != 0) {
                $hasharray = gatherFacultyStats($facultyId, $curryear, $acadyear, $queries);

                $filename = 'templates/template-degrees_facultystats.html';
                $parsed_html_file = $template->loadTemplate($filename, $hasharray, "HTML");
                echo $parsed_html_file;
            } elseif (isset($degreeId) && isset($facultyId)) {
                if ($degreeId == 0 && $facultyId == 0) {
                    $hasharray = gatherInstitutionalStats($curryear, $acadyear, $queries);

                    $filename = 'templates/template-degrees_facultystats.html';
                    $parsed_html_file = $template->loadTemplate($filename, $hasharray, "HTML");
                    echo $parsed_html_file;
                }
            }
            else {
                $hasharray = displayStatsMenu($success);

                $filename = 'templates/template-degrees_statschoose.html';
                $parsed_html_file = $template->loadTemplate($filename, $hasharray, "HTML");
                echo $parsed_html_file;
            }
            break;
    }
}//section

function gatherDegreeStats($degreeId, $curryear, $acadyear, $queries)
{
    $degree = mysqlFetchRow('degrees', "degree_id='$degreeId'");
    if (is_array($degree)) {
        $users = mysqlFetchRows('degrees_users', "degree_id='$degreeId'");
        if (is_array($users)) {
            $acad_start=substr($acadyear, 0,4);
            $acad_end=substr($acadyear,5,4);
            //if it is the current year then just use the report flag as the check
            if ($curryear) {
                $sql_for_degrees = "SELECT *
                                    FROM cas_cv_items_archive as cia
                                    LEFT JOIN degrees_users as du ON
                                    du.user_id=cia.user_id ";

//                $sql_for_degrees_tail = " AND report_flag=1
//                                    AND NOT(ISNULL(du.user_id))
//                                    AND du.degree_id=$degree[degree_id]";

                //Oct 2012 - to gather current year's stats
                $sql_for_degrees_tail=" AND
                            	(YEAR(cia.n09)='$acad_start' OR YEAR(cia.n09)='$acad_end')
                      			AND NOT(ISNULL(du.user_id))
                                AND du.degree_id=$degreeId";

            } //otherwise use the archive set up by the roll-over routine
            else {
                $sql_for_degrees = "SELECT *
                                    FROM cas_cv_items_archive as cia
                                    LEFT JOIN cv_items_check_archive as cica ON
                                    cia.cv_item_id=cica.cv_item_id
                                    LEFT JOIN degrees_users as du ON
                                    du.user_id=cia.user_id ";

                $sql_for_degrees_tail = " AND acadyear='$acadyear'
                                    AND NOT(ISNULL(du.user_id))
                                    AND du.degree_id=$degree[degree_id]";
            }
            //Books authored - cv item types
            $sql = $sql_for_degrees . $queries['where_booksauth'] . $sql_for_degrees_tail;
            $booksauth = mysql_num_rows(mysql_query($sql));

            //Books Edited - cv item types
            $sql = $sql_for_degrees . $queries['where_booksedit'] . $sql_for_degrees_tail;
            $booksedit = mysql_num_rows(mysql_query($sql));

            //Refereed Journals - cv item types
            $sql = $sql_for_degrees . $queries['where_journals'] . $sql_for_degrees_tail;
            $journals = mysql_num_rows(mysql_query($sql));

            //Other Peer-Reviewed - cv item types
            $sql = $sql_for_degrees . $queries['where_otherpeer'] . $sql_for_degrees_tail;
            $otherpeer = mysql_num_rows(mysql_query($sql));

            //Non-Peer Reviewed Pubs - cv item types
            $sql = $sql_for_degrees . $queries['where_nonpeer'] . $sql_for_degrees_tail;
            $nonpeer = mysql_num_rows(mysql_query($sql));

            //Conf Presentations - cv item types
            $sql = $sql_for_degrees . $queries['where_conf'] . $sql_for_degrees_tail;
            $conf = mysql_num_rows(mysql_query($sql));

            //Undergrad Peer Reviewed - cv item types
            $sql = $sql_for_degrees . $queries['where_undergrad'] . $sql_for_degrees_tail;
            $undergrad = mysql_num_rows(mysql_query($sql));
            echo $sql;

            //Submitted - cv item types
            $sql = $sql_for_degrees . $queries['where_sumb'] . $sql_for_degrees_tail;
            $subm = mysql_num_rows(mysql_query($sql));

            //Grants
            $sql = $sql_for_degrees . $queries['where_grants'] . $sql_for_degrees_tail;
            $grants = mysql_num_rows(mysql_query($sql));

            $hasharray = array(
                'booksauth'=> $booksauth,
                'booksedit'=> $booksedit,
                'journals' => $journals,
                'otherpeer'=> $otherpeer,
                'nonpeer'  => $nonpeer,
                'conf'     => $conf,
                'undergrad'=> $undergrad,
                'subm'     => $subm,
                'grants'   => $grants,
                'degree'   => $degree['degree_name']
            );
        } else {
            $hasharray['success'] = 'No faculty listed in this degree';
        }
    } else {
        $hasharray['success'] = 'Degree not found';
    }

    return $hasharray;
}

function gatherFacultyStats($facultyId, $curryear, $acadyear, $queries)
{
    $faculty = mysqlFetchRow('divisions', "division_id=$facultyId");
    if (is_array($faculty)) {
        $departments = mysqlFetchRows('departments', "division_id=$facultyId");
        //get full Faculty list. Note: is not necc total of dept list so have to grab separately

        if ($curryear) {
            $sql_for_faculty = "SELECT *
                                    FROM cas_cv_items_archive as cia
                                    LEFT JOIN users as u ON
                                    u.user_id=cia.user_id
                                    LEFT JOIN departments as d ON
                                    u.department_id=d.department_id
                                    LEFT JOIN divisions ON
                                    d.division_id=divisions.division_id ";

            $sql_for_faculty_tail = " AND report_flag=1
                                    AND NOT(ISNULL(u.user_id))
                                    AND divisions.division_id=$facultyId";
        } else {
            $sql_for_faculty = "SELECT *
                                    FROM cas_cv_items_archive as cia
                                    LEFT JOIN cv_items_check_archive as cica ON
                                    cia.cv_item_id=cica.cv_item_id
                                    LEFT JOIN users as u ON
                                    u.user_id=cia.user_id
                                    LEFT JOIN departments as d ON
                                    u.department_id=d.department_id
                                    LEFT JOIN divisions ON
                                    d.division_id=divisions.division_id ";

            $sql_for_faculty_tail = " AND acadyear='$acadyear'
                                    AND NOT(ISNULL(u.user_id))
                                    AND divisions.division_id=$facultyId";
        }

        $sql = $sql_for_faculty . $queries['where_booksauth'] . $sql_for_faculty_tail;
        $booksauth = mysql_num_rows(mysql_query($sql));

        //Books Edited - cv item types
        $sql = $sql_for_faculty . $queries['where_booksedit']  . $sql_for_faculty_tail;
        $booksedit = mysql_num_rows(mysql_query($sql));

        //Refereed Journals - cv item types
        $sql = $sql_for_faculty . $queries['where_journals']  . $sql_for_faculty_tail;
        $journals = mysql_num_rows(mysql_query($sql));

        //Other Peer-Reviewed - cv item types
        $sql = $sql_for_faculty . $queries['where_otherpeer']  . $sql_for_faculty_tail;
        $otherpeer = mysql_num_rows(mysql_query($sql));

        //Non-Peer Reviewed Pubs - cv item types
        $sql = $sql_for_faculty . $queries['where_nonpeer']  . $sql_for_faculty_tail;
        $nonpeer = mysql_num_rows(mysql_query($sql));

        //Conf Presentations - cv item types
        $sql = $sql_for_faculty . $queries['where_conf']  . $sql_for_faculty_tail;
        $conf = mysql_num_rows(mysql_query($sql));

        //Undergrad Peer Reviewed - cv item types
        $undergrad = 0;
        //Submitted - cv item types
        $sql = $sql_for_faculty . $queries['where_sumb']  . $sql_for_faculty_tail;
        $subm = mysql_num_rows(mysql_query($sql));

        //Grants
        $sql = $sql_for_faculty . $queries['where_grants']  . $sql_for_faculty_tail;
        $grants = mysql_num_rows(mysql_query($sql));

        $output = "
                                <tr><td colspan='2'>Faculty:<b>$faculty[name] </b></td>
                                </tr>

                                <tr><td>&nbsp;&nbsp;</td><td>Books Authored / Co-Authored:</td>
                                <td>$booksauth</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Books Edited / Co-Edited:</td>
                                <td>$booksedit</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Articles in Refereed Journals:</td>
                                <td>$journals</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Other Peer-Reviewed Scholarly Activity:</td>
                                <td>$otherpeer</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Non-Peer-Reviewed Scholarly Activity:</td>
                                <td>$nonpeer</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Conference Presentations:</td>
                                <td>$conf</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Peer-reviewed Publications: Undergrad Authors:</td>
                                <td>$undergrad</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Peer-reviewed Publications, Submitted:</td>
                                <td>$subm</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Grants:</td>
                                <td>$grants</td></tr>
                            ";

        if (is_array($departments)) {
            foreach ($departments as $department) {

                if ($curryear) {
                    $sql_for_department = "SELECT *
                                FROM cas_cv_items_archive as cia

                                LEFT JOIN (select * from cv_items_check_archive where acadyear='$acadyear') as cica ON
                                cia.cv_item_id=cica.cv_item_id 

                                LEFT JOIN users as u ON
                                u.user_id=cia.user_id
                                LEFT JOIN departments as d ON
                                u.department_id=d.department_id
                                 ";

                    $sql_for_department_tail = " AND report_flag=1
                                AND NOT(ISNULL(u.user_id))
                                AND d.department_id=$department[department_id] ";
                } else {
                    $sql_for_department = "SELECT *
                                FROM cas_cv_items_archive as cia
                                LEFT JOIN cv_items_check_archive as cica ON
                                cia.cv_item_id=cica.cv_item_id
                                LEFT JOIN users as u ON
                                u.user_id=cia.user_id
                                LEFT JOIN departments as d ON
                                u.department_id=d.department_id
                                 ";

                    $sql_for_department_tail = " AND acadyear='$acadyear'
                                AND NOT(ISNULL(u.user_id))
                                AND d.department_id=$department[department_id] ";
                }
                $sql = $sql_for_department . $queries['where_booksauth']  . $sql_for_department_tail;
                $booksauth = mysql_num_rows(mysql_query($sql));

                //Books Edited - cv item types
                $sql = $sql_for_department . $queries['where_booksedit']  . $sql_for_department_tail;
                $booksedit = mysql_num_rows(mysql_query($sql));

                //Refereed Journals - cv item types
                $sql = $sql_for_department . $queries['where_journals']  . $sql_for_department_tail;
                $journals = mysql_num_rows(mysql_query($sql));

                //Other Peer-Reviewed - cv item types
                $sql = $sql_for_department . $queries['where_otherpeer']  . $sql_for_department_tail;
                $otherpeer = mysql_num_rows(mysql_query($sql));

                //Non-Peer Reviewed Pubs - cv item types
                $sql = $sql_for_department . $queries['where_nonpeer']  . $sql_for_department_tail;
                $nonpeer = mysql_num_rows(mysql_query($sql));

                //Conf Presentations - cv item types
                $sql = $sql_for_department . $queries['where_conf']  . $sql_for_department_tail;
                $conf = mysql_num_rows(mysql_query($sql));

                //Undergrad Peer Reviewed - cv item types
                $undergrad = 0;
                //Submitted - cv item types
                $sql = $sql_for_department . $queries['where_sumb']  . $sql_for_department_tail;
                $subm = mysql_num_rows(mysql_query($sql));

                //Grants
                $sql = $sql_for_department . $queries['where_grants'] . $sql_for_department_tail;
                $grants = mysql_num_rows(mysql_query($sql));

                $output .= "
                                <tr><td colspan='2'>&nbsp;</td></tr>
                                <tr><td colspan='2'>Department:<b> $department[name] </b></td>
                                </tr>

                                <tr><td>&nbsp;&nbsp;</td><td>Books Authored / Co-Authored:</td>
                                <td>$booksauth</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Books Edited / Co-Edited:</td>
                                <td>$booksedit</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Articles in Refereed Journals:</td>
                                <td>$journals</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Other Peer-Reviewed Scholarly Activity:</td>
                                <td>$otherpeer</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Non-Peer-Reviewed Scholarly Activity:</td>
                                <td>$nonpeer</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Conference Presentations:</td>
                                <td>$conf</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Peer-reviewed Publications: Undergrad Authors:</td>
                                <td>$undergrad</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Peer-reviewed Publications, Submitted:</td>
                                <td>$subm</td></tr>
                                <tr><td>&nbsp;&nbsp;</td><td>Grants:</td>
                                <td>$grants</td></tr>
                            ";
            }
        }

        $hasharray = array(
            'output' => $output
        );
    }
    return isset($hasharray) ? $hasharray : array();
}

function gatherInstitutionalStats($curryear, $acadyear, $queries)
{
    if ($curryear) {
        $acad_start=substr($acadyear, 0,4);
        $acad_end=substr($acadyear,5,4);

        $sql_for_mru="SELECT *
                    FROM cas_cv_items_archive as cia
                    LEFT JOIN degrees_users as u ON
                    u.user_id=cia.user_id
                     ";

        $sql_for_mru_tail=" AND
                            (YEAR(cia.n09)='$acad_start' OR YEAR(cia.n09)='$acad_end')
                            AND NOT(ISNULL(u.user_id))
                        ";
    } else {
        $sql_for_mru = "SELECT *
                                    FROM cas_cv_items_archive as cia
                                    LEFT JOIN cv_items_check_archive as cica ON
                                    cia.cv_item_id=cica.cv_item_id
                                    LEFT JOIN users as u ON
                                    u.user_id=cia.user_id
                                     ";

        $sql_for_mru_tail = " AND acadyear='$acadyear'
                                    AND NOT(ISNULL(u.user_id))
                                    ";
    }

    $sql = $sql_for_mru . $queries['where_booksauth'] . $sql_for_mru_tail;
    $booksauth = mysql_num_rows(mysql_query($sql));

    //Books Edited - cv item types
    $sql = $sql_for_mru . $queries['where_booksedit'] . $sql_for_mru_tail;
    $booksedit = mysql_num_rows(mysql_query($sql));

    //Refereed Journals - cv item types
    $sql = $sql_for_mru . $queries['where_journals'] . $sql_for_mru_tail;
    $journals = mysql_num_rows(mysql_query($sql));

    //Other Peer-Reviewed - cv item types
    $sql = $sql_for_mru . $queries['where_otherpeer'] . $sql_for_mru_tail;
    $otherpeer = mysql_num_rows(mysql_query($sql));

    //Non-Peer Reviewed Pubs - cv item types
    $sql = $sql_for_mru . $queries['where_nonpeer'] . $sql_for_mru_tail;
    $nonpeer = mysql_num_rows(mysql_query($sql));

    //Conf Presentations - cv item types
    $sql = $sql_for_mru . $queries['where_conf'] . $sql_for_mru_tail;
    $conf = mysql_num_rows(mysql_query($sql));

    //Undergrad Peer Reviewed - cv item types
    $sql = $sql_for_mru . $queries['where_undergrad'] . $sql_for_mru_tail;
    $undergrad = mysql_num_rows(mysql_query($sql));
    echo $sql;

    //Submitted - cv item types
    $sql = $sql_for_mru . $queries['where_sumb'] . $sql_for_mru_tail;
    $subm = mysql_num_rows(mysql_query($sql));

    //Grants
    $sql = $sql_for_mru . $queries['where_grants'] . $sql_for_mru_tail;
    $grants = mysql_num_rows(mysql_query($sql));

    $output = "
                <tr><td colspan='2'><b>Entire University</b></td>
                </tr>

                <tr><td>&nbsp;&nbsp;</td><td>Books Authored / Co-Authored:</td>
                <td>$booksauth</td></tr>
                <tr><td>&nbsp;&nbsp;</td><td>Books Edited / Co-Edited:</td>
                <td>$booksedit</td></tr>
                <tr><td>&nbsp;&nbsp;</td><td>Articles in Refereed Journals:</td>
                <td>$journals</td></tr>
                <tr><td>&nbsp;&nbsp;</td><td>Other Peer-Reviewed Scholarly Activity:</td>
                <td>$otherpeer</td></tr>
                <tr><td>&nbsp;&nbsp;</td><td>Non-Peer-Reviewed Scholarly Activity:</td>
                <td>$nonpeer</td></tr>
                <tr><td>&nbsp;&nbsp;</td><td>Conference Presentations:</td>
                <td>$conf</td></tr>
                <tr><td>&nbsp;&nbsp;</td><td>Peer-reviewed Publications: Undergrad Authors:</td>
                <td>$undergrad</td></tr>
                <tr><td>&nbsp;&nbsp;</td><td>Peer-reviewed Publications, Submitted:</td>
                <td>$subm</td></tr>
                <tr><td>&nbsp;&nbsp;</td><td>Grants:</td>
                <td>$grants</td></tr>
            ";
    $hasharray = array(
        'output' => $output
    );

    return $hasharray;
}

function displayStatsMenu($success)
{
    //Just show degree chooser
    $degrees = mysqlFetchRows('degrees', '1 order by degree_name');
    $degree_options = '';
    foreach ($degrees as $degree) $degree_options .= "<option value='$degree[degree_id]'>$degree[degree_name]</option>\n";

    //Show Faculty Options
    $faculties = mysqlFetchRows('divisions', '1 order by name');
    $faculty_options = '';
    foreach ($faculties as $faculty) {
        $faculty_options .= "<option value='$faculty[division_id]'>$faculty[name]</option>\n";
    }

    return array(
        'success'        => $success,
        'degree_options' => $degree_options,
        'faculty_options'=> $faculty_options
    );
}

/**
 * Determine if $acadyear is the current academic year
 *      If this is the current year - should find nothing in the archive
 */
function isCurrentAcademicYear($acadyear)
{
    $sql = "SELECT COUNT(*) FROM cv_items_check_archive WHERE acadyear='$acadyear'";
    $result = mysql_query($sql);
    if (mysql_numrows($result) > 1) {
        return false;
    }
    return true;
}

/**
 * Defines the base queries to use to gather AR stats
 *
 * @param $acadyear - the academic year
 * @return array() - array of queries
 */
function defineStatQueries($acadyear)
{
    $queries = array();
    if ($acadyear == '2009-2010') {
        $queries['where_booksauth'] = "WHERE
                                    cv_item_type_id=8";
        $queries['where_booksedit'] = " WHERE
                                    cv_item_type_id=9";
        $queries['where_journals'] = " WHERE
                                    (cv_item_type_id=2 AND f10=0)";
        $queries['where_otherpeer'] = " WHERE
                                    ((cv_item_type_id=4 AND f10=0)
                                    OR (cv_item_type_id=6 AND f10=0)
                                    OR cv_item_type_id=21
                                    OR cv_item_type_id=28)";
        $queries['where_nonpeer'] = "WHERE
                                    (cv_item_type_id=3
                                    OR cv_item_type_id=5
                                    OR cv_item_type_id=7
                                    OR cv_item_type_id=43
                                    OR cv_item_type_id=82
                                    OR cv_item_type_id=84)";
        $queries['where_conf'] = " WHERE
                                    (cv_item_type_id=61
                                    OR cv_item_type_id=89)";
        $queries['where_sumb'] = " WHERE
                                    ((cv_item_type_id=2 AND f10=1)
                                    OR (cv_item_type_id=4 AND f10=1)
                                    OR (cv_item_type_id=6 AND f10=1))";
        $queries['where_grants'] = " WHERE
                                    cv_item_type_id=20";
        $queries['where_undergrad'] = " WHERE 0";  //not implemented yet.
    } //now the new style items
    else {
        $queries['where_booksauth']  = "WHERE
                                    (cas_type_id=37
                                    OR cas_type_id=39)";
        $queries['where_booksedit'] = " WHERE
                                    cas_type_id=38";
        $queries['where_journals'] = " WHERE
                                    (cas_type_id=35 AND n03=1)";
        $queries['where_otherpeer'] = " WHERE
                                    ((cas_type_id=36 AND n03=1)
                                    OR (cas_type_id=45 AND n23=1)
                                    OR cas_type_id=47
                                    OR cas_type_id=51
                                    OR cas_type_id=55
                                    OR cas_type_id=57)";
        $queries['where_nonpeer'] = "WHERE
                                    ((cas_type_id=36 AND n03=0)
                                    OR (cas_type_id=45 AND n23=0)
                                    OR cas_type_id=79
                                    OR cas_type_id=63
                                    OR cas_type_id=64
                                    OR cas_type_id=65
                                    OR cas_type_id=66
                                    OR cas_type_id=67
                                    OR cas_type_id=81
                                    OR cas_type_id=48
                                    OR cas_type_id=49
                                    OR cas_type_id=50
                                    OR cas_type_id=52
                                    OR cas_type_id=53
                                    OR cas_type_id=54
                                    OR cas_type_id=56
                                    OR cas_type_id=58
                                    OR cas_type_id=59
                                    OR cas_type_id=60
                                    OR cas_type_id=61
                                    OR cas_type_id=62
                                    OR cas_type_id=80
                                    OR cas_type_id=94)";
        $queries['where_conf'] = " WHERE
                                    ((cas_type_id=63 AND n03=0)
                                    OR cas_type_id=46
                                    OR cas_type_id=79
                                    OR (cas_type_id=63 AND n02=1))";
        $queries['where_sumb'] = " WHERE
                                    (cas_type_id=43
                                    )";
        $queries['where_grants'] = " WHERE
                                    cas_type_id=8
                                    AND (n04=2 OR n04=3)";
        $queries['where_undergrad'] = " WHERE
                                    (cas_type_id=43
                                    OR (cas_type_id=35 AND n23=1)
                                    OR (cas_type_id=36 AND n23=1)
                                    OR (cas_type_id=37 AND n23=1)
                                    OR (cas_type_id=39 AND n23=1)
                                    OR (cas_type_id=45 AND n24=1)
                                    OR (cas_type_id=79 AND n23=1)
                                    )";
    }
    return $queries;
}