<?php

include("includes/config.inc.php");
include("includes/class-template.php");

$template = new Template;
include("templates/template-header.html");

echo "<a href='finance_main.php'>Back to main</a><br>";

if(isset($_POST['code']) && isset($_POST['description'])){
	$query = "INSERT INTO finance_category_activity (`Banner Code`, `Name`) VALUES ('" . $_POST['code'] . "', '" . $_POST['description'] . "')";
	$result = mysql_query($query);
}

echo "<form name='input' action='finance_categories.php' method='post'>
Banner Code <input type='text' name='code'><br />
<textarea onclick='document.input.description.value =\"\";' name='description' rows='2' cols='90'>Description</textarea><br />
<input type='submit' value='submit' />
</form>";

$query = "SELECT * FROM finance_category_activity ORDER BY `Banner Code`";
$result = mysql_query($query);

echo "<table border='1' cellpadding='0' style='border-collapse: collapse' bordercolor='#FFFFFF' cellspacing='1'>
		<tr><td>
			<table border='0' width='100%' cellpadding='0' style='border-collapse: collapse' bordercolor='#FFFFFF' cellspacing='1'>
				<tr>
					<td colspan='10' class='successhead'>Viewing: Categories</td>
				</tr>
				<tr height='10'><td colspan='10' height='14' class='success'> </td></tr>
			</table>
		</td></tr>
		<tr><td>
			<table class='sortable' border='1' cellpadding='3' style='border-collapse: collapse' bordercolor='#FFFFFF' cellspacing='0'>
				<tr bgcolor='#000000'>
					<td><b style='color:#E1E1E1;font-size:10px'>Banner Code</b></td>
					<td><b style='color:#E1E1E1;font-size:10px'>Description</b></td>
				</tr>";
					while($row = mysql_fetch_array($result)){
					echo "<tr bgcolor='#D7D7D9'><td>" . $row['Banner Code'] . "</td><td>" . $row['Name'] . "</td></tr>";
					}
	
echo "		</table>
		</tr>
	</table>";
		


include("templates/template-footer.html");
?>
