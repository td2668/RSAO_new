<?php
/**
* This file contains functions and classes for the appropriate annual report section.
*/
/***********************************
* FUNCTIONS
************************************/

function PopulateCourseList($userId, &$tmpl) {

    global $db;
    $status = false;
    $courseList = array();
    
    // 20090326 csn still working on this:
    $termsToDisplay = array('200801','200904'); // or whatever
    $options = array('where_clause' => " AND SUBSTR(c.term,0,4) = {date('Y')} OR SUBSTR(c.term,0,4) = {}");
    
    
    $data = GetCourses($userId);
    //PrintR($data);
    //exit;
    if (sizeof($data) > 0) {        
        // include the jquery library for the checkbox ajax feature
        $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>');
        foreach ($data AS $key => $value) {
            $editScoresList = array('Practicum', 'Clinical', 'On-line');
            $testData = array(
                'course_id' => $value['course_id'],
                'course_title' => CreateCourseTitle($value), //'Test Course Title ' . $i,
                'report_flag' => ($value['report_flag']) ? ' CHECKED' : '',
                'CRN' => $value['crn'], //rand(10,100),
                'Term' => $value['term'], //rand(10,100),
                'num_students' => $value['numstudents'], //rand(10,100),
                'hours' => $value['hours'],
                'delivery_type' => $value['deliverytype'],
                //'num_reporting' => $value['num_reporting'], //rand(10,100),
                'score_le' => $value['q1'],
                'score_as' => $value['q2'],
                'score_fl' => $value['q3'],
                'score_el' => $value['q4'],
                'score_gev' => $value['q5'],
                'score_ses' => $value['sei'],
                'comments1' => $value['comments1'], //"This is a bunch of randomly generated data for now.  \n\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
                'comments2' => $value['comments2'], //"This is a bunch of randomly generated data for now.  \n\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."
                'course_teaching_id' => $value['course_teaching_id'],
                'allow_score_edit' => (in_array($value['deliverytype'], $editScoresList)) ? '' : 'READONLY',
                'score_help' => (in_array($value['deliverytype'], $editScoresList)) ? '<p class="statusMessage">Please manually enter scores if available and submit your forms manually to your Dean via internal mail.</p>' : '',
                'show_hide' => ($value['report_flag']) ? '' : ' style="display:none;"',
            );
            /* 20090326 CSN this was a mistake, the mantis item said to create a saveable drop-down...
            // set up the delivery type drop-down
            $deliveryTypeList = array(
                array('name' => 'Lecture','value' => 'Lecture'),
                array('name' => 'Tutorial','value' => 'Tutorial'),
                array('name' => 'Lab','value' => 'Lab'),
                array('name' => 'Practicum','value' => 'Practicum'),
                array('name' => 'Clinical','value' => 'Clinical'),
                array('name' => 'On-line','value' => 'On-line'),
                array('name'=> '---Counsellors---','value' => ''),
                array('name' => 'Workshop','value' => 'Workshop'),
                array('name' => 'Individual','value' => 'Individual'),
                array('name' => 'Group','value' => 'Group'),
                array('name' => '---Library---','value' => ''),
                array('name' => 'Instruction','value' => 'Instruction'),
                array('name' => 'Item2(TBA)','value' => 'Item2(TBA)'),
                array('name' => 'Item3(TBA)','value' => 'Item3(TBA)'),
            );
            $value['deliverytype'] = ($value['deliverytype'] == '') ? 'Lecture' : $value['deliverytype'];
            $testData['delivery_type_options'] = '';
            foreach($deliveryTypeList as $deliveryOption) {
                $selected = ($deliveryOption['value'] == $value['deliverytype']) ? ' SELECTED' : '';
                $testData['delivery_type_options'] .= "<option value=\"{$deliveryOption['value']}\"{$selected}>{$deliveryOption['name']}</option>\n";
            } // foreach
            */
            $courseList[] = $testData;
        } // foreach
    } else {
        // no courses found!
        $tmpl->addVar('status_message','STATUS','No courses were found for you in the database.');
    } // if

    // test data for now
    /*
    for ($i=1;$i<5;$i++) {
        $testData = array(
            'course_title' => 'Test Course Title ' . $i,
            'delivery_type' => 'Delivery Type',
            'evaluate' => 'SELECTED',
            'num_students' => rand(10,100),
            'num_reporting' => rand(10,100),
            'ses_score' => rand(50,100),
            'comments' => "This is a bunch of randomly generated data for now.  \n\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
            'other' => "This is a bunch of randomly generated data for now.  \n\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."
        );
        $courseList[] = $testData;
    }
    */
    //PrintR($courseList);
    $tmpl->addRows("courses_taught",$courseList);
/*
    // get the data from the database tables
    $sql = "
        SELECT u.first_name, u.last_name, u.department_id, u.department2_id, p1.title, p2.*
        FROM `users` AS u
        LEFT JOIN profiles AS p1 ON p1.user_id = u.user_id
        LEFT JOIN ar_profiles AS p2 ON p2.user_id = u.user_id
        WHERE u.user_id = {$userId}";
    //echo $sql;
    $data = $db->GetRow($sql);
    if (is_array($data) == false or count($data) == 0) {
        // error getting data

    } else {
        //PrintR($data);
        $status = true;
        // populate the  template text data fields
        $tmpl->addVar('PAGE','FIRST',$data['first_name']);
        $tmpl->addVar('PAGE','LAST',$data['last_name']);
        $tmpl->addVar('PAGE','TITLE',$data['title']);
        $tmpl->addVar('PAGE','PROFILE',$data['short_profile']);
        $tmpl->addVar('PAGE','PHILOSOPHY',$data['teaching_philosophy']);
        $tmpl->addVar('PAGE','TOP3',$data['top_3_achievements']);
        $tmpl->addVar('PAGE','GOALS',$data['teaching_goals']);
        $tmpl->addVar('PAGE','ACTIVITIES',$data['activities']);
        // populate the department drop-downs
        $sql = "SELECT department_id, name FROM departments ORDER BY name";
        $departmentData = $db->getAll($sql);
        $departmentList = array();
        $departmentList[] = array("department_id"=>"0",  "name"=>" -- Select a Department -- ");
        if (is_array($departmentData)) {
            //PrintR($departmentData);
            foreach ($departmentData AS $department) {
                $currentDepartment = $department;
                if ($department['department_id'] == $data['department_id']) $currentDepartment['department_id_selected'] = "SELECTED";
                $departmentList[] = $currentDepartment;
                $currentDepartment2 = $department;
                if ($department['department_id'] == $data['department2_id']) $currentDepartment2['department_id_selected'] = "SELECTED";
                $departmentList2[] = $currentDepartment2;
            } // foreach
        } else {
            // couldn't get the department information

        }
        //PrintR($departmentList);
        $tmpl->addRows("department_options",$departmentList);
        $tmpl->addRows("department_options2",$departmentList2);
    } // if
*/
    return $status;

} // function

function GetCourses($userId, $options=array()) {

    global $db;
    
    $whereClause = (isset($options['where_clause'])) ? $options['where_clause'] : false;

    $sql = "
        SELECT c.*, ct.*
        FROM course_teaching AS ct
        LEFT JOIN courses AS c ON c.course_id = ct.course_id
        WHERE ct.user_id = {$userId} 
    ";
    $sql .= ($whereClause) ? $whereClause : '';
    $sql .= "
        ORDER BY c.term DESC, c.subject, c.crsenumb
    ";
    //echo $sql;
    $data = $db->getAll($sql);

    return $data;

} // function GetCourses
?>
