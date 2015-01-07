<?php

include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");

$template = new Template;
include("templates/template-header.html");

if(isset($_POST['amount']) && is_numeric($_POST['amount'])){
	$amount = $_POST['amount'];
	$query = "UPDATE finance_entry SET ammount='$amount', reconciled_flag='1',advance_flag='0',date_reconciled='" . date('Y/m/d') . "' WHERE id='" . $_POST['entry_id'] . "'";
	$result = mysql_query($query);

	if(isset($_POST['return_to']))
		echo "<meta http-equiv=refresh content=\"0; url=" . $_POST['return_to'] . "\">";
	else 
		echo "<meta http-equiv=refresh content=\"0; url=account.php?view=reconciled&id=" . $_POST['account_id'] . "\">";
}

else 
	//echo "<meta http-equiv=refresh content=\"0; url=" . $_POST['return_to'] . "\">";

include("templates/template-footer.html");
?>