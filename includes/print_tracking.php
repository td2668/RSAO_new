<?php

use tracking\TrackingForm;

require_once('classes/tracking/TrackingForm.php');
require_once('classes/tracking/Funding.php');
require_once('classes/tracking/Approval.php');
require_once('classes/tracking/COI.php');
  
function printPDF($form_tracking_id,$user,$db){
    /**
    * Formats and prints a complete tracking form
    */
    require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');
    global $niceday;
    //Do any required checks for security

    $trackingForm = new TrackingForm();
    $form = $trackingForm->retrieveForm($form_tracking_id);


/*    //id was already checked before calling
    $sql="SELECT    forms_tracking.*,
                    ors_project.name,
                    ors_agency.name as agency,
                    ors_program.name as program_name
                    FROM forms_tracking 
                    LEFT JOIN ors_project ON (forms_tracking.project_id=ors_project.id) 
                    LEFT JOIN ors_agency ON (forms_tracking.agency_id=ors_agency.id)
                    LEFT JOIN ors_program ON (forms_tracking.program_id=ors_program.id)
                    WHERE form_tracking_id=$form_tracking_id";
    $form=$db->getRow($sql);*/
    $sql="SELECT * FROM users where user_id=$user[user_id]";
    $user=$db->getRow($sql);
    $username=$user['first_name'].' '.$user['last_name'];
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
     
     // set up the formatting styles
    $h1FontSize = 16;
    $h2FontSize = 14;
    $h3FontSize = 12;
    $normalFontSize = 12;

    // set document information
    $pdf->SetDisplayMode('real', 'OneColumn', 'UseNone'); // added by TDavis to avoid jumping at bottom of pages
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($username);
    $pdf->SetTitle('Tracking Form: ' . $trackingForm->projectTitle);
    $pdf->SetSubject('Tracking Form submitted by ' . $username);
    //$pdf->SetKeywords("cv, annual report, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}");

    // set default header data
    $headerTitle='MRU Research Tracking Form';
    $headerText=substr($trackingForm->projectTitle,0,60);
    if(strlen($trackingForm->projectTitle)> 60) $headerText.='...';
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle , $headerText);


    // set header and footer fonts
    //$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    // dejavusans-extralight is a good typewriter font. May be a poor printer
    // stsongstdlight is nice mini-serif official look. Too light for a header
    // msungstdlight is slightly darker version of the above
    // kozgopromedium is very blocky but better than helvetica
    // almohanad is OK but very tiny - only use small
    
    
    //$pdf->setHeaderFont(Array('kozgopromedium', '', 12));
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

    $indent1=40;

    // ---------------------------------------------------------
    // Create the PDF Document:

    $pdf->AddPage(); // adds a new page / page break
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP + 10, PDF_MARGIN_RIGHT); // for the next page
    
    $tagvs = array('h1' => array(0 => array('h' => '', 'n' => 2), 1 => array('h' => 1.3, 'n' => 1)));
    $pdf->setHtmlVSpace($tagvs);

    //$tableWidth = array(60,80,80);
    SetNormal($pdf);
    $pdf->SetFillColor(225);
    //$pdf->SetCellPadding(6);
    $pdf->SetX($indent1);
    AddH1($pdf,'Information');

    oneline('Tracking ID :', $trackingForm->trackingFormId, $pdf);
    oneline('Project Title:',$trackingForm->projectTitle,$pdf);
    $pdf->Ln(3);
    onemline('Synopsis :', $trackingForm->synopsis, $pdf);
    oneline('Deadline :', $trackingForm->deadline, $pdf);
    oneline('Grant Type :', $trackingForm->grantType == 0 ? 'Internal' : 'External', $pdf);
    $pdf->Ln(3);

    AddH1($pdf,'Researchers');

    if($trackingForm->projectTitle == '') $trackingForm-$projectTitle = '[NONE]';

    //Determine proper PI
    $PIDetails = $trackingForm->getPIDisplayDetails();
    $name = $PIDetails['firstName'] . " " . $PIDetails['lastName'] . ' (' . $PIDetails['departmentName'] . ')';
    oneline('PI:', $name, $pdf);
    $pdf->Ln(3);


    $plural = count($trackingForm->coResearchers > 1) ? 's' : '';
    foreach($trackingForm->coResearchers as $key=>$coResearcher) {
        $name = $coResearcher->getDisplayName();
        if($key==0) {
            oneline("Co-Researcher$plural:", $name, $pdf);
        } else {
            oneline('', $name, $pdf);
        }
    }
    $pdf->Ln(3);
       
    if($trackingForm->coResearchersExternal != ''){
        oneline('Others:',($trackingForm->coResearchersExternal),$pdf);
        $pdf->Ln(3);
    }
    
    if($trackingForm->coResearcherStudents == true){
        onecheck('Co-Students', true, $pdf, 20, 'costudents');
        $pdf->Ln(2);
    }
    if($trackingForm->hasFunding() == true) {
        $funding = $trackingForm->funding;

        SetNormal($pdf);
        $pdf->SetFillColor(225);
        $pdf->SetX($indent1);
        AddH1($pdf,'Funding');

        if($funding->hasCustomAgency() == true) {
            oneline('Agency: ', $funding->getAgency(), $pdf); $pdf->Ln(1);
        } else {
            oneline('Agency: ', $funding->getAgency(), $pdf); $pdf->Ln(1);
            oneline('Program: ', $funding->getProgram(), $pdf); $pdf->Ln(1);
        }

        oneline('Requested: ', '$' . $funding->requested, $pdf); $pdf->Ln(1);
        oneline('Awarded: ', '$' . $funding->received, $pdf); $pdf->Ln(1);
    } else {
        SetNormalBold($pdf);
        $pdf->cell(50,0,'No Funding Specified', 0, 1, 'L', 0);
    }


    SetNormal($pdf);
    $pdf->SetFillColor(225);
    $pdf->SetX($indent1);
    AddH1($pdf,'Commitments');


    if($trackingForm->commitments->requiresApproval() == true) {
        $commitments = $trackingForm->commitments;
        if($commitments->equipment == true) {
            onemline('Equipment:' ,$commitments->equipmentSummary, $pdf);
            $pdf->Ln(1);
        }
        if($commitments->space == true) {
            onemline('Space:', $commitments->spaceSummary, $pdf);
            $pdf->Ln(1);
        }
        if($commitments->other == true) {
            onemline('Other :', $commitments->otherSummary, $pdf);
            $pdf->Ln(1);
        }

        if ($commitments->employed == true) {
            $pdf->Ln(2);
            onecheck('Persons Employed', 1, $pdf, 35, 'employ_flag');
            if ($commitments->employedStudents == true) {
                onecheck('Students Employed', 1, $pdf, 35, 'emp_students');
            }
            if ($commitments->employedResearchAssistants == true) {
                onecheck('RAs Employed', 1, $pdf, 30, 'empras');
            }
            if ($commitments->employedConsultants == true) {
                onecheck('Consultants Employed', 1, $pdf, 40, 'consult');
            }
        }


    } else {
        SetNormalBold($pdf);
        $pdf->cell(50,0,'No Commitments Specified', 0, 1, 'L', 0);
    }

    $pdf->Ln(2);

    /////////////////////////////////////////
    
    //COI Section
    SetNormal($pdf);
    $pdf->SetFillColor(225);
     $pdf->SetX($indent1);
    AddH1($pdf, 'COI');

    $cois = $trackingForm->coi;

    if (count($cois) > 0) {
        foreach ($cois as $coi) {
            $name = $coi->name != '' ? $coi->name : $coi->first_name . ' ' . $coi->last_name;
            oneline('For:', $name, $pdf);
            $pdf->Ln(2);
            $dec = '';
            if ($coi->coi_none) {
                $dec = "No Conflicts";
                onemline('Declared:', $dec, $pdf);
            } else {
                if ($coi->coi01) {
                    $dec .= "Interest in a research, business, contract or transaction\n";
                }
                if ($coi->coi02) {
                    $dec .= "Influencing purchase of equipment, materials or services\n";
                }
                if ($coi->coi03) {
                    $dec .= "Acceptance of gifts, benefits or financial favours\n";
                }
                if ($coi->coi04) {
                    $dec .= "Use of information\n";
                }
                if ($coi->coi05) {
                    $dec .= "Use of students, university personnel, resources or assets\n";
                }
                if ($coi->coi06) {
                    $dec .= "Involvement in personnel decisions\n";
                }
                if ($coi->coi07) {
                    $dec .= "Evaluation of academic work\n";
                }
                if ($coi->coi08) {
                    $dec .= "Academic program decisions\n";
                }
                if ($coi->coi09) {
                    $dec .= "Favouring outside interests for personal gain\n";
                }
                if ($coi->coi10) {
                    $dec .= "Relationship\n";
                }
                if ($coi->coi11) {
                    $dec .= "Undertaking of outside activity\n";
                }
                if ($coi->coi_other) {
                    $dec .= "Other\n";
                }
                onemline('Declared:', $dec, $pdf);
            }
            $pdf->Ln(2);
        }
    }  else {
    	SetNormalBold($pdf);
        $pdf->cell(50,0,'No COI declarations filed',0,1,'L',0);
    }
    
    
    //Compliance Section

    SetNormal($pdf);
    $pdf->SetFillColor(225);
    $pdf->SetX($indent1);
    AddH1($pdf, 'Compliance');

    $compliance = $trackingForm->compliance;

    onecheck('Human Subjects (behavioural)', $compliance->requiresBehavioural(),$pdf,45,'hrebb');
    onecheck('Human Subjects (health)',$compliance->requiresHealth(),$pdf,45,'hrebh');
    onecheck('Biohazards',$compliance->requiresBiohazard(),$pdf,25,'biohaz');
    onecheck('Animal Subjects',$compliance->requiresAnimal(),$pdf,30,'animal');

    $pdf->Ln(6);

    AddH2($pdf, 'Location(s) :');

    if($compliance->locationSpecified() == true) {
        if($compliance->locationText) {
            oneline('Where:', $compliance->locationText, $pdf);
            $pdf->Ln(4);
        }
        if($compliance->locationMRU) {
            onecheck('MRU', 1, $pdf, 35, 'mru');
        }
        if($compliance->locationCanada) {
            onecheck('Canada', 1, $pdf, 35, 'canada');
        }
        if($compliance->locationInternational) {
            onecheck('International', 1, $pdf, 35, 'international');
        }
    } else {
        SetNormalBold($pdf);
        $pdf->cell(50, 0, 'No location specified', 0, 1, 'L', 0);
        $pdf->Ln(2);
    }

    $pdf->Ln(5);
    
    
    // Tracking and Signatures

    SetNormal($pdf);
    $pdf->SetFillColor(225);
    $pdf->SetX($indent1);
    AddH1($pdf,'Tracking and Approvals');
    if($trackingForm->status == PRESUBMITTED){
        oneline('Status:','Presubmitted / Draft',$pdf);
    } else {
        foreach($trackingForm->approvals as $approval) {
            AddH2($pdf, $approval->getFriendlyName());
            if($approval->status == APPROVED) {
                onecheck('Approved', 1, $pdf, 35, 'status');
                onecheck('Pending Approval', 0, $pdf, 35, 'status');
                $pdf->Ln(6);
                oneline('', 'Approved on ' . date("M-d-Y", strtotime($approval->dateApproved)), $pdf);
                $pdf->Ln(2);
                AddH3($pdf, "Comments");
                $pdf->Ln(2);
                AddParagraphPlain($pdf, $approval->comments);
            } else {
                onecheck('Approved', 0, $pdf, 35, 'status');
                onecheck('Pending Approval', 1, $pdf, 35, 'status');
                $pdf->Ln(6);
                oneline('', ' Submitted on ' . date("M-d-Y", strtotime($approval->dateSubmitted)), $pdf);
            }
            $pdf->Ln(6);
        }
    }
    $pdf->Ln(2);
    
/*    SetNormal($pdf);
    $pdf->SetFillColor(225);
     $pdf->SetX($indent1);
    AddH1($pdf,'Tracking and Approvals');
    if(!is_null($form['submit_date'])) oneline('Submitted:',date($niceday,strtotime($form['submit_date'])),$pdf);
    else oneline('Submitted:','n/a',$pdf);
    $pdf->Ln(2);*/


    
 /*   if($form['ors_sig']){
        $sql="SELECT first_name,last_name FROM users WHERE user_id=$form[ors_id]";
        $ors=$db->getRow($sql);
        if($ors) $name=$ors['first_name'] . ' ' . $ors['last_name'];
        else $name = "(Unknown)";
        oneline('ORS Signature:',$name . ' on '. date($niceday,strtotime($form['dean_date'])),$pdf);
        if($form['ors_comments']!='') onemline('ORS Comments:',$form['ors_comments'],$pdf);
    }
    
    //Agree to administer
    if($form['funding'] && !$form['funding_confirmed']){
    	//$pdf->ln(2);
       onecheck('Applicant agrees to funding policy',$form['iagree'],$pdf,50,'iagree');   
       $pdf->Ln(7);
    }
    //Documents
    if($form['documents']){
        onemline('Documents:',$form['documents'],$pdf);
        $pdf->Ln(9);
    }
    
    
    if($form['dean_sig']){
        $sql="SELECT first_name,last_name FROM users WHERE user_id=$form[dean_id]";
        $dean=$db->getRow($sql);
        if($dean) $name=$dean['first_name'] . ' ' . $dean['last_name'];
        else $name = "(Unknown)";
        oneline('Dean Signature:',$name . ' on '. date($niceday,strtotime($form['dean_date'])),$pdf);
        if($form['dean_comments']!='') onemline('Dean Comments:',$form['dean_comments'],$pdf);
    }
    else {
    	//Check if Dean needs to sign, and provide line for sig
    	//If we are doing electronic delivery then note where button is
    	if($form['status']==1 && ($form['equipment_flag'] || $form['space_flag'] || $form['commitments_flag'])){
    		
    			if($pdf->GetY() > 190)  $pdf->AddPage();
    			$pdf->ln(2);
    			onemline('Dean Approval:',"I am aware of and agree to provide the commitments listed in this proposal, subject to provisions listed below
 
    			
________________________________________________________________ (Dean or Designate)",$pdf);
    			
    			$pdf->ln(10);
    			for($x=1;$x<=6;$x++){
    				$y=$pdf->getY();
    				$pdf->ln(5);
    				$pdf->Line(20,$y,190,$y,array(width=>0.3,color=>array(0)));
    				$pdf->Ln(5);
    			}
    		}
    	}//else*/
    
     // stream to the browser
     $fileName = str_replace(" ","-", $username) . '-TID' . $form_tracking_id . '.pdf';
     $pdf->Output($fileName, 'I'); // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
}


/**
 * budgetline function. Generates the budget lines with 4 numeric columns for the pdf
 * 
 * @access public
 * @param mixed $header1
 * @param mixed $cash1
 * @param mixed $inkind1
 * @param mixed $header2
 * @param mixed $cash2
 * @param mixed $inkind2
 * @param mixed $pdf
 * @param int $fill (default: 1) Colour the box contents
 * @return void
 */
function budgetline($header1,$cash1,$inkind1,$header2,$cash2,$inkind2,$pdf,$fill=0){
    SetNormal($pdf);
    $pdf->SetFillColor(220,220,255);
    $pdf->cell(40,0,$header1,0,0,'R',0);
    if($cash1!='') $pdf->cell(20,0,'$ '. number_format($cash1),$fill,0,'L',$fill);
    else $pdf->cell(20,0,'',0,0,'L',0);
    $pdf->cell(2,0,'',0,0,'L',0);
    if($cash1!='') $pdf->cell(20,0,'$ '. number_format($inkind1),$fill,0,'L',$fill);
    else $pdf->cell(20,0,'',0,0,'L',0);
    $pdf->cell(40,0,$header2,0,0,'R',0);
    $pdf->cell(20,0,'$ '. number_format($cash2),$fill,0,'L',$fill);
    $pdf->cell(2,0,'',0,0,'L',0);
    $pdf->cell(20,0,'$ '. number_format($inkind2),$fill,1,'L',$fill); 
    $pdf->Ln(1);
    
}

/**
 * oneline function.
 * 
 * @access public
 * @param mixed $header
 * @param mixed $text
 * @param mixed $pdf
 * @param int $fill (default: 0)
 * @return void
 */
function oneline($header,$text,$pdf,$fill=0){
    SetNormal($pdf);
    $pdf->SetFillColor(220,220,255);
    $w=getwidth($text,$pdf);
    $pdf->cell(20,0,$header,array('B'=>array('color'=>array(255,255,255))),0,'R',0);
    $pdf->cell(4,0,'',0,0,'L',0);
    SetNormalBold($pdf);
    $pdf->cell($w,0,$text,array('B'=>array('width'=>0.25,'cap'=>'butt','join'=>'miter','dash'=>0,'color'=>array(150,150,150))),1,'L',$fill);
    //$pdf->cell($w,0,$text,0,1,'L',$fill);

    //$pdf->Ln(1);
}


/**
 * twoline function.
 * 
 * @access public
 * @param mixed $header
 * @param mixed $text
 * @param mixed $header2
 * @param mixed $text2
 * @param mixed $pdf
 * @param int $fill (default: 0)
 * @return void
 */
function twoline($header,$text,$header2,$text2,$pdf,$fill=0){
    SetNormal($pdf);
    $pdf->SetFillColor(220,220,255);
    $w=getwidth($text,$pdf);
    $pdf->cell(50,0,$header,0,0,'R',0);
    $pdf->cell($w,0,$text,$fill,0,'L',$fill);
    $pdf->cell(10,0,'',0,0,'L',0);
    $w=getwidth($text2,$pdf)+2;
    $pdf->cell(20,0,$header2,0,0,'R',0);
    $pdf->cell($w,0,$text2,$fill,1,'L',$fill);
    //$pdf->Ln(1);
}


/**
 * onemline function.
 * 
 * @access public
 * @param mixed $header
 * @param mixed $text
 * @param mixed $pdf
 * @param int $fill (default: 0)
 * @return void
 */
function onemline($header,$text,$pdf,$fill=0){
    SetNormal($pdf);
    $pdf->SetFillColor(220,220,255);
    $w=getwidth($text,$pdf)+5;
    if ($w > ($pdf->getPageWidth()-60)) $w=$pdf->getPageWidth()-60;
    $pdf->cell(20,0,$header,0,0,'R',0);
    //Need to set XY here to move Y because SetY resets X for some reason
    $pdf->setXY($pdf->GetX(),$pdf->GetY()-1);
    $pdf->cell(4,0,'',0,0,'L',0);
    SetNormalBold($pdf);
    $pdf->multicell($w,0,$text,array('TLBR'=>array('width'=>0.25,'cap'=>'butt','join'=>'miter','dash'=>0,'color'=>array(150,150,150))),'L',$fill,1,'','',1,0,0,1);
    //$pdf->Ln(1);
}

/**
 * onemHTMLline function.
 *
 * @access public
 * @param mixed $header
 * @param mixed $text
 * @param mixed $pdf
 * @param int $fill (default: 0)
 * @return void
 */
function onemHTMLline($header,$text,$pdf,$fill=0){
    SetNormal($pdf);
    $pdf->SetFillColor(220,220,255);
    $w=getwidth($text,$pdf)+5;
    if ($w > ($pdf->getPageWidth()-60)) $w=$pdf->getPageWidth()-60;
    $pdf->cell(20,0,$header,0,0,'R',0);
    //Need to set XY here to move Y because SetY resets X for some reason
    $pdf->setXY($pdf->GetX(),$pdf->GetY()-1);
    $pdf->cell(4,0,'',0,0,'L',0);
    SetNormalBold($pdf);
    $pdf->WriteHTML(nl2br($text),true, 0, true, true);

    //$pdf->multicell($w,0,$text,array('TLBR'=>array('width'=>0.25,'cap'=>'butt','join'=>'miter','dash'=>0,'color'=>array(150,150,150))),'L',$fill,1,'','',1,0,0,1);
    //$pdf->Ln(1);
}


/**
 * onecheckrev function.
 * 
 * @access public
 * @param string $header (default: '')
 * @param bool $bool (default: true)
 * @param mixed $pdf
 * @param int $inset (default: 20)
 * @param string $name (default: 'test')
 * @return void
 */
function onecheck($header='',$bool=true,$pdf,$inset=20,$name='test'){
    SetNormal($pdf);
    $pdf->SetFillColor(220,220,255);
    $pdf->cell($inset,0,$header,0,0,'R',0);
    $pdf->cell(4,0,'',0,0,'L',0);
    if($bool) $shade=array(125,125,125); else $shade=array();
    $pdf->Rect($pdf->GetX(),$pdf->GetY()+0.5,3,3,'b',array('all'=>array('width'=>0.3,'cap'=>'square','join'=>'round','dash'=>0,'color'=>array(50,50,50))),$shade);
    if($bool){
    	$x=$pdf->GetX();$y=$pdf->GetY();
    	$pdf->PolyLine(	array(	$x+0.75,$y+1,
    							$x+1.5,$y+2.5,
    							$x+3,$y-0.2),
    					'S',
    					array('all'=>array('width'=>0.5,'cap'=>'round','join'=>'miter','dash'=>0,'color'=>array(0,0,0)))
    					);
    }
    //$pdf->CheckBox($name,4,$bool,array(),array(),'Yes','',$pdf->getY()+12);
   
}




function getwidth($text,$pdf){
    $w=0;
    for($x=0; $x<strlen($text); $x++){
        $w+=$pdf->getCharWidth(ord(substr($text,$x,1)));
        $w+=0.11;
    }
    return $w;
}


function doHR(&$pdf) {
    //$x=$pdf->GetX();
    $y=$pdf->GetY();
    $pdf->Line(35,$y,170,$y,array(width=>1,color=>array(75)));
    $pdf->Ln(2);
}
function thinHR(&$pdf) {
    //$x=$pdf->GetX();
    $y=$pdf->GetY();
    $pdf->Line(35,$y,170,$y,array(width=>0.5,color=>array(75)));
    $pdf->Ln(3);
}

function SetH1($pdf) {
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
    $pdf->SetFont('times', '', 10);
}
function SetNormalBold(&$pdf) {
    $pdf->SetFont('times', 'B', 10);
}
function AddH1($pdf, $text) {
    
    SetH1($pdf);
    $pdf->Ln(4);
    $pdf->SetTextColor(0);
    $pdf->SetFillColor(200,200,255);
    $pdf->setX(15);
    $pdf->Cell(0, 6, $text, '', 1, 'L',1);
    $pdf->SetTextColor(0);
    $pdf->Ln(4);
    //$pdf->Bookmark(ucwords(strtolower($text)), 0, 0);

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

    //Claero DLG took out the blank suppression
    //$pdf->MultiCell(0, 5, $text, 0, 1, 'L');
    //if (substr($text, -1) != ')'){
    
    //TD added extended set conversion
    $text=htmlentities($text,ENT_COMPAT,cp1252);
    $pdf->WriteHTML(nl2br($text),true, 0, true, true);
    $pdf->Ln(2);
    //}
}

function AddParagraphPlain(&$pdf, $text) {
    SetNormal($pdf);

    //Claero DLG took out the blank suppression
    //$pdf->MultiCell(0, 5, $text, 0, 1, 'L');
    //if (substr($text, -1) != ')'){

    $pdf->WriteHTML(nl2br($text),true, 0, true, true);
    $pdf->Ln(2);
    //}
}
function AddLine(&$pdf, $text) {
    SetNormal($pdf);
    $pdf->Cell(0, 5, $text, 0, 1, 'L');
}
?>
