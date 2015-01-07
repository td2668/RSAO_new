<?php
   // error_reporting(E_ALL);
    include("includes/config.inc.php");
    include("includes/functions-required.php");
    $tmpl=loadPage("cas_xref", 'CASRAI XRef');
    
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
  
    if(isset($_REQUEST['copy'])){
        if($_REQUEST['old_id']!='' && $_REQUEST['new_id']!=''){
            //duplicate entire type pair
            $sql="SELECT * FROM cas_types_xref WHERE cv_item_type_id='$_REQUEST[cv_item_type_id]' AND cas_type_id='$_REQUEST[cas_type_id]'";
            $type=$db->getRow($sql);
            if(count($type > 0)){
                $sql="SELECT * FROM cas_fields_xref WHERE xref_id='$type[cas_types_xref_id]'";
                $fields=$db->getAll($sql);
                if (count($fields) > 0){
                    //find the target id
                    $sql="SELECT * FROM cas_types_xref WHERE cv_item_type_id='$_REQUEST[old_id]' AND cas_type_id='$_REQUEST[new_id]'";
                    $newtype=$db->getRow($sql);
                    if(!$newtype) {
                        $sql="INSERT INTO cas_types_xref (`cas_type_id`,`cv_item_type_id`,`cas_types_xref_id`) VALUES('$_REQUEST[new_id]','$_REQUEST[old_id]',NULL)";
                        $result=$db->Execute($sql);
                        $insert_id=$db->Insert_ID();
                    }
                    else $insert_id=$newtype['cas_types_xref_id'];
                    foreach($fields as $field){
                            $sql="INSERT INTO cas_fields_xref (`xref_id`,`f`,`n`,`type`) VALUES('$insert_id','$field[f]','$field[n]','$field[type]')";
                            $result=$db->Execute($sql);
                            
                    }
                }
                
            }
        }
    }
    
    if(isset($_REQUEST['deletefield'])){
            $sql="DELETE FROM cas_fields_xref WHERE `f`='$_REQUEST[f]' AND `n`='$_REQUEST[n]' AND `xref_id`='$_REQUEST[xref_id]'";
            $result=$db->Execute($sql);
            $_REQUEST['section']='edit';
            $_REQUEST['id']=$_REQUEST['xref_id'];
    }
    
    if(isset($_REQUEST['new'])){
        $sql="INSERT INTO cas_fields_xref (`xref_id`,`f`,`n`,`type`) VALUES('$_REQUEST[cas_types_xref_id]','$_REQUEST[f2]','$_REQUEST[n2]','$_REQUEST[type]')";
        $result=$db->Execute($sql);  
        $_REQUEST['section']='edit';
        $_REQUEST['id']=$_REQUEST['cas_types_xref_id']; 
    }
    
    
    
    
    if(!isset($_REQUEST['section'])) $_REQUEST['section']="load";
     switch($_REQUEST['section']){
        
         case 'view':
            $tmpl->setAttribute('list','visibility','visible');
            $sql="SELECT cv_item_headers.title as hname, cv_item_headers.category as category, cv_item_types.title, cas_types.type_name, cas_types_xref.* FROM `cas_types_xref` LEFT JOIN cv_item_types USING (cv_item_type_id) LEFT JOIN cas_types USING (cas_type_id) LEFT JOIN cv_item_headers ON (cv_item_types.cv_item_header_id=cv_item_headers.cv_item_header_id) WHERE 1 ORDER BY cv_item_type_id";
            $types=$db->getAll($sql);
            foreach($types as $key=>$type) {
                $sql="SELECT * FROM `cas_fields_xref` WHERE xref_id='$type[cas_types_xref_id]'";
                $fields=$db->getAll($sql);
                $types[$key]['count']=count($fields);
            }
            $tmpl->addRows('mainlist',$types);
         break;
         
         case 'count':
            $tmpl->setAttribute('counts','visibility','visible');
            $sql="SELECT cv_item_headers.title as hname, cv_item_headers.category as category, cv_item_types.* FROM cv_item_types LEFT JOIN cv_item_headers ON (cv_item_types.cv_item_header_id=cv_item_headers.cv_item_header_id) WHERE 1 ORDER BY title";
            $types=$db->getAll($sql);
            //var_dump($types);
            foreach($types as $key=>$type) {
                $sql="SELECT * FROM cas_cv_items WHERE cv_item_type_id='$type[cv_item_type_id]' and converted=1";
                $fields=$db->getAll($sql);
                $types[$key]['done']=count($fields);
                $sql="SELECT * FROM cas_cv_items WHERE cv_item_type_id='$type[cv_item_type_id]' and converted=0";
                $fields2=$db->getAll($sql);
                $types[$key]['todo']=count($fields2);
                $sql="SELECT * FROM cas_cv_items WHERE cv_item_type_id='$type[cv_item_type_id]' AND (n01!='' OR n05!='' OR n22!='' OR n27!='' OR n30!='') AND (n_teaching=0 AND n_scholarship=0 AND n_service=0)";
                $fields3=$db->getAll($sql);
                $types[$key]['no_n']=count($fields3);
                $percent=count($fields)/(count($fields)+count($fields2)) *100;
                if($percent<10)$colour='red'; 
                elseif($percent<90) $colour='black';
                else $colour='green';
                //if($percent==100) {unset($types[$key]); continue;}
                
                //$types[$key]['graph']="<hr size=5 width=$percent% color='$colour' align='left'>";
            }
            $tmpl->addRows('countlist',$types);
         break;
         
         case 'countnew':
            $tmpl->setAttribute('countsnew','visibility','visible');
            $sql="SELECT cas_headings.heading_name as hname, cas_types.* FROM cas_types LEFT JOIN cas_headings ON (cas_types.cas_heading_id=cas_headings.cas_heading_id) WHERE 1 ORDER BY type_name";
            $types=$db->getAll($sql);
            //var_dump($types);
            foreach($types as $key=>$type) {
                $sql="SELECT * FROM cas_cv_items WHERE cas_type_id='$type[cas_type_id]' and converted=1";
                $fields=$db->getAll($sql);
                $types[$key]['done']=count($fields);
                $sql="SELECT * FROM cas_cv_items WHERE cas_type_id='$type[cas_type_id]' and converted=0";
                $fields2=$db->getAll($sql);
                $types[$key]['todo']=count($fields2);
                $sql="SELECT * FROM cas_cv_items WHERE cas_type_id='$type[cas_type_id]' AND (n01!='' OR n05!='' OR n22!='' OR n27!='' OR n30!='') AND (n_teaching=0 AND n_scholarship=0 AND n_service=0)";
                
                $fields3=$db->getAll($sql);
                $types[$key]['no_n']=count($fields3);
                
                $sql="SELECT * FROM cas_cv_items LEFT JOIN users on (cas_cv_items.user_id=users.user_id) LEFT JOIN users_ext on (users.user_id=users_ext.user_id) WHERE cas_type_id='$type[cas_type_id]' AND (n01!='' OR n05!='' OR n22!='' OR n27!='' OR n30!='') AND (n_teaching=0 AND n_scholarship=0 AND n_service=0) AND users_ext.tss=1";
                $fields=$db->getAll($sql);
                $types[$key]['tss']=count($fields);
                
                $sql="SELECT * FROM cas_cv_items LEFT JOIN users on (cas_cv_items.user_id=users.user_id) LEFT JOIN users_ext on (users.user_id=users_ext.user_id) WHERE cas_type_id='$type[cas_type_id]'  AND (n_teaching=0 AND n_scholarship=0 AND n_service=0) AND users_ext.tss=1 AND report_flag=1";
                $fields=$db->getAll($sql);
                $types[$key]['tssactive']=count($fields);
                
                $sql="SELECT * FROM cas_cv_items LEFT JOIN users on (cas_cv_items.user_id=users.user_id) LEFT JOIN users_ext on (users.user_id=users_ext.user_id) WHERE cas_type_id='$type[cas_type_id]'  AND (n_teaching=0 AND n_scholarship=0 AND n_service=0) AND users_ext.tss=0 AND report_flag=1";
                $fields=$db->getAll($sql);
                $types[$key]['tsactive']=count($fields);
                
                
                
                $percent=count($fields)/(count($fields)+count($fields2)) *100;
                if($percent<10)$colour='red'; 
                elseif($percent<90) $colour='black';
                else $colour='green';
                //if($percent==100) {unset($types[$key]); continue;}
                
                //$types[$key]['graph']="<hr size=5 width=$percent% color='$colour' align='left'>";
            }
            $tmpl->addRows('countnewlist',$types);
         break;
         
         case 'edit':
            $tmpl->setAttribute('edit','visibility','visible');
            $sql="SELECT * FROM cas_fields_xref WHERE xref_id='$_REQUEST[id]'";
            $fields=$db->getAll($sql);
            //echo (count($fields));
            if(count($fields>0))  {
                $tmpl->addRows('editrows',$fields);
                $tmpl->setAttribute('editrows','visibility','visible');
            }
            $sql="SELECT  cv_item_types.title, cas_types.type_name, cas_types_xref.* FROM `cas_types_xref` LEFT JOIN cv_item_types USING (cv_item_type_id) LEFT JOIN cas_types USING (cas_type_id) WHERE cas_types_xref_id='$_REQUEST[id]'";
            $type=$db->getRow($sql);
            $tmpl->addVars('edit',$type);
            $sql="SELECT DISTINCT type FROM cas_fields_xref WHERE 1";
            $ftypes=$db->getAll($sql);
            //$ftypes=array('auth','date','text','journal','volume','issue','from','to','Submitted','Accepted','coauth','role','set','confname','publisher','publoc');
            $tmpl->addRows('options',$ftypes);
            
            
            
            
         break;
     
        
    }//switch

 
  
  //$tmpl->addVar('edit','success',$success);
  
  $tmpl->displayParsedTemplate('page');

?>
