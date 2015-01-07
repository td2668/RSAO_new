<?php
  
function printPDF($form_irgf_id,$user,$db){
    /**
    * Formats and prints a complete irgf form
    */
    require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');
    require_once('includes/pdf_functions.php');
    global $niceday;
    //Do any required checks for security
    
    //id was already checked before calling
    $sql="SELECT    * from forms_irgf LEFT JOIN forms_irgf_budgets using (form_irgf_id) where form_irgf_id=$form_irgf_id";
    $form=$db->getRow($sql);
    if(!$form) {echo($db->ErrorMsg); die();}
    $sql="SELECT * FROM forms_tracking WHERE form_tracking_id=$form[form_tracking_id]";
    $tf=$db->getRow($sql);
    if(!$form) {echo($db->ErrorMsg); die();}
    $sql="SELECT * FROM users where user_id=$form[user_id]";
    $user=$db->getRow($sql);
    $username=$user['first_name'].' '.$user['last_name'];
    $sql="SELECT * FROM users where user_id=$form[reviewer_id]";
    $reviewer=$db->getRow($sql);
    $reviewername=$reviewer['first_name'].' '.$reviewer['last_name'];
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
     
     // set up the formatting styles
    $h1FontSize = 16;
    $h2FontSize = 14;
    $h3FontSize = 12;
    $normalFontSize = 12;

    // set document information
    $pdf->SetDisplayMode('real', 'OneColumn', 'UseNone'); // added by TDavis to avoid jumping at bottom of pages
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('My Name');
    $pdf->SetTitle('Internal Research Grant Application: ' . $form['tracking_name']);
    $pdf->SetSubject('Internal Research Grant Fund Application submitted by ' . $username);
    //$pdf->SetKeywords("cv, annual report, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}");
    
    // set default header data
    $headerTitle='MRU IRGF Form';
    $headerText=substr($form['irgf_name'],0,60);
    if(strlen($form['irgf_name'])> 60) $headerText.='...';
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
    

    oneline('Title:',$form['irgf_name'],$pdf);
     
    
    //Determine proper PI
    if($tf['pi']) $pi_id=$tf['user_id'];
    else $pi_id=$tf['pi_id'];
    if($pi_id != 0){
        $sql="	SELECT users.last_name,users.first_name,departments.name,users_ext.emp_status,users.emp_type 
        		FROM users 
        		LEFT JOIN departments on (users.department_id=departments.department_id)  
        		LEFT JOIN users_ext using (user_id)
        		
        		WHERE user_id=$pi_id";
        $pi=$db->getRow($sql);
        if($pi) {
            $name=$pi['last_name'].', '.$pi['first_name'];
            if($pi['name']!='') $name.= " ($pi[name])";
            oneline('PI:',$name,$pdf);
            if($pi['emp_status']=='T') $status='Tenured';
            elseif($pi['emp_status']=='TN') $status='Tenure-Track';
            elseif($pi['emp_status']=='TC') $status='Term-Certain';
            elseif($pi['emp_type']=='PTFAC') $status='Part Time';
            else $status='Other';
            oneline('Status:',$status,$pdf);
        }
    }
    else {
        SetNormal($pdf);
        $fill=0;
        $pdf->SetFillColor(220,220,255);
        $pdf->cell(20,0,'PI:',0,0,'R',0);
        if($tf['lastname']=='' && $tf['firstname']=='') $name='(Not Named)'; else $name=$tf['lastname'].', '.$tf['firstname'];
        $w=getwidth($name,$pdf)+2;
        SetNormalBold($pdf);
        $pdf->cell($w,0,$name,$fill,2,'L',$fill);  
        
    }
    
     
    
    $sql="SELECT * FROM forms_tracking_coresearchers LEFT JOIN users ON (forms_tracking_coresearchers.user_id=users.user_id) LEFT JOIN departments on (users.department_id=departments.department_id) WHERE forms_tracking_coresearchers.form_tracking_id=$form[form_tracking_id]";
    $cos=$db->getAll($sql);
    if(($cos)){
        if(count($cos)>1) $plural='s'; else $plural='';
        SetNormal($pdf);
        //$pdf->SetFillColor(220,220,255);
        //$pdf->cell(20,0,"Co-Researcher$plural:",0,0,'R',0);
        //SetNormalBold($pdf);
        //$fill=1;
        $count=1;
        foreach($cos as $co){
        /*
        	$pdf->ln(2);
        	$h=$pdf->getFontSizePt()/2+1;
            $name=$co['last_name'].', '.$co['first_name'];
            if($co['name']!='') $name.= " ($co[name])";
            $w=getwidth($name,$pdf)+2;
            $pdf->cell($w,$h,$name,$fill,2,'L',$fill);
            $pdf->ln(1);
           */
           $name=$co['last_name'].', '.$co['first_name'];
            if($co['name']!='') $name.= " ($co[name])";
           if($count==1){
           	$count++; 
           	oneline("Co-Researcher$plural:",$name,$pdf);
           	
           }
           else oneline('',$name,$pdf);
        }
        $pdf->Ln(1);
    }
    
       
    if($tf['coresearchers'] != ''){
    	//$pdf->ln(2);
        onemline('Others:',($tf['coresearchers']),$pdf);
    }
 
 	if($form['which_fund']=='new_applicant') $type='New Applicant';
 	else $type='Regular Faculty';
 	$pdf->ln(2);
 	oneline('Fund Type',$type,$pdf);
 	
 	$yr1=$form['c_stipends']+$form['c_persons']+$form['c_assist']+$form['c_ustudents']+$form['c_gstudents']+$form['c_ras']+$form['c_others']+$form['c_benefits']+$form['c_equipment']+$form['c_supplies']+$form['c_travel']+$form['c_comp']+$form['c_oh']+$form['c_space'];
 	$yr2=$form['i_stipends']+$form['i_persons']+$form['i_assist']+$form['i_ustudents']+$form['i_gstudents']+$form['i_ras']+$form['i_others']+$form['i_benefits']+$form['i_equipment']+$form['i_supplies']+$form['i_travel']+$form['i_comp']+$form['i_oh']+$form['i_space'];
 	$yr1='$ '. number_format($yr1);
 	$yr2='$ '. number_format($yr2);
 	
 	oneline('Year 1 Request:',$yr1,$pdf);
 	oneline('Year 2 Request:',$yr2,$pdf);
 	
 	$start=date($niceday,strtotime($form['start_date']));
 	$end=date($niceday,strtotime($form['end_date']));
 	
 	twoline('Start:',$start,'End:',$end,$pdf);
 	
 	$pdf->ln(2);
 	onemline('Summary:',$form['summary'],$pdf);
 	
 	$pdf->ln(2);
 	onemline('Dissemination:',$form['dissemination'],$pdf);
 
 	$pdf->ln(2);
 	onemline('Other Funding:',$form['funding'],$pdf);
 
 	$pdf->ln(3);
 	SetNormal($pdf);
    $pdf->cell(40,0,'',0,0,'R',0);
    $pdf->cell(20,0,'Year 1',0,0,'L',0);
    $pdf->cell(2,0,'',0,0,'L',0);
    $pdf->cell(20,0,'Year 2',0,0,'L',0);
    $pdf->cell(40,0,'',0,0,'R',0);
    $pdf->cell(20,0,'Year 1',0,0,'L',0);
    $pdf->cell(2,0,'',0,0,'L',0);
    $pdf->cell(20,0,'Year 2',0,1,'L',0);
    budgetline('Stipends to Investigators:',$form['c_stipends'],$form['i_stipends'],'Benefits:',$form['c_benefits'],$form['i_benefits'],$pdf);
    budgetline('Secretarial/Tech Personnel:',$form['c_persons'],$form['i_persons'],'Equipment:',$form['c_equipment'],$form['i_equipment'],$pdf);
    budgetline('Assistantships   ','','','Supplies:',$form['c_supplies'],$form['i_supplies'],$pdf);
    budgetline('   Undergrad Students:',$form['c_ustudents'],$form['i_ustudents'],'Travel:',$form['c_travel'],$form['i_travel'],$pdf);
    budgetline('   Grad Students/Post-Docs:',$form['c_gstudents'],$form['i_gstudents'],'IT Services:',$form['c_comp'],$form['i_comp'],$pdf);
    budgetline('   Other RAs:',$form['c_ras'],$form['i_ras'],'Overhead:',$form['c_oh'],$form['i_oh'],$pdf);
    budgetline('   Other:',$form['c_others'],$form['i_others'],'Space/Facilities:',$form['c_space'],$form['i_space'],$pdf);
    if(trim($budget['others_text'])!=''){
        $pdf->cell(40,0,'(Stipulate):',0,0,'R',0);
        $pdf->cell(124,0,$budget['others_text'],1,1,'L',0);
    }
    $pdf->ln(5);
    $i_total=$c_total=0;
    foreach($form as $key=>$oneitem){
        if(substr($key,0,2)=='c_')   $c_total+=$form[$key];
        if(substr($key,0,2)=='i_')   $i_total+=$form[$key];
    }
       SetNormal($pdf);
		    $w=getwidth($c_total,$pdf);
		    $pdf->cell(50,0,'Total Cash',0,0,'R',0);
		    $pdf->cell($w,0,'$ '. number_format($c_total),0,0,'L',0);
		    $pdf->cell(10,0,'',0,0,'L',0);
		    $w=getwidth($i_total,$pdf);
		    $pdf->cell(20,0,'Total In-Kind:',0,0,'R',0);
		    $pdf->cell($w,0,'$ '. number_format($i_total),0,1,'L',0);
  
    
    
     // stream to the browser
     $fileName='form.pdf';
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
 /*
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

    $pdf->Ln(1);
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
    $h=$pdf->getFontSizePt()/2+1;
    $pdf->SetFillColor(220,220,255);
    $w=getwidth($text,$pdf);
    $pdf->cell(20,0,$header,array('B'=>array('color'=>array(255,255,255))),0,'R',0);
    $pdf->cell(4,0,'',0,0,'L',0);
    SetNormalBold($pdf);
    $pdf->cell($w,0,$text,array('B'=>array('width'=>0.25,'cap'=>'butt','join'=>'miter','dash'=>0,'color'=>array(150,150,150))),0,'L',$fill);
    $pdf->cell(10,0,'',0,0,'L',0);
    $w=getwidth($text2,$pdf)+2;
    SetNormal($pdf);
    $pdf->cell(20,0,$header2,array('B'=>array('color'=>array(255,255,255))),0,'R',0);
    SetNormalBold($pdf);
    $pdf->cell($w,0,$text2,array('B'=>array('width'=>0.25,'cap'=>'butt','join'=>'miter','dash'=>0,'color'=>array(150,150,150))),1,'L',$fill);
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
function onecheckrev($header='',$bool=true,$pdf,$inset=20,$name='test'){
    SetNormal($pdf);
    $pdf->SetFillColor(220,220,255);
    $pdf->cell($inset,0,$header,0,0,'R',0);
    $pdf->CheckBox($name,4,$bool,array(),array(),'Yes','',$pdf->getY()+12);
    $pdf->Ln(7);
}


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

?>
