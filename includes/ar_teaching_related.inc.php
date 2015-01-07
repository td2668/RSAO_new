<?php
/**
* This file contains functions and classes for the appropriate annual report section.
*/
/***********************************
* FUNCTIONS
************************************/
function GenerateEditForm($cvItemId, $userId, &$tmpl) {
    global $db;
    $status = false;
    $item = array();
    $userId = GetVerifyUserId();
    $typeId = (isset($_REQUEST["cv_item_type_id"])) ? CleanString($_REQUEST["cv_item_type_id"]) : false;
    $alertMessage = 'none';

    // first check to see if this is an add, if so, create the new record and reload the page

    if(!$cvItemId && $typeId) {
        //echo "<p>Generate a new record.</p>";exit;
        //generate a new item and reload it
        $now = getdate();
        $insertdate = mktime(0,0,0,1,1,$now['year']); //Jan 1 is the default. (no month showing)
        $sql = "INSERT INTO cv_items (cv_item_type_id, user_id, f2, f3)
             VALUES ({$typeId}, {$userId}, {$insertdate}, {$insertdate})";
        $db->Execute($sql);
        $cvItemId = $db->insert_id();
        header("location: /annual_report.php?page=ar_teaching_related&mr_action=edit&cv_item_type_id={$typeId}&cv_item_id={$cvItemId}");
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

    // get the types of teaching related activities and populate the type drop-down
    $sql = "
        SELECT header.`cv_item_header_id`, header.`title` AS header_title,
            type.`title` AS title, type.cv_item_type_id
        FROM `cv_item_headers` AS header
        LEFT JOIN `cv_item_types` AS type ON type.cv_item_header_id = header.cv_item_header_id
        WHERE header.`category` = 'teaching'
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
                    $field["fvalue"] = $item["f$i"];
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
    $statusMessage='';
    if (get_magic_quotes_gpc()==1) $statusMessage .=  'Yes '; else $statusMessage.="No ";
    //PrintR($_POST);

    //get the item data
    $typeId = (isset($_POST["cv_item_type_id"])) ? CleanString($_POST["cv_item_type_id"]) : false;
    $item = GetCvItem($cvItemId, $userId);

    if ($item) {
        $typeId = $item['cv_item_type_id'];
        //run through the fields, process dates, load into array
        $fields = array();
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
        } else {
            $updateThis = '';
        } // if
        //do the update
        $sql = "
            UPDATE cv_items SET cv_item_type_id = {$typeId} {$updateThis}
            WHERE user_id = {$userId} AND cv_item_id = {$cvItemId}
        ";
        if ($db->Execute($sql) == false) {
            $statusMessage .= 'Sorry, an error occured (update query failed).';
            echo $sql;
        } else {
            $statusMessage .= 'The record has been saved.';
            $status = true;
        } // if
    } else {
        // invalid data received
        $statusMessage = 'Sorry, an error occured (could not get item data).';
    } // if

    $tmpl->addVar('status_message','STATUS',$statusMessage);

    return $status;

} // function

function DeleteForm($id) {
    global $db;
    $status = false;
    $userId = GetVerifyUserId();

    // check to make sure the item exists and belongs to this user
    $sql = "SELECT teach_item_id FROM teach_items WHERE user_id = {$userId} AND teach_item_id = {$deleteId}";
    $data = $db->GetRow($sql);
    if (is_array($data) == false or count($data) == 0) {
        // couldn't locate the item, possible security error or hack attempt

    } else {
        $sql = "DELETE FROM teach_items WHERE id = {$deleteId}";
        $status = $db->Execute($sql);
    }

    return $status;
} // function DeleteForm


function PopulateList($userId, $tmpl) {

    global $db;

    // include the jquery library for the checkbox ajax feature
    $tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>');

    $cvData = array();
    $cvItemTypeId = null;
    $cvItemHeaderCategory = 'teaching'; // 'teaching';
    $index = 0;
    //$sql = "SELECT cv_item_header_id, title FROM cv_item_headers WHERE category = '{$cvItemHeaderCategory}' ORDER BY rank;";
    //echo $sql;
    //$headers = $db->getAll($sql);
    //PrintR($headers);
    //foreach ($headers as $header) {
        //$cvData[$index] = array("type" => "header1","title" => $header["title"]);
        //$index++;
        $trClass = 'odd';
        $sql = "
            SELECT item.*, type.*
            FROM cv_items AS item
            LEFT JOIN cv_item_types AS type ON type.cv_item_type_id = item.cv_item_type_id
            LEft JOIN cv_item_headers AS header ON header.cv_item_header_id = type.cv_item_header_id
            WHERE item.user_id = {$userId} AND header.category = '{$cvItemHeaderCategory}'
            ORDER BY header.rank, type.rank, item.cv_item_id DESC"
        ; // AND cvit.cv_item_header_id = {$header["cv_item_header_id"]}
        $items = $db->getAll($sql);
        //echo "<p>{$sql}</p>";
        //PrintR($items);
        if ($items && sizeof($items) > 0) {
            foreach ($items as $item) {
                // check for new item type and add header if needed
                if ($cvItemTypeId != $item["cv_item_type_id"]) {
                    $cvData[$index] = array("type" => "header1", "title" => $item["title"]);
                    $trClass = 'odd'; // always start odd
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

                // set up main field
                if($item["f1_name"] == '') {
                    $titleField="f4";
                } else if(strcasecmp($item["f1_name"],"title") == 0) {
                    $titleField="f1";
                } else if(strcasecmp($item["f4_name"],"title") == 0) {
                    $titleField="f4";
                } else {
                    $titleField="f1";
                } // if

                // evaluate and set the output if the display code column is set
                $output = EvalDisplayCode($item["display_code"],$item);
                if ($output != '') {
                    $item["output"] = $output;
                    $titleField = "output";
                }

                //echo "<p>title field is {$titleField} and output is {$item["output"]}</p>";
                //echo "<p>{$item[$titleField]}</p>";

                // set up the reaining fields for display in the template
                if ($item[$titleField] == '') $item[$titleField] = '...';
                $cvData[$index]["title"] = $item[$titleField];
                $cvData[$index]["item_id"] = $cvItemId;
                $cvData[$index]["cv_fname"]="item_{$cvItemId}_cv";
                $cvData[$index]["profile_fname"]="item_{$cvItemId}_profile";
                //if($item["web_show"] == 1) $cvData[$index]["profile_check"]="checked";
                //if($item["current_par"] == 1) $cvData[$index]["cv_check"]="checked";
                $cvData[$index]["title_fname"]="item_{$cvItemId}_title";
                $trClass = ($trClass == 'odd') ? 'even' : 'odd';
                $index++;
            } // foreach
        } else {
            if ($items) {
                // no items found?
                echo "<p>no items found: $sql</p>";
            } else {
                // error in query
                echo "<p>error in query: $sql</p>";
            } // if
        } // if
    //} // foreach
    //PrintR($cvData);
    $tmpl->addRows("teaching_related_list", $cvData);

} // function
?>
