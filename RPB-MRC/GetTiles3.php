<?php
	require_once('DB_init.php');

	// Storage for the setup database / table names that we need.
	$sDatabaseName_Data = "";
	$sTableName_Data_MainData = "";
	$sTableName_Data_BelongsIn = "";
	$sTableName_Data_ReadibleNames = "";

	$sDatabaseName_Settings = "";
	$sTableName_Settings_Options = "";
	
	// Storage for the XML Response:
	$sResponse = "";

	$sCurrentSort = "";
	
	$sSortParamString = "";
	$bUseBrackets = TRUE;

	// Storage for the Sort Criteria / Required Tables
	$aSortKeyArray = array();	// 2-D array - outer array is sort key , inner[0] = sortKeyTableName, inner[1] = sortKeyFieldName, inner[2] = optional select value, inner[3] = readibleName.
	$aSQLTableNames = array();	// Holds [TableName] => SQLVariableName for SELECT statement...
	$aSharedDataInfo = array();	// 2-D array - outer array is parentTable.parentField, inner[0] = parentTable, inner[1] = parentField, inner[2] = SQLBelongsInVarName, inner[3] = childTableName.
	
	$aGroupInfo = array();

	
	// Queries the setup info database for the setup info for install ID $sInstallId. Once finished, this will
	// select the appropriate database for future queries.
	function getSetupInfo ($sInstallId) {
		global $sDBSetup_FieldName_InstallID;
		
		global $sDBSetup_FieldName_DatabaseName_Data;
		global $sDBSetup_FieldName_TableName_Data_MainData;
		global $sDBSetup_FieldName_TableName_Data_BelongsIn;
		global $sDBSetup_FieldName_TableName_Data_ReadibleNames;
		
		global $sDBSetup_FieldName_DatabaseName_Settings;
		global $sDBSetup_FieldName_TableName_Settings_Options;
		
		global $sDBSetupTableName;

		global $sDatabaseName_Data;
		global $sTableName_Data_MainData, $sTableName_Data_BelongsIn, $sTableName_Data_ReadibleNames;
				
		global $sDatabaseName_Settings;
		global $sTableName_Settings_Options;
		
		$sQuery = "SELECT S.".$sDBSetup_FieldName_DatabaseName_Data.
					", S.".$sDBSetup_FieldName_TableName_Data_MainData.
					", S.".$sDBSetup_FieldName_TableName_Data_BelongsIn.
					", S.".$sDBSetup_FieldName_TableName_Data_ReadibleNames.
					", S.".$sDBSetup_FieldName_DatabaseName_Settings.
					", S.".$sDBSetup_FieldName_TableName_Settings_Options.
					" FROM ".$sDBSetupTableName." S WHERE S.".$sDBSetup_FieldName_InstallID." = '".$sInstallId."';";
		
		debug("Setup Info Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);

		if ($oResult != FALSE) {
			$sDatabaseName_Data = mysql_result($oResult, 0, $sDBSetup_FieldName_DatabaseName_Data);
			$sTableName_Data_MainData = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Data_MainData);
			$sTableName_Data_BelongsIn = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Data_BelongsIn);
			$sTableName_Data_ReadibleNames = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Data_ReadibleNames);

			$sDatabaseName_Settings = mysql_result($oResult, 0, $sDBSetup_FieldName_DatabaseName_Settings);
			$sTableName_Settings_Options = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Settings_Options);
			
			debug("Got setup info:");
			debug("Main DB Name = '$sDatabaseName_Data'");
			debug("Main Data Table Name = '$sTableName_Data_MainData'");
			debug("Belongs In Data Table Name = '$sTableName_Data_BelongsIn'");
			debug("Readible Names Data Table Name = '$sTableName_Data_ReadibleNames'");
			
			debug("Settings DB Name = '$sDatabaseName_Settings'");
			debug("Options Data Table Name = '$sTableName_Settings_Options'");
		}
		
		@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open database";
	} // function getSetupInfo ($sInstallId)
	
	
	// Used to parse the data paths.
	function processPath ($sPath, $sDestinationTableName) {
		global $aSharedDataInfo;
		
		debug("Processing path on '$sPath' for table '$sDestinationTableName'.");
		
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
			$sPathFieldName = $aPathComponents[$rComponentIndex][1];
			
			// Check to make sure this path hasn't already been added...
			$rIndex = getSharedDataIndex($sPathTableName, $sPathFieldName);
			
			if ($rIndex == -1) {
				// We need to add an entry into the shared data array for this path component...
				debug("Entry needed...");
				
				$rIndex = count($aSharedDataInfo);
				
				$aSharedDataInfo[$rIndex][0] = $sPathTableName;
				$aSharedDataInfo[$rIndex][1] = $sPathFieldName;
				
				addRequiredTable($sPathTableName);

				// Add a belongs in reference...
				$aSharedDataInfo[$rIndex][2] = "B$rIndex";
				
				// Add the name of the child table that this points to:
				
				if ($rComponentIndex == (count($aPathComponents) - 1)) {
					// There is not another path component to get the child info from...
					$aSharedDataInfo[$rIndex][3] = $sDestinationTableName;
				}
				else {
					$aSharedDataInfo[$rIndex][3] = $aPathComponents[$rComponentIndex + 1][0];
				}
				
				debug("Done adding entry: [0] = ".$aSharedDataInfo[$rIndex][0].", [1] = ".$aSharedDataInfo[$rIndex][1].", [2] = ".$aSharedDataInfo[$rIndex][2].", [3] = ".$aSharedDataInfo[$rIndex][3]);
			}
		}
	} // function processPath ($sPath, $sDestinationTableName)
	
	
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
	
	
	// Queries the SQL database to find the readible name with the lowest priority that matches the
	// specified table name, field name, and path.
	function getBestReadibleName ($sTableName, $sFieldName, $sPath) {
		global $sDBReadibleNames_FieldName_FieldName, $sDBReadibleNames_FieldName_TableName, $sDBReadibleNames_FieldName_Path, $sDBReadibleNames_FieldName_ReadibleName, $sDBReadibleNames_FieldName_Priority;
		global $sTableName_Data_ReadibleNames;

		$sQuery = "SELECT RN.".$sDBReadibleNames_FieldName_ReadibleName.
				", MIN(RN.".$sDBReadibleNames_FieldName_Priority.
				") AS 'Lowest' FROM ".$sTableName_Data_ReadibleNames." RN WHERE ".
				"RN.".$sDBReadibleNames_FieldName_FieldName." = '".$sFieldName."' AND ".
				"RN.".$sDBReadibleNames_FieldName_TableName." = '".$sTableName."' AND ".
				"RN.".$sDBReadibleNames_FieldName_Path." = '".$sPath."' GROUP BY RN.".$sDBReadibleNames_FieldName_ReadibleName.
				" ORDER BY 'Lowest' LIMIT 1";
				
		debug("Query: ".$sQuery);

		$oBestNameResult = mysql_query($sQuery);

		if ($oBestNameResult != FALSE) {
			$sBestName = mysql_result($oBestNameResult, 0, $sDBReadibleNames_FieldName_ReadibleName);
			
			return $sBestName;
		}
		else {
			error("Error null SQL result for query:".$sQuery);
		}
		
		// Error if we get here...
		return null;
	} // function getBestReadibleName ($sTableName, $sFieldName, $sPath)
	

	// Used to parse the sort keys.
	function processSortKey ($sKey, $sSelectValue) {
		global $aSortKeyArray;
		global $sDBReadibleNames_FieldName_FieldName, $sDBReadibleNames_FieldName_TableName, $sDBReadibleNames_FieldName_Path, $sDBReadibleNames_FieldName_ReadibleName;
		global $sTableName_Data_ReadibleNames;
		global $sSortParamString;
		
		//$sSelectValue = str_replace("%20", " ", $sSelectValue);
		//$sSelectValue = str_replace("%26", "&", $sSelectValue);
		$sSelectValue = str_replace("{}", "&", urldecode($sSelectValue));

		debug("Processing key: ".urlencode($sKey).", Select value: ".urlencode($sSelectValue));
		
		$sQuery = "SELECT RN.".$sDBReadibleNames_FieldName_FieldName.
					", RN.".$sDBReadibleNames_FieldName_TableName.
					", RN.".$sDBReadibleNames_FieldName_Path.
					" FROM ".$sTableName_Data_ReadibleNames." RN WHERE RN.".$sDBReadibleNames_FieldName_ReadibleName." = '".$sKey."';";
		
		debug("Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);

		if ($oResult != FALSE) {
			$rNumResults = mysql_num_rows($oResult);

			$rIndex = 0;
			while ($rIndex < $rNumResults) {
			
				$sFieldName = mysql_result($oResult, $rIndex, $sDBReadibleNames_FieldName_FieldName);
				$sTableName = mysql_result($oResult, $rIndex, $sDBReadibleNames_FieldName_TableName);
				$sPath = mysql_result($oResult, $rIndex, $sDBReadibleNames_FieldName_Path);
			
				debug("Table Name: ".$sTableName);
				debug("Field Name: ".$sFieldName);
				debug("Path: ".$sPath);
				
				// TO DO: It would be best to query the server and find the optimum readible name based on priority...
				debug("Readible Name: ".$sKey);
				
				$sBestName = getBestReadibleName($sTableName, $sFieldName, $sPath);
				
				debug("Best Readible Name: ".$sBestName);
				
				if ($sBestName == null) {
					error("Could not find best readible name for table: " + $sTableName + ", field: " + $sFieldName + ", path: " + $sPath);
					$sBestName = $sKey;
				}
				
				// Record the information we will need for the sort key:
				$aSortKeyArray[$sBestName][0] = $sTableName;
				$aSortKeyArray[$sBestName][1] = $sFieldName;
				$aSortKeyArray[$sBestName][2] = $sSelectValue;
				$aSortKeyArray[$sBestName][3] = $sBestName;
				
				if ($sSortParamString != "") $sSortParamString = $sSortParamString."&";
				
				if ($sSelectValue == null) $sSelectValue = 'null';
				$sSortParamString = $sSortParamString.$sBestName."=".$sSelectValue;
				
				// Add this table to our list of requried tables (and generate a SQL variable name)
				addRequiredTable($sTableName);
						
				if ($sPath != null) {
					// This data is shared, add the shared data info...
					processPath($sPath, $sTableName);
				}
				
				$rIndex++;				
			}
		}
		else {
			error("Error null SQL result for query:".$sQuery);
		}
		
		if ($rNumResults == 0) {
			error("Error finding human readible name for key:".$sKey);
		}
	} // function processSortKey ($sKey)
	
	
	// Check the array $aGroupInfo to see if there is an entry that contains the group named $sCurrrentGroupName.
	// If found, this returns the index of the found item, otherwise it returns -1.
	function getGroupNameIndex ($sCurrrentGroupName) {
		global $aGroupInfo;
		
		$rIndex = 0;
		$rGroupInfoSize = count($aGroupInfo);
		
		for ($rIndex = 0; $rIndex < $rGroupInfoSize; $rIndex++) {			
			if ($aGroupInfo[$rIndex][0][0] == $sCurrrentGroupName) {
				// Found key at $rIndex.
				
				return $rIndex;
			}
		}
		
		return -1;
	} // function getGroupNameIndex ($aCurrrentGroupInfo)
	
	
	function getOptionValue ($sOptionName) {
		global $sDatabaseName_Settings, $sDatabaseName_Data;
		global $sTableName_Settings_Options;
		
		debug("sDatabaseName_Settings = $sDatabaseName_Settings");
		
		@mysql_select_db($sDatabaseName_Settings) or $sSQLError = "Unable to open settings database:".$sDatabaseName_Settings;
	
		$sOptionQuery = "SELECT O.optionValue FROM $sTableName_Settings_Options O WHERE O.optionName = '$sOptionName'";
		
		debug("sOptionQuery = $sOptionQuery");

		$oResult = mysql_query($sOptionQuery);
		
		@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open 'data' database:".$sDatabaseName_Data;

		$rNumRows = mysql_numrows($oResult);
		
		if ($rNumRows == 0) return null;
		else return mysql_result($oResult, $rIndex, "optionValue");
	}
	
	
	// Examines the row in $oResult at $rRowIndex using the $aSortKeyArray array. Returns an array with two strings. Index
	// 0 holds the generated group name and index 1 holds the HTTPRequest params that are needed by this script to select that group.
	function getGroupInfo ($oResult, $rRowIndex) {
		global $aSortKeyArray;
		
		global $sCurrentSort, $bUseBrackets;
		
		debug("Attempting to name group... row: $rRowIndex");
		
		// Storage for the group name:
		$sGroupName = "";
		
		// Storage for the group sort parameters (for the group name link):
		$sGroupSortParameters = "";
		
		$aGroupInfoResult = array();
		
		$aSortKeyValues = array();
		$aSelectedKeyValues = array();
		
		$bNeedsAnd = FALSE;
		$counter = 0;
		
		foreach ($aSortKeyArray as $aSortKeyInfo) {
			$counter++;
			// First add any items that have no select value...
			
			$sFieldName = $aSortKeyInfo[1];
			$sSelectValue = $aSortKeyInfo[2];
			$sReadibleName = $aSortKeyInfo[3];
			
			$sSQLResult = mysql_result($oResult, $rRowIndex, $sFieldName);
			
			debug("Key field name = ".urlencode($sFieldName).", Key select Value = ".urlencode($sSelectValue).", Key Readible Name = ".urlencode($sReadibleName));
			debug("Result from DB = ".urlencode($sSQLResult));
			
			if ($bNeedsAnd == TRUE) {
				$sGroupSortParameters = $sGroupSortParameters."</LinkArgument><LinkArgument ";
			}
			else {
				$sGroupSortParameters = $sGroupSortParameters."<LinkArgument ";
			}
			
			$sGroupSortParameters = $sGroupSortParameters."key=\"".urlencode($sReadibleName)."\">";
			$sGroupSortParameters = $sGroupSortParameters.urlencode($sSQLResult);
				
			if ($sSelectValue == null || $sSelectValue == 'null') {
				// No select value, add this item to the $aSortKeyValues array...
				$aSortKeyValues[count($aSortKeyValues)] = $sSQLResult;
			}
			else {
				// Has select value, add this item to the $aSelectedKeyValues array...
				$aSelectedKeyValues[count($aSelectedKeyValues)] = $sSQLResult;
			}
			
			$bNeedsAnd = TRUE;
		}
		if ($counter > 0) $sGroupSortParameters = $sGroupSortParameters."</LinkArgument>";

		$bBracketsRequired = FALSE;
		$bPlusRequired = FALSE;
		
		foreach ($aSortKeyValues as $sSortKeyValue) {
			if ($bPlusRequired == TRUE) $sGroupName = $sGroupName." + ";
			
			$sGroupName = $sGroupName.$sSortKeyValue;
			
			$bPlusRequired = TRUE;
			$bBracketsRequired = TRUE;
		}
			
		if ($bBracketsRequired == TRUE && count($aSelectedKeyValues) > 0 && $bUseBrackets) {
			$sGroupName = $sGroupName." (";
		}
		
		$bPlusRequired = FALSE;

		if (($bBracketsRequired == TRUE && $bUseBrackets) || $bBracketsRequired == FALSE) {
			$sInnerBrackets = "";
			foreach ($aSelectedKeyValues as $sSelectedKeyValue) {
				if ($bPlusRequired == TRUE) $sInnerBrackets = $sInnerBrackets." + ";
				
				$sInnerBrackets = $sInnerBrackets.$sSelectedKeyValue;
				
				$bPlusRequired = TRUE;
			}
			$sGroupName = $sGroupName.$sInnerBrackets;
		}
		
		if ($sCurrentSort == "") {
			debug("Setting sCurrentSort to :".$sInnerBrackets);
			$sCurrentSort = $sInnerBrackets;
		}
		
		if ($bBracketsRequired == TRUE && count($aSelectedKeyValues) > 0 && $bUseBrackets) {
			$sGroupName = $sGroupName.")";
		}
			
		debug("Group name: '".urlencode($sGroupName)."'");
		
		$aGroupInfoResult[0] = $sGroupName;
		$aGroupInfoResult[1] = $sGroupSortParameters;
		
		return $aGroupInfoResult;
	}
	
	//$sTemp = file_get_contents("http://127.0.0.1/~eze/RPB_MRC/makeTables.php?installID=5");
//	require("/RPB_MRC/makeTables.php?installID=5");

	// Set the header to be text/XML:
	//header('Content-Type: text/xml');
	header("Content-Type: text/xml; charset=utf-8");
	header("Cache-Control: no-cache, must-revalidate");
	
	// Output the HTTPRequest info:
	if ($bDebug == TRUE) {
		$getValues = array_keys($_GET);
	
		debug("All request keys: ");
		foreach($getValues as $getKey) {
			debug($getKey."=".urlencode($_GET[$getKey]));
		}
	}
		
	// Make a Database Connection
	$oLink = mysql_connect($sDBServer,$sDBUsername,$sDBPassword);
	@mysql_select_db($sDBSetupDatabaseName) or $sSQLError = "Unable to open database";
	
	
	// Check for errors connecting to database:
	if ($sSQLError != '') {
		error($sSQLError);
	}
	else {
		$sOutputRemainder = $_GET["showRemainder"];
		if ($sOutputRemainder == '') {
			$sOutputRemainder = 'false';
		}
		
		$sInstallID = $_GET["installID"];
		if ($sInstallID == '') {
			error("No 'installID' parameter found - can't continue.");
		}
		else {		
			// We should have a database connection by now, get the required setup info:
			getSetupInfo($sInstallID);
			
			addRequiredTable($sTableName_Data_MainData);
			
			$bUseBrackets_Temp = getOptionValue("tileGroup_UseBrackets");
			if ($bUseBrackets_Temp == null) {
				$bUseBrackets = true;
			}
			else {
				if ($bUseBrackets_Temp == 'false' || $bUseBrackets_Temp == 'False' || $bUseBrackets_Temp == 'FALSE' || $bUseBrackets_Temp == false) {
					$bUseBrackets = false;
				}
				else {
					$bUseBrackets = true;
				}
			}
			
			// Process the sort keys...
			$aSortKeys = array_keys($_GET);
					
			foreach($aSortKeys as $sSortKey) {
				if ($sSortKey != "installID" && $sSortKey != "showRemainder") {
					
					$sSortKeyWithSpaces = str_replace("_", " ", $sSortKey);
					
					processSortKey($sSortKeyWithSpaces, $_GET[$sSortKey]);
				}
			}
			
			$sSortParamString = urlencode($sSortParamString);
					
			debug("sSortParamString = $sSortParamString");
			
			// Build an SQL query based on the processed keys:
			$sSQLQuery = "SELECT ".$aSQLTableNames[$sTableName_Data_MainData].".id";
			
			// Build the non-matching query:
			$sSQLQuery_NonMatching = "SELECT ".$aSQLTableNames[$sTableName_Data_MainData]."_NM".".id FROM $sTableName_Data_MainData ".$aSQLTableNames[$sTableName_Data_MainData]."_NM"." WHERE ".$aSQLTableNames[$sTableName_Data_MainData]."_NM".".id NOT IN (";
			$sSQLQuery_NonMatching = $sSQLQuery_NonMatching."SELECT ".$aSQLTableNames[$sTableName_Data_MainData].".id";
			
			if ($sOutputRemainder == 'true') {
				// Build the remaining query (lists groups with no members):
				$sSQLQuery_Remaining = "SELECT DISTINCT ";
				$sSQLQuery_Remaining_NotIn = "SELECT ";
			}
			
			$bFirst = TRUE;

			// Add the keys we are selcting on:
			foreach ($aSortKeyArray as $aSortKeyInfo) {
				$sTableName = $aSortKeyInfo[0];
				$sFieldName = $aSortKeyInfo[1];
				
				debug("Building select... table name = '$sTableName', fieldName = '$sFieldName', selectValue = '$sSelectValue'.");
				
				$sSQLQuery .= ", ".$aSQLTableNames[$sTableName].".".$sFieldName;
				
				if ($sOutputRemainder == 'true') {
					if ($bFirst == TRUE) {
						$sSQLQuery_Remaining .= $aSQLTableNames[$sTableName].".".$sFieldName;
						$sSQLQuery_Remaining_NotIn .= $aSQLTableNames[$sTableName].".".$sFieldName;
						
						$bFirst = FALSE;
					}
					else {
						$sSQLQuery_Remaining .= ", ".$aSQLTableNames[$sTableName].".".$sFieldName;
						$sSQLQuery_Remaining_NotIn .= ", ".$aSQLTableNames[$sTableName].".".$sFieldName;
					}
				}
			}
			
			// Add the from portion:
			$sSQLQuery .= " FROM ";
			$sSQLQuery_NonMatching .= " FROM ";
			
			if ($sOutputRemainder == 'true') {
				$sSQLQuery_Remaining .= " FROM ";
				$sSQLQuery_Remaining_NotIn .= " FROM ";
			}
			
			$bFirst = TRUE;
			$bSecond = TRUE;
			
			foreach ($aSQLTableNames as $sTableName) {
				$sTableRealName = KeyName($aSQLTableNames, $sTableName);
				//$sTableName = $aSortKeyInfo[0];
				
				debug("Still buiding, table name = '$sTableName', sTableRealName = '$sTableRealName'.");
				
				if ($bFirst == FALSE) {
					$sSQLQuery .= ", ";
					$sSQLQuery_NonMatching .= ", ";
					
					if ($sOutputRemainder == 'true') {
						if ($bSecond == FALSE) {
							$sSQLQuery_Remaining .= ", ";
						}
						$sSQLQuery_Remaining_NotIn .= ", ";
	
						$bSecond = FALSE;
					}
				}
				
				$sSQLQuery .= $sTableRealName." ".$aSQLTableNames[$sTableRealName];
				$sSQLQuery_NonMatching .= $sTableRealName." ".$aSQLTableNames[$sTableRealName];
				
				if ($sOutputRemainder == 'true') {
					if ($bFirst == FALSE) $sSQLQuery_Remaining .= $sTableRealName." ".$aSQLTableNames[$sTableRealName];
					$sSQLQuery_Remaining_NotIn .= $sTableRealName." ".$aSQLTableNames[$sTableRealName];
				}
				
				$bFirst = FALSE;
			}
			
			// Add any belongsIn tables that we will need:
			foreach ($aSharedDataInfo as $aSharedData) {
				$sBelongsInSQLName = $aSharedData[2];
				
				$sSQLQuery .= ", ".$sTableName_Data_BelongsIn." ".$sBelongsInSQLName;
				$sSQLQuery_NonMatching .= ", ".$sTableName_Data_BelongsIn." ".$sBelongsInSQLName;

				if ($sOutputRemainder == 'true') $sSQLQuery_Remaining_NotIn .= ", ".$sTableName_Data_BelongsIn." ".$sBelongsInSQLName;
			}		
	
			
			// Add the WHERE clause:

			if ($sOutputRemainder == 'true') {
				$sSQLQuery_Remaining .= " WHERE (";
	
				$bFirst = TRUE;
	
				foreach ($aSortKeyArray as $aSortKeyInfo) {
					$sTableName = $aSortKeyInfo[0];
					$sFieldName = $aSortKeyInfo[1];
					
					if ($bFirst == TRUE) {
						$sSQLQuery_Remaining .= $aSQLTableNames[$sTableName].".".$sFieldName;
						$bFirst = FALSE;
					}
					else {
						$sSQLQuery_Remaining .= ", ".$aSQLTableNames[$sTableName].".".$sFieldName;
					}
				}
				
				$sSQLQuery_Remaining .= ") NOT IN (";
			}
			
			$bFirst = TRUE;
			$bNeedsAnd = FALSE;
			$bNeedsWhere = TRUE;
			
			foreach ($aSharedDataInfo as $aSharedData) {
				$sParentTableName = $aSharedData[0];
				$sParentFieldName = $aSharedData[1];
				$sBelongsInSQLName = $aSharedData[2];
				$sChildTableName = $aSharedData[3];
				
				if ($bNeedsWhere == TRUE) {
					$sSQLQuery .= " WHERE ";
					$sSQLQuery_NonMatching .= " WHERE ";
					if ($sOutputRemainder == 'true') $sSQLQuery_Remaining_NotIn .= " WHERE ";
					$bNeedsWhere = FALSE;
				}
				if ($bFirst == FALSE) {
					$sSQLQuery .= " AND ";
					$sSQLQuery_NonMatching .= " AND ";
					if ($sOutputRemainder == 'true') $sSQLQuery_Remaining_NotIn .= " AND ";
				}
							
				$sBelongsInClauses = $sBelongsInSQLName.".".$sDBBelongsIn_FieldName_ParentDataTableName." = '".$sParentTableName."' AND ";
				$sBelongsInClauses .= $sBelongsInSQLName.".".$sDBBelongsIn_FieldName_ParentDataFieldName." = '".$sParentFieldName."' AND ";
				$sBelongsInClauses .= $aSQLTableNames[$sParentTableName].".id = ".$sBelongsInSQLName.".".$sDBBelongsIn_FieldName_ItemId." AND ";
				$sBelongsInClauses .= $aSQLTableNames[$sChildTableName].".id = ".$sBelongsInSQLName.".".$sDBBelongsIn_FieldName_SharedItemId;
				
				$sSQLQuery .= $sBelongsInClauses;
				$sSQLQuery_NonMatching .= $sBelongsInClauses;
				if ($sOutputRemainder == 'true') $sSQLQuery_Remaining_NotIn .= $sBelongsInClauses;

				$bNeedsAnd = TRUE;
				$bFirst = FALSE;
			}
			
			// Add the select values to the WHERE clause:
			foreach ($aSortKeyArray as $aSortKeyData) {
				$sSelectValue = $aSortKeyData[2];
				
				if ($sSelectValue != null && $sSelectValue != "null") {
					$sTableName = $aSortKeyData[0];
					$sFieldName = $aSortKeyData[1];
					
					if ($bNeedsWhere == TRUE) {
						$sSQLQuery .= " WHERE ";
						$sSQLQuery_NonMatching .= " WHERE ";
						
						if ($sOutputRemainder == 'true') $sSQLQuery_Remaining_NotIn .= " WHERE ";

						$bNeedsWhere = FALSE;
					}
					if ($bNeedsAnd == TRUE) {
						$sSQLQuery .= " AND ";
						$sSQLQuery_NonMatching .= " AND ";
						
						if ($sOutputRemainder == 'true') $sSQLQuery_Remaining_NotIn .= " AND ";
					}
					
					$sSQLQuery .= $aSQLTableNames[$sTableName].".".$sFieldName." = '".str_replace("\\", "\\\\", str_replace("'", "\'", $sSelectValue))."'";
					$sSQLQuery_NonMatching .= $aSQLTableNames[$sTableName].".".$sFieldName." = '".str_replace("\\", "\\\\", str_replace("'", "\'", $sSelectValue))."'";
					
					if ($sOutputRemainder == 'true') $sSQLQuery_Remaining_NotIn .= $aSQLTableNames[$sTableName].".".$sFieldName." = '".str_replace("\\", "\\\\", str_replace("'", "\'", $sSelectValue))."'";

					$bNeedsAnd = TRUE;
				}
			}
			
			$sSQLQuery .= ";";
			$sSQLQuery_NonMatching .= ");";
			
			if ($sOutputRemainder == 'true') $sSQLQuery_Remaining .= $sSQLQuery_Remaining_NotIn.");";

	//		debug("Main Query:".urlencode($sSQLQuery));
			debug("Main Query:".($sSQLQuery));
	//		debug("Final NonMatching Query:".urlencode($sSQLQuery_NonMatching));
			debug("Final NonMatching Query:".($sSQLQuery_NonMatching));
			if ($sOutputRemainder == 'true') debug("Final Remainder Query:".urlencode($sSQLQuery_Remaining));
	//		if ($sOutputRemainder == 'true') debug("Final Remainder Query:".($sSQLQuery_Remaining));
			
	
			
			// Get the matching tiles:
			$oResult = mysql_query($sSQLQuery);
			$rNumMatchingTiles = mysql_num_rows($oResult);		
			
			$rAddedGroups = 0;
			
			if ($oResult != FALSE && $rNumMatchingTiles > 0) {
				for ($rIndex = 0; $rIndex < $rNumMatchingTiles; $rIndex++) {
					$aCurrrentGroupInfo = getGroupInfo($oResult, $rIndex);
					
					$sCurrentTileID = mysql_result($oResult, $rIndex, "id");
					//$sCurrentTileName = mysql_result($oResult, $rIndex, "name");
					
					// Check the $aGroupInfo array to see if we already have an entry for this group:
					$rGroupNameIndex = getGroupNameIndex($aCurrrentGroupInfo[0]);
									
					if ($rGroupNameIndex != -1) {
						// We have already added this group name to the $aGroupInfo array, add the tile id to this index...
						$aTileIdArray = $aGroupInfo[$rGroupNameIndex][1];
						$aGroupInfo[$rGroupNameIndex][1][count($aTileIdArray)] = $sCurrentTileID;
						
						//$aTileNameArray = $aGroupInfo[$rGroupNameIndex][2];
						//$aGroupInfo[$rGroupNameIndex][2][count($aTileNameArray)] = $sCurrentTileName;
					}
					else {
						// We have not encountered this group name before. Add it to the $aGroupInfo array along with the tile id...
						$aGroupInfo[$rAddedGroups][0] = $aCurrrentGroupInfo;
						$aGroupInfo[$rAddedGroups][1] = array($sCurrentTileID);
						//$aGroupInfo[$rAddedGroups][2] = array($sCurrentTileName);
						
						$rAddedGroups = $rAddedGroups + 1;
					}
				}
				
			}
			
			// Get a name for the Non-Matching group:
			$sNonMatchingGroupName = "";
			$bNeedsAnd = FALSE;
			
			foreach ($aSortKeyArray as $aSortKeyInfo) {
				// First add any items that have no select value...
				
				$sFieldName = $aSortKeyInfo[1];
				$sSelectValue = $aSortKeyInfo[2];
				$sReadibleName = $aSortKeyInfo[3];
				
				
				if ($sSelectValue != null && $sSelectValue != 'null') {
					if ($bNeedsAnd == TRUE) $sNonMatchingGroupName = $sNonMatchingGroupName." + ";
	
					$sNonMatchingGroupName = $sNonMatchingGroupName."NOT '".$sSelectValue."'";
					$bNeedsAnd = TRUE;
				}
				else {
					if ($sNonMatchingGroupName == "") $bNeedsAnd = FALSE;
					else $bNeedsAnd = TRUE;
				}
			}
			debug("NonMatching Group Name:".urlencode($sNonMatchingGroupName));
			
			// Get the non-matching tiles:
			$aNonMatchingTiles = array();
			
			$oResult = mysql_query($sSQLQuery_NonMatching);
			$rNumNonMatchingTiles = mysql_num_rows($oResult);
			
			if ($oResult != FALSE && $rNumNonMatchingTiles > 0) {
				for ($rIndex = 0; $rIndex < $rNumNonMatchingTiles; $rIndex++) {
					$sCurrentTileID = mysql_result($oResult, $rIndex, "id");
	
					$aNonMatchingTiles[$rIndex] = $sCurrentTileID;
				}
			}
			
			if ($sOutputRemainder == 'true') {
				// Get the remaining groups:
				$aRemainingGroups = array();
				
				$oResult = mysql_query($sSQLQuery_Remaining);
				$rNumRemainingGroups = mysql_num_rows($oResult);
				$rNumRemainingGroups_Fields = mysql_num_fields($oResult);
				
				if ($oResult != FALSE && $rNumRemainingGroups > 0) {
					for ($rIndex = 0; $rIndex < $rNumRemainingGroups; $rIndex++) {
						$sCurrentTileID = '';
						for ($rIndex2 = 0; $rIndex2 < $rNumRemainingGroups_Fields; $rIndex2++) {
							$sCurrentGroupName = mysql_result($oResult, $rIndex, $rIndex2);
							
							if ($rIndex2 > 0) {
								$sCurrentTileID .= ', ';
							}
							$sCurrentTileID .= $sCurrentGroupName;
						}
						$aRemainingGroups[$rIndex] = $sCurrentTileID;
					}
				}
			}
			
			// $aGroupInfo should be populated now - generate the group XML:
			$rNumMatchingGroups = count($aGroupInfo);
			$rNumNonMatchingGroups = ($rNumNonMatchingTiles > 0) ? 1 : 0;
			
			$sResponse = $sResponse."<Results paramString='".$sSortParamString."' currentSortText='".urlencode(str_replace('&', '{}', $sCurrentSort))."'>";
			
			$sResponse = $sResponse."<MatchingGroups numGroups='".$rNumMatchingGroups."' numTiles='".$rNumMatchingTiles."'>";
			
			$sLowestName = "";
			$rLowestIndex = 0;
			$aUsedSpaces = array();
			for ($i = 0; $i < $rNumMatchingGroups; $i++) {
				$aUsedSpaces[$i] = FALSE;
			}
			
			for ($i = 0; $i < $rNumMatchingGroups; $i++) {
				// First pass... init this with the first name found.
				for ($j = 0; $j < $rNumMatchingGroups; $j++) {
					if ($aUsedSpaces[$j] == FALSE) {
						$sLowestName = $aGroupInfo[$j][0][0];
						$rLowestIndex = $j;
					}
				}
				
				for ($j = 0; $j < $rNumMatchingGroups; $j++) {
					
					if ($aUsedSpaces[$j] == FALSE) {
						$aCurrentGroupInfo = $aGroupInfo[$j];
						$sCurrentName = $aCurrentGroupInfo[0][0];
						
						if ($sCurrentName < $sLowestName) {
							$sLowestName = $sCurrentName;
							$rLowestIndex = $j;
						}
						
					}
				}
				
				// We should have the lowest index...
				$aUsedSpaces[$rLowestIndex] = TRUE;
				$aCurrentGroupInfo = $aGroupInfo[$rLowestIndex];
				
				$sResponse .= "<Group numTiles='".count($aCurrentGroupInfo[1])."'>";
				if ($rNumMatchingGroups == 1 && $rNumNonMatchingTiles == 0) {
					$sResponse .= "<Name>".getOptionValue('tileGroup_Label_AllTilesText')."</Name>";
				}
				else {
					$sResponse .= "<Name>".urlencode(str_replace('&', '{}', $aCurrentGroupInfo[0][0]))."</Name>";
				}
				$sResponse .= "<LinkArguments>".str_replace('&', '{}', $aCurrentGroupInfo[0][1])."</LinkArguments>";
				$sResponse .= "<Tiles>";
				
				foreach ($aCurrentGroupInfo[1] as $sTileId) {
					$sResponse .= "<Tile id='$sTileId'></Tile>";
				}
				
				$sResponse .= "</Tiles>";
				$sResponse .= "</Group>";
			}
			
			
			$sResponse .= "</MatchingGroups>";
			
			$sResponse .= "<NonMatchingGroups numGroups='".$rNumNonMatchingGroups."'>";
			if ($rNumNonMatchingGroups > 0) {
				$sResponse .= "<Group numTiles='$rNumNonMatchingTiles'>";
				$sResponse .= "<Name>".urlencode($sNonMatchingGroupName)."</Name>";
				$sResponse .= "<Tiles>";
				
				foreach ($aNonMatchingTiles as $sTileId) {
					$sResponse .= "<Tile id='$sTileId'></Tile>";
				}
				
				$sResponse .= "</Tiles>";
				$sResponse .= "</Group>";
			}
			$sResponse .= "</NonMatchingGroups>";
			
			if ($sOutputRemainder == 'true') {
				// Add the remaining groups (if required...)
				$sResponse .= "<RemainingGroups numGroups='".$rNumRemainingGroups."'>";
				if ($rNumRemainingGroups > 0) {
					foreach ($aRemainingGroups as $sGroupName) {
						$sResponse .= "<Group>";
						$sResponse .= "<Name>".urlencode($sGroupName)."</Name>";
						$sResponse .= "</Group>";
					}
				}
				$sResponse .= "</RemainingGroups>";
			}
		}
	}
	
	// Add the error XML:
	$sResponse .= "<Errors>";
	foreach($aErrorArray as $sError) {
		$sResponse.= "<Error>".$sError."</Error>";
	}
	$sResponse .= "</Errors>";

	$rEndTime = microtime_Float();
	$rTotalTime = $rEndTime - $rStartTime;
	debug("Total execution time: $rTotalTime seconds.");

	if ($bDebug == TRUE) {
		// Add the debug XML:
		$sResponse .= "<DebugInfo>";
		foreach($aDebugArray as $sDebug) {
			$sResponse.= "<Message>".$sDebug."</Message>";
		}
		$sResponse .= "</DebugInfo>";
	}
	else {
		// Output the execution time:
		$sResponse .= "<Time total='$rTotalTime'></Time>";
	}
	$sResponse .= "</Results>";

	// Close the database connection:
	mysql_close($oLink);
	
	echo $sResponse;
?>