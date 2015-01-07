<?php

include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");

$template = new Template;

$overdue_colour = "";
$query = "SELECT DISTINCT * FROM finance_entry WHERE id IN (SELECT id FROM (SELECT TIMESTAMPDIFF(DAY, NOW(), `transaction_date`) AS 'days_gone' , id AS id FROM finance_entry WHERE `date_reconciled` = 0000-00-00) AS overdue WHERE days_gone < -29)";
$result = mysql_query($query);
if(mysql_num_rows($result) > 0)
	$overdue_colour = "style='background-color:red'";

include("templates/template-header.html");
	echo "<form>";
	echo " <button type='button' onClick='window.location=\"account.php?view=add\"'>Add account</button>";
	echo " <button type='button' onClick='window.location=\"account.php?view=unreconciled\"'>Unreconciled</button>";
	echo " <button type='button' onClick='window.location=\"account.php?view=advances\"'>Advances</button>";
	echo " <button type='button' " . $overdue_colour . " onClick='window.location=\"account.php?view=overdue\"'>Overdue</button>";	
	echo " <button type='button' onClick='window.location=\"finance_categories.php\"'>Categories</button>";		
	echo "</form>";
	echo "<table border='1' cellpadding='0' style='border-collapse: collapse' bordercolor='#FFFFFF' cellspacing='1'>
		<tr><td>
			<table border='0' width='100%' cellpadding='0' style='border-collapse: collapse' bordercolor='#FFFFFF' cellspacing='1'>
				<tr>
					<td colspan='10' class='successhead'>Viewing: All Accounts</td>
				</tr>
				<tr height='10'><td colspan='10' height='14' class='success'> </td></tr>
			</table>
		</td></tr>
		<tr><td>
			<table class='sortable' border='1' cellpadding='3' style='border-collapse: collapse' bordercolor='#FFFFFF' cellspacing='0'>
				<tr bgcolor='#000000'>
					<td><b style='color:#E1E1E1;font-size:10px'>Account Owners</b></td>
					<td><b style='color:#E1E1E1;font-size:10px'>FOAP</b></td>
					<td><b style='color:#E1E1E1;font-size:10px'>Date opened</b></td>
					<td><b style='color:#E1E1E1;font-size:10px'>Unreconciled</b></td>
					<td><b style='color:#E1E1E1;font-size:10px'>Advances</b></td>
					<td><b style='color:#E1E1E1;font-size:10px'></b></td>
					<td><b style='color:#E1E1E1;font-size:10px'></b></td>
				</tr>";
	
		$query = "SELECT * FROM finance_account";
		$result = mysql_query($query);
	
		$order   = array("\r\n", "\n", "\r");
		$replace = '<br />';
		$i = 0;
	
		while($row = mysql_fetch_array($result)){
			$unreconciled_query = "SELECT * FROM finance_entry WHERE account_id=" . $row['account_id'] . " AND reconciled_flag = 0";
			$unreconciled = mysql_query($unreconciled_query);
			$advances_query = "SELECT * FROM finance_entry WHERE account_id=" . $row['account_id'] . " AND advance_flag = 1";
			$advances = mysql_query($advances_query);
			
			if ($row['date_closed'] == "0000-00-00"){
				$closed_colour = "bgcolor='#D7D7D9'";
				$add = "<a href='finance_entry.php?view=add&id=" . $row['account_id'] . "'>add</a>";
				}
			else{
				$closed_colour = "bgcolor='#AAAAAA'";
				$add = "";
				}
			
			echo "<tr onClick=\"document.location.href='account.php?view=reconciled&id=" . $row['account_id']  . "'\" onMouseOver=\"ShowHelp('d" . $i . "', 'Description', '" . str_replace($order, $replace, $row['description']) . "', 400)\" onMouseOut=\"HideHelp('d" . $i . "')\" " . $closed_colour . ">";
			echo "<td>" . get_name($row['owner1']) . ", " . get_name($row['owner2']) . ", " . get_name($row['owner3']) . " </td><td>" . $row['fund'] ."-" . $row['organization'] . "-" . $row['program'] . "<td> " . convert_date_to_dmY($row['date_open']) . "</td> <td>" . mysql_num_rows($unreconciled) . "</td><td>" . mysql_num_rows($advances) . "</td><td bgcolor='#E09731'>" . $add . "</td><td bgcolor='#E09731'><a href=\"account.php?view=edit&id=" . $row['account_id'] . "\">edit</a><div  style=\"display:none; font-size:10px\" id=\"d" . $i . "\"></div></td>";
			echo "</tr>";
			$i++;
		}
		echo "</table></td></tr></table>";
	
include("templates/template-footer.html");

function get_name($user_id)
{
	if($user_id == 0){
		$first_name = "";$last_name = "";
	}
	else{
		$query = "SELECT first_name, last_name FROM `users` WHERE user_id='" . $user_id . "'";
		$result = mysql_query($query) or die(mysql_error());
		$first_name = mysql_result($result,0);
		$last_name = mysql_result($result,0,1);
	}
	return $first_name . " " . $last_name;
}

function convert_date_to_dmY($date){
	$date_array = explode("-",$date);
	$year = $date_array[0];
	$month = $date_array[1];
	$day = $date_array[2];		
	
	return $day . "/" . $month . "/" . $year;
}
?>
