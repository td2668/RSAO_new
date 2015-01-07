<?php


function eval_display_code($display_code,$item) {
	$allowedCalls= explode(',',
		'explode,implode,date,time,round,trunc,rand,ceil,floor,srand,getdate,'.
		'strtolower,strtoupper,substr,stristr,strpos,print,print_r,'.
		'f1,f2,f3,f4,f5,f6,f7,f8,f9,f10,f11,f12,item,output,cv_item_id');
	$output='';


	$parseErrors = array();
	$tokens = token_get_all('<?'.'php '.$display_code.' ?'.'>');
	$vcall = '';

	foreach ($tokens as $token) {
	if (is_array($token)) {
		$id = $token[0];
		switch ($id) {
			case(T_VARIABLE): { $vcall .= 'v'; break; }
			case(T_STRING): { $vcall .= 's'; }
			case(T_REQUIRE_ONCE): case(T_REQUIRE): case(T_NEW): case(T_RETURN):
			case(T_BREAK): case(T_CATCH): case(T_CLONE): case(T_EXIT):
			case(T_PRINT): case(T_GLOBAL): case(T_ECHO): case(T_INCLUDE_ONCE):
			case(T_INCLUDE): case(T_EVAL): case(T_FUNCTION): {
			  if (array_search($token[1], $allowedCalls) === false)
				$parseErrors[] = 'illegal call: '.$token[1];
			}
		}
	}
	else
		$vcall .= $token;  
	}

	if (stristr($vcall, 'v(') != '')
		$parseErrors[] = array('illegal dynamic function call');
	$cv_item_id=$item['cv_item_id'];
	//if($item['f2']==0) $item['f2']="";
	//if($item['f3']==0) $item['f3']="";
   //print_r($item);echo "<br><br>";
	if($display_code!="")
		if(sizeof($parseErrors) == 0)
			eval($display_code);
		else $output='error: the display_code of selected item type contains errors.<br />
						 <i>'.implode(", ",$parseErrors).'</i>';

        
	return $output;
}

function research_list() {
	global $db;

	$tmpl=loadPage("myactivities_research_home","My Activities","my_research");
	$username=sessionLoggedUser();
	$sql="SELECT * FROM users WHERE username = \"$username\"";
	$user=$db->GetRow($sql);

	if(is_array($user)==false or count($user)==0) {
			displayBlankPage("Error","<h1>Error</h1>Couldnt locate current user in the database.");
			die;
	}
	$user_id=$user["user_id"];

	if(isset($_POST['action'])) if($_POST["action"]=="update_research_list") {
		$sql="SELECT cv_item_id,web_show,current_par
				FROM cv_items
				WHERE cv_items.user_id=$user_id  ";

		$items=$db->getAll($sql);
		if($items)
		foreach($items as $item) {
			$cv_item_id=$item["cv_item_id"];
			if($_POST["item_{$cv_item_id}_cv"]=="checked" and $item["current_par"]==0
			or $_POST["item_{$cv_item_id}_cv"]!="checked" and $item["current_par"]==1
			or $_POST["item_{$cv_item_id}_profile"]=="checked" and $item["web_show"]==0
			or $_POST["item_{$cv_item_id}_profile"]!="checked" and $item["web_show"]==1) {
				//database and posted info are not the same, update needed
				$sql="UPDATE cv_items SET
								current_par=".($_POST["item_{$cv_item_id}_cv"]=="checked" ? 1 : 0).",
								web_show=".($_POST["item_{$cv_item_id}_profile"]=="checked" ? 1 : 0)."
								WHERE user_id=$user_id AND cv_item_id=$cv_item_id ";

				if($db->Execute($sql)==false) {
					displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
					die;
				}
			}

		}
	} // end update






	$cvdata=Array();
	$cv_type=-1;
	$cv_header_type=-1;
	$index=0;
	$headers=$db->getAll("SELECT cv_item_header_id,title,category FROM cv_item_headers ORDER BY category,rank ");
	foreach($headers as $header) {
        $header['category']=substr_replace($header['category'],strtoupper(substr($header['category'],0,1)),0,1);
		$cvdata[$index]=Array("type"=> "header1","title"=>$header['category'].': '.$header["title"]);
		$index++;

		$odd_even="oddrow";
        $sql="SELECT     cv_items.*,
                        cv_item_types.cv_item_header_id,
                        cv_item_types.title,f1,f1_name,f4,f4_name,
                        current_par,display_code
                 FROM     cv_items,cv_item_types
                WHERE   cv_items.user_id=$user_id
                  AND cv_items.cv_item_type_id = cv_item_types.cv_item_type_id
                  AND cv_item_types.cv_item_header_id=".$header["cv_item_header_id"]."
            ORDER BY  cv_item_types.rank, f11 desc, f10 desc, f2 desc";
		/*$sql="SELECT 	cv_items.user_id,
						cv_item_types.cv_item_header_id,
						cv_items.web_show,
						cv_items.cv_item_type_id,
						cv_items.cv_item_id,
						cv_item_types.title,f1,f1_name,f4,f4_name,
						current_par,display_code
				 FROM 	cv_items,cv_item_types
				WHERE   cv_items.user_id=$user_id
				  AND cv_items.cv_item_type_id = cv_item_types.cv_item_type_id
				  AND cv_item_types.cv_item_header_id=".$header["cv_item_header_id"]."
			ORDER BY  cv_item_types.rank";
    */
		$items=$db->getAll($sql);
		if($items)
		foreach($items as $item) {
			if(isset($cv_item_type_id)) if($cv_item_type_id!=$item["cv_item_type_id"]) { 	// current item type is not he one we got, insert new type label
				$cvdata[$index]=Array("type"=> "header2","title"=>$item["title"]);
				$odd_even="oddrow";						// reset to odd row for CSS
				$cv_item_type_id=$item["cv_item_type_id"];
				$index++;
			}

			$cv_item_id=$item["cv_item_id"];
			// add new row to the table
			if($item["f1_name"]=="")						// If first field name is empty
				$title_field="f4";							// use fourth field as main field
			else
			if(strcasecmp($item["f1_name"],"title")==0) 	// if First field is "title"
				$title_field="f1";							// then use it as main field
			else
			if(strcasecmp($item["f4_name"],"title")==0) 	// if fourth field is "title"
				$title_field="f4";							// then use it as main field
			else $title_field="f1";							// if no field is "title" fall back to first field as main field

            
            $output='';
        
            // Use htmlentities to get display of the full character set.
            $fields=array('f1','f4','f5','f6','f7','f8','f9');
            foreach($fields as $field) if ($item[$field]!='') $item[$field]=htmlentities($item[$field]);
            //$showdescription=false;
            eval($item["display_code"]);
    
            
            
			//$output=eval_display_code($item["display_code"],$item);
			if($output!="") {
				$item["output"]=$output;
				$title_field="output";
			}

			$cvdata[$index]["type"]=$odd_even;
			if($odd_even=="oddrow")
				$odd_even="evenrow";
			else
				$odd_even="oddrow";

			if($item[$title_field]=="")
				$item[$title_field]="...";
			$cvdata[$index]["title"]=$item[$title_field];
			$cvdata[$index]["item_id"]=$cv_item_id;

			$cvdata[$index]["cv_fname"]="item_{$cv_item_id}_cv";
			$cvdata[$index]["profile_fname"]="item_{$cv_item_id}_profile";
			if($item["web_show"]==1) $cvdata[$index]["profile_check"]="checked";
			if($item["current_par"]==1) $cvdata[$index]["cv_check"]="checked";
			$cvdata[$index]["title_fname"]="item_{$cv_item_id}_title";
			$index++;
		}
	}

	$tmpl->addRows("research_list",$cvdata);
	return $tmpl;
}

function edit_research_item() {
    /* This routine can go several ways. 
    1 - save and continue
        called with action='update_research_item' and cv_item_id is set
    2 - save and create new
        called with action='saveandadd' and cv_item_id is set
    3 - just create new 
        called with action='add'  
    4 - delete item                           */
    

	global $db;
    
    // Get user info
	$tmpl=loadPage("myactivities_research_edit","My research","my_research");
	$username=sessionLoggedUser();
	$sql="SELECT * FROM users WHERE username = \"$username\"";
	$user=$db->GetRow($sql);

	if(is_array($user)==false or count($user)==0) {
			displayBlankPage("Error","<h1>Error</h1>Couldnt locate current user in the database.");
			die;
	}
	$user_id=$user["user_id"];

    //Process delete request
    if(!isset($_REQUEST['action'])) $_REQUEST['action']='';
    if($_REQUEST['action'] == "delete_research_item"){
        $delete_cv_item_id = $_REQUEST['delete_cv_item_id'];

        if($delete_cv_item_id>0){
            $sql="DELETE FROM cv_items 
                            WHERE user_id=$user_id AND cv_item_id=$delete_cv_item_id ";

            if($db->Execute($sql)==false) {
                displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
                die;
            }
            unset($items);
            unset($item);
            unset($fields);
        }

        header("location: /myactivities.php?section=my_research");
    } // end delete
    
    
    
    
    //First save 
    if($_REQUEST["action"]=="update_research_item" || $_REQUEST["action"]=="saveandadd") {
        //Make sure item to save is legit
        $cv_item_id=intval($_POST["cv_item_id"]);
        if($cv_item_id==0)
            $cv_item_id=intval($_GET["cv_item_id"]);
        if($cv_item_id==0)  {
            displayBlankPage("Error","<h1>Error</h1>Couldnt locate item record in the database.");
            die;
        } 
        $update_cv_item_id = $_POST['update_cv_item_id'];
        
        //get the item type
        $sql="SELECT     *
                 FROM     cv_items
                LEFT JOIN cv_item_types ON cv_items.cv_item_type_id = cv_item_types.cv_item_type_id
                WHERE   cv_items.user_id=$user_id
                  AND cv_item_id = $update_cv_item_id ";
        $items=$db->getAll($sql);
        $item=reset($items);
        
        //run through the fields, process dates, load into array
        $fields=array();
        for($i=1;$i<=11;$i++){
            if($item["f{$i}_name"]!="") {
                
                // ** Need to check for year/month errors etc AND do the Jan 2 thing      TO DO
                if($item["f{$i}_type"]=="year" || $item["f{$i}_type"]=="month") {
                    $fx=$_POST["f$i"];
                    if(is_numeric($fx)) {//likely a year
                        if (($fx > 1902) && ($f2 < 2038)) {
                            $fx=mktime(0,0,0,1,1,$fx); //stored Jan 1
                        }
                    }
                    else if(count(explode("/",$fx)) == 2) { // month and year?
                        $temp_date=explode("/",$fx);
                        if(!(is_numeric($temp_date[0]))){    //someone used a text month
                           $tmp_m=strtotime($temp_date[0]);
                           if($tmp_m===false) $temp_date[0]=1; //could not resolve, so default to January
                           else {$tmp=getdate($tmp_m); $temp_date[0]=$tmp['mon'];}  
                        }
                        $fx=mktime(0,0,0,$temp_date[0],2,$temp_date[1]);  //stored Jan 2 because a specific month was entered
                    }
                    else if(count(explode("/",$fx)) == 3) { // day month and year?
                        $temp_date=explode("/",$fx);
                        $fx=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
                    }
                    else $fx=0;
                    $fields[]="f$i = \"".$fx.'"';
                }
                else {
                    $fields[]="f$i = \"".$_POST["f$i"].'"';
                }
            }
        }//for 11 fields
        if(count($fields)) $updateThis=",".implode(",",$fields);
        else $updateThis='';

        //if the posted typeid is in error then use the loaded one  - in case someone chooses a divider
        if($_POST["type_id"]==0) $_POST["type_id"]=$item["cv_item_type_id"];
        
        //Bypass in case its the first time
        if($_POST["type_id"]!=''){
            //do the update
            $sql="UPDATE cv_items SET
                            cv_item_type_id=".($_POST["type_id"]).",
                            web_show=".($_POST["web_show"]=="checked" ? 1 : 0).",
                            current_par=".($_POST["current_par"]=="checked" ? 1 : 0)
                            .$updateThis."
                            WHERE user_id=$user_id AND cv_item_id=$update_cv_item_id ";

            if($db->Execute($sql)==false) {
                displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
                die;
            }
        }
        unset($items);
        unset($item);
        unset($fields);

        
    }//save section
 
 
 
 
    
    
	if($_REQUEST["action"]=="addnew_research_item" || $_REQUEST["action"]=="saveandadd") {
		//generate a new item and reload it
        $sql="SELECT * FROM cv_item_types ORDER BY rank";  //pick top item in ranking as default
        $items=$db->getAll($sql);
        $item=reset($items);
        
        $now=getdate();
        $insertdate=mktime(0,0,0,1,1,$now['year']); //Jan 1 is the default. (no month showing)
        $sql="INSERT INTO cv_items
             (cv_item_type_id,user_id,f2,f3)
             VALUES (".(intval($_POST["type_id"])).",".$user_id.",".$insertdate.",".$insertdate.")";
        $db->Execute($sql);
        $cv_item_id=$db->insert_id();
        
        header("location: /myactivities.php?section=my_research&subsection=edititem&cv_item_id=$cv_item_id");
        
	} //new section
	

    // If we are simply updating, then reload the item
    if(isset($cv_item_id) || isset($_REQUEST['cv_item_id'])){
        if(!(isset($cv_item_id))) $cv_item_id=$_REQUEST['cv_item_id'];
        $sql="SELECT     *
             FROM     cv_items
             LEFT JOIN cv_item_types ON cv_items.cv_item_type_id = cv_item_types.cv_item_type_id
            WHERE   cv_items.user_id=$user_id
              AND cv_item_id = $cv_item_id";
              $items=$db->getAll($sql);
              $item=reset($items); 
    } //reload the item
    
    
    //Default Actions
    if(!(is_array($item))) {
       //if we are editing via mainpage then need to load 
       $cv_item_id=intval($_POST["cv_item_id"]);
        if($cv_item_id==0)
            $cv_item_id=intval($_GET["cv_item_id"]);
        if($cv_item_id==0)  {
            displayBlankPage("Error","<h1>Error</h1>Couldn\'t locate item record in the database.");
            die;
        }
       
           $sql="SELECT     *
             FROM     cv_items
             LEFT JOIN cv_item_types ON cv_items.cv_item_type_id = cv_item_types.cv_item_type_id
            WHERE   cv_items.user_id=$user_id
              AND cv_item_id = $cv_item_id";
    $items=$db->getAll($sql);
    $item=reset($items);
    }
    

    //For every type - set up template 
    $sql="SELECT  cv_item_header_id,title, category
             FROM cv_item_headers
         ORDER BY category,rank";
    $headers=$db->getAll($sql);
    $glue_me=true;
    $typesList=array();
    //$typesList[]=Array("cv_item_type_id"=>"0",  "title"=>" --------------- ");
    if($headers)
    foreach($headers as $header) {
        $sql="SELECT  cv_item_type_id,title
                 FROM cv_item_types
                WHERE cv_item_header_id=".$header["cv_item_header_id"]."
             ORDER BY rank";
        $someTypes=$db->getAll($sql);
        $header['category']=substr_replace($header['category'],strtoupper(substr($header['category'],0,1)),0,1);
        if($glue_me) $typesList[]=Array("cv_item_type_id"=>"0",  "title"=>" ---------$header[category]: $header[title]---- ");
        foreach($someTypes as $thisType) {
            if($thisType["cv_item_type_id"]==$item["cv_item_type_id"])
                $thisType["cv_item_type_id_selected"]="SELECTED";
            $typesList[]=$thisType;
        }
        $glue_me=true;
    }

    $tmpl->addRows("research_item_types",$typesList);

    

    
    if($item["current_par"]) $item["current_par_check"]="checked";
    if($item["web_show"]) $item["web_show_check"]="checked";
    $tmpl->addVars("page",$item);


    for($i=1;$i<=11;$i++){
        if($item["f{$i}_name"]!="")
        {
        $field=array();
        $field["f_formname"]="f$i";
        $field["fvalue"]=$item["f$i"];
        $field["fexample"]=$item["f{$i}_eg"]!=""? "Example: ".$item["f{$i}_eg"] : "";
        $field['rightexample']=$item["f{$i}_eg"];
        $field["fname"]=$item["f{$i}_name"];
        
        if(!isset($item["f{$i}_type"])) $item["f{$i}_type"]='';
        
        if($item["f{$i}_type"]=="")
            $field["ftype"]="textarea";
        else
            $field["ftype"]=$item["f{$i}_type"];
//        if($field["ftype"]=="checkbox"){ 
        if($i>=10) {
            $field["ftype"]="checkbox";
            if(intval($field["fvalue"]))
                $field["f_check"]="checked";
        }
        // Year adjust? Nov/08
        if($item["f{$i}_type"]=="year") {
            if($field["fvalue"]!=0) $field["fvalue"] = date ("Y", $field["fvalue"]); //Old system sometimes used a zero - but this results in a 1969 date.
            else $field["fvalue"]="";
        }
        if($item["f{$i}_type"]=="month") {
            if($field["fvalue"]==0) $field["fvalue"]="";
            else {
                $temp_date=getdate($field['fvalue']);
                //If the date is Jan 1, the user did not specify a month, so default to just showing YEAR
                if($temp_date['mday']==1 && $temp_date['mon']==1) $field["fvalue"] = date ("Y", $field["fvalue"]);            
                else $field["fvalue"] = date ("m/Y", $field["fvalue"]);
            }
        }
        $fields[]=$field;
        }
    }


    $tmpl->addRows("research_item_fields",$fields);
    return $tmpl;
    
    
    
	

    
}

function addnew_research_item() {
	global $db;

	$tmpl=loadPage("myactivities_research_addnew","My research","my_research");
	$username=sessionLoggedUser();
	$sql="SELECT * FROM users WHERE username = \"$username\"";
	$user=$db->GetRow($sql);

	if(is_array($user)==false or count($user)==0) {
			displayBlankPage("Error","<h1>Error</h1>Couldnt locate current user in the database.");
			die;
	}
	$user_id=$user["user_id"];



	if($_POST["action"]=="addnew_research_item") {


		/*$sql="INSERT INTO cv_items
			SET (cv_item_type_id,web_show,current_apar)
			 VALUES (".(intval($_POST["type_id"])).",
					 ".($_POST["web_show"]=="checked" ? 1 : 0).",
					 ".($_POST["current_par"]=="checked" ? 1 : 0).")";*/
        $now=getdate();
        $insertdate=mktime(0,0,0,1,1,$now['year']);
		$sql="INSERT INTO cv_items
			 (cv_item_type_id,user_id,f2,f3)
			 VALUES (".(intval($_POST["type_id"])).",".$user_id.",$insertdate,$insertdate)";

		if($db->Execute($sql)==false) {
			displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
			die;
		}

		unset($_POST);
		$cv_item_id=$db->insert_id();
		header("location: myactivities.php?section=my_research&subsection=edititem&cv_item_id=$cv_item_id");
		echo " ";
		die();

	} // end update
	else {

		$sql="SELECT cv_item_type_id,title
				 FROM 	cv_item_types ";
		$types=$db->getAll($sql);

		$tmpl->addRows("research_item_types",$types);
	}
	return $tmpl;
}



function save_visibility(){
	global $db;

	$username=sessionLoggedUser();
	$sql="SELECT * FROM users WHERE username = \"$username\"";
	$user=$db->GetRow($sql);

	if($user){
		$user_id=$user["user_id"];

		$cv_item_id = $_POST['item_id'];
		$cv = $_POST['cv'];
		$profile = $_POST['profile'];

		$sql="UPDATE cv_items SET
						web_show=".($profile=="true" ? 1 : 0).",
						current_par=".($cv=="true" ? 1 : 0)."
						WHERE user_id=$user_id AND cv_item_id=$cv_item_id ";

		$db->Execute($sql);
	}
}


function my_research() {

	$subsection=(isset($_REQUEST['subsection'])) ? $_REQUEST["subsection"] : '';
	switch ($subsection) {
		case "edititem":
            $tmpl=edit_research_item();
			break;
		/*case "addnew":
			$tmpl=addnew_research_item();
			break;*/
		case "save_visibility":
			save_visibility();
		//so we don't get an error when we try to display nothing
			$tmpl=new patTemplate();
			$tmpl->setRoot('html');
			$tmpl->readTemplatesFromInput("blanktemplate.html");
			break;
		case "":
		default:
			$tmpl=research_list();
			break;
	}

	return $tmpl;
}
?>