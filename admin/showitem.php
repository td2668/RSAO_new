<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>
Item Detail
</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="includes/ucc.css" rel="stylesheet" type="text/css">
</head>
<body bgcolor="#CCCCCC">
<?
//Dump a single paper to screen with all details
include("includes/config.inc.php");
include("includes/functions-required.php");
if(!isset($id)) echo "<p>No paper specified</p>";
else {
	$item=mysqlFetchRow("cv_items left join cv_item_types on cv_items.cv_item_type_id = cv_item_types.cv_item_type_id","cv_item_id=$id");
	if(is_array($item)){
		echo "	<table border='0' width='500'><tr><td align='center'><div style='bottommargin:6px; font:verdana,arial,san-serif; font-size:12px;font-weight:bold;'>$item[title]</div></td></tr><tr><td align='center'><table  width='90%' style='BORDER-LEFT-COLOR:#cccc99; BORDER-BOTTOM-COLOR:#cccc99; BORDER-TOP-STYLE:solid; BORDER-TOP-COLOR:#cccc99; BORDER-RIGHT-STYLE:solid; BORDER-LEFT-STYLE:solid; BORDER-COLLAPSE:collapse; BORDER-RIGHT-COLOR:#cccc99; BORDER-BOTTOM-STYLE:none;' bordercolor='#cccc99' cellSpacing='0' cellPadding='5' rules='all' border='1' >";
		for($x=1;$x<=9;$x++){
			$name="f".$x; $name2="f".$x."_name";
			if($item["$name2"]!="") {
				echo"<tr align='left' valign='top'><td bgcolor='#FFFFCC'>$item[$name2]</td><td bgcolor='#FFFFFF'>";
				if($x==2){
					if($item['f11_name']=="Forthcoming" && $item['f11']) echo "Forthcoming";
					else if($item['f10_name']=="Submitted" && $item['f10']) echo "Submitted";
					else {
						if($item['f2']==0) echo "";
						else if($item['f2_type']=="year") echo (date("Y",$item['f2']));
						else echo (date("M Y",$item['f2']));
					}
				} //x=2
				 else if($x==3){
				 	if($item['f3']==0) echo "";
					else if($item['f3_type']=="year") echo (date("Y",$item['f3']));
					else echo (date("M Y",$item['f3']));
				}//x=3
				else {
					if($item["$name2"]=="URL" && $item["$name"] != "") echo "<a href='http://$item[$name]'>http://";
					echo "$item[$name]";
					if($item["$name2"]=="URL" && $item["$name"] != "") echo "</a>";
				}//else
				echo "</td></tr>\n";
			}//if
		}//for

		echo"</table></td></tr></table>";
		
	}
	else echo "<p>No item with that id located</p>";
}
?>
</body>
</html>
