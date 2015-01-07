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


			debug("Got setup info:");
			debug("Main DB Name = '$sDatabaseName_Data'");
			debug("Main Data Table Name = '$sTableName_Data_MainData'");
		}
	} // function getSetupInfo ($sInstallId)
	
	
	
	
	function authenticateUser () {
		global $sPHPValidateUserName;
		
		$sUserName = $_GET["user"];
		if ($sUserName == '') {
			error("No user argument found - can't continue.");
			return FALSE;
		}
		
		$sPassword = $_GET["pass"];
		if ($sPassword == '') {
			error("No pass argument found - can't continue.");
			return FALSE;
		}
		
		$sTileID = $_GET["tileID"];
		if ($sTileID == '') {
			error("No tileID argument found - can't continue.");
			return FALSE;
		}

		$sInstallID = $_GET["installID"];
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
						debug("CurrentID = ".$sCurrentID);
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
		
		$bUserCanEdit = authenticateUser();
		
		if ($bUserCanEdit) {

			debug("Here...");
			
			// We should have a database connection by now, get the required setup info:
			getSetupInfo($_GET["installID"]);
			
			$rNumItems = $_GET["numItems"];
			if ($rNumItems == '') {
				error("No numItems argument found - using 1.");
				$rNumItems = '1';
			}
			
			$sMode = $_GET["mode"];
			if ($sMode == '') {
				error("No mode argument found.");
				$sMode = null;
			}
			
			@mysql_select_db($sDatabaseName_Data) or $sSQLError = "Unable to open database";

			$bDataCleared = false;
			$sTileID = $_GET["tileID"];
			$sShortTileID = substr($sTileID, 0, strlen($sTileID) - 1);

			for ($i = 0; $i < $rNumItems; $i++) {
				$sItemID = $_GET["itemID_".$i];
				if ($sItemID == '') {
					error("No itemID_".$i." argument found.");
					$sItemID = '-1';
				}
				
			
				if ($sItemID != '-1') {
					if ($sMode == 'topic') {
						// Check if it's a valid item...
						$sQuery = "SELECT id FROM rpb_topics_research D WHERE D.id='".$sItemID."'";
						debug("ID Validation query: ".$sQuery);
						$oResult = mysql_query($sQuery);
	
						if ($oResult != FALSE) {
							$rNumMatching = mysql_num_rows($oResult);
							if ($rNumMatching > 0) {
								$sResult = $sResult."Id is valid...";
								
								if ($bDataCleared == false) {
									// Clear any 'Topic' data...
									$bDataCleared = true;
									
									$sQuery = "DELETE FROM user_topics_profile WHERE user_id = '".$sShortTileID."'";
									debug("Clear data query: ".$sQuery);
									
									$oClearResult = mysql_query($sQuery);
									if ($oClearResult != FALSE) {
										$sResult = $sResult."cleared topic data for tile:".$sTileID;
									}
									else {
										error('Invalid query: ' . mysql_error());
										$bDataCleared = false;
									}
								}
								
								if ($bDataCleared == true) {
									$sQuery = "INSERT INTO user_topics_profile (topic_id, user_id) VALUES (".$sItemID.",".$sShortTileID.")";
									debug("Update data query: ".$sQuery);
									
									$oUpdateResult = mysql_query($sQuery);
									if ($oUpdateResult != FALSE) {
										$sResult = $sResult."Updated topic data for tile:".$sTileID;
									}
									else {
										error('Invalid query: ' . mysql_error());
									}
								}
								else {
									error("Could not clear data, not continuing.");
								}
							}
							else {
								error('Item '.$i.' ID is not valid: '.$sItemID);
							}
						}
						else {
							error('Invalid query: ' . mysql_error());
						}

					}
					else if ($sMode == 'department') {
						// Check if it's a valid item...
						$sQuery = "UPDATE users SET department_id = '".$sItemID."' WHERE user_id = '".$sShortTileID."'";
						debug("Update Topics query: ".$sQuery);
						$oResult = mysql_query($sQuery);
	
						if ($oResult == FALSE) {
							error('Invalid query: ' . mysql_error());
						}
					}
					else {
						error("Invalid mode argument supplied (".$sMode." is not a valid option).");
					}

				}
				else {
					error('Invalid query: ' . mysql_error());
					$sResult = $sResult."Failure";
				}
			}
			
			if ($rNumItems == 0) {
				// Remove all relationships for this shared data.
				$sQuery = "DELETE FROM user_topics_profile WHERE user_id = '".$sShortTileID."'";
				debug("Clear data query: ".$sQuery);
				
				$oClearResult = mysql_query($sQuery);
				if ($oClearResult != FALSE) {
					$sResult = $sResult."cleared topic data for tile:".$sTileID;
				}
				else {
					error('Invalid query: ' . mysql_error());
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