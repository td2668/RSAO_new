<?php
	// FILENAME: DB_functions.php
	
	// Returns a representation of the current time (to be used for calculating elapsed time).
	function microtime_Float () {
		return array_sum(explode(' ',microtime()));
	} // function microtime_Float ()
	
	
	// Used to report debugging info - adds the message to a global array ($aDebugArray) which is then added to the XML result.
	function debug ($sMessage) {
		global $bDebug, $aDebugArray, $rAddedDebugMessages;
		
		if ($bDebug == TRUE) {
			$aDebugArray[$rAddedDebugMessages] = $sMessage;
			$rAddedDebugMessages = $rAddedDebugMessages + 1;
		}
	} // function debug ($sMessage)
	
	
	// Used to report error info - adds the message to a global array ($aErrorArray) which is then added to the XML result.
	function error ($sMessage) {
		global $aErrorArray;
		
		$aErrorArray[count($aErrorArray)] = $sMessage;
	} // function error ($sMessage)
	
	
	// This first checks to see if the $aSQLTableNames array contains an entry for $sTableName. If not,
	// it creates a SQL variable name for it and adds it to the array.
	function addRequiredTable ($sTableName) {
		global $aSQLTableNames;
		
		// First, check to see if we have already created a variable name for this table...
		$sResult = $aSQLTableNames[$sTableName];
		
		if ($sResult == null) {
			//We need to add a SQL variable name for table '$sTableName'...
			
			$aSQLTableNames[$sTableName] = $sTableName."_1";
			
			debug("Added table name: '$aSQLTableNames[$sTableName]'.");
		}
	} // function addRequiredTable ($sTableName)
	
	
	// Returns the key in the array $myArray that references $sValue.
	function KeyName ($myArray, $sValue) {
		debug("looking for key for value '$sValue'");
		
		$asRes = array_search($sValue, $myArray);
		
		debug("Array seqrch result = $asRes");
		
		return $asRes;
	} // function KeyName ($myArray, $sValue)
	
	
	// Outputs to the debug stream an array of ints.
	function outputIntArray ($aIDs) {
		$rNumItems = count($aIDs);
		debug("Outputting ".$rNumItems." IDs:");
		
		for ($i = 0; $i< $rNumItems; $i++) {
			debug($i.": Value = '".$aIDs[$i]."'");
		}
		debug("End Output");
	} // function outputIntArray ($aIDs)
	
	
	// Using curl, returns the contents of the passed in URL, FALSE on failure.
	function curl_get_file_contents($theURL) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $theURL);
		$contents = curl_exec($c);
		curl_close($c);
		
		if ($contents) return $contents;
		else return FALSE;
	} // function curl_get_file_contents($theURL)
?>