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
		$sInstallID = $_GET["installID"];
		$sOutputRemainder = $_GET["showRemainder"];
		if ($sOutputRemainder == '') {
			$sOutputRemainder = 'true';
		}
		$rTileID = $_GET["tileID"];
		$sListPath = $_GET["path"];
		$sListTitleField = $_GET["list_TitleField"];
		if ($sInstallID == '' || $rTileID == '' || $sListPath == '' || $sListTitleField == '') {
			if ($sInstallID == '') error("No 'installID' parameter found - can't continue.");
			if ($rTileID == '') error("No 'tileID' parameter found - can't continue.");
			if ($sListPath == '') error("No 'path' parameter found - can't continue.");
			if ($sListTitleField == '') error("No 'list_TitleField' parameter found - can't continue.");
		}
		else {
			// We should have a database connection by now, get the required setup info:
			getSetupInfo($sInstallID);
			
			@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open database: $sDatabaseName_Data";
			
			$sEndString = strrchr($sListPath, ">");
			$rEndLength = strlen($sEndString);
			
			$sPath = substr($sListPath, 0, strlen($sListPath) - $rEndLength);
			$sTableName = substr($sEndString, 1, $rEndLength - 2);
			
			$sReadibleName = getBestReadibleName($sTableName, $sListTitleField, $sPath);
			
			//debug("Query: '".$sPHPGetTilesName."?installID=".$sInstallID."&".$sReadibleName."&showRemainder=".$sOutputRemainder."'");
			
			$sListInfoResult = "<ListInfo name='".$sReadibleName."' tileID='".$rTileID."'>";

			if ($oXMLResult = simplexml_load_file($sPHPGetTilesName."?installID=".$sInstallID."&".urlencode($sReadibleName)."&showRemainder=".$sOutputRemainder)) {
				debug(print_r($oXMLResult, TRUE));
				
				foreach ($oXMLResult->MatchingGroups->Group as $oGroup) {
					// Check to see if this group contins the current tileID - if so, mark it as selected.
					$bSelected = FALSE;
					foreach ($oGroup->Tiles->Tile as $oTile) {
						if ($oTile['id'] == $rTileID) $bSelected = TRUE;
					}
					$sGroupName = $oGroup->{'Name'};
					
					$sIDQuery = "SELECT id FROM ".$sTableName." D WHERE D.".$sListTitleField."='".str_replace("'", "\'", str_replace("{}", "&", urldecode($sGroupName)))."'";
					//debug("ID's Query: ".$sIDQuery);
					
					$oDBIDResult = mysql_query($sIDQuery);

					if ($oDBIDResult != FALSE) {
						$sDBID = mysql_result($oDBIDResult, 0, "id");
					}
					else {
						error("Error null SQL result for query:".$sIDQuery);
						$sDBID = -1;
					}
		
					$sListInfoResult .= "<ListItem selected=".($bSelected ? "'true'" : "'false'")." db_id='".$sDBID."' name='".$sGroupName."'></ListItem>";
				}
				
				foreach ($oXMLResult->RemainingGroups->Group as $oGroup) {
					// Check to see if this group contins the current tileID - if so, mark it as selected.
					$bSelected = FALSE;
					
					$sGroupName = $oGroup->{'Name'};
					
					$sIDQuery = "SELECT id FROM ".$sTableName." D WHERE D.".$sListTitleField."='".str_replace("'", "\'", str_replace("{}", "&", urldecode($sGroupName)))."'";
					//debug("ID's Query: ".$sIDQuery);
					
					$oDBIDResult = mysql_query($sIDQuery);

					if ($oDBIDResult != FALSE) {
						$sDBID = mysql_result($oDBIDResult, 0, "id");
					}
					else {
						error("Error null SQL result for query:".$sIDQuery);
						$sDBID = -1;
					}
					
					$sListInfoResult .= "<ListItem selected=".($bSelected ? "'true'" : "'false'")." db_id='".$sDBID."' name='".$sGroupName."'></ListItem>";
				}
				
				debug($oXMLResult->MatchingGroups->Group[0]->{'Name'});
			}
			else {
				debug("Error");
			}
			
			//$sListInfoResult .= $oXML->Results->MatchingGroups->Group[0];
			$sListInfoResult .= "</ListInfo>";
		}
	}
	
	$sResponse = $sResponse."<Results>";
	$sResponse = $sResponse.$sListInfoResult;

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
?>