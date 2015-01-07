

<?php
echo "test";
//Declarations and includes

//Open Files

//Loop through users

//Load user

//If user meets criteria

//If user is TSS
//Insert TSS header
//Else insert Other header

// Personal Details
// Summary 
//Topics
//Picture
//Projects


//End if user meets criteria

//End user loop

/*

//$filepath = "/home/html_root/htdocs/";
//$filepath = "../";
$filepath="c:/cygwin/home/tdavis/htdocs/";
include("{$filepath}admin/includes/config.inc.php");
include("{$filepath}includes/functions-required.php");
if(!($debug)) 
    include("{$filepath}includes/mail-functions.php");

//open log file
if (!$logfile = fopen("{$filepath}admin/mail_log.txt","a+")) die("Mail Log Is Not Writeable");
$date=date("j/n/y",$todays_date);
fwrite($logfile,"-----------------\nDate: $date  (Reminders) \n\n");

// Gather info re each Researcher and manufacture reminder notice
//Checklist: Department: (can be multiple); Research Topics; big list of available topics at bottom; Short Description; CV-based Description; Projects linked, Keywords, Topic Filter setting, CV Profile last updated, Have a picture

$num_msgs=0;
$users=mysqlFetchRows("users","user_level==0 order by last_name,first_name");
if (is_array($users)) {
	foreach($users as $user) {
		set_time_limit(30); // to avoid a script timeout
		//Departments  
			
		$body="<html><head><style type='text/css'>
<!--
body,td,th {
font-family: Verdana, Arial, Helvetica, sans-serif;
font-size: 11px;
}
.contreg {
border-bottom-width: 1px;
border-bottom-style: dotted;
border-bottom-color: #999999;
background-color: #CCFFCC;
}
.contspec {
border-bottom-width: 1px;
border-bottom-style: dotted;
border-bottom-color: #999999;
background-color: #FF9999;
}
.field {
border-bottom-width: 1px;
border-bottom-style: dotted;
border-bottom-color: #999999;
background-color: #CCCCCC;
font-weight: bold;
width: 200px;
}
.italic {
font-size:11px; 
font-weight:100;
}
-->
</style></head><body>

$user[first_name]:\n\n<br><br>
This is an automatically generated bi-annual confirmation of the information published on the MRU Research Website under your name. Please go over the following and update the website or, in the case of projects, return edited copy to Research Services by replying to this message. <br><br>\n

The outside world sees you as follows:<br><br>";


			   if(!is_null($first_dept)) $body.="

<a href='http://$server_name/researchers.php?section=all&search_department=$first_dept'>In the list of faculty</a>, and when they click on your name, ";

			   $body.="
<a href='http://$server_name/researchers.php?section=single&id=$researcher[researcher_id]'>your Faculty Profile</a><p>The two main descriptive fields are highlighted below in red. Please ensure that they are up-to-date descriptions.<p>


<table cellspacing=0 border='1' cellpadding=4><tr><td colspan=2 bgcolor='#000000' ><b><font color='#FFFFFF'>User Information</font></b></td></tr><br>\n";
		$body.="
<tr><td bgcolor='#CCCCCC' width='200'><b>Researcher Name:</b></td><td bgcolor='#CCFFCC'>$researcher[first_name] $researcher[last_name]</td></tr>\n
<tr><td bgcolor='#CCCCCC' width='200'><b>Username:</b></td><td bgcolor='#CCFFCC'>$user[username]</td></tr><tr><td bgcolor='#CCCCCC' width='200' valign='top'><b>Password:</b></td>\n";
		if ($user['username']==$user['password']) $body.="<td bgcolor='#CCCCCC' valign='top'>Your password is still the default - the same as your username. Please change it so that no one else can access your profile and CV</td></tr>";
		else $body .="<td bgcolor='#CCFFCC'>**** (if you need a reminder use the button on the login page)</td></tr>\n";

		if($user['mail_deadlines'] <> 1) $maild="<b>disabled</b>"; else $maild="enabled";
		if($user['mail_opps'] <> 1) $mailo="<b>disabled</b>"; else $mailo="enabled";
		
		//User Topics
		$objects = mysqlFetchRowsOneCol("user_topics_filter", "topic_id", "user_id=$user[user_id]");
		$topics = mysqlFetchRows("topics_research", "1 ORDER BY name");
		$topic_options = "";
		$topic_list="";
		$ids=NULL;
		if(is_array($objects)) foreach($objects as $object) $ids[] = $object['topic_id'];			
		if(is_array($topics)) {
			foreach($topics as $topic) {
				if(is_array($objects)) { 
				if(@in_array($topic['topic_id'], $objects)) {$topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>"; $topic_list.="$topic[name]<br>";}
				else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
				 }
				 else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
			}
		}
		if ($topic_list == "") $topic_list="<u>All</u> topics selected";
		$body.="
<tr><td valign='top' bgcolor='#CCCCCC' width='200'><b>Topic Filter:</b><br><i class='italic'>(the topic areas you are interested in receiving information on)</font></i></td><td valign='top' bgcolor='#CCFFCC'>$topic_list</td></tr>";

//<td valign='top' align='middle'><form action=''><i>&nbsp;&nbsp;The full list</i><br>&nbsp;&nbsp;<select style='font-size:11px;' name='topics_research[]' multiple size='8'>$topic_options</select></form></td></tr></table></td></tr>\n";
		
		
		$body.="
<tr><td valign='top' bgcolor='#CCCCCC' width='200'><b>Options:</b></td><td valign='top' bgcolor='#CCFFCC'>Automatic mail of Deadlines: $maild<br>Automatic mail of Opportunities: $mailo</td></tr>\n";

		
		
		
		//-- Selects the Topics
		$objects = mysqlFetchRowsOneCol("researchers_associated", "object_id", "researcher_id=$researcher[researcher_id] AND table_name='topics_research'");
		$topics = mysqlFetchRows("topics_research", "1 ORDER BY name");
		$topic_options = ""; 
		$topic_list="";
		$ids=NULL;
		if(is_array($objects)) foreach($objects as $object) $ids[] = $object['topic_id'];			
		if(is_array($topics)) {
			foreach($topics as $topic) {
				if(is_array($objects)) { 
				if(@in_array($topic['topic_id'], $objects)) {$topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>"; $topic_list.="$topic[name]<br>";}
				else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
				 }
				 else $topic_options .= "<option value='$topic[topic_id]'>$topic[name]</option>";
				
			}
		}



//<td valign='top' align='middle'><form action=''><i>&nbsp;&nbsp;The full list</i><br>&nbsp;&nbsp;<select name='topics_research[]' multiple size='8' style='font-size:11px;'>$topic_options</select></form></td></tr></table></td></tr>\n";
		$body.="
<tr><td colspan=2>All of the above items can be edited on your <a href='http://$server_name/preferences.php'>custom Preferences page.</a><br><br><br></td></tr>
<tr><td colspan=2 bgcolor='#000000' ><b><font color='#FFFFFF'>Faculty Profile</font></b></td></tr>\n";
		$profile = mysqlFetchRow("profiles", "user_id=$user[user_id]") ;
		if(is_array($profile)){
			$body.="
<tr><td bgcolor='#CCCCCC' width='200'><b>Job Title/Rank:</b></td><td bgcolor='#CCFFCC'>$profile[present_title]</td></tr>\n";

			$dept=mysqlFetchRow('departments',"department_id=$user[department_id]");
			if(is_array($dept)) $dept_name=$dept['name']; else $dept_name="";
			$body.="
<tr><td bgcolor='#CCCCCC' width='200'><b>Home Department:</b></td><td bgcolor='#CCFFCC'>$dept_name</td></tr>\n
<tr><td valign='top' bgcolor='#CCCCCC' width='200'><b>Department$dept_p:</b></td><td valign='top' bgcolor='#CCFFCC'>$dept_list</td></tr>\n

<tr><td bgcolor='#CCCCCC' width='200'><b>Phone:</b></td><td bgcolor='#CCFFCC'>$profile[phone]</td></tr>\n
<tr><td bgcolor='#CCCCCC' width='200'><b>Fax:</b></td><td bgcolor='#CCFFCC'>$profile[fax]</td></tr>\n
<tr><td bgcolor='#CCCCCC' width='200'><b>Email:</b></td><td bgcolor='#CCFFCC'>$profile[email]</td></tr>\n";


			if($user['researcher_id'] != 0) {
				$researcher = mysqlFetchRow("researchers", "researcher_id=".$user['researcher_id']);
				
				$body.="
<tr><td valign='top'  bgcolor='#CCCCCC' width='200'><b>Research Topics:</b><br><i class='italic'>(the areas you work in)</i></td><td valign='top'  bgcolor='#CCFFCC'>$topic_list</td></tr>\n				
<tr><td valign='top' bgcolor='#CCCCCC' width='200'><b>Research/Teaching Interests:</b></td><td valign='top' bgcolor='#FF9999'>$researcher[description]</td></tr>\n
<tr><td valign='top'  bgcolor='#CCCCCC' width='200'><b>Synopsis of Above:</b><br><i class='italic'>(text below your name in the department list)</font></i></td><td valign='top' bgcolor='#FF9999'>$profile[research_and_teaching_interests]</td></tr>\n
<tr><td valign='top'  bgcolor='#CCCCCC' width='200'><b>Professional Affiliations / Other Details:</b></td><td valign='top' bgcolor='#CCFFCC'>$profile[professional]</td></tr>\n
";
			}//if researcher
		
		} #my_cv
		else $body.="<tr><td colspan=2>You do not have a 'Profile' set up.<br><br><br></tr>";
		$body.="
<tr><td colspan=2>All of the above items can be edited on your <a href='http://$server_name/profile.php'>Modify Profile page.</a><br><br><br></td></tr>";
		$body.="
<tr><td colspan=2  bgcolor='#000000' ><font color='#FFFFFF'><b>Your Projects</b></font></td></tr>";
		$projects = mysqlFetchRows("projects AS p LEFT JOIN projects_associated AS pra USING(project_id) LEFT JOIN researchers AS r ON(r.researcher_id=pra.object_id)","pra.table_name='researchers' AND pra.object_id=$researcher[researcher_id]");

		$project_list = "";
		if(is_array($projects)) {
			foreach($projects as $project) {
				$body.="
<tr><td valign='top' bgcolor='#CCCCCC' width='200'><b>Name:</b></td><td bgcolor='#CCFFCC'><u>$project[name]</u></td></tr>
<tr><td valign='top' bgcolor='#CCCCCC' width='200'><b>Synopsis:</b></td><td valign='top' bgcolor='#CCFFCC'>$project[synopsis]</td></tr>
<tr><td bgcolor='#CCCCCC' width='200'><b>Full Listing:</b></td><td bgcolor='#CCFFCC'><a href='http://$server_name/projects.php?section=single&id=$project[project_id]'>Full Project Description Online</a></td></tr><tr><td colspan=2>&nbsp;</td></tr>";
				
				
			}
			$body.="
<tr><td colspan=2>To add to this list or to update any of the above (including the description) reply to this email with the new / edited text. Send any pictures you wish associated with the project to Research Services (If they aren't digital I'll scan and return them)<br><br><br></td></tr>";
		}	
		else $body.= "
<tr><td colspan=2 bgcolor='#FF9999'>You have no projects associated with your name</td></tr>
<tr><td colspan=2>If you would like to have one or more of your research projects associated with your listing, please forward the information to Research Services (pictures too...)<br><br><br></td></tr>";
		

		$body.="
		<tr><td valign='top' bgcolor='#000000' colspan=2><font color='#FFFFFF'><b>Your Picture:</b></font></td></tr>";

		$pictures = mysqlFetchRows("pictures_associated AS pa LEFT JOIN pictures AS p USING(picture_id)", "pa.object_id='$researcher[researcher_id]' AND pa.table_name='researchers' AND p.feature !=1 order by associated_id");
		if(is_array($pictures)){
			foreach($pictures as $picture) {
				if($picture['feature'] != 1) {
					$image = 'http://$server_name/pictures/'.$picture['file_name'];
				}
			}
			$body.="
			<tr><td valign='top' colspan=2 align='center'><img src='$image'></td></tr>
			<tr><td colspan=2>Not happy? Old hair style? Send an updated picture to  Research Services (digital preferred; prints will be scanned and returned)</td></tr>";
		}
		else $body.="<tr><td valign='top' bgcolor='#FF9999' colspan=2>There is no picture associated with your file. Please send one along ASAP (good resolution digital preferred, but a print would do)</td></tr>";
		
		
		$body.="</table><p>&nbsp;</p><p>
		Thanks for your assistance,<br>
		Trevor<br>
		<hr width='200' align='left'>
		MRU Research Services<br>
		research@mtroyal.ca<br>
		
		<hr width='200' align='left'>";
		
		if($debug){
            //Dump Directly to screen
            echo "<hr>To: $user[first_name] $user[last_name] <hr>";
            echo $body;
        }
        
        else{
        
        //Mail it
		$subject = "Please update your online profile";
		global $debug;
		global $server_name;
		$headers = "From: MRC Research Services <research@mtroyal.ca>\n";
		$headers .= "Reply-To: research@mtroyal.ca\n";
    	$headers .= "X-Mailer: PHP/" . phpversion();
		$headers  .= "\nMIME-Version: 1.0\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1";
		
		if (!$debug) $result = mail($user['email'],	$subject, $body, $headers);
		else $result=true;
		if ($result==true) {
			if ($debug)	echo("Sent to $user[first_name] $user[last_name]\n");
			$date=mktime();
			$date_text = date("M j y g:i a", $date);
			$values2=array('null',$subject,$user['email'],$date,$date_text);
			$result = mysqlInsert("maillog",$values2);
			if ($result != 1) echo("Error in logging: $result<br>");
		} 
		else {
			$date=mktime();
			$date_text = date("M j y g:i a", $date);
			$subject .= " SENDING ERROR";
			$values2=array('null',$subject,$user['email'],$date,$date_text);
			$result = mysqlInsert("maillog",$values2);
			if ($result != 1) echo("Error in logging: $result<br>");
		}
        
        }
		$num_msgs++;
		
		

		
		
	} #for each user
} #if isarray users

				

if($num_msgs >= 1) fwrite($logfile,"Profile update sent to $num_msgs users\n\n");
else fwrite($logfile,"Profile update sent to no one at all\n\n");

fclose($logfile);

*/
?>
