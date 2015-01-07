<?php

function genPDF($id){
    global $configInfo;
    global $db;

    require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');
    
    $sql="SELECT * FROM forms_tracking
        WHERE form_tracking_id=$id";
        //echo ($sql);
    $form=$db->getRow($sql);
    if(is_array($form)){
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
        
        //Get the info
        $sql="SELECT * FROM users WHERE user_id=$form[user_id]";
        $user=$db->getRow($sql);
        if(is_array($user)) $fullname="$user[first_name] $user[last_name]";
        $moddate=date('M j, Y',$form['modified']);
        $filename='Filename';
        $pdf = new MYPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
        $pdf->SetDisplayMode( 'default', 'continuous' );
        // set up some personalized data parameters
        $headerTitle = 'Tracking Form';
        $statusText = "Submitted: ".$moddate;
        //$statusText = ($submittedDate) ? "Submitted on {$submittedDate}" : $statusText;
        $headerText = $fullname . ' | ' . $statusText; // . ' | Printed on: ' . date('M d, Y');

        // set document information
        $pdf->SetDisplayMode( 'real', 'OneColumn', 'UseNone' ); // added by TDavis to avoid jumping at bottom of pages
        $pdf->SetCreator( PDF_CREATOR );
        //$pdf->SetAuthor( $userFullName );
        $pdf->SetTitle( 'Tracking Form for ' . 'My Name' );
        //$pdf->SetSubject( 'A summary of profile and CV information that has been submitted by ' . $userFullName );
        //$pdf->SetKeywords( "cv, annual report, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}" );

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
               
        $pdf->AddPage();
        
        
        
        if ( $localFileName ) {
            // send to a local file
            
            $pdf->Output( $localFileName, 'F' );
        } else {
            
            // stream to the browser
            $pdf->Output( $fileName, 'D' ); // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
        } // if
        
        
    }// isarray (form)
    return (1);
}
?>
