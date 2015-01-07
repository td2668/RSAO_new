<?php
/**
* Format CV Item based on type. Called for any display of the data.
* 
* @param mixed $cv_item The item, drawn from cas_cv_items table
* @param mixed $style Display style - currently only APA is implemeneted
* @param mixed $target Can be one of three: Report-for printing on annual report (no errors); 'screen' - for listing on screen (show errors), 'web' - for ORS website. Currently 'report' and 'web' are set the same.
*/
function formatitem ( $cv_item, $style='apa', $target='report' ) {
    global $db;
    //Set the same for now. May want to diverge later.
    if($target=='web') $target='report';
    
    //Build a standardized array from the db fields. This depends on the field #s remaining constant for each type.
    //Grab user info for later

    $sql = "SELECT first_name, last_name FROM `users` WHERE `user_id`='$cv_item[user_id]'";
    $user = $db->getRow( $sql );
    if ( !$user )
        $user['last_name'] = $user['first_name'] = '';
    $ref = array( );
    $ref['authors'] = array( );
    $result = '';
    $icons = '';
    
    //For error messages
    $errhead ="<font color='red'>";
    $errtail="</font>";

    switch ( $cv_item['cas_type_id'] ) {

       case 1: /////////////  Degrees  //////////////////////
             if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_degree_types WHERE `id`='$cv_item[n02]'";
                $degree=$db->getRow($sql);
                if($degree) $ref['degree']=($degree['name']);
             }
             
             if($cv_item['n05']!='') $ref['major']=cleanText($cv_item['n05']);


             if($cv_item['n09']!='0000-00-00') $start=formatDate($cv_item['n09']);
             if($cv_item['n18']!='0000-00-00') $end=formatDate($cv_item['n18']);
             
             if($cv_item['n19']!='0000-00-00') $exp=formatDate($cv_item['n19']);
             if(!isset($start) && !isset($end) && !isset($exp) && $target=='screen') $ref['dates']="{$errhead}DATES{$errtail}";

             if($cv_item['n13']==0 && $target=='screen') $ref['status']="{$errhead}STATUS{$errtail}";
             elseif($cv_item['n13']!=2) {  //no status if complete
                 $sql="SELECT * FROM cas_degree_statuses WHERE `id`='$cv_item[n13]'";
                 $status=$db->getRow($sql);
                 if($status) $ref['status']=$status['name'];
             }
             if($cv_item['n13']==2 || $cv_item['n13']==4) {//completed or withdrawn
                 if(!isset($start) && isset($end)) $ref['dates']="$end";
                 elseif(isset($start) && isset($end)) $ref['dates']="$start - $end";
                 elseif(isset($start) && !isset($end)) $ref['dates']="$start - ";
             }
             elseif($cv_item['n13']==1 || $cv_item['n13']==3) {//in progress or abd
                if(!isset($start) && isset($exp)) $ref['dates']="Expected: $exp";
                 elseif(isset($start) && isset($exp)) $ref['dates']="$start - $exp";
                 elseif(isset($start) && !isset($exp)) $ref['dates']="$start - Present";
             }

             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $inst=$db->getRow($sql);
                if($inst) $ref['institution']=cleanText($inst['name']);
             }
             if($cv_item['n14']!='') $ref['specialization']= cleanText($cv_item['n14']);
             if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);

             $sql="SELECT * FROM `cas_sub_names` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='n15' ";
             $authors=$db->getAll($sql);
             if(count($authors) > 0){
                foreach($authors as $author){
                      $ref['supervisors'][]=cleanText($author['lastname']) . ', '. cleanText($author['firstname']);
                }
             }//count
             if(count($authors)> 1) $ref['plural']='s'; else $ref['plural']='';


             switch ($style){
                case 'apa':
                    $result='';
                    if(isset($ref['degree'])) $result.="$ref[degree]";
                    elseif($target=='screen') $result.="{$errhead}DEGREE TYPE{$errtail}";
                    if(isset($ref['major'])) $result.=" in $ref[major]";
                    if(isset($ref['status'])) $result.=" ($ref[status])";
                    if(isset($ref['dates'])) $result.=", $ref[dates]";
                    if(isset($ref['institution'])) $result.=". <i>$ref[institution]</i>";
                    if(isset($ref['specialization'])) $result.=". Specialization: $ref[specialization]";
                    if(isset($ref['degree'])) if($ref['degree']=='Ph.D.') $type='Dissertation'; else $type='Thesis';
                    if(isset($ref['title'])) $result.=". $type title: \"$ref[title]\"";
                    if(isset($ref['supervisors'])) $result.=". Supervised by: ";

                    if(isset($ref['supervisors'])) {
                        if(count($ref['supervisors']) > 0) {$result.= $ref['supervisors'][0]; array_shift($ref['supervisors']);}
                        while($ref['supervisors'])  {
                            if(count($ref['supervisors'])==1) $connector=', & '; else $connector=', ';
                            $result.=$connector . $ref['supervisors'][0];
                            array_shift($ref['supervisors']);
                        }
                    }
                    $result.=".";

                break;  //style
             }

          break;  //end of type

          case 2:     /////////////// Professional Designations //////////////////
            $ref['description']=cleanText($cv_item['n01']);
            if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $inst=$db->getRow($sql);
                if($inst) $ref['institution']=cleanText($inst['name']);
             }

             if($cv_item['n09']!='0000-00-00') $ref['year']= formatDate($cv_item['n09']);
             

             switch ($style) {
                case 'apa':
                    $result=$ref['description'];
                    if(isset($ref['year'])) $result.=", $ref[year]"; 
                    elseif($target=='screen') $result.="{$errhead}, DATE{$errtail}";
                   
                    if(isset($ref['institution'])) $result.=". <i>$ref[institution]</i>";
                    $result.='.';
                break;
             }
          break;



          case 3: ////////  Educ Institution Employment ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']); 
            
            if($cv_item['n03']) $ref['current']=TRUE;
            if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $inst=$db->getRow($sql);
                if($inst) $ref['institution']=cleanText($inst['name']);
             }
             if($cv_item['n05']!=''){
                 $ref['campus']=cleanText($cv_item['n05']);
                 $ref['campus']=preg_replace('/\sCampus/i','',$ref['campus']);
             }

             $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],$cv_item['n03'],$target);
             if($cv_item['n13']!=0){
                $sql="SELECT * FROM cas_institution_departments WHERE `id`='$cv_item[n13]'";
                $inst=$db->getRow($sql);
                if($inst) $ref['department']=cleanText($inst['name']);
             }
             if($cv_item['n19']!='0000-00-00') {
                 $ref['tenure']=formatDate($cv_item['n19']);
                 
             }
             if($cv_item['n20']!=0){
                $sql="SELECT * FROM cas_countries WHERE `id`='$cv_item[n20]'";
                $inst=$db->getRow($sql);
                if($inst) $ref['country']=cleanText($inst['name']);
             }
             if($cv_item['n21']!=0){
                $sql="SELECT * FROM cas_institutional_position_types WHERE `id`='$cv_item[n21]'";
                $inst=$db->getRow($sql);
                if($inst) $ref['postype']=cleanText($inst['name']);
             }

             switch ($style){
                 case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="<i>$ref[title]</i>";
                    elseif($target=='screen') $result.="{$errhead}TITLE{$errtail}";
                    if(isset($ref['department'])) $result.=", $ref[department]";
                    if(isset($ref['institution'])) $result.=", $ref[institution]";
                    if(isset($ref['campus'])) $result.=" ($ref[campus] Campus)";
                    if(isset($ref['country'])) $result.=", $ref[country]";
                    if(isset($ref['dates'])) $result.=", $ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['postype'])) $result.=" ($ref[postype])";
                    if(isset($ref['tenure'])) $result.=". Tenure achieved: $ref[tenure]";
                    $result.='.';

                 break;
             }
          break;

         case 4:  ///////// Other Employment ////////////////
            if($cv_item['n01']!='') $ref['employer']=cleanText($cv_item['n01']);
            //if($cv_item['n03']) $ref['current']=true;
            if($cv_item['n05']!='') $ref['unit']=cleanText($cv_item['n05']);
            $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],$cv_item['n03'],$target);
            if($cv_item['n14']!='') $ref['position']=cleanText($cv_item['n14']);
            if($cv_item['n20']!=0){
                $sql="SELECT * FROM cas_countries WHERE `id`='$cv_item[n20]'";
                $inst=$db->getRow($sql);
                if($inst) $ref['country']=cleanText($inst['name']);
             }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['position'])) $result.="<i>$ref[position]</i>";
                    if(isset($ref['unit'])) $result.=", $ref[unit]";
                    if(isset($ref['employer'])) $result.=", $ref[employer]";
                    if(isset($ref['country'])) $result.=", $ref[country]";
                    if(isset($ref['dates'])) $result.=", $ref[dates]";
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    $result.='.';
                break;
            }
         break;

          case 5:  ///////////////// Other Studies //////////////////////
            $ref['description']=cleanText($cv_item['n01']);
            if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $inst=$db->getRow($sql);
                if($inst) $ref['institution']=cleanText($inst['name']);
             }
             $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);

             switch ($style) {
                 case 'apa':
                    $result=$ref['description'];
                    if(isset($ref['dates'])) $result.=", $ref[dates]";
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['institution'])) $result.=". <i>$ref[institution]</i>";
                    $result.='.';
                 break;
             }

          break;

         case 6:  ///////// Professional Leaves ////////////////
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_professional_leave_types WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }
             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']=cleanText($result['name']);
             }
             $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],$cv_item['n03'],$target);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['type'])) $result.=", $ref[type] Leave";
                    if(isset($ref['institution'])) $result.=", $ref[institution]";
                    $result.='.';
                break;
            }
         break;

         case 7:  ///////// Personal Leaves ////////////////

            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_professional_leave_types WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }

             $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],$cv_item['n03'],$target);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['type'])) $result.=", $ref[type]";
                    $result.='.';

                break;
            }
         break;

         case 8:  ///////// Grants ////////////////
            //Use the year-awarded as the main item, otherwise use the start date.
            if(namesList($cv_item,'n16')!='') $ref['others']=namesList($cv_item,'n16');
            if($cv_item['n09']!='0000-00-00') {
                $ref['dates']=formatDate($cv_item['n09']);

                //echo ("The date is ".var_dump($res));
            }
            elseif($cv_item['n18']!='0000-00-00'  || $cv_item['n19']!='0000-00-00') $ref['dates']=startendDate($cv_item['n18'],$cv_item['n19'],false,$target);


            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n14']!='') $ref['fund']=cleanText($cv_item['n14']);
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_funding_organizations WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['org']=cleanText($result['name']);
             }
             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_funding_statuses WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result){
                     if($result['name']=='Applied') $ref['status']='Applied For';
                     elseif ($result['name']=='Not Funded') $ref['status']='Not Funded';
                }
             }
             if($cv_item['n20']!=0){
                $sql="SELECT * FROM cas_funding_types WHERE `id`='$cv_item[n20]'";
                $result=$db->getRow($sql);
                if($result)  $ref['type']=cleanText($result['name']);
             }
             if($cv_item['n06']!='0') $ref['amount']=formatMoney($cv_item['n06'], $cv_item['n13']);


            if(!$cv_item['n03']) $ref['competitive']='Non-Competitive'; else $ref['competitive']='Competitive';

            if($cv_item['n21']!=0){
                $sql="SELECT * FROM cas_investigator_roles WHERE `id`='$cv_item[n21]'";
                $result=$db->getRow($sql);
                if($result)  $ref['role']=cleanText($result['name']);
             }
             //Multi-year details become a table below the item
             if($target=='report'){
                $sql="SELECT * FROM `cas_sub_multiyear` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='n15'";
                $years=$db->getAll($sql);

                if(count($years) > 0){
                    $ref['multiyear']="<br><table align='center'><td></td><td><b>Year</b></td><td><b>Amount</b></td><td><b>% Time</b></td></tr>";
                    foreach($years as $year){
                    $amount=formatMoney($year['amount'],$cv_item['n13']);

                    $ref['multiyear'].="<tr><td>&nbsp;&nbsp;&nbsp;</td><td>$year[year]</td><td>$amount</td><td align='center'>$year[percent_time]</td></tr>";
                    }
                    $ref['multiyear'].="</table>";
                }//count
             }//target type



            switch($style){
                case 'apa':
                $result='';
                if(isset($ref['dates'])) $result.="$ref[dates]";
                elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                if(isset($ref['status'])) $result.=" ($ref[status])";
                if(isset($ref['org'])) $result.=" $ref[org]";
                if(isset($ref['fund'])) $result.=": $ref[fund]";
                if(isset($ref['title'])) $result.=", \"$ref[title]\"";
                if(isset($ref['amount'])) $result.=". $ref[amount]";
                if(isset($ref['type'])) $result.=". Type: $ref[type]-$ref[competitive]";
                if(isset($ref['role'])) $result.=" (Role: $ref[role])";
                if(isset($ref['others'])) $result.=". Other Investigators: $ref[others]";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                if(isset($ref['multiyear'])) $result.=$ref['multiyear'];

                break;
            }
         break;

         case 9:  ///////// Contracts ////////////////
            if(namesList($cv_item,'n16')!='') $ref['others']=namesList($cv_item,'n16');

            if($cv_item['n18']!='0000-00-00' || $cv_item['n19']!='0000-00-00') $ref['dates']=startendDate($cv_item['n18'],$cv_item['n19'],false,$target);

            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_funding_organizations WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['org']=cleanText($result['name']);
             }
             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_funding_statuses WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) if($result['name']=='Applied') $ref['status']='Applied For';
             }
             if($cv_item['n06']!='0') $ref['amount']=formatMoney($cv_item['n06'], $cv_item['n13']);
            if(!$cv_item['n03']) $ref['competitive']='Non-Competitive'; else $ref['competitive']='Competitive';
            if($cv_item['n21']!=0){
                $sql="SELECT * FROM cas_investigator_roles WHERE `id`='$cv_item[n21]'";
                $result=$db->getRow($sql);
                if($result)  $ref['role']=cleanText($result['name']);
             }

            switch($style){
                case 'apa':
                $result='';
                if(isset($ref['dates'])) $result.="$ref[dates]";
                elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                if(isset($ref['status'])) $result.=" ($ref[status])";
                if(isset($ref['org'])) $result.=" $ref[org]";
                if(isset($ref['title'])) $result.=", \"$ref[title]\"";
                if(isset($ref['amount'])) $result.=". $ref[amount]";
                if(isset($ref['competitive'])) $result.=". Type: $ref[competitive]";
                if(isset($ref['role'])) $result.=" (Role: $ref[role])";
                if(isset($ref['others'])) $result.=". Other Investigators: $ref[others]";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';

                break;
            }
         break;

         case 10:  ///////// Non-research presentations ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['event']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['loc']=cleanText($cv_item['n14']);
            if($cv_item['n22']!='') $ref['org']=cleanText($cv_item['n22']);
            if($cv_item['n03']) $ref['invited']=true;
            if($cv_item['n23']) $ref['keynote']=true;
            if($cv_item['n24']) $ref['competitive']=true;
            if($cv_item['n09']!='0000-00-00') $ref['date']=formatDate($cv_item['n09'],true);
             if(namesList($cv_item,'n15',false)!='') $ref['others']=namesList($cv_item,'n15',false,'',false,'Co-presenter');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="$ref[date]"; 
                    elseif($target=='screen') $result.="{$errhead}DATE{$errtail}";
                    if(isset($ref['title'])) $result.=". \"$ref[title]\"";
                    if(isset($ref['org'])) $result.=". Presented to $ref[org]";
                    if(isset($ref['event'])) $result.=". Presented at $ref[event]";
                    if(isset($ref['loc'])) $result.=", $ref[loc]";
                    if(isset($ref['invited'])) $result.=". Invited Speaker";
                    if(isset($ref['keynote'])) $result.=". Keynote Speaker";
                    if(isset($ref['others'])) $result.=". $ref[others]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';


                break;
            }
         break;

         case 11:  ///////// Committee memberships ////////////////
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_committee_types WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result)  $ref['type']=cleanText($result['name']);
             }
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['do']=cleanText($cv_item['n05']);
            if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']=cleanText($result['name']);
             }
            if($cv_item['n13']!=0){
                $sql="SELECT * FROM cas_committee_roles WHERE `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result)  $ref['role']=cleanText($result['name']);
             }
             if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['title'])) $result.=" $ref[title]";
                    if(isset($ref['institution'])) $result.=", $ref[institution]";
                    if(isset($ref['type'])) $result.=" (Committee Type: $ref[type])";
                    if(isset($ref['role'])) $result.=". Role: $ref[role]";
                    if(isset($ref['do'])) $result.=". $ref[do]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';

                break;
            }
         break;

         case 12:  ///////// Offices Held ////////////////
            if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']=cleanText($result['name']);
             }
             if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_office_held_types WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }
             if($cv_item['n01']!='') $ref['name']=cleanText($cv_item['n01']);
             if($cv_item['n05']!='') $ref['other']=cleanText($cv_item['n05']);
             if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['name'])) $result.=" $ref[name]";
                    if(isset($ref['institution'])) $result.=", $ref[institution]";
                    if(isset($ref['other'])) $result.=", $ref[other]";
                    $result.='.';

                break;
            }
         break;

         case 13:  ///////// Event Admin ////////////////
            if($cv_item['n01']!='') $ref['role']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['descrip']=cleanText($cv_item['n05']);

            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['actdates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            if($cv_item['n19']!='0000-00-00' || $cv_item['n29']!='0000-00-00') $ref['eventdates']=startendDate($cv_item['n19'],$cv_item['n29'],false,$target);
            if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_event_types WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }
             if($cv_item['n13']!=0){
                $sql="SELECT * FROM cas_event_organizers WHERE `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['organizer']=cleanText($result['name']);
             }


            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['actdates'])) $result.="$ref[actdates]"; 
                    elseif($target=='screen' && !isset($ref['eventdates'])) $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['role'])) $result.=". $ref[role]";
                    if(isset($ref['descrip'])) $result.=", $ref[descrip]";
                    if(isset($ref['eventdates'])) $result.=" ($ref[eventdates])";
                    if(isset($ref['type'])) $result.=". Type: $ref[type]";
                    if(isset($ref['organizer'])) $result.=". Organizer: $ref[organizer]";

                    $result.='.';
                break;
            }
         break;

         case 14:  ///////// Editorial Activities ////////////////
            if($cv_item['n01']!='') $ref['pub']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['type']=cleanText($cv_item['n05']);
            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";
                    if(isset($ref['pub'])) $result.=" $ref[pub]";
                    if(isset($ref['type'])) $result.=": $ref[type]";
                    $result.='.';
                break;
            }
         break;

         case 15:  ///////// Consulting/Advising ////////////////
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['org']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['dept']=cleanText($cv_item['n14']);
            if($cv_item['n13']!=0){
                $sql="SELECT * FROM cas_institution_types WHERE `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }
             if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";
                    if(isset($ref['org'])) $result.=", for $ref[org]";
                    if(isset($ref['dept'])) $result.=", $ref[dept]";
                    if(isset($ref['type'])) $result.=". (Type: $ref[type])";
                    $result.='.';
                break;
            }
         break;

         case 16:  ///////// Expert Witness ////////////////
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['case']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['loc']=cleanText($cv_item['n14']);
            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['desc'])) $result.=" $ref[desc]";
                    if(isset($ref['case'])) $result.=" for $ref[case]";
                    if(isset($ref['loc'])) $result.=", $ref[loc]";

                    $result.='.';
                break;
            }
         break;

         case 17:  ///////// Journal Reviewing/Refereeing ////////////////
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_research_journals WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['journal']=cleanText($result['name']);
             }
             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_review_types WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }
             if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);
             if($cv_item['n06']!=0) $ref['num']=$cv_item['n06'];
             if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['journal'])) $result.=" $ref[journal]";

                    if(isset($ref['num'])) {$num=$ref['num'].' '; $plural=($ref['num']>1) ? 's':'';}
                    else $num=$plural='';
                    if(isset($ref['type'])) $result.=". $num$ref[type] Review$plural";
                    if(isset($ref['desc'])) $result.=". \"$ref[desc]\"";
                    $result.='.';
                break;
            }
         break;

         case 18:  ///////// Conferenece Reviewing ////////////////
             if($cv_item['n05']!='') $ref['host']=cleanText($cv_item['n05']);
             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_review_types WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }
             if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);
             if($cv_item['n06']!=0) $ref['num']=$cv_item['n06'];
             if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['num'])) {$num=$ref['num'].' '; $plural=($ref['num']>1) ? 's':'';}
                    else $num=$plural='';
                    if(isset($ref['type'])) $result.="&nbsp; $num$ref[type] Review$plural";
                    if(isset($ref['desc'])) $result.=". \"$ref[desc]\"";
                    if(isset($ref['host'])) $result.=", $ref[host]";
                    $result.='.';
                break;
            }
         break;

         case 19:  ///////// Graduate Exam ////////////////
            if($cv_item['n01']!='' && $cv_item['n01']!='|') $ref['name']=formatName($cv_item['n01']);
            $ref['date']=formatDate($cv_item['n09'],true,true);

             if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_graduate_examination_roles WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['role']=cleanText($result['name']);
             }
             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']=cleanText($result['name']);
             }
             if($cv_item['n13']!=0){
                $sql="SELECT * FROM cas_institution_departments WHERE `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['dept']=cleanText($result['name']);
             }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="$ref[date]";  
                    elseif($target=='screen') $result.="{$errhead}DATE{$errtail}";
                    if(isset($ref['name'])) $result.=" Student: $ref[name]";
                    if(isset($ref['dept'])) $result.=" $ref[dept]";
                    if(isset($ref['institution'])) $result.=", $ref[institution]";
                    if(isset($ref['role'])) $result.=". Role: $ref[role]";
                    $result.='.';
                break;
            }
         break;

         case 20:  ///////// Grant Applic Assessment ////////////////
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_assessment_types WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }
             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) if(trim($result['name'])!='') $ref['institution']=cleanText($result['name']);
             }
             if($cv_item['n13']!=0){
                $sql="SELECT * FROM cas_institution_departments WHERE `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['dept']=cleanText($result['name']).', ';
                else $ref['dept']='';
             }
             else $ref['dept']='';
             if($cv_item['n20']!=0){
                $sql="SELECT * FROM cas_funding_organizations WHERE `id`='$cv_item[n20]'";
                $result=$db->getRow($sql);
                if($result) $ref['org']=cleanText($result['name']);
             }
             if($cv_item['n06']!=0) $ref['num']=$cv_item['n06'];
             if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";      
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['num'])) {$num=$ref['num'].' '; $plural=($ref['num']>1) ? 's':'';}
                    else $num=$plural='';
                    if(isset($ref['type'])) $result.="&nbsp; $num$ref[type] Assessment$plural"; 
                    elseif($target=='screen') $result.="{$errhead} TYPE{$errtail}";

                    if(isset($ref['institution'])) $result.=". Institution: $ref[dept]$ref[institution]";
                    if(isset($ref['desc'])) $result.=". Program: $ref[desc]";
                    if(isset($ref['org'])) $result.=". Funder: $ref[org]";


                    $result.='.';
                break;
            }
         break;

         case 21:  ///////// Promotion/Tenure Assessment ////////////////
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);
            if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']=cleanText($result['name']);
             }
            if($cv_item['n13']!=0){
                $sql="SELECT * FROM cas_institution_departments WHERE `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['dept']=cleanText($result['name']).', ';
                else $ref['dept']='';
             }
             else $ref['dept']='';
             if($cv_item['n06']!=0) $ref['num']=$cv_item['n06'];
             if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";  
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";
                    if(isset($ref['num'])) {$num=$ref['num'].' '; $plural=($ref['num']>1) ? 's':'';}
                    else $num=$plural='';
                    if(isset($ref['num'])) $result.=".&nbsp; $num Assessment$plural";
                    if(isset($ref['institution'])) $result.=". Institution: $ref[dept]$ref[institution]";
                    $result.='.';
                break;
            }
         break;

         case 22:  ///////// Institutional Review ////////////////

           if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);

             if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_institutions WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']=cleanText($result['name']);
             }
             if($cv_item['n13']!=0){
                $sql="SELECT * FROM cas_institution_departments WHERE `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['dept']=cleanText($result['name']);

             }


             if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";   
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['dept'])) $result.=" $ref[dept]";
                    if(isset($ref['dept']) && isset($ref['institution'])) $result.=',';
                    if(isset($ref['institution'])) $result.=" $ref[institution]";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";



                    $result.='.';
                break;
            }
         break;

         case 23:  ///////// Broadcast Interviews ////////////////
            if($cv_item['n01']!='' && $cv_item['n01']!='|') $ref['name']=formatName($cv_item['n01']);
            if($cv_item['n14']!='') $ref['desc']=cleanText($cv_item['n14']);
            if($cv_item['n22']!='') $ref['program']=cleanText($cv_item['n22']);
            if($cv_item['n25']!='') $ref['network']=cleanText($cv_item['n25']);
            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    
                    if(isset($ref['program'])) $result.=" On <i>$ref[program]</i>";
                     if(isset($ref['program']) && isset($ref['network'])) $result.=':';
                    if(isset($ref['network'])) $result.=" $ref[network]";
                    if(isset($ref['name'])) $result.=", by $ref[name]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['desc'])) $result.=" \"$ref[desc]\"";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 24:  ///////// Text Interviews ////////////////
            if($cv_item['n01']!='' && $cv_item['n01']!='|' ) $ref['name']=formatName($cv_item['n01']);
            if($cv_item['n14']!='') $ref['desc']=cleanText($cv_item['n14']);
            if($cv_item['n22']!='') $ref['program']=cleanText($cv_item['n22']);

            $ref['date']=formatDate($cv_item['n09'],true,true);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="$ref[date]"; 
                    elseif($target=='screen') $result.="{$errhead}DATE{$errtail}";
                    
                    if(isset($ref['program'])) $result.=". In <i>$ref[program]</i>";
                    if(isset($ref['name'])) $result.=", by $ref[name]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['desc'])) $result.=" \"$ref[desc]\"";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 25:  ///////// Event Participation ////////////////
            if($cv_item['n09']!='0000-00-00') $ref['date']=formatDate($cv_item['n09'],true);
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_event_types WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }
            switch($style){

                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="$ref[date]"; 
                    elseif($target=='screen') $result.="{$errhead}DATE{$errtail}";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";
                    if(isset($ref['type'])) $result.=". ($ref[type])";
                    $result.='.';
                break;
            }
         break;

         case 26:  ///////// Memberships ////////////////
            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            if($cv_item['n01']!='' && $cv_item['n01']!='|') $ref['name']=formatName($cv_item['n01']);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";
                    if(isset($ref['name'])) $result.=" &nbsp;$ref[name]";
                    $result.='.';
                break;
            }
         break;

         case 27:  ///////// Community Service ////////////////
            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            if($cv_item['n01']!='' ) $ref['role']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='' ) $ref['org']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['descrip']=cleanText($cv_item['n14']);
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_community_service_types WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']=cleanText($result['name']);
             }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['role'])) $result.=". &nbsp;$ref[role]";
                    if(isset($ref['org'])) $result.=", with $ref[org]";
                    if(isset($ref['descrip'])) $result.=". Duties: $ref[descrip]";
                    if(isset($ref['type'])) $result.=" (Type: $ref[type])";
                    $result.='.';
                break;
            }
         break;

         case 28:  ///////// Awards and Distinctions ////////////////
            if($cv_item['n01']!='') $ref['name']= cleanText($cv_item['n01']);
            //if($cv_item['n02']!='') $ref['type']= cleanText($cv_item['n02']);

            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_distinction_types` where `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']= $result['name'];
            }
            if($cv_item['n04']!='0') {
                $sql="SELECT * FROM `cas_countries` where `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['country']= $result['name'];
            }
            if($cv_item['n13']!='0') {
                $sql="SELECT * FROM `cas_institutions` where `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']= $result['name'];
            }
            if($cv_item['n06']!='0') $ref['amount']=formatMoney($cv_item['n06'],$cv_item['n20']);
            $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['type'])) $result.="$ref[type]: ";
                    if(isset($ref['name'])) $result.="\"$ref[name]\"";
                    if(isset($ref['amount'])) $result.= ". $ref[amount]";
                    if(isset($ref['country'])) if($ref['country'] !='Canada' || $ref['country'] !='United States')$result.= " ($ref[country])";
                    if(isset($ref['institution'])) $result.=". Conferred by <i>$ref[institution]</i>";
                    if(isset($ref['dates'])) $result.= ". $ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}. DATES{$errtail}";
                    $result.='.';

                break;
            }
         break;

         case 29:  ///////// Courses Taught ////////////////
            if($cv_item['n04']!='0') {
                $sql="SELECT * FROM `cas_institutions` where `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']= cleanText($result['name']);
            }
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_academic_sessions` where `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['sessions']= $result['name'];
            }
            if($cv_item['n13']!='0') {
                $sql="SELECT * FROM `cas_course_levels` where `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['level']= $result['name'];
            }
            if($cv_item['n01']!='') $ref['code']= cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['title']= cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['section']= cleanText($cv_item['n14']);
            if($cv_item['n06']!=0) $ref['students']=$cv_item['n06'];
            if($cv_item['n07']!=0) $ref['credits']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['lhours']=$cv_item['n08'];
            if($cv_item['n10']!=0) $ref['thours']=$cv_item['n10'];
            if($cv_item['n11']!=0) $ref['labhours']=$cv_item['n11'];
            if($cv_item['n12']!=0) $ref['chours']=$cv_item['n12'];
            $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            if(namesList($cv_item,'n15')!='') $ref['others']=namesList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]"; 
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['code'])) $result.=" $ref[code]";
                    if(isset($ref['title'])) $result.=" \"$ref[title]\"";
                    if(isset($ref['section'])) $result.=" ($ref[section])";
                    if(isset($ref['institution'])) if($ref['institution']!='MRU' && $ref['institution']!='Mount Royal University') $result.=" at $ref[institution]";
                    if(isset($ref['level'])) $result.=". Level: $ref[level]";
                    if(isset($ref['others'])) $result.=". With $ref[others]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['students'])) $result.=" Students: $ref[students]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['credits'])) $result.=" Credits: $ref[credits]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['credits']) || isset($ref['lhours']) || isset($ref['thours']) || isset($ref['labhours']) || isset($ref['chours'])) $result.= " Hours:";
                    if(isset($ref['lhours'])) $result.=" Lecture: $ref[lhours]";
                    if(isset($ref['thours'])) $result.=" Tutorial: $ref[thours]";
                    if(isset($ref['labhours'])) $result.=" Lab: $ref[labhours]";
                    if(isset($ref['chours'])) $result.=" Other Contact: $ref[chours]";

                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 30:  ///////// Course Development ////////////////
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['descrip']= cleanText($cv_item['n05']);
            if($cv_item['n04']!='0') {
                $sql="SELECT * FROM `cas_institutions` where `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']= cleanText($result['name']);
            }
            if($cv_item['n13']!='0') {
                $sql="SELECT * FROM `cas_institutions` where `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['institutionusing']= cleanText($result['name']);
            }
            $ref['date']=formatDate($cv_item['n09'],true);
            if(namesList($cv_item,'n15')!='') $ref['others']=namesList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="$ref[date]";  
                    elseif($target=='screen') $result.="{$errhead}DATE{$errtail}";
                    if(isset($ref['title'])) $result.=" \"$ref[title]\"";
                    if(isset($ref['descrip'])) $result.=". $ref[descrip]";
                    if(isset($ref['institution'])) if($ref['institution']!='MRU' && $ref['institution']!='Mount Royal University') $result.=" for $ref[institution]";
                    if(isset($ref['institutionusing'])) if($ref['institutionusing']!='MRU' && $ref['institutionusing']!='Mount Royal University') $result.=". Used at $ref[institutionusing]";
                    if(isset($ref['others'])) $result.=". With $ref[others]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';

                break;
            }
         break;

         case 31:  ///////// Program Development ////////////////
            if($cv_item['n04']!='0') {
                $sql="SELECT * FROM `cas_institutions` where `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']= cleanText($result['name']);
            }
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_degree_types` where `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']= $result['name'];
            }
            if($cv_item['n13']!='0') {
                $sql="SELECT * FROM `cas_course_levels` where `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['level']= $result['name'];
            }
            if($cv_item['n21']!='0') {
                $sql="SELECT * FROM `cas_partner_organizations` where `id`='$cv_item[n21]'";
                $result=$db->getRow($sql);
                if($result) $ref['partner']= $result['name'];
            }
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['char']= cleanText($cv_item['n05']);

            $ref['date']=formatDate($cv_item['n09']);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="$ref[date]";
                    if(isset($ref['title'])) $result.=" \"$ref[title]\"";
                    if(isset($ref['institution'])) if($ref['institution']!='MRU' && $ref['institution']!='Mount Royal University') $result.=" at $ref[institution]";
                    if(isset($ref['level'])) $result.=". Level: $ref[level]";
                    if(isset($ref['type'])) $result.=". Type: $ref[type]";
                    if(isset($ref['partner'])) $result.=". Partner: $ref[partner]";
                    if(isset($ref['char'])) $result.=". $ref[char]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 32:  ///////// Research-based degree ////////////////
            if($cv_item['n01']!='' && $cv_item['n01']!='|') $ref['name']= formatName($cv_item['n01'],false);
            if($cv_item['n14']!='') $ref['title']= cleanText($cv_item['n14']);
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_degree_types` where `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']= $result['name'];
            }
            if($cv_item['n22']!='') $ref['subject']= cleanText($cv_item['n22']);
            if($cv_item['n13']!='0') {
                $sql="SELECT * FROM `cas_degree_statuses` where `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['status']= $result['name'];
            }
            if($cv_item['n20']!='0') {
                $sql="SELECT * FROM `cas_institutions` where `id`='$cv_item[n20]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']= cleanText($result['name']);
            }
            $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            if($cv_item['n25']!='') $ref['position']= cleanText($cv_item['n25']);
            if($cv_item['n21']!='0') {
                $sql="SELECT * FROM `cas_supervisory_roles` where `id`='$cv_item[n21]'";
                $result=$db->getRow($sql);
                if($result) $ref['role']= cleanText($result['name']);
            }
            if(namesList($cv_item,'n15',true)!='') $ref['cosupervisors']= namesList($cv_item,'n15',true);
            if($cv_item['n14']!='') $ref['thesis']= cleanText($cv_item['n14']);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";  
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['name'])) $result.=". $ref[name]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['type'])) $result.=" Type: $ref[type]";
                    if(isset($ref['subject'])) $result.=" in $ref[subject]";
                    if(isset($ref['status'])) $result.=" ($ref[status])";
                    if(isset($ref['institution'])) $result.=" at $ref[institution]";
                    if(isset($ref['role'])) $result.=". Role: $ref[role]";
                    if(isset($ref['cosupervisors'])) $result.=". Co-Supervisors: $ref[cosupervisors]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['position'])) $result.=" Current Position: $ref[position]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['thesis'])) $result.=" Thesis Title: \"$ref[thesis]\"";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';

                break;
            }
         break;

         case 33:  ///////// Course-based degree ////////////////
            if($cv_item['n01']!='' && $cv_item['n01']!='|') $ref['name']= formatName($cv_item['n01'],false);
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_degree_types` where `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']= $result['name'];
            }
            if($cv_item['n22']!='') $ref['subject']= cleanText($cv_item['n22']);
            if($cv_item['n13']!='0') {
                $sql="SELECT * FROM `cas_degree_statuses` where `id`='$cv_item[n13]'";
                $result=$db->getRow($sql);
                if($result) $ref['status']= $result['name'];
            }
            if($cv_item['n20']!='0') {
                $sql="SELECT * FROM `cas_institutions` where `id`='$cv_item[n20]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']= cleanText($result['name']);
            }
            $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            if($cv_item['n25']!='') $ref['position']= cleanText($cv_item['n25']);
            if($cv_item['n21']!='0') {
                $sql="SELECT * FROM `cas_supervisory_roles` where `id`='$cv_item[n21]'";
                $result=$db->getRow($sql);
                if($result) $ref['role']= cleanText($result['name']);
            }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['name'])) $result.=". $ref[name]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['type'])) $result.=" Type: $ref[type]";
                    if(isset($ref['subject'])) $result.=" in $ref[subject]";
                    if(isset($ref['status'])) $result.=" ($ref[status])";
                    if(isset($ref['institution'])) $result.=" at $ref[institution]";
                    if(isset($ref['role'])) $result.=". Role: $ref[role]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['position'])) $result.=" Current Position: $ref[position]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';

                break;
            }
         break;

         case 34:  ///////// Employee Supervisions ////////////////
            if($cv_item['n01']!='' && $cv_item['n01']!='|') $ref['name']= formatName($cv_item['n01'],false);
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_employee_types` where `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['type']= $result['name'];
            }
            if($cv_item['n22']!='') $ref['subject']= cleanText($cv_item['n22']);

            if($cv_item['n20']!='0') {
                $sql="SELECT * FROM `cas_institutions` where `id`='$cv_item[n20]'";
                $result=$db->getRow($sql);
                if($result) $ref['institution']= cleanText($result['name']);
            }
            $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);

            if($cv_item['n21']!='0') {
                $sql="SELECT * FROM `cas_supervisory_roles` where `id`='$cv_item[n21]'";
                $result=$db->getRow($sql);
                if($result) $ref['role']= cleanText($result['name']);
            }
            if(namesList($cv_item,'n15',true)!='') $ref['cosupervisors']= namesList($cv_item,'n15',true);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.="$ref[dates]";
                    elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                    if(isset($ref['name'])) $result.=". $ref[name]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if(isset($ref['type'])) $result.=" Type: $ref[type]";
                    if(isset($ref['subject'])) $result.=". Subject: $ref[subject]";
                    if(isset($ref['institution'])) $result.=", at $ref[institution]";
                    if(isset($ref['role'])) $result.=". Supervisory Role: $ref[role]";
                    if(isset($ref['cosupervisors'])) $result.=". Co-Supervisors: $ref[cosupervisors]";

                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';

                break;
            }
         break;

         case 35:  ////////////////// Journal Article  //////////////////////////

            $ref=authorList($ref,$user,$cv_item,'n14','n15',$cv_item['n13'],$target);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_research_journals` where `id`='$cv_item[n02]'";
                $journal=$db->getRow($sql);
                if($journal) $ref['journal']= $journal['name'];
            }
            if($cv_item['n03']) $ref['refereed']=TRUE;
            if($cv_item['n05']!='') $ref['volume']=$cv_item['n05'];
            if($cv_item['n06']!=0) $ref['issue']=$cv_item['n06'];
            if($cv_item['n07']!=0) $ref['from']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['to']=$cv_item['n08'];

            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            if($cv_item['n04']==0 || $cv_item['n04']>4) $ref['published']=TRUE;

            switch ($style) {
              case 'apa':
                $result='';
                if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                if(isset($ref['authors'])) while($ref['authors'])  {
                    if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                    if(isetal($ref['authors'][0])) $connector=', ';
                    $result.=$connector . $ref['authors'][0];
                    array_shift($ref['authors']);
                }
                if(isset($ref['date'])) $result.= ' ('. $ref['date'] .').';
                if(isset($ref['title'])) $result.= ' ' . $ref['title'] . '.';
                if(isset($ref['journal'])) $result.= ' <i>' . $ref['journal'] . '</i>';
                if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                if(isset($ref['from'])) {
                    $result.=", $ref[from]";
                    if(isset($ref['to'])) {
                        $result.="-$ref[to]";
                    }
                }
                if(isset($ref['status'])) $result.=" ($ref[status])";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                if($target!='list'){
                    if(isset($ref['refereed'])) if($ref['refereed']) $icons.="<img src='images/referee-flag-icon.png' alt='Refereed' title='Refereed'>";
                    if(isset($ref['published'])) if($ref['published']) $icons.="<img src='images/book-icon.png' alt='Published' title='Published'>";
                }
              break;
          }//switch style

          break;

          case 36:  ///////// Journal Issues ////////////////
            $ref=authorList($ref,$user,$cv_item,'n14','n15',$cv_item['n13'],$target);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_research_journals` where `id`='$cv_item[n02]'";
                $journal=$db->getRow($sql);
                if($journal) $ref['journal']= $journal['name'];
            }
            if($cv_item['n03']) $ref['refereed']=TRUE;
            if($cv_item['n05']!='') $ref['volume']=$cv_item['n05'];
            if($cv_item['n06']!=0) $ref['issue']=$cv_item['n06'];
            if($cv_item['n07']!=0) $ref['numpages']=$cv_item['n07'];

            switch ($style) {
              case 'apa':
                $result='';
                if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                if(isset($ref['firstauthor']) && isset($ref['authors'])&& count($ref['authors'])> 0) $sub= ' (Eds)';
                elseif(isset($ref['firstauthor'])) $sub= ' (Ed)';
                if(isset($ref['authors'])) while($ref['authors'])  {
                    if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                    if(isetal($ref['authors'][0])) $connector=', ';
                    $result.=$connector . $ref['authors'][0];
                    array_shift($ref['authors']);
                }
                if(isset($ref['firstauthor'])) $result.=$sub;

                if(isset($ref['date'])) $result.= ' ('. $ref['date'] .').';
                if(isset($ref['title'])) $result.= ' ' . $ref['title'] . '.';
                if(isset($ref['journal'])) $result.= ' <i>' . $ref['journal'] . '</i>';
                if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                if(isset($ref['numpages'])) $result.=", $ref[numpages] p.";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                if($target!='list'){
                    if(isset($ref['refereed'])) if($ref['refereed']) $icons.="<img src='images/referee-flag-icon.png' alt='Refereed' title='Refereed'>";
                    if(isset($ref['published'])) if($ref['published']) $icons.="<img src='images/book-icon.png' alt='Published' title='Published'>";
                }
              break;
          }//switch style
         break;

         case 37:  ///////// Books ////////////////26
            //Roles are really tricky here.
            //If the role is 'First Listed Author' or Co-Author, we act normally. Editor Fields come later if set
            //If no author fields are set, but role is 'First Listed Author', then we use their name as author and do editors as above
            //if no author fields are set and role is editor, then the editors come first in the citation.
            //The editor citation can be either initials after (if it comes first) or initials first (if it comes later)
            //THis means that the STYLE is not terribly independent, as we have to set up a variety of options first

            //Grab the role name first to keep this section comprehensible
            $sql="SELECT * FROM cas_book_roles WHERE id='$cv_item[n02]'";
            $role=$db->getRow($sql);
            if($role){
               //If role is author, set up the list normally.
               if($role['name']=='First Listed Author' || $role['name']=='Co-Author'){
                   $ref=authorList($ref,$user,$cv_item,'n26','n16',$cv_item['n02'],$target);
                   //Remove leftover if editor deleted.
                   //TODO: add this fix to other sections
                   if($cv_item['n14']=='|')$cv_item['n14']='';
                   //Eds list comes second, in normal (initials first) order
                   $ref=edsList($ref,$user,$cv_item,'n14','n15',$role['name']);
               }
               elseif($role['name']=='First Listed Editor' || $role['name']=='Co-Editor'){
                   //If there are no author fields filled out then put the eds first, but we need to flag that
                   if($cv_item['n26']=='' && coauthorList($cv_item,'n16')=='') {
                       //echo("nameslist n26=" . coauthorList($cv_item,'n16'));
                       $ref=authorList($ref,$user,$cv_item,'n14','n15',1,$target);
                       if(count($ref['authors'])>0 ) $ref['eds']='Eds'; else  $ref['eds']='Ed';
                   }
                   else{ //there are some author fields, so these go first and we deal with the editors later
                       $ref=authorList($ref,$user,$cv_item,'n26','n16',$cv_item['n02'],$target);

                       $ref=edsList($ref,$user,$cv_item,'n14','n15',$role['name']);
                   }
               }
            }

            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n25']!='') $ref['edition']= cleanText($cv_item['n25']);
            if($cv_item['n05']!='') $ref['vol']= cleanText($cv_item['n05']);
            if($cv_item['n22']!='') $ref['publisher']= cleanText($cv_item['n22']);
            if($cv_item['n27']!='') $ref['publoc']= cleanText($cv_item['n27']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n07']!=0) $ref['numpages']=$cv_item['n07'];
            if($cv_item['n04']!='0') {
                $sql="SELECT * FROM `cas_book_statuses` where `id`='$cv_item[n04]'";
                $journal=$db->getRow($sql);
                if($journal) $ref['status']= $journal['name'];
            }
            if($cv_item['n03']) $ref['refereed']=TRUE;
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_book_roles` where `id`='$cv_item[n04]'";
                $journal=$db->getRow($sql);
                if($journal) $ref['role']= $journal['name'];
            }


            switch($style){

                case 'apa':

                if(isset($ref['firstauthor'])) $result.=$ref['firstauthor']; 
                elseif($target=='screen') $result.="{$errhead}ROLE{$errtail}";
                if(isset($ref['authors'])) while($ref['authors'])  {
                    if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                    if(isetal($ref['authors'][0])) $connector=', ';
                    $result.=$connector . $ref['authors'][0];
                    array_shift($ref['authors']);
                }
                if(isset($ref['eds'])) $result.=" ($ref[eds].)";
                if(isset($ref['date'])) $result.=" ($ref[date])";
                if(isset($ref['title'])) $result.=". <i>$ref[title]</i>";
                if(isset($ref['edition'])) $result.=", $ref[edition]";
                if(isset($ref['vol'])) $result.="($ref[vol])";

                if((isset($ref['firsteditor']) && $ref['firsteditor']!='') || count($ref['editors'])>0) {
                    //echo("Firsteitor: '". ($ref['firsteditor'])."'");
                    $result.=". (";
                    if(count($ref['editors'])>0) $end=", Eds.)"; else $end=", Ed.)";
                    if(isset($ref['firsteditor'])) $result.=$ref['firsteditor'];
                    if(isset($ref['editors'])) while($ref['editors'])  {
                        if(count($ref['editors'])==1) $connector=', & '; else $connector=', ';
                        //Override
                        if(isetal($ref['editors'][0])) $connector=', ';
                        $result.=$connector . $ref['editors'][0];
                        array_shift($ref['editors']);
                        }
                    $result.=$end;
                }
                if(isset($ref['publoc'])) $result.=". $ref[publoc]:";
                if(isset($ref['publisher'])) {
                        if(substr($result,strlen($result)-1,1)!=':') $result.='.';
                        $result.=" $ref[publisher]";
                    }
                if(isset($ref['status'])) $result.=" (Status: $ref[status])";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                if($target!='list'){
                    if(isset($ref['refereed'])) if($ref['refereed']) $icons.="<img src='images/referee-flag-icon.png' alt='Refereed' title='Refereed'>";
                }

                break;
            }
         break;

         case 38:  ///////// Edited Books ////////////////
            $sql="SELECT * FROM cas_book_roles WHERE id='$cv_item[n02]'";
            $role=$db->getRow($sql);
            if($role){
                if($role['name']=='First Listed Author' || $role['name']=='First Listed Editor') $type=1; else $type=2;
               $ref=authorList($ref,$user,$cv_item,'n14','n15',$type,$target);
               if($role['name']=='First Listed Editor' || $role['name']=='Co-Editor')
                    {if(count($ref['authors'])>0 ) $ref['eds']='Eds'; else  $ref['eds']='Ed';}

            }


            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n25']!='') $ref['edition']= cleanText($cv_item['n25']);
            if($cv_item['n05']!='') $ref['vol']= cleanText($cv_item['n05']);
            if($cv_item['n22']!='') $ref['publisher']= cleanText($cv_item['n22']);
            if($cv_item['n27']!='') $ref['publoc']= cleanText($cv_item['n27']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n07']!=0) $ref['numpages']=$cv_item['n07'];
            if($cv_item['n04']!='0') {
                $sql="SELECT * FROM `cas_book_statuses` where `id`='$cv_item[n04]'";
                $journal=$db->getRow($sql);
                if($journal) $ref['status']= $journal['name'];
            }
            if($cv_item['n03']) $ref['refereed']=TRUE;
            if($cv_item['n02']!='0') {
                $sql="SELECT * FROM `cas_book_roles` where `id`='$cv_item[n04]'";
                $journal=$db->getRow($sql);
                if($journal) $ref['role']= $journal['name'];
            }


            switch($style){

                case 'apa':

                if(isset($ref['firstauthor'])) $result.=$ref['firstauthor']; 
                elseif($target=='screen') $result.="{$errhead}ROLE{$errtail}";
                if(isset($ref['authors'])) while($ref['authors'])  {
                    if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                    if(isetal($ref['authors'][0])) $connector=', ';
                    $result.=$connector . $ref['authors'][0];
                    array_shift($ref['authors']);
                }
                if(isset($ref['eds'])) $result.=" ($ref[eds].)";
                if(isset($ref['date'])) $result.=" ($ref[date])";
                if(isset($ref['title'])) $result.=". <i>$ref[title]</i>";
                if(isset($ref['edition'])) $result.=", $ref[edition]";
                if(isset($ref['vol'])) $result.="($ref[vol])";

                if(isset($ref['publoc'])) $result.=". $ref[publoc]:";
                if(isset($ref['publisher'])) {
                        if(substr($result,strlen($result)-1,1)!=':') $result.='.';
                        $result.=" $ref[publisher]";
                    }
                if(isset($ref['status'])) $result.=" (Status: $ref[status])";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                if($target!='list'){
                    if(isset($ref['refereed'])) if($ref['refereed']) $icons.="<img src='images/referee-flag-icon.png' alt='Refereed' title='Refereed'>";
                }

                break;
            }
         break;

         case 39:  ///////// Book Chapters ////////////////
            if($cv_item['n01']!='') $ref['chtitle']= cleanText($cv_item['n01']);
            if($cv_item['n07']!=0) $ref['from']= ($cv_item['n07']);
            if($cv_item['n08']!=0) $ref['to']= ($cv_item['n08']);
            if($cv_item['n30']!='') $ref['booktitle']= cleanText($cv_item['n30']);
            if($cv_item['n25']!='') $ref['edition']= cleanText($cv_item['n25']);
            if($cv_item['n05']!='') $ref['vol']= cleanText($cv_item['n05']);
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            if($cv_item['n22']!='') $ref['publisher']= cleanText($cv_item['n22']);
            if($cv_item['n27']!='') $ref['publoc']= cleanText($cv_item['n27']);
            if($cv_item['n09']!='0000-00-00') $ref['date']= formatDate($cv_item['n09']);
            if($cv_item['n03']) $ref['refereed']=true;
            if($cv_item['n13']!=0) {
                $sql="SELECT * FROM `cas_book_roles` WHERE `id`='$cv_item[n13]'";
                $status=$db->getRow($sql);
                if($status) $ref['role']=$status['name'];
            }
            //Grab the role name first to keep this section comprehensible
            $sql="SELECT * FROM cas_book_roles WHERE id='$cv_item[n02]'";
            $role=$db->getRow($sql);
            if($role){
               //If role is author, set up the list normally.
               if($role['name']=='First Listed Author' || $role['name']=='Co-Author'){
                   $ref=authorList($ref,$user,$cv_item,'n26','n16',$cv_item['n02'],$target);
                   //Eds list comes second, in normal (initials first) order
                   $ref=edsList($ref,$user,$cv_item,'n14','n15',$role['name']);
               }
               elseif($role['name']=='First Listed Editor' || $role['name']=='Co-Editor'){
                   //If there are no author fields filled out then put the eds first, but we need to flag that
                   if($cv_item['n26']=='' && coauthorList($cv_item,'n16')=='') {
                       //echo("nameslist n26=" . coauthorList($cv_item,'n16'));
                       $ref=authorList($ref,$user,$cv_item,'n14','n15',1,$target);
                       if(count($ref['authors'])>0 ) $ref['eds']='Eds'; else  $ref['eds']='Ed';
                   }
                   else{ //there are some author fields, so these go first and we deal with the editors later
                       $ref=authorList($ref,$user,$cv_item,'n26','n16',$cv_item['n02'],$target);

                       $ref=edsList($ref,$user,$cv_item,'n14','n15',$role['name']);
                   }
               }
            }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor']; 
                    elseif($target=='screen') $result.="{$errhead}ROLE{$errtail}";
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        if(isetal($ref['authors'][0])) $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['eds'])) $result.=" ($ref[eds].)";
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['chtitle'])) $result.=". $ref[chtitle]";


                    if(isset($ref['booktitle'])) $result.=". In";

                    if((isset($ref['firsteditor']) && $ref['firsteditor']!='') || count($ref['editors'])>0) {
                       // echo("Firsteitor: '". ($ref['firsteditor'])."'");
                        $result.=". (";
                        if(count($ref['editors'])>0) $end=", Eds.)"; else $end=", Ed.)";
                        if(isset($ref['firsteditor'])) $result.=$ref['firsteditor'];
                        if(isset($ref['editors'])) while($ref['editors'])  {
                            if(count($ref['editors'])==1) $connector=', & '; else $connector=', ';
                            if(isetal($ref['editors'][0])) $connector=', ';
                            $result.=$connector . $ref['editors'][0];
                            array_shift($ref['editors']);
                            }
                        $result.=$end;
                    }
                    if(isset($ref['booktitle'])) $result.=", <i>$ref[booktitle]</i>";
                    if(isset($ref['edition'])) $result.=", $ref[edition]";
                    if(isset($ref['vol'])) $result.="($ref[vol])";
                    if(isset($ref['from'])) {
                        $result.=" (pp.$ref[from]";
                        if(isset($ref['to'])) {
                            $result.="-$ref[to]";
                            }
                            $result.=')';
                        }


                    if(isset($ref['publoc'])) $result.=". $ref[publoc]:";
                    if(isset($ref['publisher'])) {
                        if(substr($result,strlen($result)-1,1)!=':') $result.='.';
                        $result.=" $ref[publisher]";
                    }
                    if(isset($ref['status'])) $result.=". (Status: $ref[status])";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if($target!='list'){
                        if(isset($ref['refereed'])) if($ref['refereed']) $icons.="<img src='images/referee-flag-icon.png' alt='Refereed' title='Refereed'>";
                    }
                break;
            }
         break;

         case 40:  ///////// Book Reviews ////////////////
            if($cv_item['n01']!='') $ref['rtitle']= cleanText($cv_item['n01']);
            if($cv_item['n07']!=0) $ref['from']= ($cv_item['n07']);
            if($cv_item['n08']!=0) $ref['to']= ($cv_item['n08']);
            if($cv_item['n30']!='') $ref['booktitle']= cleanText($cv_item['n30']);
            if($cv_item['n25']!='') $ref['bedition']= cleanText($cv_item['n25']);
            if($cv_item['n05']!='') $ref['volume']= cleanText($cv_item['n05']);
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            if($cv_item['n22']!='') $ref['publisher']= cleanText($cv_item['n22']);
            if($cv_item['n27']!='') $ref['journal']= cleanText($cv_item['n27']);
            if($cv_item['n09']!='0000-00-00') $ref['date']= formatDate($cv_item['n09'],true);
            if($cv_item['n03']) $ref['refereed']=true;
            if($cv_item['n26']!='') $ref['bvolume']= cleanText($cv_item['n26']);
            if($cv_item['n06']!=0) $ref['issue']= ($cv_item['n06']);
            if($cv_item['n23']) $ref['bookrefereed']=true;
            if($cv_item['n18']!='0000-00-00') $ref['bookdate']= formatDate($cv_item['n18']);
            if(coauthorList($cv_item,'n15')!='') $ref['authors']=coauthorList($cv_item,'n15');else {$ref['authors']='';unset ($ref['authors']);}

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="($ref[date])"; 
                    elseif($target=='screen') $result.="{$errhead}DATE{$errtail}";
                    if(isset($ref['rtitle'])) $result.=". $ref[rtitle]";
                    if(isset($ref['booktitle'])) $result.=". [Review of the book <i>$ref[booktitle]</i>";
                    if(isset($ref['bvolume'])) $result.=", Vol. $ref[bvolume]";
                    if(isset($ref['bedition'])) $result.=", Edition $ref[bedition]";
                    if(isset($ref['bookdate'])) $result.=" ($ref[bookdate])";
                    if(isset($ref['authors'])) $result.=", by $ref[authors]";
                    if(isset($ref['booktitle'])) $result.=']';
                    if(isset($ref['journal'])) $result.= ' <i>' . $ref['journal'] . '</i>';
                if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                if(isset($ref['from'])) {
                    $result.=", $ref[from]";
                    if(isset($ref['to'])) {
                        $result.="-$ref[to]";
                    }
                }
                if(isset($ref['status'])) $result.=" ($ref[status])";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                if($target!='list'){
                    if(isset($ref['refereed'])) if($ref['refereed']) $icons.="<img src='images/referee-flag-icon.png' alt='Review Refereed' title='Review Refereed'>";

                }
                break;
            }
         break;

         case 41:  ///////// Translations //////////////// --- REMOVED FOR NOW

            switch($style){
                case 'apa':

                break;
            }
         break;

         case 42:  ///////// Dissertations ////////////////
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n20']!=0) {
                $sql="SELECT * FROM `cas_institutions` WHERE `id`='$cv_item[n20]'";
                $status=$db->getRow($sql);
                if($status) $ref['institution']=$status['name'];
            }
            if($cv_item['n05']!='' && $cv_item['n05']!='|') $ref['supervisor']= formatName($cv_item['n05']);
            if($cv_item['n09']!='0000-00-00') $ref['date']= formatDate($cv_item['n09'],true);
            if($cv_item['n02']!=0) {
                $sql="SELECT * FROM `cas_degree_types` WHERE `id`='$cv_item[n02]'";
                $status=$db->getRow($sql);
                if($status){
                    $ref['type']=$status['name'];
                    if($ref['type']=='Ph.D.' || $ref['type']=='PhD') $diss=true;
                }
            }
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="($ref[date])";
                    if(isset($ref['title'])) $result.=". <i>$ref[title]</i>";
                    if(isset($diss)) $result.=" (Doctoral Dissertation)";
                    if(isset($ref['institution'])) $result.=". $ref[institution]";
                    if(isset($ref['supervisor'])) $result.=". (Supervisor: $ref[supervisor])";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 43:  ///////// Supervised Student Pubs ////////////////
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n05']!='' && $cv_item['n05']!='|') $ref['name']= formatName($cv_item['n05'],false);
            if($cv_item['n02']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n02]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            if($cv_item['n09']!='0000-00-00') $ref['date']= formatDate($cv_item['n09'],true);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="($ref[date])";
                    if(isset($ref['title'])) $result.=". \"$ref[title]\"";
                    if(isset($ref['name'])) $result.=". Student: $ref[name]";
                    if(isset($ref['status'])) $result.=". (Status: $ref[status])";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 44:  ///////// Litigation ////////////////
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n05']!='' && $cv_item['n05']!='|') $ref['name']= formatName($cv_item['n05'],false);
            if($cv_item['n14']!='') $ref['court']= cleanText($cv_item['n14']);
            if($cv_item['n09']!='0000-00-00') $ref['date']= formatDate($cv_item['n09']);
            if($cv_item['n22']!='') $ref['issues']= cleanText($cv_item['n22']);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="($ref[date])";
                    if(isset($ref['title'])) $result.=". Case: $ref[title]";
                    if(isset($ref['court'])) $result.=", $ref[court]";
                    if(isset($ref['name'])) $result.=". Person Acted For: $ref[name]";
                    if(isset($ref['issues'])) $result.=". Key Issues: $ref[issues]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 45:  //////////////////// Conference Papers ////////////////////////////

            $ref=authorList($ref,$user,$cv_item,'n27','n16',$cv_item['n20'],$target);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            if($cv_item['n03']) $ref['published']=TRUE;
            if($cv_item['n05']!='') $ref['confname']= cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['confloc']= cleanText($cv_item['n14']);
            if($cv_item['n23']!='') $ref['refereed']=TRUE;

            $sql="SELECT * FROM `cas_sub_names` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='n15' ";
            $authors=$db->getAll($sql);
            if(count($authors) > 0){
                foreach($authors as $author){
                      $ref['editors'][]=cleanText($author['lastname']) . ', '. getInitials($author['firstname']);
                }
            }//count


            if($cv_item['n25']!='') $ref['publisher']= cleanText($cv_item['n25']);
            if($cv_item['n26']!='') $ref['publoc']= cleanText($cv_item['n26']);
            if($cv_item['n07']!=0) $ref['from']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['to']=$cv_item['n08'];
            if($cv_item['n20']> 3) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_journal_article_authorship_roles where `id`='$cv_item[n20]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }

            switch ($style) {
              case 'apa':
                if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                if(isset($ref['authors'])) while($ref['authors'])  {
                    if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                    $result.=$connector . $ref['authors'][0];
                    array_shift($ref['authors']);
                }
                if(isset($ref['date'])) $result.= ' ('. $ref['date'] .').';
                if(isset($ref['title'])) $result.= ' ' . $ref['title'] . '.'; 
                elseif($target=='screen') $result.="{$errhead}. TITLE{$errtail}";
                if(isset($ref['confname'])) {
                    if(isset($ref['published'])) $result.=' In ';
                    else $result.=' Presented at ';
                }
                if(isset($ref['editors'])) {
                    if(count($ref['editors']) > 0) {
                        if(count($ref['editors']>1)) $plural='s'; else $plural='';
                        $result.= $ref['editors'][0];
                        array_shift($ref['editors']);
                    }
                    while($ref['editors'])  {
                        if(count($ref['editors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['editors'][0];
                        array_shift($ref['editors']);
                    }
                    $result.=" (Ed$plural)";
                }
                if(isset($ref['confname'])) $result.= ' <i>' . $ref['confname'] . '</i>'; 
                elseif($target=='screen') $result.="{$errhead} CONFERENCE NAME{$errtail}";
                if(isset($ref['confloc'])) $result.= ' (' . $ref['confloc'] . ')';
                if(isset($ref['from'])) {
                    $result.=", p. $ref[from]";
                    if(isset($ref['to'])) {
                        $result.="-$ref[to]";
                    }
                }
                if(isset($ref['publisher'])) $result.= '. ' . $ref['publisher'] . '';
                if(isset($ref['publoc'])) $result.= ': ' . $ref['publoc'] . '.';
                if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                if(isset($ref['status'])) $result.=" ($ref[status])";
                if(isset($ref['role'])) if($ref['role']!='') $result.=". (Role: $ref[role])";
                $result.='.';
                if($target!='list'){
                    if(isset($ref['refereed'])) if($ref['refereed']) $icons.="<img src='images/referee-flag-icon.png' alt='Refereed' title='Refereed'>";
                    if(isset($ref['published'])) if($ref['published']) $icons.="<img src='images/book-icon.png' alt='Published' title='Published'>";
                }
              break;
          }
          break;

          case 46:  ///////// Conference Abstracts ////////////////
            $ref=authorList($ref,$user,$cv_item,'n25','n16',$cv_item['n13'],$target);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            if($cv_item['n05']!='') $ref['confname']= cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['confloc']= cleanText($cv_item['n14']);
            if($cv_item['n07']!=0) $ref['from']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['to']=$cv_item['n08'];
            if($cv_item['n13']> 3) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_journal_article_authorship_roles where `id`='$cv_item[n13]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }
            if($cv_item['n22']!='') $ref['volume']= cleanText($cv_item['n22']);
            if($cv_item['n06']!=0) $ref['issue']= ($cv_item['n06']);

            switch ($style) {
              case 'apa':
                if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                if(isset($ref['authors'])) while($ref['authors'])  {
                    if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                    $result.=$connector . $ref['authors'][0];
                    array_shift($ref['authors']);
                }
                if(isset($ref['date'])) $result.= ' ('. $ref['date'] .').';
                if(isset($ref['title'])) $result.= ' ' . $ref['title'] . '.'; 
                elseif($target=='screen') $result.="{$errhead} TITLE.{$errtail}";
                if(isset($ref['confname'])) {
                    if(isset($ref['published'])) $result.=' In ';
                    else $result.=' For ';
                }
                if(isset($ref['confname'])) $result.= ' <i>' . $ref['confname'] . '</i>'; 
                elseif($target=='screen') $result.="{$errhead} CONFERENCE NAME{$errtail}";
                if(isset($ref['confloc'])) $result.= ' (' . $ref['confloc'] . ')';


                if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                if(isset($ref['from'])) {
                    $result.=", p. $ref[from]";
                    if(isset($ref['to'])) {
                        $result.="-$ref[to]";
                    }
                }
                if(isset($ref['status'])) $result.=" ($ref[status])";
                if(isset($ref['role'])) if($ref['role']!='') $result.=". (Role: $ref[role])";
                $result.='.';

              break;
          }
         break;

         case 47:  ///////// Artistic Exhibitions ////////////////
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue')!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue');
            if(namesList($cv_item,'n15')!='') $ref['collabs']=namesList($cv_item,'n15');
            if($cv_item['n09']!='0000-00-00') $ref['date']=formatDate($cv_item['n09'],true);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="$ref[title]";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['collabs'])) $result.=". Collaborated with: $ref[collabs]";
                    if(isset($ref['date'])) $result.=". First Performance: $ref[date]";
                    $result.='.';
                break;
            }
         break;

         case 48:  ///////// Audio Recording ////////////////
            if($cv_item['n01']!='') $ref['ptitle']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['atitle']=cleanText($cv_item['n05']);
            if($cv_item['n25']!='') $ref['dist']=cleanText($cv_item['n25']);
            if(namesList($cv_item,'n15')!='') $ref['coperformers']=namesList($cv_item,'n15');
            if($cv_item['n14']!='' && $cv_item['n14']!='|') $ref['producer']=formatName($cv_item['n14'],false,true);
            if($cv_item['n09']!='0000-00-00') $ref['date']=formatDate($cv_item['n09'],true,true);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['ptitle']) && isset($ref['atitle'])) $result.="\"$ref[ptitle]\", in <i>$ref[atitle]</i>";
                    elseif(isset($ref['ptitle'])) $result.="Piece: \"$ref[ptitle]\"";
                    else $result.="<i>$ref[atitle]</i>";
                    if(isset($ref['coperformers'])) $result.=". Performed with $ref[coperformers]";
                    if(isset($ref['producer'])) $result.=". Produced by $ref[producer]";
                    if(isset($ref['dist'])) $result.=". Distributed by $ref[dist]";
                    if(isset($ref['date']))$result.=". ($ref[date])";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 49:  ///////// Exhibition Catalogues ////////////////
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n25']!='') $ref['pub']= cleanText($cv_item['n25']);
            if($cv_item['n06']!=0) $ref['pages']= ($cv_item['n06']);
            if(namesList($cv_item,'n15')!='') $ref['artists']=namesList($cv_item,'n15');
            if($cv_item['n09']!='0000-00-00') $ref['date']=formatDate($cv_item['n09']);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="$ref[title]";
                    if(isset($ref['date'])) $result.=". ($ref[date])";
                    if(isset($ref['pub'])) $result.=". With: $ref[pub]";
                    if(isset($ref['artists'])) $result.=". Artists: $ref[artists]";
                    if(isset($ref['pages'])) $result.=". ($ref[pages] pages)";
                    $result.='.';
                break;
            }
         break;

         case 50:  ///////// Musical Compositions ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['tags']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['pub']=cleanText($cv_item['n14']);
            if($cv_item['n06']!=0) $ref['pages']=($cv_item['n06']);
            if($cv_item['n07']!=0) $ref['duration']=($cv_item['n07']);
            if(coauthorList($cv_item,'n15')!='') $ref['cocomposers']=coauthorList($cv_item,'n15');
            switch($style){

                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="$ref[title]";
                    if(isset($ref['duration'])) $result.=". $ref[duration] min.";
                    if(isset($ref['pages'])) $result.=". $ref[pages] pp";
                    if(isset($ref['pub'])) $result.=". Publisher: $ref[pub]";
                    if(isset($ref['cocomposers'])) $result.=". Co-composers: $ref[cocomposers]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 51:  ///////// Musical Performances ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true);
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true)!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true);

            if($cv_item['n02']!=0) {
                $sql="SELECT * FROM cas_musical_performance_roles where `id`='$cv_item[n02]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }
            if(namesList($cv_item,'n15')!='') $ref['collabs']=namesList($cv_item,'n15',false,'',true);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="$ref[title]";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['collabs'])) $result.=". Collaborated with: $ref[collabs]";
                    if(isset($ref['date'])) $result.=". First Performance: $ref[date]";
                    if(isset($ref['role'])) $result.=". (Role: $ref[role])";
                    $result.='.';
                break;
            }
         break;

         case 52:  ///////// Radio/TV Programs ////////////////
            if($cv_item['n01']!='') $ref['ptitle']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['etitle']=cleanText($cv_item['n05']);
            if($cv_item['n06']!=0) $ref['episodes']=($cv_item['n06']);
            if($cv_item['n14']!='') $ref['stitle']=cleanText($cv_item['n14']);
            if($cv_item['n25']!='') $ref['pub']=cleanText($cv_item['n25']);
            if(namesList($cv_item,'n15')!='') $ref['cocreators']=namesList($cv_item,'n15');
            if(broadcastList($cv_item['cv_item_id'],'n16','Broadcast',true)!='') $ref['broadcasts']=broadcastList($cv_item['cv_item_id'],'n16','Broadcast',true);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['ptitle'])) $result.="\"$ref[ptitle]\"";
                    if(isset($ref['stitle'])) $result.=". <i>$ref[stitle]</i>";
                    if(isset($ref['episodes'])) $result.=". $ref[episodes] episodes";
                    if(isset($ref['etitle'])) $result.=". Episode: \"$ref[etitle]\"";
                    if(isset($ref['broadcasts'])) $result.=". $ref[broadcasts]";
                    if(isset($ref['cocreators'])) $result.=". Co-creators: $ref[cocreators]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 53:  ///////// Scripts ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if(namesList($cv_item,'n15')!='') $ref['coauthors']=namesList($cv_item,'n15');
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['coauthors'])) $result.=". With $ref[coauthors]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 54:  ///////// Short Fiction ////////////////
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);


            if($cv_item['n14']!='') $ref['in']= cleanText($cv_item['n14']);
            if($cv_item['n05']!='') $ref['volume']= cleanText($cv_item['n05']);
            if($cv_item['n25']!='') $ref['issue']= cleanText($cv_item['n25']);
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            if($cv_item['n22']!='') $ref['publisher']= cleanText($cv_item['n22']);
            if($cv_item['n27']!='') $ref['publoc']= cleanText($cv_item['n27']);
            if($cv_item['n09']!='0000-00-00') $ref['date']= formatDate($cv_item['n09'],true);
            if(namesList($cv_item,'n15')!='') $ref['coauthors']=namesList($cv_item,'n15');
            if(namesList($cv_item,'n17')!='') $ref['editors']=namesList($cv_item,'n17',false,'Ed');
            if(pagesList('n16')!='') $ref['pages']=pagesList('n16');

            switch($style){
                case 'apa':
                    $result='';

                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['in'])) $result.=". In <i>$ref[in]</i>";
                    if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                    elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                    elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                    if(isset($ref['pages'])) $result.=" $ref[pages]";
                    if(isset($ref['editors'])) $result.=" ($ref[editors])";

                    if(isset($ref['publoc'])) $result.=". $ref[publoc]:";
                    if(isset($ref['publisher'])) {
                        if(substr($result,strlen($result)-1,1)!=':') $result.='.';
                        $result.=" $ref[publisher]";
                    }
                    if(isset($ref['status'])) $result.=". (Status: $ref[status])";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                    if($target!='list'){
                        if(isset($ref['refereed'])) if($ref['refereed']) $icons.="<img src='images/referee-flag-icon.png' alt='Refereed' title='Refereed'>";
                    }
                break;
            }
         break;

         case 55:  ///////// Theatre Performances ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true)!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true);
            if($cv_item['n25']!='') $ref['producer']=cleanText($cv_item['n25']);
            if($cv_item['n02']!=0) {
                $sql="SELECT * FROM cas_musical_performance_roles where `id`='$cv_item[n02]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }
            if(namesList($cv_item,'n15')!='') $ref['collabs']=namesList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['producer'])) $result.=". Produced by $ref[producer]";
                    if(isset($ref['collabs'])) $result.=". Collaborated with: $ref[collabs]";
                    if(isset($ref['date'])) $result.=". First Performance: $ref[date]";
                    //if(isset($ref['role'])) $result.=". (Role: $ref[role])";
                    $result.='.';
                break;
            }
         break;

         case 56:  ///////// Video Recording ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['stitle']=cleanText($cv_item['n05']);

            if($cv_item['n14']!='' && $cv_item['n14']!='|') $ref['director']=formatName($cv_item['n14']);
            if($cv_item['n25']!='' && $cv_item['n25']!='|') $ref['producer']=formatName($cv_item['n25']);
            if(namesList($cv_item,'n15')!='') $ref['coperf']=namesList($cv_item,'n15');
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['stitle'])) $result.=". <i>$ref[stitle]</i>";
                    if(isset($ref['director'])) $result.=". Directed by $ref[director]";
                    if(isset($ref['producer']))$result.=". Produced by $ref[producer]";
                    if(isset($ref['coperf']))$result.=". Co-performers: $ref[coperf]";
                    if(isset($ref['date'])) $result.=". Released: $ref[date]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 57:  ///////// Visual Artworks ////////////////

             if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if(namesList($cv_item,'n15')!='') $ref['coauthors']=namesList($cv_item,'n15');
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['coauthors'])) $result.=". With $ref[coauthors]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';
                break;
            }
         break;

         case 58:  ///////// Sound Design ////////////////

            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true)!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true);
            if($cv_item['n14']!='' && $cv_item['n14']!='|') $ref['producer']=formatName($cv_item['n14']);
            if(namesList($cv_item,'n15')!='') $ref['collabs']=namesList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['producer'])) $result.=". Written/Produced by $ref[producer]";
                    if(isset($ref['collabs'])) $result.=". Collaborated with: $ref[collabs]";
                    if(isset($ref['date'])) $result.=". Opening Date: $ref[date]";
                    $result.='.';
                break;
            }
         break;

         case 59:  ///////// Light Design ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true)!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true);
            if($cv_item['n14']!='' && $cv_item['n14']!='|') $ref['producer']=formatName($cv_item['n14']);
            if(namesList($cv_item,'n15')!='') $ref['collabs']=namesList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['producer'])) $result.=". Written/Produced by $ref[producer]";
                    if(isset($ref['collabs'])) $result.=". Collaborated with: $ref[collabs]";
                    if(isset($ref['date'])) $result.=". Opening Date: $ref[date]";
                    $result.='.';
                break;
            }
         break;

         case 60:  ///////// Choreography ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n14']!='' && $cv_item['n14']!='|') $ref['composer']=formatName($cv_item['n14']);
            if($cv_item['n05']!='') $ref['company']=cleanText($cv_item['n05']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);
            if(namesList($cv_item,'n15')!='') $ref['collabs']=namesList($cv_item,'n15');
            if(namesList($cv_item,'n28')!='') $ref['dancers']=namesList($cv_item,'n28');
            if(datesList('n16')!='') $ref['perfs']=datesList('n16');
            if(datesList('n17')!='') $ref['releases']=datesList('n17');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['company'])) $result.=". Danced by $ref[company]";
                    if(isset($ref['composer'])) $result.=". Composed by $ref[composer]";
                    if(isset($ref['date'])) $result.=". Premier: $ref[date]";
                    if(isset($ref['collabs'])) $result.=". Collaborated with: $ref[collabs]";
                    if(isset($ref['dancers'])) $result.=". Principals: $ref[dancers]";
                    if(isset($ref['perfs'])) $result.=". Performed: $ref[perfs]";
                    if(isset($ref['releases'])) $result.=". Media Releases: $ref[releases]";
                    $result.='.';
                break;
            }
         break;

         case 61:  ///////// Curatorial ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['ctitle']=cleanText($cv_item['n05']);
            if(namesList($cv_item,'n15')!='') $ref['artists']=namesList($cv_item,'n15');
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true)!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true);
            if(datesList('n28')!='') $ref['dates']=datesList('n28');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['artists'])) $result.=". Artists: $ref[artists]";
                    if(isset($ref['dates'])) $result.=". Dates: $ref[dates]";
                    if(isset($ref['ctitle'])) $result.=". Catalogue Title: $ref[ctitle]";
                    $result.='.';

                break;
            }
         break;

         case 62:  ///////// Performance Art ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if(namesList($cv_item,'n15')!='') $ref['artists']=namesList($cv_item,'n15');
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true)!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true);
            if(datesList('n28')!='') $ref['dates']=datesList('n28');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['artists'])) $result.=". Collaborated with: $ref[artists]";
                    if(isset($ref['dates'])) $result.=". Dates: $ref[dates]";
                    $result.='.';

                break;
            }
         break;

         case 63:  ///////// Newspaper Articles ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',1,$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['paper']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['edition']=cleanText($cv_item['n14']);
            if($cv_item['n25']!='') $ref['city']=cleanText($cv_item['n25']);
            if(pagesList('n16')!='') $ref['pages']=pagesList('n16');
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);
            //if($cv_item['n26']!='') $ref['author']=formatName($cv_item['n26']);
            //if(coauthorList($cv_item,'n15')!='') $ref['coauthors']=coauthorList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['paper'])) $result.=". <i>$ref[paper]</i>";
                    if(isset($ref['edition'])) $result.=", $ref[edition] Ed";
                    if(isset($ref['city'])) $result.=" ($ref[city])";
                    if(isset($ref['pages'])) $result.=" pp. $ref[pages]";
                     $result.='.';
                break;
            }
         break;

         case 64:  ///////// Newsletter Articles ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',1,$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['newsletter']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['volume']=cleanText($cv_item['n14']);
            if($cv_item['n06']!=0) $ref['issue']=($cv_item['n06']);
            if($cv_item['n07']!=0) $ref['from']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['to']=$cv_item['n08'];
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true);
            //if($cv_item['n26']!='') $ref['author']=formatName($cv_item['n26']);
            //if(coauthorList($cv_item,'n15')!='') $ref['coauthors']=coauthorList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['newsletter'])) $result.=". <i>$ref[newsletter]</i>";
                    if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                    elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                    elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                    if(isset($ref['from'])) {
                        $result.=", p. $ref[from]";
                        if(isset($ref['to'])) {
                            $result.="-$ref[to]";
                        }
                    }
                     $result.='.';
                break;
            }
         break;

         case 65:  ///////// Encyclopedia Entries ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',$cv_item['n13'],$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['etitle']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['edition']=cleanText($cv_item['n14']);
            if($cv_item['n22']!='') $ref['volume']=cleanText($cv_item['n22']);
            if($cv_item['n25']!='') $ref['publisher']=cleanText($cv_item['n25']);
            if($cv_item['n06']!=0) $ref['num']=$cv_item['n06'];
            if($cv_item['n07']!=0) $ref['from']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['to']=$cv_item['n08'];
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true);
            if($cv_item['n13']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_simple_roles where `id`='$cv_item[n13]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['etitle'])) $result.=". <i>$ref[etitle]</i>";
                    if(isset($ref['edition'])) $result.=" <i> $ref[edition] Edition</i>";
                    if(isset($ref['volume'])) $result.= ", <i>$ref[volume]</i>";
                    if(isset($ref['num'])) $result.= " (of $ref[num])";

                    if(isset($ref['from'])) {
                        $result.=", p. $ref[from]";
                        if(isset($ref['to'])) {
                            $result.="-$ref[to]";
                        }
                    }
                    if(isset($ref['publisher'])) $result.=". $ref[publisher]";
                    if(isset($ref['role'])) if($ref['role']!='') $result.=". (Role: $ref[role])";
                    if(isset($ref['status'])) if($ref['status']!='') $result.=". (Status: $ref[status])";
                     $result.='.';
                break;
            }
         break;

         case 66:  ///////// Magazine Articles ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',$cv_item['n13'],$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['mag']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['volume']=cleanText($cv_item['n14']);
            if($cv_item['n06']!=0) $ref['issue']=($cv_item['n06']);
            if($cv_item['n07']!=0) $ref['from']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['to']=$cv_item['n08'];
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true);
            if($cv_item['n13']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_simple_roles where `id`='$cv_item[n13]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['mag'])) $result.=". <i>$ref[mag]</i>";
                    if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                    elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                    elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                    if(isset($ref['from'])) {
                        $result.=", p. $ref[from]";
                        if(isset($ref['to'])) {
                            $result.="-$ref[to]";
                        }
                    }
                    if(isset($ref['role'])) if($ref['role']!='') $result.=". (Role: $ref[role])";
                     $result.='.';
                break;
            }
         break;

         //Need to fix the author/editor relationship here    /////////////////////////////////////////////////////////////
         // Play around with item 6343 for a test
         case 67:  ///////// Dictionary ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',$cv_item['n13'],$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['etitle']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['edition']=cleanText($cv_item['n14']);
            if($cv_item['n22']!='') $ref['volume']=cleanText($cv_item['n22']);
            if($cv_item['n25']!='') $ref['publisher']=cleanText($cv_item['n25']);
            if($cv_item['n06']!=0) $ref['num']=$cv_item['n06'];
            if($cv_item['n07']!=0) $ref['from']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['to']=$cv_item['n08'];
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);

            if($cv_item['n13']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_simple_roles where `id`='$cv_item[n13]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". \"$ref[title]\"";
                    if(isset($ref['etitle'])) $result.=". In <i>$ref[etitle]</i>";
                    if(isset($ref['edition'])) $result.=" <i> $ref[edition] Edition</i>";
                    if(isset($ref['volume'])) $result.= ", <i>$ref[volume]</i>";
                    if(isset($ref['num'])) $result.= " (of $ref[num])";

                    if(isset($ref['from'])) {
                        $result.=", p. $ref[from]";
                        if(isset($ref['to'])) {
                            $result.="-$ref[to]";
                        }
                    }
                    if(isset($ref['publisher'])) $result.=". $ref[publisher]";
                    if(isset($ref['role'])) if($ref['role']!='') $result.=". (Role: $ref[role])";
                    if(isset($ref['status'])) if($ref['status']!='') $result.=". (Status: $ref[status])";
                     $result.='.';
                break;
            }
         break;

         case 68:  ///////// Reports ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',$cv_item['n13'],$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);

            if($cv_item['n06']!=0) $ref['num']=$cv_item['n06'];
            if($cv_item['n07']!=0) $ref['from']=$cv_item['n07'];
            if($cv_item['n08']!=0) $ref['to']=$cv_item['n08'];
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n13']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_simple_roles where `id`='$cv_item[n13]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }
            if($cv_item['n02']!=0) {
                $sql="SELECT * FROM `cas_institutions` WHERE `id`='$cv_item[n02]'";
                $status=$db->getRow($sql);
                if($status) $ref['institution']=$status['name'];
            }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['institution'])) $result.=". For $ref[institution]";
                    if(isset($ref['num'])) $result.= ". $ref[num] pp";

                    if(isset($ref['role'])) if($ref['role']!='') $result.=". (Role: $ref[role])";
                    if(isset($ref['status'])) if($ref['status']!='') $result.=". (Status: $ref[status])";
                     $result.='.';
                break;
            }
         break;

         case 69:  ///////// Working Papers ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',1,$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    //if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    $result.='.';
                break;
            }
         break;

         case 70:  ///////// Research Tools ////////////////
             if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
             if($cv_item['n05']!='') $ref['type']=cleanText($cv_item['n05']);
             if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
             if($cv_item['n02']!=0) {
                $sql="SELECT * FROM `cas_institutions` WHERE `id`='$cv_item[n02]'";
                $status=$db->getRow($sql);
                if($status) $ref['institution']=$status['name'];
            }
            if(pagesList('n16')!='') $ref['pages']=pagesList('n16');
            switch($style){
                case 'apa':
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['type'])) $result.=". Tool Type: $ref[type]";
                    if(isset($ref['institution'])) $result.=". $ref[institution]";
                    if(isset($ref['pages'])) $result.=". Pages $ref[pages]";
                break;
            }
         break;

         case 71:  ///////// Manuals ////////////////

            $ref=authorList($ref,$user,$cv_item,'n26','n15',1,$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['stitle']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['edition']=cleanText($cv_item['n14']);
            if($cv_item['n22']!='') $ref['volume']=cleanText($cv_item['n22']);
            if($cv_item['n25']!='') $ref['publisher']=cleanText($cv_item['n25']);
            if($cv_item['n27']!='') $ref['publoc']=cleanText($cv_item['n27']);
            if($cv_item['n06']!=0) $ref['numvol']=($cv_item['n06']);
            if($cv_item['n07']!=0) $ref['pages']=$cv_item['n07'];

            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['stitle'])) $result.=". <i>$ref[stitle]</i>";
                    if(isset($ref['edition']))  $result.= "<i>, $ref[edition]</i>";
                    if(isset($ref['volume']))  $result.= ", Vol $ref[volume]";
                    if(isset($ref['numvol']))  $result.= " of $ref[numvol]";
                    if(isset($ref['pages']))  $result.= ", $ref[pages] pp.";
                    if(isset($ref['publoc'])) $result.=". $ref[publoc]:";
                    if(isset($ref['publisher'])) {
                        if(substr($result,strlen($result)-1,1)!=':') $result.='.';
                        $result.=" $ref[publisher]";
                    }
                     if(isset($ref['status'])) if($ref['status']!='') $result.=". (Status: $ref[status])";
                     $result.='.';
                break;
            }
         break;

         case 72:  ///////// Online Resources ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',$cv_item['n13'],$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['url']=($cv_item['n05']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['url']) && $target=='list') $result.=". <a href='$ref[url]'>$ref[url]</a>";
                    elseif(isset($ref['url'])) $result.=". $ref[url]";
                    $result.='.';
                break;
            }
         break;

         case 73:  ///////// Tests ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',$cv_item['n13'],$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['date'])) $result.=" (First Used: $ref[date])";
                    $result.='.';
                break;
            }
         break;

         case 74:  ///////// Patents ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',1,$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['num']=cleanText($cv_item['n05']);
            if($cv_item['n04']!=0) {
                $sql="SELECT * FROM `cas_publication_statuses` WHERE `id`='$cv_item[n04]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            if($cv_item['n02']!=0) {
                $sql="SELECT * FROM `cas_countries` WHERE `id`='$cv_item[n02]'";
                $status=$db->getRow($sql);
                if($status) $ref['country']=$status['name'];
            }
            if($cv_item['n09']!='0000-00-00') $ref['dfiling'] = formatDate($cv_item['n09'],true,true);
            if($cv_item['n18']!='0000-00-00') $ref['dissued'] = formatDate($cv_item['n18'],true,true);
            if($cv_item['n19']!='0000-00-00') $ref['dend'] = formatDate($cv_item['n19'],true,true);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['dissued'])) {
                        if(!isset($ref['dend'])) $result.=" ($ref[dissued])";
                        else $result.=" ($ref[dissued] - $ref[dend])";
                    }
                    elseif(isset($ref['dfiling'])) $result.=" (Filed: $ref[dfiling])";
                    if(isset($ref['title'])) $result.=". <i>$ref[title]</i>";
                    if(isset($ref['country'])) $result.=". $ref[country]";
                    if(isset($ref['num'])) $result.=" # $ref[num]";
                    if(isset($ref['status'])) $result.=". (Status: $ref[status])";
                    $result.='.';
                break;
            }
         break;

         case 75:  ///////// Licenses ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['dfiling'] = formatDate($cv_item['n09'],true,true);
            if($cv_item['n18']!='0000-00-00') $ref['dissued'] = formatDate($cv_item['n18'],true,true);
            if($cv_item['n19']!='0000-00-00') $ref['dend'] = formatDate($cv_item['n19'],true,true);
            if($cv_item['n02']!=0) {
                $sql="SELECT * FROM `cas_license_statuses` WHERE `id`='$cv_item[n02]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            switch($style){
                case 'apa':
                    if(isset($ref['dissued'])) {
                        if(!isset($ref['dend'])) $result.=" ($ref[dissued])";
                        else $result.=" ($ref[dissued] - $ref[dend])";
                    }
                    elseif(isset($ref['dfiling'])) $result.=" (Filed: $ref[dfiling])";
                    if(isset($ref['title'])) $result.=". <i>$ref[title]</i>";
                    if(isset($ref['status'])) $result.=". (Status: $ref[status])";
                break;
            }
         break;

         case 76:  ///////// Disclosures ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['dfiling'] = formatDate($cv_item['n09'],true,true);
            if($cv_item['n18']!='0000-00-00') $ref['dissued'] = formatDate($cv_item['n18'],true,true);
            if($cv_item['n19']!='0000-00-00') $ref['dend'] = formatDate($cv_item['n19'],true,true);
            if($cv_item['n02']!=0) {
                $sql="SELECT * FROM `cas_disclosure_statuses` WHERE `id`='$cv_item[n02]'";
                $status=$db->getRow($sql);
                if($status) $ref['status']=$status['name'];
            }
            switch($style){
                case 'apa':
                    if(isset($ref['dissued'])) {
                        if(!isset($ref['dend'])) $result.=" ($ref[dissued])";
                        else $result.=" ($ref[dissued] - $ref[dend])";
                    }
                    elseif(isset($ref['dfiling'])) $result.=" (Filed: $ref[dfiling])";
                    if(isset($ref['title'])) $result.=". <i>$ref[title]</i>";
                    if(isset($ref['status'])) $result.=". (Status: $ref[status])";
                break;
            }
         break;

         case 77:  ///////// Registered Copyrights ////////////////

            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['dfiling'] = formatDate($cv_item['n09'],true,true);
            if($cv_item['n18']!='0000-00-00') $ref['dissued'] = formatDate($cv_item['n18'],true,true);
            if($cv_item['n19']!='0000-00-00') $ref['dend'] = formatDate($cv_item['n19'],true,true);
            if($cv_item['n05']!='') $ref['status']=cleanText($cv_item['n05']);
            switch($style){
                case 'apa':
                    if(isset($ref['dissued'])) {
                        if(!isset($ref['dend'])) $result.=" ($ref[dissued])";
                        else $result.=" ($ref[dissued] - $ref[dend])";
                    }
                    elseif(isset($ref['dfiling'])) $result.=" (Filed: $ref[dfiling])";
                    if(isset($ref['title'])) $result.=". <i>$ref[title]</i>";
                    if(isset($ref['status'])) $result.=". (Status: $ref[status])";
                break;
            }
         break;

         case 78:  ///////// Trademarks ////////////////

            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['dfiling'] = formatDate($cv_item['n09'],true,true);
            if($cv_item['n18']!='0000-00-00') $ref['dissued'] = formatDate($cv_item['n18'],true,true);
            if($cv_item['n19']!='0000-00-00') $ref['dend'] = formatDate($cv_item['n19'],true,true);
            if($cv_item['n05']!='') $ref['status']=cleanText($cv_item['n05']);
            switch($style){
                case 'apa':
                    if(isset($ref['dissued'])) {
                        if(!isset($ref['dend'])) $result.=" ($ref[dissued])";
                        else $result.=" ($ref[dissued] - $ref[dend])";
                    }
                    elseif(isset($ref['dfiling'])) $result.=" (Filed: $ref[dfiling])";
                    if(isset($ref['title'])) $result.=". <i>$ref[title]</i>";
                    if(isset($ref['status'])) $result.=". (Status: $ref[status])";
                break;
            }
         break;

         case 79:  ///////// Posters ////////////////

            $ref=authorList($ref,$user,$cv_item,'n25','n16',$cv_item['n13'],$target);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true);
            if($cv_item['n01']!='') $ref['title']= cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['confname']= cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['confloc']= cleanText($cv_item['n14']);
            if($cv_item['n13']> 3) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_journal_article_authorship_roles where `id`='$cv_item[n13]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }

            switch ($style) {
              case 'apa':
                if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                if(isset($ref['authors'])) while($ref['authors'])  {
                    if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                    $result.=$connector . $ref['authors'][0];
                    array_shift($ref['authors']);
                }
                if(isset($ref['date'])) $result.= ' ('. $ref['date'] .').';
                if(isset($ref['title'])) $result.= ' <i>' . $ref['title'] . '</i>.';
                elseif($target=='screen') $result.="{$errhead} TITLE{$errtail}";
                if(isset($ref['confname'])) $result.= " Presented at $ref[confname]"; 
                elseif($target=='screen') $result.="{$errhead} CONFERENCE NAME{$errtail}";
                if(isset($ref['confloc'])) $result.= ' (' . $ref['confloc'] . ')';


                if(isset($ref['volume']) && isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>($ref[issue])";
                elseif(isset($ref['volume']) && !isset($ref['issue'])) $result.= ", <i>$ref[volume]</i>";
                elseif(isset($ref['issue'])) $result.= ", ($ref[issue])";
                                if(isset($ref['from'])) {
                    $result.=", p. $ref[from]";
                    if(isset($ref['to'])) {
                        $result.="-$ref[to]";
                    }
                }
                if(isset($ref['status'])) $result.=" ($ref[status])";
                if(isset($ref['role'])) if($ref['role']!='') $result.=". (Role: $ref[role])";
                $result.='.';

              break;
          }
         break;

         case 80:  ///////// Set Design ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true)!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true);
            if($cv_item['n14']!='' && $cv_item['n14']!='|') $ref['producer']=formatName($cv_item['n14']);
            if(namesList($cv_item,'n15')!='') $ref['collabs']=namesList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['producer'])) $result.=". Written/Produced by: $ref[producer]";
                    if(isset($ref['collabs'])) $result.=". Collaborated with: $ref[collabs]";
                    if(isset($ref['date'])) $result.=". Opening Date: $ref[date]";
                    $result.='.';
                break;
            }
         break;

         case 81:  ///////// Other Communications ////////////////
            $ref=authorList($ref,$user,$cv_item,'n26','n15',$cv_item['n13'],$target);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['type']=cleanText($cv_item['n05']);
            if($cv_item['n25']!='') $ref['other']=cleanText($cv_item['n25']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            if($cv_item['n13']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_simple_roles where `id`='$cv_item[n13]'";
                $role=$db->getRow($sql);
                if($role) $ref['role']=$role['name'];
            }


            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['firstauthor'])) $result.=$ref['firstauthor'];
                    if(isset($ref['authors'])) while($ref['authors'])  {
                        if(count($ref['authors'])==1) $connector=', & '; else $connector=', ';
                        $result.=$connector . $ref['authors'][0];
                        array_shift($ref['authors']);
                    }
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['type'])) $result.=". ($ref[type])";
                    if(isset($ref['other'])) $result.=". $ref[other]";
                    if(isset($ref['role'])) if($ref['role']!='') $result.=". (Role: $ref[role])";
                     $result.='.';
                break;
            }
         break;

         //////////////// New Stuff not in CASRAI Standard



         case 82: /// ////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['body']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['type']=cleanText($cv_item['n14']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09']);
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['type'])) $result.=" ($ref[type])";
                    if(isset($ref['body'])) $result.=". Certified by <i>$ref[body]</i>";


                    $result.='.';
                break;
            }
         break;

         case 83: /// Coordination////
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);

            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            if($cv_item['n02']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_institutions where `id`='$cv_item[n02]'";
                $role=$db->getRow($sql);
                if($role) $ref['institution']=$role['name'];
            }
            if($cv_item['n04']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_institution_departments where `id`='$cv_item[n04]'";
                $role=$db->getRow($sql);
                if($role) $ref['dept']=$role['name'];
            }
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.=" ($ref[dates])";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";
                    if(isset($ref['institution'])) $result.=". $ref[institution]";
                    if(isset($ref['dept'])) $result.=", $ref[dept]";
                    $result.='.';
                break;
            }
         break;

          case 84:  ///////// research presentations ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['event']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['loc']=cleanText($cv_item['n14']);
            if($cv_item['n22']!='') $ref['org']=cleanText($cv_item['n22']);
            if($cv_item['n03']) $ref['invited']=true;
            if($cv_item['n23']) $ref['keynote']=true;
            if($cv_item['n24']) $ref['competitive']=true;
            if($cv_item['n09']!='0000-00-00') $ref['date']=formatDate($cv_item['n09'],true);
             if(namesList($cv_item,'n15',false)!='') $ref['others']=namesList($cv_item,'n15',false,'',false,'Co-presenter');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.="$ref[date]";
                    elseif($target=='screen') $result.="{$errhead}DATE{$errtail}";
                    if(isset($ref['title'])) $result.=". \"$ref[title]\"";
                    if(isset($ref['org'])) $result.=". Presented to $ref[org]";
                    if(isset($ref['event'])) $result.=". Presented at $ref[event]";
                    if(isset($ref['loc'])) $result.=", $ref[loc]";
                    if(isset($ref['invited'])) $result.=". Invited Speaker";
                    if(isset($ref['keynote'])) $result.=". Keynote Speaker";
                    if(isset($ref['others'])) $result.=". $ref[others]";
                    if(substr($result,strlen($result)-1,1)!='.') $result.='.';


                break;
            }
         break;

         case 85: /// Teaching in progress and other////
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);

            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            if($cv_item['n02']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_institutions where `id`='$cv_item[n02]'";
                $role=$db->getRow($sql);
                if($role) $ref['institution']=$role['name'];
            }
            if($cv_item['n04']> 1) {   //Not an author or co-author, so list the role
                $sql="SELECT * FROM cas_institution_departments where `id`='$cv_item[n04]'";
                $role=$db->getRow($sql);
                if($role) $ref['dept']=$role['name'];
            }
            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.=" ($ref[dates])";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";
                    if(isset($ref['institution'])) $result.=". $ref[institution]";
                    if(isset($ref['dept'])) $result.=", $ref[dept]";
                    $result.='.';
                break;
            }
         break;

         case 86: /// Other Service////
            if($cv_item['n01']!='') $ref['agency']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['desc']=cleanText($cv_item['n05']);

            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);


            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.=" ($ref[dates])";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";
                    if(isset($ref['agency'])) $result.=". $ref[agency]";

                    $result.='.';
                break;
            }
         break;

         case 87: /// Clinical ////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['unit']=cleanText($cv_item['n05']);
            if($cv_item['n14']!='') $ref['where']=cleanText($cv_item['n14']);

            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);


            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.=" ($ref[dates])";
                    if(isset($ref['title'])) $result.=". $ref[title]";
                    if(isset($ref['unit'])) $result.=", $ref[unit]";
                    if(isset($ref['where'])) $result.=", $ref[where]";
                    $result.='.';
                break;
            }
         break;

         case 88: /// Professional CUrrency////
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);


            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);


            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.=" ($ref[dates])";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";


                    $result.='.';
                break;
            }
         break;

         case 89: /// Other Media ////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n14']!='') $ref['type']=cleanText($cv_item['n14']);
            if($cv_item['n05']!='') $ref['role']=cleanText($cv_item['n05']);


            if($cv_item['n09']!='0000-00-00') $ref['date']=formatDate($cv_item['n09']);


            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['date'])) $result.=" ($ref[date])";
                    if(isset($ref['type'])) $result.=" $ref[type]:";
                    if(isset($ref['title'])) $result.=" \"$ref[title]\"";
                    if(isset($ref['role'])) $result.=". $ref[role]";

                    $result.='.';
                break;
            }
         break;

         case 90: /// Other Professional Act////
            if($cv_item['n01']!='') $ref['desc']=cleanText($cv_item['n01']);


            if($cv_item['n09']!='0000-00-00' || $cv_item['n18']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);


            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['dates'])) $result.=" ($ref[dates])";
                    if(isset($ref['desc'])) $result.=". $ref[desc]";
                    $result.='.';
                break;
            }
         break;

         case 91:  ///////// Projects in Progress ////////////////
            if(namesList($cv_item,'n16')!='') $ref['others']=namesList($cv_item,'n16');

            if($cv_item['n18']!='0000-00-00' || $cv_item['n19']!='0000-00-00') $ref['dates']=startendDate($cv_item['n18'],$cv_item['n19'],false,$target);
            

            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['desc']=cleanText($cv_item['n05']);
            if($cv_item['n02']!=0){
                $sql="SELECT * FROM cas_funding_organizations WHERE `id`='$cv_item[n02]'";
                $result=$db->getRow($sql);
                if($result) $ref['org']=cleanText($result['name']);
             }
             if($cv_item['n04']!=0){
                $sql="SELECT * FROM cas_funding_statuses WHERE `id`='$cv_item[n04]'";
                $result=$db->getRow($sql);
                if($result) if($result['name']=='Applied') $ref['status']='Applied For';
             }
             if($cv_item['n06']!='0') $ref['amount']=formatMoney($cv_item['n06'], $cv_item['n13']);
            //if(!$cv_item['n03']) $ref['competitive']='Non-Competitive'; else $ref['competitive']='Competitive';
            if($cv_item['n21']!=0){
                $sql="SELECT * FROM cas_investigator_roles WHERE `id`='$cv_item[n21]'";
                $result=$db->getRow($sql);
                if($result)  $ref['role']=cleanText($result['name']);
             }

            switch($style){
                case 'apa':
                $result='';
                if(isset($ref['dates'])) $result.="$ref[dates]";
                elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                if(isset($ref['status'])) $result.=" ($ref[status])";
                if(isset($ref['org'])) $result.=" $ref[org]";
                if(isset($ref['title'])) $result.=", \"$ref[title]\"";
                if(isset($ref['desc'])) $result.=". $ref[desc]";
                if(isset($ref['amount'])) $result.=". $ref[amount]";
                if(isset($ref['competitive'])) $result.=". Type: $ref[competitive]";
                if(isset($ref['role'])) $result.=" (Role: $ref[role])";
                if(isset($ref['others'])) $result.=". Other Investigators: $ref[others]";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';

                break;
            }
         break;
         
         case 92: ///policy development
            if($cv_item['n09']) $ref['date']=formatDate($cv_item['n09']);
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['role']=cleanText($cv_item['n05']);
            
            switch($style){
                case 'apa':
                $result='';
                if(isset($ref['date'])) $result.="$ref[date]";
                elseif($target=='screen') $result.="{$errhead}DATE{$errtail}";
                if(isset($ref['title'])) $result.=". \"$ref[title]\"";
                if(isset($ref['role'])) $result.=". $ref[role]";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';
            }
            
         break;
         
         case 93: ///Mentorship
            if($cv_item['n18']!='0000-00-00' || $cv_item['n09']!='0000-00-00') $ref['dates']=startendDate($cv_item['n09'],$cv_item['n18'],false,$target);
            
            if($cv_item['n01']!='') $ref['descrip']=cleanText($cv_item['n01']);
            if($cv_item['n05']!='') $ref['person']= formatName($cv_item['n05'],false,true);
            if($cv_item['n03']==1) $ref['type']='Faculty';
            else if($cv_item['n23']==1) $ref['type']='Student';
            
            switch($style){
                case 'apa':
                $result='';
                if(isset($ref['dates'])) $result.="$ref[dates]";
                elseif($target=='screen') $result.="{$errhead}DATES{$errtail}";
                if(isset($ref['person'])) $result.=". $ref[person]";
                if(isset($ref['type'])) $result.=" (".$ref['type'].")";
                if(isset($ref['descrip'])) $result.=". $ref[descrip]";
                if(substr($result,strlen($result)-1,1)!='.') $result.='.';
            }
            
         break;
         
         case 94:  ///////// Costume Design ////////////////
            if($cv_item['n01']!='') $ref['title']=cleanText($cv_item['n01']);
            if($cv_item['n09']!='0000-00-00') $ref['date'] = formatDate($cv_item['n09'],true,true);
            if(buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true)!='') $ref['venues']=buildformatList($cv_item['cv_item_id'],'n16','cas_sub_venues','venue','Venue',true);
            if($cv_item['n14']!='' && $cv_item['n14']!='|') $ref['producer']=formatName($cv_item['n14']);
            if(namesList($cv_item,'n15')!='') $ref['collabs']=namesList($cv_item,'n15');

            switch($style){
                case 'apa':
                    $result='';
                    if(isset($ref['title'])) $result.="\"$ref[title]\"";
                    if(isset($ref['venues'])) $result.=". $ref[venues]";
                    if(isset($ref['producer'])) $result.=". Written/Produced by $ref[producer]";
                    if(isset($ref['collabs'])) $result.=". Collaborated with: $ref[collabs]";
                    if(isset($ref['date'])) $result.=". Opening Date: $ref[date]";
                    $result.='.';
                break;
            }
         break;
         
    }//switch type


    //if ( $target == 'list' ) $result = "<table ><tr><td>$result</td><td>$icons</td></tr></table>";
    
    return $result;
}

////////////////   Functions From ORSADMIN //////////////////////////
 /**
  * Formats start and end date string, with option for 'present'
  * 
  * Note: Modified 11-04-07 TDavis - Switch to 00=N/A, and ignore month and day flags.
  *
  * @param mixed $startdate Actual start date in std SQL format
  * @param mixed $enddate Actual end date in std SQL format
  * @param mixed $currentflag Flag for current (then it uses the term 'Present'
  * @param boolean $month Flag for printing month or not
  *  @param boolean $day Flag for printing day or not
  * @return mixed
  */
  function startendDate($startdate,$enddate,$currentflag=false,$target='report'){
      
      if($startdate!='0000-00-00') $start= unpackDate($startdate);
      if($enddate!='0000-00-00') $end=unpackDate($enddate);
      if(!isset($start) && !isset($end) ) 
      if( $target=='screen') return "<font color='red'>DATES</font>";
      else return "";
      
      if($startdate==$enddate) unset($end);
      if($currentflag) {$end['year']='Present';$end['month']=''; $end['day']=0;}
      if(isset($start)){
        if($start['month']=='') $fstart=$start['year'];
        elseif($start['day']==0) $fstart="$start[year], $start[month]";
        else $fstart="$start[year], $start[month] $start[day]";
      }
      else $fstart='';
      
      if(isset($end)){
        if($end['month']=='') $fend=$end['year'];
        elseif($end['day']==0) $fend="$end[year], $end[month]";
        else $fend="$end[year], $end[month] $end[day]";
      }
      else $fend='';
      $result=$fstart;
      if ($fend!='') $result.=' - '.$fend;
      return $result;
      
      
      
      /*
      if($month==false){
          if(!isset($start) && isset($end)) return "$end[year]";
          elseif(isset($start) && isset($end)) return "$start[year]-$end[year]";
          elseif(isset($start) && (!isset($end) || !$currentflag)) return "$start[year]";
          elseif(isset($start) && (!isset($end) || $currentflag)) return "$start[year]-Present";
      }
      elseif($day==false) {
          if(!isset($start) && isset($end)) return "$end[year], $end[month]";
          elseif(isset($start) && isset($end)) return "$start[year], $start[month] - $end[year], $end[month]";
          elseif(isset($start) && (!isset($end) || !$currentflag)) return "$start[year], $start[month]";
          elseif(isset($start) && (!isset($end) || $currentflag)) return "$start[year], $start[month]-Present";
      }
      else {
          if(!isset($start) && isset($end)) return "$end[year], $end[month] $end[mday]";
          elseif(isset($start) && isset($end)) return "$start[year], $start[month] $start[mday] - $end[year], $end[month] $end[mday]";
          elseif(isset($start) && (!isset($end) || !$currentflag)) return "$start[year], $start[month] $start[mday]";
          elseif(isset($start) && (!isset($end) || $currentflag)) return "$start[year], $start[month] $start[mday]-Present";
      }
      */

  }
  /**
  * Unpacks an SQL date string and returns an array with year/mon/day using empty string for n/a
  * 
  * @param mixed $datestring
  */
  function unpackDate($datestring,$shortmonth=false,$numdate=false){
      //Shouldn't send a zeroed one, but check just the same
      if($datestring=='0000-00-00') return false;
      $date=explode('-',$datestring);
      if(count($date)< 3) return false;
      
      $fdate=array();
      $fdate['year']=$date[0];
      if($shortmonth){
          switch (intval($date[1])) {
          case 0: $mon=''; break;
          case 1: $mon='Jan'; break;
          case 2: $mon='Feb'; break;
          case 3: $mon='Mar'; break;
          case 4: $mon='Apr'; break;
          case 5: $mon='May'; break;
          case 6: $mon='Jun'; break;
          case 7: $mon='Jul'; break;
          case 8: $mon='Aug'; break;
          case 9: $mon='Sep'; break;
          case 10: $mon='Oct'; break;
          case 11: $mon='Nov'; break;
          case 12: $mon='Dec'; break;
          }
      }
      elseif(!$numdate) {
          switch (intval($date[1])) {
          case 0: $mon=''; break;
          case 1: $mon='January'; break;
          case 2: $mon='February'; break;
          case 3: $mon='March'; break;
          case 4: $mon='April'; break;
          case 5: $mon='May'; break;
          case 6: $mon='June'; break;
          case 7: $mon='July'; break;
          case 8: $mon='August'; break;
          case 9: $mon='September'; break;
          case 10: $mon='October'; break;
          case 11: $mon='November'; break;
          case 12: $mon='December'; break;
          }
      }
      else $mon=$date[1];
      $fdate['month']=$mon;
      $fdate['day']= (intval($date[2])==0) ? 0 : intval($date[2]);
      
      return $fdate;
  }
  

  function formatDate($date,$month=false,$day=false){
      if($date!='0000-00-00') {
         /*
        $result=getdate(strtotime($date));
        if($month && ! $day)  return "$result[mon]/$result[year]";
        elseif($month && $day) return "$result[mday]/$result[mon]/$result[year]";
        else return "$result[year]";
        */
        $fdate=unpackDate($date);
        if(!$fdate) return '';
        if($fdate['year']=='0000') $fdate['year']='';
        if($fdate['month']=='') return ($fdate['year']);
        elseif($fdate['day']==0) return ($fdate['year']. ', '. $fdate['month']);
        else return ($fdate['year']. ', '. $fdate['month'].' '.$fdate['day']);
      }
      else return '';
  }

  function cleanText($text){
      $text=stripSpaces($text);
      $text=preg_replace('/\.$/','',$text);
      return $text;
  }

  function getInitials($firstname){
      //could receive 1) Robert 2) Robert Stanley 3) R. 4) R.A. 5) R 6) RA 7) RAJ etc.

      //lose leading/trailing spaces
      $firstname=stripSpaces($firstname);
      //split into words
      $names=explode(' ',$firstname);
      $init='';
      //loop in case there are two or three first names
      foreach($names as $name){
          //if its a proper name just grab initial
          if(preg_match('/^([A-Z]{1})[a-z]+/',$name,$matches)) $init.="$matches[1].";
          elseif(preg_match('/^([A-Z])\.?([A-Z]?)\.?([A-Z]?)/',$name,$matches)) {  //match multiple initials without periods
                array_shift($matches); // get rid of first item
              foreach($matches as $match){
                  if($match=='') break;
                  $init.=$match . '.';
              }
          }
      }//foreach

      return $init;
  }
  /**
  * Strip spaces from front and back of string
  *
  * @param mixed $text
  * @return mixed
  */
  function stripSpaces($text){
      $text=preg_replace('/^\s+/','',$text);
      $text=preg_replace('/\s+$/','',$text);
      return $text;
  }

  /**
  * Inserts a formatted author list into the $ref array
  *
  * @param mixed $ref Pre-initialized array. Also returns this array with changes
  * @param array $user The user array - just use first and last name
  * @param array $cv_item The cv_item
  * @param mixed $fa_field the field name of the first author
  * @param mixed $co_field  the field name of the co_authors
  * @param mixed $role the value of the role field (not the name). If it's one this is the first_author. Can  be ommitted.
  */
  function authorList($ref,$user,$cv_item,$fa_field,$co_field,$role=1,$target='report'){
      global $db;
      if(($role==1 || $role==4 || $role==6 || $role==7) && ($cv_item[$fa_field]=='' || $cv_item[$fa_field]=='|' ))   //First listed Author or presenter or moderator
      //Then we ignore the first listed author field and use the username
      $ref['firstauthor']=$user['last_name'] . ', '. getInitials($user['first_name']);
      else { // Must be a co_author or something else - use the field
            if($cv_item[$fa_field]=='' && $target=='screen') $ref['firstauthor']="<font color='red'>FIRST AUTHOR</font>";

            else $ref['firstauthor'] = formatName($cv_item[$fa_field],true);
            //$firstauth=explode('|',$cv_item[$fa_field]);
            //if(!isset($firstauth[0])) $ref['firstauthor']=''; // nothing there
            //elseif(!isset($firstauth[1])) $ref['firstauthor']=$firstauth[0];  // no first name
            //else $ref['firstauthor']=$firstauth[0] . ', '. getInitials($firstauth[1]);
        }
        $sql="SELECT * FROM `cas_sub_coauthors` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='$co_field' ORDER BY `order`";
        $authors=$db->getAll($sql);
        if(count($authors) > 0){
            foreach($authors as $author){

                  $ref['authors'][]=($author['firstname']!='') ? $author['lastname'] . ', '. getInitials($author['firstname']) :  $author['lastname'];
            }
        }//count
        return $ref;
  }
    /**
  * Inserts a formatted editor list into the $ref array
  *
  * @param mixed $ref Pre-initialized array. Also returns this array with changes
  * @param array $user The user array
   * @param array $cv_item The cv_item
  * @param mixed $fa_field the field name of the first author
  * @param mixed $co_field  the field name of the co_authors
  * @param mixed $role the value of the editor role - either 'First Listed Editor' or 'Co-Editor'.
  */
  function edsList($ref,$user,$cv_item,$fa_field,$co_field,$role='First Listed Editor'){
      global $db;
      if($role=='First Listed Editor' && $cv_item[$fa_field]=='')   //First listed Ed
      //Then we ignore the first listed author field and use the username
      $ref['firsteditor']=getInitials($user['first_name']).' '.$user['last_name'];
      elseif($role=='Co-Editor') { //  use the field
            if($cv_item[$fa_field]=='') $ref['firsteditor']="<font color='red'>FIRST EDITOR</font>";
            else $ref['firsteditor'] = formatName($cv_item[$fa_field],true,true);
            //$firstauth=explode('|',$cv_item[$fa_field]);
            //if(!isset($firstauth[0])) $ref['firstauthor']=''; // nothing there
            //elseif(!isset($firstauth[1])) $ref['firstauthor']=$firstauth[0];  // no first name
            //else $ref['firstauthor']=$firstauth[0] . ', '. getInitials($firstauth[1]);
        }
        else{  //it was a first or co-author, so we don't really care if there are editors or not
            if($cv_item[$fa_field]!='') $ref['firsteditor'] = formatName($cv_item[$fa_field],true,true);
        }
        $sql="SELECT * FROM `cas_sub_coauthors` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='$co_field' ORDER BY `order`";
        $authors=$db->getAll($sql);
        if(count($authors) > 0){
            foreach($authors as $author){
                  $ref['editors'][]=getInitials($author['firstname']).' '.$author['lastname'];
            }
        }//count
        return $ref;
  }



  /**
  * Return a properly formatted name from a compound field (uses '|' as name delimiter
  *
  * @param mixed $source Field contents
  * @param mixed $initials Flag to return initials or full first name
  * @param mixed $backwards Reverse usual initial/name order
  */
  function formatName($source,$initials=true,$backwards=false){
      $name=explode('|',$source);
      if(!isset($name[0])) return ''; // nothing there
      elseif(!isset($name[1])) return stripSpaces($name[0]);  // no first name
      else{
        if($backwards){
            if($initials)  return getInitials($name[1]) . ' '. stripSpaces($name[0]) ;
            else return stripSpaces($name[1]).' '.stripSpaces($name[0]);
        }
        else {//not backwards
            if($initials)  return stripSpaces($name[0]) . ', '. getInitials($name[1]);
            else return stripSpaces($name[0]) . ', '. stripSpaces($name[1]);
        }
      }

  }

    /**
  * Returns formatted author list
  *
  * @param array $cv_item The Item
  * @param mixed $co_field  the field name of the co_authors
  * @param Boolean $roles Include roles - uses diff table.
  * @param text $append Text to put at the end eg 'Co-author'. Will be plural-ed if neccessary
  * @param boolean  $reverse Put full first name first. Defualt is lastname, firstinitial
  * Returns a string with formatted list
  */
  function namesList($cv_item,$co_field,$roles=false,$append='',$reverse=false,$lead=''){
      global $db;
        $result='';
        if($roles) $table='cas_sub_namerole'; else $table='cas_sub_names';
        $sql="SELECT * FROM `$table` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='$co_field'";
        $authors=$db->getAll($sql);

        if(count($authors) > 0){
            if(count($authors)>1) $plural='s'; else $plural='';
            foreach($authors as $key=>$author){
                if($reverse){
                    if($key==0) {
                        $result.=cleanText($author['firstname']);
                    }
                    else {
                        $result.=', '.$author['firstname'];

                    }
                    if($author['lastname']!='') $result.=  ' '. ($author['lastname']);
                    if($roles) if($author['role']!='') $result.=" ($author[role])";
                }//reversed
                else {
                    if($key==0) {
                        $result.=cleanText($author['lastname']);
                    }
                    else {
                        $result.=', '.$author['lastname'];
                    }
                    if($author['firstname']!='') $result.=  ', '. getInitials($author['firstname']);
                    if($roles) if($author['role']!='') $result.=" ($author[role])";
                }//not reversed
            }
            if($append!='') $result.=", $append$plural";
            if($lead!='') $result="$lead$plural: $result";
        }//count

        return $result;
  }

  function coauthorList($cv_item,$co_field){
      global $db;
        $result='';

        $sql="SELECT * FROM `cas_sub_coauthors` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='$co_field' ORDER BY `order`";
        $authors=$db->getAll($sql);

        if(count($authors) > 0){
            $result.=cleanText($authors[0]['lastname']);
            if($authors[0]['firstname']!='') $result.=  ', '. getInitials($authors[0]['firstname']);
            array_shift($authors);
            while($authors){
                 if(count($authors)==1) $connector=', & '; else $connector=', ';
                 $result.=$connector . $authors[0]['lastname'];
                 if($authors[0]['firstname']!='') $result.=  ', '. getInitials($authors[0]['firstname']);
                 array_shift($authors);
            }
        }//count
        return $result;
  }


  /**
  * Format based on currency type, if it exists
  *
  * @param mixed $amount int containing amount
  * @param mixed $currency_id ID from cas_currenacy_types
  */
  function formatMoney($amount,$currency_id){
      global $db;
      $sql="SELECT * FROM `cas_currency_types` where `id`='$currency_id'";
        $result=$db->getRow($sql);
        if($result) {
            if($result['locale']!='') {
                setlocale(LC_MONETARY, $result['locale']);
                return money_format('%.0i', $amount);
            }
            else return '$'. number_format($amount);  //locale not set, so return standard $
        }//if result
        else {  //no result, so ID was wrong
            return '$'. number_format($amount);  //locale not set, so return standard $
        }//else
  }
  /**
  * Checks for the phrase 'et al' in any form. Returns true or false
  *
  * @param mixed $text
  */
  function isetal($text){

      $text=stripSpaces($text);
      if(preg_match('/(^et)/',$text) && preg_match('/(al)/',$text)) return true;
      else return false;
  }

  /**
  * Builds a list with commas and &s if required
  *
  * @param mixed $cv_item_id The id of the current item
  * @param mixed $fieldname The n-field name supplying the data
  * @param mixed $tablename The cas_ table for lookup
  * @param mixed $targetfield The name field holding the data you wish to list (in cas_...)
  * @param text $leadingword The word to use to preface the list (eg Venue)
  * @param bool $amp Use an ampersand after the last one or don't
  */
  function buildformatList($cv_item_id,$fieldname,$tablename,$targetfield,$leadingword='',$amp=false) {
        global $db;
        $sql="SELECT * FROM `$tablename` WHERE `cv_item_id`='$cv_item_id]' AND `fieldname`='$fieldname' ";
        $result=$db->getAll($sql);
        if(count($result) > 0){
            //
            if(count($result)>1) $plural='s';else $plural='';
            $output='';
            if($leadingword!='') $output.="$leadingword$plural: ";
            $output.=$result[0][$targetfield];
            array_shift($result);
            while($result ){
                if(count($result==1) && $amp) $output.=" &amp; ".$result[0][$targetfield];
                  else $output.=", ".$result[0][$targetfield];
                  array_shift($result);
            }
            return $output;
        }//count
  }
  /**
  * List of Broadcast Dates
  *
  * @param mixed $cv_item_id cv_item
  * @param mixed $fieldname the field to use
  * @param mixed $leadingword Word to lead the list
  * @param mixed $amp Use ampersand or not
  */
  function broadcastList($cv_item_id,$fieldname,$leadingword='',$amp=false) {
        global $db;
        $sql="SELECT * FROM `cas_sub_broadcasts` WHERE `cv_item_id`='$cv_item_id]' AND `fieldname`='$fieldname' ";
        $result=$db->getAll($sql);
        if(count($result) > 0){
            //
            if(count($result)>1) $plural='s';else $plural='';
            $output='';
            if($leadingword!='') $output.="$leadingword$plural: ";
            $output.=$result[0]['month'].' '. $result[0]['year'] .' on '. $result[0]['network_name'];
            array_shift($result);
            while($result ){
                if(count($result==1) && $amp) $output.=" &amp; ".$result[0]['month'].' '. $result[0]['year'] .' on '. $result[0]['network_name'];
                  else $output.=", ".$result[0]['month'].' '. $result[0]['year'] .' on '. $result[0]['network_name'];
                  array_shift($result);
            }
            return $output;
        }//count
  }

    function pagesList($co_field){
      global $db,$cv_item;
        $result='';
        $sql="SELECT * FROM `cas_sub_ranges` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='$co_field'";
        $ranges=$db->getAll($sql);

        if(count($ranges) > 0){
            foreach($ranges as $key=>$range){
                    if($key==0) {
                        if($range['to']=='') $result.=$range['from'];
                        else $result.="$range[from]-$range[to]";
                    }
                    else {
                        if($range['to']=='') $result.=", ".$range['from'];
                        else $result.=", $range[from]-$range[to]";
                    }
            }
        }//count
        //echo("Returning $result");
        return $result;
  }

  function datesList($co_field){
      global $db,$cv_item;
        $result='';
        $sql="SELECT * FROM `cas_sub_dates` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='$co_field'";
        $dates=$db->getAll($sql);
        if(count($dates) > 0){
            foreach($dates as $key=>$date){
                    if($key==0) {
                        $result.=date('Y, F j',strtotime($date['date']));
                    }
                    else {
                        $result.=", ".date('Y, F j',strtotime($date['date']));
                    }
            }
        }//count
        //echo("Returning $result");
        return $result;
  }

  ////////////////////////////////   END ORSADMIN FUNCTION BLOCK /////////////////////////////


/**
 * Builds the selection list for a given reference table
 *
 * @param string $tableName The table that has the possible values for this select list.
 * @param string $selectValue The currently selection option
 * @returns string A string that contains the options for the given table
 */
function BuildList ( $tableName, $selectValue ) {
    global $db;
    $returnVal = '';
    $sql = "SELECT id,name FROM {$tableName} ORDER BY name";
    $data = $db->getAll( $sql );

    $returnVal = '<option value=""></option>';
    foreach ( $data as $rowData ) {
        $returnVal .= '<option value="' . $rowData['id'] . '" ';
        if ( $rowData['id'] == $selectValue ) {
            $returnVal .= ' selected="selected" ';
        }
        $returnVal .= '>';
        $returnVal .= $rowData['name'];
        $returnVal .= "</option>\r\n";
    }
    return $returnVal;
}

/**
 * Retrieve's the heading for the current cas_heading_id
 *
 * @param string $casTypeId  cas heading to retrieve
 * @return string returns the heading according to the id that was passed in.
 */
function GetHeading ( $casTypeId ) {
    global $db;
    $sql = "select type_name from cas_types where cas_type_id = {$casTypeId}";
    $result = $db->getAll( $sql );
    return $result[0]['type_name'];
}

function GetCasHeading ( $casTypeId ) {
    global $db;
    $sql = "SELECT heading_name FROM `cas_types`
        join `cas_headings` on `cas_headings`.`cas_heading_id` = `cas_types`.`cas_heading_id`
        where `cas_type_id` = {$casTypeId}";
    $result = $db->getAll( $sql );
    return $result[0]['heading_name'];
}

/**
 * Gets The count of items for the current CV Hhading Type.
 *
 * @param int $userId
 * @param int $casTypeId
 * @return int number of rows for that item type.
 */
function GetCvItemPerHeading ( $userId, $casTypeId ) {
    global $db;
    $sql = "SELECT count(*) AS items FROM cas_cv_items WHERE user_id = {$userId} AND cas_type_id = {$casTypeId}";
    $result = $db->getAll( $sql );
    return $result[0]['items'];
}

/**
 * Builds the list of fields for "sub" cv items
 *
 * @param array $cvItem array containing the current cvItem that is a "sub" item
 * @param array $fieldmeta array containing the current field definition for the "sub" item
 * @return string containing the built up sub form elements
 *
 */
function BuildSubTableFields ( $cvItem, $fieldMeta ) {
    global $db;
    global $configInfo;

    $fieldName = $fieldMeta['cas_cv_item_field'];
    $size = $fieldMeta['size'];
    if (isset($fieldMeta['maxlength'])){
        $maxlength = $fieldMeta['maxlength'];
    }
    $sql = "select * from cas_subtables where table_name = '{$fieldMeta['subtable']}'";
    $subTableResult = $db->getAll( $sql );

    if ( $subTableResult[0]['order_by'] ) {
        $orderBy = ' ORDER BY ' . $subTableResult[0]['order_by'];
    }
    $sql = "select * from {$fieldMeta['subtable']} where cv_item_id = {$cvItem['cv_item_id']} and fieldname = '{$fieldName}' {$orderBy}";
    $subTableData = $db->getAll( $sql );
    $field .= " <table> ";
    $field .= "<tr>\r\n";
    $cols = 0;
    if ( $subTableResult[0]['field1_name'] != '' ) {
        $field .= '<th>' . $subTableResult[0]['field1_display'] . "</th>";
        $cols++;
    }
    if ( $subTableResult[0]['field2_name'] != '' ) {
        $field .= '<th>' . $subTableResult[0]['field2_display'] . "</th>";
        $cols++;
    }
    if ( $subTableResult[0]['field3_name'] != '' ) {
        $field .= '<th>' . $subTableResult[0]['field3_display'] . "</th>";
        $cols++;
    }
    $field .= "<th>Delete?</th></tr>\r\n";

    foreach ( $subTableData as $data ) {
        $field .= "<tr>\r\n";
        if ( $subTableResult[0]['field1_name'] != '' ) {
            if ($subTableResult[0]['field1_size'] != 0){
                $size = $subTableResult[0]['field1_size'];
            }else{
                if ($size == 0){
                    switch ( $subTableResult[0]['field1_type'] ) {
                        case "text":
                            $size = $configInfo["subfields_text_length"];
                            break;
                        case "date":
                        case "num":
                            $size = $configInfo["subfields_num_length"];
                            break;
                    }
                }
            }
            $field .= '<td><input type="text" name="' . $fieldName . '[' . $data['id'] . '][' . $subTableResult[0]['field1_name'] . ']" id="' . $subTableResult[0]['field1_name'] . '" value="' . $data[$subTableResult[0]['field1_name']] . '" size="' . $size . '" maxlength = "' . $size . '" /></td>';
        }
        if ( $subTableResult[0]['field2_name'] != '' ) {
            if ($subTableResult[0]['field2_size'] != 0){
                $size = $subTableResult[0]['field2_size'];
            }else{
                if ($size == 0){
                    switch ( $subTableResult[0]['field2_type'] ) {
                        case "text":
                            $size = $configInfo["subfields_text_length"];
                            break;
                        case "date":
                        case "num":
                            $size = $configInfo["subfields_num_length"];
                            break;
                    }
                }
            }
            $field .= '<td><input type="text" name="' . $fieldName . '[' . $data['id'] . '][' . $subTableResult[0]['field2_name'] . ']" id="' . $subTableResult[0]['field2_name'] . '" value="' . $data[$subTableResult[0]['field2_name']] . '" size="' . $size . '" maxlength = "' . $size . '" /></td>';
        }
        if ( $subTableResult[0]['field3_name'] != '' ) {
            if ($subTableResult[0]['field3_size'] != 0){
                $size = $subTableResult[0]['field3_size'];
            }else{
                if ($size == 0){
                    switch ( $subTableResult[0]['field3_type'] ) {
                        case "text":
                            $size = $configInfo["subfields_text_length"];
                            break;
                        case "date":
                        case "num":
                            $size = $configInfo["subfields_num_length"];
                            break;
                    }
                }
            }
            $field .= '<td><input type="text" name="' . $fieldName . '[' . $data['id'] . '][' . $subTableResult[0]['field3_name'] . ']" id="' . $subTableResult[0]['field3_name'] . '" value="' . $data[$subTableResult[0]['field3_name']] . '"  size="' . $size . '" maxlength = "' . $size . '" /></td>';
        }
        $field .= "<td><input type=\"checkbox\" name=\"{$fieldName}[{$data['id']}][delete_row]\" value=\"1\" /></td></tr>\r\n";
    }

    $field .= "<tr>\r\n";
    if ( $subTableResult[0]['field1_name'] != '' ) {
         if ($subTableResult[0]['field1_size'] != 0){
            $size = $subTableResult[0]['field1_size'];
        }else{
            if ($size == 0){
                switch ( $subTableResult[0]['field1_type'] ) {
                    case "text":
                        $size = $configInfo["subfields_text_length"];
                        break;
                    case "date":
                    case "num":
                        $size = $configInfo["subfields_num_length"];
                        break;
                }
            }
        }

        $field .= '<td><input type="text" name="' . $fieldName . '[new][' . $subTableResult[0]['field1_name'] . ']" id="' . $subTableResult[0]['field1_name'] . '" value="" size="' . $size . '" maxlength = "' . $size . '" /></td>';
    }
    if ( $subTableResult[0]['field2_name'] != '' ) {
        if ($subTableResult[0]['field2_size'] != 0){
            $size = $subTableResult[0]['field2_size'];
        }else{
            if ($size == 0){
                switch ( $subTableResult[0]['field2_type'] ) {
                    case "text":
                        $size = $configInfo["subfields_text_length"];
                        break;
                    case "date":
                    case "num":
                        $size = $configInfo["subfields_num_length"];
                        break;
                }
            }
        }
        $field .= '<td><input type="text" name="' . $fieldName . '[new][' . $subTableResult[0]['field2_name'] . ']" id="' . $subTableResult[0]['field2_name'] . '" value="" size="' . $size . '" maxlength = "' . $size . '" /></td>';
    }
    if ( $subTableResult[0]['field3_name'] != '' ) {
        if ($subTableResult[0]['field3_size'] != 0){
            $size = $subTableResult[0]['field3_size'];
        }else{
            if ($size == 0){
                switch ( $subTableResult[0]['field3_type'] ) {
                    case "text":
                        $size = $configInfo["subfields_text_length"];
                        break;
                    case "date":
                    case "num":
                        $size = $configInfo["subfields_num_length"];
                        break;
                }
            }
        }
        $field .= '<td><input type="text" name="' . $fieldName . '[new][' . $subTableResult[0]['field3_name'] . ']" id="' . $subTableResult[0]['field3_name'] . '" value=""  size="' . $size . '" maxlength = "' . $size . '" /></td>';
    }
    $field .= "<td>&nbsp;</td></tr><tr><td colspan = \"{$cols}\" align=\"right\"><input type=\"submit\" name=\"mr_action\" value=\"Save Changes\" title=\"Update the list with new items or deletions\"></td></tr>\r\n";

    $field .= " </table> ";
    return $field;
}

/**
 * Delete blank items from the cas_cv_items table when a user generates their own list.
 *
 */
function ClearBlanks () {
    global $db;
        $fieldarray=array(  'n01'=>'Text',
                        'n02'=>'List',
                        'n03'=>'Bool',
                        'n04'=>'List',
                        'n05'=>'Text',
                        'n06'=>'Num',
                        'n07'=>'Num',
                        'n08'=>'Num',
                        'n09'=>'Date',
                        'n10'=>'Num',
                        'n11'=>'Num',
                        'n12'=>'Num',
                        'n13'=>'List',
                        'n14'=>'Text',
                        'n15'=>'Sub',
                        'n16'=>'Sub',
                        'n17'=>'Sub',
                        'n18'=>'Date',
                        'n19'=>'Date',
                        'n20'=>'List',
                        'n21'=>'List',
                        'n22'=>'Text',
                        'n23'=>'Bool',
                        'n24'=>'Bool',
                        'n25'=>'Text',
                        'n26'=>'Text',
                        'n27'=>'Text',
                        'n28'=>'Sub',
                        'n29'=>'Date',
                        'n30'=>'Text'
                    );
                    
    $where = " cv_item_type_id=0 AND cas_type_id <> 0 AND user_id = " . GetVerifyUserId();

    for ( $i = 1; $i <= 30; $i++ ) {
        $field='n'.sprintf('%02d',$i);
        
        if($fieldarray[$field]=='Text') $targ='';
        elseif($fieldarray[$field]=='Date') $targ='0000-00-00';
        else $targ=0;
        $where .= " AND $field = '$targ'";
    }
    $where.= " AND details_teaching='' AND details_scholarship='' AND details_service=''";
    $sql = 'DELETE FROM cas_cv_items WHERE ' . $where;

    if ( !$db->Execute( $sql ) ) {
        echo "An error occured when clearing blank items";
    }
}

/**
 * Sorts cvItems based on user driven choises
 *
 * @param int $userId  User ID of the currently logged in user
 * @param int $casTypeId Type ID of the current CV item
 * @param int $cvItemId ID of the current CV item
 * @param string $direction can be either 'up' or 'down'
 * @returns bool was the operation successful or not.
 */
function SortCvItems ( $userId, $casTypeId, $cvItemId, $direction ) {
    global $db;
    $success = true;
    //Modified by TD; 11-04-20; Longer lists with some new entries had a habit of becoming corrupted in the rank, and became unsortable. 
    //So even if the rank has been created, recreate it each time this routine is called.
    

    $sql = "SELECT cv_item_id,rank FROM cas_cv_items WHERE user_id = {$userId}  AND cas_type_id = {$casTypeId} ORDER BY rank desc, f2 desc";
    $result = $db->GetAll( $sql );
    //if ( $result[0]['rank'] == 0 ) {
    $rank = sizeof( $result );
    foreach ( $result as $data ) {
        $sql = "UPDATE cas_cv_items SET rank = {$rank} WHERE cv_item_id = {$data['cv_item_id']}";
        $db->Execute( $sql );
        $rank--;
    }

    //refresh the results
    $sql = "SELECT rank FROM cas_cv_items WHERE cv_item_id = {$cvItemId}";
    $result = $db->GetAll( $sql );
    if ( sizeof( $result ) > 0 ) {
        $currentRank = $result[0]['rank'];

        //update the records that apply to the current sort

        switch ( $direction ) {
            case "up":
                $newRank = $currentRank + 1;
                break;
            case "down":
                $newRank = $currentRank - 1;
                break;
        }
        $sql = "UPDATE cas_cv_items SET rank = {$currentRank} WHERE user_id = {$userId}  AND cas_type_id = {$casTypeId} AND rank = {$newRank}";
        $db->Execute( $sql );
        $sql = "UPDATE cas_cv_items SET rank = {$newRank} WHERE cv_item_id={$cvItemId}";
        $db->Execute( $sql );
    } else {
        $success = false;
    }

    return $success;
}

?>
