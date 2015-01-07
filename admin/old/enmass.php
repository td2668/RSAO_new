<?php
    //error_reporting(E_ALL);
    include("includes/config.inc.php");
    include("includes/functions-required.php");
    include("includes/cv_functions.php");
        class cite{
        public $text;
        public $authors;
        public $aufull;
        public $aulast;
        public $aufirst;
        public $auinit;
        public $atitle;
        public $title;
        public $volume;
        public $issue;
        public $supl;
        public $spage;
        public $year;
        public $date;
        public $debug=1;
    }

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
   $_REQUEST['cas_type_id']=25;
   $_REQUEST['cv_item_type_id']=58;  
                   
   $sql="SELECT * FROM cas_cv_items WHERE cv_item_type_id='$_REQUEST[cv_item_type_id]' AND converted=0 order by cv_item_id desc";
   $items=$db->getAll($sql);
   if(count($items)>0) foreach($items as $cv_item){
                   
    
    
    
    $sql="SELECT * FROM `cas_types_xref` WHERE `cas_type_id`='$_REQUEST[cas_type_id]' AND `cv_item_type_id`='$_REQUEST[cv_item_type_id]'";
            $xref=$db->getRow($sql);
            if($xref){
                $sql="SELECT * FROM cas_fields_xref WHERE xref_id='$xref[cas_types_xref_id]'";
                $xrefs=$db->getAll($sql);
                if(count($xrefs > 0)){
                    //load the item
                    
                    foreach($xrefs as $xref){
                        switch($xref['type']){
                            
                            case 'text'://type text
                                (preg_replace('/^([\"\']).*([\"\'])$/','',$cv_item[$xref['f']])) ;
                                $cv_item[$xref['n']]=mysql_real_escape_string($cv_item[$xref['f']]);
                            break;
                            case 'text-concat'://type text
                                (preg_replace('/^([\"\']).*([\"\'])$/','',$cv_item[$xref['f']])) ;
                                if($cv_item[$xref['f']] !='') $cv_item[$xref['n']].=', ' . mysql_real_escape_string($cv_item[$xref['f']]);
                            break;
                            
                            case 'auth':
                                //parse field for first author. Put in result field, last name first.
                                //Uses the 'author' type only - the result is stored as Lastname|Firstname 
                                //first check if it is a full name, which is XX,XX - only works for main author
                                if(preg_match('/^([a-zA-Z]{2,}),\s?([a-zA-Z]{2,})/',$cv_item[$xref['f']],$matches)) {
                                    $cv_item[$xref['n']]= "$matches[1]|$matches[2]";
                                }
                                else {
                                    $cite=new cite();
                                    
                                    //test data
                                    //$cv_item[$xref['f']]="Engler, S., Jones, P. and James, L.K.";
                                    normalisation($cv_item[$xref['f']]);
                                    find_authors($cv_item[$xref['f']]);                               
                                    find_first_author($cv_item[$xref['f']]);
                                    //reverse the lastname and initial - everything from the right up to a period.
                                    //$cite->aufirst='S.B.Engler';
                                    //echo("First Author: ". $cite->aufirst ."<br>");
                                    //var_dump($cite->aufull);
                                    preg_match('/(.*\.)(.*)$/',$cite->aufirst,$matches);
                                    //echo("MATCHES:<br>");
                                    //var_dump($matches);
                                    if(isset($matches[2])) $cv_item[$xref['n']]= "$matches[2]|$matches[1]";
                                    //aufirst wasnt set, so the author is messed up somehow. Fall back to the list    
                                    else $cv_item[$xref['n']]= $cite->aufull[0];   
                                }                        
                                
                            break;
                            
                            case 'coauth':
                                //look for any additional authors.
                                
                                //test data
                                //$cv_item[$xref['f']]="Engler, S., Jones, P. and James, L.K.";
                                $cite=new cite();
                                normalisation($cv_item[$xref['f']]);
                                find_authors($cv_item[$xref['f']]);
                                find_first_author($cv_item[$xref['f']]);
                                array_shift($cite->aufull);
                                $order=1;
                                foreach($cite->aufull as $author){
                                    preg_match('/(.*\.)(.*)$/',$author,$matches);
                                    
                                    $sql="SELECT * FROM `cas_field_index` WHERE `cas_type_id`='$_REQUEST[cas_type_id]' AND `cas_cv_item_field`='$xref[n]'";
                                    $cas_subfield=$db->getRow($sql);
                                    if($cas_subfield){
                                        $sql="SELECT * FROM `cas_subtables` WHERE `table_name`='$cas_subfield[subtable]'";
                                        $substructure=$db->getRow($sql);
                                        //var_dump($substructure);
                                        unset($f1); unset($f2); unset($f3);
                                        if($substructure['field1_name'] !='') $f1= $matches[2];
                                        //if(isset($f1)) echo("F1= $f1 <br>");
                                        if($substructure['field2_name'] !='') $f2= $matches[1];
                                        //if(isset($f2)) echo("F2= $f2 <br>");
                                        if(strlen($substructure['field3_name']) > 0) $f3= $order;
                                        $order++;
                                        //if(isset($f3)) echo("F3= $f3 <br>");
                                        //if f1 is blank then dont bother, as this wasn't needed - COULD BE MORE ROBUST
                                        if($f1 != ''){
                                            //first lets check if it exists
                                            $sql="SELECT * FROM `$cas_subfield[subtable]` WHERE `cv_item_id`='$_REQUEST[cv_item_id]' AND `fieldname`='$xref[n]' AND `lastname`='$f1' AND `firstname`='$f2'";
                                            $result=$db->getAll($sql);
                                            //var_dump($result);
                                            if(count($result)==0){
                                            
                                                $sql="INSERT INTO `$cas_subfield[subtable]` (`id`, `cv_item_id`, `fieldname`" .
                                                     (isset($f1) ? ", `$substructure[field1_name]`" : '' ) .
                                                     (isset($f2) ? ", `$substructure[field2_name]`" : '' ) .
                                                     (isset($f3) ? ", `$substructure[field3_name]`" : '' ) .
                                                     ") VALUES(NULL, '$_REQUEST[cv_item_id]', '$xref[n]'" .
                                                     (isset($f1) ? ", '$f1'" : '' ) .
                                                     (isset($f2) ? ", '$f2'" : '' ) .
                                                     (isset($f3) ? ", '$f3'" : '' ) .
                                                     ")";
                                                     
                                                $result=$db->Execute($sql);
                                            }
                                            //echo("Just wrote to the db for subtable $xref[n]<br>");
                                        }
                                    }// if the type is used
                                    //the zero means nothing - just used to help keep things standardized
                                   //$$fieldname=0; 
                                    
                                    
                                }//foreach author
                                //for each one put an entry into the relevant table.
                                
                            
                            break;
                            
                            case 'role':
                                $cite=new cite();   
                            //first check if it is a full name, which is XX,XX - only works for main author
                                if(preg_match('/^([a-zA-Z]{2,}),\s?([a-zA-Z]{2,})/',$cv_item[$xref['f']],$matches)) {
                                    $cv_item[$xref['n']]= 1;
                                }
                                else {
                                
                                
                                    //parse for first author.
                                    normalisation($cv_item[$xref['f']]);
                                    find_authors($cv_item[$xref['f']]);                               
                                    find_first_author($cv_item[$xref['f']]);
                                    //if first author has the same name as the user, use the 'first-listed author' role
                                    $sql="SELECT * FROM users WHERE user_id=$cv_item[user_id]";
                                    $user=$db->getRow($sql);
                                    //var_dump($user);
                                    if($user){
                                        if(preg_match('/(.*\.)(.*)$/',$cite->aufirst,$matches))
                                            if(stripos($matches[0],$user['last_name'])) $cv_item[$xref['n']]=1;
                                        //elseif(stripos($cite->aufull[0],$user['last_name'])) $cv_item[$xref['n']]=1;
                                        else $cv_item[$xref['n']]=3;                          
                                    }
                                }
                                //else use the co-author role
                                 
                            break;
                            
                            case 'bookrole':
                                $cite=new cite();   
                            //first check if it is a full name, which is XX,XX - only works for main author
                                if(preg_match('/^([a-zA-Z]{2,}),\s?([a-zA-Z]{2,})/',$cv_item[$xref['f']],$matches)) {
                                    $cv_item[$xref['n']]= 1;
                                }
                                else {
                                
                                
                                    //parse for first author.
                                    normalisation($cv_item[$xref['f']]);
                                    find_authors($cv_item[$xref['f']]);                               
                                    find_first_author($cv_item[$xref['f']]);
                                    //if first author has the same name as the user, use the 'first-listed author' role
                                    $sql="SELECT * FROM users WHERE user_id=$cv_item[user_id]";
                                    $user=$db->getRow($sql);
                                    //var_dump($user);
                                    if($user){
                                        if(preg_match('/(.*\.)(.*)$/',$cite->aufirst,$matches))
                                            if(stripos($matches[0],$user['last_name'])) $cv_item[$xref['n']]=1;
                                        //elseif(stripos($cite->aufull[0],$user['last_name'])) $cv_item[$xref['n']]=1;
                                        else $cv_item[$xref['n']]=3;                          
                                    }
                                }
                                
                                 
                            break;
                            
                            case 'simplerole-author':
                               $cv_item [$xref['n']]=1;                                
                            break;
                            
                            case 'simplerole-presenter':
                               $cv_item [$xref['n']]=4;                                
                            break;
                            
                            case 'grantrole':
                                if(preg_match('/(co)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=2;
                               else $cv_item [$xref['n']]=1;                                
                            break;
                            
                            
                            case 'journal': //parse the source field for the jounral name
                                //should be everything up to a (number or a number.
                                //$cv_item[$xref['f']]='Revista de Estudos da Religião 45(3): 123-124';
                                if(preg_match('/^(.*?)[0-9]/',$cv_item[$xref['f']],$matches)) {
                                    //echo('Journal - found a #: '); var_dump($matches);
                                    $j=$matches[1];
                                    //strip space
                                    $j=preg_replace('/([\.,;\s]*)$/','',$j);                 
                                }                               
                                elseif(preg_match('/(.*)\([0-9]/',$cv_item[$xref['f']],$matches)) {
                                    
                                    //echo("matches: <br>");
                                    //var_dump($matches);
                                    $j=$matches[1];
                                    $j=preg_replace('/([\.,;\s]*)$/','',$j);
                                }                                 
                                // .*? (lazy)                                 
                                else $j=$cv_item[$xref['f']];
                                
                                //$cv_item[$xref['n']]=$j;
                                //Now look up in the table. 
                                if($j!=''){
                                    $sql="SELECT * FROM cas_research_journals WHERE name='$j'";
                                    $result=$db->getRow($sql);
                                    //echo('Journal lookup .'); var_dump($result);
                                    if(count($result)>0) $cv_item[$xref['n']]=$result['id'];
                                    else {
                                        //if it is found dump it into a temp field so it can be checked by the operator before commiting
                                        $j=mysql_real_escape_string($j);
                                        $sql="UPDATE cas_cv_items SET `document_filename`='$j' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
                                        $result=$db->Execute($sql);
                                        //$cv_item[$xref['n']]=$db->Insert_ID();
                                    }
                                }                                                                                              
                            break;
                            
                            case 'department':  
                            $j=addslashes(cleanText($cv_item[$xref['f']]));
                                if($j!=''){
                                    $sql="SELECT * FROM cas_institution_departments WHERE name='$j'";
                                    $result=$db->getRow($sql);
                                    if(count($result)>0) $cv_item[$xref['n']]=$result['id'];
                                    else {
                                        //if it is found dump it into a temp field so it can be checked by the operator before commiting
                                        $j=mysql_real_escape_string($j);
                                        $sql="UPDATE cas_cv_items SET `document_filename`='$j' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
                                        $result=$db->Execute($sql);
                                        //$cv_item[$xref['n']]=$db->Insert_ID();
                                    }
                                }                                                                                              
                            break;
                            
                            case 'university':  
                            //if the data has a comma
                            $j=addslashes(cleanText($cv_item[$xref['f']]));
                                if($j!=''){
                                    $sql="SELECT * FROM cas_institutions WHERE name='$j'";
                                    $result=$db->getRow($sql);
                                    if(count($result)>0) $cv_item[$xref['n']]=$result['id'];
                                    else {
                                        //if it is found dump it into a temp field so it can be checked by the operator before commiting
                                        $j=mysql_real_escape_string($j);
                                        $sql="UPDATE cas_cv_items SET `document_filename`='$j' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
                                        $result=$db->Execute($sql);
                                        //$cv_item[$xref['n']]=$db->Insert_ID();
                                    }
                                }                                                                                              
                            break;
                            
                            
                            case 'degree':  
                            $j=($cv_item[$xref['f']]);
                                if($j!=''){
                                    if(cleanText($j)=='BA') $j='B.A.';
                                    if(cleanText($j)=='B.Comm') $j='B.Comm.';
                                    if(cleanText($j)=='PhD') $j='Ph.D.';
                                    if(cleanText($j)=='B. Sc.') $j='B.Sc.';
                                    if(cleanText($j)=='BSc') $j='B.Sc.';
                                    if(cleanText($j)=='MSc') $j='M.Sc.';
                                    if(cleanText($j)=='Ph. D.') $j='Ph.D.';
                                    if(cleanText($j)=='Doctor of Philosophy') $j='Ph.D.';
                                    if(cleanText($j)=='MA') $j='M.A.';
                                    if(cleanText($j)=='CMA') $j='C.M.A.';
                                    if(cleanText($j)=='RN') $j='R.N.';
                                    if(cleanText($j)=='CA') $j='C.A.';
                                    if(cleanText($j)=='MBA') $j='M.B.A.';
                                    $sql="SELECT * FROM cas_degree_types WHERE name='$j'";
                                    $result=$db->getRow($sql);
                                    if(count($result)>0) $cv_item[$xref['n']]=$result['id'];
                                    
                                }                                                                                              
                            break;
                            
                            case 'fundingorg':  
                            $j=(cleanText($cv_item[$xref['f']]));
                                if($j!=''){
                                    $sql="SELECT * FROM cas_funding_organizations WHERE name='".mysql_real_escape_string($j)."'";
                                    $result=$db->getRow($sql);
                                    if(count($result)>0) $cv_item[$xref['n']]=$result['id'];
                                    else {
                                        //if it is found dump it into a temp field so it can be checked by the operator before commiting
                                        $j=mysql_real_escape_string($j);
                                        $sql="UPDATE cas_cv_items SET `document_filename`='$j' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
                                        $result=$db->Execute($sql);
                                        //$cv_item[$xref['n']]=$db->Insert_ID();
                                    }
                                }                                                                                              
                            break;
                            
                            case 'confname':
                                if(preg_match('/^(.*?)[0-9]/',$cv_item[$xref['f']],$matches)) {
                                    
                                    $j=$matches[1];
                                    //strip space
                                    $j=preg_replace('/([\.,;\s]*)$/','',$j);                 
                                }
                                
                                elseif(preg_match('/(.*)\([0-9]/',$cv_item[$xref['f']],$matches)) {
                                    
                                    //echo("matches: <br>");
                                    //var_dump($matches);
                                    $j=$matches[1];
                                    $j=preg_replace('/([\.,;\s]*)$/','',$j);
                                } 
                                
                                // .*? (lazy) 
                                
                                else $j=$cv_item[$xref['f']];
                                
                                $cv_item[$xref['n']]=mysql_real_escape_string($j);
                            break;
                            
                            case 'publisher':
                                if(preg_match('/(.*):\s(.*)/',$cv_item[$xref['f']],$matches)) {
                                    $cv_item[$xref['n']]=mysql_real_escape_string($matches[2]);
                                    
                                }
                                else $cv_item[$xref['n']]=$cv_item[$xref['f']];
                            break;
                            
                            case 'publoc':
                                if(preg_match('/(.*):\s(.*)/',$cv_item[$xref['f']],$matches)) {
                                    $cv_item[$xref['n']]=mysql_real_escape_string($matches[1]);
                                }
                            break;
                            
                            case 'date':
                                if($cv_item[$xref['f']] != 0){
                                    $cv_item[$xref['n']]=date('Y-m-d',$cv_item[$xref['f']]);
                                }
                                else $cv_item[$xref['n']]=0;
                            
                            break;
                            case 'volume':
                                //should be the first number encountered
                                //$cv_item[$xref['f']]='Revista de Estudos da Religio 6(3): 123-124';
                                //if(preg_match('/^.*?([0-9]+)[\(:\s\/\.]([0-9]+)[\):\s\/\.]*([0-9]*)[-]([0-9]*)/',$cv_item[$xref['f']],$matches)){
                                if(preg_match('/^.*?([0-9]+)[\(:\s\/\.].*/',$cv_item[$xref['f']],$matches)){
                                    $cv_item[$xref['n']]=$matches[1];
                                }
                                else $cv_item[$xref['n']]= '';
                                
                            
                            break;
                            case 'issue':
                                //$cv_item[$xref['f']]='Revista de Estudos da Religio 6(3): 123-124';
                                if(preg_match('/^.*?([0-9]+)[\(:\s\/\.]([0-9]+)[\),:\s\/\.]*([0-9]*)[-]([0-9]*)/',$cv_item[$xref['f']],$matches)){
                                    $cv_item[$xref['n']]=$matches[2];
                                    //echo("Issues: ". var_dump($matches));
                                }
                                else $cv_item[$xref['n']]= 0;
                            
                            break;
                            case 'from':
                                //$cv_item[$xref['f']]='Revista de Estudos da Religio 6(3): 123-124';
                                if(preg_match('/^.*?([0-9]+)[\(:\s\/\.]([0-9]*)[\),:\s\/\.]*([0-9]*)[-]([0-9]*)/',$cv_item[$xref['f']],$matches)){
                                    $cv_item[$xref['n']]=$matches[3];
                                }
                                else $cv_item[$xref['n']]= 0;                         
                            
                            break;
                            case 'to':
                                //$cv_item[$xref['f']]='Revista de Estudos da Religio 6(3):123';
                                if(preg_match('/^.*?([0-9]+)[\(:\s\/\.]([0-9]*)[\),:\s\/\.]*([0-9]*)[-]([0-9]*)/',$cv_item[$xref['f']],$matches)){
                                    $cv_item[$xref['n']]=$matches[4];
                                }
                                else $cv_item[$xref['n']]= 0;
                            
                            break;
                            case 'money':
                                if(preg_match('/([0-9]+)[,]*([0-9]+).*/',$cv_item[$xref['f']],$matches)) {
                                    $temp=$matches[1];
                                    if(isset($matches[2])) $temp.=$matches[2];
                                    $cv_item[$xref['n']]=intval($temp);
                                }
                                //var_dump($matches);
                            break;
                            case 'Submitted':
                                if($cv_item[$xref['f']]) $cv_item[$xref['n']]=2;
                            break;
                            
                            case 'Accepted':
                                if($cv_item[$xref['f']]) $cv_item[$xref['n']]=5;                           
                            break;
                            
                            case 'inprog':
                                if($cv_item[$xref['f']]) $cv_item[$xref['n']]=3;                           
                            break;
                            
                            case 'abd':
                                if($cv_item[$xref['f']]) $cv_item[$xref['n']]=1;                           
                            break;
                            
                            break;
                            case 'Submitted-book':
                                if($cv_item[$xref['f']]) $cv_item[$xref['n']]=2;
                            break;
                            
                            case 'Accepted-book':
                                if($cv_item[$xref['f']]) $cv_item[$xref['n']]=5;                           
                            break;
                            
                            case 'set':
                                //just set the flag, period
                                $cv_item[$xref['n']]=1;                            
                            break;
                            
                            case 'degree_status':
                                $cv_item[$xref['n']]=2;
                            break;
                            case 'commtype-dept':
                                $cv_item[$xref['n']]=1;
                            break;
                            case 'commtype-fac':
                                $cv_item[$xref['n']]=2;
                            break;
                            case 'commtype-univ':
                            
                                $cv_item[$xref['n']]=3;
                            break;
                            case 'commtype-other':
                            
                                $cv_item[$xref['n']]=5;
                            break;
                            case 'commrole':
                                if(preg_match('/(member)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=2;
                                elseif(preg_match('/(chair)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=1;
                                else $cv_item[$xref['n']]=2;
                            break;
                            case 'eventtype-conf':
                                $cv_item[$xref['n']]=1;
                            break;
                            case 'eventtype-course':
                                $cv_item[$xref['n']]=2;
                            break;
                            case 'eventtype-seminar':
                                $cv_item[$xref['n']]=3;
                            break;
                            case 'eventtype-workshop':
                                $cv_item[$xref['n']]=4;
                            break;
                            case 'eventtype-other':
                                $cv_item[$xref['n']]=5;
                            break;
                            case 'coursetype-lib':
                                $cv_item[$xref['n']]='Library Research Course';
                            break;
                            case 'awardtype-certif':
                                $cv_item[$xref['n']]=3;
                            break;
                            case 'chairs-support':
                                $cv_item[$xref['n']]='Chairs Support Program';
                            break;
                            
                            case 'mru':
                                if(preg_match('/(mount)/i',$cv_item[$xref['f']],$matches) && preg_match('/(college)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=12;
                                elseif(preg_match('/(mount)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=1;
                                if(preg_match('/(mru)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=1;
                                if(preg_match('/(mrc)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=1;
                            break;
                            
                        }//switch
                    }//foreach
                    //save the item
                    
                    foreach($xrefs as $xref) {
                        $sql="UPDATE `cas_cv_items` SET $xref[n]='{$cv_item[$xref['n']]}'  WHERE `cv_item_id`='$cv_item[cv_item_id]'";
                        $result=$db->Execute($sql);
                        
                    }
                    if($cv_item['details_teaching'] != '') {
                        $sql="UPDATE `cas_cv_items` SET n_teaching=1 WHERE `cv_item_id`='$cv_item[cv_item_id]'";
                        $result=$db->Execute($sql);   
                    }
                    if($cv_item['details_scholarship'] != '') {
                        $sql="UPDATE `cas_cv_items` SET n_scholarship=1 WHERE `cv_item_id`='$cv_item[cv_item_id]'";
                        $result=$db->Execute($sql);   
                    }
                    if($cv_item['details_service'] != '') {
                        $sql="UPDATE `cas_cv_items` SET n_service=1 WHERE `cv_item_id`='$cv_item[cv_item_id]'";
                        $result=$db->Execute($sql);   
                    }
                    //set the cas_type
                    $sql="UPDATE `cas_cv_items` SET converted=1  WHERE `cv_item_id`='$cv_item[cv_item_id]'";
                    $result=$db->Execute($sql);
                    
                    $sql="UPDATE `cas_cv_items` SET cas_type_id=$_REQUEST[cas_type_id]  WHERE `cv_item_id`='$cv_item[cv_item_id]'";
                    $result=$db->Execute($sql);
                    
                    echo ($cv_item['cv_item_id'] . '<br>');
                    

                    
                }//> 0
            }
    
   }
  
  function find_vol_no() {
        global $cite;
        $text=$cite->rest_text;
        if(preg_match('/[,;. ]\s*(?:volume|vol|v)?\.?\s*(\d+)\s*[ ,;]\s*(?:n|no|issue|\#)\.?\s*(\d+)\b/i',$text,$matches)) {
                $text=preg_replace('/[,;. ]\s*(?:volume|vol|v)?\.?\s*(\d+)\s*[ ,;]\s*(?:n|no|issue|\#)\.?\s*(\d+)\b/i','$1',$text);
                $cite->{'volume'} = $matches[2];
                $cite->{'issue'}  = $matches[3];
               // $cite->{'jnl_epos'} = length($`);
                $cite->{'rest_text'} = $text;
                return 1;
                }
        else  return 0;
     }
     
     function find_vol_supl($text) {
        global $cite;
 
        if(preg_match('/(\s|,|;|\.)\s*(?:volume|vol|v)?\.?\s*(\d+)\s*[\s,;]\s*(?:supl|supplement)\.?\s*(\d+)\b/$1/i',$text,$matches)) {
            $text=preg_replace('/(\s|,|;|\.)\s*(?:volume|vol|v)?\.?\s*(\d+)\s*[\s,;]\s*(?:supl|supplement)\.?\s*(\d+)\b/$1/i','',$text);
                $cite->{'volume'} = $matches[2];
                $cite->{'supl'}  = $matches[3];
               // $cite->{'jnl_epos'} = length($`);
                $cite->{'rest_text'} = $text;
                return 1;
                }
        else  return 0;
     }
     
      function find_vol($text) {
        global $cite;
 
        if(preg_match('/[,;:\. ]\s*(?:volume|vol)[\. ]\s*([a-z]*\d+[a-z]*)\b/i',$text,$matches)) {
            $text=preg_replace('/[,;:\. ]\s*(?:volume|vol)[\. ]\s*([a-z]*\d+[a-z]*)\b/i','',$text);
            $cite->{'volume'} = $matches[1];
            $cite->{'rest_text'} = $text;
            return;
        }
        # "..., Vol9 ..."
        if(preg_match('/[,;:\. ]\s*(?:volume|vol)(\d+[a-z]*)\b/i',$text,$matches)) {
            $text=preg_replace('/[,;:\. ]\s*(?:volume|vol)(\d+[a-z]*)\b/i','',$text);
            $cite->{'volume'} = $matches[1];
            $cite->{'rest_text'} = $text;
            return;
        }
        
        if(preg_match('/[,;:\. ]\s*(?:volume|vol)(\d+[a-z]*)\b/i',$text,$matches)) {
            $text=preg_replace('/[,;:\. ]\s*(?:volume|vol)(\d+[a-z]*)\b/i','',$text);
            $cite->{'volume'} = $matches[1];
            $cite->{'rest_text'} = $text;
            return;
        }
        # beware: "Smith, V. 1990, Phys. Rev. A. v. 10 ..."    
        while(preg_match('/[,;\. ]\s*V\s*[\. ]\s*([a-z]*\d+[a-z]*)\b/i',$text,$matches)){
            $guess_vol=$matches[1];
            if(preg_match('/(19|20)\d\d/',$guess_vol)) continue;
            $cite->volume=$guess_vol;
            $text=preg_replace('s/[,;\. ]\s*V\s*[\. ]\s*[a-z]*\d+[a-z]\b/i','',$text);
            $cite->{'rest_text'} = $text;
            return;
        }
        // V10
        if(preg_match('/[,;:\. ]\s*V(\d+[a-z]*)\b/i',$text,$matches)) {
            $text=preg_replace('/[,;:\. ]\s*V(\d+[a-z]*)\b/i','',$text);
            $cite->{'volume'} = $matches[1];
            $cite->{'rest_text'} = $text;
            return;
        }
        
     }
    
    function find_supplement() {
        global $cite; 
        $text = $cite->{'rest_text'};

        if(preg_match('/[,;:. ]\s*(?:suppl|supplement)\.?\s*(\d+)\b/i',$text,$matches))  {
            $text=preg_replace('/[,;:. ]\s*(?:suppl|supplement)\.?\s*(\d+)\b/i','',$text);
            $cite->{'supl'} = $matches[1];
            $cite->{'num_of_fig'} = $cite->{'num_of_fig'} - 1;
            $cite->{'rest_text'} = $text;
        }
    }
 
    
    function find_page() {
        global $cite;
        $text = $cite->{'rest_text'};

        # keep the order of the pattern matching.

        # '... p.20, p 20, ...'
        if (preg_match('/[,;:. ]\s*(?:pages|page|pp)\s*[.# ]\s*([a-z]*\d+[a-z]*)\b/i',$text,$matches)) {
            $text=preg_replace('/[,;:. ]\s*(?:pages|page|pp)\s*[.# ]\s*([a-z]*\d+[a-z]*)\b/i','',$text);
            $cite->{'spage'} = $matches[1];
            $cite->{'rest_text'} = $text;
        return;
        }

        # " ... pp20, ..." 
        if (preg_match('/[,;:. ]\s*(?:pages|page|pp)(\d+[a-z]*)\b/i',$text,$matches)) {
            $text=preg_replace('/[,;:. ]\s*(?:pages|page|pp)(\d+[a-z]*)\b/i','',$text);
            $cite->{'spage'} = $matches[1];
            $cite->{'rest_text'} = $text;
            return;
        }                                                              

        # ... p. 1990-1993
        if (preg_match('/[,;. ]\s*(?:p)\s*[. ]\s*([a-z]*\d+[a-z]*)\s*\-\s*[a-z]*d+[a-z]*\b/i',$text,$matches)) {
            $text=preg_replace('/[,;. ]\s*(?:p)\s*[. ]\s*([a-z]*\d+[a-z]*)\s*\-\s*[a-z]*d+[a-z]*\b/i','',$text);
            $cite->{'spage'} = $matches[1];
            $cite->{'rest_text'} = $text;
            return;
        }     

        # Beware "Smith P. 1990, ..., p. 100"
        while(preg_match('/[,;. ]\s*p\s*[. ]\s*([a-z]*\d+[a-z]*)\s*(?!\-)/i',$text,$matches)){
            $guess_page=$matches[1];
            if(preg_match('/(19|20)\d\d/',$guess_vol)) continue;
            $cite->spage=$guess_page;
            $text=preg_replace('s/[,;. ]\s*p\s*[. ]\s*[a-z]*\d+[a-z]*\s*(?!\-)/i','',$text);
            $cite->{'rest_text'} = $text;
            return;
        }
        

        # " ... p20, ..."
        if (preg_match('/[,;:. ]\s*p(\d+[a-z]*)\b/i',$text,$matches)) {
            $text=preg_replace('/[,;:. ]\s*p(\d+[a-z]*)\b/i','',$text);
            $cite->{'spage'} = $matches[1];
            $cite->{'rest_text'} = $text;
            return;
        }
        

    }
    
    
    function find_year() {
        global $cite;
        if(($cite->{'year'})) return 1;
 
        $text = $cite->{'rest_text'};
 
        # priority is given to (1989) type.
        if(preg_match('s/\(((19|20)\d\d)\w?\)/',$text,$matches)){
                $text=preg_replace('s/\(((19|20)\d\d)\w?\)/','',$text);
                $cite->{'year'} = $matches[1];
                $cite->{'rest_text'} = $text;
                return 1;
        }

 
        # year like numbers not before/after a '-'
        # e.g. 1966-1988 may indicate a page range.
        if(preg_match('/[^\w\-"]((19|20)\d\d)\w?([^\w\-"]|$)/i',$text,$matches)){
            $cite->{'year'} = $matches[1];
            $cite->{'rest_text'} = $text;
            return 1;
        }
        return 0;
    }
    
    # Apt'e, C., et al. ACM Transactions on Information Systems 12, 3, 233-251
    function guess_vol_no_pg() {
        global $cite;
        if ($cite->{'volume'} && $cite->{'issue'} &&  $cite->{'spage'}) return 1; 
        //if ($cite->{'num_of_fig'} < 3) return 0;

        $text = $cite->{'rest_text'};

        # change (1,1) alike to ().
        $text = preg_replace('/\(\d+\s*,\s*\d+\s*\)/','\(\)',$text);        
        $text = preg_replace('/\(\d+\s*;\s*\d+\s*\)/','\(\)',$text);

        if(preg_match('/[^\w\/.-](?:volume|vol\.?|v\.?)?\s*([a-z]*?\d+[a-z]*?)#volume[^\w\/.-]+(?:n|no|number|issue|\#)?\.?\s*([a-z]*?\d+[a-z]*?)#issue[^\w\/.-]+(?:pages|page|pp|p)?\.?\s*([a-z]*?\d+[a-z]*?)(?:\s*-\s*[a-z]*?\d+[a-z]*?)?(\W*|$)/i',$text,$matches)) {
     
            $cite->{'volume'} = $matches[1];
            $cite->{'issue'}  = $matches[2];
            $cite->{'spage'}  = $matches[3];
            // $cite->{'jnl_epos'} = length($`) + 1;
                return 1;  
        }
        return 0;
    }
        
    
    function pre_process($text){
        $text = normalisation($text);
        //$text = normalise_date($text);
        //$text = normalise_html($text);

         # "1. Gary Smith, ...." 
        $text=preg_replace('/^\d+\s*\.\s+/','',$text);
        # "1 Gary Smith, ...."
        $text=preg_replace('/^\s*\d+ ([A-Z])/','$1',$text);
        # "2) Brand, P. ..."  
        $text=preg_replace('/^[\[\(]?\s*\w+\s*[\])]\s*/','',$text);   
        //$cite->{'rest_text'} = $Text;    
        return $text;
    }
    
    function normalisation($text) {
        # replace embedded '\n' with ' '
        $text=preg_replace('/^\s+/','',$text);
        $text=preg_replace('/\s+$/','',$text);
        
        $text=preg_replace('/\s+/',' ',$text);# Use single space
        
        $text=preg_replace("/``(.*?)''/",'"$1"',$text);# Replace ``A Paper Title'' with "A Paper Title"
        $text=preg_replace('/\s*-\s*/','-',$text);# remove space around '-'
        $text=preg_replace("/\s*'\s*/","'",$text); # remove space around '
        $text=preg_replace('/\s*:\s*/',':',$text); #remove space around :
        $text=preg_replace('/\(\s+/','(',$text);# ( 1998) ==> (1998)
        $text=preg_replace('/\s+\)/',')',$text) ; # (1998 ) ==> (1998)
        $text=preg_replace('/--+/','-',$text);
        $text=preg_replace('/~/','',$text);# remove '~' (e.g. C.~B.~Hanna)
        $text=preg_replace('/[,;\s]+$/','',$text); # remove last ',;\s' on a line  
        
        return $text;
    }
    
    function find_authors($text){
        global $cite;
        $text=preg_replace('/(&)/','and',$text); 
        $atext=locate_authors($text);
        //echo("ATEXT after Locate Authors: $atext <br>");
        if($atext=='' || preg_match('/^\W+$/',$atext)) return 0;
        
        $chunks=preg_split('/\s*[,;:\&]\s*/',$atext);
        $author=$authors='';
        
        while($chunks){
            //var_dump($chunks);
            if(count($chunks)==1){
                if(!full_name($chunks[0])) break;
                $author=normalize_name($chunks[0]);
                $authors.=':'. $author;
                break;
            }
            //(1) forename and surname are not separated by [,;].
            if(full_name($chunks[0])){
                if(preg_match('/^\s*Jr\.?\s*$/i',$chunks[1])){
                    $author="$chunks[0], $chunks[1]";
                    $author=normalize_name($author);
                    $authors.=":$author";
                    array_splice($chunks,0,2);
                    //if($cite->debug) echo("Author = $author (no comma)<br>");
                    continue;
                }
                elseif(!only_initials($chunks[1])){
                    $author=normalize_name($chunks[0]);
                    $authors.=":$author";
                    //if($cite->debug) echo("Author = $author (no comma)<br>");
                    array_shift($chunks);
                    continue;
                }
            }
            elseif(full_name($chunks[1])){
                //[0] is not a anme...skip
                array_shift($chunks);
                continue;
            }
            
            //(2) forename and surname are separated by [,;].
            // Ignore text containing too many words.
            $afull = "$chunks[0] $chunks[1]";
            if (word_count($afull) > 4) break;
            if(preg_match('/[\d\/]+/',$afull)) break;
            
            # surname first.
            # "Oemler, A., Jr.  and  Lynds, C. R. 1975, ApJ, 199, 558"
            if (count($chunks) > 2) {
                if (is_surname($chunks[0]) && has_initials($chunks[1]) && preg_match('/^\s*Jr\.?\s*$/i',$chunks[2])) {
                    $author = "$chunks[1] $chunks[0], Jr";
                    $author = normalise_name($author);
                    $authors = "$authors:$author";
                    array_splice($chunks, 0, 3); # remove the first three 
                    //if($cite->debug) echo("Author = $author (surname first w Jr)<br>");
                    continue;
                }
            }
            
            # surname first
            # "Reisenegger, A.  and  Miralda-Escude, J. 1995, ApJ, 449, 476 
            if (is_surname($chunks[0]) && has_initials($chunks[1])) {
                if(preg_match('/(.+?\.?)\s*Jr\.?\s*$/i',$chunks[1])) 
                    $author=preg_replace('/(.+?\.?)\s*Jr\.?\s*$/i',"$1 $chunks[0], Jr",$author);
                else $author = "$chunks[1] $chunks[0]"; 
                $author = normalize_name($author);
                $authors = "$authors:$author";
                array_splice($chunks, 0, 2); # remove the first two
                //if($cite->debug) echo("Author = $author (surname first)<br>");
                continue;
            }
            
            # forename first
            if (only_initials($chunks[0]) && is_surname($chunks[1])) { 
                if(preg_match('/(.+?[. ])\s*Jr\.?\s*$/i',$chunks[0]))
                   $author=preg_replace('/(.+?[. ])\s*Jr\.?\s*$/i',"$1 $chunks[1], Jr",$author);
                else $author = $afull ;
                $author = normalize_name($author);
                $authors = "$authors:$author";
                array_splice($chunks, 0, 2); # remove the first two
                //if($cite->debug) echo("Author = $author (forename first)<br>");
                continue;
            }
            
            #  'Liu, Gong', hard to tell which is the surname;
            if (no_initials($chunks[0]) && no_initials($chunks[1])) {
                if (word_count($afull) <= 4 ) {
                    $author = normalize_name($afull);
                    $authors = "$authors:$author";
                    array_splice($chunks, 0, 2); # remove the first two
                    //if($cite->debug) echo("Author = $author (awkward one)<br>");
                    continue;
                }
            }
 
            # cannot determin the author name
            break;
            
            //array_shift($chunks); //remove later
        }//while
        if($authors=='') return 0;
        //if($cite->debug) echo("Authors = $authors <br>");
        $authors=preg_replace('/^:/','',$authors);
        $authors=preg_replace('/^ :/','',$authors);
        $cite->authors=$authors;
    }
    
    function find_first_author ($text) {
        global $cite;
        if (!isset($cite->authors)) return 0;
        $authors=explode(':',$cite->{'authors'});        
        $cite->{'aufull'} = $authors;
        //if( $author =~ /(.*)[\s\._]([^\s\.]+)/ ) {
        $cite->{'aufirst'} = $authors[0];
        $cite->{'aulast'} = $authors[count($authors)-1];
        
    }

    
    function locate_authors($text) {
        $text=preg_replace('/^\s*For .*?review(s)?\W+/i','',$text);
        $text=preg_replace('/^\s*(see )?also /i','',$text);
        $text=preg_replace('/^\s*see[, ]\s*for example\W+/i','',$text);
        $text=preg_replace('/^\s*see e\.g\.\W+/i','',$text);
        $atext=$text;
        
        
        # last author name after 'and'.
        if(preg_match('/[,; ]\s*and ([^,;:]+)[,:;]([^,;:]+)/i',$atext,$matches)) {          
            $atext=preg_replace('/[,; ]\s*and ([^,;:]+)[,:;]([^,;:]+)/i','',$atext);

            $aft1=$matches[1];
            $aft2=$matches[2];
            if(full_name($aft1)) $atext= $atext . ",$aft1";
            else $atext= $atext.",$aft1,$aft2";
        }

    # remove non-alphabets 
        $atext=preg_replace('/^[^a-z]+/i','',$atext);
    
        $atext=preg_replace('/^by /i','',$atext);
        $atext=preg_replace('/[,; ]+and /i',',',$atext);
        $atext=preg_replace('/[,; ]+et\.?\s+al\.?([,; ]+|$)/i',', et al,',$atext);
        //$atext=preg_replace('/[,;:.]+\s*$/','',$atext);
        return $atext;
        
        
    }
  
    
    function only_initials($text){
        if(preg_match('/^[a-z]{2,} /i',$text)) return 0;
        if(preg_match('/\.?\s*[a-z][a-z]+$/i',$text)) return 0;
        $words=preg_split('/[\.\s]/',$text);
        foreach($words as $word) if(strlen($word)>2) return 0;
        return 1;
    }

    
    function has_surname($text){
        if(preg_match('/\d+/',$text)) return 0;
        if(preg_match('/^[a-z]{2,}[\s\-\']/i',$text)) return 1;
        if(preg_match('/[\-\'\s.][a-z][a-z]+(\s+Jr\.?)?\s*$/i',$text)) return 1;
        return 0;
        
    }
    function has_initials($text){
        if(preg_match('/\d+/',$text)) return 0;
        if(preg_match("/^\s*[\']?\s*[A-Z](\s|\.|$)/",$text)) return 1;  
        if(preg_match('/(^|\s|\.)[a-z](\s|\.|$)/i',$text)) return 1;
        return 0;
    }
    
    function is_surname($text)  {
        preg_replace('/ Jr\W+$/i','',$text);
        if(preg_match('/ (e-print|archive)s? /i',$text)) return 0;
        if(preg_match('/\bCollaboration\b/i',$text)) return 0;
                
        if(preg_match("/^(\s*[a-z][\-'a-z]+){1,3}$/i",$text)) return 1;
    
        # return 1 if ($Text =~ /^\s*[a-z]+[\-'a-z]+\s*$/i);

        return 0;
    }
    
      function no_initials($text){
        //do not count 'Jr.
        $text=preg_replace('/(\W)Jr\.?\s*$/i','$1',$text);
        if(preg_match('/(^| )[a-z]\./i',$text)) return 0;
        if(preg_match('/(^| )[a-z]( |$)/i',$text)) return 0;
        return 1;
    }  
    
        function full_name($text){
        $text=preg_replace('/(^|s*)Jr[. ]/i','',$text);
        if(preg_match('/^\s*et al\s*$/i',$text)) return 1;
        if(preg_match('/^in /i',$text)) return 0;
        if(!preg_match('/[A-Z]/',$text)) return 0; //no upper case letter
        if(preg_match('/^((von|van|de|den|der)\s+)+\S\S+\s*$/i',$text)) return 0; //"van Albada" or "van den Bergh" (surname only)
        if(preg_match('/^(von|van|de|den|der)\s+\S\S+\s+([a-z]+\s*)+$/i',$text)) return 1;  //"van Buren D"
        $wcount= word_count($text);
        # 'W. B. Burton', 'Burton W. B.', 'W B Burton', etc.
        if (has_surname($text) && has_initials($text) && $wcount >= 1 && $wcount < 5 ) return 1;
        // 'Vivek Agrawal', 'Liu Xin' types; hard to distinguish
        //  surname/firstname.
        if ($wcount >= 2 && $wcount <= 3  &&  no_initials($text)) return 1;
           
        return 0;

    }
    

    
    function normalize_name ($text){
        //Jr.
        $suffix='';
        if(preg_match('/[, \.]+(Jr|Sr|Snr)\.?\s*$/i',$text,$matches)) $suffix=$matches[1] ;
        elseif(preg_match('/([, \.]+)(Jr|Sr|Snr)[. ]/i',$text,$matches)) $suffix=$matches[2];
        //van der Buren D => D van der Buren
        if(preg_match('/^\s*(((van|von|de|den|der)\s+)+)(\S\S+)\s+(.+)/i',$text,$matches))
                $text="$matches[5] $matches[1] $matches[4]";
        
        $text=preg_replace('/\s+/',' ',$text);
        $text=preg_replace('/^\W+/','',$text);
        $text=preg_replace('/\s+$/','',$text);
        
        //"A. Smith" => "A.Smith"
        $text=preg_replace('/(\w)\.\s+/','$1.',$text);
        
        //Ghisellini G. A. ==> G.A. Ghisellini
        //Konenkov D. Yu. => D.Yu. Konenkov
        if(preg_match('/^([^\s.]{2,})\s+(([A-Z][\w]?\W+)*)([A-Z][\w]?)\W*$/',$text,$matches))
            $text="$matches[2]$matches[4] $matches[1]";
        
        return($text);
    }
    
    
    function word_count ($text) {
        //mainly used to count 'words' in author names
        
        //Do some cleaning
        $text=preg_replace('/(von|van|de|den|der)/','',$text);
        $text=preg_replace('/^\s+/','',$text);
        $text=preg_replace('/\s+$/','',$text);
        $my_words=preg_split('/\s+/',$text);
        $count=0;
        //Ignore initials in names
        foreach($my_words as $word){
            if(!preg_match('/^[a-z]\.?$/i',$word)) $count++;
        }
        return $count;
        
    }   
?>
