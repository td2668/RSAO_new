<?php
  
function printPDF($form_tracking_id,$user,$db,$dest='I',$prefix=''){
    /**
    * Formats and prints a complete tracking form
    * Extended to allow a write to file on the server.
    * If called with just the first 3 params it returns a PDF to the browser. 
    * Otherwise, if dest='F', the prefix is the loc to save on the server. 
    */
    if($prefix !='') $prefix="$prefix/";
    require_once('includes/tcpdf/config/lang/eng.php');
    require_once('includes/tcpdf/tcpdf.php');
    require_once('includes/pdf_functions.php');
    global $niceday;
    //Do any required checks for security
    
    //id was already checked before calling
    $sql="SELECT     forms_tracking.*, 
                    ors_project.name,
                    ors_agency.name as agency,
                    ors_program.name as program_name
                    FROM forms_tracking 
                    LEFT JOIN ors_project ON (forms_tracking.project_id=ors_project.id) 
                    LEFT JOIN ors_agency ON (forms_tracking.agency_id=ors_agency.id)
                    LEFT JOIN ors_program ON (forms_tracking.program_id=ors_program.id)
                    WHERE form_tracking_id=$form_tracking_id";
    $form=$db->getRow($sql);
    $sql="SELECT * FROM users where user_id=$user";
    $user=$db->getRow($sql);
    $username=$user['last_name'].' '.$user['first_name'];
    
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
    $pdf->SetTitle('Tracking Form: ' . $form['tracking_name']);
    $pdf->SetSubject('Tracking Form submitted by ' . $username);
    //$pdf->SetKeywords("cv, annual report, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}");
    
    // set default header data
    $headerTitle='MRU Research Tracking Form';
    $headerText=substr($form['tracking_name'],0,60);
    if(strlen($form['tracking_name'])> 60) $headerText.='...';
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
    

    oneline('Title:',$form['tracking_name'],$pdf);
    
    if($form['name']=='') $form['name']='[NONE]';
    oneline('Project:',$form['name'],$pdf);  
    
    //Determine proper PI
    if($form['pi']) $pi_id=$form['user_id'];
    else $pi_id=$form['pi_id'];
    if($pi_id != 0){
        $sql="SELECT users.last_name,users.first_name,departments.name FROM users LEFT JOIN departments on (users.department_id=departments.department_id)  WHERE user_id=$pi_id";
        $pi=$db->getRow($sql);
        if($pi) {
            $piname=$pi['last_name'].' '.$pi['first_name'];
            if($pi['name']!='') $name.= " ($pi[name])";
            oneline('PI:',$piname,$pdf);
        }
    }
    else {
        
        if($form['lastname']=='' && $form['firstname']=='') $name='(Not Named)'; else $piname=$form['lastname'].' '.$form['firstname'];
        oneline('PI:',$piname,$pdf); 
        if($form['phone']!='') oneline('',$form['phone'],$pdf);
        if($form['email']!='') oneline('',$form['email'],$pdf);
        if($form['address1'] != '') oneline('',$form['address1'],$pdf);
        if($form['address2'] != '') oneline('',$form['address2'],$pdf);
        if($form['address3'] != '') oneline('',$form['address3'],$pdf);
    }
    $pdf->Ln(3);
     
    
    $sql="SELECT * FROM forms_tracking_coresearchers LEFT JOIN users ON (forms_tracking_coresearchers.user_id=users.user_id) LEFT JOIN departments on (users.department_id=departments.department_id) WHERE forms_tracking_coresearchers.form_tracking_id=$form[form_tracking_id]";
    $cos=$db->getAll($sql);
    if(($cos)){
        if(count($cos)>1) $plural='s'; else $plural='';
        foreach($cos as $key=>$co){
            $name=$co['last_name'].', '.$co['first_name'];
            if($co['name']!='') $name.= " ($co[name])";
            if($key==0) oneline("Co-Researcher$plural:",$name,$pdf);
            else oneline('',$name,$pdf);
        }
        $pdf->Ln(3);
    }
    
       
    if($form['coresearchers'] != ''){
        onemline('Others:',($form['coresearchers']),$pdf);
        $pdf->Ln(3);
    }
    
    
    if($form['costudents']){
        onecheck('Co-Students',true,$pdf,20,'costudents');
        $pdf->Ln(2);
    }
    
    if($form['funding']){
        SetNormal($pdf);
        $pdf->SetFillColor(225);
            $pdf->SetX($indent1);
        AddH1($pdf,'Funding');
        
        if($form['agency']!='') {oneline('Agency:',$form['agency'],$pdf); $pdf->Ln(1);}
        
        if($form['program_name']!='') {oneline('Program:',$form['program_name'],$pdf); $pdf->Ln(1);}
        if($form['agency_name']!='') {oneline('Agency/Prog:',$form['agency_name'],$pdf); $pdf->Ln(1);}

        $sql="SELECT * FROM forms_tracking_budgets WHERE form_tracking_id=$form[form_tracking_id]";
        $budget=$db->getRow($sql);
        if($budget){
            SetNormal($pdf);
            $pdf->Ln(2);
            $pdf->cell(40,0,'',0,0,'R',0);
            $pdf->cell(20,0,'Cash',0,0,'L',0);
            $pdf->cell(2,0,'',0,0,'L',0);
            $pdf->cell(20,0,'In-Kind',0,0,'L',0);
            $pdf->cell(40,0,'',0,0,'R',0);
            $pdf->cell(20,0,'Cash',0,0,'L',0);
            $pdf->cell(2,0,'',0,0,'L',0);
            $pdf->cell(20,0,'In-Kind',0,1,'L',0);
            budgetline('Stipends to Investigators:',$budget['c_stipends'],$budget['i_stipends'],'Benefits:',$budget['c_benefits'],$budget['i_benefits'],$pdf);
            budgetline('Secretarial/Tech Personnel:',$budget['c_persons'],$budget['i_persons'],'Equipment:',$budget['c_equipment'],$budget['i_equipment'],$pdf);
            budgetline('Assistantships   ','','','Supplies:',$budget['c_supplies'],$budget['i_supplies'],$pdf);
            budgetline('   Undergrad Students:',$budget['c_ustudents'],$budget['i_ustudents'],'Travel:',$budget['c_travel'],$budget['i_travel'],$pdf);
            budgetline('   Grad Students/Post-Docs:',$budget['c_gstudents'],$budget['i_gstudents'],'IT Services:',$budget['c_comp'],$budget['i_comp'],$pdf);
            budgetline('   Other RAs:',$budget['c_ras'],$budget['i_ras'],'Overhead:',$budget['c_oh'],$budget['i_oh'],$pdf);
            budgetline('   Other:',$budget['c_others'],$budget['i_others'],'Space/Facilities:',$budget['c_space'],$budget['i_space'],$pdf);
            $pdf->SetFillColor(220,220,255);
            
            if(trim($budget['others_text'])!=''){
             	$pdf->cell(40,0,'(Stipulate):',0,0,'R',0);
             	$pdf->cell(124,0,$budget['others_text'],1,1,'L',0);
            }
            $pdf->ln(4);
            $i_total=$c_total=0;
            foreach($budget as $key=>$oneitem){
                if(substr($key,0,2)=='c_')   $c_total+=$budget[$key];
                if(substr($key,0,2)=='i_')   $i_total+=$budget[$key];
            }
            SetNormal($pdf);
		    $w=getwidth($c_total,$pdf);
		    $pdf->cell(50,0,'Total Cash',0,0,'R',0);
		    $pdf->cell($w,0,'$ '. number_format($c_total),0,0,'L',0);
		    $pdf->cell(10,0,'',0,0,'L',0);
		    $w=getwidth($i_total,$pdf);
		    $pdf->cell(20,0,'Total In-Kind:',0,0,'R',0);
		    $pdf->cell($w,0,'$ '. number_format($i_total),0,1,'L',0);
            
            $pdf->Ln(1);
            onecheck('Confirmed:',$form['funding_confirmed'],$pdf,20,'confirmed');
            SetNormal($pdf);
		    $w=getwidth($form['requested'],$pdf);
		    $pdf->cell(26,0,'Requested',0,0,'R',0);
		    $pdf->cell($w,0,'$ '. number_format($form['requested']),0,0,'L',0);
		    $pdf->cell(10,0,'',0,0,'L',0);
		    $w=getwidth($form['received'],$pdf);
		    $pdf->cell(20,0,'Awarded:',0,0,'R',0);
		    $pdf->cell($w,0,'$ '. number_format($form['received']),0,1,'L',0);
            //twoline('Requested:','$ '.$form['requested'],'Awarded:','$ '.$form['received'],$pdf);
        }//end budget
        
    }//end funding
    

    SetNormal($pdf);
    $pdf->SetFillColor(225);
     $pdf->SetX($indent1);
    AddH1($pdf,'Commitments');
    if($form['equipment_flag']) {onemline('Equipment:',$form['equipment'],$pdf); $pdf->Ln(1);}
    if($form['space_flag']) {onemline('Space:',$form['space'],$pdf);$pdf->Ln(1);}
    if($form['commitments_flag']) {onemline('Other:',$form['commitments'],$pdf);$pdf->Ln(1);}
    
    if(!$form['equipment_flag'] && !$form['space_flag'] && !$form['commitments_flag']) {
        SetNormalBold($pdf);
        
        //$pdf->cell(20,0,'',0,0,'R',0);
        $pdf->cell(50,0,'No Commitments Specified',0,1,'L',0);
    }
    $pdf->Ln(2);
    if($form['employ_flag']) onecheck('Persons Employed',1,$pdf,35,'employ_flag');
    if($form['emp_students']) onecheck('Students Employed',1,$pdf,35,'emp_students');
    if($form['emp_ras']) onecheck('RAs Employed',1,$pdf,30,'empras');
    if($form['emp_consultants']) onecheck('Consultants Employed',1,$pdf,40,'consult');
    
    $pdf->Ln(2);
    
    
    /////////////////////////////////////////
    
    //COI Section
    SetNormal($pdf);
    $pdf->SetFillColor(225);
     $pdf->SetX($indent1);
    AddH1($pdf,'COI');
    
    $sql="SELECT * FROM `forms_coi` 
          LEFT JOIN `users` using (`user_id`)
          WHERE `form_tracking_id`= '{$form['form_tracking_id']}'
                            ";
    $cois=$db->GetAll($sql);
    if(is_array($cois)) if(count($cois) > 0){
        $count=1;
        foreach($cois as $coi){
            if($coi['name']!='') $name=$coi['name'];
            else $name=$coi['first_name'] . ' ' . $coi['last_name'];
            oneline('For:',$name,$pdf);
            $pdf->Ln(2);
            $dec='';
            if($coi['coi_none']) {$dec="No Conflicts"; onemline('Declared:',$dec,$pdf);}
            else{
                if($coi['coi01']) $dec.="Interest in a research, business, contract or transaction\n";
                if($coi['coi02']) $dec.="Influencing purchase of equipment, materials or services\n";
                if($coi['coi03']) $dec.="Acceptance of gifts, benefits or financial favours\n";
                if($coi['coi04']) $dec.="Use of information\n";
                if($coi['coi05']) $dec.="Use of students, university personnel, resources or assets\n";
                if($coi['coi06']) $dec.="Involvement in personnel decisions\n";
                if($coi['coi07']) $dec.="Evaluation of academic work\n";
                if($coi['coi08']) $dec.="Academic program decisions\n";
                if($coi['coi09']) $dec.="Favouring outside interests for personal gain\n";
                if($coi['coi10']) $dec.="Relationship\n";
                if($coi['coi11']) $dec.="Undertaking of outside activity\n";
                if($coi['coi_other']) $dec.="Other\n";
                onemline('Declared:',$dec,$pdf);
                //onemline('The Relationship:',$coi['relationship'],$pdf);
                //onemline('The Situation:',$coi['situation'],$pdf);
                if(count($cois)>$count) $pdf->Ln(2);
                $count++;
            }
            
        }
    }
    else {
    	SetNormalBold($pdf);
        $pdf->cell(50,0,'No COI declarations filed',0,1,'L',0);
    }
    
    
    //Compliance Section
    
    SetNormal($pdf);
    $pdf->SetFillColor(225);
     $pdf->SetX($indent1);
    AddH1($pdf,'Compliance');
    
    $loc='';
    if($form['loc_mru']) $loc.='MRU';
    if($form['loc_canada']){
        if($loc !='') $loc.=', ';
        if($form['where']=='') $loc.='Elsewhere in Canada';
        else 
            if(!$form['loc_international']) $loc.=$form['where'];
    }
    if($form['loc_international']){
        if($loc !='') $loc.=', ';
        if($form['where']=='') $loc.='Outside of Canada';
        else $loc.=$form['where'];
    }
    if(trim($loc)!='') {
    	oneline('Location:',$loc,$pdf);
    	$pdf->Ln(2);
    }
    else  {
    	SetNormalBold($pdf);
        $pdf->cell(50,0,'No location specified',0,1,'L',0);
    	$pdf->Ln(2);
    }
    
    onecheck('Human Subjects (behavioural)',$form['human_b'],$pdf,45,'hrebb');
    onecheck('Human Subjects (health)',$form['human_h'],$pdf,45,'hrebh');
    onecheck('Biohazards',$form['biohaz'],$pdf,25,'biohaz');
    onecheck('Animal Subjects',$form['animal'],$pdf,30,'animal');
    $pdf->Ln(5);
    
    
    // Tracking and Signatures
    
    SetNormal($pdf);
    $pdf->SetFillColor(225);
     $pdf->SetX($indent1);
    AddH1($pdf,'Tracking and Approvals');
    if(!is_null($form['submit_date'])) oneline('Submitted:',date($niceday,strtotime($form['submit_date'])),$pdf);
    else oneline('Submitted:','n/a',$pdf);
    $pdf->Ln(2);
    
    
    if($form['ors_sig']){
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
    	}//else
    
    
    
    
    
    
    
     // stream to the browser
     $fileName="Tracking Form $piname #$form[form_tracking_id].pdf";
     $fileName=CleanFilename($fileName);
     umask(0000);
     $pdf->Output("$prefix$fileName", $dest); // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
     chmod("$prefix$fileName",0777);
}





?>
