<?php
	require_once('DB_init.php');
	
	// Storage for the setup database / table names that we need.
	$sDatabaseName_Data = "";
	$sTableName_Data_MainData = "";
	
	$sDatabaseName_Settings = "";
	$sTableName_Settings_Options = "";
	$sTableName_Settings_SortControls = "";
	$sTableName_Settings_Styles = "";
	$sTableName_Settings_SummaryFields = "";
	
	// Storage for the XML Response:
	$sResponse = "";
	
	// Queries the setup info database for the setup info for install ID $sInstallId.
	function getSetupInfo ($sInstallId) {
		global $sDBSetup_FieldName_InstallID;
		
		global $sDBSetup_FieldName_DatabaseName_Data;
		global $sDBSetup_FieldName_TableName_Data_MainData;
		
		global $sDBSetup_FieldName_DatabaseName_Settings;
		global $sDBSetup_FieldName_TableName_Settings_Options, $sDBSetup_FieldName_TableName_Settings_SortControls, $sDBSetup_FieldName_TableName_Settings_Styles, $sDBSetup_FieldName_TableName_Settings_SummaryFields;

		global $sDBSetupTableName;
		
		global $sDatabaseName_Data;
		global $sTableName_Data_MainData;
		
		global $sDatabaseName_Settings;
		global $sTableName_Settings_Options, $sTableName_Settings_SortControls, $sTableName_Settings_Styles, $sTableName_Settings_SummaryFields;
		
		$sQuery = "SELECT S.".$sDBSetup_FieldName_DatabaseName_Data.
					", S.".$sDBSetup_FieldName_TableName_Data_MainData.
					", S.".$sDBSetup_FieldName_DatabaseName_Settings.
					", S.".$sDBSetup_FieldName_TableName_Settings_Options.
					", S.".$sDBSetup_FieldName_TableName_Settings_SortControls.
					", S.".$sDBSetup_FieldName_TableName_Settings_Styles.
					", S.".$sDBSetup_FieldName_TableName_Settings_SummaryFields.
					" FROM ".$sDBSetupTableName." S WHERE S.".$sDBSetup_FieldName_InstallID." = '".$sInstallId."';";
		
		debug("Setup Info Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);

		if ($oResult != FALSE) {
			$sDatabaseName_Data = mysql_result($oResult, 0, $sDBSetup_FieldName_DatabaseName_Data);
			$sTableName_Data_MainData = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Data_MainData);

			$sDatabaseName_Settings = mysql_result($oResult, 0, $sDBSetup_FieldName_DatabaseName_Settings);
			$sTableName_Settings_Options = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Settings_Options);
			$sTableName_Settings_SortControls = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Settings_SortControls);
			$sTableName_Settings_Styles = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Settings_Styles);
			$sTableName_Settings_SummaryFields = mysql_result($oResult, 0, $sDBSetup_FieldName_TableName_Settings_SummaryFields);

			debug("Got setup info:");
			debug("Main DB Name = '$sDatabaseName_Data'");
			debug("Main Data Table Name = '$sTableName_Data_MainData'");
			debug("Settings DB Name = '$sDatabaseName_Settings'");
			debug("Settings Options Table Name = '$sTableName_Settings_Options'");
			debug("Settings Sort Controls Table Name = '$sTableName_Settings_SortControls'");
			debug("Settings Styles Table Name = '$sTableName_Settings_Styles'");
			debug("Settings SummaryFields Table Name = '$sTableName_Settings_SummaryFields'");
		}
	} // function getSetupInfo ($sInstallId)
	
	
	// Queries the database and returns the result as XML with the root being $sRootXML and each element $sItemXML. The
	// attributes are all the field names for the table and their values are filled in correctly.
	function getSQLData ($sRootXML, $sItemXML, $sDatabaseName, $sTableName) {
		@mysql_select_db($sDatabaseName) or error("Unable to open database '$sDatabaseName'");

		$sQuery = "SELECT * FROM ".$sTableName." ORDER BY 'id'";

		debug("SQL Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);
		$rNumResultsTotal = mysql_num_rows($oResult);
	
		$oFields = mysql_list_fields($sDatabaseName, $sTableName);
		$rNumFieldsTotal = mysql_num_fields($oFields);
		
		if ($oResult != FALSE && $rNumResultsTotal > 0) {
			debug("Found ".$rNumResultsTotal." results(s).");
			debug("Found ".$rNumFieldsTotal." fields(s).");

			$sResult.="<$sRootXML num$sRootXML=\"".$rNumResultsTotal."\">";
			
			$rIndex = 0;
			while ($rIndex < $rNumResultsTotal) {
				$sResult.="<$sItemXML ";
				
				for ($i=0; $i < $rNumFieldsTotal; ++$i) {
					$oField = mysql_fetch_field($oFields, $i);
					$sFieldName = $oField->name;
					
					$sCurrentData = mysql_result($oResult, $rIndex, $sFieldName);

					$sResult.= " $sFieldName=\"".$sCurrentData."\"";
				}
				$sResult.=" ></$sItemXML>";
					
				$rIndex++;
			}
	
			$sResult.="</$sRootXML>";
		}
		else {
			error("No results or can't find database.");
		}
		
		return $sResult;
	} // function getSQLData ($sRootXML, $sItemXML, $sDatabaseName, $sTableName)
	
	
	// This queries the SQL database and returns an XML result of tile id's and images.
	// TO DO: It wouldbe better if there could be multiple image fields, the image field could have any name, or if the image field could be somewhere other than the main data table.
	function getBasicTileData ($sDatabaseName, $sTableName, $sImageField, $sNameField) {
		@mysql_select_db($sDatabaseName) or error("Unable to open database '$sDatabaseName'");

		$sRootXML = "TileData";
		$sItemXML = "Tile";
		
		if (strncmp($sNameField, "@DB_COMPOUND:", strlen("@DB_COMPOUND:")) == 0) {
			$sQuery = "SELECT id, $sImageField FROM $sTableName";
			$bCompoundName = TRUE;
		}
		else {
			$sQuery = "SELECT id, $sImageField, $sNameField FROM $sTableName";
			$bCompoundName = FALSE;
		}
		
		debug("SQL Query: ".$sQuery);
		
		$oResult = mysql_query($sQuery);
		$rNumResultsTotal = mysql_num_rows($oResult);
	
		if ($oResult != FALSE && $rNumResultsTotal > 0) {
			debug("Found ".$rNumResultsTotal." results(s).");

			$sResult.="<$sRootXML num$sRootXML=\"".$rNumResultsTotal."\">";
			
			$rIndex = 0;
			while ($rIndex < $rNumResultsTotal) {
				$sResult.="<$sItemXML ";
				
				$sResult.="id=\"".mysql_result($oResult, $rIndex, "id")."\">";
				
				$sResult.="<Image>".mysql_result($oResult, $rIndex, $sImageField)."</Image>";
				
				if ($bCompoundName) {
					$tempName = substr($sNameField, strlen("@DB_COMPOUND:") , strlen($sNameField) - (strlen("@DB_COMPOUND:")));
					debug("tempName ".$tempName);

					$nameComponents = strtok($tempName, "//");
					
					$finishedName = '';
					while ($nameComponents !== false) {
						$currentItem = $nameComponents;
						
						if (strncmp($currentItem, "@", strlen("@")) == 0) {
							// Look up this data...
							$objectID = mysql_result($oResult, $rIndex, "id");
							$fieldName = substr($currentItem, 1, strlen($currentItem) - 1);
							$sQuery = "SELECT U.$fieldName FROM $sTableName U WHERE U.id = '".$objectID."'";
							
							debug("Compound Name SQL Query: ".$sQuery);
							
							$oTempResult = mysql_query($sQuery);
							//debug("SQL field result: ".mysql_result($oTempResult, 0, $fieldName));

							if ($oTempResult != FALSE) {
								$finishedName = $finishedName.mysql_result($oTempResult, 0, $fieldName);
							}
							else {
								debug("eErr");
							}

						}
						else {
							// Static seperator...
							$sSeperatorText = substr($currentItem, 0, strlen($currentItem) - 0);
							$finishedName = $finishedName.$sSeperatorText;
						}
						
						$nameComponents = strtok("//");
					}
					$sResult.="<Name>".$finishedName."</Name>";
					
				}
				else {
					$sResult.="<Name>".mysql_result($oResult, $rIndex, $sNameField)."</Name>";
				}
				
				
				$sResult.="</$sItemXML>";
					
				$rIndex++;
			}
	
			$sResult.="</$sRootXML>";
		}
		else {
			error("No image name results or can't find database.");
		}
		
		return $sResult;
	} // function getBasicTileData ($sDatabaseName, $sTableName, $sImageField, $sNameField)
	
	
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
		if ($sInstallID == '') {
			error("No 'installID' parameter found - can't continue.");
		}
		else {		
			// We should have a database connection by now, get the required setup info:
			getSetupInfo($sInstallID);
			
			$sResponse = $sResponse."<Results>";
	
			$sControls = getSQLData("SortControls", "SortControl", $sDatabaseName_Settings, $sTableName_Settings_SortControls);
			$sStyles = getSQLData("Styles", "Style", $sDatabaseName_Settings, $sTableName_Settings_Styles);
			$sOptions = getSQLData("Options", "Option", $sDatabaseName_Settings, $sTableName_Settings_Options);
			$sSummaryFields = getSQLData("SummaryFields", "SummaryField", $sDatabaseName_Settings, $sTableName_Settings_SummaryFields);
			
			$sTileNameQuery = "SELECT O.optionValue FROM $sTableName_Settings_Options O WHERE O.optionName = 'tabs_Label_DataPath'";
			$oResult = mysql_query($sTileNameQuery);
			$sTileNamePath = mysql_result($oResult, $rIndex, "optionValue");
	
	
			$sBasicData = getBasicTileData($sDatabaseName_Data, $sTableName_Data_MainData, "imageName", $sTileNamePath);
			
		
			$sResponse = $sResponse.$sControls;
			$sResponse = $sResponse.$sStyles;
			$sResponse = $sResponse.$sOptions;
			$sResponse = $sResponse.$sSummaryFields;
			$sResponse = $sResponse.$sBasicData;
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