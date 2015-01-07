<?php
	require_once('DB_init.php');
	
	// Storage for the setup database / table names that we need.
	$sDatabaseName_Data = "";
	$sTableName_Data_MainData = "";
	$sTableName_Data_BelongsIn = "";
	$sTableName_Data_ReadibleNames = "";

	// Storage for the settings database / table names that we need.
	$sDatabaseName_Settings = "";
	$sTableName_Settings_SummaryFields = "";

	$aSharedDataInfo = array();	// 2-D array - outer array is entry number, inner[0] = parentTable, inner[1] = parentField.

	$aProcessedLists = array();
	$aTimesRequiredToProcess = array();

	// Storage for the XML Response:
	$sResponse = "";

	$aSQLTableNames = array();	// Holds [TableName] => SQLVariableName for SELECT statement...
	
	
	// Queries the setup info database for the setup info for install ID $sInstallId. Once finished, this will
	// select the appropriate database for future queries.
	function getSetupInfo ($sInstallId) {
		// Field / Database Names:
		global $sDBSetup_FieldName_InstallID;
		
		global $sDBSetup_FieldName_DatabaseName_Data;
		global $sDBSetup_FieldName_TableName_Data_MainData;
		global $sDBSetup_FieldName_TableName_Data_BelongsIn;
		global $sDBSetup_FieldName_TableName_Data_ReadibleNames;

		global $sDBSetup_FieldName_DatabaseName_Settings;
		global $sDBSetup_FieldName_TableName_Settings_SummaryFields;
		
		global $sDBSetupTableName;

		// Storage for Found Names:
		global $sDatabaseName_Data;
		global $sTableName_Data_MainData;
		global $sTableName_Data_BelongsIn;
		global $sTableName_Data_ReadibleNames;
		
		global $sDatabaseName_Settings;
		global $sTableName_Settings_SummaryFields;
				
		$sQuery = "SELECT S.".$sDBSetup_FieldName_DatabaseName_Data.
					", S.".$sDBSetup_FieldName_TableName_Data_MainData.
					", S.".$sDBSetup_FieldName_TableName_Data_BelongsIn.
					", S.".$sDBSetup_FieldName_TableName_Data_ReadibleNames.
					", S.".$sDBSetup_FieldName_DatabaseName_Settings.
					", S.".$sDBSetup_FieldName_TableName_Settings_SummaryFields.
					" FROM ".$sDBSetupTableName." S WHERE S.".$sDBSetup_FieldName_InstallID." = '".$sInstallId."';";
		
		debug("Setup Info Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);

		if ($oResult != FALSE) {
			$sDatabaseName_Data = mysql_result($oResult, 0, $sDBSetup_FieldName_DatabaseName_Data);
			$sTableName_Data_MainData = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Data_MainData);
			$sTableName_Data_BelongsIn = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Data_BelongsIn);
			$sTableName_Data_ReadibleNames = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Data_ReadibleNames);
			$sDatabaseName_Settings = mysql_result($oResult, 0, $sDBSetup_FieldName_DatabaseName_Settings);
			$sTableName_Settings_SummaryFields = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Settings_SummaryFields);

			debug("Got setup info:");
			debug("Main DB Name = '$sDatabaseName_Data'");
			debug("Main Data Table Name = '$sTableName_Data_MainData'");
			debug("Belongs In Data Table Name = '$sTableName_Data_BelongsIn'");
			debug("Readible Names Data Table Name = '$sTableName_Data_ReadibleNames'");
			debug("Settings Database Name = '$sDatabaseName_Settings'");
			debug("Summary Fields Table Name = '$sTableName_Settings_SummaryFields'");
		}
	} // function getSetupInfo ($sInstallId)
	
	
	// Get the summary fields that we will be returning:
	function getSummaryFields ($sRegion) {
		global $sDatabaseName_Settings;
		global $sTableName_Settings_SummaryFields;
		
		@mysql_select_db($sDatabaseName_Settings) or $sSQLError = "Unable to open database";
		$sQuery = "SELECT * FROM ".$sTableName_Settings_SummaryFields." WHERE region='".$sRegion."' ORDER BY weight;";
		
		debug("Summary Field Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);
		
		return $oResult;
	}
	
	
	function processSummaryFields ($oSQLResult, $aIDs, $bInList, $sItemXML, $sParentID) {
		global $sTableName_Data_MainData;
		global $sDatabaseName_Data;
		global $rTileID;

		$rNumSummaryFields = mysql_num_rows($oSQLResult);		
		$rNumDataFields = mysql_num_fields($oSQLResult);
		
		$sResultXML = "";
		
		debug("ProcessSummaryFields... (".$rNumSummaryFields." summary field entries, $rNumDataFields data fields.)");
		outputIntArray($aIDs);
		
		@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open database";

		for ($i = 0; $i < $rNumSummaryFields; $i++) {
			// Get the data for this entry:

			$sEntryType = mysql_result($oSQLResult, $i, 'type');
			$sPath = mysql_result($oSQLResult, $i, 'path');
			$sRegion = mysql_result($oSQLResult, $i, 'region');
			
			debug($i.': EntryType:'.$sEntryType);

			if ($sEntryType == 'list') {
				$sID = mysql_result($oSQLResult, $i, 'id');
				
				if (checkIfProcessed($sID) == FALSE) {
					$sListXML = processList($oSQLResult, $i, $aIDs, $sParentID);
					
					debug("sListXML: ".$sListXML);
					
					if ($sItemXML == "BlockItem") {
						$sResultXML .= "<BlockItem>".$sListXML."</BlockItem>";
					}
					else $sResultXML .= $sListXML;
				}
			}
			else if ($sEntryType == 'block') {
				$sID = mysql_result($oSQLResult, $i, 'id');
				
				if (checkIfProcessed($sID) == FALSE) {
					$sBlockXML = processBlock($oSQLResult, $i, $aIDs, $sParentID);
					
					debug("sBlockXML: ".$sBlockXML);
					
					$sResultXML .= $sBlockXML;
				}
			}
			else if ($sEntryType == 'static') {
				if ($sItemXML != NULL) {
					$sResultXML .= "<".$sItemXML.">";
				}
				
				$sResultXML .= getXMLOpener($oSQLResult, $i).">";
				$sResultXML .= urlencode(mysql_result($oSQLResult, $i, 'label'));
				$sResultXML .= getXMLCloser($oSQLResult, $i);	
						
				if ($sItemXML != NULL) {
					$sResultXML .= "</".$sItemXML.">";
				}
			}
			else if ($sEntryType == 'verticalSpace') {
				if ($sItemXML != NULL) {
					$sResultXML .= "<".$sItemXML.">";
				}
				
				$sResultXML .= getXMLOpener($oSQLResult, $i).">";
				$sResultXML .= urlencode(mysql_result($oSQLResult, $i, 'label'));
				$sResultXML .= getXMLCloser($oSQLResult, $i);	
						
				if ($sItemXML != NULL) {
					$sResultXML .= "</".$sItemXML.">";
				}
			}
			else {
				// TO DO: Why did I do this check - don't seem to need it...
				debug("strpos(".$sRegion.", \"list\") = ".strpos($sRegion, "list"));
				debug("strpos(".$sRegion.", \"block\") = ".strpos($sRegion, "block"));
				
			//	if (strpos($sRegion, "list") == FALSE || strpos($sRegion, "block") == FALSE || $bInList == TRUE) {
					// Proceed, this item is neither defining nor a member of a list, nor is it in a block or a member of a block.
					$sData = processSummaryField($sPath, $aIDs, getXMLOpener($oSQLResult, $i), $sItemXML);
						
					$sResultXML .= $sData;
				//}
			}
			
		}
		debug('Result XML:'.$sResultXML);
		
		return $sResultXML;
	}
	
	
	function generateSQLSelect ($sPath, $aIDs) {
		global $sTableName_Data_BelongsIn;
		global $aSQLTableNames;
		global $sDBBelongsIn_FieldName_ParentDataTableName, $sDBBelongsIn_FieldName_ParentDataFieldName, $sDBBelongsIn_FieldName_ItemId, $sDBBelongsIn_FieldName_SharedItemId;

		debug("Generating SQL Select...");
		outputIntArray($aIDs);
		
		$aPathComponents = processPath($sPath);
		$rNumPathComponents = count($aPathComponents);

		$sTableToSelect = $aPathComponents[$rNumPathComponents - 1][0];
		$sFieldToSelect = $aPathComponents[$rNumPathComponents - 1][1];
		
		$sFieldsToSelect = $aSQLTableNames[$sTableToSelect].".".$sFieldToSelect.", ".$aSQLTableNames[$sTableToSelect].".id";
		
		$sQuery = "SELECT ".$sFieldsToSelect." FROM " ;
		
		for ($rIndex = 0; $rIndex < $rNumPathComponents; $rIndex++) {
			$aCurrentPathComponent = $aPathComponents[$rIndex];
			$sCurrentTableName = $aCurrentPathComponent[0];
			$sCurrentTableSQLName = $aSQLTableNames[$sCurrentTableName];
			
			$sQuery = $sQuery.$sCurrentTableName." ".$sCurrentTableSQLName;
			
			// Add a comma...
			if ($rNumPathComponents > 1) $sQuery = $sQuery.", ";
		}
		
		// Add the 'belongs in' tables to the FROM portion...
		$aBelongsInNames = array();
		
		for ($rIndex = 0; $rIndex < $rNumPathComponents - 1; $rIndex++) { // We need one less than we have table names in the path.
			$aBelongsInNames[$rIndex] = "B$rIndex";
			
			$sQuery = $sQuery.$sTableName_Data_BelongsIn." ".$aBelongsInNames[$rIndex];
			
			if ($rIndex < ($rNumPathComponents - 2) && ($rNumPathComponents - 1) > 1) {
				// Add a comma...
				$sQuery = $sQuery.", ";
			}
		}
		
		
		for ($rIndex = 0; $rIndex < $rNumPathComponents - 1; $rIndex++) { // We need one less than we have table names in the path.
			$sCurrentBelongsInSQLName = $aBelongsInNames[$rIndex];
			
			if ($rIndex == 0) {
				$sQuery = $sQuery." WHERE ";
			}
			else {
				$sQuery = $sQuery." AND ";
			}
			
				$sQuery = $sQuery.$sCurrentBelongsInSQLName.".".$sDBBelongsIn_FieldName_ParentDataTableName." = '".$aPathComponents[$rIndex][0]."'";
				$sQuery = $sQuery." AND ".$sCurrentBelongsInSQLName.".".$sDBBelongsIn_FieldName_ParentDataFieldName." = '".$aPathComponents[$rIndex][1]."'";
				$sQuery = $sQuery." AND ".$aSQLTableNames[$aPathComponents[$rIndex][0]].".id = ".$sCurrentBelongsInSQLName.".".$sDBBelongsIn_FieldName_ItemId;
				$sQuery = $sQuery." AND ".$aSQLTableNames[$aPathComponents[$rIndex + 1][0]].".id = ".$sCurrentBelongsInSQLName.".".$sDBBelongsIn_FieldName_SharedItemId;
		}
		
		if ($rNumPathComponents == 1) {
			$sQuery = $sQuery." WHERE ";
		}
		else {
			$sQuery = $sQuery." AND ";
		}
		
		$sQuery = $sQuery.$aSQLTableNames[$aPathComponents[0][0]].".id = '".$aIDs[0]."'";

		$rNumIDs = count($aIDs);
		if ($rNumIDs > 1) {
			for ($j = 1; $j < $rNumIDs; $j++) {
				$sQuery = $sQuery." AND ";
				$sQuery = $sQuery.$aSQLTableNames[$aPathComponents[$j][0]].".id = '".$aIDs[$j]."'";
			}
		}
		
		debug("Query so far: $sQuery");
		
		return $sQuery;
	}
	
	
	function processSummaryField ($sPath, $aIDs, $sXMLOpener, $sItemXML) {
		debug("Processing summary field...");
		outputIntArray($aIDs);
		
		$sQuery = generateSQLSelect($sPath, $aIDs);
		
		debug("Query so far: $sQuery");		
				
		$oResult = mysql_query($sQuery) or debug("SQL error fetching data.");
		$rNumResults = mysql_num_rows($oResult);
		
		$sResultXML = "";
		
		$aPathComponents = processPath($sPath);
		$rNumPathComponents = count($aPathComponents);

		//$sTableToSelect = $aPathComponents[$rNumPathComponents - 1][0];
		$sFieldToSelect = $aPathComponents[$rNumPathComponents - 1][1];
		
		for ($i = 0; $i < $rNumResults; $i++) {
			
			$sDB_ID = mysql_result($oResult, $i, "id");

			if ($sItemXML != NULL) {
				$sResultXML .= "<".$sItemXML." db_id='".$sDB_ID."'>";
			}
			
			$sResultXML .= $sXMLOpener." db_id='".$sDB_ID."'>";
			
			
			debug("In processSummaryField, i = $i, fieldToSelect = $sFieldToSelect");		

			$sData = mysql_result($oResult, $i, $sFieldToSelect);

			$sResultXML .= urlencode($sData);

			$sResultXML .= getXMLCloser();
			
			if ($sItemXML != NULL) {
				$sResultXML .= "</".$sItemXML.">";
			}
		}
		
		return $sResultXML;
	}
	
	
	function getXMLOpener ($oSQLResult, $rRowNumber) {
		$rNumDataFields = mysql_num_fields($oSQLResult);
		
		$sResultXML = "<SummaryData ";
		
		for ($j = 0; $j < $rNumDataFields; $j++) {
			$sCurrentFieldName = mysql_field_name($oSQLResult, $j);
			$sResultXML .= $sCurrentFieldName."=\"".urlencode(mysql_result($oSQLResult, $rRowNumber, $sCurrentFieldName))."\" ";
		}

		//$sResultXML .= ">";
		
		return $sResultXML;
	}
	
	
	function getXMLCloser () {		
		$sResultXML = "</SummaryData>";
		
		return $sResultXML;
	}
	

	function processBlock ($oSQLResult, $rBlockFieldNumber, $aIDs, $sParentID) {
		global $sTableName_Settings_SummaryFields;
		global $sDBSettings_FieldName_Region;
		global $sDatabaseName_Settings;

		$sResultXML = getXMLOpener($oSQLResult, $rBlockFieldNumber)." db_id='".$sParentID."'>";

		$sBlockID = mysql_result($oSQLResult, $rBlockFieldNumber, 'id');
		
		debug("Processing block:".$sBlockID);		
		
		// Select all of the summary fields that belong to this block:
		
		$sBlockID = "block_".$sBlockID;
		
		$sQueryR = "SELECT * FROM ".$sTableName_Settings_SummaryFields." WHERE ".$sDBSettings_FieldName_Region."='".$sBlockID."' ORDER BY weight";

		debug("Block query: ".$sQueryR);

		@mysql_select_db($sDatabaseName_Settings) or $sSQLError = "Unable to open database";
		$oSummaryFieldsResult = mysql_query($sQueryR);
		$rNumSummaryFields = mysql_num_rows($oSummaryFieldsResult);
		
		if ($oSummaryFieldsResult != FALSE) {
			$sResultXML = $sResultXML.ProcessSummaryFields($oSummaryFieldsResult, $aIDs, FALSE, "BlockItem", $sParentID);
		}

		$sResultXML .= getXMLCloser();
		
		return $sResultXML;
	}
	
	
	function processList ($oSQLResult, $rListFieldNumber, $aIDs, $sParentID) {
		global $sTableName_Settings_SummaryFields;
		global $sDBSettings_FieldName_Region;
		global $sDatabaseName_Settings;
		global $sDatabaseName_Data;

		global $aProcessedLists;
		global $aTimesRequiredToProcess;
		
		$listID = mysql_result($oSQLResult, $rListFieldNumber, 'id');

		debug("Processing list:".$listID);
		outputIntArray($aIDs);

		$sResultXML = getXMLOpener($oSQLResult, $rListFieldNumber)." db_id='".$sParentID."'>";
		
		// Select all of the summary fields that belong to this list:
		
		
		$sListID = "list_";
		$sListID = $sListID.$listID;
	
		$sQueryR = "SELECT * FROM ".$sTableName_Settings_SummaryFields." WHERE ".$sDBSettings_FieldName_Region."='".$sListID."' ORDER BY weight";
		$sQueryRL = "SELECT * FROM ".$sTableName_Settings_SummaryFields." WHERE ".$sDBSettings_FieldName_Region."='".$sListID."' AND type='list' ORDER BY weight;";

		debug("Querrry: ".$sQuery);
	
		@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open database";

		$sPath = mysql_result($oSQLResult, $rListFieldNumber, 'path').'id';

		$sQuery = generateSQLSelect($sPath, $aIDs);
		
		debug("IDs Querrry: ".$sQuery);
		$oResult = mysql_query($sQuery);
		
		if ($oResult != FALSE) {
			$rNumItemsToList = mysql_num_rows($oResult);		
		}
		

		
		@mysql_select_db($sDatabaseName_Settings) or $sSQLError = "Unable to open database";
		$oSummaryFieldsResult = mysql_query($sQueryR);
		$rNumSummaryFields = mysql_num_rows($oSummaryFieldsResult);
		
		$oSummaryListFieldsResult = mysql_query($sQueryRL);
		$rNumSummaryListFields = mysql_num_rows($oSummaryListFieldsResult);
	
		$aSummaryListFields = array ();
		
		if ($oSummaryListFieldsResult != FALSE) {
			for ($i = 0; $i < $rNumSummaryListFields; $i++) {
				$aSummaryListFields[count($aSummaryListFields)] = mysql_result($oSummaryListFieldsResult, $i, 'id');
			}
		}

		if ($oSummaryFieldsResult != FALSE) {
			for ($i = 0; $i < $rNumSummaryFields; $i++) {
				$sCurrentID = mysql_result($oSummaryFieldsResult, $i, 'id');
				
				$aInfo = array();
				$aInfo[0] = $sCurrentID;
				$aInfo[1] = $rNumItemsToList;
				$aTimesRequiredToProcess[count($aTimesRequiredToProcess)] = $aInfo;
				
				debug("Summary field id ".$sCurrentID." numItemsToList = ".$rNumItemsToList.", count(aTimesRequiredToProcess) = ".count($aTimesRequiredToProcess));

			}
		}


		
		$rListLength = count($aIDs);
		
		if ($oResult != FALSE) {
			debug($rNumItemsToList." items in list...");
			
			for ($i = 0; $i < $rNumItemsToList; $i++) {
				$rCurrentID = mysql_result($oResult, $i, 'id');
				
				debug("Current ID: ".$rCurrentID);
				
				// if the number of path components > list length      (number of path components - $aIDs) > 1
				$aPathComponents = processPath($sPath);
				
				if ((count($aPathComponents) - count($aIDs)) <= 1) {
					$aIDs[$rListLength] = $rCurrentID;
					$sResultXML = $sResultXML."<ListItem>";
					$sResultXML = $sResultXML.ProcessSummaryFields($oSummaryFieldsResult, $aIDs, TRUE, NULL, $rCurrentID);
					$sResultXML = $sResultXML."</ListItem>";
				}
				else {
					$i = $rNumItemsToList + 1;
					$sResultXML = $sResultXML.ProcessSummaryFields($oSummaryFieldsResult, $aIDs, TRUE, "ListItem", $rCurrentID);

					for ($k = 0; $k < $rNumSummaryListFields; $k++) {
						adjustProcessingSteps($aSummaryListFields[$k], 0);
					}
				}

			}
		}


		debug("Done processing: ".$listID);
		$aProcessedLists[$listID] = $aProcessedLists[$listID] + 1;
		
		$sResultXML .= getXMLCloser();
		
		return $sResultXML;
	}
	
	
	function adjustProcessingSteps ($rID, $rNewAmount) {
		global $aTimesRequiredToProcess;

		debug("adjusting processing steps for id: ". $rID." to ".$rNewAmount);
		
		$bFound = FALSE;

		for ($i = 0; $i < count($aTimesRequiredToProcess) && $bFound == FALSE; $i++) {
			debug("aProcessedLists[".$rID."]: ".$aProcessedLists[$rID].", aTimesRequiredToProcess[".$i."][0]: ".$aTimesRequiredToProcess[$i][0].", aTimesRequiredToProcess[".$i."][1]: ".$aTimesRequiredToProcess[$i][1]);

			if ($rID == $aTimesRequiredToProcess[$i][0]) {
				$aTimesRequiredToProcess[$i][1] = $rNewAmount;
				$bFound = TRUE;
			}
		}
	}
	
	
	function checkIfProcessed ($rListID) {
		global $aProcessedLists;
		global $aTimesRequiredToProcess;
		
		debug("Checking: ".$rListID);

		$bDone = FALSE;
		$bFound = FALSE;

		$rNumItems = 0;

		debug("count(aTimesRequiredToProcess):".count($aTimesRequiredToProcess));
		
		for ($i = 0; $i < count($aTimesRequiredToProcess) && $bFound == FALSE; $i++) {
			debug("aProcessedLists[".$rListID."]: ".$aProcessedLists[$rListID].", aTimesRequiredToProcess[".$i."][0]: ".$aTimesRequiredToProcess[$i][0].", aTimesRequiredToProcess[".$i."][1]: ".$aTimesRequiredToProcess[$i][1]);

			if ($rListID == $aTimesRequiredToProcess[$i][0]) {
				$rNumItems = $aTimesRequiredToProcess[$i][1];
				$bFound = TRUE;
			}
		}
		

		if ($bFound == TRUE) {
			if ($aProcessedLists[$rListID] >= $rNumItems) {
				$bDone = TRUE;
			}
		}
		
		return $bDone;
	}
	
	// Queries the belongs in database for to get all of the possibly shared data fields.
	function getSharedInfo () {
		global $sDBBelongsIn_FieldName_ParentDataTableName, $sDBBelongsIn_FieldName_ParentDataFieldName;
		global $aSharedDataInfo;
		global $sTableName_Data_BelongsIn;
		global $sDatabaseName_Data;
		
		@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open database";

		// Build an SQL query to get all of the linked fields so we can trace everything for a tile:
		$sSQLQuery = "SELECT DISTINCT B.$sDBBelongsIn_FieldName_ParentDataTableName, B.$sDBBelongsIn_FieldName_ParentDataFieldName FROM $sTableName_Data_BelongsIn B";

		debug("Belongs In Query: $sSQLQuery");
		
		$oResult = mysql_query($sSQLQuery);

		$rNumLinked = mysql_num_rows($oResult);		

		debug("Num linked: $rNumLinked");
		
		if ($oResult != FALSE && $rNumLinked > 0) {
			for ($rIndex = 0; $rIndex < $rNumLinked; $rIndex++) {
				$aSharedData = array();
				
				$aSharedData[0] = mysql_result($oResult, $rIndex, $sDBBelongsIn_FieldName_ParentDataTableName);
				$aSharedData[1] = mysql_result($oResult, $rIndex, $sDBBelongsIn_FieldName_ParentDataFieldName);
				
				debug("$rIndex: Table: $aSharedData[0] Field: $aSharedData[1]");
				$aSharedDataInfo[$rIndex] = $aSharedData;
			}
		}
	} // function getSharedInfo ()
	
	
	// Check the array $aSharedDataInfo to see if there is an entry that contains both $sPathTableName and $sPathFieldName. If so,
	// this returns the index of the found item, otherwise it returns -1.
	function getSharedDataIndex ($sPathTableName, $sPathFieldName) {
		global $aSharedDataInfo;
		
		$rIndex = 0;
		$rSharedDataCount = count($aSharedDataInfo);
		
		for ($rIndex = 0; $rIndex < $rSharedDataCount; $rIndex++) {			
			if ($aSharedDataInfo[$rIndex][1] == $sPathFieldName && $aSharedDataInfo[$rIndex][0] == $sPathTableName) {
				// Found key at $rIndex.
				
				return $rIndex;
			}
		}
		
		return -1;
	} // function getSharedDataIndex ($sPathTableName, $sPathFieldName)
	
	
	// This returns the table / field name for the data pointed to by $sPath
	function getSharedTableName ($sPath) {
		global $sDBReadibleNames_FieldName_TableName, $sDBReadibleNames_FieldName_Path;
		global $sTableName_Data_ReadibleNames;

		$sQuery = "SELECT DISTINCT R.".$sDBReadibleNames_FieldName_TableName.
					" FROM ".$sTableName_Data_ReadibleNames." R WHERE R.".$sDBReadibleNames_FieldName_Path." = '".$sPath."';";
				
		debug("Query: ".$sQuery);

		$oTableNameResult = mysql_query($sQuery);

		if ($oTableNameResult != FALSE) {
			$sTableName = mysql_result($oTableNameResult, 0, $sDBReadibleNames_FieldName_TableName);
			
			return $sTableName;
		}
		else {
			error("Error null SQL result for query:".$sQuery);
		}
		
		// Error if we get here...
		return null;
	} // function getSharedTableName ($sPath)
	
		
	// Used to parse the data paths.
	function processPath ($sPath) {		
		debug("Processing path on '$sPath'.");
		
		$rStartPos = 0;
		$rEndPos = 0;
		
		$aPathComponents = array();
		$rPathLength = strlen($sPath);
		
		while ($rEndPos != $rPathLength) {
			$rEndPos = strpos($sPath, ">", $rStartPos);
			
			if ($rEndPos == FALSE) {
				$rEndPos = strlen($sPath);
			}
			
			$sPathComponent = substr($sPath, $rStartPos, $rEndPos - $rStartPos);

			debug("Path component: '$sPathComponent'");
			
			// Parse out the table and field name...
			$rSeperatorLocation = strpos($sPathComponent, ".");
			$sPathTableName = substr($sPathComponent, 0, $rSeperatorLocation);
			$sPathFieldName = substr($sPathComponent, $rSeperatorLocation + 1, strlen($sPathComponent) - ($rSeperatorLocation + 1));
			
			debug("Table Name: '$sPathTableName', Field Name: '$sPathFieldName'");
			
			$rInsertionPoint = count($aPathComponents);
			$aPathComponents[$rInsertionPoint][0] = $sPathTableName;
			$aPathComponents[$rInsertionPoint][1] = $sPathFieldName;
			
			$rStartPos = $rEndPos + 1;
		}
		
		$rNumComponents = count($aPathComponents);
		for ($rComponentIndex = 0; $rComponentIndex < $rNumComponents; $rComponentIndex++) {
			$sPathTableName = $aPathComponents[$rComponentIndex][0];

				addRequiredTable($sPathTableName);

		}
		
		return $aPathComponents;
	} // function processPath ($sPath)
	
	
	// Set the header to be text/XML:
	header('Content-Type: text/xml');

	// Output the HTTPRequest info:
	if ($bDebug == TRUE) {
		$getValues = array_keys($_GET);
	
		debug("All request keys: ");
		foreach($getValues as $getKey) {
			debug($getKey."=".$_GET[$getKey]);
		}
	}
		
	// Make a Database Connection
	$oLink = mysql_connect($sDBServer, $sDBUsername, $sDBPassword);
	@mysql_select_db($sDBSetupDatabaseName) or $sSQLError = "Unable to open database";
	
	
	// Check for errors connecting to database:
	if ($sSQLError != '') {
		error($sSQLError);
	}
	else {
		$sTileInfoResult = "<TileInfo>";
		
		$sInstallID = $_GET["installID"];
		if ($sInstallID == '') {
			error("No 'installID' parameter found - can't continue.");
		}
		else {
			// We should have a database connection by now, get the required setup info:
			getSetupInfo($sInstallID);
	
			// Get the shared data info:
			getSharedInfo();
			
			$rTileID = $_GET["tileID"];
			if ($rTileID == '') {
				error("No tileID argument found.");
				$rTileID = '-1';
			}
	
			$aIDs = array();
			$aIDs[0] = $rTileID;		// Index 0 holds the tile ID...
			
			$sResult .= "<Header>";
			$sResult .= ProcessSummaryFields(getSummaryFields('header'), $aIDs, FALSE, NULL, $rTileID);
			$sResult .= "</Header>";
			$sResult .= "<Left>";
			$sResult .= ProcessSummaryFields(getSummaryFields('left'), $aIDs, FALSE, NULL, $rTileID);
			$sResult .= "</Left>";
			$sResult .= "<Right>";
			$sResult .= ProcessSummaryFields(getSummaryFields('right'), $aIDs, FALSE, NULL, $rTileID);
			$sResult .= "</Right>";
			$sResult .= "<Footer>";
			$sResult .= ProcessSummaryFields(getSummaryFields('footer'), $aIDs, FALSE, NULL, $rTileID);
			$sResult .= "</Footer>";
			
			$sTileInfoResult = $sTileInfoResult.$sResult;
		}
	}
	
	$sTileInfoResult = $sTileInfoResult."</TileInfo>";
	debug("TileInfo: $sTileInfoResult");
			
	$sResponse = $sResponse."<Results>";
	$sResponse = $sResponse.$sTileInfoResult;

	// Add the error XML:
	$sResponse = $sResponse."<Errors>";
	foreach($aErrorArray as $sError) {
		$sResponse.= "<Error>".$sError."</Error>";
	}
	$sResponse = $sResponse."</Errors>";

	$rEndTime = microtime_Float();
	$rTotalTime = $rEndTime - $rStartTime;
	debug("Total execution time: $rTotalTime seconds.");

	if ($bDebug == TRUE) {
		// Add the debug XML:
		$sResponse = $sResponse."<DebugInfo>";
		foreach($aDebugArray as $sDebug) {
			$sResponse.= "<Message>".$sDebug."</Message>";
		}
		$sResponse = $sResponse."</DebugInfo>";
	}
	else {
		// Output the execution time:
		$sResponse = $sResponse."<Time total='$rTotalTime'></Time>";
	}
	$sResponse = $sResponse."</Results>";

	// Close the database connection:
	mysql_close($oLink);
	
	echo $sResponse;
	
	return $sResponse;
?>