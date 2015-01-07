<?php
/**
* This file contains functions and classes for the appropriate annual report section.
*/
/***********************************
* FUNCTIONS
************************************/
function GenerateWordCaqc($reportUserId, $userId) {

    require_once('includes/ar_courses_taught.inc.php');

    // set up section cv_item_type ids, these are the titles, and this is the order that will be used
    // if you add or change type ids in this array, make sure you also addchange in the switch statement below this code which formats the text
    $sectionType = array(
        'Completed Academic Degrees' => array(10),
        'Advanced Studies in Progress' => array(48), //(Needs a new item - should look same as id 10 (degrees), titled same as this section: 'Advanced...)
        'Academic Appointments' => array(41),
        'Administrative Appointements' => array(49), //: need a new item (same field names as type_id=12)
        'Teaching Experience' => array(50), //: Will be a new item. Will have same field names as type_id=30
        'Books Authored or Edited' => array(8,9), // primary heading is 'SCHOLARLY PARTICIPATION'
        'Referred Publications' => array(2,4,6), // primary heading is 'SCHOLARLY PARTICIPATION'
        'Academc And Professional Presentations' => array(7,17,38,44),
        'Professional Memberships, Qualifications and Experience' => array(36),
        'Professional Qualifications' => array(35),
        'Professional Experience' => array(29,37),
    );

    // make sure this user is allowed to generate the CAQC report


    // get the profile data
    $cvData = GetCvData($reportUserId); // only used for profile information

    // get the cv item data
    $cvItemData = GetCvItems($reportUserId); // get for all header types
    $cvDataByType = array();
    if (is_array($cvItemData) && sizeof($cvItemData) == 0) {
        // no items found
    } else if (is_array($cvItemData)) {
        // got some data, now create an array that is indexed by cv item type
        $cvItems = array();
        foreach ($cvItemData AS $key => $data) {
            // ignore items with no type assigned
            $currentTypeId = $data['cv_item_type_id'];
            if ($currentTypeId > 0) {
                $cvDataByType[$currentTypeId][] = $data;
            } // if
        } // foreach
    } else {
        // an error occured
        $status = false;
        $statusMessage = "An error occurred while getting the cv item data. ({$cvItemData})";
    } // if

    //PrintR($cvItemData);
    //PrintR($cvDataByType);

    // create display data array
    $displayData = array();
    foreach ($sectionType AS $heading => $dataTypes) {
        $addScholarlyHeading = true;
        foreach ($dataTypes AS $type) {
            if (isset($cvDataByType[$type])) {
                // check for sub headings in book/publications section and add as necessary
                if (in_array($type,array(2,4,6,8,9))) {
                    // a book category type
                    // add new heading first if applicable
                    if ($addScholarlyHeading) {
                        $displayData['SCHOLARLY PARTICIPATION'][] = '';
                        $addScholarlyHeading = false;
                    }
                } else {
                    // make all other types upper case
                    $heading = strtoupper($heading);
                } // if
                $displayData[$heading] = array();
                // generate the items in the section and format based on type, add to the display array used by the template
                foreach ($cvDataByType[$type] AS $itemData) {
                    // generate the display text
                    $displayText = '';
                    switch($type) {
                        case 2: // Referred Publications
                        case 4: // Referred Publications
                        case 6: // Referred Publications
                            // date, publication name, journal
                            $year = GetYear($itemData['f2']);
                            $displayText = "{$year}, {$itemData['f4']}, {$itemData['f6']}";
                            break;
                        case 8: // Books Authored or Edited
                        case 9: // Books Authored or Edited
                            // date, name of book, publisher
                            $year = GetYear($itemData['f2']);
                            $displayText = "{$year}, {$itemData['f4']}, {$itemData['f6']}";
                            break;
                        case 7: // Academc And Professional Presentations
                        case 17: // Academc And Professional Presentations
                        case 38: // Academc And Professional Presentations
                        case 44: // Academc And Professional Presentations (20090331 CSN need to check with Trevor on this one, I added it)
                            // date, presentation, organisation
                            $year = GetYear($itemData['f2']);
                            $displayText = "{$year}, {$itemData['f4']} {$itemData['f5']} {$itemData['f6']}";
                            break;
                        case 29: // Professional Experience
                        case 37: // Professional Experience
                        case 35: // Professional Qualifications
                        case 36: // Professional Memberships, Qualifications and Experience
                            // title, organisation
                            $displayText .= ($itemData['f4'] != '') ? "{$itemData['f4']}," : '';
                            $displayText .= "{$itemData['f1']}";
                            break;
                        case 41: // Academic Appointments
                            $year1 = GetYear($itemData['f2']);
                            $year2 = GetYear($itemData['f3']);
                            $displayText = "{$itemData['f4']}, {$itemData['f1']}, {$year1} to {$year2} {$itemData['f6']}";
                            break;
                        case 50: // Teaching Experience
                            // institution, date, courses taught
                            $year = GetYear($itemData['f2']);
                            $displayText = "Institution {$year} {$itemData['f1']} {$itemData['f4']}";
                            break;
                        default:
                            $displayText = 'undefined';
                            break;
                    } // switch
                    if ($displayText != '') $displayData[$heading][] = $displayText;
                } // foreach
            } // if
        } // foreach
    } // foreach

    //PrintR($displayData);exit;

    if ($cvData['status']) {

        // set up some personalized data parameters
        $userFullName = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'];
        $fileName = "{$userFullName}.doc";
        $fileName = CleanFilename($fileName);
        $headerTitle = 'Faculty Annual Report';

        // send the stream the document to the browser as a Word doc to prompt opening by MS Word
        header("Content-type: application/vnd.ms-word");
        header("Content-Disposition: attachment;Filename={$fileName}");

        include('html/ar_report_caqc_word.html.php');
        exit;
    } else {
        // failed to get data
        echo $cvData['status_message'];
        exit;
    } // if

} // function GenerateWordCaqc

function GenerateWordCv($reportUserId, $userId) {

    require_once('includes/ar_courses_taught.inc.php');

    // make sure this user is allowed generate the preview for the requested report
    if ($reportUserId != $userId) {
        // check for permissions / dean, etc.

    } else {
        // anyone can preview their own reports
    } // if

    // get the report data
    $cvData = GetCvData($reportUserId);
    //PrintR($cvData);
    //PrintR($cvData['cv_items']['courses']);exit;

    if ($cvData['status']) {

        // set up some personalized data parameters
        $userFullName = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'];
        $fileName = "{$userFullName}.doc";
        $fileName = CleanFilename($fileName);
        $headerTitle = 'Faculty Annual Report';

        // send the stream the document to the browser as a Word doc to prompt opening by MS Word
        header("Content-type: application/vnd.ms-word");
        header("Content-Disposition: attachment;Filename={$fileName}");
        include('html/ar_report_cv_word.html.php');
        exit;
    } else {
        // failed to get data
        echo $cvData['status_message'];
        exit;
    } // if

} // function GenerateWordCv

function GenerateAnnualReport($reportUserId, $userId, $options = array()) {

    global $configInfo;
	
    require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');
    require_once('includes/ar_cv_item.inc.php');
    require_once('includes/ar_courses_taught.inc.php');

    $localFileName = (isset($options['local_file_name'])) ? $options['local_file_name'] : false;
    //$reportId = (isset($options['report_user_id'])) ? $options['report_user_id'] : false;
    $submittedDate = (isset($options['submitted_date'])) ? $options['submitted_date'] : false;

    // make sure this user is allowed generate the preview for the requested report
    if ($reportUserId != $userId) {
        // check for permissions / dean, etc.

    } else {
        // anyone can preview their own reports
    } // if

    // get the report data
    $cvData = GetCvData($reportUserId);
    //PrintR($cvData);
    //PrintR($cvData['cv_items']['courses']);exit;

    if ($cvData['status']) {

        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set up some personalized data parameters
        $userFullName = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'];
        $fileName = "{$userFullName}.pdf";
        $fileName = CleanFilename($fileName);
        $headerTitle = 'Faculty Annual Report';
        $statusText = 'In progress';
        $statusText = ($submittedDate) ? "Submitted on {$submittedDate}" : $statusText;
        $headerText = $userFullName . ' | ' . $statusText; // . ' | Printed on: ' . date('M d, Y');

        // set up the formatting styles
        $h1FontSize = 16;
        $h2FontSize = 14;
        $h3FontSize = 12;
        $normalFontSize = 12;

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($userFullName);
        $pdf->SetTitle('MRU Annual Report for ' . $userFullName);
        $pdf->SetSubject('A summary of profile and CV information that has been submitted by ' . $userFullName);
        $pdf->SetKeywords("cv, annual report, mrc, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}");

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle , $headerText);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        //set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //set some language-dependent strings
        $pdf->setLanguageArray($l);
        
        $indent1=20;

        // ---------------------------------------------------------
        // Create the PDF Document:

        $pdf->AddPage(); // adds a new page / page break
		
		$tagvs = array('h1' => array(0 => array('h' => '', 'n' => 2), 1 => array('h' => 1.3, 'n' => 1)));
		$pdf->setHtmlVSpace($tagvs);

        // ***************************************   PROFILE SECTION
        $pdf->Bookmark('Profile', 0, 0);
        $tableWidth = array(60,60,60);
        SetNormal($pdf);
        $pdf->SetFillColor(225);
        //$pdf->SetCellPadding(6);
        $pdf->SetX(40);
        $pdf->Cell($tableWidth[0],5,$userFullName,'LT',0,'L',1);
        $statusValue = ucwords($cvData['cv_information']['status']);
        $pdf->Cell($tableWidth[1],5,'Status: ' . $configInfo['status_option_type'][$statusValue],'RT',0,'L',1);
        //if ($submittedDate) $pdf->Cell($tableWidth[2],5,'Submitted: ' . $submittedDate,'',0,'L');
        $pdf->Ln();
        $pdf->SetX(40);
        $pdf->Cell($tableWidth[0],5,$cvData['cv_information']['title'],'L',0,'L',1);
        $workPatternValue = ucwords($cvData['cv_information']['work_pattern']);
        $pdf->Cell($tableWidth[1],5,'Work Pattern: ' . $configInfo['work_pattern_type'][$workPatternValue],'R',0,'L',1);
        //$pdf->Cell($tableWidth[2],5,'','',0,'L');
        $pdf->Ln();
        $pdf->SetX(40);
        $pdf->Cell($tableWidth[0],5,'Dept: '. $cvData['cv_information']['department_name'],'LB',0,'L',1);
        //$pdf->Cell($tableWidth[1],5,'','',0,'L');
        $pdf->Cell($tableWidth[2],5,'','RB',0,'L',1);
        $pdf->Ln(9);
        AddH1($pdf,'1. TEACHING');
        $pdf->SetLeftMargin($indent1);
        if (trim($cvData['cv_information']['teaching_philosophy']) != '') {
            AddH2($pdf, 'Teaching Philosophy');
            AddParagraph($pdf, $cvData['cv_information']['teaching_philosophy']);
        } // if
        if (trim($cvData['cv_information']['top_3_achievements']) != '') {
            AddH2($pdf, 'Top 3 Accomplishments');
            AddParagraph($pdf, $cvData['cv_information']['top_3_achievements']);
        } // if
        if (trim($cvData['cv_information']['teaching_goals']) != '') {
            AddH2($pdf, 'Goals for the coming year');
            AddParagraph($pdf, $cvData['cv_information']['teaching_goals']);
        } // if
		$pdf->Ln(3);
		doHR($pdf);
        // ***************************************   COURSES SECTION
        if (isset($cvData['cv_items']['courses']) && sizeof($cvData['cv_items']['courses']) > 0) {
            AddH2($pdf, 'Courses Taught');
            foreach ($cvData['cv_items']['courses'] AS $key => $data) {
                AddH3($pdf, CreateCourseTitle($data));
                SetNormal($pdf);
                $pdf->Cell(50, 5, 'Number of Students: ' . $data['numstudents'], 0, 0, 'L');
                $pdf->Cell(50, 5, 'Hours: ' . $data['hours'], 0, 0, 'L');
                $pdf->Cell(50, 5, 'SES Score : ' . $data['sei'], 0, 1, 'L');
                //AddLine($pdf, 'num of students: ' . $data['num_students']);
                //AddLine($pdf, 'num reporting: ' . $data['num_reporting']);
                //AddLine($pdf, 'ses score : ' . $data['ses_score']);
                $indent = 25;
                if ($data['comments1'] != '') {
                    $pdf->setX($indent);
                    AddH3($pdf, 'Instructor reflections on evaluations: ');
                    $pdf->setX($indent);
                    AddParagraph($pdf, $data['comments1']);
                } // if
                if ($data['comments2'] != '') {
                    $pdf->setX($indent);
                    AddH3($pdf, 'Key points: ');
                    $pdf->setX($indent);
                    Addparagraph($pdf, $data['comments2']);
                } // if
                $pdf->Ln(5);
            } // foreach
			doHR($pdf);
        } // if
		
        // ***************************************   TEACHING ACTIVITIES SECTION
        if (is_array($cvData['cv_items']['teaching']) && sizeof($cvData['cv_items']['teaching']) > 0) {
            //AddH2($pdf, 'Teaching Related');
            SetNormal($pdf);
            DisplayCvData($cvData['cv_items']['teaching'], $pdf);
        } // if

        // ***************************************   SCHOLARSHIP SECTION
        $pdf->SetLeftMargin(PDF_MARGIN_LEFT);
        $pdf->Ln(5);
        AddH1($pdf, '2. SCHOLARSHIP');
        $pdf->SetLeftMargin($indent1);
        AddH2($pdf, 'Comments on Achievements Relative to Goals');
        AddParagraph($pdf, $cvData['cv_information']['scholarship_achievements']);
        AddH2($pdf, 'Goals for the coming year');
        AddParagraph($pdf, $cvData['cv_information']['scholarship_goals']);
        SetNormal($pdf);
        //DisplayCvData($cvData['cv_items']['research'], $pdf);
		$pdf->Ln(3);
		doHR($pdf);
        // ***************************************   SCHOLARLY ACTIVITIES SECTION
        if (is_array($cvData['cv_items']['research']) && sizeof($cvData['cv_items']['research']) > 0) {
            //AddH1($pdf, 'Scholarly Actvities');
            SetNormal($pdf);
            DisplayCvData($cvData['cv_items']['research'], $pdf);
        } // if

        // ***************************************   SERVICE SECTION
        AddH1($pdf, '3. SERVICE');
        if (trim($cvData['cv_information']['service_achievements']) != '') {
            AddH2($pdf, 'Achievements');
            AddParagraph($pdf, $cvData['cv_information']['service_achievements']);
        } // if
        if (trim($cvData['cv_information']['service_goals']) != '') {
            AddH2($pdf, 'Goals for the coming year');
            AddParagraph($pdf, $cvData['cv_information']['service_goals']);
        } // if

		if ($cvData['cv_information']['chair_duties_flag'] == 1){
			//$pdf->addHTML('<hr>');
			if (trim($cvData['cv_information']['service_chair_goals']) != '') {
            AddH2($pdf, 'Goals as a Chair');
            AddParagraph($pdf, $cvData['cv_information']['service_chair_goals']);
        	} // if
			if (trim($cvData['cv_information']['service_chair_achievements']) != '') {
            AddH2($pdf, 'Achievements as a Chair');
            AddParagraph($pdf, $cvData['cv_information']['service_chair_achievements']);
        	} // if
			if (trim($cvData['cv_information']['service_chair_other']) != '') {
            AddH2($pdf, 'Other Items');
            AddParagraph($pdf, $cvData['cv_information']['service_chair_other']);
        	} // if
		}
		//echo "<pre>";
		//print_r($cvData);
		$pdf->Ln(3);
		doHR($pdf);
        // ***************************************   SERVICE ACTIVITIES SECTION
        if (is_array($cvData['cv_items']['service']) && sizeof($cvData['cv_items']['service']) > 0) {
            //AddH1($pdf, 'Service Activities');
            SetNormal($pdf);
            DisplayCvData($cvData['cv_items']['service'], $pdf);
        } // if

        // ---------------------------------------------------------

        //Close and output PDF document
        if ($localFileName) {
            // send to a local file
            $pdf->Output($localFileName, 'F');
        } else {
            // stream to the browser
            $pdf->Output($fileName, 'D'); // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
        } // if

    } else {
        // failed to get data
        return $cvData['status_message'];
    } // if

} // function GenerateAnnualReport
function doHR(&$pdf) {
	//$x=$pdf->GetX();
	$y=$pdf->GetY();
	$pdf->Line(35,$y,170,$y,array(width=>1,color=>array(75)));
	$pdf->Ln(3);
}

function SetH1(&$pdf) {
    $pdf->SetFont('helvetica', 'B', 12);
}
function SetH2(&$pdf) {
    $pdf->SetFont('helvetica', 'B', 11);
}
function SetH3(&$pdf) {
    $pdf->SetFont('helvetica', 'B', 10);
}
function SetH4(&$pdf) {
    $pdf->SetFont('times', 'B', 12);
}
function SetNormal(&$pdf) {
    $pdf->SetFont('helvetica', '', 9);
}
function AddH1(&$pdf, $text) {
    $pdf->Bookmark($text, 0, 0);
    SetH1($pdf);
    $pdf->Ln(8);
    $pdf->SetTextColor(255);
    $pdf->SetFillColor(75);
	$pdf->setX(15);
    $pdf->Cell(0, 7, $text, '', 1, 'L',1);
    $pdf->SetTextColor(0);
	$pdf->Ln(4);
    
}
function AddH2(&$pdf, $text) {
    //$pdf->Bookmark($text, 0, 0);
    SetH2($pdf);
    $pdf->Cell(0, 7, $text, '', 1, 'L');
}
function AddH3(&$pdf, $text) {
    //$pdf->Bookmark($text, 0, 0);
    SetH3($pdf);
    $pdf->Cell(0, 4, $text, 0, 1, 'L');
}
function AddParagraph(&$pdf, $text) {
    SetNormal($pdf);
    //$pdf->MultiCell(0, 5, $text, 0, 1, 'L');
	$pdf->WriteHTML($text,true, 0, true, true);
	$pdf->Ln(2);
}
function AddLine(&$pdf, $text) {
    SetNormal($pdf);
    $pdf->Cell(0, 5, $text, 0, 1, 'L');
}
function DisplayCvData($cvHeaderData, &$pdf) {
    foreach ($cvHeaderData AS $key1 => $cvHeader) {
        // add the section header?
		$pdf->SetLeftMargin(16);
        AddH2($pdf, $key1);
        foreach ($cvHeader AS $key2 => $data) {
            $cvItemSummary = GetCvItemSummary($data, true);
            $cvItemSummary = ($cvItemSummary != '') ? $cvItemSummary : 'unavailable';
			$pdf->SetLeftMargin(20);
            AddParagraph($pdf, $cvItemSummary);
        } // foreach
    } // foreach

} // function DisplayCvData

function DisplayPersonalCvData($cvHeaderData) {

    foreach ($cvHeaderData AS $key1 => $cvHeader) {
        // add the section header
        echo "<h4>{$key1}</h4>";
        foreach ($cvHeader AS $key2 => $data) {
            echo GetCvItemSummary($data, true) . '<br />';
        } // foreach
    } // foreach

} // function DisplayCvData


function GetCvData($reportUserId) {

    global $db;

    $status = true; // assume true but set to false on any error throughout the function
    $statusMessage = ''; // append throughout the function and return as part of $cvData
    $cvData = array(); // array of results to return from function
    $cvData['cv_information'] = array();
    $cvData['cv_items']['courses'] = array();
    $cvData['cv_items']['teaching'] = array();
    $cvData['cv_items']['research'] = array();
    $cvData['cv_items']['service'] = array();

    require_once('includes/ar_cv_item.inc.php');

    // get the profile information
    $cvInformation = GetPersonData($reportUserId);
    //PrintR($cvInformation);exit;
    if (sizeof($cvInformation) > 0) {
        $cvData['cv_information'] = $cvInformation;
    } else {
        // no data, failed
        $status = false;
        $statusMessage = 'An error occured while getting the personal information.';
    } // if

    // get the course information
    $cvCourseData = GetCourses($reportUserId);
    if (is_array($cvCourseData) && sizeof($cvCourseData) == 0) {
        // no items found
    } else if (is_array($cvCourseData)) {
        // got some data
        $cvData['cv_items']['courses'] = $cvCourseData;
    } else {
        // an error occured
        $status = false;
        $statusMessage = "An error occurred while getting the course data. ({$cvCourseData})";
    } // if

    // get the cv item data and store by header type
    $cvItemData = GetCvItems($reportUserId); // get for all header types
    if (is_array($cvItemData) && sizeof($cvItemData) == 0) {
        // no items found
    } else if (is_array($cvItemData)) {
        // got some data
        $cvItems = array();
        foreach ($cvItemData AS $key => $data) {
            // ignore items with no type assigned
            if ($data['cv_item_type_id'] > 0 && $data['report_flag']) {
				//20091030 Changed by Trevor to switch from category display to types display. 
				$currentHeaderCategory = $data['header_category'];
                //$currentHeaderTitle = $data['header_title'];
				$currentHeaderTitle = $data['type_plural'];
                $cvData['cv_items'][$currentHeaderCategory][$currentHeaderTitle][] = $data; // store the data by header type
            } // if
        } // foreach
    } else {
        // an error occured
        $status = false;
        $statusMessage = "An error occurred while getting the cv item data. ({$cvItemData})";
    } // if

    $cvData['status']  = $status;
    $cvData['status_message'] = $statusMessage;

    return $cvData;

} // if

function SubmitReport($userId, &$tmpl) {

    global $configInfo, $db;

    $status = true;

    // should we check to make sure a report for this employee has not been approved already for this school year?

    // create a new record in the reports table
    $sql = "INSERT INTO `ar_reports` SET `user_id` = {$userId}, `submitted_flag` = 1, `submitted_date` = NOW()";
    $db->Execute($sql);

    // get the id
    $reportId = $db->insert_id();

    // get the user data
    $userInformation = GetPersonData($userId);

    // generate the pdf
    $fileName = (is_array($userInformation) && sizeof($userInformation) == 1) ? $userInformation['full_name'] : 'annual_report';
    $schoolYear = GetSchoolYear(time());
    $localFileName = sprintf($schoolYear . '_%05d_%08d_',$userId,$reportId) . $fileName . '.pdf';
    $localFilePath = $configInfo['file_root'] . '/documents/annual_reports/' . $localFileName;
    $options = array(
        'local_file_name' => $localFilePath,
        'submitted_date' => date('M d, Y'),
    );
    GenerateAnnualReport($userId, $userId, $options);

    // make sure the file is there

    // update the report table
    $sql = "UPDATE `ar_reports` SET `filename` = '{$localFileName}' WHERE `report_id` = {$reportId}";
    $db->Execute($sql);

    // update the status on the page
    if ($status) {
        $statusText = 'The report has been submitted.';
    } else {
        $statusText = 'An error occurred and the report was not submitted properly.';
    } // if
    $tmpl->addVar('Page','STATUS', $statusText);

    return $status;

} // function SubmitReport

function ApproveReport($reportId, $userId, &$tmpl) {

    global $db;

    // make sure this person really can approve

    // js - confirm want to do this
    // js - check for comments, warn if none, or verify with comments?

    // mark approved_flag in reports table
    if ($reportId > 0) {
        $sql = "UPDATE `ar_reports` SET `approved_flag` = 1, `approved_date` = NOW(), `approved_user_id` = {$userId} WHERE report_id = {$reportId}";
    } // if
    $db->Execute($sql);


    // email staff member?
    // reset for next year
        // clear 'show on report' flag on all cv items
        // copy goals, etc. to 'previous year' fields
        // clear other year-specific fields in profile, ar_profile, ana/maybe user

} // function ApproveReport

function CreateReportList($userId, &$tmpl, $options = array()) {

    global $db, $configInfo;

    $userInformation = GetPersonData($userId); // get information for this user
    $deanFlag = (isset($options['dean_flag'])) ? $options['dean_flag'] : false;
    $chairFlag = (isset($options['chair_flag'])) ? $options['chair_flag'] : false;
    $deanOrChair = $deanFlag || $chairFlag;
    $reportList = array();

    // include the jquery library for the comments ajax feature
    $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');

    $whereClause = ($deanFlag || $chairFlag) ? " WHERE YEAR(r.submitted_date) = 2009 " : " WHERE r.user_id = {$userId} ";
    $whereClause .= ($deanFlag && isset($_SESSION['user_info']['dean_division_id']) && $_SESSION['user_info']['dean_division_id'] != '') ? " AND di.division_id = {$_SESSION['user_info']['dean_division_id']} " : '';
    $whereClause .= ($chairFlag && isset($_SESSION['user_info']['chair_department_id']) && $_SESSION['user_info']['chair_department_id'] != '') ? " AND dep.department_id = {$_SESSION['user_info']['chair_department_id']} " : '';
    // not working as expected: $groupClause = ($deanFlag || $chairFlag) ? " GROUP BY r.user_id " : '';
    $sql = "
        SELECT r.*,
            u1.first_name, u1.last_name, CONCAT(u1.first_name,' ',u1.last_name) AS person_name,
            u1.department_id,
            CONCAT(u2.first_name,' ',u2.last_name) AS approved_name,
            dep.name AS department_name, CONCAT(chair.first_name,' ',chair.last_name) AS chair_name,
            di.name AS division_name, CONCAT(dean.first_name,' ',dean.last_name) AS dean_name
        FROM ar_reports AS r
        LEFT JOIN users AS u1 ON u1.user_id = r.user_id
        LEFT JOIN users AS u2 ON u2.user_id = r.approved_user_id
        LEFT JOIN departments AS dep ON dep.department_id = u1.department_id
        LEFT JOIN users AS chair ON chair.user_id = dep.chair
        LEFT JOIN divisions AS di ON di.division_id = dep.division_id
        LEFT JOIN users AS dean ON dean.user_id = di.dean
        {$whereClause}
        ORDER BY division_name, department_name, person_name, r.submitted_date DESC, r.report_id DESC";
    //echo $sql;
    $items = $db->getAll($sql);
    $index = 0;
    if (!$deanOrChair) {
        // add default first row, for new (current) report
        $reportList[$index]["tr_class"] = 'oddrow';
        $reportList[$index]["status"] = 'In Progress';
        $reportList[$index]["type"] = 'new';
        $reportList[$index]["year"] = GetSchoolYear(time());
        $reportList[$index]["user_id"] = $userId;
        $reportList[$index]['person'] = $userInformation['last_name'];
        $index++;
    } // if

    if (is_array($items) && sizeof($items) == 0) {
        // no items found
    } else if (is_array($items)) {
        // populate the list
        $trClass = 'oddrow';
        $lastUserId = null;
        $lastDepartment = null;
        foreach($items AS $itemData) {
            // display the department heading if this is a dean
            if ( ($chairFlag || $deanFlag) && $lastDepartment != $itemData['department_id']) {
                $reportList[$index]["tr_class"] = 'oddrow';
                $reportList[$index]["status"] = $itemData['department_name'];
                $reportList[$index]["type"] = 'heading';
                $index++;
                $trClass = 'oddrow';
            } // if
            // only show the most recent report for each user for dean and chair report lists
            if (!$deanOrChair || ($deanOrChair && $itemData['user_id'] != $lastUserId)) {
                $reportList[$index]["tr_class"] = $trClass;
                $reportList[$index]['report_id'] = $itemData['report_id'];
                $reportList[$index]['filepath'] = '/documents/annual_reports/' . $itemData['filename'];
                $reportList[$index]['submitted_flag'] = $itemData['submitted_flag'];
                $reportList[$index]['submitted_date'] = $itemData['submitted_date'];
                $reportList[$index]['year'] = substr($itemData['submitted_date'],0,4);
                $reportList[$index]['approved_flag'] = $itemData['approved_flag'];
                $reportList[$index]['approved_date'] = $itemData['approved_date'];
                $reportList[$index]['approved_by_user_id'] = $itemData['approved_user_id'];
                $reportList[$index]['approved_by_name'] = $itemData['approved_name'];
                $reportList[$index]['comments'] = $itemData['comments'];
                $reportList[$index]['person'] = $itemData['last_name'];
                if ($itemData['approved_flag']) {
                    $reportList[$index]["status"] = 'Approved on ' . $itemData['approved_date'];
                    $reportList[$index]["type"] = 'approved';
                    $reportList[$index]["tr_class"] .= ' signed';
                } else {
                    $reportList[$index]["status"] = 'Submitted on ' . $itemData['submitted_date'];
                    $reportList[$index]["type"] = 'submitted';
                    if ($deanFlag) {
                        $approveLink = ' | <a href="javascript:;" onClick="ConfirmApprove(\'?page=ar_dean&report_id=' . $itemData['report_id'] . '&mr_action=approve\');">Approve</a>';
                        $approveLink .= ' | <a href="javascript:;" onClick="$(\'#commentFormDiv' . $itemData['report_id'] . ', #commentLink' . $itemData['report_id'] . '\').toggle(200);" id="commentLink">Comment</a>';
                        $reportList[$index]['approve_link'] = $approveLink;
                    } // if
                } // if
                $reportList[$index]["comment_link"] = (trim($itemData['comments']) != '') ? '<span class="ar_comments" title="' . trim($itemData['comments']) . '">YES (View)</a>' :'';
                $lastUserId = $itemData['user_id'];
                $trClass = ($trClass == 'oddrow') ? 'evenrow' : 'oddrow';
                $index++;
            } // if
            $lastDepartment = $itemData['department_id'];
        } // foreach
    } else {
        // an error occured
        $status = false;
        $statusMessage = "An error occurred while getting the report list.";
        //echo $sql;
    } // if
    //PrintR($reportList);

    $tmpl->addRows('annual_report_list', $reportList);

    if (in_array($_SESSION['loggeduser'], $configInfo['admin'])) {
        $adminTools = '<div style="border:1px #ccc solid; padding:2px;">' . "\n";
        $adminTools .= 'Submit on behalf of:<br />' . "\n";
        $adminTools .= '<select id="admin" name="admin">' . "\n";
        $sql = "SELECT * FROM users WHERE first_name != '' AND last_name != '' ORDER BY first_name, last_name";
        $users = $db->getAll($sql);
        foreach($users AS $userData) {
            $adminTools .= "<option value=\"{$userData['user_id']}\" ";
            $adminTools .= "onClick=\"ConfirmSubmitOnBehlaf('?page=ar_review_submit&mr_action=submit&user_id={$userData['user_id']}&target={$userData['first_name']} {$userData['last_name']}')\">";
            $adminTools .= "{$userData['first_name']} {$userData['last_name']}</option>\n";
        } // foreach
        $adminTools .= '</select>' . "\n";
        $adminTools .= '</div>' . "\n";
        $tmpl->addVar('tools_box','ADMIN_TOOLS',$adminTools);
    } // if

} // function PopulateList


/** (currently not used) get the annual report data depending on whether the given user id is for a regular user, a dean, or a chair
*
*   @param      integer     $userId     the user id of the logged in user
*   @param      array       $options    an optional array with options for the function
*   @return     array       the array of rows of data, one row per report
*/
function GetAnnualReports($userId, $options = array()) {

    global $db, $configInfo;
    $now=getdate();
    $thisyear=$now['year'];

    $userInformation = GetPersonData($userId); // get information for this user
    $deanFlag = (isset($options['dean_flag'])) ? $options['dean_flag'] : false;
    $chairFlag = (isset($options['chair_flag'])) ? $options['chair_flag'] : false;
    $reportData = array();

    $whereClause = ($deanFlag || $chairFlag) ? " WHERE YEAR(r.submitted_date) = $thisyear " : " WHERE r.user_id = {$userId} ";
    if ($deanFlag) {
        $orderByClause = 'ORDER BY YEAR(r.submitted_date) DESC, u1.department_id, r.approved_flag DESC, u1.last_name ASC';
    } else if ($chairFlag) {
        $orderByClause = 'ORDER BY YEAR(r.submitted_date) DESC, r.approved_flag DESC, u1.last_name ASC';
    } else {
        $orderByClause = 'ORDER BY r.submitted_date DESC, r.id DESC';
    } // if
    // not working as expected: $groupClause = ($deanFlag || $chairFlag) ? " GROUP BY r.user_id " : '';
    $sql = "
        SELECT r.*,
            u1.first_name, u1.last_name, CONCAT(u1.first_name,' ',u1.last_name) AS person_name,
            CONCAT(u2.first_name,' ',u2.last_name) AS approved_name
        FROM ar_reports AS r
        LEFT JOIN users AS u1 ON u1.user_id = r.user_id
        LEFT JOIN users AS u2 ON u2.user_id = r.approved_user_id
        {$whereClause}
        {$orderByClause}
    ";
    //echo $sql;
    $items = $db->getAll($sql);
    if (is_array($items) && sizeof($items) == 0) {
        // no items found
        if (!$deanFlag && !$chairFlag) {
            // add default first row, for new (current) report
            $reportData[0]["tr_class"] = 'oddrow';
            $reportData[0]["status"] = 'In Progress';
            $reportData[0]["type"] = 'new';
            $reportData[0]["year"] = GetSchoolYear(time());
            $reportData[0]["user_id"] = $userId;
            $reportData[0]['person'] = $userInformation['last_name'];
        } // if
    } else if (is_array($items)) {
        // populate the list
        $trClass = 'oddrow';
        $index = 0;
        if (!$deanFlag && !$chairFlag) {
            // add default first row, for new (current) report
            $reportData[0]["tr_class"] = 'oddrow';
            $reportData[0]["status"] = 'In Progress';
            $reportData[0]["type"] = 'new';
            $reportData[0]["year"] = GetSchoolYear(time());
            $reportData[0]["user_id"] = $userId;
            $reportData[0]['person'] = $userInformation['last_name'];
            $trClass = 'even';
            $index = 1;
        } // if
        $lastUserId = null;
        foreach($items AS $itemData) {
            // only show the most recent report for each user for dean and chair report lists
            $deanOrChair = $deanFlag || $chairFlag;
            if (!$deanOrChair || ($deanOrChair && $itemData['user_id'] != $lastUserId)) {
                $reportData[$index]["tr_class"] = $trClass;
                $reportData[$index]['report_id'] = $itemData['report_id'];
                $reportData[$index]['filepath'] = '/documents/annual_reports/' . $itemData['filename'];
                $reportData[$index]['submitted_flag'] = $itemData['submitted_flag'];
                $reportData[$index]['submitted_date'] = $itemData['submitted_date'];
                $reportData[$index]['year'] = substr($itemData['submitted_date'],0,4);
                $reportData[$index]['approved_flag'] = $itemData['approved_flag'];
                $reportData[$index]['approved_date'] = $itemData['approved_date'];
                $reportData[$index]['approved_by_user_id'] = $itemData['approved_user_id'];
                $reportData[$index]['approved_by_name'] = $itemData['approved_name'];
                $reportData[$index]['comments'] = $itemData['comments'];
                $reportData[$index]['person'] = $itemData['last_name'];
                if ($itemData['approved_flag']) {
                    $reportData[$index]["status"] = 'Approved on ' . $itemData['approved_date'];
                    $reportData[$index]["type"] = 'approved';
                    $reportData[$index]["tr_class"] .= ' signed';
                } else {
                    $reportData[$index]["status"] = 'Submitted on ' . $itemData['submitted_date'];
                    $reportData[$index]["type"] = 'submitted';
                    if ($deanFlag) {
                        $approveLink = ' | <a href="javascript:;" onClick="ConfirmApprove(\'?page=ar_dean&report_id=' . $itemData['report_id'] . '&mr_action=approve\');">Approve</a>';
                        $approveLink .= ' | <a href="javascript:;" onClick="$(\'#commentFormDiv' . $itemData['report_id'] . ', #commentLink' . $itemData['report_id'] . '\').toggle(200);" id="commentLink">Comment</a>';
                        $reportData[$index]['approve_link'] = $approveLink;
                    } // if
                } // if
                $reportData[$index]["comment_link"] = (trim($itemData['comments']) != '') ? '<span class="ar_comments" title="' . trim($itemData['comments']) . '">YES (View)</a>' :'';
                $lastUserId = $itemData['user_id'];
                $trClass = ($trClass == 'oddrow') ? 'evenrow' : 'oddrow';
                $index++;
            } // if
        } // foreach
    } else {
        // an error occured
        $status = false;
        $statusMessage = "An error occurred while getting the annual report data.";
        echo $sql;
    } // if

    return $reportData;

} // function GetAnnualReports


/** get stats for annual report listing on internal home page for deans and chairs
*	Note: Added code for Directors and Associate Deans, but snce the roles are basically the same as Chairs and Deans left the 
*   arStats[role] exactly the same. 
*   @return     array       the stats
*/
function GetArStats($arOptions = array()) {


    global $db, $configInfo;
    $now=getdate();
    $thisyear=$now['year'];
    $academic_year=$thisyear-1 . '/' . $thisyear;
    $arStats = array(
        'num_not_signed' => 'unknown',
        'num_signed' => 'unknown',
        'status' => true,
        'status_message' => '',
        'academic_year' => $academic_year        
    );
    // get unsigned count
    $whereClause = ($arOptions['dean_flag'] || $arOptions['chair_flag'] || $arOptions['director_flag']  || $arOptions['associate_dean_flag']) ? " WHERE YEAR(r3.submitted_date) = $thisyear " : " WHERE r3.user_id = {$_SESSION['user_info']['user_id']} ";
    //Modifed by TD to allow multiple divisions
    if($arOptions['dean_flag'] && isset($_SESSION['user_info']['dean_division_id']) && $_SESSION['user_info']['dean_division_id'] != '') {
    	$divs=explode(',',$_SESSION['user_info']['dean_division_id']);
    	$whereClause.= " AND (";
    	$count=1;
    	foreach($divs as $div){ 
    		$whereClause.= " d.division_id = $div ";
    		if($count < count($divs)) $whereClause.='OR';
    		$count++;
    	}
    	$whereClause.=')';
    }
    
    if($arOptions['associate_dean_flag'] && isset($_SESSION['user_info']['associate_dean_division_id']) && $_SESSION['user_info']['assocaite_dean_division_id'] != '') {
    	$divs=explode(',',$_SESSION['user_info']['associate_dean_division_id']);
    	$whereClause.= " AND (";
    	$count=1;
    	foreach($divs as $div){ 
    		$whereClause.= " d.division_id = $div ";
    		if($count < count($divs)) $whereClause.='OR';
    		$count++;
    	}
    	$whereClause.=')';
    }
    
    
    // and multiple depts
    if($arOptions['chair_flag'] && isset($_SESSION['user_info']['chair_department_id']) && $_SESSION['user_info']['chair_department_id'] != '') {
    	$divs=explode(',',$_SESSION['user_info']['chair_department_id']);
    	$whereClause.= " AND (";
    	$count=1;
    	foreach($divs as $div){ 
    		$whereClause.= " d.department_id = $div ";
    		if($count < count($divs)) $whereClause.='OR';
    		$count++;
    	}
    	$whereClause.=')';
    }
    
    // and multiple depts
    if($arOptions['director_flag'] && isset($_SESSION['user_info']['director_department_id']) && $_SESSION['user_info']['director_department_id'] != '') {
    	$divs=explode(',',$_SESSION['user_info']['director_department_id']);
    	$whereClause.= " AND (";
    	$count=1;
    	foreach($divs as $div){ 
    		$whereClause.= " d.department_id = $div ";
    		if($count < count($divs)) $whereClause.='OR';
    		$count++;
    	}
    	$whereClause.=')';
    }
    
    
    //$whereClause .= ($arOptions['dean_flag'] && isset($_SESSION['user_info']['dean_division_id']) && $_SESSION['user_info']['dean_division_id'] != '') ? " AND d.division_id = {$_SESSION['user_info']['dean_division_id']} " : '';
    //$whereClause .= ($arOptions['chair_flag'] && isset($_SESSION['user_info']['chair_department_id']) && $_SESSION['user_info']['chair_department_id'] != '') ? " AND d.department_id = {$_SESSION['user_info']['chair_department_id']} " : '';

    $sql = "
        SELECT COUNT( r.report_id ) AS num_reports, r.approved_flag
        FROM ar_reports AS r
        INNER JOIN (
            SELECT r3.user_id, MAX( r3.submitted_date ) AS max_date
            FROM ar_reports AS r3
            LEFT JOIN users AS u ON r3.user_id = u.user_id
            LEFT JOIN departments AS d ON d.department_id = u.department_id
            {$whereClause}
            GROUP BY r3.user_id
            ) AS r2 ON r2.user_id = r.user_id
        AND r2.max_date = r.submitted_date
        GROUP BY approved_flag
    ";
    //echo $sql;
    $items = $db->getAll($sql);
    if (is_array($items)) {
        $arStats['num_not_signed'] = 0;
        $arStats['num_signed'] = 0;
        if (sizeof($items) > 0) {
            //PrintR($items);
            foreach ($items AS $item) {
                if ($item['approved_flag']) {
                    $arStats['num_signed'] = $item['num_reports'];
                } else {
                    $arStats['num_not_signed'] = $item['num_reports'];
                } // if
            } // foreach
        } // if
    } else {
        // an error occured
        $arStats['status'] = false;
        $arStats['status_message'] .= "An error occurred while getting the annual report statistics for unsigned reports.  ";
        //echo $sql;
    } // if
    
    if($arOptions['dean_flag'] || $arOptions['associate_dean_flag']) {
        $arStats['role']="Dean";
        $divs=explode(',',$_SESSION['user_info']['associate_dean_division_id']);
    	$whereClause= "  (";
    	$count=1;
    	foreach($divs as $div){ 
	    	if($div=='')$div=0;
    		$whereClause.= " divisions.division_id = $div ";
    		if($count < count($divs)) $whereClause.='OR';
    		$count++;
    	}
    	$whereClause.=')';
        
        
        $sql="SELECT COUNT(users.user_id) as faculty_reporting
        FROM users 
        LEFT JOIN departments ON (users.department_id=departments.department_id)
        LEFT JOIN divisions ON (departments.department_id=divisions.division_id)
        WHERE {$whereClause}
        ";
        $result=$db->getRow($sql);
        if(is_array($result)) $arStats['faculty_reporting']=$result['faculty_reporting'];
    }
    else {
        $arStats['role']=  'Chair';
        $divs=explode(',',$_SESSION['user_info']['chair_department_id']);
    	$whereClause= " (";
    	$count=1;
    	foreach($divs as $div){ 
    		$whereClause.= " departments.department_id = $div ";
    		if($count < count($divs)) $whereClause.='OR';
    		$count++;
    	}
    	$whereClause.=')';
        
        
        $sql="SELECT COUNT(users.user_id) as faculty_reporting
        FROM users 
        INNER JOIN departments ON (users.department_id=departments.department_id)
        WHERE {$whereClause}
        ";
        $result=$db->getRow($sql);
        //var_dump($result);
        if(is_array($result)) $arStats['faculty_reporting']=$result['faculty_reporting'];
    }

    return $arStats;

} // function GetArStats
//
//
//
?>
