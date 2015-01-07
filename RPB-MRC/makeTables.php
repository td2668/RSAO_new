<?php
	require_once('DB_init.php');
	
	// Storage for the setup database / table names that we need.
	$sDatabaseName_Data = "";
	$sTableName_Data_MainData = "";

	// Storage for the XML Response:
	$sResponse = "";

	$aSQLTableNames = array();	// Holds [TableName] => SQLVariableName for SELECT statement...
	
	debug("here...");
	
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


			debug("Got setup info:");
			debug("Main DB Name = '$sDatabaseName_Data'");
			debug("Main Data Table Name = '$sTableName_Data_MainData'");
		}
	} // function getSetupInfo ($sInstallId)
	
	
	function readFromFile ($sFileName) {
		$sReturn = '';
		$fHandle = fopen($sFileName, "r");
		while (!feof($fHandle))
		$sReturn .= fread($fHandle, 4096);
		fclose($fHandle);
		return($sReturn);
	}
	
	
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
	debug("here...");
	
	// Check for errors connecting to database:
	if ($sSQLError != '') {
		error($sSQLError);
	}
	else {
		$sTileInfoResult = "<TileInfo>";

		
		// We should have a database connection by now, get the required setup info:
		getSetupInfo($_GET["installID"]);
		
		@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open database";

		debug("here...");
		$sInputFile = readFromFile("./makeTables.sql");
		
		debug($sInputFile);
				
		$sQuery = strtok($sInputFile, "\n");
		
		while ($sQuery !== false) {

			if (strncmp($sQuery, "#", 1) == 0 || $sQuery == '') {
			}
			else {
				debug($sQuery);
				$oResult = mysql_query($sQuery);
				
				if ($oResult != FALSE) {
					$sResult = $sResult."Success";
				}
				else {
					error('Invalid query: ' . mysql_error().' for query: '.$sQuery);
					$sResult = $sResult."Failure";
				}
			}
			
				
			$sQuery = strtok("\n");
		}
			
		$sTileInfoResult = $sTileInfoResult.$sResult;
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