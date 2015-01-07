<?php

/**
 * Generate the Annual Report PDF
 *
 * @global array $configInfo current detected confiuration
 * @param int $reportUserId user id of the pdf to be generated
 * @param int $userId User Id that is currently generating the pdf
 * @param array $options array of topoins
 * @return string Return message if something went wrong
 */
function GenerateAnnualReport ( $reportUserId, $userId, $options = array( ) ) {

    global $configInfo;
    //error_reporting(E_ALL);
    require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');
    require_once('includes/cv_item.inc.php');
    require_once('includes/ar_courses_taught.inc.php');
    require_once('includes/pdf_functions.php');
    $cvData = array( );

    $localFileName = (isset( $options['local_file_name'] )) ? $options['local_file_name'] : false;
    //$reportId = (isset($options['report_user_id'])) ? $options['report_user_id'] : false;
    $submittedDate = (isset( $options['submitted_date'] )) ? $options['submitted_date'] : false;

    // make sure this user is allowed generate the preview for the requested report
    if ( $reportUserId != $userId ) {
        // check for permissions / dean, etc.
    } else {
        // anyone can preview their own reports
    } // if
    // get the report data
    $cvData = GetCvData( $reportUserId, true );
    //PrintR($cvData);
    //PrintR($cvData['cv_items']['courses']);exit;

    if ( $cvData['status'] ) {
        //Extend the class to allow custom footer
        //ToDo: Fix up header in this section
        class MYPDF extends TCPDF {
            public function Footer() {    
                $mydate=date('M j, Y',mktime());
                //$mydate='Jan 21, 2011';            
                $cur_y = $this->GetY();
                $ormargins = $this->getOriginalMargins();
                $this->SetTextColor(0, 0, 0);            
                //set style for cell border
                $line_width = 0.85 / $this->getScaleFactor();
                $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
                //$this->Line($ormargins['left'],$cur_y-5, $ormargins['right'],$cur_y-5,array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
                //$this->SetLineStyle(array('width' => 0, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
                $this->Line( 15, $cur_y, 190, $cur_y, array( width => 0.2, color => array( 0,0,0 ) ) );
                if (empty($this->pagegroups)) {
                    $pagenumtxt = $this->l['w_page'].' '.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
                } else {
                    $pagenumtxt = $this->l['w_page'].' '.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
                }        
                $this->SetY($cur_y);
                //Print page number and date
                
                if ($this->getRTL()) {
                    $this->SetX($ormargins['left']);
                    $this->Cell(0, 0, $mydate, 'T', 0, 'R');
                    $this->SetX($ormargins['right']);
                    $this->Cell(0, 0, $pagenumtxt, 'T', 0, 'L');
                } else {
                    $this->SetX($ormargins['left']+7);
                    $this->Cell(20, 0, $pagenumtxt, 0, 0, 'L');
                    $this->SetX($ormargins['right']-60);
                    $this->Cell(20, 0, 'Printed on '. $mydate, 0, 0, 'R');
                }
                
            }
        }
        
        // create new PDF document
        $pdf = new MYPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
        $pdf->SetDisplayMode( 'default', 'continuous' );
        // set up some personalized data parameters
        $userFullName = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'];
        $fileName = "{$userFullName}.pdf";
        $fileName = CleanFilename( $fileName );
        $headerTitle = 'Faculty Annual Report';
        $statusText = 'In progress';
        $statusText = ($submittedDate) ? "Submitted on {$submittedDate}" : $statusText;
        $headerText = $userFullName . ' | ' . $statusText; // . ' | Printed on: ' . date('M d, Y');

        // set document information
        //$pdf->SetDisplayMode( 'real', 'OneColumn', 'UseNone' ); // added by TDavis to avoid jumping at bottom of pages
        $pdf->SetCreator( PDF_CREATOR );
        $pdf->SetAuthor( $userFullName );
        $pdf->SetTitle( 'MRU Annual Report for ' . $userFullName );
        $pdf->SetSubject( 'A summary of profile and CV information that has been submitted by ' . $userFullName );
        $pdf->SetKeywords( "cv, annual report, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}" );

        // set default header data
        $pdf->SetHeaderData( PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle, $headerText );

        // set header and footer fonts
        $pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, 'B', PDF_FONT_SIZE_MAIN ) );
        $pdf->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );

        //set margins
        $pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
        $pdf->SetHeaderMargin( 10 );
        $pdf->SetFooterMargin( PDF_MARGIN_FOOTER );

        //set auto page breaks
        $pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );

        //set image scale factor
        $pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

        //set some language-dependent strings
        $pdf->setLanguageArray( $l );

        $indent1 = 20;
        
        if(isset($cvData['cv_information']['optout'])) if($cvData['cv_information']['optout']) $optout=true; else $optout=false;
        else $optout=false;

        // ---------------------------------------------------------
        // Create the PDF Document:

        $pdf->AddPage(); // adds a new page / page break

        $tagvs = array( 'h1' => array( 0 => array( 'h' => '', 'n' => 2 ), 1 => array( 'h' => 1.3, 'n' => 1 ) ) );
        $pdf->setHtmlVSpace( $tagvs );
        
        //turn off headers now - just image
        //$pdf->setPrintHeader(false);
        $pdf->SetHeaderData( PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, '', '' );
        $pdf->SetHeaderMargin( 15 );

        // ***************************************   PROFILE SECTION
        $pdf->Bookmark( 'Profile', 0, 0 );
        $tableWidth = array( 60, 80, 80 );
        SetNormal( $pdf );
        //$pdf->SetFillColor( 225 );
        $pdf->SetFillColor( 192,217,229 );
        SetNormalBold($pdf);
        //$pdf->SetCellPadding(6);
        $pdf->SetX( 30 );
        $pdf->Cell( $tableWidth[0], 5, $userFullName, 'LT', 0, 'L', 1 );
        $statusValue = ucwords( $cvData['cv_information']['status'] );
        $pdf->Cell( $tableWidth[1], 5, 'Status: ' . $configInfo['status_option_type'][$statusValue], 'RT', 0, 'L', 1 );
        //if ($submittedDate) $pdf->Cell($tableWidth[2],5,'Submitted: ' . $submittedDate,'',0,'L');
        $pdf->Ln();
        $pdf->SetX( 30 );
        $pdf->Cell( $tableWidth[0], 5, $cvData['cv_information']['title'], 'L', 0, 'L', 1 );
        $workPatternValue = ucwords( $cvData['cv_information']['work_pattern'] );
        $pdf->Cell( $tableWidth[1], 5, 'Work Pattern: ' . $configInfo['work_pattern_type'][$workPatternValue], 'R', 0, 'L', 1 );
        //$pdf->Cell($tableWidth[2],5,'','',0,'L');
        $pdf->Ln();
        $pdf->SetX( 30 );
        $pdf->Cell( $tableWidth[0], 5, 'Dept: ' . $cvData['cv_information']['department_name'], 'LB', 0, 'L', 1 );
        //$pdf->Cell($tableWidth[1],5,'','',0,'L');
        $pdf->Cell( $tableWidth[2], 5, '', 'RB', 0, 'L', 1 );
        $pdf->Ln( 9 );
        SetNormal($pdf);
        //$pdf->SetLeftMargin( $indent1 );
        if ( trim( $cvData['cv_information']['short_profile'] ) != '' ) {
            AddH2( $pdf, 'Profile' );
            AddParagraph( $pdf, $cvData['cv_information']['short_profile'] );
        }
        
        
        if($optout) AddH2( $pdf, '** All CV Information Forwarded Under Separate Cover' );
        
        
        AddH1( $pdf, 'Teaching' );
        //$pdf->SetLeftMargin( $indent1 );
        if ( trim( $cvData['cv_information']['teaching_philosophy'] ) != '' ) {
            AddH2( $pdf, 'Teaching Philosophy' );

            $temp = htmlentities( $cvData['cv_information']['teaching_philosophy'], ENT_COMPAT, cp1252 );
            AddParagraph( $pdf, $cvData['cv_information']['teaching_philosophy'] );
        } // if
        if ( trim( $cvData['cv_information']['teaching_goals_lastyear'] ) != '' ) {
            AddH2( $pdf, 'Last Year\'s Goals' );
            AddParagraph( $pdf, $cvData['cv_information']['teaching_goals_lastyear'] );
        } // if
        if ( trim( $cvData['cv_information']['top_3_achievements'] ) != '' ) {
            AddH2( $pdf, 'Top 3 Accomplishments' );
            AddParagraph( $pdf, $cvData['cv_information']['top_3_achievements'] );
        } // if
        if ( trim( $cvData['cv_information']['teaching_goals'] ) != '' ) {
            AddH2( $pdf, 'Goals for the Coming Year' );
            AddParagraph( $pdf, $cvData['cv_information']['teaching_goals'] );
        } // if
        if ( trim( $cvData['cv_information']['activities'] ) != '' ) {
            AddH2( $pdf, 'Teaching Equivalent Activities' );
            AddParagraph( $pdf, $cvData['cv_information']['activities'] ); 
        } // if
        $pdf->Ln( 3 );
        
        //////////////////////////   Courses Taught Section///////////////////////////
        if (isset($cvData['cv_items']['courses']) && sizeof($cvData['cv_items']['courses']) > 0) {
            AddH1($pdf, 'Courses Taught');
            $counter=1;
            foreach ($cvData['cv_items']['courses'] AS $key => $data) {
                
                AddH3($pdf, CreateCourseTitle($data));
                SetNormal($pdf);
                $pdf->Cell(50,5, 'Term: '.$data['term'],0,0,'L');
                $pdf->Cell(50, 5, 'Number of Students: ' . $data['numstudents'], 0, 0, 'L');
                $pdf->Cell(50, 5, 'Hours: ' . $data['hours'], 0, 1,  'L');
                if($data['report_flag']) {
                    //$pdf->Cell(50, 5, 'SES Score : ' . $data['sei'], 0, 1, 'L');
                    
                    // Add the 5 sectional scores
                    $pdf->Ln(3);
                    $pdf->Cell(70, 5, 'Learning Environment (Q.1 - Q.4): ', 0, 0, 'L');
                    $pdf->Cell(70, 5, $data['q1'], 0, 1, 'L');
                    $pdf->Cell(70, 5, 'Assistance to Students (Q.5 - Q.7): ', 0, 0, 'L');
                    $pdf->Cell(70, 5, $data['q2'], 0, 1, 'L');
                    $pdf->Cell(70, 5, 'Facilitation of Learning (Q.8 - Q.14): ', 0, 0, 'L');
                    $pdf->Cell(70, 5, $data['q3'], 0, 1, 'L');
                    $pdf->Cell(70, 5, 'Evaluation of Learning (Q.15 - Q.19): ', 0, 0, 'L');
                    $pdf->Cell(70, 5, $data['q4'], 0, 1, 'L');
                    $pdf->Cell(70, 5, 'General Eval of Instructor (Q.20 - Q.21): ', 0, 0, 'L');
                    $pdf->Cell(70, 5, $data['q5'], 0, 1, 'L');
                    SetNormalBold($pdf);
                    $pdf->Cell(70, 5, 'Mean SES: ', 0, 0, 'L');
                    $pdf->Cell(70, 5, $data['sei'], 0, 1, 'L');
                    SetNormal($pdf);
                    $pdf->Ln(5);
                    
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
                    //$pdf->Ln(5);
                } //if report_flag
                $pdf->Ln(10);
                if($counter < count($cvData['cv_items']['courses'])) thinHR($pdf);
                $counter++;
            } // foreach
            
        } // if
        
        

        // ***************************************   TEACHING ACTIVITIES SECTION
        if ( is_array( $cvData['cv_items']['teaching'] ) && sizeof( $cvData['cv_items']['teaching'] ) > 0 && !$optout) {
            AddH1( $pdf, 'Teaching Related Activities' );
            SetNormal( $pdf );
            $count=0;
            foreach ( $cvData['cv_items']['teaching'] as $cvItem ) {
                DisplayCvData( $cvItem, $pdf, false, 'teaching' );
                $count++;
                if($count < count($cvData['cv_items']['teaching'])) {
                    $pdf->Ln( 3 );
                    doHR( $pdf );
                }
            }
        }

        // ***************************************   SCHOLARSHIP SECTION
        
        ///SKIP IF EMPTY////
        if($cvData['cv_information']['scholarship_achievements']!='' && $cvData['cv_information']['scholarship_goals']!=''){
            $pdf->SetLeftMargin( PDF_MARGIN_LEFT );
            $pdf->Ln( 5 );
            AddH1( $pdf, 'Scholarship' );
            $pdf->SetLeftMargin( $indent1 );
            if ( trim( $cvData['cv_information']['scholarship_goals_lastyear'] ) != '' ) {
                AddH2( $pdf, 'Last Year\'s Goals' );
                AddParagraph( $pdf, $cvData['cv_information']['scholarship_goals_lastyear'] );
            }
            AddH2( $pdf, 'Comments on Achievements Relative to Goals' );
            AddParagraph( $pdf, $cvData['cv_information']['scholarship_achievements'] );
            AddH2( $pdf, 'Goals for the Coming Year' );
            AddParagraph( $pdf, $cvData['cv_information']['scholarship_goals'] );
            SetNormal( $pdf );
            //DisplayCvData($cvData['cv_items']['research'], $pdf);
            $pdf->Ln( 3 );
            //doHR( $pdf );
        }

        // ***************************************   Scholarship ACTIVITIES SECTION
        if ( is_array( $cvData['cv_items']['scholarship'] ) && sizeof( $cvData['cv_items']['scholarship'] ) > 0 && !$optout) {
            AddH1( $pdf, 'Scholarship Related Activities' );
            SetNormal( $pdf );
            $count=0;
            foreach ( $cvData['cv_items']['scholarship'] as $key=>$cvItem ) {
                DisplayCvData( $cvItem, $pdf, false, 'scholarship' );
                $count++;
                if($count < count($cvData['cv_items']['scholarship'])) {
                    $pdf->Ln( 3 );
                    doHR( $pdf );
                }
            }
        }
        // ***************************************   SERVICE SECTION
        AddH1( $pdf, 'Service' );
        if ( trim( $cvData['cv_information']['service_goals_lastyear'] ) != '' ) {
                AddH2( $pdf, 'Last Year\'s Goals' );
                AddParagraph( $pdf, $cvData['cv_information']['service_goals_lastyear'] );
            }
        if ( trim( $cvData['cv_information']['service_achievements'] ) != '' ) {
            AddH2( $pdf, 'Achievements' );
            AddParagraph( $pdf, $cvData['cv_information']['service_achievements'] );
        } // if
        if ( trim( $cvData['cv_information']['service_goals'] ) != '' ) {
            AddH2( $pdf, 'Goals for the coming year' );
            AddParagraph( $pdf, $cvData['cv_information']['service_goals'] );
        } // if

        if ( $cvData['cv_information']['chair_duties_flag'] == 1 ) {
            //$pdf->addHTML('<hr>');
            if ( trim( $cvData['cv_information']['service_chair_goals'] ) != '' ) {
                AddH2( $pdf, 'Goals as a Chair' );
                AddParagraph( $pdf, $cvData['cv_information']['service_chair_goals'] );
            } // if
            if ( trim( $cvData['cv_information']['service_chair_achievements'] ) != '' ) {
                AddH2( $pdf, 'Achievements as a Chair' );
                AddParagraph( $pdf, $cvData['cv_information']['service_chair_achievements'] );
            } // if
            if ( trim( $cvData['cv_information']['service_chair_other'] ) != '' ) {
                AddH2( $pdf, 'Other Items' );
                AddParagraph( $pdf, $cvData['cv_information']['service_chair_other'] );
            } // if
        }
        //echo "<pre>";
        //print_r($cvData);
        $pdf->Ln( 3 );
        //doHR( $pdf );

        // ***************************************   Service ACTIVITIES SECTION
        if ( is_array( $cvData['cv_items']['service'] ) && sizeof( $cvData['cv_items']['service'] ) > 0  && !$optout) {
            AddH1( $pdf, 'Service Related Activities' );
            SetNormal( $pdf );
            $count=0;
            foreach ( $cvData['cv_items']['service'] as $cvItem ) {
                DisplayCvData( $cvItem, $pdf, false, 'service' );
                $count++;
                if($count < $cvData['cv_items']['service']){
                    $pdf->Ln( 3 );
                    doHR( $pdf );
                }
            }
        }
        if ( trim( $options['comments'] ) != '' ) {
            $pdf->AddPage();
            AddH1( $pdf, "Dean's Comments" );
            AddParagraph( $pdf, $options['comments'] );
        }
        
        //Close and output PDF document
        if ( $localFileName ) {
            // send to a local file
            
            $pdf->Output( $localFileName, 'F' );
        } else {
            
            // stream to the browser
            $pdf->Output( $fileName, 'D' ); // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
        } // if
    } else {
        // failed to get data
        
        return $cvData['status_message'];
    } // if
}

// function GenerateAnnualReport

/**
 * Generate the CV for the current user
 *
 * @global array $configInfo currently detected config
 * @param int $userId userId to generated the PDF for
 * @param string $flag determines which CV set to generate
 * @return string return error message if something goes wrong
 */
function GenerateCV ( $userId, $flag='', $style='apa',$local_file_name='' ) {

    global $configInfo;
    
    error_reporting(E_ALL);
    //require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');
    require_once('includes/cv_item.inc.php');
    require_once('includes/pdf_functions.php');
    if (!$style) {
        $style='apa';
    }
    
    $cvData = array( );
    // get the profile information
    $cvInformation = GetPersonData( $userId );
    //PrintR($cvInformation);exit;
    if ( sizeof( $cvInformation ) > 0 ) {
        $cvData['cv_information'] = $cvInformation;
    } else {
        // no data, failed
        $status = false;
        $statusMessage = 'An error occured while getting the personal information.';
    }
    $localFileName = (isset( $local_file_name)) ? $local_file_name : false;
    //$reportId = (isset($options['report_user_id'])) ? $options['report_user_id'] : false;
    //$submittedDate = (isset( $options['submitted_date'] )) ? $options['submitted_date'] : false;

    // make sure this user is allowed generate the preview for the requested report
    if ( $reportUserId != $userId ) {
        // check for permissions / dean, etc.
    } else {
        // anyone can preview their own reports
    } // if
    // get the report data
    $cvItemData = GetCvItems( $userId, array( ), $flag ); // get for all header types

    if ( is_array( $cvItemData ) && sizeof( $cvItemData ) == 0 ) {
        // no items found
    } else if ( is_array( $cvItemData ) ) {
        // got some data
        $cvItems = array( );
        foreach ( $cvItemData AS $key => $data ) {
            // ignore items with no type assigned
            if ( $data['cas_type_id'] > 0  ) {
                //20091030 Changed by Trevor to switch from category display to types display.

                $currentHeaderCategory = GetCasHeading( $data['cas_type_id'] );
                //$currentHeaderTitle = $data['header_title'];
                $currentHeaderTitle = GetHeading( $data['cas_type_id'] );
                $cvData['cv_items'][$currentHeaderCategory][$currentHeaderTitle][] = $data; // store the data by header type
            } // if
        } // foreach
    } else {
        // an error occured
        $status = false;
        $statusMessage = "An error occurred while getting the cv item data. ({$cvItemData})";
    } // if
    //PrintR($cvData);
    //PrintR($cvData['cv_items']['courses']);exit;
    
    if ( sizeof( $cvData['cv_items'] ) >= 0 ) {
        
        // create new PDF document
        $pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
        
        //var_dump($pdf);
        $pdf->SetDisplayMode( 'default', 'continuous' );
        // set up some personalized data parameters
        $userFullName = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'];
        $fileName = "{$userFullName}.pdf";
        $fileName = CleanFilename( $fileName );
        $headerTitle = "Curriculum Vitae: $userFullName";
        //$statusText = 'In progress';
        //$statusText = ($submittedDate) ? "Submitted on {$submittedDate}" : $statusText;
        $headerText = ' ' ;

        // set document information
        $pdf->SetDisplayMode( 'real', 'OneColumn', 'UseNone' ); // added by TDavis to avoid jumping at bottom of pages
        $pdf->SetCreator( PDF_CREATOR );
        $pdf->SetAuthor( $userFullName );
        $pdf->SetTitle( "Curriculum Vitae for $userFullName" );
        $pdf->SetSubject( 'CV: ' . $userFullName );
        $pdf->SetKeywords( "cv, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}" );

        // set default header data
        $pdf->SetHeaderData( PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle, $headerText );

        // set header and footer fonts
        $pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );
        $pdf->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );

        //set margins
        $pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
        $pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
        $pdf->SetFooterMargin( PDF_MARGIN_FOOTER );

        //set auto page breaks
        $pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );

        //set image scale factor
        $pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

        //set some language-dependent strings
        $pdf->setLanguageArray( $l );

        //$indent1 = 20;

        // ---------------------------------------------------------
        // Create the PDF Document:

        $pdf->AddPage(); // adds a new page / page break

        $tagvs = array( 'h1' => array( 0 => array( 'h' => '', 'n' => 2 ), 1 => array( 'h' => 1.3, 'n' => 1 ) ) );
        $pdf->setHtmlVSpace( $tagvs );

        $pdf->SetHeaderData( PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, '', '' );
        
        // ***************************************   
        if ( is_array( $cvData['cv_items'] ) && sizeof( $cvData['cv_items'] ) > 0 ) {
            //AddH2($pdf, 'Teaching Related');
            SetNormal( $pdf );
            foreach ( $cvData['cv_items'] as $heading => $cvItem ) {

                AddH1( $pdf, $heading );

                DisplayCvData( $cvItem, $pdf, true );
                
                $pdf->Ln( 3 );
                //doHR( $pdf );
            }
        }
        
        //Close and output PDF document
        if ( $localFileName ) {
            // send to a local file
            $pdf->Output( $localFileName, 'F' );
        } else {
            // stream to the browser
            $pdf->Output( $fileName, 'D' ); // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
        } // if
    } else {
        // failed to get data
        return $cvData['status_message'];
    } // if
}


/**
 * Generate the CV for the current user
 *
 * @global array $configInfo currently detected config
 * @param int $userId userId to generated the PDF for
 * @param string $flag determines which CV set to generate
 * @return string return error message if something goes wrong
 */
function GenerateCAQC ( $userId, $flag='', $style='apa' ) {

    global $configInfo;
    global $db;
	
	
    //require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');
    require_once('includes/cv_item.inc.php');
    require_once('includes/pdf_functions.php');
    if (!$style) {
        $style='apa';
    }
    
    $cvData = array( );
    // get the profile information
    $cvInformation = GetPersonData( $userId );
    //PrintR($cvInformation);exit;
    if ( sizeof( $cvInformation ) > 0 ) {
        $cvData['cv_information'] = $cvInformation;
        //print_r($cvInformation);
    } else {
        // no data, failed
        $status = false;
        $statusMessage = 'An error occurred while getting the personal information.';
    }
    $localFileName = (isset( $options['local_file_name'] )) ? $options['local_file_name'] : false;
    //$reportId = (isset($options['report_user_id'])) ? $options['report_user_id'] : false;
    //$submittedDate = (isset( $options['submitted_date'] )) ? $options['submitted_date'] : false;

    // make sure this user is allowed generate the preview for the requested report
    if ( $reportUserId != $userId ) {
        // check for permissions / dean, etc.
    } else {
        // anyone can preview their own reports
    } // if

    if (1) {
		// Extend the TCPDF class to create custom Header and Footer
		class MYPDF extends TCPDF {
		
			//Page header
			public function Header() {
				global $userId;
				$cvData = array( );
    			// get the profile information
			    $cvInformation = GetPersonData( $userId );
			    //PrintR($cvInformation);exit;
			    if ( sizeof( $cvInformation ) > 0 ) {
			        $cvData['cv_information'] = $cvInformation;
			        //print_r($cvInformation);
			    } else {
			        // no data, failed
			        $status = false;
			        $statusMessage = 'An error occurred while getting the personal information.';
			    }
				
				// Logo
				$image_file = K_PATH_IMAGES.PDF_HEADER_LOGO;
				
				$this->Image($image_file, 14, 12, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
				// Set font
				$this->setFont( 'coprg', '', 13 );

        		$headerTitle = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'].' - '.$cvData['cv_information']['title'];
        		$headerText = "Curriculum Vitae" ; 
				$this->SetY($this->GetY()+5);
				
				// Title
				$this->Cell(0, 
							15, 
							$headerText, 
							0, 
							2, 
							'C', 	//align
							0, 		//fill
							'', 	//link
							0, 		//stretch
							false, 	//ignore min
							'C', 	//calign
							'C');	//valign

				$this->Cell(0, 
							15, 
							$headerTitle, 
							0, 
							2, 
							'C', 	//align
							0, 		//fill
							'', 	//link
							0, 		//stretch
							false, 	//ignore min
							'C', 	//calign
							'C');	//valign
							
				
			}
		
			
		}
		
		
        // create new PDF document
        $pdf = new MYPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

        // set up some personalized data parameters
        $userFullName = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'];
        $userTitle=$cvData['cv_information']['title'];
        $fileName = "{$userFullName}.pdf";
        $fileName = CleanFilename( $fileName );

        // set document information
        $pdf->SetDisplayMode( 'real', 'OneColumn', 'UseNone' ); // added by TDavis to avoid jumping at bottom of pages
        $pdf->SetCreator( PDF_CREATOR );
        $pdf->SetAuthor( $userFullName );
        $pdf->SetTitle( "Curriculum Vitae for $userFullName" );
        $pdf->SetSubject( 'CV: ' . $userFullName );
        $pdf->SetKeywords( "cv, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}" );
        
        
        define('CAQC_NORMAL_FONT_SIZE',10);
        define('CAQC_SMALLER_FONT_SIZE',9);

        // set header data
        $pdf->SetHeaderMargin( 15 );
        $pdf->SetFooterMargin( 20 );
        //$pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );
        //print_r($pdf->GetHeaderFont());
        $pdf->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );
        //$headerTitle = "$userFullName - ". $cvData['cv_information']['title'];
        //$headerText = "Curriculum Vitae" ;        
        //$pdf->SetHeaderData( PDF_HEADER_LOGO, 30 , $headerTitle, $headerText );
		

        //set margins
        $pdf->SetMargins( 	 15, //PDF_MARGIN_LEFT
        					 38, //PDF_MARGIN_TOP
        					 15 ); //PDF_MARGIN_RIGHT


        //set auto page breaks
        $pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );

        //set image scale factor
        $pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

        //set some language-dependent strings
        $pdf->setLanguageArray( $l );

        //$indent1 = 20;

        // ---------------------------------------------------------
        // Create the PDF Document:

        $pdf->AddPage(); // adds a new page / page break
        
        //Now reset some params for first-page only header
        $pdf->setPrintHeader(false);
        $pdf->SetMargins( 	 15, //PDF_MARGIN_LEFT
        					 15, //PDF_MARGIN_TOP
        					 15 ); //PDF_MARGIN_RIGHT
		$pdf->SetHeaderMargin( 10 );
		
		
        $tagvs = array( 'h1' => array( 0 => array( 'h' => '', 'n' => 2 ), 1 => array( 'h' => 1.3, 'n' => 1 ) ) );
        $pdf->setHtmlVSpace( $tagvs );

        //$pdf->SetHeaderData( PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, '', '' );
        
        $pdf->SetCellHeightRatio(1.2);

		$pdf->ln(5);
		
        // ***************************************   Degrees
        
        $sql="SELECT item.n05,YEAR(item.n18) as year,types.name as degree_name,cas_institutions.name as institution_name
            FROM cas_cv_items as item
            LEFT JOIN cas_degree_types as types on item.n02=types.id
            LEFT JOIN cas_institutions on item.n04=cas_institutions.id
            WHERE user_id = {$userId}
            AND cas_type_id=1 
            AND item.n13=2
            AND mycv2=1
            ORDER BY rank desc";
        $list=$db->GetAll($sql);
        if($list){
        	AddCAQC1($pdf,'Completed Academic Degrees');
        	CAQC_Header($pdf, array('Degree Name','Subject Area','Where Completed','Date of Completion'),array(0.18,0.3,0.32,0.2));
            //echo("<pre>");
           // print_r($list);
           $count=1;
            foreach($list as $key=>$item){
                if($item['year']==0) $item['year']='';
                //echo("Before Call: ". $evenodd."<br>");
                $maxH=CAQC_list($pdf,array($item['degree_name'],$item['n05'],$item['institution_name'],$item['year']),array('L','L',"l",'C'),array(0.18,0.3,0.32,0.2));
                if($count != count($list)) cvline($pdf,$maxH);
                $count++;
                //echo("AfterCall: $evenodd<br>");
                $pdf->Ln($maxH);
                //$list[$key]['return']=$pdf->GetLastH();
            }
            //print_r($list);
        }
        
        
        // ************************* Advanced Studies in Progress
        
        $sql="SELECT item.n05,YEAR(item.n19) as year,types.name as degree_name,cas_institutions.name as institution_name
            FROM cas_cv_items as item
            LEFT JOIN cas_degree_types as types on item.n02=types.id
            LEFT JOIN cas_institutions on item.n04=cas_institutions.id
            WHERE user_id = {$userId}
            AND cas_type_id=1 
            AND mycv2=1
            AND (item.n13=1 OR item.n13=3)
            ORDER BY rank desc";
        $list=$db->GetAll($sql);
        if($list){
        	
        	AddCAQC1($pdf,'Advanced Studies in Progress');
			CAQC_Header($pdf, array('Degree Name','Subject Area','Where Enrolled','Est. Completion'),array(0.2,0.3,0.32,0.18));
            //echo("<pre>");
           // print_r($list);
           	$count=1;
            foreach($list as $item){
                if($item['year']==0) $item['year']='';
                $maxH=CAQC_list($pdf,array($item['degree_name'],$item['n05'],$item['institution_name'],$item['year']),array('L','L',"L",'C'),array(0.2,0.3,0.32,0.18));
                if($count != count($list)) cvline($pdf,$maxH);
                $count++;
                $pdf->Ln($maxH);
            }
        }
        
        // ************************* Academic Appointments
        
        $sql="SELECT 
                item.n01,
                YEAR(item.n09) as start_year,
                YEAR(item.n18) as end_year,
                cas_institutions.name as institution_name,
                depts.name as subject
              FROM
                cas_cv_items as item
                LEFT JOIN cas_institution_departments as depts on item.n13=depts.id
                LEFT JOIN cas_institutions on item.n04=cas_institutions.id
              WHERE user_id = {$userId}
                AND item.n01 NOT LIKE '%Chair%'
                AND item.n01 NOT LIKE '%Co-ordinator%'
                AND item.n01 NOT LIKE '%Coordinator%'
                AND item.n01 NOT LIKE '%Director%'
                AND item.n01 NOT LIKE '%Manager%'
                AND cas_type_id=3 
                AND mycv2=1
              ORDER BY rank desc";
          $list=$db->GetAll($sql);
          if($list){
          	AddCAQC1($pdf,'Academic Appointments');
        	CAQC_Header($pdf, array('Appointment Level','Institution','Dates','Subject Area'),array(0.25,0.35,0.15,0.25));
            //echo("<pre>");
            //echo ($sql);
           //print_r($list);
           	$count=1;
          	foreach($list as $item){
            	if($item['start_year']==0 && $item['start_year']==0) $years='';
            	elseif($item['start_year']==0 ) $years='    -'.$item['end_year'];
            	elseif($item['end_year']==0 ) $years=$item['start_year'].' -';
            	else $years=$item['start_year'].' - '.$item['end_year'];
            
            	$maxH=CAQC_list($pdf,array($item['n01'],$item['institution_name'],$years,$item['subject']),array('L','L',"L",'L'),array(0.25,0.35,0.15,0.25));
            	if($count != count($list)) cvline($pdf,$maxH);
                $count++;
                $pdf->Ln($maxH);
            }
        }    
        
        // ************************* Administrative Appointments
        
        $sql="SELECT 
                item.n01,
                YEAR(item.n09) as start_year,
                YEAR(item.n18) as end_year,
                cas_institutions.name as institution_name,
                depts.name as subject
              FROM
                cas_cv_items as item
                LEFT JOIN cas_institution_departments as depts on item.n13=depts.id
                LEFT JOIN cas_institutions on item.n04=cas_institutions.id
              WHERE user_id = {$userId}
                AND (item.n01 LIKE '%Chair%'
                    OR item.n01 LIKE '%Co-ordinator%'
                    OR item.n01 LIKE '%Coordinator%'
                    OR item.n01 LIKE '%Director%'
                    OR item.n01 LIKE '%Manager%')
                AND cas_type_id=3 
                AND mycv2=1
              ORDER BY rank desc";
          $list=$db->GetAll($sql);
          if($list){
          	AddCAQC1($pdf,'Administrative Appointments');
        	CAQC_Header($pdf, array('Appointment Level','Institution','Dates'),array(0.4,0.42,0.18));
            //echo("<pre>");
           // print_r($list);
           	$count=1;
          	foreach($list as $item){
	            if($item['start_year']==0 && $item['start_year']==0) $years='';
	            elseif($item['start_year']==0 ) $years='    -'.$item['end_year'];
	            elseif($item['end_year']==0 ) $years=$item['start_year'].' -';
	            else $years=$item['start_year'].' - '.$item['end_year'];
	            
	            $maxH=CAQC_list($pdf,array($item['n01'],$item['institution_name'],$years),array('L','L',"L"),array(0.4,0.42,0.18));
	            if($count != count($list)) cvline($pdf,$maxH);
                $count++;
	            $pdf->Ln($maxH);
            }
        } 
        
        // *************************  TEACHING EXPERIENCE SECTION
        
        $output=array();
        $useditems=array();
        
        $sql="SELECT * FROM user_disable_banner WHERE user_id=$userId";
        $disable_banner=$db->GetRow($sql);
        
        if(!$disable_banner){
	        //Grab all the info from the 'courses' table - group by course number
	        $sql="SELECT subject,crsenumb,crsedescript FROM course_teaching LEFT JOIN courses on (course_teaching.course_id=courses.course_id) WHERE course_teaching.user_id={$userId} group by CONCAT(subject,crsenumb)";
	        $courses=$db->GetAll($sql);
	        //echo("<pre>");
	        //print_r($courses);
	        
	        
	        $sql="SELECT * FROM cas_institutions WHERE name LIKE '%Mount Royal%' OR name LIKE '%MRU%' OR name LIKE '%MRC%'";
			$inst=$db->getAll($sql);
			$list='AND (n04=0 OR ';
			foreach($inst as $one) $list.="n04=$one[id] OR ";
			$list.="n02=9999) ";//filler
			
	        foreach($courses as $course) if($course['subject'] !='' AND $course['crsenumb']!=''){
	        	//Then pull all year data for the particular course
	        	$sql="SELECT course_teaching_id, LEFT(term,4) as term FROM course_teaching LEFT JOIN courses on (course_teaching.course_id=courses.course_id) WHERE course_teaching.user_id={$userId} AND subject='$course[subject]' AND crsenumb='$course[crsenumb]'  GROUP BY LEFT(term,4)";
	        	$results=$db->GetAll($sql);
	        	
	        	//now make the array key the course_teaching_id to allow sorting
	        	$years=array();
	        	foreach($results as $result) $years[$result['course_teaching_id']]=$result['term'];
	        	
	        	//Now load the course record from the cv items into the same table
	        	
				
	        	$sql="SELECT cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n05 as descrip FROM cas_cv_items WHERE cas_type_id=29 AND user_id=$userId $list AND (n01 LIKE '$course[subject] $course[crsenumb]' OR n01 LIKE '$course[subject]$course[crsenumb]') AND mycv2=1 ORDER BY start_year";
	        	$type29=$db->GetAll($sql);
	        	
	        	if(count($type29)>0) {
	        	//Add entries to the years table and then sort on the term
	        		foreach($type29 as $item){
	        			$useditems[]=$item['cv_item_id'];
	        			//Search (the hard way) for duplicate entries before adding
	        			for($x=$item['start_year'];$x<=$item['end_year'];$x++) {
	        				$found=false;
	        				foreach($years as $year) if($year==$x) $found=true;
	        				if (!$found) $years[]=$x;
	        			}
	        			
	        		}
	        	
	        	}
	        	
	        	$excludes='';
        		if(count($useditems)>0){
        			foreach($useditems as $item) $excludes.="AND cv_item_id != $item ";
        		}
	        	
	        	
	        	//Sort it all by year
	        	sort($years);
	        	//echo("<pre>");
	        	//print_r($years);
	        	
	        	
	        	//Now assemble - continuous stretch of years becomes xxxx-yyyy. 
	        	$start=false; $end=false;
	        	
				foreach($years as $year){
	        		if($start==false) $start=$year;
	        		elseif($end==false && $year==$start+1) $end=$year;
	        		elseif($year==($end+1)) $end=$year;
	        		else {
	        			if($end==false) $output[]=array('course'=>"$course[subject]$course[crsenumb]",'year'=>$start,'descrip'=>$course['crsedescript']);
	        			else $output[]=array('course'=>"$course[subject]$course[crsenumb]",'year'=>"$start - $end",'descrip'=>$course['crsedescript']);
	        			$start=false; $end=false;
	        		}
	        		
	        	}
	        	if($start==false) $output[]=array('course'=>"$course[subject]$course[crsenumb]",'year'=>$year,'descrip'=>$course['crsedescript']);
	        	elseif($end==false) $output[]=array('course'=>"$course[subject]$course[crsenumb]",'year'=>$start,'descrip'=>$course['crsedescript']);
	        	else $output[]=array('course'=>"$course[subject]$course[crsenumb]",'year'=>"$start - $end",'descrip'=>$course['crsedescript']);
	
	        }//Next course
	     
	    }//banner enabled
	        
        //Now get remaining MRU ones as selected
        
        $sql="SELECT n01 as course, cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n04,n05 as crsedescript, ca.name
       		FROM cas_cv_items 
       		LEFT JOIN cas_institutions as ca on(cas_cv_items.n04=ca.id)
       		WHERE cas_type_id=29 
       			AND user_id=$userId 
       			and mycv2=1 
       			AND YEAR(n09)!=0 
       			$excludes 
       			AND (
       			(ca.name LIKE '%Mount Royal%' OR ca.name LIKE '%MRU%' OR ca.name LIKE '%MRC%')
       			OR ca.name IS NULL)
       			GROUP BY course
       			";

       	$courses=$db->GetAll($sql);
       	
       	//These data are grouped by course - now get all items 
       	// note - if the name is NULL then it is assumed to be MRU
       	foreach($courses as $course){
       		$sql="SELECT n01 as course, cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n04,n05 as crsedescript, ca.name
       		FROM cas_cv_items 
       		LEFT JOIN cas_institutions as ca on(cas_cv_items.n04=ca.id)
       		WHERE cas_type_id=29 
       			AND user_id=$userId 
       			and mycv2=1 
       			AND YEAR(n09)!=0 
       			$excludes 
       			AND (
       			(ca.name LIKE '%Mount Royal%' OR ca.name LIKE '%MRU%' OR ca.name LIKE '%MRC%')
       			OR ca.name IS NULL)
       			AND n01='$course[course]'
       			";
       		$datelist=$db->GetAll($sql);
       		//echo("<PRE>");
        	//print_r($datelist);
        	
       		if(count($datelist)>0){
       			$years=array();
       			foreach($datelist as $item){
        			$useditems[]=$item['cv_item_id'];
        			//Search (the hard way) for duplicate entries before adding
        			for($x=$item['start_year'];$x<=$item['end_year'];$x++) {
        				$found=false;
        				foreach($years as $year) if($year==$x) $found=true;
        				if (!$found) $years[]=$x;
        			}//for
        			
        		}//foreach
       			
       		}//if count>0
       		//echo("<PRE>");
        	//print_r($years);
        	
        	//Sort it all by year
        	sort($years);
        	
        	//Now assemble - continuous stretch of years becomes xxxx-yyyy. 
        	$start=false; $end=false;
        	
			foreach($years as $year){
        		if($start==false) $start=$year;
        		elseif($end==false && $year==$start+1) $end=$year;
        		elseif($year==($end+1)) $end=$year;
        		else {
        			if($end==false) $output[]=array('course'=>"$course[course]",'year'=>$start,'descrip'=>$course['crsedescript']);
        			else $output[]=array('course'=>"$course[course]",'year'=>"$start - $end",'descrip'=>$course['crsedescript']);
        			$start=false; $end=false;
        		}
        		
        	}
        	if($start==false) $output[]=array('course'=>"$course[course]",'year'=>$year,'descrip'=>$course['crsedescript']);
        	elseif($end==false) $output[]=array('course'=>"$course[course]",'year'=>$start,'descrip'=>$course['crsedescript']);
        	else $output[]=array('course'=>"$course[course]",'year'=>"$start - $end",'descrip'=>$course['crsedescript']);
        	
        	
       	}//foreach remainder
       			
        
        //echo("<PRE>");
        //print_r($years);
        
        //Sort by year (otherwise its by course)
        foreach($output as $key=>$row){
        	$course1[$key]=$row['course'];
        	$year1[$key]=$row['year'];
        	$descrip1[$key]=$row['descrip'];
        }
        //print_r($output);
        if((array_multisort($year1, SORT_DESC, SORT_STRING, $course1, SORT_ASC ,SORT_STRING, $descrip1, SORT_ASC, SORT_STRING, $output))) 
        	$biglist[]=array('institution'=>'Mount Royal University','courses'=>$output);
        
        else {
        //echo "ERROR SORTING_____________________________________________________";
        }
               
        
        //Now deal with all the courses in the cas_cv_items that weren't found above. 
        
        $excludes='';
        if(count($useditems)>0){
        	
        	foreach($useditems as $item) $excludes.="AND cv_item_id != $item ";
        }
        
        //Grab all unique institutions
        //Note: May need to collapse multiple names
       $sql="SELECT cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n04,n05, cas_institutions.name 
       		FROM cas_cv_items 
       		LEFT JOIN cas_institutions on(cas_cv_items.n04=cas_institutions.id)
       		WHERE cas_type_id=29 
       			AND user_id=$userId 
       			and mycv2=1 
       			AND YEAR(n09)!=0 
       			$excludes 
       			AND n04 != 0
       			AND id IS NOT NULL
       			GROUP BY n04";

       $institutions=$db->GetAll($sql);
       
       //For each institution, grab courses. If they did individual entries per year then this is a problem. But they have to fix it.
       foreach($institutions as $inst){
       		$sql="SELECT n01 as course, cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n04,n05, cas_institutions.name 
		       		FROM cas_cv_items 
		       		LEFT JOIN cas_institutions on(cas_cv_items.n04=cas_institutions.id)
		       		WHERE cas_type_id=29 
		       			AND user_id=$userId 
		       			and mycv2=1 
		       			AND YEAR(n09)!=0 
		       			$excludes 
		       			AND n04=$inst[n04]
		       			GROUP BY course,start_year,end_year
		       			ORDER BY start_year DESC
		       			";
		    $instlist=$db->getAll($sql);
		    if($instlist){
		    	
		    	$output=array();
		    	foreach($instlist as $item){
		    		//I don't collect by years here. Just a simple dump. Only issue is that a start year with no end year
		    		//  might be a '2013 - on' or just '2013'. I assume the latter
		    		if($item['end_year']==0 OR $item['start_year']==$item['end_year']) $output[]=array('course'=>$item['course'],'year'=>$item['start_year'],'descrip'=>$item['n05']);
		    		else $output[]=array('course'=>$item['course'],'year'=>"$item[start_year] - $item[end_year]",'descrip'=>$item['n05']);
		    	}
		    }
		    $biglist[]=array('institution'=>$inst['name'],'courses'=>$output);
       }//next institution
       	
       	if(count($biglist)>0){
	        $spacer=array(0.25,0.14,0.18,0.43);
	        AddCAQC1($pdf,'Teaching Experience');
	        CAQC_Header($pdf,array('Institution','Years','Course','Description'),$spacer);
	        
	        foreach($biglist as $ikey=>$institution){
	        	foreach($institution['courses'] as $key=>$course){
	        		if($key==0) {
	        			if($ikey !=0) cvline($pdf,0);
	        			$maxH=CAQC_List($pdf,array($institution['institution'],$course['year'],$course['course'],$course['descrip']),array('L','L','L','L'),$spacer);
	        			$pdf->Ln($maxH);
	        		}
	        		else {
	        			$maxH=CAQC_List($pdf,array('',$course['year'],$course['course'],$course['descrip']),array('L','L','L','L'),$spacer);
	        			$pdf->Ln($maxH);
	        			//echo($maxH);
	        			//echo("<BR>");
	        		}
	        	}
	        }
	     }
        
        // *************************
        
        require_once('includes/caqc.php');
        require_once('includes/cv_functions.php');
        
        $sql="SELECT YEAR(n09) as theyear, cas_cv_items.* FROM cas_cv_items WHERE user_id=$userId AND mycv2=1 ORDER BY theyear DESC";
        $allitems=$db->getAll($sql);
        //echo('<pre>');
        //print_r($allitems);
        if($allitems) {
        	foreach($allitems as $key=>$item) {
        		$flags=new CaqcFlags();
         		$flags->GetStats($item['cv_item_id']);
        		$sql="UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id=$item[cv_item_id]";
        		$result=$db->Execute($sql);
        		$allitems[$key]['caqc_flags']=$flags->AsInt();
        		//echo("$sql<br>")
        	}
        	
        	//Now do a pre-check to see if it is worth printing a header. I know this is inefficient but
        	// I didn't fel like revising everything to fix a bug. Shoot me. 
        	$h1=false;
        	$h2=false;
        	foreach($allitems as $item){
        		if ($item['caqc_flags']>0) $h1=true;
        		if( $item['cas_type_id']==26 ||
        			$item['cas_type_id']==82 ||
        			$item['cas_type_id']==15 ||
        			$item['cas_type_id']==23 ||
        			$item['cas_type_id']==24 || 
        			$item['cas_type_id']==87 || 
        			$item['cas_type_id']==90) $h2=true;
        		
        	}
        	
        	
        	if($h1) AddCAQC1($pdf,'Scholarly Participation');
        	
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isBooksAuthored()) {
	        		$output=formatItem($item);
	        		if($ignore==true) {AddCAQC2($pdf,'Books Authored'); $ignore=false;}
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isBooksEdited()) {
	        		$output=formatItem($item);
	        		if($ignore==true) {AddCAQC2($pdf,'Books Edited'); $ignore=false;}
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isRefJournals()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Articles in Refereed Journals / Book Chapters'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isOtherPeer()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Other Peer-reviewed Scholarly Activity'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isNonPeer()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Non Peer-reviewed Scholarly Activity'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isConfPres()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Conference Presentations'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isConfAttend()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Conference Attendance'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isStudent()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Student Publications'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;

	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isSubmitted()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Peer-reviewed Publications, Submitted'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isGrants()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Grants'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	$flags=new CaqcFlags();
	        	$flags->GetStats($item['cv_item_id']);
	        	if ($flags->isService()) {
	        		if($ignore==true) {AddCAQC2($pdf,'Scholarly Service'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        //**************************
	        
	        if ($h2) AddCAQC1($pdf,'Professional Memberships, Qualifications and Experience');
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	if($item['cas_type_id']==26 && $item['mycv2']==true) {
	        		if($ignore==true) {AddCAQC2($pdf,'Professional Memberships'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	if(($item['cas_type_id']==2 || $item['cas_type_id']==82)  && $item['mycv2']==true) {
	        		if($ignore==true) {AddCAQC2($pdf,'Professional Qualifications'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
	        
	        $ignore=true;
	        foreach($allitems as $item){
	        	if(
	        		(	$item['cas_type_id']==15 OR 
	        			$item['cas_type_id']==23 OR 
	        			$item['cas_type_id']==24 OR 
	        			$item['cas_type_id']==87 OR 
	        			$item['cas_type_id']==90 OR 
	        			$item['cas_type_id']==4  OR
	        			$item['cas_type_id']==63 OR 
	        			$item['cas_type_id']==64 OR 
	        			$item['cas_type_id']==10 OR
	        			$item['cas_type_id']==66 OR
	        		
	        			(	
	        				(
	        					$item['cas_type_id']>=47 AND $item['cas_type_id']<=62
	        				) 
	        				OR $item['cas_type_id']==80 OR
	        				$item['cas_type_id']==94
	        			) 
	        			&& $item['n23']==true
	        		)  
	        		&& $item['mycv2']==true
	        	) {
	        		if($ignore==true) {AddCAQC2($pdf,'Professional Experience'); $ignore=false;}
	        		$output=formatItem($item);
	        		AddParagraphPlain($pdf,$output);
	        	}
	        }
        }
        
        
        
        
        //Close and output PDF document
        if ( $localFileName ) {
            // send to a local file
            $pdf->Output( $localFileName, 'F' );
        } else {
            // stream to the browser
            $pdf->Output( $fileName, 'D' ); // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
        } // if
        
    } else {
        // failed to get data
        return $cvData['status_message'];
    } // if
    
}

/**
 * Generate a Help PDF
 *
 * @global array $configInfo currently detected config
 * @global ADODB-object $db ADODB database object
 */
function GenerateHelpPdf () {
    global $configInfo, $db;

    require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');

    // create new PDF document
    $pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
    $pdf->SetDisplayMode( 'default', 'continuous' );


    $fileName = "cv_help.pdf";
    $fileName = CleanFilename( $fileName );
    //$headerTitle = 'CV Help';


    // set document information
    $pdf->SetDisplayMode( 'real', 'OneColumn', 'UseNone' ); // added by TDavis to avoid jumping at bottom of pages
    $pdf->SetCreator( PDF_CREATOR );
    $pdf->SetAuthor( $userFullName );
    $pdf->SetTitle( 'Help Document' );
    $pdf->SetSubject( 'Help for CV Item Types' );
    $pdf->SetKeywords( "cv, annual report, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}" );

    // set default header data
    //$pdf->SetHeaderData( PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle, $headerText );

    // set header and footer fonts
    //$pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );
    //$pdf->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );

    //set margins
    $pdf->SetMargins( PDF_MARGIN_LEFT, 18, PDF_MARGIN_RIGHT );
    $pdf->SetHeaderMargin( 10 );
    $pdf->SetFooterMargin( PDF_MARGIN_FOOTER );

    //set auto page breaks
    $pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );

    //set image scale factor
    $pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

    //set some language-dependent strings
    $pdf->setLanguageArray( $l );

    $indent1 = 20;
    
    
    define("PAGE_WIDTH", 180);
    define("INDENT_1_LEFT", 20);
    define("INDENT_1_RIGHT", 0);

    define("INDENT_2_LEFT", 30);
    define("INDENT_2_RIGHT", 10);

    $sql = "SELECT heading_name,type_name,help_text FROM cas_headings ch JOIN cas_types ct ON (ch.cas_heading_id=ct.cas_heading_id)
ORDER BY ch.`order`,ct.`order`";
    $helpData = $db->GetAll( $sql );
    // ---------------------------------------------------------
    // Create the PDF Document:
    
    $pdf->AddPage(); // adds a new page / page break
    $currentHeading = "";
    foreach ( $helpData as $help ) {
        if ( $currentHeading != $help['heading_name'] ) {
            $currentHeading = $help['heading_name'];
            $pdf->SetFont( MRUPDF_H1_FONT_FACE, 'BU', 10 );
            $pdf->Ln( 3 );
            //$pdf->SetTextColor( 255 );
            //$pdf->SetFillColor( 75 );
            $pdf->setX( 15 );
            $pdf->Cell( 0, 5, $currentHeading, '', 1, 'L', 0 );
            //$pdf->SetTextColor( 0 );
            $pdf->Ln( 1 );
            //$pdf->Bookmark( ucwords( strtolower( $currentHeading ) ), 0, 0 );
            
            
        }
        
        
        $pdf->SetFont( MRUPDF_H2_FONT_FACE,'', 9 );
        $pdf->Cell( 0, 4, $help['type_name'], '', 1, 'L');
    
        if($help['help_text'] != '' ) {
            $helpText=$help['help_text'];
            $pdf->SetFont( MRUPDF_REGULAR_FONT_FACE, 'I', 8 );
            $text = htmlentities( $helpText, ENT_COMPAT, cp1252 );
            $pdf->WriteHTML( nl2br( $helpText ), true, 0, true, true );
            $pdf->Ln( 1 );
        }
        else $pdf->Ln( 3 );
        
    }



    //Close and output PDF document
    if ( $localFileName ) {
        // send to a local file
        $pdf->Output( $localFileName, 'F' );
    } else {
        // stream to the browser
        $pdf->Output( $fileName, 'D' ); // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
    } // if
}













function SetCAQC1 (&$pdf) {
    $pdf->SetFont(MRUPDF_H4_FONT_FACE, 'B', MRUPDF_H4_FONT_SIZE );
}

function AddCAQC1 ( &$pdf, $text ) {
	global $EvenOdd;
    $y=$pdf->getY();
    if($y>250) $pdf->AddPage();
    SetCAQC1( $pdf );
    $pdf->Ln();
    $pdf->SetTextColor( 0 );
    $pdf->SetFillColor( 255 );
    $pdf->setX( 15 );
    $utext=" " . strtoupper($text);
    $pdf->Cell( 0, 7, $utext, '', 1, 'L', 1 );
    //$pdf->SetTextColor( 0 );
    //$pdf->Ln();
    $pdf->Bookmark($text, 0, -1 );
    $EvenOdd=0;
    
}

function AddCAQC2 ( &$pdf, $text ) {
	global $EvenOdd;
    $y=$pdf->getY();
    if($y>250) $pdf->AddPage();
    SetCAQC1( $pdf );
    $pdf->Ln( 2 );
    $pdf->SetTextColor( 0 );
    $pdf->SetFillColor( 255 );
    $pdf->setX( 15 );
    $text=" $text";
    $pdf->Cell( 0, 7, $text, '', 1, 'L', 1 );
    //$pdf->SetTextColor( 0 );
    $pdf->Ln( 0 );
    $pdf->Bookmark($text, 1, -1 );
    $EvenOdd=0;
}





/**
 * Builds up the list of CV items to draw
 *
 * @param array $cvItemsData list of items to add the CV
 * @param TCPDF $pdf
 * @param bool $forCv switches what kind of CV item data was passed in.
 * @param string $type used to render the details of the n_teaching,n_scholarship &n_service details.
 */
function DisplayCvData ( $cvItemsData, &$pdf, $forCv = false, $type="" ) {
    //echo("In Display CV Data");
    require_once($_SERVER["DOCUMENT_ROOT"] . 'admin/includes/cv_functions.php');
    
    global $style;
    if ($style == '' ){
        $style = 'apa';
    }
    if ( $forCv ) {
        foreach ( $cvItemsData AS $key1 => $cvHeader ) {
            // add the section header?
            $pdf->SetX( 16 );
            AddH2( $pdf, $key1 );
            $pdf->SetX( 20 );
            foreach ( $cvHeader AS $key2 => $data ) {
                $cvItemSummary = formatitem( $data, $style, 'report');
                //echo("CV_ITEM_SUMMARY: $cvItemSummary");
                $cvItemSummary = ($cvItemSummary != '') ? $cvItemSummary : 'unavailable';
                AddParagraphPlain( $pdf, $cvItemSummary );
                //$pdf->ln();
            } // foreach
        } // foreach
    } else {
        $currentHeader = GetHeading( $cvItemsData[0]['cas_type_id'] );
        AddH2( $pdf, $currentHeader );
        foreach ( $cvItemsData AS $key1 => $cvHeader ) {
            // add the section header?
            $pdf->SetX( 16 );
            $cvItemSummary = formatitem( $cvHeader, $style,'report' );
            $cvItemSummary = ($cvItemSummary != '') ? $cvItemSummary : 'unavailable';
            $pdf->SetX( 20 );
            if ( $currentHeader != GetHeading( $cvHeader['cas_type_id'] ) ) {
                $currentHeader = GetHeading( $cvHeader['cas_type_id'] );
                AddH2( $pdf, $key1 );
            }
            $pdf->SetX( 25 );
            AddParagraphPlain( $pdf, $cvItemSummary );
            //$pdf->ln();
            if(trim($cvHeader['details_' . $type])!='') 
                AddParagraphSummary( $pdf, $cvHeader['details_' . $type] );
        } // foreach
    }
}



/**
* Adds a formatted header to the PDF. 
* 
* @param mixed $pdf
* @param array $titles  Array of titles as text
*/
function CAQC_Header(&$pdf,$titles,$width=''){
    //print_r($width);
    SetNormal( $pdf );
    $pdf->Ln(3);
    if(count($titles) > 0){       
        foreach($titles as $key=>$title){  
            if($width!='') $w=$width[$key]*180.0; else $w=180/count($titles);  
            //echo("Width of $w  ");
            $pdf->Cell($w,6,$title,1,0,'L',0,'',0,0,'C','C');
        }
        $pdf->Ln(5);
    }
}
/**
* Generate a list under a header
* 
* @param mixed $pdf
* @param array $list The items to list
* @param array $align Optional array of alignment codes (L,R,C)
* @param array $width Optional array of relative width amounts. Should add up to 1
*/
function CAQC_List(&$pdf,$list,$align='',$width=''){
	global $evenodd;
    SetNormal( $pdf );
    //$pdf->SetFillColor(150,150,150);
    if(count($list) > 0){
        $maxH=0;
        foreach($list as $key=>$item){ 
            $item = htmlentities( $item, ENT_COMPAT, cp1252 );
            $item=htmlspecialchars_decode($item,ENT_NOQUOTES);
            if($align!='') $al=$align[$key]; else $al='L';   
            if($width!='') $w=$width[$key]*180; else $w=180/count($list);
            //$pdf->Cell($w,6,$item,0,0,$al,0,'',1,0,'C','C'
            $pdf->ResetLastH();
            
            $pdf->MultiCell($w,  	//width
            				6,		//height
            				$item,	//text
            				0,		//border
            				'L',	//align
            				0,		//fill
            				0,		//ln
            				'',		//x position
            				'',		//y position
            				1,		//reset height
            				0,		//stretch
            				1       //ishtml
            				);
            
            
           // echo ("<pre> $evenodd <br></pre>");
           	
            //$lines=$pdf->GetNumLines($item,$w,0,1,'',0);
            //if($lines>$maxH)$maxH=$lines;
            if($pdf->GetLastH()>$maxH) $maxH=$pdf->GetLastH();
            
        }
        
        $y = $pdf->GetY();
    	
    	if($y>250) $pdf->AddPage();
        return $maxH;
        
    }
}

// function DisplayCvData
?>
