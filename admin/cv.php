<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");
//$filepath = "/home/html_root/htdocs/";
$filepath = "../";
include("includes/mail-functions.php");
//include("includes/garbage_collector.php");
$template = new Template;
//require("security.php");
//-- Header File
include("templates/template-header.html");

if(!isset($section)) $section='view';
$success="";
$date_error=FALSE;



if(isset($update)) {
	if(isset($id) && isset($user_id)){
		if(isset($f10)) $f10=TRUE; else $f10=FALSE;
		if(isset($f11)) $f11=TRUE; else $f11=FALSE;
		if(isset($report_flag)) $report_flag=TRUE; else $report_flag=FALSE;
		if(isset($web_show)) $web_show=TRUE; else $web_show=FALSE;
		if(isset($f7)) $f7=str_replace("http://", "", $f7);
		//Do a date check
		//This is a leftover - should never fire due to Javascript expression checking in the HTML
		$date_error=FALSE; $f2_type="";$f3_type="";
		if(!isset($f2)) $f2=0;
		else if($f2=="") $f2=0;
		else {
			if(is_numeric($f2)) {//likely a year
				if (($f2 > 1970) && ($f2 < 2038)) {
					$f2=mktime(0,0,0,1,1,$f2);
				}
			}
			else if(count(explode("/",$f2)) == 2) { // month and year?
				$temp_date=explode("/",$f2);
				$f2=mktime(0,0,0,$temp_date[0],1,$temp_date[1]);
			}
			else if(count(explode("/",$f2)) == 3) { // day month and year?
				$temp_date=explode("/",$f2);
				$f2=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
			}
			else $f2=0;
		}
		if(!isset($f3)) $f3=0;
		else if($f3=="") $f3=0;
		else{
			if(is_numeric($f3)) {//likely a year
				if (($f3 > 1970) && ($f3 < 2038)) $f3=mktime(0,0,0,1,1,$f3);
			}
			else if(count(explode("/",$f3)) == 2) { // month and year?
				$temp_date=explode("/",$f3);
				$f3=mktime(0,0,0,$temp_date[0],1,$temp_date[1]);
			}
			else if(count(explode("/",$f3)) == 3) { // day month and year?
				$temp_date=explode("/",$f3);
				$f3=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
			}
			else $f3=0;
		}

		if(!isset($f1)) $f1="";
		if(!isset($f4)) $f4="";
		if(!isset($f5)) $f5="";
		if(!isset($f6)) $f6="";
		if(!isset($f7)) $f7="";
		if(!isset($f8)) $f8="";
		if(!isset($f9)) $f9="";

		if($date_error) $success="<font color='#AA0000'>Error Formatting Date</font>";
		else {
			$values = array('cv_item_type_id'=>$cv_item_type_id,
							'f1'=>$f1,
							'f2'=>$f2,
							'f3'=>$f3,
							'f4'=>$f4,
							'f5'=>$f5,
							'f6'=>$f6,
							'f7'=>$f7,
							'f8'=>$f8,
							'f9'=>$f9,
							'f10'=>$f10,
							'f11'=>$f11,
							'report_flag'=>$report_flag,
							'web_show'=>$web_show);
			$result=mysqlUpdate("cv_items",$values,"cv_item_id=$id");
			if($result != 1) $success= "Error updating database";
			else $success="Updated";
			$section="view";
		}
	}//isset id
}

if(isset($add)) {
	if(isset($f10)) $f10=TRUE; else $f10=FALSE;
	if(isset($f11)) $f11=TRUE; else $f11=FALSE;
	if(isset($report_flag)) $report_flag=TRUE; else $report_flag=FALSE;
	if(isset($web_show)) $web_show=TRUE; else $web_show=FALSE;
	if(isset($f7)) $f7=str_replace("http://", "", $f7);
	//Do a date check, but this is item dependent, so I need a way of confirming the type of date.
	//If it fails, the values should be used to populate an 'update' page to fix things.
	//So a date can either be a) a 4 digit year, b) an 01/2003 type, or c) a full 21/01/2003 type
	// In the first two cases I must convert to full date, then do an strtotime()
	$date_error=FALSE; $f2_type="";$f3_type="";
	if(!isset($f2)) $f2=0;
	else if($f2=="") $f2=0;
	else{
		if(is_numeric($f2)) {//likely a year
			if (($f2 > 1970) && ($f2 < 2037)) {
				$f2=mktime(0,0,0,1,1,$f2);
			}
		}
		else if(count(explode("/",$f2)) == 2) { // month and year?
			$temp_date=explode("/",$f2);
			$f2=mktime(0,0,0,$temp_date[0],1,$temp_date[1]);
		}
		else if(count(explode("/",$f2)) == 3) { // day month and year?
			$temp_date=explode("/",$f2);
			$f2=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);

		}
		else $f2=0;
	}
	if(!isset($f3)) $f3=0;
	else if($f3=="") $f3=0;
	else{
		if(is_numeric($f3)) {//likely a year
			if (($f3 > 1970) && ($f3 < 2037)) $f3=mktime(0,0,0,1,1,$f3);
		}
		else if(count(explode("/",$f3)) == 2) { // month and year?
			$temp_date=explode("/",$f3);
			$f3=mktime(0,0,0,$temp_date[0],1,$temp_date[1]);
		}
		else if(count(explode("/",$f3)) == 3) { // day month and year?
			$temp_date=explode("/",$f3);
			$f3=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
		}
		else $f3=0;
	}


	if(!isset($f1)) $f1="";
	if(!isset($f4)) $f4="";
	if(!isset($f5)) $f5="";
	if(!isset($f6)) $f6="";
	if(!isset($f7)) $f7="";
	if(!isset($f8)) $f8="";
	if(!isset($f9)) $f9="";

	if($date_error) $success="<font color='#AA0000'>Error Formatting Date</font>";
	else {
		$values = array('null',$user_id,$cv_item_type_id,$f1,$f2,$f3,$f4,$f5,$f6,$f7,$f8,$f9,$f10,$f11,$report_flag,$web_show,$report_flag);
		$result=mysqlInsert("cv_items",$values);
		if($result != 1) $success="<font color='#AA0000'>Error Adding Item: $result</font>";
		else $success="Added";
	}

}

if(isset($unshow)){
	$values = array('web_show'=>0);
	$result=mysqlUpdate("cv_items",$values,"cv_item_id=$id");
	if($result != 1) $success= "Error updating database";
	else $success="Updated";
	$section="view";
}
if(isset($reshow)){
	$values = array('web_show'=>1);
	$result=mysqlUpdate("cv_items",$values,"cv_item_id=$id");
	if($result != 1) $success= "Error updating database";
	else $success="Updated";
	$section="view";
}

if(isset($unshowpar)){
	$values = array('report_flag'=>0);
	$result=mysqlUpdate("cv_items",$values,"cv_item_id=$id");
	if($result != 1) $success= "Error updating database";
	else $success="Updated";
	$section="view";
}
if(isset($reshowpar)){
	$values = array('report_flag'=>1);
	$result=mysqlUpdate("cv_items",$values,"cv_item_id=$id");
	if($result != 1) $success= "Error updating database";
	else $success="Updated";
	$section="view";
}

if(isset($delete)) {
	if(isset($id)){
		$result=mysqlDelete("cv_items","cv_item_id=$id");
		if($result != 1) $success= "Error Deleting";
		else $success="Deleted";
		$section="view";
	}
}

if(isset($section)) switch ($section){
	case"update":
		if($date_error) {
		//already have the values - so reset a few things and skip the loading
		//$item=array('cv_item_type_id'=>$cv_item_type_id,'f1'=>$f1,'f2'=>"",'f3'=>"",'f4'=>$f4,'f5'=>$f5,'f6'=>$f6,'f7'=>$f7,'f8'=>$f8,'f9'=>$f9,'f10'=>$f10,'f11'=>$f11,'report_flag'=>$report_flag,'web_show'=>$web_show);
		}
		else $item=mysqlFetchRow("cv_items","cv_item_id=$id");
		if(is_array($item)){
			//process dates
			$type=mysqlFetchRow("cv_item_types","cv_item_type_id=$item[cv_item_type_id]");
			if($item['f2']!=0){
				if($type['f2_type']=="year") $f2=date("Y",$item['f2']);
				else if($type['f2_type']=="month") $f2=date("m/Y",$item['f2']);
				else if($type['f2_type']=="day") $f2=date("d/m/Y",$item['f2']);
				else $f2="";
			}
			else $f2="";

			if($item['f3']!=0){
				if($type['f3_type']=="year") $f3=date("Y",$item['f3']);
				else if($type['f3_type']=="month") $f3=date("m/Y",$item['f3']);
				else if($type['f3_type']=="day") $f3=date("d/m/Y",$item['f3']);
				else $f3="";
			}
			else $f3="";

			if($item['f10']) $f10="checked"; else $f10="";
			if($item['f11']) $f11="checked"; else $f11="";
			if($item['report_flag']) $report_flag="checked"; else $report_flag="";
			if($item['web_show']) $web_show="checked"; else $web_show="";

			$type_options="";
            $varset="nametext=new Array();\n helptext=new Array();\n";
			$headers=mysqlFetchRows("cv_item_headers","1 order by category,rank");
			if(is_array($headers)) {
			foreach($headers as $header){
                $header['category']=ucfirst($header['category']);
                $type_options.="<option value='0' >&nbsp;</option>/n<option value='0' >----- $header[category]: $header[title] -----</option>";
				$types=mysqlFetchRows("cv_item_types","cv_item_header_id=$header[cv_item_header_id] order by rank");
				if(is_array($types)) foreach($types as $type){
					$tsel="";
					if (isset($item['cv_item_type_id'])) if($item['cv_item_type_id']==$type['cv_item_type_id']) $tsel="selected";
					$type_options.="<option value='$type[cv_item_type_id]' $tsel>$type[title]</option>\n";
					$type['f1_eg']=addslashes($type['f1_eg']);
					$type['f2_eg']=addslashes($type['f2_eg']);
					$type['f3_eg']=addslashes($type['f3_eg']);
					$type['f4_eg']=addslashes($type['f4_eg']);
					$type['f5_eg']=addslashes($type['f5_eg']);
					$type['f6_eg']=addslashes($type['f6_eg']);
					$type['f7_eg']=addslashes($type['f7_eg']);
					$type['f8_eg']=addslashes($type['f8_eg']);
					$type['f9_eg']=addslashes($type['f9_eg']);
					$type['f10_eg']=addslashes($type['f10_eg']);
					$type['f11_eg']=addslashes($type['f11_eg']);
					$varset.="nametext[$type[cv_item_type_id]]= new Array('','$type[f1_name]','$type[f2_name]','$type[f3_name]','$type[f4_name]','$type[f5_name]','$type[f6_name]','$type[f7_name]','$type[f8_name]','$type[f9_name]','$type[f10_name]','$type[f11_name]');\n
					helptext[$type[cv_item_type_id]]= new Array('','$type[f1_eg]','$type[f2_eg]','$type[f3_eg]','$type[f4_eg]','$type[f5_eg]','$type[f6_eg]','$type[f7_eg]','$type[f8_eg]','$type[f9_eg]','$type[f10_eg]','$type[f11_eg]');\n";
				}//foreach type
				//$type_options.="<option value='0' >------------</option>";
				}//if isarry
			}//foreach header
			$id=$item['cv_item_id'];
			$hasharray = array(	'success'=>$success,'f1'=>$item['f1'],'f2'=>$f2,'f3'=>$f3,'f4'=>$item['f4'],'f5'=>$item['f5'],'f6'=>$item['f6'],'f7'=>$item['f7'],'f8'=>$item['f8'],'f9'=>$item['f9'],'f10'=>$f10,'f11'=>$f11,'type_options'=>$type_options,'varset'=>$varset,'report_flag'=>$report_flag,'web_show'=>$web_show,'id'=>$item['cv_item_id'],'user_id'=>$user_id);
			$filename = 'templates/template-cv_items_update.html';
			$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			echo $parsed_html_file;
		}
	break;

	case "add":
		$type_options="";$varset="nametext=new Array();\n helptext=new Array();\n webshow=new Array();\n";
		$headers=mysqlFetchRows("cv_item_headers","1 order by rank");
		if(is_array($headers)) foreach($headers as $header){
			$types=mysqlFetchRows("cv_item_types","cv_item_header_id=$header[cv_item_header_id] order by rank");
			if(is_array($types)) {

			foreach($types as $type){
				$tsel="";
				if (isset($cv_item_type_id)) if($cv_item_type_id==$type['cv_item_type_id']) $tsel="selected";
				$type_options.="<option value='$type[cv_item_type_id]' $tsel>$type[title]</option>\n";
				$type['f1_eg']=addslashes($type['f1_eg']);
				$type['f2_eg']=addslashes($type['f2_eg']);
				$type['f3_eg']=addslashes($type['f3_eg']);
				$type['f4_eg']=addslashes($type['f4_eg']);
				$type['f5_eg']=addslashes($type['f5_eg']);
				$type['f6_eg']=addslashes($type['f6_eg']);
				$type['f7_eg']=addslashes($type['f7_eg']);
				$type['f8_eg']=addslashes($type['f8_eg']);
				$type['f9_eg']=addslashes($type['f9_eg']);
				$type['f10_eg']=addslashes($type['f10_eg']);
				$type['f11_eg']=addslashes($type['f11_eg']);
				$varset.="nametext[$type[cv_item_type_id]]= new Array('','$type[f1_name]','$type[f2_name]','$type[f3_name]','$type[f4_name]','$type[f5_name]','$type[f6_name]','$type[f7_name]','$type[f8_name]','$type[f9_name]','$type[f10_name]','$type[f11_name]');\n
				helptext[$type[cv_item_type_id]]= new Array('','$type[f1_eg]','$type[f2_eg]','$type[f3_eg]','$type[f4_eg]','$type[f5_eg]','$type[f6_eg]','$type[f7_eg]','$type[f8_eg]','$type[f9_eg]','$type[f10_eg]','$type[f11_eg]');\n
				webshow[$type[cv_item_type_id]] = $type[default_web];\n";
			}//foreach type
			$type_options.="<option value='0' >------------</option>";
			}//isarray types
		}//foreach header

		//Processing in case there was a date error

		$hasharray = array(	'success'=>$success,
							'type_options'=>$type_options,
							'varset'=>$varset,
                            'user_id'=>$user_id);
		$filename = 'templates/template-cv_items_add.html';
		$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
		echo $parsed_html_file;
	break;

	case "view":
		$output="";
		$items=mysqlFetchRows("cv_items","user_id=$_REQUEST[user_id] limit 1");
		if(!is_array($items)) $output.="<tr><td><b>No entries in the database.</td></tr>";
		if(isset($datesort)){
			$output.="<tr><td bgcolor='#000000' style='font-size=12px; font-weight=bold; color:#CBD1FD;' colspan='3'>Sorted by Entry Order</td><td bgcolor='#000000'><b style='color:#E1E1E1;font-size:10px'>Show?</b></td></tr>";
			$items=mysqlFetchRows("cv_items","user_id=$_REQUEST[user_id] order by cv_item_id desc");
			foreach ($items as $item){
				if($item['web_show']) $pcol="<img src='/old/images/public/check.gif'>"; else $pcol="";
				$output .= "<tr><td bgcolor='#CC6699'><a style='color:white' href='cv.php?section=update&id=$item[cv_item_id]'>Update</a></td>";
				$type=mysqlFetchRow("cv_item_types","cv_item_type_id=$item[cv_item_type_id]");
				$output.="<td bgcolor='#D7D7D9'>$type[title]</td><td bgcolor='#D7D7D9'>";
				if($type['display_code']!="") eval($type['display_code']);
				$output.="</td><td align='center' bgcolor='#D7D7D9'>$pcol</td></tr>";
			}//each item
		}
		else {
			$rowcount=1;
			$headers=mysqlFetchRows("cv_item_headers","1 order by rank");
			foreach($headers as $header){
				$output.="<tr height='6'><td colspan='3' height='6'><img src='/old/images/spacer.gif' height='6' width='6'></td></tr>
				<tr><td bgcolor='#000000' style='font-size=12px; font-weight=bold; color:#CBD1FD;' colspan='2'> $header[title]</td><td bgcolor='#000000'><b style='color:#E1E1E1;font-size:10px'>Web?</b></td><td bgcolor='#000000'><b style='color:#E1E1E1;font-size:10px'>PAR?</b></td></tr>";
				$cv_item_types=mysqlFetchRows("cv_item_types","cv_item_header_id=$header[cv_item_header_id] order by rank");

				foreach($cv_item_types as $type){
		//		echo "user_id=".$_COOKIE['user_conn']['user_id']." and paper_cat_id=$cat[paper_cat_id] order by submitted desc, year desc";
					$items=mysqlFetchRows("cv_items","user_id=$_REQUEST[user_id] and cv_item_type_id=$type[cv_item_type_id] order by f11 desc, f10 desc,  f2 desc");
					if(is_array($items)){
						$output.="<tr><td colspan='4' bgcolor='#000000'  style='font-size=10px; font-weight=bold; color:white;' colspan='3'>$type[title]</td></tr>";

						//$output.="<tr><td colspan='3'><table border='0' cellpadding='3' style='border-collapse: collapse' bordercolor='#BDBDBD' cellspacing='1' width='100%'>\n";
						//build list of items to print -
						//I may need a second version for the main display

						foreach ($items as $item){
						$target=$rowcount-2;
						//if(!($type['default_web'])) $pcol="";
						 if($item['web_show']) $pcol="<a href='cv.php?unshow&id=$item[cv_item_id]&rowgo=$target&user_id=$_REQUEST[user_id]'><img src='/old/images/public/check.gif' height='15' width='15' border='0' alt='Change status'></a>"; else $pcol="<a href='cv.php?reshow&id=$item[cv_item_id]&rowgo=$target&user_id=$_REQUEST[user_id]'><img src='/old/images/public/x.gif' height='16' width='12' border='0' alt='Change status'></a>";

						 if($item['report_flag']) $ccol="<a href='cv.php?unshowpar&id=$item[cv_item_id]&rowgo=$target&user_id=$_REQUEST[user_id]'><img src='/old/images/public/check.gif' height='15' width='15' border='0' alt='Change status'></a>"; else $ccol="<a href='cv.php?reshowpar&id=$item[cv_item_id]&rowgo=$target&user_id=$_REQUEST[user_id]'><img src='/old/images/public/x.gif' height='16' width='12' border='0' alt='Change status'></a>";


							$output .= "<tr><td bgcolor='#CC6699'><a style='color:white' href='cv.php?section=update&id=$item[cv_item_id]&user_id=$_REQUEST[user_id]' name='$rowcount'>Update</a></td><td bgcolor='#D7D7D9'>";
							if($item['f2']==0) $item['f2']="";
							if($item['f3']==0) $item['f3']="";
							if($type['display_code']!="") eval($type['display_code']);
							if($item['report_flag']) $colour='#D7FFD7'; else $colour='#D7D7D9';
							$output.="</td><td align='center' bgcolor='#D7D7D9'>$pcol</td><td align='center' bgcolor='#D7D7D9'>$ccol</td></tr>";
							$rowcount++;
						}//each item

					}//is_array items

				}//foreach type

			} //foreach header
		}// not a date sort
		if(isset($rowgo)) {
			$output.="<script>window.location.href = window.location + '#$rowgo';</script>";
		}
		$user=mysqlFetchRow("users","user_id=$_REQUEST[user_id]");
		$profile_button="<button onClick=\"window.location='researchers.php?section=single&id=$user[user_id]';\">Preview Web CV</button>";

		$hasharray = array('success'=>$success,'output'=>$output,'profile_button'=>$profile_button,'user_id'=>$_REQUEST['user_id']);
		$filename = 'templates/template-cv_items_view.html';
		$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
		echo $parsed_html_file;
	break;

	case "apar":
		//Formatted verion of the VIEW page with popup links - restricted to current year items.
		//At this stage include a 'remove me' button for each item
		//NOTES: at year end the whole table must be cloned using only the current year items.
		//  It becomes cv_items-05. Supervisors can access via drop-downs to set the table.
			case "view":
		$output="";
		$items=mysqlFetchRows("cv_items","user_id=".$_COOKIE['user_conn']['user_id']." limit 1");
		if(!is_array($items)) $output.="<tr><td><b>No entries in the database.</td></tr>";
		$rowcount=1;
		$headers=mysqlFetchRows("cv_item_headers","1 order by rank");
		foreach($headers as $header){
			$output.="<tr height='6'><td colspan='3' height='6'><img src='/old/images/spacer.gif' height='6' width='6'></td></tr>
			<tr><td bgcolor='#000000' style='font-size=12px; font-weight=bold; color:#CBD1FD;' colspan='2'> $header[title]</td><td bgcolor='#000000'><b style='color:#E1E1E1;font-size:10px'>Show?</b></td></tr>";
			$cv_item_types=mysqlFetchRows("cv_item_types","cv_item_header_id=$header[cv_item_header_id] order by rank");

			foreach($cv_item_types as $type){
	//		echo "user_id=".$_COOKIE['user_conn']['user_id']." and paper_cat_id=$cat[paper_cat_id] order by submitted desc, year desc";
				$items=mysqlFetchRows("cv_items","user_id=".$_COOKIE['user_conn']['user_id']." and cv_item_type_id=$type[cv_item_type_id] AND report_flag=1 order by f10 desc, f11 desc, f2 desc");
				if(is_array($items)){
					$output.="<tr><td style='font-size=11px; font-weight=bold; color:black; padding-top:7px;' colspan='3' ><u>$type[title_plural]</u></td></tr>";

					//$output.="<tr><td colspan='3'><table border='0' cellpadding='3' style='border-collapse: collapse' bordercolor='#BDBDBD' cellspacing='1' width='100%'>\n";
					//build list of items to print -
					//I may need a second version for the main display

					foreach ($items as $item){
					$output .= "<tr><td bgcolor='#FFFFFF'>";
					if($type['display_code']!="") eval($type['display_code']);
					$output.="</td></tr>";
					if($item['f9']!="")	$output.="<tr><td><table cellpadding='0' cellspacing='0' style='margin-left:20px; margin-right:20px'><tr><td><b>$type[f9_name]</b>: $item[f9]</td></tr></table></td></tr>";
					}//each item

				}//is_array items

			}//foreach type

		} //foreach header

		if(isset($rowgo)) {
			$output.="<script>window.location.href = window.location + '#$rowgo';</script>";
		}
		$user=mysqlFetchRow("users","user_id=".$_COOKIE['user_conn']['user_id']."");
		if($user['researcher_id']!=0) $profile_button="<button onClick=\"window.location='researchers.php?section=single&id=$user[researcher_id]';\">Preview Web Profile</button>";
		else $profile_button="";
		$hasharray = array('success'=>$success,'output'=>$output,'profile_button'=>$profile_button);
		$filename = 'templates/template-cv_items_view.html';
		$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
		echo $parsed_html_file;
	break;

	break;
} //switch


//-- Footer File
include("templates/template-footer.html");
?>