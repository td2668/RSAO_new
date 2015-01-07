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
        $pdf->SetDisplayMode( 'real', 'OneColumn', 'UseNone' ); // added by TDavis to avoid jumping at bottom of pages
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
function GenerateCV ( $userId, $flag='', $style='apa' ) {

    global $configInfo;

    require_once('includes/tcpdf/config/lang/eng.php');
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
    $localFileName = (isset( $options['local_file_name'] )) ? $options['local_file_name'] : false;
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






/**
 * Builds up the list of CV items to draw
 *
 * @param array $cvItemsData list of items to add the CV
 * @param TCPDF $pdf
 * @param bool $forCv switches what kind of CV item data was passed in.
 * @param string $type used to render the details of the n_teaching,n_scholarship &n_service details.
 */
function DisplayCvData ( $cvItemsData, &$pdf, $forCv = false, $type="" ) {
    require_once('includes/cv_functions.php');
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



// function DisplayCvData
?>
