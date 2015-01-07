<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");

$template = new Template;
include("templates/template-header.html");

	if( (isset($_POST['fund']) && $_POST['fund'] != "")){
		$query = "INSERT INTO finance_fund (name) VALUES ('$fund')";
		mysql_query($query);
	}
	
	if( (isset($_POST['organization']) && $_POST['organization'] != "")){
		$query = "INSERT INTO finance_organization (name) VALUES ('$organization')";
		mysql_query($query);
	}
	
	if( (isset($_POST['program']) && $_POST['program'] != "")){
		$query = "INSERT INTO finance_program (name) VALUES ('$program')";
		mysql_query($query);
	}	
?>

<a href="#organization">Jump to Organization</a><br />
<a href="#program">Jump to Program</a>

<form name='input' action='finance_foap.php' method='post'>
    Fund <input type="text" name="fund" />
    <br />
    <input type="submit" value="submit" />
</form>
<table border="1" cellpadding="3" style="border-collapse: collapse" bordercolor="#FFFFFF" cellspacing="1">
	<tr>
		<td colspan="10" class="successhead">Viewing: Fund</td>
	</tr>
    <tr height="10"><td colspan='10' height="14" class="success"> </td></tr>
    <tr bgcolor="#000000">
		<td><b style="color:#E1E1E1;font-size:10px">Number</b></td>
   		<td><b style="color:#E1E1E1;font-size:10px">Name</b></td>
	</tr>
	<?php
		$query = "SELECT * FROM finance_fund";
		$result = mysql_query($query);
		
		while($row = mysql_fetch_array($result)){
			echo "<tr><td>" . $row['number'] . "</td><td>" . $row['name'] . "</td></tr>";
		}
	?>
</table>    
<br /><br />
<a name="organization">&nbsp;</a>
<form name='input' action='finance_foap.php' method='post'>
    Organization <input type="text" name="organization" />
    <br />
    <input type="submit" value="submit" />
</form>
<table border="1" cellpadding="3" style="border-collapse: collapse" bordercolor="#FFFFFF" cellspacing="1">
	<tr>
		<td colspan="10" class="successhead">Viewing: Organization</td>
	</tr>
    <tr height="10"><td colspan='10' height="14" class="success"> </td></tr>
    <tr bgcolor="#000000">
		<td><b style="color:#E1E1E1;font-size:10px">Number</b></td>
   		<td><b style="color:#E1E1E1;font-size:10px">Name</b></td>
	</tr>
   	<?php
		$query = "SELECT * FROM finance_organization";
		$result = mysql_query($query);
		
		while($row = mysql_fetch_array($result)){
			echo "<tr><td>" . $row['number'] . "</td><td>" . $row['name'] . "</td></tr>";
		}
	?>
</table>
<br /><br />
<a name="program">&nbsp;</a>
<form name='input' action='finance_foap.php' method='post'>
    Program <input type="text" name="program" />
    <br />
    <input type="submit" value="submit" />
</form>
<table border="1" cellpadding="3" style="border-collapse: collapse" bordercolor="#FFFFFF" cellspacing="1">
	<tr>
		<td colspan="10" class="successhead">Viewing: Program</td>
	</tr>
    <tr height="10"><td colspan='10' height="14" class="success"> </td></tr>
    <tr bgcolor="#000000">
		<td><b style="color:#E1E1E1;font-size:10px">Number</b></td>
   		<td><b style="color:#E1E1E1;font-size:10px">Name</b></td>
	</tr>
    <?php
		$query = "SELECT * FROM finance_program";
		$result = mysql_query($query);
		
		while($row = mysql_fetch_array($result)){
			echo "<tr><td>" . $row['number'] . "</td><td>" . $row['name'] . "</td></tr>";
		}
	?>
</table>
<?php
include("templates/template-footer.html");
?>