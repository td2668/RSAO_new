<?php
	//Routine to fix up the big user-generated lists such as cas_funding_organizations by removing items and reassigning to others. 
	//Works on all existing cas_cv_items records, and cas cv_items_archive records
	//
	
    //error_reporting(E_ALL);
    include("includes/config.inc.php");
    include("includes/functions-required.php");
    include("includes/cv_functions.php");
    $tmpl=loadPage("cas_fix", 'Fix CAS Lists');
    
    $success='';
  
    if(!isset($_REQUEST['section'])) $_REQUEST['section']="choose";
     switch($_REQUEST['section']){
        case 'choose':  
        
        	//print_r($_REQUEST);
        	
        	//Choose the type to fix
        	$sql="SELECT * FROM cas_lists ORDER BY listname";
        	$cas_lists=$db->getAll($sql);
        	if(isset($_REQUEST['cas_list'])) $curr_list=$_REQUEST['cas_list']; else $curr_list='';
        	$list_options='';
        	foreach($cas_lists as $cas_list){
        		if(strcmp($curr_list,$cas_list['listname'])==0) $selected='selected'; else $selected='';
        		$list_options.="<option value='$cas_list[listname]' $selected>$cas_list[listname]</option>\n";
        	}
        	$tmpl->addVar('choose_list','cas_list_options',$list_options);
        	
        	
        	
        	if(isset($_REQUEST['doit'])){
        		if($curr_list != '' AND isset($_REQUEST['from_list']) AND isset($_REQUEST['to_list'])) if($_REQUEST['from_list']!=0 AND $_REQUEST['to_list']!=0) {
        			//This is a bit inefficient, but easier to code and that's what counts today
        			//Grab the list of types and field names where the list occurs
        			$sql="SELECT * FROM cas_field_index WHERE sublist='$curr_list'";
        			$fields_list=$db->getAll($sql);
        			$fresults=array();
        			//Now grab all the cv_items where the types exist AND they are using the requested from_list item
        			$rowcount=0;$arowcount=0;
        			foreach($fields_list as $field){
        				$sql="UPDATE cas_cv_items 
        				SET $field[cas_cv_item_field] = $_REQUEST[to_list]
        				WHERE cas_type_id=$field[cas_type_id] AND $field[cas_cv_item_field]=$_REQUEST[from_list]";
        				$result=$db->Execute($sql);
        				$rows=$db->Affected_Rows();
        				if($rows > 0) $rowcount += $rows;
        				
        				//duplicate for archive
        				$sql="UPDATE cas_cv_items_archive 
        				SET $field[cas_cv_item_field] = $_REQUEST[to_list]
        				WHERE cas_type_id=$field[cas_type_id] AND $field[cas_cv_item_field]=$_REQUEST[from_list]";
        				$result=$db->Execute($sql);
        				$arows=$db->Affected_Rows();
        				if($rows > 0) $arowcount += $rows;
        				
        				//echo'<br>';
        			}//each field list   
        			//echo ("<br>Query affected " . $rowcount . " items.<br>");     			
        			//Then kill the actual entry
        			$sql="DELETE from $curr_list WHERE id=$_REQUEST[from_list]";
        			$result=$db->Execute($sql);
        				//echo($sql);
        				//echo'<br>';
        			//Now swap over so we can see the results
        			$_REQUEST['showresults']='showresults';
        			$_REQUEST['from_list']=$_REQUEST['to_list'];
        			$tmpl->AddVar('count','items',$rowcount);
        			$tmpl->AddVar('count','archiveitems',$arowcount);
        			$tmpl->setAttribute('count','visibility','visible');
        			
        		}//if 
        	
        	}
        	
        	if(isset($_REQUEST['showresults'])){
        	
        		if($curr_list != '' AND isset($_REQUEST['from_list']) ) if($_REQUEST['from_list']!=0) {
        			//This is a bit inefficient, but easier to code and that's what counts today
        			//Grab the list of types and field names where the list occurs
        			$sql="SELECT * FROM cas_field_index WHERE sublist='$curr_list'";
        			$fields_list=$db->getAll($sql);
        			$fresults=array();
        			//Now grab all the cv_items where the types exist AND they are using the requested from_list item
        			foreach($fields_list as $field){
        				$sql="SELECT * FROM cas_cv_items WHERE cas_type_id=$field[cas_type_id] AND $field[cas_cv_item_field]=$_REQUEST[from_list]";
        				$result=$db->getAll($sql);
        				if(count($result)>0) foreach($result as $x) $fresults[]=$x;
        				
        				//echo(count($result));
        				
        			}//each field list
        			//echo"<pre>";
        				//print_r($fresults);
        			if(count($fresults)>0){
        				$outlist='';
        				foreach($fresults as $fresult){
        					$out=formatitem($fresult);
        					$sql="SELECT * FROM cas_types WHERE cas_type_id=$fresult[cas_type_id]";
        					$type=$db->getRow($sql);
        					$sql="SELECT username from users WHERE user_id=$fresult[user_id]";
        					$theuser=$db->getRow($sql);
        					$out.=" (Type: " . $type['type_name'].", User: ".$theuser['username'].")";
        					$outlist.=$out.'<br><br>';
        				}
        				$tmpl->setAttribute('outlist','visibility','visible');
        				$tmpl->addVar('outlist','outlist',$outlist);
        			}//count fresults >0
        			
        			//echo('<pre>');
        			//print_r($fresult);
        			//echo('<br><br>');
        		}//if 
        	
        	}
        	
        	if($curr_list !=''){
        	
        		$sql="SELECT * FROM $curr_list WHERE 1  ORDER BY name";
        		$list=$db->getAll($sql);
        		if(count($list)>0){        		
        		
        			$from_list=$to_list='';
        			if(isset($_REQUEST['from_list'])) $from_list_sel=$_REQUEST['from_list']; else $from_list_sel='';
        			if(isset($_REQUEST['to_list'])) $to_list_sel=$_REQUEST['to_list']; else $to_list_sel='';
        			foreach($list as $item){
        			
        				//Temp addition to add a freq count to the list
	        			$sql="SELECT * FROM cas_field_index WHERE sublist='$curr_list'";
	        			$fields_list=$db->getAll($sql);
	        			$fresults=array();
	        			$numentries=0;
	        			//Now grab all the cv_items where the types exist AND they are using the requested from_list item
	        			/*
	        			foreach($fields_list as $field){
	        				$sql="SELECT cas_type_id FROM cas_cv_items WHERE cas_type_id=$field[cas_type_id] AND $field[cas_cv_item_field]=$item[id]";
	        				$result=$db->getAll($sql);
	        				if(count($result)>0) {$numentries=1; break;}
	        				//$result=$db->RecordCount($sql);
	        				//if($reuslt>0){$numentries=1; break;}
	        				
	        				
	        			}//each field list
        				*/
        			
        				if($from_list_sel==$item['id']) $selected='selected'; else $selected='';
        				$from_list.="<option value='$item[id]' $selected>$item[name] </option>\n";
        				if($to_list_sel==$item['id']) $selected='selected'; else $selected='';
        				$to_list.="<option value='$item[id]' $selected>$item[name]</option>\n";
        			}
        			$tmpl->addVar('fromto_list','from_list_options',$from_list);
        			$tmpl->addVar('fromto_list','to_list_options',$to_list);
        			$tmpl->setAttribute('fromto_list','visibility','visible');
        			
        		}
        	
        	}
        	
        	
            
        break;
        
        case 'edit':
            
           
        break;
        
       
        
    }//switch
  
  
  
  
  
  
 
  
  $tmpl->addVar('edit','success',$success);
  
  $tmpl->displayParsedTemplate('page');
?>
