<?php
class Template {
	/*******************************************************************************
	Function:		loadTemplate($inFileLocation,$inHash,$inFormat)
	Description:	reads in a template file and replaces hash values
	Arguments:		$inFileLocation as string with relative directory
					$inHash as Hash with populated values
					$inFormat as string either "text" or "html"
	Returns:		true if loaded
	*******************************************************************************/
	function loadTemplate($inFileLocation,$inHash,$inFormat){
		/*
		template files have lines such as:
			Dear ~!UserName~,
			Your address is ~!UserAddress~
		*/
		//--specify template delimeters
		$templateDelim = "~";
		$templateNameStart = "!";
		//--set out string
		$templateLineOut = "";
		//--open template file
		if($templateFile = fopen($inFileLocation,"r")){
			//--loop through file, line by line
			while(!feof($templateFile)){
				//--get 1000 chars or (line break internal to fgets)
				$templateLine = fgets($templateFile,1000);
				//--split line into array of hashNames and regular sentences
				$templateLineArray = explode($templateDelim,$templateLine);
				//--loop through array
				for( $i=0; $i<count($templateLineArray)-1;$i++){ // -1
					//--look for $templateNameStart at position 0
					if(strcspn($templateLineArray[$i],$templateNameStart)==0){
						//--get hashName after $templateNameStart
						$hashName = substr($templateLineArray[$i],1);
						// creates a temporary hash variable
						$tmpHash = (isset($inHash[$hashName])) ? $inHash[$hashName] : '';
						// checks if hash variable is an array,
						// if it is, loop through array and add values to $replace_with
						if (!is_array($tmpHash)) $replace_with = (isset($inHash[$hashName])) ? $inHash[$hashName] : '';
						else {
							while (list ($key, $value) = each($tmpHash)) {
								$replace_with .= $value;
							}
						}
						//--replace hashName with acual value in $inHash
						//--(string) casts all values as "strings"
						@$templateLineArray[$i] = ereg_replace($hashName,(string)$replace_with,$hashName);
						// clear variable for next line
						unset($replace_with);
					}
				}
				//--output array as string and add to out string
				$templateLineOut .= implode($templateLineArray,"");
			}
			//--close file
			fclose($templateFile);
			//--set template file body to proper format
			//if( strtoupper($inFormat)=="HTML" )
			return $templateLineOut;
		}
		return false;
	}
}
?>