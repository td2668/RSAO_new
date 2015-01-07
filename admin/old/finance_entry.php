
<?php

include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");

$template = new Template;
include("templates/template-header.html");

if(isset($_POST["account"])){
	$account = $_POST["account"];
	$category_activity = $_POST["category_activity"];
	$description = $_POST["description"];
	$ammount = $_POST["ammount"];
	$advance_flag = 0;
	$reconciled_flag = 0;
	$transaction_date = convert_date_to_Ymd($_POST["transaction_date"]);
	$date = date("Y-m-d");
	
	$entry_tables = "account_id,date_entered,transaction_date,ammount,description,category_activity";
	$data_tables = "'$account','$date','$transaction_date','$ammount','$description','$category_activity'";	
	
	if(isset($_POST["reconciled"])){
		$entry_tables = $entry_tables . ",reconciled_flag,date_reconciled";
		$data_tables = $data_tables . ",'1','$date'";
		$reconciled_flag = "1";
	}
	else{
		$entry_tables = $entry_tables . ",reconciled_flag";
		$data_tables = $data_tables . ",'0'";
		if(isset($_POST["advance"])){
			$entry_tables = $entry_tables . ",advance_flag";
			$data_tables = $data_tables . ",'1'";
			$advance_flag = "1";
		}		
		else{
			$entry_tables = $entry_tables . ",advance_flag";
			$data_tables = $data_tables . ",'0'";				
		}
	}
	
	if($_POST["category_2"] != ""){
		$entry_tables = $entry_tables . ",category_2";
		$data_tables = $data_tables . ",'" . $_POST["category_2"] . "'";
	}
	if(isset($_POST["edit"]))
		$query = "UPDATE finance_entry SET ammount='$ammount', description='$description', advance_flag='$advance_flag', reconciled_flag='$reconciled_flag', category_activity='$category_activity', transaction_date='$transaction_date' WHERE id='" . $_POST['entry_id'] . "'";	
	else
		$query = "INSERT INTO finance_entry ($entry_tables) VALUES ($data_tables)";
	$result = mysql_query($query);
	echo $query;
	
//	if($_POST['submit'] == "Submit")
//		echo"<meta http-equiv=refresh content=\"0; url=finance_main.php\">";
//	else
//		echo"<meta http-equiv=refresh content=\"0; url=finance_entry.php?id=" . $account . "\">";
}

else if(isset($_GET['view']) && $_GET['view'] == 'add'){
	echo "<link rel='stylesheet' type='text/css' href='/includes/datechooser.css'>";
	echo "<a href='finance_main.php'>Back to main</a>";
	$query = "Select * from finance_account";
	$result = mysql_query($query);
	
	echo "<form action='finance_entry.php' method='post'> 
	<table>
	<input type='hidden' name='account' value='" . $_GET['id'] . "'>
	<tr><td>Amount </td><td><input type='text' name='ammount'></td></tr>
	<tr><td></td><td><textarea onclick='document.input.description.value =\"\";' name='description' rows='6' cols='90'>Description</textarea></td></tr>
	<tr><td>Advance </td><td><input type='checkbox' name='advance' value='advance'></td></tr>
	<tr><td>Reconciled </td><td><input type='checkbox' name='reconciled' value='reconciled'></td></tr>";
	
	//Query and listing of Category Activity
	echo "<tr><td>Category Activity </td><td><select name='category_activity'>";
	$query = "Select * from finance_category_activity";
	$result = mysql_query($query);
	while($row = mysql_fetch_array($result)){
		echo "<option value='" . $row['Banner Code'] . "'>" . $row['Banner Code'] . " " . $row['Name'] . "</option>";	
	}
	
	//Query and listing of Category Activity
	echo "</select></td></tr>";
	echo "<tr><td>Category 2 </td><td><select name='category_2'>";
	$query = "Select * from finance_catigory_2 ORDER BY `Banner Code` ASC";
	$result = mysql_query($query);
	while($row = mysql_fetch_array($result)){
		echo "<option value='" . $row['Banner Code'] . "'>" . $row['Banner Code'] . " " . $row['Name'] . "</option>";	
	}
	echo "</select></td></tr>";
	
	  echo "<tr><td>Transaction date: </td><td><input name='transaction_date' id='transaction_date' size='8' value='" . date('d/m/Y') . "' onChange='document.form1.submit();'> <img src='/includes/calendar.gif'  align='absmiddle' onclick='showChooser(this, \"transaction_date\", \"chooserSpan\", 2011, 2020, \"d/m/Y\", false);'>
<div id='chooserSpan' class='dateChooser select-free' style='display: none; visibility: hidden; width: 166px;'>
</div></td></tr>";
	
	echo "<tr><td><input type='submit' name='submit' value='Submit'></td><td><input type='submit' name='submit' value='Add another'></td></tr>";
	echo "</table></form>";
}

else if(isset($_GET['view']) && $_GET['view'] == 'edit'){
	echo "<link rel='stylesheet' type='text/css' href='/includes/datechooser.css'>";
	echo "<a onclick=\"javascript: history.go(-1)\" onmouseover=\"this.style.textDecoration='underline';this.style.color='black'\" onmouseout=\"this.style.textDecoration='none'\">back</a>";
	$query = "Select * from finance_entry WHERE id=" . $_GET['entry_id'];
	$result = mysql_query($query);

	$row = mysql_fetch_array($result);
	if($row['advance_flag'] == 1){$advance="checked='checked'";}else{$advance="";}
	if($row['reconciled_flag'] == 1){$reconciled="checked='checked'";}else{$reconciled="";}	
	
	echo "<form action='finance_entry.php' method='post'> 
	<table>
	<input type='hidden' name='edit' value='TRUE'>
	<input type='hidden' name='account' value='" . $row['id'] . "'>
	<input type='hidden' name='entry_id' value='" . $_GET['entry_id'] . "'>
	<tr><td>Amount </td><td><input type='text' name='ammount' value='" . $row['ammount'] . "'></td></tr>
	<tr><td></td><td><textarea onclick='document.input.description.value =\"\";' name='description' rows='6' cols='90'>" . $row['description'] . "</textarea></td></tr>
	<tr><td>Advance </td><td><input type='checkbox' " . $advance . "' name='advance' value='advance'></td></tr>
	<tr><td>Reconciled </td><td><input type='checkbox' " . $reconciled . " name='reconciled' value='reconciled'></td></tr>";
	
	//Query and listing of Category Activity
	echo "<tr><td>Category Activity </td><td><select name='category_activity'>";
	$query = "Select * from finance_category_activity";
	$result = mysql_query($query);
	while($row = mysql_fetch_array($result)){
		echo "<option value='" . $row['Banner Code'] . "'>" . $row['Banner Code'] . " " . $row['Name'] . "</option>";	
	}
	
	//Query and listing of Category Activity
	echo "</select></td></tr>";
	echo "<tr><td>Category 2 </td><td><select name='category_2'>";
	$query = "Select * from finance_catigory_2 ORDER BY `Banner Code` ASC";
	$result = mysql_query($query);
	while($row = mysql_fetch_array($result)){
		echo "<option value='" . $row['Banner Code'] . "'>" . $row['Banner Code'] . " " . $row['Name'] . "</option>";	
	}
	echo "</select></td></tr>";
	
	  echo "<tr><td>Transaction date: </td><td><input name='transaction_date' id='transaction_date' size='8' value='" . date('d/m/Y') . "' onChange='document.form1.submit();'> <img src='/includes/calendar.gif'  align='absmiddle' onclick='showChooser(this, \"transaction_date\", \"chooserSpan\", 2011, 2020, \"d/m/Y\", false);'>
<div id='chooserSpan' class='dateChooser select-free' style='display: none; visibility: hidden; width: 166px;'>
</div></td></tr>";
	
	echo "<tr><td><input type='submit' name='submit' value='Submit'></td></tr>";
	echo "</table></form>";
}


include("templates/template-footer.html");

function convert_date_to_Ymd($date){
	$date_array = explode("/",$date);
	$year = $date_array[2];
	$month = $date_array[1];
	$day = $date_array[0];		
	
	return $year . "-" . $month . "-" . $day;
}
?>
