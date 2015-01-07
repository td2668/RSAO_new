<?php

/*
----------------------------|
MySQL Custom Query Functions|
----------------------------|
*/
function mysqlQuery($query) {
	if ($result = mysql_query($query)) return $result ;
	else return mysql_error();
}
//-- Inserts in Database takes the Table Name, and Array of Values.
function mysqlInsert($table, $values, $error=false) {
	for($i=1;$i<count($values);$i++) {$values[$i] = "'".$values[$i]."'";}
	$values = implode(",", $values);
	$query = "INSERT INTO ".$table." VALUES (".$values.")";
	if($error == true) echo $query."<br>";
	//rebuildSiteIndex();
	return mysqlQuery($query);
}

//-- Updates a record in a table. Takes Table Name, Values as an Associative array, and the Where Clause.
function mysqlUpdate($table, $values, $where, $error=false) {
	$str_values = "";
	foreach($values as $k => $v) {
		$str_values .= $k."='".$v."', ";
	}
	$str_values = substr($str_values, 0, -2);
	$query = "UPDATE ".$table." SET ".$str_values." WHERE ".$where;
	if($error == true) echo $query."<br>";
	//rebuildSiteIndex();
	return mysqlQuery($query);
}

function mysqlDelete($table, $where, $error=false) {
	$query = "DELETE FROM ".$table." WHERE ".$where;
	if($error == true) echo $query."<br>";
	return mysqlQuery($query);
}
//-- Returns a mutil dimensional array, first dimension is numeric, and second dimension is associative.
function mysqlFetchRows($table, $where="", $error=false) {
	if (!empty($where)) $where = "WHERE ".$where;
	$query = "SELECT * FROM ".$table." ".$where;
	$rows = mysqlQuery($query);
	if($error == true) echo "String: <font color=#669900>$query</font><br>Query: <font color=#669900>$rows</font><br>";
	$i = 0;
	while($row = mysql_fetch_assoc($rows)) {
		foreach($row as $k => $v) {$values[$i][$k] = $v;}
		++$i;
	}
	if (isset($values)) return $values;
}

function mysqlFetchRow($table, $where, $colums="*", $error=false) {
	$query = "SELECT ".$colums." FROM ".$table." WHERE ".$where. " LIMIT 1";
	$error_query = mysqlQuery($query);
	$value = mysql_fetch_assoc(mysqlQuery($query));
	if($error == true) echo "String: <font color=#669900>$query</font><br>Query: <font color=#669900>$error_query</font><br>";
	return $value;
}

function mysqlFetchRowsOneCol($table, $colum, $where, $error=false) {
	$query = "SELECT ".$colum." FROM ".$table." WHERE ".$where;
	$rows = mysqlQuery($query);
	if($error == true) echo "String: <font color=#669900>$query</font><br>Query: <font color=#669900>$rows</font><br>";
	while($row = mysql_fetch_assoc($rows)) foreach($row as $v) $values[] = $v;
	if (isset($values)) return $values;
}
/*
------------------|
End Function Block|
------------------|
*/

//-- Goes to specified url. Used to refresh page after changes.
//function goTo($url) {
//	echo "<script>window.location='$url';</script>";
//}

function traverse($array) {
	if (gettype($array)=="array")  {
		echo "<ul>";
		while (list($index, $subarray) = each($array)) {
			echo "<li>$index <code>=&gt;</code> ";
			traverse($subarray);
			echo "</li>";
		}
		echo "</ul>";
	}
	else echo "<font color=#669900>".htmlspecialchars($array)."</font>";
}

//-- Compairs the keys of 2 Arrays.
function compareArrays($item1, $key, $topics) {
	global $show;
	if(in_array($item1,$topics)) $show=true;
}
function test_print ($item2, $key) {
    echo "$key. $item2<br>\n";
}

//Generate a site Index page for spiders to reference
function rebuildSiteIndex() {
   //only run once every day
   if(filemtime("../site_index.php") < mktime()-(60*60*24)) {
     	$outfile = fopen("../site_index.php","wb");
     	if ($outfile==false) { print "Could not open site_index.php for writing.<br>"; return false;}
     	//Initially this is just a basic page - later make this a formatted page and add to site properly
     	$content="<?php\n
     include(\"includes/config.inc.php\");
     include(\"includes/functions-required.php\");
     include(\"includes/class-template.php\");

     include(\"templates/template-header.php\");
     ?>
     <td align=\"left\" valign=\"top\" width=\"100%\" colspan=\"3\">";

     	$researchers = mysqlFetchRows("researchers", "1 order by last_name");
     	if (is_array($researchers)) {
     		$content .= "<br><h4>Researchers</h4>";
     		$content .= "<table border ='0' cellpadding='4'>";
     		foreach($researchers as $index) {
     			$content .= "<tr><td><a href=\"/researchers.php?section=single&id=$index[researcher_id]\">$index[first_name] $index[last_name]</a></td><td>$index[keywords]</td></tr>\n";
     		}
     		$content .= "</table>";
     	}
     	$projects = mysqlFetchRows("projects");
     	if (is_array($projects)) {
     		$content .= "<h4>Projects</h4>";
     		$content .= "<table border='0' cellpadding='4'>";
     		foreach($projects as $index) {
     			$content .= "<tr><td><a href=\"projects.php?section=single&id=$index[project_id]\">$index[name]</a></td><td>$index[keywords]</td></tr>\n";
     		}
     		$content .= "</table>";
     	}

     	$content .= "</body></html>\n";
     	fwrite($outfile,$content);


     	fclose($outfile);
   }
   //else echo "skipped site index";

}

//-- Check the database for the existance of that user name and returns true or false.
//--- Used for making sure user names are unique.
function authorizeUsername($username, $old_username="") {
	if($old_username != "" ) $old_username = "AND username != '$old_username'";
	if(mysqlFetchRow("users", "username='$username' $old_username")) return false;
	else return true;
}

//Activity flag update for ethics
function flagActivity($id=0) {
		$app=mysqlFetchRow("ethicsapps","eth_id=$id");
		if(is_array($app)){
			$activity=mysqlFetchRow("ethicsapps_activity","eth_id=$id");
			if(!is_array($activity)){
				$values=array($id,mktime());
				$result=mysqlInsert('ethicsapps_activity',$values);
				if($result!=1) echo "Error inserting activity record: $result<br>";
			}
			else {
				$result=mysqlUpdate('ethicsapps_activity',array('date'=>mktime()),"eth_id=$id");
				if($result!=1) echo "Error updating activity record: $result<br>";
			}
		}
}


/*-------  MY CV Function 01 -------*/
function threeFieldRow($row_name, $num) {
	global $table_num, ${$row_name."_list"}, ${$row_name."_01"}, ${$row_name."_02"}, ${$row_name."_03"}, $build_action;
	if (isset($build_action) && $build_action == "add_".$row_name."_field") {
			${$row_name."_list"} .=
				'<tr><td align="center" colspan="2"><input type="text" name="'.$row_name.'_01[]" maxlength="255" size="9"></td>
					<td align="center"><input type="text" name="'.$row_name.'_02[]" maxlength="255" size="17"></td>
					<td align="center"><textarea name="'.$row_name.'_03[]" rows="1" cols="56"></textarea></td></tr>';
			$table_num = "table".$num;
	}
	for($i=0;$i<@count(${$row_name."_01"});$i++) {
		if(${$row_name."_01"}[$i] != "") {
			 ${$row_name."_list"} .=
				'<tr><td align="center" colspan="2"><input type="text" name="'.$row_name.'_01[]" maxlength="255" size="9" value="'.${$row_name."_01"}[$i].'"></td>
					<td align="center"><input type="text" name="'.$row_name.'_02[]" maxlength="255" size="17" value="'.${$row_name."_02"}[$i].'"></td>
					<td align="center"><textarea name="'.$row_name.'_03[]" rows="1" cols="56">'.${$row_name."_03"}[$i].'</textarea></td></tr>';
		}
	}
}
function formatViewTables($rows, $type='') {
	$result = "";
	$rows = explode("!#$*$#!",$rows);
	$count = count(explode("!#$^$#!",$rows[0]));
	for($x=0;$x!=count($rows);$x++) $rows[$x]  = explode("!#$^$#!", $rows[$x]);
	for($i=0;$i<$count;$i++) {
		$result .= '<tr>';
		//assume that the last item is always the URL, and the second-last is the text
		$url_item = count($rows) - 1;
		$text_item = $url_item -1;
		for($x=0;$x!=count($rows);$x++) {
			if($type == 'url') {
				$rows[$url_item][$i] = str_replace("http://", "", $rows[$url_item][$i]);
				if ($x == 0) $result .= '<td align="left">'.$rows[$x][$i].'</td>';
				if ($x == 1) $result .= '<td align="left">'.$rows[$x][$i];
				if ($x == $text_item) {
					if ($rows[$url_item][$i] == "" &&  $rows[$text_item][$i] == "") $result .= '&nbsp;</td>';
					else if ($rows[$url_item][$i] == "" &&  $rows[$text_item][$i] != "") $result .= ' | '.$rows[$text_item][$i].'</td>';
					else $result .= ' | <a href="http://'.$rows[$url_item][$i].'" target="_blank" >'.$rows[$x][$i].'</a></td>';
				}
			}
			else {
				if ($x == 0) $result .= '<td align="left" valign="top" nowrap>'.$rows[$x][$i].'</td>';
				else $result .= '<td align="left" valign="top">'.$rows[$x][$i].'</td>';
			}
		}
		$result .= '</tr>';
	}
	return $result;
}

function formatPrintTables($rows, $type='') {
	$result = "";
	$rows = explode("!#$*$#!",$rows);
	$count = count(explode("!#$^$#!",$rows[0]));
	for($x=0;$x!=count($rows);$x++) $rows[$x]  = explode("!#$^$#!", $rows[$x]);
	for($i=0;$i<$count;$i++) {
		$result .= '<tr>';
		//assume that the last item is always the URL, and the second-last is the text
		for($x=0;$x!=count($rows);$x++) {
			if($type == 'url') {

				if ($x == 0) $result .= '<td align="left">'.$rows[$x][$i].'</td>';
				if ($x == 1 || $x == 2 || $x ==3) $result .= '<td align="left">'.$rows[$x][$i];
			}
			else {
				if ($x == 0) $result .= '<td align="left" valign="top" nowrap>'.$rows[$x][$i].'</td>';
				else $result .= '<td align="left" valign="top">'.$rows[$x][$i].'</td>';
			}
		}
		$result .= '</tr>';
	}
	return $result;
}
/*-------  MY CV Function 02 -------*/
function formatter($name,$count) {
	global ${$name}, ${$name."_01"};
	if(isset(${$name."_01"})) {
		for($i=1;$i!=($count+1);$i++) {
			global ${$name."_0".$i};
			${$name}[] = implode("!#$^$#!", ${$name."_0".$i});
		}
		${$name} = implode("!#$*$#!", ${$name});
	}
}
function unFormatter($values,$name) {
	$arrays = explode("!#$*$#!", $values);
	if(is_array($arrays) && $arrays[0] != "") {
		$loop_limit = count($arrays);
		for($i=0;$i<$loop_limit;$i++) {
			$contents = explode("!#$^$#!", $arrays[$i]);
			foreach ($contents as $c) {
				global  ${$name."_0".($i+1)};
				${$name."_0".($i+1)}[] = $c;
			}
		}
	}
}

/**
*   Used for debugging, returns a nicely formatted version of an array  or object
*
*   @param      array or object     $var        target array or object
*   @return     string                          HTML formatted result
*/
if (!function_exists('PrintR')) {
    function PrintR($var) {
        echo '<div align="left"><pre>';
        print_r($var);
        echo '</pre></div>';
    } // function PrintR
} // if


function unique_filename($xtn = ".tmp")
  {
  // explode the IP of the remote client into four parts
  $ipbits = explode(".", $_SERVER["REMOTE_ADDR"]);
  // Get both seconds and microseconds parts of the time
  list($usec, $sec) = explode(" ",microtime());

  // Fudge the time we just got to create two 16 bit words
  $usec = (integer) ($usec * 65536);
  $sec = ((integer) $sec) & 0xFFFF;

  // Fun bit - convert the remote client's IP into a 32 bit
  // hex number then tag on the time.
  // Result of this operation looks like this xxxxxxxx-xxxx-xxxx
  $uid = sprintf("%08x-%04x-%04x",($ipbits[0] << 24)
         | ($ipbits[1] << 16)
         | ($ipbits[2] << 8)
         | $ipbits[3], $sec, $usec);

  // Tag on the extension and return the filename
  return $uid.'.'.$xtn;
  }

?>