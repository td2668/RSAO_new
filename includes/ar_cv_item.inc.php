<?php
/**
* This file contains functions and classes for the appropriate annual report section.
*/
/***********************************
* FUNCTIONS
************************************/
/**
*   Generate the cv items edit form, will also create a new record if case is 'add'.  Populates the cv_item_types and cv_item_fields templates
*
*   @param      int     $cvItemId   the id of the cv item, if not new add case
*   @param      int     $userId     the user id for the current user
*   @param      string  $page       the name of the page used in the annual_report.php code and GET var
*   @param      object  $tmpl       the actual patTemplate object so we can make changes
*   @return     boolean             the return status of whether everything worked
*/
function GenerateEditForm($cvItemId, $page, $userId, &$tmpl) {
    global $db;
    $status = false;
    $item = array();
    $userId = GetVerifyUserId();
    $typeId = (isset($_REQUEST["cv_item_type_id"])) ? CleanString($_REQUEST["cv_item_type_id"]) : false;
    $alertMessage = 'none';
    $cvItemHeaderCategory = GetCategory($page);

    // first check to see if this is an add, if so, create the new record and reload the page in 'edit' mode

    if(!$cvItemId && $typeId) {
        //echo "<p>Generate a new record.</p>";exit;
        //generate a new item and reload it
        $now = getdate();
        $insertdate = mktime(0,0,0,1,1,$now['year']); //Jan 1 is the default. (no month showing)
        $sql = "INSERT INTO cv_items (cv_item_type_id, user_id, f2, f3, report_flag)
             VALUES ({$typeId}, {$userId}, {$insertdate}, {$insertdate}, 1)";
        $db->Execute($sql);
        $cvItemId = $db->insert_id();
        header("location: /annual_report.php?page={$page}&mr_action=edit&cv_item_type_id={$typeId}&cv_item_id={$cvItemId}");
    }

    // include the jquery library
    $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');

    // get the record data
    if ($cvItemId) {
        //echo "<p>get record data for item id {$cvItemId} and type {$typeId}</p>";
        $item = GetCvItem($cvItemId, $userId);
        if ($item) {
            $typeId = $item['cv_item_type_id'];
            //echo "<p>item data</p>";
            //PrintR($item);
        } else {
            // error getting record data
            $cvItemId = false;
            $item = array();
            // generate an error message???? / log error
            echo $sql;
        } // if
    } // if

    // get the types of cv items and populate the type drop-down
    $sql = "
        SELECT header.`cv_item_header_id`, header.`title` AS header_title,
            type.`title` AS title, type.cv_item_type_id
        FROM `cv_item_headers` AS header
        LEFT JOIN `cv_item_types` AS type ON type.cv_item_header_id = header.cv_item_header_id
        WHERE header.`category` = '{$cvItemHeaderCategory}'
        ORDER BY header.`rank`, header.`title`, type.`rank`, type.`title`
    ";
    //echo $sql;
    $types = $db->getAll($sql);
    $typesList = array();
    if (!$typeId) $typesList[] = array("teach_item_type_id" => "0",  "title" => "--- Please select a type. ---");
    if ($types) {
        $currentHeaderId = null;
        foreach ($types as $type) {
            // check for new header
            if ($type['cv_item_header_id'] != $currentHeaderId) {
                $typesList[] = array("teach_item_type_id" => "0",  "title" => "******  {$type['header_title']}  ******");
            } // if
            // check for currently selected item
            if ($type["cv_item_type_id"] == $typeId) $type["cv_item_type_id_selected"] = "SELECTED";
            // add to drop-down list
            $typesList[] = $type;
            $currentHeaderId = $type['cv_item_header_id'];
        } // foreach
    } // if
    $tmpl->addRows("cv_item_types", $typesList);

    // if we have a specific type, populate the form field template variables (otherwise we are just prompting for a type)
    if ($typeId && sizeof($item) > 0) {
        //echo "<p>get the meta data for type id {$typeId}</p>";
        // get the type meta data
        $sql = "SELECT * FROM `cv_item_types` WHERE `cv_item_type_id` = {$typeId}";
        $typeData = $db->getAll($sql);
        //echo 'here1';
        if ($typeData) {
            //echo 'here2';
            $tmpl->addVars("page", $item); // 20090309 CSN what is this?
            for ($i=1; $i<=11; $i++) {
                if ($item["f{$i}_name"] != "") {
                    $field = array();
                    $field["f_formname"] = "f$i";
                    $field["fvalue"] = htmlentities($item["f$i"]);
                    $field["fexample"] = $item["f{$i}_eg"]!=""? "Example: ".$item["f{$i}_eg"] : "";
                    $field['rightexample'] = $item["f{$i}_eg"];
                    $field["fname"] = $item["f{$i}_name"];
                    if ($item["f{$i}_type"]=="") {
                        $field["ftype"]="textarea";
                    } else {
                        $field["ftype"]=$item["f{$i}_type"];
                    }
                    // if($field["ftype"]=="checkbox"){
                    if ($i>=10) {
                        $field["ftype"]="checkbox";
                        if(intval($field["fvalue"])) $field["f_check"]="checked";
                    }
                    // Year adjust? Nov/08
                    if ($item["f{$i}_type"]=="year") {
                        if ($field["fvalue"]!=0) {
                            $field["fvalue"] = date ("Y", $field["fvalue"]); //Old system sometimes used a zero - but this results in a 1969 date.
                        } else {
                            $field["fvalue"] = "";
                        } // if
                    }
                    if($item["f{$i}_type"] == "month") {
                        if($field["fvalue"] == 0) $field["fvalue"] = "";
                        else {
                            $temp_date = getdate($field['fvalue']);
                            //If the date is Jan 1, the user did not specify a month, so default to just showing YEAR
                            if($temp_date['mday'] == 1 && $temp_date['mon'] == 1) $field["fvalue"] = date ("Y", $field["fvalue"]);
                            else $field["fvalue"] = date ("m/Y", $field["fvalue"]);
                        }
                    }
                    $fields[] = $field;
                } else {
                    //echo 'here3';
                } // if
            } // for
            $fields[] = array('ftype' => 'hidden', 'f_formname' => 'cv_item_id', 'fvalue' => $cvItemId);
            //PrintR($fields);
            $tmpl->addRows("cv_item_fields", $fields);
            $alertMessage = 'WARNING: If you change the type of this item you will probably lose any data that you have already entered.  Do you want to proceed with the change?';
        } else {
            // error getting type meta data, cannot display form
            echo $sql;
            PrintR($typeData);
        } // if
    } else {
        // no item type found, prompt for type
    } // if

    $tmpl->addVar('page','ALERT_MESSAGE',$alertMessage);

    return $status;

} // function

function SaveForm($cvItemId, $userId, &$tmpl) {

    global $db;
    $status = false;
    $statusMessage = '';
    //PrintR($_POST);

    //get the item data
    $typeId = (isset($_POST["cv_item_type_id"])) ? CleanString($_POST["cv_item_type_id"]) : false;
    $item = GetCvItem($cvItemId, $userId);

    if ($item) {
		
			$typeId = $item['cv_item_type_id'];
			//run through the fields, process dates, load into array
			$fields = array();
			//remove from-to pairs that are the same (TREVOR) - assumes f2 and f3 are only date fields
			if(($item['f2_type'] =='year' || $item['f2_type'] =='month') && ($item['f3_type'] =='year' || $item['f3_type'] =='month')){
				if($_POST['f2'] == $_POST['f3']) $_POST['f3']='';
			}
			//
			
			for ($i=1; $i<=11; $i++) {
				if($item["f{$i}_name"] != "") {
					// ** Need to check for year/month errors etc AND do the Jan 2 thing      TO DO
					if ($item["f{$i}_type"]=="year" || $item["f{$i}_type"]=="month") {
						$fx=$_POST["f$i"];
						if( is_numeric($fx)) {//likely a year
							if (($fx > 1902) && ($fx < 2038)) {
								$fx=mktime(0,0,0,1,1,$fx); //stored Jan 1
							}
						} else if(count(explode("/",$fx)) == 2) { // month and year?
							$temp_date=explode("/",$fx);
							if(!(is_numeric($temp_date[0]))){    //someone used a text month
							   $tmp_m=strtotime($temp_date[0]);
							   if($tmp_m===false) $temp_date[0]=1; //could not resolve, so default to January
							   else {$tmp=getdate($tmp_m); $temp_date[0]=$tmp['mon'];}
							}
							$fx=mktime(0,0,0,$temp_date[0],2,$temp_date[1]);  //stored Jan 2 because a specific month was entered
						} else if(count(explode("/",$fx)) == 3) { // day month and year?
							$temp_date=explode("/",$fx);
							$fx=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
						} else {
							$fx=0;
						}
						$fields[]="f$i = \"".$fx.'"';
					} else {
						$fields[]="f$i = \"".$_POST["f$i"].'"';
					}
				}
			} // for
			if (count($fields)) {
				$updateThis = "," . implode(",", $fields);
				// if this is an add case, also set report flag to be true
				// right now, just set it all the time
				$updateThis .= (isset($_POST["report_flag"])) ? ', report_flag = ' . CleanString($_POST["report_flag"]) : '';
			} else {
				$updateThis = '';
			} // if
			
					
			//do the update
			$sql = "
				UPDATE cv_items SET current_par = 1, cv_item_type_id = {$typeId} {$updateThis}
				WHERE user_id = {$userId} AND cv_item_id = {$cvItemId}
			";
			if ($db->Execute($sql) == false) {
				$statusMessage = 'Sorry, an error occured (update query failed).';
				echo $sql;
			} else {
				$statusMessage = 'The record has been saved.';
				$status = true;
			} // if
		
		
	} // if item
	else {
		// invalid data received
		$statusMessage = 'Sorry, an error occured (could not get item data).';
	} //else
    $tmpl->addVar('status_message','STATUS',$statusMessage);

    return $status;

} // function SaveForm

function DeleteItem($cvItemId, &$tmpl) {

    global $db;
    $status = false;
    $userId = GetVerifyUserId();

    // check to make sure the item exists and belongs to this user
    $sql = "SELECT cv_item_id FROM cv_items WHERE user_id = {$userId} AND cv_item_id = {$cvItemId}";
    $data = $db->GetRow($sql);
    if (is_array($data) == false or count($data) == 0) {
        // couldn't locate the item, possible security error or hack attempt
        $statusMessage = 'The item could not be deleted.  It looks like you are trying to delete an item that does not exist, or that does not belong to you.';
    } else {
        $sql = "DELETE FROM cv_items WHERE cv_item_id = {$cvItemId}";
        if ($db->Execute($sql)) {
            $statusMessage = 'The item has been successfully deleted.';
            $status = true;
        } else {
            $statusMessage = 'An error occured and the item was not deleted. (query error)';
            echo $sql;
        } // if
    }

    $tmpl->addVar('status_message2','STATUS',$statusMessage);

    return $status;

} // function DeleteForm


function PopulateList($userId, $page, &$tmpl) {

    global $db;

    // include the jquery library for the checkbox ajax feature
    $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');

    $cvData = array();
    $cvItemTypeId = null;
    $cvItemHeaderName = null;
    $cvItemHeaderCategory = GetCategory($page);
    $index = 0;
    //$sql = "SELECT cv_item_header_id, title FROM cv_item_headers WHERE category = '{$cvItemHeaderCategory}' ORDER BY rank;";
    //echo $sql;
    //$headers = $db->getAll($sql);
    //PrintR($headers);
    //foreach ($headers as $header) {
        //$cvData[$index] = array("type" => "header1","title" => $header["title"]);
        //$index++;
        $trClass = 'oddrow';
        $items = GetCvItems($userId, $cvItemHeaderCategory);
        //PrintR($items);
        if ($items && sizeof($items) > 0) {
            foreach ($items as $item) {
                // check for new header type and add header if needed
                if ($cvItemHeaderName != $item["header_title"]) {
                    $cvData[$index] = array("type" => "header1", "title" => $item["header_title"]);
                    $trClass = 'oddrow'; // always start odd
                    $cvItemHeaderName = $item["header_title"];
                    $index++;
                }
                // check for new item type and add header if needed
                if ($cvItemTypeId != $item["cv_item_type_id"]) {
                    $cvData[$index] = array("type" => "header2", "title" => $item["title"]);
                    $trClass = 'oddrow'; // always start odd
                    $cvItemTypeId = $item["cv_item_type_id"];
                    $index++;
                }
                $cvItemId = $item['cv_item_id'];
                $cvData[$index]["type"] = 'item1';
                $cvData[$index]["tr_class"] = $trClass;
                $cvData[$index]["cv_item_id"] = $item['cv_item_id'];
                $cvData[$index]['report_flag'] = ($item['report_flag']) ? ' CHECKED' : '';

                // this is just a debug field
                $cvData[$index]["summary"] = '';
                $cvData[$index]["summary"] .= "type id: {$item['cv_item_type_id']}";
                $cvData[$index]["summary"] .= ($item['f1'] != '') ? ' | ' . $item['f1'] : '';
                $cvData[$index]["summary"] .= ($item['f2'] != '') ? ' | ' . date('M Y',$item['f2']) : '';
                $cvData[$index]["summary"] .= ($item['f3'] != '') ? ' | ' . date('M Y',$item['f3']) : '';
                $cvData[$index]["summary"] .= ($item['f4'] != '') ? ' | ' . $item['f4'] : '';
                $cvData[$index]["summary"] .= ($item['f5'] != '') ? ' | ' . $item['f5'] : '';
                $cvData[$index]["summary"] .= ($item['f6'] != '') ? ' | ' . $item['f6'] : '';
                $cvData[$index]["summary"] .= ($item['f7'] != '') ? ' | ' . $item['f7'] : '';
                $cvData[$index]["summary"] .= ($item['f8'] != '') ? ' | ' . $item['f8'] : '';
                $cvData[$index]["summary"] .= ($item['f9'] != '') ? ' | ' . $item['f9'] : '';
                $cvData[$index]["summary"] .= ($item['f10'] != '') ? ' | ' . $item['f10'] : '';
                $cvData[$index]["summary"] .= ($item['f11'] != '') ? ' | ' . $item['f11'] : '';

                $cvItemSummary = GetCvItemSummary($item, true);

                // set up the reaining fields for display in the template
                $cvData[$index]["title"] = ($cvItemSummary != '') ? $cvItemSummary : '...';
                $cvData[$index]["item_id"] = $cvItemId;
                $cvData[$index]["cv_fname"]="item_{$cvItemId}_cv";
                $cvData[$index]["profile_fname"]="item_{$cvItemId}_profile";
                //if($item["web_show"] == 1) $cvData[$index]["profile_check"]="checked";
                //if($item["current_par"] == 1) $cvData[$index]["cv_check"]="checked";
                $cvData[$index]["title_fname"]="item_{$cvItemId}_title";
                $trClass = ($trClass == 'oddrow') ? 'evenrow' : 'oddrow';
                $index++;
            } // foreach
        } else {
            if (is_array($items) && sizeof($items) == 0) {
                // no items found?
                //echo "<p>no items found: ($items) $sql</p>";
                $tmpl->addVar('status_message','STATUS','None of these types of items were found related to your account.');
            } else {
                // error in query
                echo "<p>error in query: ($items) $sql</p>";
                $tmpl->addVar('status_message','STATUS','An error occured and we were not able to display the results.  Please try again later. (query error)');
            } // if
        } // if
    //} // foreach
    //PrintR($cvData);
    $tmpl->addRows("cv_item_list", $cvData);

} // function PopulateList

function GetCategory($page) {

    $category = '';
    switch ($page) {
        case 'ar_teaching_related':
            $category = 'teaching';
            break;
        case 'ar_scholarly_activities':
            $category = 'research';
            break;
        case 'ar_service_activities':
            $category = 'service';
            break;
    } // switch
    return $category;

} // function GetCategory

function GetNextPageName($page) {
    $nextPageName = '';
    switch($page) {
        case "ar_teaching_related":
            $nextPageName = 'ar_scholarship';
            break;
        case "ar_scholarly_activities":
            $nextPageName = 'ar_service';
            break;
        case "ar_service_activities":
            $nextPageName = 'ar_review_submit';
            break;
    }
    return $nextPageName;
} // function GetNextPageName


function GetPageTitle($page, $mrAction) {

    $pageTitle = '';

    switch ($mrAction) {
        case 'Save and New':
        case 'edit':
        case 'Add an item':
        case 'save':
        case 'Save Changes':
        case 'delete':
            switch ($page) {
                case 'ar_teaching_related':
                    $pageTitle = 'Teaching Related Add/Edit';
                    break;
                case 'ar_scholarly_activities':
                    $pageTitle = 'Scholarly Activities Add/Edit';
                    break;
                case 'ar_service_activities':
                    $pageTitle = 'Service Activities Add/Edit';
                    break;
            } // switch
            break;
        case 'back_to_list':
        default:
            switch ($page) {
                case 'ar_teaching_related':
                    $pageTitle = 'Teaching Related';
                    break;
                case 'ar_scholarly_activities':
                    $pageTitle = 'Scholarly Activities';
                    break;
                case 'ar_service_activities':
                    $pageTitle = 'Service Activities';
                    break;
            } // switch
            break;
    } // switch
    return $pageTitle;

} // function GetPagetitle


function AddPageVars($page, $mrAction, &$tmpl) {

    $tmpl->addVar('Page','PAGE_NAME',$page);
    switch ($mrAction) {
        case 'Save and New':
        case 'edit':
        case 'Add an item':
            $tmpl->addVar('Page','PAGE_TITLE','Annual Report - Scholarly Activities');
            $tmpl->addVar('Page','PAGE_INTRO','');
            break;
        case 'save':
        case 'Save Changes':
            $tmpl->addVar('Page','PAGE_TITLE','Annual Report - Scholarly Activities');
            $tmpl->addVar('Page','PAGE_INTRO','');
            break;
        case 'delete':
            // should be a javascript warning before doing this
            break;
        case 'back_to_list':
            // should be a javascrip warning for this option if the item is not saved
        default:
            $tmpl->addVar('cv_item_list','PAGE_NAME',$page);
            switch ($page) {
                case 'ar_teaching_related':
                    $tmpl->addVar('Page','PAGE_TITLE','Annual Report - Teaching Related Activities');
                    $introText = "This protected database is a record of all teaching related activities
                        that you have undertaken and reported on.  Those with 'report' checked
                        (default for new entries) will be included in your annual report.<br><br>";
                    $tmpl->addVar('Page','PAGE_INTRO',$introText);
                    break;
                case 'ar_scholarly_activities':
                    $tmpl->addVar('Page','PAGE_TITLE','Annual Report - Scholarly Activities');
                    $introText = "This protected database is a record of all scholarly activities
                        that you have undertaken and reported on.  Those with 'report' checked
                        (default for new entries) will be included in your annual report.<br><br>";
                    $tmpl->addVar('Page','PAGE_INTRO',$introText);
                    break;
                case 'ar_service_activities':
                    $tmpl->addVar('Page','PAGE_TITLE','Annual Report - Service Activities');
                    $introText = "This protected database is a record of all service activities
                        that you have undertaken and reported on.  Those with 'report' checked
                        (default for new entries) will be included in your annual report.<br><br>";
                    $tmpl->addVar('Page','PAGE_INTRO',$introText);
                    break;
            } // switch
            break;
    } // switch

    return true;
} // if


function GetCvItem($cvItemId, $userId) {

    global $db;

    $sql="
        SELECT *
        FROM `cv_items` AS item
        LEFT JOIN `cv_item_types` AS type ON type.`cv_item_type_id` = item.`cv_item_type_id`
        WHERE item.`user_id` = {$userId} AND item.`cv_item_id` = {$cvItemId} LIMIT 1";
    $items = $db->getAll($sql);
    if ($items) {
        $item = reset($items);
    } else {
        $item = false;
    } // if

    return $item;

} // function GetCvItem

function GetCvItems($userId, $cvItemHeaderCategory = '') {

    global $db;

	
	
    $sql = "
        SELECT item.*, header.category AS header_category, header.title AS header_title,
			 type.cv_item_type_id, type.cv_item_header_id,	type.rank, type.title, type.title_plural as type_plural, type.f1_name, type.f1_type,
			 type.f2_name, type.f2_type, type.f3_name, type.f3_type, type.f4_name, type.f4_type,
			 type.f5_name, type.f5_type, type.f6_name, type.f6_type, type.f7_name, type.f7_type,
			 type.f8_name, type.f8_type, type.f9_name, type.f9_type, type.f10_name, 
			 type.f11_name,  type.display_code,
			 type.default_web, type.show_url, type.url_type, type.show_abstract  
		     
        FROM cv_items AS item
        LEFT JOIN cv_item_types AS type ON type.cv_item_type_id = item.cv_item_type_id
        LEft JOIN cv_item_headers AS header ON header.cv_item_header_id = type.cv_item_header_id
        WHERE 1
            AND item.user_id = {$userId} ";
    $sql .= ($cvItemHeaderCategory != '') ? " AND header.category = '{$cvItemHeaderCategory}' " : '';
    //Changed by Trevor 20090403 so that papers sort by submitted, forthcoming, then date. This may
    // screw up some other types that use those boolean fields. Needs to be tested
    $sql .= "
        ORDER BY header.category, header.rank, header.title, type.rank, item.f11 DESC, item.f10 DESC, item.f2 DESC"
    ;
    $items = $db->getAll($sql);

    return $items;

} // function GetCvItems

function GetCvItemSummary($cvItemData, $htmlFlag = true) {

    //global $showdescription;
    //$showdescription = false; // display the abstract f9 field data if present

    if($cvItemData["f1_name"] == '') {
        $titleField="f4";
    } else if(strcasecmp($cvItemData["f1_name"],"title") == 0) {
        $titleField="f1";
    } else if(strcasecmp($cvItemData["f4_name"],"title") == 0) {
        $titleField="f4";
    } else {
        $titleField="f1";
    } // if
    // evaluate and set the output if the display code column is set
    $output = EvalDisplayCode($cvItemData["display_code"], $cvItemData);
    if ($output != '') {
        if (!$htmlFlag) {
            // deconvert from HTML
            $output = strip_tags($output); // remove markup like <li></li> etc.
            $output = html_entity_decode($output);  // convert HTML characters like &mdash; etc.
            $output = str_replace('&mdash;','-',$output); // for some reason &mdash; is not in the conversion table on the claero server
            // for full conversion table of html entities run: PrintR(get_html_translation_table(HTML_ENTITIES));
        } // if
        $cvItemData["output"] = $output;
        $titleField = "output";
    }

    return $cvItemData[$titleField];

} // function GetCvItemSummary

function EvalDisplayCode($displayCode, $item) {

    //echo "<h2>EvalDisplayCode() Called</h2>";
    //PrintR($displayCode);
    //PrintR($item);

    //global $showdescription;

    $allowedCalls= explode(',',
        'explode,implode,date,time,round,trunc,rand,ceil,floor,srand,getdate,'.
        'strtolower,strtoupper,substr,stristr,strpos,print,print_r,isset,'.
        'f1,f2,f3,f4,f5,f6,f7,f8,f9,f10,f11,f12,item,output,cv_item_id,showdescription');
    $output='';
    $parseErrors = array();
    $tokens = token_get_all('<?'.'php '.$displayCode.' ?'.'>');
    //PrintR($tokens);
    $vcall = '';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            $id = $token[0];
            switch ($id) {
                case(T_VARIABLE): { $vcall .= 'v'; break; }
                case(T_STRING): { $vcall .= 's'; }
                case(T_REQUIRE_ONCE):
                case(T_REQUIRE):
                case(T_NEW):
                case(T_RETURN):
                case(T_BREAK):
                case(T_CATCH):
                case(T_CLONE):
                case(T_EXIT):
                case(T_PRINT):
                case(T_GLOBAL):
                case(T_ECHO):
                case(T_INCLUDE_ONCE):
                case(T_INCLUDE):
                case(T_EVAL):
                case(T_FUNCTION): {
                    if (array_search($token[1], $allowedCalls) === false) $parseErrors[] = 'illegal call: '.$token[1];
                }
            }
        } else {
            $vcall .= $token;
        } // if
    } // foreach

    if (stristr($vcall, 'v(') != '') $parseErrors[] = array('illegal dynamic function call');
    $cv_item_id = $item['cv_item_id'];
    if ($displayCode != "") {
        if (sizeof($parseErrors) == 0) {
			$showdescription=true;
            eval($displayCode);
        } else {
            $output='error: the display_code of selected item type contains errors.<br /><i>'.implode(", ",$parseErrors).'</i>';
        }
    }
    //PrintR($output);
    //echo "<hr>";

    return $output;
} // function EvalDisplayCode
?>
