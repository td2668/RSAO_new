<?php
	require_once('DB_init.php');

	// Storage for the database / table names that we need.

	$sDatabaseName_Users = "";
	$sTableName_Users_Users = "";
	$sTableName_Users_Roles = "";
	
	// Storage for the XML Response:
	$sResponse = "";

	// Queries the setup info database for the setup info for install ID $sInstallId.
	function getSetupInfo ($sInstallId) {
		global $sDBSetupTableName;
	
		global $sDBSetup_FieldName_InstallID;
		
		global $sDBSetup_FieldName_DatabaseName_Users;
		global $sDBSetup_FieldName_TableName_Users_Roles;
		global $sDBSetup_FieldName_TableName_Users_Users;
		
		global $sDatabaseName_Users;
		global $sTableName_Users_Users;
		global $sTableName_Users_Roles;
		
		$sQuery = "SELECT S.".$sDBSetup_FieldName_DatabaseName_Users.
					", S.".$sDBSetup_FieldName_TableName_Users_Roles.
					", S.".$sDBSetup_FieldName_TableName_Users_Users.
					" FROM ".$sDBSetupTableName." S WHERE S.".$sDBSetup_FieldName_InstallID." = '".$sInstallId."';";
		
		debug("Setup Info Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);

		if ($oResult != FALSE) {
			$sDatabaseName_Users = mysql_result($oResult, 0, $sDBSetup_FieldName_DatabaseName_Users);
			$sTableName_Users_Roles = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Users_Roles);
			$sTableName_Users_Users = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Users_Users);

			debug("Got setup info:");
			debug("Users DB Name = '$sDatabaseName_Users'");
			debug("User Roles Table Name = '$sTableName_Users_Roles'");
			debug("User List Table Name = '$sTableName_Users_Users'");
		}
	} // function getSetupInfo ($sInstallId)
	
	
	function validateUser ($sUserName, $sUserPass) {
		global $sDatabaseName_Users;
		global $sTableName_Users_Users;
		global $sTableName_Users_Roles;
		
		global $sDBUsers_FieldName_Name;
		global $sDBUsers_FieldName_Pass;
		global $sDBUsers_FieldName_Role;
		global $sDBUsers_FieldName_TileID;
	
		global $sDBRoles_FieldName_Name;
		global $sDBRoles_FieldName_EditableTiles;
		
		// Check if the user / pass is valid:
		
		@mysql_select_db($sDatabaseName_Users) or error("Unable to open database '$sDatabaseName'");
		
		$sQuery = "SELECT $sDBUsers_FieldName_Role, $sDBUsers_FieldName_TileID FROM $sTableName_Users_Users WHERE $sDBUsers_FieldName_Name = '".$sUserName."' AND $sDBUsers_FieldName_Pass = '".$sUserPass."'";

		debug("Validate User Query: ".$sQuery);

		$oResult = mysql_query($sQuery);

		if ($oResult != FALSE) {
			$rNumRows = mysql_numrows($oResult);
			
			if ($rNumRows > 0) {
				$sRole = mysql_result($oResult, 0, $sDBUsers_FieldName_Role);
				$sTileID = mysql_result($oResult, 0, $sDBUsers_FieldName_TileID);
				
			}
			else {
				$sResponse = "<User name='$sUserName' tileID='NONE'>";
				$sResponse = $sResponse."</User>";
		
				return $sResponse;
			}
		}
		else {
			error("Error in query: $sQuery");
		}

		// Get the role information:
		
		$sQuery = "SELECT $sDBRoles_FieldName_EditableTiles FROM $sTableName_Users_Roles WHERE $sDBRoles_FieldName_Name = '".$sRole."'";

		debug("User Role Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);

		if ($oResult != FALSE) {
			$rNumRows = mysql_numrows($oResult);
			
			if ($rNumRows > 0) {
				$sEditableTiles = mysql_result($oResult, 0, $sDBRoles_FieldName_EditableTiles);
			}
			else {
				error("Can't find role: $sRole");
			}
		}
		else {
			error("Error in query: $sQuery");
		}
		
		// Build the XML Response...
		
		if ($sTileID == NULL) {
			$sTileID = $sEditableTiles;
		}
		$sResponse = "<User name='$sUserName' tileID='$sTileID'>";
		$sResponse = $sResponse."</User>";
		
		return $sResponse;
	} // function validateUser ($sUserName, $sUserPass)
	
	

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
	@mysql_select_db($sDBSetupDatabaseName) or $sSQLError = "Unable to open database '$sDBSetupDatabaseName'";
	
	
	// Check for errors connecting to database:
	if ($sSQLError != '') {
		error($sSQLError);
	}
	else {
		$sInstallID = $_GET["installID"];
		$sUserID = $_GET["user"];
		$sPass = $_GET["pass"];
		if ($sInstallID == '' || $sUserID == '' || $sPass == '') {
			if ($sInstallID == '') error("No 'installID' parameter found - can't continue.");
			if ($sUserID == '') error("No 'user' parameter found - can't continue.");
			if ($sPass == '') error("No 'pass' parameter found - can't continue.");
		}
		else {
			// We should have a database connection by now, get the required setup info:
			getSetupInfo($sInstallID);
			
			$sResponse = $sResponse."<Results>";
	
			$sResponse = $sResponse.validateUser($_GET["user"], $_GET["pass"]);
		}
	}
	
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