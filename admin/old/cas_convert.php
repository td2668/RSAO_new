<?php
    //error_reporting(E_ALL);
    include("includes/config.inc.php");
    include("includes/functions-required.php");
    include("includes/cv_functions.php");
    $tmpl=loadPage("cas_convert", 'CASRAI Convert');
    
    $success='';
    //pre-defined set of types for the CV fields used in 'cas_types'
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
    //var_dump($_REQUEST);
    
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
    
    if(isset($_REQUEST['guess']) || isset($_REQUEST['guessandsave'])){
        //check if types are set
        if(isset($_REQUEST['cas_type_id']) && isset($_REQUEST['cv_item_type_id'])){
            $sql="SELECT * FROM `cas_types_xref` WHERE `cas_type_id`='$_REQUEST[cas_type_id]' AND `cv_item_type_id`='$_REQUEST[cv_item_type_id]'";
            $xref=$db->getRow($sql);
            if($xref){
                $sql="SELECT * FROM cas_fields_xref WHERE xref_id='$xref[cas_types_xref_id]'";
                $xrefs=$db->getAll($sql);
                if(count($xrefs > 0)){
                    //load the item
                    $sql="SELECT * FROM `cas_cv_items` WHERE `cv_item_id`='$_REQUEST[cv_item_id]' ";
                    $cv_item=$db->getRow($sql);
                    foreach($xrefs as $key=>$xref){
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
                                    if(cleanText($j)=='Ph.D') $j='Ph.D.';
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
                                    //echo ("Date: ". date('m-d',$cv_item[$xref['f']]));
                                    if(date('m-d',$cv_item[$xref['f']])=='01-01')  $cv_item[$xref['n']]=date('Y-00-00',$cv_item[$xref['f']]);
                                    else $cv_item[$xref['n']]=date('Y-m-00',$cv_item[$xref['f']]);
                                    //echo($cv_item[$xref['n']]. "<br>");
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
                            case 'assistant':
                                if(preg_match('/(assistant)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=1;
                                                               
                            break;
                            case 'supervisor':
                                if(preg_match('/(supervisor)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=11;
                                                               
                            break;
                            case 'switchnames':
                                if(preg_match('/(\w*)\s(\w*)/i',$cv_item[$xref['f']],$matches)) $cv_item[$xref['n']]=$matches[2].'|'.$matches[1];
                                else $cv_item[$xref['n']]=$cv_item[$xref['f']];
                                                               
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
                            
                            case 'setmru':
                                $cv_item[$xref['n']]='MRU';
                            break;
                            
                            case 'textoverflow'://type text
                                (preg_replace('/^([\"\']).*([\"\'])$/','',$cv_item[$xref['f']])) ;
                                if(strlen($cv_item[$xref['f']])<150 ) $cv_item[$xref['n']]=mysql_real_escape_string($cv_item[$xref['f']]);
                                elseif ($cv_item['f09']=='') {
                                    $cv_item['details_teaching']=mysql_real_escape_string($cv_item[$xref['f']]);
                                    $xrefs[$key]['n']='details_teaching';
                                    var_dump($xrefs);
                                }
                                
                            break;
                            
                            case 'iftext':
                                if($cv_item[$xref['n']]=='') $cv_item[$xref['n']]=mysql_real_escape_string(cleanText($cv_item[$xref['f']])) ;
                            break;
                            
                        }//switch
                    }//foreach
                    //save the item
                   // var_dump($xrefs);
                    foreach($xrefs as $xref) {
                        $sql="UPDATE `cas_cv_items` SET $xref[n]='{$cv_item[$xref['n']]}'  WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
                        $result=$db->Execute($sql);
                    }

                    

                    
                }//> 0
            }
        }
        
        /*
        $source="Photogrammetric Engineering And Remote Sensing, 60(10):1243-1251";       
        $cite=new cite();             
        $source=pre_process($source);
        $cite->rest_text=$source;
        echo ("<pre>");
        //find_supplement();
        //echo("SUPPLEMENT: ". $cite->supl);
        guess_vol_no_pg();*
        find_vol_no();
        find_vol($source);
        //find_jnl_name();
        find_page();
        var_dump($cite);
        //$fullname=find_authors($source);
        //find_first_author($source);
       // echo($source . ': '. $fullname);
        //echo("<br>Cite->authors = " . $cite->authors);
        //echo("<br>Cite->aufirst = " . $cite->aufirst);
        //echo("<br>Cite->aulast = " . $cite->aulast);
        //echo("<br>Cite->aufull = " . var_dump($cite->aufull));
        echo("</pre>");
         */    
    }
    
    
    
    if(isset($_REQUEST['prefs'])) if($_REQUEST['prefs']> 0) {
        $_REQUEST['cas_type_id']=$_REQUEST['prefs'];
        //Also reset the heading to keep things below in order
        $sql="SELECT * FROM cas_types WHERE cas_type_id='$_REQUEST[cas_type_id]'";
        $type=$db->getRow($sql);
        $_REQUEST['cas_heading_id']= $type['cas_heading_id'];
    }
    
    if(isset($_REQUEST['deletesub'])){
        if(isset($_REQUEST['delete_item']) && isset($_REQUEST['delete_table'])){
            $sql="DELETE FROM `$_REQUEST[delete_table]` WHERE `id`='$_REQUEST[delete_item]'";
            $result=$db->Execute($sql);
        }
    }
    
    if(isset($_REQUEST['deleteorig'])) if($_REQUEST['deleteorig']!='cancel'){
        //delete original item
        $sql="DELETE FROM cas_cv_items WHERE cv_item_id='$_REQUEST[cv_item_id]'";
        $result=$db->Execute($sql);
    }
    
    /**
    * Save form 
    */
    if(isset($_REQUEST['saveme']) || isset($_REQUEST['saveandclose']) || isset($_REQUEST['guessandsave']) || isset($_REQUEST['savesub']) || isset($_REQUEST['deletesub'])){
        if(isset($_REQUEST["cv_item_id"])){
            //Convert the item using the chosen type
            
            foreach($fieldarray as $fieldname=>$fieldtype){
                switch($fieldtype){
                    case 'Text':
                        if(isset($_REQUEST["{$fieldname}_1"])) {
                            //echo("Checking: {$fieldname}_1". $_REQUEST["{$fieldname}_1"]);
                            //this is an 'author' subtype
                            $last=mysql_real_escape_string($_REQUEST["{$fieldname}_1"]);
                            $first=mysql_real_escape_string($_REQUEST["{$fieldname}_2"]);
                            if($first != '') $$fieldname="$last|$first"; else $$fieldname=$last;
                        } 
                        elseif(isset($_REQUEST[$fieldname])) $$fieldname= mysql_real_escape_string($_REQUEST[$fieldname]);
                        else $$fieldname='';
                        
                    break;
                    case 'Bool':
                        if(isset($_REQUEST[$fieldname])) $$fieldname = 1;
                        else $$fieldname = 0;
                    break;
                    case 'Num':
                        if(isset($_REQUEST[$fieldname])) $$fieldname = intval($_REQUEST[$fieldname]);
                        else $$fieldname = 0;
                    break;
                    case 'List':
                        //first check if 'add' was used - this overrides the field itself, as user is adding to the main list
                        if(isset($_REQUEST["{$fieldname}_add"])){
                            //if both are set then use the existing item.
                            if($_REQUEST["{$fieldname}_add"] != '' && $_REQUEST[$fieldname]=='0') {
                                $contents=$_REQUEST["{$fieldname}_add"];
                                //Figure out what list I'm adding to
                                $sql="SELECT * FROM `cas_field_index` WHERE `cas_type_id`='".addslashes($_REQUEST['cas_type_id'])."' AND `cas_cv_item_field`='$fieldname'";
                                $type=$db->getRow($sql);
                                
                                //check if it already exists
                                $sql="SELECT * FROM `$type[sublist]` WHERE `name`='" . addslashes($contents)."'";
                                $result=$db->getRow($sql);
                                if(!$result){
                                    $contents=addslashes($contents);
                                    $sql="INSERT INTO `$type[sublist]` (`id`,`name`) VALUES(NULL,'$contents')";
                                    $result=$db->Execute($sql);
                                    //echo("REsult of insert is " . var_dump($result));
                                    $$fieldname=$db->Insert_ID();
                                    //and clean out the temp field since the contents have been used
                                    $sql="UPDATE cas_cv_items SET `document_filename`='' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
                                    $result=$db->Execute($sql);
                                }
                                else $$fieldname=$result['id'];
                                $sql="UPDATE cas_cv_items SET `document_filename`='' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
                                $result=$db->Execute($sql);
                            }
                            else {
                                if(isset($_REQUEST[$fieldname])) $$fieldname = $_REQUEST[$fieldname];
                                else $$fieldname='0';
                            }
                        }
                        else {
                                if(isset($_REQUEST[$fieldname])) $$fieldname = $_REQUEST[$fieldname];
                                else $$fieldname='0';
                            }
                        
                    break;
                    case 'Date':
                        if(isset($_REQUEST["{$fieldname}_y"])){
                            $year=$_REQUEST["{$fieldname}_y"];
                            $month=$_REQUEST["{$fieldname}_m"];
                            $day=$_REQUEST["{$fieldname}_d"];
                            $$fieldname="$year-$month-$day";
                        }
                        else $$fieldname='0';
                    break;
                    case 'Sub':
                        //diff since sub returns a set of fields
                        //What set of fields hould I be expecting?
                        $sql="SELECT * FROM `cas_field_index` WHERE `cas_type_id`='$_REQUEST[cas_type_id]' AND `cas_cv_item_field`='$fieldname'";
                        $cas_subfield=$db->getRow($sql);
                        if($cas_subfield){
                            $sql="SELECT * FROM `cas_subtables` WHERE `table_name`='$cas_subfield[subtable]'";
                            $substructure=$db->getRow($sql);
                            //var_dump($substructure);
                            unset($f1); unset($f2); unset($f3);
                            if($substructure['field1_name'] !='') $f1= $_REQUEST["{$fieldname}_{$substructure['field1_name']}"];
                            //if(isset($f1)) echo("F1= $f1 <br>");
                            if($substructure['field2_name'] !='') $f2= $_REQUEST["{$fieldname}_{$substructure['field2_name']}"];
                            //if(isset($f2)) echo("F2= $f2 <br>");
                            if(strlen($substructure['field3_name']) > 0) $f3= $_REQUEST["{$fieldname}_{$substructure['field3_name']}"];
                            //if(isset($f3)) echo("F3= $f3 <br>");
                            //if f1 is blank then dont bother, as this wasn't needed - COULD BE MORE ROBUST
                            
                            
                            //first lets check if it exists
                            if(isset($f2))  $additional=" AND `$substructure[field2_name]`='".addslashes($f2)."'"; else $additional='';               
                            $sql="SELECT * FROM `$cas_subfield[subtable]` WHERE `cv_item_id`='". addslashes($_REQUEST['cv_item_id'])."' AND `fieldname`='$fieldname' AND `$substructure[field1_name]`='".addslashes($f1)."'". $additional;
                            $result=$db->getAll($sql);
                            //var_dump($result);
                            if(count($result)==0){
                           
                            
                            
                                if($f1 != ''){
                                    $sql="INSERT INTO `$cas_subfield[subtable]` (`id`, `cv_item_id`, `fieldname`" .
                                         (isset($f1) ? ", `$substructure[field1_name]`" : '' ) .
                                         (isset($f2) ? ", `$substructure[field2_name]`" : '' ) .
                                         (isset($f3) ? ", `$substructure[field3_name]`" : '' ) .
                                         ") VALUES(NULL, '$_REQUEST[cv_item_id]', '$fieldname'" .
                                         (isset($f1) ? ", '".addslashes($f1)."'" : '' ) .
                                         (isset($f2) ? ", '".addslashes($f2)."'" : '' ) .
                                         (isset($f3) ? ", '".addslashes($f3)."'" : '' ) .
                                         ")";
                                         
                                    $result=$db->Execute($sql);
                                    $id=$db->Insert_ID();
                                    //echo("Just wrote to the db for subtable $fieldname with $id<br>");
                                    
                                }
                            }
                        }// if the type is used
                        //the zero means nothing - just used to help keep things standardized
                       $$fieldname=0; 
                    break;
                    
                
                }//switch
            }//foreach fieldarray
            
            
            //Build the UPDATE; by now all the fields should be set. (Could do above but here it's more obvious)
            
            $sql="UPDATE `cas_cv_items` SET ";
            foreach($fieldarray as $fieldname=>$fieldtype) {
                if($fieldname=='n01') $sql.="`$fieldname`='{$$fieldname}' ";
                else $sql.=", `$fieldname`='{$$fieldname}' ";
            }
            

            $sql.=" WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
            $result=$db->Execute($sql);
            
            //Do the last 3 
            if($_REQUEST['n_teaching']) $_REQUEST['n_teaching']=1; else $_REQUEST['n_teaching']=0;
            if($_REQUEST['n_scholarship']) $_REQUEST['n_scholarship']=1; else $_REQUEST['n_scholarship']=0;
            if($_REQUEST['n_service']) $_REQUEST['n_service']=1; else $_REQUEST['n_service']=0;
            $sql="UPDATE `cas_cv_items` SET
            `n_teaching`='$_REQUEST[n_teaching]', 
            `n_scholarship`='$_REQUEST[n_scholarship]', 
            `n_service`='$_REQUEST[n_service]', 
            `details_teaching`='".mysql_real_escape_string($_REQUEST['details_teaching'])."', 
            `details_scholarship`='".mysql_real_escape_string($_REQUEST['details_scholarship'])."',
            `details_service`='".mysql_real_escape_string($_REQUEST['details_service'])."' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
            $result=$db->Execute($sql);
            //echo ("$sql <br>");
        }        
    }
    
    //Set the type no matter what - since I could have selected a new type.
    if(isset($_REQUEST['cas_type_id']) && isset($_REQUEST['cv_item_id'])){
        $sql="UPDATE `cas_cv_items` SET `cas_type_id`=$_REQUEST[cas_type_id] WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
        $result=$db->Execute($sql);
    }
    /**
    * Save form and finish with item (save perform already)
    */
    if(isset($_REQUEST['saveandclose']) || isset($_REQUEST['guessandsave'])){
        $sql="UPDATE `cas_cv_items` SET `converted`='1' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
        $result=$db->Execute($sql);
    }
    
    if(isset($_REQUEST['shelve'])){
        $sql="UPDATE `cas_cv_items` SET `converted`='2' WHERE `cv_item_id`='$_REQUEST[cv_item_id]'";
        $result=$db->Execute($sql);
    }
    
    /**
    * Save a new subtable item (and everything else)
    */
    if(isset($_REQUEST['savesub'])){
        //echo "savesub";
    }
    
    
    if(!isset($_REQUEST['section'])) $_REQUEST['section']="load";
     switch($_REQUEST['section']){

        
        case 'convert':
            //pick and load the next item in the cv_type that is not yet converted
            
            //Chooser for new_type
            
            if(isset($_REQUEST['cas_type_id'])) {
                //If type already chosen then serve data
            }
            
        
        break;
        
        case 'choose_type':
            //Just use a chooser to pick the source type (cv_type)
           
        break;
        
        case 'load':
        //Load data into a rudimentary form. No checks
            
            
            
            //Pick a source data type first
            $sql="SELECT cv_item_headers.title as hname, cv_item_headers.category as category, cv_item_types.* FROM `cv_item_types` LEFT JOIN `cv_item_headers` ON (cv_item_types.cv_item_header_id=cv_item_headers.cv_item_header_id) WHERE 1 ORDER BY `title_plural`, `rank`";
            $cv_types=$db->getAll($sql);
            $cv_options="<select name='cv_item_type_id' onchange='document.form1.submit();'><option value='0'></option>\n";
            foreach($cv_types as $cv_type){
                $sql="SELECT cv_item_id FROM cas_cv_items WHERE cv_item_type_id=$cv_type[cv_item_type_id] AND (n_teaching=0 AND n_scholarship=0 AND n_service=0) AND report_flag=1";
                $list=$db->getAll($sql);
               // echo("$cv_type[]")
                if(count($list)==0) continue;
                $selected='';
                if(isset($_REQUEST['cv_item_type_id'])) if($_REQUEST['cv_item_type_id']==$cv_type['cv_item_type_id']) $selected='selected';
                $cv_options.="<option value='$cv_type[cv_item_type_id]' $selected >$cv_type[title_plural] ($cv_type[category]-$cv_type[hname])</option>\n";
            }
            $cv_options.="</select>\n";
            if(isset($_REQUEST['cv_item_type_id'])) $cv_options.=" ($_REQUEST[cv_item_type_id])";
            $tmpl->addVar('load','cv_options',$cv_options);
            
            
            //The preferred item item chooser
            $sql="SELECT * FROM `cas_types_xref` WHERE `cv_item_type_id`='$_REQUEST[cv_item_type_id]'";
            
                
            // 
            if(isset($_REQUEST['cv_item_type_id'])) {

                //The preferred item item chooser
                $sql="SELECT * FROM `cas_types_xref` LEFT JOIN cas_types ON(cas_types_xref.cas_type_id=cas_types.cas_type_id) WHERE `cv_item_type_id`='$_REQUEST[cv_item_type_id]' ORDER BY cas_types_xref.cas_type_id";
                $xrefs=$db->getAll($sql);
                if(count($xrefs)> 0){
                    $tmpl->addVar('load','head3','Preferred Types');
                    $out="<SELECT name='prefs' onchange=\"javascript: document.form1.submit();\" ><option value='0'></option>";
                    foreach ($xrefs as $xref){
                        $out.="<option value='$xref[cas_type_id]'>$xref[type_name]</option>\n";
                    }
                    $out.="</select>\n";
                    $tmpl->addVar('load','prefs',$out);
                }
                
                
                //load the next data to process
                $sql="SELECT * FROM `cas_cv_items` WHERE `cv_item_type_id`='$_REQUEST[cv_item_type_id]' AND (n_teaching=0 AND n_scholarship=0 AND n_service=0) AND report_flag=1 ORDER BY `cv_item_id` DESC LIMIT 1";
                $cv_item=$db->getRow($sql);
                $tmpl->addVar('load','cv_item_id',$cv_item['cv_item_id']);
                //get the user
                $sql="SELECT * FROM `users` LEFT JOIN users_ext on (users.user_id=users_ext.user_id) WHERE users.user_id='$cv_item[user_id]'";
                $user=$db->getRow($sql);
                if($user['tss']) $stream='Scholarship'; else $stream='Teaching';
                $username="$user[last_name], $user[first_name] <b>$stream</b>";
                $tmpl->addVar('load','username',$username);
                
                $sql="SELECT * FROM cas_cv_items WHERE cv_item_type_id='$_REQUEST[cv_item_type_id]' AND (n_teaching!=0 OR n_scholarship=!0 OR n_service!=0) AND report_flag=1";
                $done=$db->getAll($sql);
                $sql="SELECT * FROM cas_cv_items WHERE cv_item_type_id='$_REQUEST[cv_item_type_id]' AND (n_teaching=0 AND n_scholarship=0 AND n_service=0) AND report_flag=1";
                $todo=$db->getAll($sql);
                $tmpl->addVar('load','numdone',count($done));
                $tmpl->addVar('load','numtodo',count($todo));
                
                if(isset($_REQUEST['cas_type_id'])){
                    $sql="SELECT * FROM `cas_types` WHERE `cas_type_id`='$_REQUEST[cas_type_id]'";
                    $type=$db->getRow($sql);
                }
                //Set up the source data rows
                $sql="SELECT * FROM `cv_item_types` WHERE `cv_item_type_id` = '$cv_item[cv_item_type_id]'";
                $cv_type=$db->getRow($sql);
                $orig_rows='';
                $cv_item['f1']=htmlentities($cv_item['f1'],ENT_QUOTES);
                if($cv_type['f1_name'] != '') $orig_rows.="<tr><td>f1: $cv_type[f1_name]</td><td><input type='text' size='50' value='$cv_item[f1]'></td></tr>";
                if($cv_type['f2_name'] != '') {
                    $date=0;
                    if($cv_item['f2'] != 0) {
                        if($cv_type['f2_type']=='year') $date=date('Y',$cv_item['f2']);
                        elseif($cv_type['f2_type']=='month') $date=date('Y-n',$cv_item['f2']);
                        
                    }
                   $orig_rows.="<tr><td>f2: $cv_type[f2_name]</td><td><input type='text' size='10' value='$date'></td></tr>"; 
                }
                if($cv_type['f3_name'] != '') {
                    $date=0;
                    if($cv_item['f3'] != 0) {
                        if($cv_type['f3_type']=='year') $date=date('Y',$cv_item['f3']);
                        elseif($cv_type['f3_type']=='month') $date=date('Y-n',$cv_item['f3']);
                        
                    }
                   $orig_rows.="<tr><td>f3: $cv_type[f3_name]</td><td><input type='text' size='10' value='$date'></td></tr>"; 
                }
                /*
                if($cv_type['f4_name'] != '') $orig_rows.="<tr><td>$cv_type[f4_name]</td><td><input type='text' size='50' value='$cv_item[f4]'></td></tr>";
                if($cv_type['f5_name'] != '') $orig_rows.="<tr><td>$cv_type[f5_name]</td><td><input type='text' size='50' value='$cv_item[f5]'></td></tr>";
                if($cv_type['f6_name'] != '') $orig_rows.="<tr><td>$cv_type[f6_name]</td><td><input type='text' size='50' value='$cv_item[f6]'></td></tr>";
                if($cv_type['f7_name'] != '') $orig_rows.="<tr><td>$cv_type[f7_name]</td><td><input type='text' size='50' value='$cv_item[f7]'></td></tr>";
                if($cv_type['f8_name'] != '') $orig_rows.="<tr><td>$cv_type[f8_name]</td><td><input type='text' size='50' value='$cv_item[f8]'></td></tr>";
                if($cv_type['f9_name'] != '') $orig_rows.="<tr><td>$cv_type[f9_name]</td><td><textarea rows='4' cols='50'>$cv_item[f9]</textarea></td></tr>";
                */
                $cv_item['f4']=htmlentities($cv_item['f4'],ENT_QUOTES);
                $orig_rows.="<tr><td>f4: $cv_type[f4_name]</td><td><textarea cols='50' rows='2'>$cv_item[f4]</textarea></td></tr>\n";
                $orig_rows.="<tr><td>f5: $cv_type[f5_name]</td><td><textarea cols='50' rows='2'>$cv_item[f5]</textarea></td></tr>\n";
                $orig_rows.="<tr><td>f6: $cv_type[f6_name]</td><td><input type='text' size='50' value='$cv_item[f6]'></td></tr>\n";
                $orig_rows.="<tr><td>f7: $cv_type[f7_name]</td><td><input type='text' size='50' value='$cv_item[f7]'></td></tr>\n";
                $orig_rows.="<tr><td>f8: $cv_type[f8_name]</td><td><textarea  cols='50' rows='4'>$cv_item[f8]</textarea></td></tr>\n";
                $orig_rows.="<tr><td>f9: $cv_type[f9_name]</td><td><textarea rows='4' cols='50'>$cv_item[f9]</textarea></td></tr>\n";
                $cv_item['f10']= ($cv_item['f10']) ? 'checked' : '';
                $orig_rows.="<tr><td>f10: $cv_type[f10_name]</td><td><input type='checkbox' $cv_item[f10] ></td></tr>\n";
                $cv_item['f11']= ($cv_item['f11']) ? 'checked' : '';
                $orig_rows.="<tr><td>f11: $cv_type[f11_name]</td><td><input type='checkbox' $cv_item[f11] ></td></tr>\n";
                $orig_rows.="<tr><td></td></tr>\n";
                $orig_rows.="<tr><td colspan='3'>
                            <button onClick='javascript: var answer=confirm(\"Are you sure?\"); if(!answer) this.value=\"cancel\";' type='submit' name='deleteorig' value='deleteorig'>Delete Me</button>&nbsp;&nbsp;&nbsp;
                            <button onClick='document.form1.submit();' type='submit' name='saveme' value='saveme'>Save</button>&nbsp;&nbsp;&nbsp;
                            <button onClick='document.form1.submit();' type='submit' name='saveandclose' value='saveandclose'>Save/Close</button>&nbsp;&nbsp;&nbsp;
                            <button onClick='document.form1.submit();' type='submit' name='shelve' value='saveandclose'>Shelve</button>&nbsp;&nbsp;&nbsp;
                
                            <button onClick='document.form1.submit();' type='submit' name='guess' value='guess'>Convert</button>
                            </td></tr>
                           \n";
                $tmpl->addVar('load','orig_rows',$orig_rows);
                
                
                
                //The preferred item chooser
                /*
                $sql="SELECT * FROM `cas_types_xref` WHERE `cv_item_type_id`='$_REQUEST[cv_item_type_id]'";
                $prefs=$db->getAll($sql);
                if(count($prefs > 0)){
                    $prefs_sel="<select name='prefs_sel_id' onchange='document.form1.submit();'>\n";
                    
                    foreach($prefs as $pref) {
                        $sql="SELECT * FROM `cas_types` WHERE `cas_type_id`='$pref[cas_type_id]'";
                        $type=$db->getRow($sql);
                        $prefs_sel.="<option value='$pref[cas_type_id]'>$type[type_name]</option>\n";
                    }
                    $prefs_sel.="</select>\n";
                    $tmpl->addVar('load','prefs_sel',$prefs_sel);
                }
                */
                
                //if there's already a heading id set then use it instead.
                //If the header ID that comes in doesn't match the type ID, then the user selected a new header and we have to deliver a blank
                    //form with those values.
                if(isset($_REQUEST['cas_type_id'])){
                    $sql="SELECT * FROM `cas_types` WHERE `cas_type_id`=$_REQUEST[cas_type_id]";
                    $curtype=$db->getRow($sql);
                    if($_REQUEST['cas_heading_id'] != $curtype['cas_heading_id']) {
                        unset ($_REQUEST['cas_type_id']);
                        //remove the type from the saved item
                        $cv_item['cas_type_id']=0;
                    }
                    if($cv_item['cas_type_id'] > 0){
                        
                        $_REQUEST['cas_type_id']=$cv_item['cas_type_id'];
                        $sql="SELECT * FROM cas_types WHERE cas_type_id=$_REQUEST[cas_type_id]";
                        $curtype=$db->getRow($sql);
                        $_REQUEST['cas_heading_id']=$curtype['cas_heading_id'];
                    } 
                }
                
                $sql="SELECT * FROM `cas_headings` WHERE 1 ORDER BY `order`";
                $headings=$db->getAll($sql);
                $heading_options="<select name='cas_heading_id' onchange='document.form1.submit();'><option value='0'></option>"; 
                foreach($headings as $heading){
                    $selected='';
                    if(isset($_REQUEST['cas_heading_id'])) if($_REQUEST['cas_heading_id']==$heading['cas_heading_id']) $selected='selected';
                    $heading_options.="<option value='$heading[cas_heading_id]' $selected >$heading[heading_name]</option>\n";
                }
                $heading_options.="</select>";
                $tmpl->addVar('load','heading_options',$heading_options);
                $tmpl->addVar('load','head1','Choose a Heading');
                if(isset($_REQUEST['cas_heading_id'])) if($_REQUEST['cas_heading_id'] > 0){
                    $sql="SELECT * FROM `cas_types` WHERE `cas_heading_id`='$_REQUEST[cas_heading_id]' ORDER BY `order`";
                    $types=$db->getAll($sql);
                    if(count($types) > 0){
                        $type_chooser="<select name='cas_type_id' onChange='javascript:document.form1.submit();'><option value='0'></option>\n";
                        foreach($types as $type){
                            $selected='';
                            if(isset($_REQUEST['cas_type_id'])) if($_REQUEST['cas_type_id']==$type['cas_type_id']) { $selected='selected';$current=$type['type_name'];}
                            $type_chooser.="<option value='$type[cas_type_id]' $selected >$type[type_name]</option>\n";
                        }//foreach
                        $type_chooser.="</select>\n";
                        $tmpl->addVar('load','type_chooser',$type_chooser);
                        $tmpl->addVar('load','head2','Choose a Type');
                    }//count > 0
                    
                } //isset cas_heading_id
            }// isset cv_item_type_id
            
            if(isset($_REQUEST['cas_type_id'])){
                //Check if there is a x-ref for conversion. If so, enable the convert button
                
                
                
                $sql="SELECT * FROM `cas_field_index` WHERE `cas_type_id`='$_REQUEST[cas_type_id]' ORDER BY `order`";
                $fields=$db->getAll($sql);
                if(count($fields > 0)) {
                    $rows="<tr><td colspan='2'><b>Viewing: <u>$current ($_REQUEST[cas_type_id])</u></b></td></tr>";
                    foreach($fields as $field){
                        switch($field['type']){
                            case "Text":
                                if($field['subtype']=='author'){
                                    //author type shows as two subfields. In the db it is LASTNAME|FIRSTNAME
                                    $out=array();
                                    if(stripos($cv_item[$field['cas_cv_item_field']],'|')) {
                                        $out=explode('|',$cv_item[$field['cas_cv_item_field']]);
                                        $out[0]=htmlentities($out[0],ENT_QUOTES);
                                        $out[1]=htmlentities($out[1],ENT_QUOTES);
                                    }             
                                    else {$out[0]=htmlentities($cv_item[$field['cas_cv_item_field']],ENT_QUOTES); $out[1]='';}
                                    $rows.="<tr><td width='180' style='border-top: 1px solid black;'>$field[cas_cv_item_field]: $field[field_name]</td><td style='border-top: 1px solid black;'>
                                        Last: <input type='text' name='$field[cas_cv_item_field]_1' size='15' value='$out[0]'\>
                                        First: <input type='text' name='$field[cas_cv_item_field]_2' size='15' value='$out[1]'\></td></tr>\n";
                                }
                                else {
                                    $contents=htmlentities($cv_item[$field['cas_cv_item_field']],ENT_QUOTES);
                                    $rows.="<tr ><td width='180' style='border-top: 1px solid black;'>$field[cas_cv_item_field]: $field[field_name]</td><td style='border-top: 1px solid black;'><textarea name='$field[cas_cv_item_field]' cols='50' rows='2'>$contents</textarea></td></tr>\n";
                                }
                            break;
                            
                            case "Num":
                                $rows.="<tr><td style='border-top: 1px solid black;'>$field[cas_cv_item_field]: $field[field_name]</td><td style='border-top: 1px solid black;'><input type='text' name='$field[cas_cv_item_field]' size='5' value='{$cv_item[$field['cas_cv_item_field']]}'> <i>Number Only</i></td></tr>\n";
                            break;
                            
                            case "Bool":
                                $cv_item[$field['cas_cv_item_field']]= ($cv_item[$field['cas_cv_item_field']]) ? 'checked':'';
                                $rows.="<tr><td style='border-top: 1px solid black;'>$field[cas_cv_item_field]: $field[field_name]</td><td style='border-top: 1px solid black;'><input type='checkbox' name='$field[cas_cv_item_field]' {$cv_item[$field['cas_cv_item_field']]}></td></tr>\n";
                            break;
                            
                            case "Date":
                                $fdate=unpackDate($cv_item[$field['cas_cv_item_field']],false,true);
                                //year select
                                $yoptions="<select name='$field[cas_cv_item_field]_y' id='$field[cas_cv_item_field]_y'>\n";
                                if($fdate['year']==0) $selected='selected=selected'; else $selected='';
                                $yoptions.="<option value='0' $selected>n/a</option>\n";
                                for($x=2020; $x>=1960; $x--){
                                    if($x==$fdate['year']) $selected='selected=selected'; else $selected='';
                                    $yoptions.="<option value='$x' $selected>$x</option>\n";
                                }
                                $yoptions.="</select>\n";
                                
                                //month select
                                $moptions="<select name='$field[cas_cv_item_field]_m' id='$field[cas_cv_item_field]_m'>\n";
                                if($fdate['month']=='0') $selected='selected=selected'; else $selected='';
                                $moptions.="<option value='0' $selected>n/a</option>\n";
                                //echo($fdate['month']. '<br>');
                                for($x=1; $x<=12; $x++){
                                    if($x==intval($fdate['month'])) $selected='selected=selected'; else $selected='';
                                    $moptions.="<option value='$x' $selected>$x</option>\n";
                                }
                                $moptions.="</select>\n";
                                
                                //day select
                                $doptions="<select name='$field[cas_cv_item_field]_d' id='$field[cas_cv_item_field]_d'>\n";
                                if($fdate['day']==0) $selected='selected=selected'; else $selected='';
                                $doptions.="<option value='0' $selected>n/a</option>\n";
                                for($x=1; $x<=31; $x++){
                                    if($x==$fdate['day']) $selected='selected=selected'; else $selected='';
                                    $doptions.="<option value='$x' $selected>$x</option>\n";
                                }
                                $doptions.="</select>\n";
                                
                                $rows.="<tr><td style='border-top: 1px solid black;'>
                                $field[cas_cv_item_field]: $field[field_name]</td>\n
                                <td style='border-top: 1px solid black;'>
                                $yoptions  $moptions $doptions  </td></tr>\n";
                            break;
                            
                            case "List":
                                //Simply a drop-down to choose from
                                if ($field['sublist']=='cas_currency_types') $order='`country_name`,`name`'; else $order='`name`';
                                //if its the big funding org table then just grab the ones already used
                                
                                $sql="SELECT * FROM `$field[sublist]` ORDER BY $order";
                                $list=$db->getAll($sql);
                                                            
                                if(count($list)>0){
                                    $rows.="<tr><td style='border-top: 1px solid black;'>$field[cas_cv_item_field]: $field[field_name]</td>\n<td style='border-top: 1px solid black;'><select name='$field[cas_cv_item_field]'><option value='0'></option>\n";
                                    
                                    foreach($list as $item){
                                        if($item['id']==$cv_item[$field['cas_cv_item_field']]) $selected='selected'; else $selected='';
                                        
                                        if ($field['sublist']=='cas_currency_types'){
                                             $item['name']= $item['country_name'].': '.$item['name'];
                                             if($item['locale']!='') $item['name'].='**';
                                        }
                                        $rows.="<option value='$item[id]' $selected >$item[name]</option>\n";
                                    }
                                    $rows.="</select>\n";
                                    //but there's also the modify flag - allows manual entry of a new list item 
                                    if($field['list_add']) {
                                        if($cv_item['document_filename']!= '') $cont=htmlentities($cv_item['document_filename'],ENT_QUOTES); else $cont='';
                                        $rows.=" Add: <input type='text' value='$cont' name='$field[cas_cv_item_field]_add' size='70'/>\n";
                                    }
                                    $rows.="</td></tr>\n";
                                }
                            break;
                            
                            case "Sub":
                                //build list; need separate save function and delete function
                                //subtable structure is specified in the cas_subtables table; they always have 'id' and 'user_id' as well
                                $sql="SELECT * FROM `cas_subtables` WHERE `table_name`='$field[subtable]'";
                                $substructure=$db->getRow($sql);
                                if($substructure){
                                    $sql="SELECT * FROM `$substructure[table_name]` WHERE `cv_item_id`='$cv_item[cv_item_id]' AND `fieldname`='$field[cas_cv_item_field]'";
                                    $subtable=$db->getAll($sql);
                                    $output="<table  border='1' cellpadding='3' style='border-collapse: collapse' bordercolor='#FFFFFF' cellspacing='1'>";
                                    $output.="<tr><td bgcolor='#CCCCCC'><b>$substructure[field1_display]</b></td><td bgcolor='#CCCCCC'><b>$substructure[field2_display]</b></td><td bgcolor='#CCCCCC'><b>$substructure[field3_display]</b></td></tr>\n";
                                    if(count($subtable)>0){                                      
                                        foreach($subtable as $item){
                                            if($substructure['field1_name']!='') $f1=$item[$substructure['field1_name']]; else $f1='';
                                            if($substructure['field2_name']!='') $f2=$item[$substructure['field2_name']]; else $f2='';
                                            if($substructure['field3_name']!='') $f3=$item[$substructure['field3_name']]; else $f3='';
                                            
                                           $output.="<tr><td bgcolor='#CCCCCC'>$f1</td><td bgcolor='#CCCCCC'>$f2</td><td bgcolor='#CCCCCC'>$f3</td><td><button type='submit' name='deletesub' value='deletesub' onClick=\"javascript:document.form1.delete_table.value='$field[subtable]'; document.form1.delete_item.value='$item[id]'; document.form1.submit();\" >Delete</button></td></tr>\n";
                                        }
                                        
                                    }//if count > 0
                                    $output.="<tr>";
                                    $output.= ($substructure['field1_name']=='') ? '<td></td>' : "<td><input type='text' size='20' name='$field[cas_cv_item_field]_{$substructure['field1_name']}'/></td>\n";
                                    $output.= ($substructure['field2_name']=='') ? '<td></td>' : "<td><input type='text' size='20' name='$field[cas_cv_item_field]_{$substructure['field2_name']}'/></td>\n";
                                    $output.= ($substructure['field3_name']=='') ? '<td></td>' : "<td><input type='text' size='20' name='$field[cas_cv_item_field]_{$substructure['field3_name']}'/></td>\n";
                                    $output.="<td><button type='submit' value='savesub' name='savesub' >Save</button></tr></table>";
                                    $rows.="<tr><td style='border-top: 1px solid black;'  valign='top'>$field[cas_cv_item_field]: $field[field_name]</td><td style='border-top: 1px solid black;'>$output</td></tr>\n";
                                }
                            break;
                            
                        }//switch
                    }//foreach field
                    //3 fields always show to allow transfer of data from details.
                    if($cv_item['n_teaching']) $checked_t='checked'; else $checked_t='';
                    if($cv_item['n_scholarship']) $checked_sc='checked'; else $checked_sc='';
                    if($cv_item['n_service']) $checked_se='checked'; else $checked_se='';
                    $rows.="<tr><td style='border-top: 1px solid black;'  valign='top'>Details: Teaching:</td><td style='border-top: 1px solid black;'><textarea rows='2' cols='75' name='details_teaching'>$cv_item[details_teaching]</textarea> <input type='checkbox' name='n_teaching' $checked_t /></td></tr>\n";
                    $rows.="<tr><td style='border-top: 1px solid black;'  valign='top'>Details: Scholarship:</td><td style='border-top: 1px solid black;'><textarea rows='2' cols='75' name='details_scholarship'>$cv_item[details_scholarship]</textarea> <input type='checkbox' name='n_scholarship' $checked_sc /></td></tr>\n";
                    $rows.="<tr><td style='border-top: 1px solid black;'  valign='top'>Details: Service:</td><td style='border-top: 1px solid black;'><textarea rows='2' cols='75' name='details_service'>$cv_item[details_service]</textarea> <input type='checkbox' name='n_service' $checked_se /></td></tr>\n";
                    
                    $tmpl->addVar('load','format',formatitem($cv_item,'apa','report'));
                    
                    $tmpl->addVar('load','rows',$rows);
                }//count > 0
            }
        
        break;
    }//switch
  
  /* Date Chooser Code
  <img src='/includes/calendar.gif'  align='absmiddle' onclick='showChooser(this, "post_date", "chooserSpan", 2000, 2012, "d/m/Y", false);'>
        <div id='chooserSpan' class='dateChooser select-free' style='display: none; visibility: hidden; width: 166px;'>
</div>
*/
  
  
  
  
  
  
  
  //$tmpl->addVar('edit','success',$success);
  
  $tmpl->displayParsedTemplate('page');
  
 
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
