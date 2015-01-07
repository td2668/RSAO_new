<?php

/**
 * This file contains functions and classes for the appropriate annual report section.
 */
/* * *********************************
 * FUNCTIONS
 * ********************************** */
/**
 *   Generate the cv items edit form, will also create a new record if case is 'add'.  Populates the cv_item_types and cv_item_fields templates
 *
 *   @param      int     $cvItemId   the id of the cv item, if not new add case
 *   @param      int     $userId     the user id for the current user
 *   @param      string  $page       the name of the page used in the annual_report.php code and GET var
 *   @param      object  $tmpl       the actual patTemplate object so we can make changes
 *   @return     boolean             the return status of whether everything worked
 */
require_once('cv_functions.php');

/**
 * Generates the edit form for the current CV Item
 *
 * @param mixed $cvItemId current CV item
 * @param mixed $page Page being generated
 * @param return $userId userId for the form being generated
 * @param mixed $tmpl  patTemplate object for current template
 */
function GenerateEditForm ( $cvItemId, $page, $userId, &$tmpl ) {
    global $db;
    $status = false;
    $item = array( );
    $userId = GetVerifyUserId();
    $typeId = (isset( $_REQUEST["cas_type_id"] )) ? CleanString( $_REQUEST["cas_type_id"] ) : false;
    $casHeadingId = (isset( $_REQUEST["cas_heading_id"] )) ? CleanString( $_REQUEST["cas_heading_id"] ) : false;
    $alertMessage = 'none';
    //$cvItemHeaderCategory = GetCategory( $page );
    // first check to see if this is an add, if so, create the new record and reload the page in 'edit' mode

    if ( !$cvItemId && $typeId ) {
        //echo "<p>Generate a new record.</p>";exit;
        //generate a new item and reload it
        $now = getdate();
        $insertdate = mktime( 0, 0, 0, 1, 1, $now['year'] ); //Jan 1 is the default. (no month showing)

        $sql = "SELECT max(rank) AS max_rank FROM cas_cv_items WHERE user_id = {$userId} and cas_type_id = {$typeId}";
        $result = $db->GetAll( $sql );
        $cRank = $result[0]['max_rank'];
        $nextRank = ( $cRank != '' ? $cRank + 1 : 1);


        $sql = "INSERT INTO cas_cv_items (cas_type_id, user_id, f2, f3, report_flag,rank)
            VALUES ({$typeId}, {$userId}, {$insertdate}, {$insertdate}, 1,{$nextRank})";
        $db->Execute( $sql );
        $cvItemId = $db->insert_id();
        header( "location: /cv.php?cas_heading_id={$casHeadingId}&mr_action=edit&cv_item_id={$cvItemId}" );
    }

    // include the jquery library
    $jQueryInclude = '    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>
     <script type="text/javascript" src="js/jquery-ui-1.7.2.widgets.min.js"></script>
    ';

    $tmpl->addVar( 'HEADER', 'ADDITIONAL_HEADER_ITEMS', $jQueryInclude );
    //$tmpl->addVar( 'HEADER', 'TITLE', $jQueryInclude );




    // get the record data
    if ( $cvItemId ) {
        $tmpl->addVar( 'PAGE', 'CURRENTACTION', ' - Edit' );
        //echo "<p>get record data for item id {$cvItemId} and type {$typeId}</p>";
        $item = GetCvItem( $cvItemId, $userId );
        if ( $item ) {
            $typeId = $item['cas_type_id'];
            //echo "<p>item data</p>";
            //PrintR($item);
        } else {
            // error getting record data
            $cvItemId = false;
            $item = array( );
            // generate an error message???? / log error
            echo $sql;
        } // if
    }else{
        $tmpl->addVar( 'PAGE', 'CURRENTACTION', 'Add an item' );
    } // if
    // get the types of cv items and populate the type drop-down
    $sql = "
        SELECT cas_type_id, type_name
        FROM `cas_types`
        WHERE cas_heading_id = {$casHeadingId}
        ORDER BY `order`, `type_name`
        ";
    //echo $sql;
    $types = $db->getAll( $sql );
    $typesList = array( );
    if ( !$typeId )
        $typesList[] = array( "cas_type_id" => "0", "type_name" => "--- Please select a type. ---" );
    if ( $types ) {
        $currentHeaderId = null;
        foreach ( $types as $type ) {
            if ( $type["cas_type_id"] == $typeId )
                $type["cas_type_id_selected"] = 'SELECTED="SELECTED"';
            // add to drop-down list
            $typesList[] = $type;
            $currentHeaderId = $type['cv_item_header_id'];
        } // foreach
    } // if
    $tmpl->addRows( "cas_types", $typesList );
    
    //Populate the dropdown with all possible type for the translate function
    $sql = "
        SELECT cas_type_id, type_name
        FROM `cas_types`     
        ORDER BY `type_name`
        ";
    //echo $sql;
    $types = $db->getAll( $sql );
    $typesList2 = array( );
    if ( !$typeId )
        $typesList2[] = array( "cas_type_id" => "0", "type_name" => "" );
    if ( $types ) {
        $currentHeaderId = null;
        foreach ( $types as $type ) {
            if ( $type["cas_type_id"] == $typeId )
                $type["cas_type_id_selected"] = 'SELECTED="SELECTED"';
            // add to drop-down list
            if(strlen($type['type_name'])>30) $type['type_name']=substr($type['type_name'],0,30);
            $typesList2[] = $type;
            $currentHeaderId = $type['cv_item_header_id'];
        } // foreach
    } // if
    $tmpl->addRows( "cas_types2", $typesList2 );

    // if we have a specific type, populate the form field template variables (otherwise we are just prompting for a type)
    if ( $typeId && sizeof( $item ) > 0 ) {
        $preview = formatitem($item,'apa','screen');
        $tmpl->addVar( 'preview_area', 'PREVIEW', $preview );

        $tmpl->setAttribute("realtedto", "visibility", "show");
        $tmpl->setAttribute("toolssection", "visibility", "show");

        //echo "<p>get the meta data for type id {$typeId}</p>";
        // get the type meta data
        $sql = "SELECT * FROM `cas_field_index` WHERE `cas_type_id` = {$typeId} ORDER BY `order`";
        $typeData = $db->getAll( $sql );
        //echo 'here1';
        if ( $typeData ) {
            //echo 'here2';
            $tmpl->addVars( "page", $item ); // 20090309 CSN what is this?

            foreach ( $typeData as $fieldMeta ) {
            	//print_r($fieldMeta);
                if ( $fieldMeta["field_name"] != "" ) {
                    $fieldType = strtolower( $fieldMeta["type"] );
                    $field = array( );
                    $field["f_formname"] = $fieldMeta["cas_cv_item_field"];
                    $field["fvalue"] = htmlentities( $item[$fieldMeta["cas_cv_item_field"]] );
                    if($fieldMeta["size"] > 0 )$field["fsize"] = $fieldMeta["size"];
                    else $field["fsize"]=75;
                    if (isset($fieldMeta["maxlength"])){
                        $field["fmaxlength"] = $fieldMeta["maxlength"];
                    }else{
                        $field["fmaxlength"] = "255";
                    }
                    $field['fieldindexid'] = $fieldMeta["field_index_id"];
                    if (trim($fieldMeta['help_text']) == ''){
                        $field['hidehelp'] = "display:none";
                    }
                    //$field["fexample"] = $item["f{$i}_eg"]!=""? "Example: ".$item["f{$i}_eg"] : "";
                    //$field['rightexample'] = $item["f{$i}_eg"];
                    $field["fname"] = $fieldMeta["field_name"];
                    $field["ftype"] = $fieldType;
                    switch ( $fieldType ) {
                        case "bool":
                            $field["ftype"] = "checkbox";
                            if ( intval( $field["fvalue"] ) )
                                $field["f_check"] = "checked";
                            break;
                        case "date":
                            $yearOptions=$monthOptions=$dayOptions='';
                            $arrDate = explode('-', $field["fvalue"]);
                            $selYear = $arrDate[0];
                            $selMonth = $arrDate[1];
                            $selDay = $arrDate[2];
                            $months=array('',
                                            'Jan',
                                            'Feb',
                                            'Mar',
                                            'Apr',
                                            'May',
                                            'Jun',
                                            'Jul',
                                            'Aug',
                                            'Sep',
                                            'Oct',
                                            'Nov',
                                            'Dec');

                            $yearOptions .='<option value="0" ' . $selected . ">N/A</option>\r\n";
                            for ($year = date('Y') +6; $year >= 1950; $year --){
                                if ($year == $selYear){
                                    $selected = ' selected=selected ';
                                }else{
                                    $selected = '';
                                }

                                $yearOptions .='<option value="' . $year . '" ' . $selected . '>' . $year . "</option>\r\n";
                            }
                            $field["F_YEAROPTIONS"] = $yearOptions;

                            if ($selMonth == 0){
                                $selected = ' selected=selected ';
                            }
                            $monthOptions .='<option value="0" ' . $selected . ">N/A</option>\r\n";
                            for ($month = 1; $month <= 12; $month ++){
                                if ($month == $selMonth){
                                    $selected = ' selected=selected ';
                                }else{
                                    $selected = '';
                                }

                                $monthOptions .='<option value="' . $month . '" ' . $selected . '>' . $months[$month] . "</option>\r\n";
                            }
                            $field["F_MONTHOPTIONS"] = $monthOptions;



                            if ($selDay == 0){
                                $selected = ' selected=selected ';
                            }
                            $dayOptions .='<option value="0" ' . $selected . ">N/A</option>\r\n";
                            for ($day = 1; $day <= 31; $day ++){
                                if ($day == $selDay){
                                    $selected = ' selected=selected ';
                                }else{
                                    $selected = '';
                                }

                                $dayOptions .='<option value="' . $day . '" ' . $selected . '>' . $day . "</option>\r\n";
                            }
                            $field["F_DAYOPTIONS"] = $dayOptions;
                            break;
                        case "list":
                            $field["foptions"] = BuildList( $fieldMeta['sublist'], $field["fvalue"] );
                            if ( $fieldMeta['list_add'] == 1 ) {
                                $field["faddfield"] = '<br/>Or add ' . $field["fname"] . ': <input type="text" name = "new[' . $fieldMeta['sublist'] . ']" maxlength="100" size="65"/>';
                            }

                        case "text":
                            if ( $fieldMeta['subtype'] == 'author' ) {
                                $field["ftype"] = "author";
                                $values = explode( '|', $field["fvalue"] );
                                $field["fvaluelname"] = $values[0];
                                $field["fvaluefname"] = $values[1];
                                //$field["fvalue"] = htmlentities( $item[$fieldMeta["cas_cv_item_field"]] );
                            }

                        case "num":
                            break;
                        case "sub":
                            $fieldString = BuildSubTableFields( $item, $fieldMeta );
                            $field['extrafields'] = $fieldString;
                            break;
                        default:
                            $field["ftype"] = "textarea";
                    }
                    $fields[] = $field;
                } else {
                    //echo 'here3';
                } // if
            } // for
            if ( $typeData[0]['requires_full_text'] == 1 ) {
                $fields[] = array( 'ftype' => 'file', 'f_formname' => 'document_filename', 'fname' => 'Add/Update Document full text', 'fvalue' => $item['document_filename'] );
            }
            $fields[] = array( 'ftype' => 'hidden', 'f_formname' => 'cv_item_id', 'fvalue' => $cvItemId );
            //PrintR($fields);
            $tmpl->addRows( "cv_item_fields", $fields );
            $alertMessage = 'WARNING: If you change the type of this item you will probably lose any data that you have already entered.  Do you want to proceed with the change?';
        } else {
            // error getting type meta data, cannot display form
            echo $sql;
            PrintR( $typeData );
        } // if
    } else {
        // no item type found, prompt for type
    } // if

    $tmpl->addVar( 'page', 'ALERT_MESSAGE', $alertMessage );

    //do details for teaching

$tmpl->addVar( 'realtedto', 'DETAILS_TEACHING', $item['details_teaching'] );
$tmpl->addVar( 'realtedto', 'DETAILS_SERVICE', $item['details_service'] );
$tmpl->addVar( 'realtedto', 'DETAILS_SCHOLARSHIP', $item['details_scholarship'] );

    if ( $item['n_teaching'] == '1' ) {
        $tmpl->addVar( 'realtedto', 'N_TEACHING_CHECK', ' CHECKED="CHECKED" ' );
    }else{
        $tmpl->addVar( 'realtedto', 'DIV_TEACHING_TOGGLE', 'display:none' );
    }

    //do details for scholarship
    if ( $item['n_scholarship'] == '1' ) {
        $tmpl->addVar( 'realtedto', 'N_SCHOLARSHIP_CHECK', ' CHECKED="CHECKED" ' );
    }else{
        $tmpl->addVar( 'realtedto', 'DIV_SCHOLARSHIP_TOGGLE', 'display:none' );
    }


    //do details for service items
    if ( $item['n_service'] == '1' ) {
        $tmpl->addVar( 'realtedto', 'N_SERVICE_CHECK', ' CHECKED="CHECKED" ' );
    }else{
        $tmpl->addVar( 'realtedto', 'DIV_SERVICE_TOGGLE', 'display:none' );
    }


    if ( $cvItemId ) {
        $tmpl->addVar( 'flags', 'CV_ITEM_ID', $cvItemId );
        if ( $item['report_flag'] == '1' ) {
            $tmpl->addVar( 'flags', 'REPORT_FLAG', ' CHECKED="CHECKED" ' );
        }
        if ( $item['web_show'] == '1' ) {
            $tmpl->addVar( 'flags', 'WEB_SHOW', ' CHECKED="CHECKED" ' );
        }
        if ( $item['mycv1'] == '1' ) {
            $tmpl->addVar( 'flags', 'MYCV1', ' CHECKED="CHECKED" ' );
        }
        if ( $item['mycv2'] == '1' ) {
            $tmpl->addVar( 'flags', 'MYCV2', ' CHECKED="CHECKED" ' );
        }
    }

    return $status;
}

// function

/**
 * Saves the current cv id
 *
 * @param mixed $cvItemId current CV Item
 * @param mixed $userId current User Id
 * @param mixed $tmpl patTemplate object for current template
 */
function SaveForm ( $cvItemId, $userId, &$tmpl ) {

    global $db;
    $status = false;
    $statusMessage = '';

    //PrintR($_POST);
    //get the item data
    $typeId = (isset( $_POST["cas_type_id"] )) ? CleanString( $_POST["cas_type_id"] ) : false;
    $item = GetCvItem( $cvItemId, $userId );

    if ( $item ) {

        //Added by TD: Check if the typeID coming back from the translate dropdown is different from the original 
        if($_POST['cas_type_id2'] != $item['cas_type_id'] || $typeId != $item['cas_type_id']){
            $typeId=$_POST['cas_type_id2'];
            $newHeader=true;
        }
        else $newHeader=false;

        //Changed by TD to first save as the original type and then load as the new type.
        $sql = "SELECT * FROM `cas_field_index` WHERE `cas_type_id` = {$item['cas_type_id']}";
        $typeData = $db->getAll( $sql );
        //run through the fields, process dates, load into array
        $fields = array( );

        foreach ( $typeData as $fieldMeta ) {
            if ( $fieldMeta["field_name"] != "" ) {
                $fieldName = $fieldMeta["cas_cv_item_field"];
                $fieldType = strtolower( $fieldMeta["type"] );
                $_POST[$fieldMeta["field_name"]] = addslashes( $_POST[$fieldName] );
                switch ( $fieldType ) {
                    case "sub":
                        $sql = '';
                        $subArray = $_POST[$fieldName];
                        foreach ( $subArray as $row => $rowData ) {

                            if ( $row != 'new' ) {
                                if ( isset( $rowData['delete_row'] ) && $rowData['delete_row'] == 1 ) {
                                    $sql = "DELETE FROM `{$fieldMeta['subtable']}` WHERE id = {$row};";
                                } else {
                                    $fieldsToProcess = '';
                                    foreach ( $rowData as $field => $fieldValue ) {
                                        $fieldValue = mysql_real_escape_string( $fieldValue );
                                        if ( $fieldsToProcess != '' )
                                            $fieldsToProcess .= ',';
                                        $fieldsToProcess .= "`{$field}`='{$fieldValue}'";
                                    }
                                    $sql = "UPDATE `{$fieldMeta['subtable']}` SET {$fieldsToProcess} WHERE id = {$row}";
                                }
                            }else {

                                $fieldsToInsert = '`cv_item_id`,`fieldname`';
                                $valuesToInsert = "{$cvItemId},'{$fieldName}'";
                                $hasValue = false;

                                foreach ( $rowData as $field => $fieldValue ) {
                                    if ( $fieldValue != '' ) {
                                        $fieldValue = mysql_real_escape_string( $fieldValue );
                                        if ( $fieldsToInsert != '' )
                                            $fieldsToInsert .= ',';
                                        if ( $valuesToInsert != '' )
                                            $valuesToInsert .= ',';
                                        $hasValue = TRUE;
                                        $fieldsToInsert .= "`{$field}`";
                                        $valuesToInsert .= "'{$fieldValue}'";
                                    }
                                }
                                if ( $hasValue ) {
                                    $sql = "INSERT INTO `{$fieldMeta['subtable']}` ({$fieldsToInsert}) VALUES ({$valuesToInsert});";
                                }
                            }
                            if ($sql){
                                if ( !$db->Execute( $sql ) ) {
                                    $statusMessage = 'An error occured with saving a subtable item. (query error)';
                                    echo $sql;
                                } // if
                            }
                        }

                        continue;
                    case "date":
                        $fx = $_POST[$fieldName];

                        $value = $_POST[$fieldName]['year'] . '-' . $_POST[$fieldName]['month'] . '-' . $_POST[$fieldName]['day'];


                        $fields[] = "$fieldName = \"{$value}\"";
                        break;

                    case "bool":
                        if ( !isset( $_POST[$fieldName] ) || $_POST[$fieldName] == '' ) {
                            $_POST[$fieldName] = 0;
                        }
                        $fields[] = "$fieldName = \"" . $_POST[$fieldName] . '"';
                        break;
                    case "list":
                        $id = '';
                        if ( $fieldMeta['list_add'] == 1 && $_POST['new'][$fieldMeta['sublist']] != '' ) {
                            $name = mysql_real_escape_string( $_POST['new'][$fieldMeta['sublist']] );
                            //Added by TD to eliminate leading space problems
                            $name=preg_replace('/^\s+/','',$name);
                            $name=preg_replace('/\s+$/','',$name);
                            $name=preg_replace('/[,\.]$/','',$name);
                            //Check if it exists first
                            $sql="SELECT * FROM `{$fieldMeta['sublist']}` WHERE `name`='$name'";
                            $res=$db->getRow($sql);
                            if($res) $id=$res['id'];
                            else {
                                $sql = "INSERT INTO `{$fieldMeta['sublist']}` (`name`) VALUES ('{$name}')";
                                $db->Execute( $sql );
                                $id = $db->insert_id();
                            }
                        } else {
                            $id = $_POST[$fieldName];
                        }
                        $fields[] = "$fieldName = \"" . $id . '"';
                        break;
                    case "text":
                        if ( $fieldMeta['subtype'] == 'author' ) {
                            $_POST[$fieldName] = implode( '|', $_POST[$fieldName] );
                        }
                        $_POST[$fieldName] = mysql_real_escape_string($_POST[$fieldName]);
                    case "num":
                    default:
                        $fields[] = "$fieldName = \"" . $_POST[$fieldName] . '"';
                }
            }
        }

        //where we check for the details for teaching schlarship and service.

        if ( $_POST['n_teaching'] == 1 ) {
            $fields[] = 'n_teaching = 1';
            $fields[] = "details_teaching = '" . mysql_real_escape_string( $_POST['details_teaching'] ) . "'";
        }else{
            $fields[] = 'n_teaching = 0';
        }

        if ( $_POST['n_scholarship'] == 1 ) {
            $fields[] = 'n_scholarship = 1';
            $fields[] = "details_scholarship = '" . mysql_real_escape_string( $_POST['details_scholarship'] ) . "'";
        }else{
            $fields[] = 'n_scholarship = 0';
        }

        if ( $_POST['n_service'] == 1 ) {
            $fields[] = 'n_service = 1';
            $fields[] = "details_service = '" . mysql_real_escape_string( $_POST['details_service'] ) . "'";
        }else{
            $fields[] = 'n_service = 0';
        }


        if ( count( $fields ) ) {
            $updateThis = "," . implode( ",", $fields );
            // if this is an add case, also set report flag to be true
            // right now, just set it all the time
            $updateThis .= ( isset( $_POST["report_flag"] )) ? ', report_flag = ' . CleanString( $_POST["report_flag"] ) : '';
        } else {
            $updateThis = '';
        } // if
        //do the update
        $sql = "UPDATE cas_cv_items SET current_par = 1, cas_type_id = {$typeId} {$updateThis}
            WHERE user_id = {$userId} AND cv_item_id = {$cvItemId}
            ";
        if ( $db->Execute( $sql ) == false ) {
            $statusMessage .= 'Sorry, an error occured (update query failed).';
            echo $sql;
        } else {
            $statusMessage .= 'The record has been saved.';
            $status = true;
        } // if
    } // if item
    else {
        // invalid data received
        $statusMessage = 'Sorry, an error occured (could not get item data).';
    } //else
    $tmpl->addVar( 'status_message', 'STATUS', $statusMessage );
    
    //if($newHeader) $typeId=$_POST['cas_type_id2'];
    return $newHeader;
}

// function SaveForm

/**
 * Deletes the selected CV item
 *
 * @param mixed $cvItemId ID of the cv item to be deleted
 * @param mixed $tmpl patTemplate object for current template
 */
function DeleteItem ( $cvItemId, &$tmpl ) {

    global $db;
    $status = false;
    $userId = GetVerifyUserId();

    // check to make sure the item exists and belongs to this user
    $sql = "SELECT cv_item_id,cas_type_id FROM cas_cv_items WHERE user_id = {$userId} AND cv_item_id = {$cvItemId}";
    $data = $db->GetRow( $sql );
    if ( is_array( $data ) == false or count( $data ) == 0 ) {
        // couldn't locate the item, possible security error or hack attempt
        $statusMessage = 'The item could not be deleted.  It looks like you are trying to delete an item that does not exist, or that does not belong to you.';
    } else {
        $rowData = $db->getAll( $sql );
        $sql = "SELECT subtable FROM `cas_field_index` WHERE `cas_type_id`={$rowData[0]['cas_type_id']} AND `type` LIKE 'sub'";
        $subTableResult = $db->query( $sql );

        $sql = "DELETE FROM cas_cv_items WHERE cv_item_id = {$cvItemId}";

        if ( $db->Execute( $sql ) ) {
            foreach ( $subTableResult as $subtable ) {
                $sql = "DELETE FROM `{$subtable['subtable']}` WHERE cv_item_id = {$cvItemId}";
                if ( !$db->Execute( $sql ) ) {
                    $statusMessage = 'An error occured deleting a subtable item. (query error)';
                }
            }
            $statusMessage = 'The item has been successfully deleted.';
            $status = true;
        } else {
            $statusMessage = 'An error occured and the item was not deleted. (query error)';
            echo $sql;
        } // if
    }

    $tmpl->addVar( 'status_message2', 'STATUS', $statusMessage );

    return $status;
}

// function DeleteForm

/**
 * Builds the display list for the currently selected Heading.
 *
 * @param mixed $userId current User
 * @param mixed $casHeadingId Current heading ID
 * @param mixed $tmpl patTemplate object for current template
 */
function PopulateList ( $userId, $casHeadingId, &$tmpl ) {

    global $db;
    global $casHeadingId;
    //Taken out to keep things running. ClearBlanks routine needs fixing
    ClearBlanks();
    // include the jquery library for the checkbox ajax feature
    $tmpl->addVar( 'HEADER', 'ADDITIONAL_HEADER_ITEMS', '    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>' );

    $cvData = array( );
    $cvItemTypeId = null;
    $cvItemHeaderName = null;
    //$cvItemHeaderCategory = GetCategory( $page );
    $index = 0;
    $sql = "SELECT cas_heading_id,heading_name from cas_headings WHERE cas_heading_id = '{$casHeadingId}';";

    //echo $sql;
    $headers = $db->getAll( $sql );
    //PrintR($headers);
    $tmpl->addVar( 'HEADER', 'TITLE', $headers[0]["heading_name"] );
    $tmpl->addVar( 'PAGE', 'CV_SECTION_TITLE', $headers[0]["heading_name"] );

    $cvData[$index] = array( "type" => "header1", "title" => $headers[0]["heading_name"] );
    $index++;
    $trClass = 'oddrow';

    $fields = GetCvItemTypeFields( $casHeadingId );

    $items = GetCvItems( $userId, $fields );
    $allReportShow = true;
    $allWebShow = true;
    $allMyCV1Show = true;
    $allMyCV2Show = true;

    //PrintR($items);
    if ( $items && sizeof( $items ) > 0 ) {
        $itemCount = 1;
        foreach ( $items as $item ) {
            // check for new header type and add header if needed
            /*
              if ($cvItemHeaderName != $item["header_title"]) {
              $cvData[$index] = array("type" => "header1", "title" => $item["header_title"]);
              $trClass = 'oddrow'; // always start odd
              $cvItemHeaderName = $item["header_title"];
              $index++;
              }
             */
            // check for new item type and add header if needed
            if ( $cvItemTypeId != $item["cas_type_id"] ) {
                $itemCount = 1;
                $totalItems = GetCvItemPerHeading( $userId, $item["cas_type_id"] );
                $cvData[$index] = array( "type" => "header2", "title" => GetHeading( $item["cas_type_id"] ) );
                $trClass = 'oddrow'; // always start odd
                $cvItemTypeId = $item["cas_type_id"];
                $index++;
            }

            $itemFields = getCvItemFields( $item["cas_type_id"], $fields );

            $cvItemId = $item['cv_item_id'];
            $sortLinks = '';
            if ( $itemCount != 1 ) {
                $sortLinks .= '<a href="cv.php?cas_heading_id={CAS_HEADING_ID}&cas_type_id={CAS_TYPE_ID}&cv_item_id={CV_ITEM_ID}&mr_action=move&direction=up"><img border="0" src="/images/uparrow.gif" /></a>';
            } else {
                $sortLinks .= '<img border="0" src="/images/blankarrow.gif" />';
            }

            if ( $itemCount != $totalItems ) {
                $sortLinks .= ( $sortLinks != '' ? ' &nbsp; ' : '');
                $sortLinks .= '<a href="cv.php?cas_heading_id={CAS_HEADING_ID}&cas_type_id={CAS_TYPE_ID}&cv_item_id={CV_ITEM_ID}&mr_action=move&direction=down"><img border="0" src="/images/downarrow.gif" /></a>';
            } else {
                $sortLinks .= ( $sortLinks != '' ? ' &nbsp; ' : '');
                $sortLinks .= '<img border="0" src="/images/blankarrow.gif" />';
            }
            $cvData[$index]['sortlinks'] = $sortLinks;
            $cvData[$index]["cas_heading_id"] = $casHeadingId;
            $cvData[$index]["cas_type_id"] = $item["cas_type_id"];
            $cvData[$index]["type"] = 'item1';
            $cvData[$index]["tr_class"] = $trClass;
            $cvData[$index]["cv_item_id"] = $item['cv_item_id'];
            $cvData[$index]['report_flag'] = ($item['report_flag']) ? ' CHECKED' : '';
            $cvData[$index]['web_show'] = ($item['web_show']) ? ' CHECKED' : '';
            $cvData[$index]['mycv1'] = ($item['mycv1']) ? ' CHECKED' : '';
            $cvData[$index]['mycv2'] = ($item['mycv2']) ? ' CHECKED' : '';

            if ( $allReportShow ) {
                $allReportShow = ($item['report_flag']) ? TRUE : FALSE;
            }
            if ( $allWebShow ) {
                $allWebShow = ($item['web_show']) ? TRUE : FALSE;
            }
            if ( $allMyCV1Show ) {
                $allMyCV1Show = ($item['mycv1']) ? TRUE : FALSE;
            }
            if ( $allMyCV2Show ) {
                $allMyCV2Show = ($item['mycv2']) ? TRUE : FALSE;
            }

            $cvData[$index]['web_show'] = ($item['web_show']) ? ' CHECKED' : '';
            $cvData[$index]['mycv1'] = ($item['mycv1']) ? ' CHECKED' : '';
            $cvData[$index]['mycv2'] = ($item['mycv2']) ? ' CHECKED' : '';

            // this is just a debug field
            $cvData[$index]["summary"] = '';
            $cvData[$index]["summary"] .= "type id: {$item['cas_type_id']}";

            // set up the reaining fields for display in the template
            $title = formatitem( $item, 'apa', 'screen' );
            if ( !$title ) {
                $title = "No title has been generated for this item yet.";
            }
            $cvData[$index]["title"] = $title;

            $cvData[$index]["item_id"] = $cvItemId;
            $cvData[$index]["cv_fname"] = "item_{$cvItemId}_cv";
            $cvData[$index]["profile_fname"] = "item_{$cvItemId}_profile";
            //if($item["web_show"] == 1) $cvData[$index]["profile_check"]="checked";
            //if($item["current_par"] == 1) $cvData[$index]["cv_check"]="checked";
            $cvData[$index]["title_fname"] = "item_{$cvItemId}_title";
            $trClass = ($trClass == 'oddrow') ? 'evenrow' : 'oddrow';
            $itemCount++;
            $index++;
        } // foreach
    } else {
        if ( is_array( $items ) && sizeof( $items ) == 0 ) {
            // no items found?
            //echo "<p>no items found: ($items) $sql</p>";
            $tmpl->addVar( 'status_message', 'STATUS', "No entries yet. Use the Add an Item button to start." );
        } else {
            // error in query
            echo "<p>error in query: ($items) $sql</p>";
            $tmpl->addVar( 'status_message', 'STATUS', 'An error occured and we were not able to display the results.  Please try again later. (query error)' );
        } // if
    } // if
    //} // foreach
    //PrintR($cvData);
    if ( $allReportShow ) {
        $tmpl->addVar( 'PAGE', 'ALLREPORT_FLAG', " CHECKED" );
    }
    if ( $allWebShow ) {
        $tmpl->addVar( 'PAGE', 'ALLWEB_SHOW', " CHECKED" );
    }
    if ( $allMyCV1Show ) {
        $tmpl->addVar( 'PAGE', 'ALLMYCV1', " CHECKED" );
    }
    if ( $allMyCV2Show ) {
        $tmpl->addVar( 'PAGE', 'ALLMYCV2', " CHECKED" );
    }

    $tmpl->addRows( "cv_item_list", $cvData );
}

// function PopulateList

/**
 * Gets the Page title for the selected CV Item
 *
 * @param mixed $cvItemId ID of the CV item to get the head for.
 * @param mixed $mrAction
 * @return string current header.
 */
function GetPageTitle ( $cvItemId ) {
    global $db;
    $pageTitle = '';
    if($cvItemId=='') return '';
    $sql = "select type_name from cas_types where cas_type_id = (select cas_type_id from cas_cv_items where cv_item_id = {$cvItemId})";
    $result = $db->getAll( $sql );
    return $result[0]['type_name'];
}

// function GetPagetitle

/**
 * Add common Page Vars to the current template
 *
 * @param mixed $casHeadingId Current Heading ID
 * @param mixed $pageTitle the title of the page
 * @param mixed $mrAction current action
 * @param mixed $tmpl patTemplate object for current template
 */
function AddPageVars ( $casHeadingId, $pageTitle, $mrAction, &$tmpl ) {
    global $db;


    $tmpl->addVar( 'Page', 'cas_heading_id', $casHeadingId );
    switch ( $mrAction ) {
        case 'Save and New':
        case 'edit':
        case 'Add an item':
            $tmpl->addVar( 'Page', 'PAGE_TITLE', $pageTitle );
            $tmpl->addVar( 'header', 'TITLE', $pageTitle );
            $tmpl->addVar( 'Page', 'PAGE_INTRO', '' );
            break;
        case 'save':
        case 'Save Changes':
            $tmpl->addVar( 'Page', 'PAGE_TITLE', $pageTitle );
            $tmpl->addVar( 'Page', 'PAGE_INTRO', '' );
            break;
        case 'delete':
            // should be a javascript warning before doing this
            break;
        case 'back_to_list':
        // should be a javascrip warning for this option if the item is not saved
        default:
            $tmpl->addVar( 'cv_item_list', 'PAGE_NAME', $page );
            //$tmpl->addVar( 'Page', 'PAGE_INTRO', '' );
            //Generate a list of the heading sub-types for reference
            $sql="SELECT type_name from `cas_types` WHERE `cas_heading_id`='$casHeadingId' ORDER BY `order`";
            $typeslist=$db->getAll($sql);
            if($typeslist) {
                $list="<p><div class='enfasis'>Available Categories:</div>";
                foreach($typeslist as $key=>$type) {
                    if($key==0) $list.= " $type[type_name] ";
                    else $list.= "| $type[type_name] ";
                }
                $list.="</p>";
            }
            $tmpl->addVar( 'Page', 'SUB_LIST', $list );
            switch ( $page ) {
                case 'ar_teaching_related':
                    $tmpl->addVar( 'Page', 'PAGE_TITLE', $pageTitle );
                    $introText = "This protected database is a record of all teaching related activities
                    that you have undertaken and reported on.  Those with 'report' checked
                    (default for new entries) will be included in your annual report.<br><br>";
                    $tmpl->addVar( 'Page', 'PAGE_INTRO', $introText );
                    break;
                case 'ar_scholarly_activities':
                    $tmpl->addVar( 'Page', 'PAGE_TITLE', $pageTitle );
                    $introText = "This protected database is a record of all scholarly activities
                    that you have undertaken and reported on.  Those with 'report' checked
                    (default for new entries) will be included in your annual report.<br><br>";
                    $tmpl->addVar( 'Page', 'PAGE_INTRO', $introText );
                    break;
                case 'ar_service_activities':
                    $tmpl->addVar( 'Page', 'PAGE_TITLE', $pageTitle );
                    $introText = "This protected database is a record of all service activities
                    that you have undertaken and reported on.  Those with 'report' checked
                    (default for new entries) will be included in your annual report.<br><br>";
                    $tmpl->addVar( 'Page', 'PAGE_INTRO', $introText );
                    break;
            } // switch
            break;
    } // switch

    return true;
}

// if

/**
 * Get specified CV Item
 *
 * @param mixed $cvItemId ID of CV item to retrieve
 * @param mixed $userId current User ID
 * @return array
 */
function GetCvItem ( $cvItemId, $userId ) {

    global $db;

    $sql = "
        SELECT *
        FROM `cas_cv_items` AS item
        WHERE item.`user_id` = {$userId} AND item.`cv_item_id` = {$cvItemId} LIMIT 1";
    $items = $db->getAll( $sql );
    if ( $items ) {
        $item = reset( $items );
    } else {
        $item = false;
    } // if

    return $item;
}

// function GetCvItem

/**
 * Get the list of fields and labels for the given item type header id
 *
 * @param mixed $cvItemHeaderId
 * @returns array
 */
function GetCvItemTypeFields ( $cvItemHeaderId ) {
    global $db;

    $sql = 'SELECT cas_field_index.*
        FROM cas_field_index
        JOIN cas_types on (cas_types.cas_type_id = cas_field_index.cas_type_id)
        WHERE cas_heading_id = ' . $cvItemHeaderId . '
        ORDER BY cas_field_index.cas_type_id, cas_field_index.`order`';

    $items = $db->getAll( $sql );
    return $items;
}

/**
 * Returns the specific fields for the given castTypeId
 *
 * @param INT $casTypeId
 * @param array $fields
 * @returns array
 */
function getCvItemFields ( $casTypeId, $fields ) {
    $specificFields = array( );
    foreach ( $fields as $data ) {
        if ( $data['cas_type_id'] == $casTypeId ) {
            $specificFields[] = $data;
        }
    }
    return $specificFields;
}

/**
 * Get The CV Items based on the Header ID being passed id.
 *
 * @param mixed $userId
 * @param string $fields list of cas_cv_items fields to retrieve.
 */
function GetCvItems ( $userId, $fields = array( ), $flag = '' ) {

    global $db;
    $casTypeIds = array( );
    $casTypeIdsWhere = '';
    foreach ( $fields as $data ) {
        $casTypeIds[] = $data['cas_type_id'];
    }
    if ( sizeof( $casTypeIds ) > 0 ) {
        $casTypeIdsWhere = implode( ',', array_unique( $casTypeIds ) );
    }
    $sql = "
        SELECT item.*, type_name, heading_name
        FROM cas_cv_items AS item
        JOIN cas_types on (cas_types.cas_type_id = item.cas_type_id)
        JOIN cas_headings on (cas_types.cas_heading_id = cas_headings.cas_heading_id)
        WHERE 1
        AND item.user_id = {$userId} ";
    $sql .= ( $casTypeIdsWhere != '') ? " AND item.cas_type_id in ({$casTypeIdsWhere}) " : '';
    $sql .= ( $flag != '') ? " AND item.{$flag} = 1 " : '';
    $sql .= 'ORDER BY cas_headings.order,cas_type_id,rank desc, f2 desc';
    $items = $db->getAll( $sql );

    return $items;
}

// function GetCvItems

?>
