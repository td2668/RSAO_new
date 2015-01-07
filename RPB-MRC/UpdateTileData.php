<?php
	require_once('DB_init.php');
		
	// Storage for the setup database / table names that we need.
	$sDatabaseName_Data = "";
	$sTableName_Data_MainData = "";

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

		
		global $sDBSetupTableName;

		// Storage for Found Names:
		global $sDatabaseName_Data;
		global $sTableName_Data_MainData;

				
		$sQuery = "SELECT S.".$sDBSetup_FieldName_DatabaseName_Data.
					", S.".$sDBSetup_FieldName_TableName_Data_MainData.
					" FROM ".$sDBSetupTableName." S WHERE S.".$sDBSetup_FieldName_InstallID." = '".$sInstallId."';";
		
		debug("Setup Info Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);

		if ($oResult != FALSE) {
			$sDatabaseName_Data = mysql_result($oResult, 0, $sDBSetup_FieldName_DatabaseName_Data);
			$sTableName_Data_MainData = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Data_MainData);

			// MOUNT ROYAL SPECIFIC CODE:
			
			$sTableName_Data_MainData = substr($sTableName_Data_MainData, strlen("rpb_"), (strlen($sTableName_Data_MainData) - strlen("rpb_")));
	
			// END

			debug("Got setup info:");
			debug("Main DB Name = '$sDatabaseName_Data'");
			debug("Main Data Table Name = '$sTableName_Data_MainData'");
		}
	} // function getSetupInfo ($sInstallId)
	
	
	// MOUNT ROYAL SPECIFIC CODE:
	
	// 
	function getMtRoyalIDFieldNames ($sTableName) {
		$sQuery = "SELECT id_field_name FROM rpb_id_conversion WHERE table_name = 'users';";
		
		debug("ID Coversion Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);

		$sIDFieldName = "id";
		if ($oResult != FALSE) {
			$sIDFieldName = mysql_result($oResult, 0, 'id_field_name');

			debug("Got conversion info:");
			debug("ID Field Name = '$sIDFieldName'");
		}
		
		return $sIDFieldName;
	} // function getMtRoyalIDFieldNames ($sTableName)
	
	// END

	
	function authenticateUser () {
		global $sPHPValidateUserName;
		
		$sUserName = $_POST["user"];
		if ($sUserName == '') {
			error("No user argument found - can't continue.");
			return FALSE;
		}
		
		$sPassword = $_POST["pass"];
		if ($sPassword == '') {
			error("No pass argument found - can't continue.");
			return FALSE;
		}
		
		$sTileID = $_POST["tileID"];
		if ($sTileID == '') {
			error("No tileID argument found - can't continue.");
			return FALSE;
		}

		$sInstallID = $_POST["installID"];
		if ($sInstallID == '') {
			error("No 'installID' parameter found - can't continue.");
			return FALSE;
		}


		if ($oXMLResult = simplexml_load_file($sPHPValidateUserName."?installID=".$sInstallID."&user=".$sUserName."&pass=".$sPassword)) {
			debug(print_r($oXMLResult, TRUE));
			
			foreach ($oXMLResult->User as $oUser) {
				$sAllowedList = $oUser['tileID'];
				
				debug($sAllowedList);
				
				if ($sAllowedList == '*') {
					return TRUE;
				}
				else if ($sAllowedList == 'NONE') {
					return FALSE;
				}
				else {
					$aAllowedArray = split(",", $sAllowedList);
					foreach ($aAllowedArray as $sCurrentID) {
						$sShortTileID = substr($sTileID, 0, strlen($sTileID) - 1);
						debug("CurrentID = ".$sCurrentID.", ShortTileID = ".$sShortTileID);
						if ($sCurrentID == $sShortTileID) {
							return TRUE;
						}
					}
				}				
			}
		}
		else {
			debug("Error");
		}

		
		return FALSE;
	}
	
	// Set the header to be text/XML:
	header('Content-Type: text/xml');

	// Output the HTTPRequest info:
	if ($bDebug == TRUE) {
		$getValues = array_keys($_POST);
	
		debug("All request keys: ");
		foreach($getValues as $getKey) {
			debug($getKey."=".$_POST[$getKey]);
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
		
		$bUserCanEdit = authenticateUser();
		
		if ($bUserCanEdit) {

			debug("Here...");
			
			// We should have a database connection by now, get the required setup info:
			getSetupInfo($_POST["installID"]);
			
			$rNumItems = $_POST["numItems"];
			if ($rNumItems == '') {
				error("No numItems argument found - using 1.");
				$rNumItems = '1';
			}
			

			
			@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open database";

			for ($i = 0; $i < $rNumItems; $i++) {
				$rItemID = $_POST["itemID_".$i];
				if ($rItemID == '') {
					error("No itemID_".$i." argument found.");
					$rItemID = '-1';
				}
		
				$sData = $_POST["data_".$i];
				if ($sData == '') {
					debug("No data_".$i." argument found - using ''.");
					$sData = '';
				}
				
				$sTableName = $_POST["tableName_".$i];
				if ($sTableName == '') {
					error("No tableName_".$i." argument found.");
					$sTableName = '-1';
				}
				
				// MOUNT ROYAL SPECIFIC CODE:
			
				$sTableName = substr($sTableName, strlen("rpb_"), (strlen($sTableName) - strlen("rpb_")));
			
				// END
				
				$sFieldName = $_POST["fieldName_".$i];
				if ($sFieldName == '') {
					error("No fieldName_".$i." argument found.");
					$sFieldName = '-1';
				}
			
				if ($rItemID != '-1' && $sTableName != '-1' && $sFieldName != '-1') {
					$sIDName = getMtRoyalIDFieldNames($sTableName);
				
					$sQuery = "UPDATE ".$sTableName." SET ".$sTableName.".".$sFieldName." = '".$sData."' WHERE ".$sTableName.".".$sIDName." = '".$rItemID."'";
					
					$oResult = mysql_query($sQuery);
	
					if ($oResult != FALSE) {
						$sResult = $sResult."Success";
					}
					else {
						error('Invalid query: ' . mysql_error());
						$sResult = $sResult."Failure";
					}
				}
				else {
					error('Invalid query: ' . mysql_error());
					$sResult = $sResult."Failure";
				}
			}
			
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