<?php
	// FILENAME: DB_init.php
	require_once('DB_functions.php');

	$rStartTime = microtime_Float();

	// Debug Information:
	$bDebug = FALSE;
	$aDebugArray = array();
	$rAddedDebugMessages = 0;
		
	// Variables to Hold Error Info:
	$sSQLError = "";
	$aErrorArray = array();
	
	// Database Information:
	$sDBServer = "bckup-sigyn.mtroyal.ca";
	$sDBUsername = "ors";
	$sDBPassword = "rilinc";
	
	// Setup info:
	$sDBSetupDatabaseName = "research";
	$sDBSetupTableName = "DB_Available_Installs";
	
	// PHP Location Info:
	$sPHPGetTilesName = "http://research.mtroyal.ca/RPB-MRC/GetTiles3.php";	
	$sPHPValidateUserName = "http://research.mtroyal.ca/RPB-MRC/validateUser.php";	

	// These are the field names in the SQL server setup database that hold relevant setup info:
	$sDBSetup_FieldName_InstallID = "id";
	
    $sDBSetup_FieldName_DatabaseName_Data = "databaseName_Data";
	$sDBSetup_FieldName_TableName_Data_MainData = "tableName_Data_MainData";
	$sDBSetup_FieldName_TableName_Data_BelongsIn = "tableName_Data_BelongsIn";
	$sDBSetup_FieldName_TableName_Data_ReadibleNames = "tableName_Data_ReadibleNames";
	
    $sDBSetup_FieldName_DatabaseName_Settings = "databaseName_Settings";
	$sDBSetup_FieldName_TableName_Settings_Options = "tableName_Settings_Options";
	$sDBSetup_FieldName_TableName_Settings_SortControls = "tableName_Settings_SortControls";
	$sDBSetup_FieldName_TableName_Settings_Styles = "tableName_Settings_Styles";
	$sDBSetup_FieldName_TableName_Settings_SummaryFields = "tableName_Settings_SummaryFields";
	
	$sDBSetup_FieldName_DatabaseName_Users = "databaseName_Users";
	$sDBSetup_FieldName_TableName_Users_Roles = "tableName_Users_Roles";
	$sDBSetup_FieldName_TableName_Users_Users = "tableName_Users_Users";
	
	// These are the field names in the SQL readible names database that we are interested in:
	$sDBReadibleNames_FieldName_TableName = "tableName";
	$sDBReadibleNames_FieldName_FieldName = "fieldName";
	$sDBReadibleNames_FieldName_Path = "path";
	$sDBReadibleNames_FieldName_Priority = "priority";
	$sDBReadibleNames_FieldName_ReadibleName = "readibleName";

	// These are the field names in the SQL belongs in database that we are interested in:
	$sDBBelongsIn_FieldName_ParentDataTableName = "parentDataTableName";
	$sDBBelongsIn_FieldName_ParentDataFieldName = "parentDataFieldName";
	$sDBBelongsIn_FieldName_ItemId = "item_id";
	$sDBBelongsIn_FieldName_SharedItemId = "sharedItem_id";
	
	// These are the field names in the summary fields table that we are interested in:
	$sDBSettings_FieldName_ID = "id";
	$sDBSettings_FieldName_TableName = "tableName";
	$sDBSettings_FieldName_FieldName = "fieldName";
	$sDBSettings_FieldName_Path = "path";
	$sDBSettings_FieldName_Region = "region";
	
	// These are the field names in the users table that we are interested in:
	$sDBUsers_FieldName_Name = "name";
	$sDBUsers_FieldName_Pass = "password";
	$sDBUsers_FieldName_Role = "role";
	$sDBUsers_FieldName_TileID = "tileID";

	// These are the field names in the roles table that we are interested in:
	$sDBRoles_FieldName_Name = "name";
	$sDBRoles_FieldName_EditableTiles = "editableTiles";
?>
