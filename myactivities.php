<?php
require_once('includes/global.inc.php');
$internal=true;
$title="";
$page="internal";
//$tomorrow = strtotime("+1 day");
function getResearchItems() {
	global $internal,$db;
	$research_items=array();

	$sql="SELECT pr.name, pr.synopsis, p.file_name, p.caption, pr.project_id FROM projects AS pr LEFT JOIN pictures_associated as pa ON(pr.project_id=pa.object_id) LEFT JOIN pictures AS p USING(picture_id)
	WHERE pa.table_name='projects' AND p.feature=1 AND pr.feature=1 ORDER BY rand() LIMIT 10";
	//Obtain the ranting of//print $sql ."<br>";
	//$project = $db1->GetSQL($sql,'assoc');

	//$sql=getsqlRating($logged_in, 'projects',$internal,$todays_date,$user_id );
	$projects = $db->GetAll($sql);
	if (!$projects) {
		print $db->ErrorMsg();
	}

	if ($projects) {
		//$project['synopsis'] = str_replace("\n", "<br>", $project['synopsis']);
		//Trim string length to fit box
		$research_item .= "<div class='titles'>Current Research Projects</div>";
		foreach($projects as $project){
			$maxlength = 220;
			$researchers = $db->GetAll("SELECT * FROM projects_associated AS pra LEFT JOIN researchers AS r ON(r.researcher_id=pra.object_id)
					WHERE pra.table_name='researchers' AND pra.project_id=" . $project['project_id'] ." and r.researcher_id >0");
			$researchers_names ="";
			if(is_array($researchers)) {
			   foreach($researchers as $researcher) {
					$dis=$db->Execute("SELECT * FROM users_disabled left join users on users_disabled.user_id=users.user_id WHERE users.researcher_id='". $researcher['researcher_id'] ."'");
					if(!(is_array($dis))){
						//$rcount++;
						$researchers_names .= $researcher['first_name'] ." ".$researcher['last_name'].", ";
					}
			   }
			}
			$researchers_names = rtrim($researchers_names,', ') .".";
			if (strlen($project['synopsis']) > $maxlength) {
				$whitespace = strpos($project['synopsis']," ",$maxlength);
				if ($whitespace !== false) $synop = substr($project['synopsis'],0,$whitespace);
				else $synop = substr($project['synopsis'],0,$maxlength);
				$synop .= "...";
				$synop = nl2br($synop);
			}
			else $synop = nl2br($project['synopsis']);

			if($project['file_name'] != "") $picture = $public_picture_path.$project['file_name'];

			$research_item=array();
			$research_item["researchers_names"]=$researchers_names;
			$research_item["image"] =$picture;

			$size = getimagesize($picture);
			$research_item["image_width"] =$size[0];
			$research_item["image_height"] =$size[1];
			$research_item["project_name"] =$project['name'];
			$research_item["synopsis"]=$synop;
			$research_item["project_id"]=$project['project_id'];
			$research_items[]=$research_item;
		}
	}
	return $research_items;
}

function getDeadlines() {	//Deadlines & Opportunities
	global $internal,$db;
	$deadline_list=array();
	$todays_date=mktime();

	//print "SELECT * FROM deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id WHERE d_date >= $todays_date AND approved='yes' AND internal != 'yes' ORDER BY d_date";
	if(isset($internal)) $values = $db->GetAll("SELECT * FROM deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id WHERE d_date >= $todays_date AND approved='yes' AND internal = 'yes' ORDER BY d_date");
	else $values = $db->GetAll("SELECT * FROM deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id WHERE d_date >= $todays_date AND approved='yes' AND internal != 'yes' ORDER BY d_date");

	//$sql=getsqlRating($logged_in, 'deadlines',$internal,$todays_date,$user_id );
	//print $sql;
	//$values =$db->GetAll($sql);
	//print_r($values);

	$warning="";

	//print $todays_date;



	if (is_array($values)) {
		foreach($values as $index) {
			$index['d_date'] = date("F j, Y", $index['d_date']);
			$index['close_warning_date'] = date("F j, Y", $index['close_warning_date']+45);
			//$index['synopsis'] = str_replace("\n", "<br>", $index['synopsis']);
			if($index['close_warning_date']==$todays_date){
				  $warning='!';
			}else {
				  $warning='&nbsp';
			}


			$deadline=array();
			$deadline["warning"]=$warning;
			$deadline["title"]=$index['title'];
			$deadline["date"]=$index['d_date'];
			$deadline["id"]=$index['deadline_id'];
			$deadline["close_warning_date"]=$index['close_warning_date'];
			//$deadline_list .=drawstars($index['fr']);
			/*$deadline_list .='<td nowrap><br style="clear: both;"><ul id="star'.$i.'" class="star" onmousedown="star.update(event,this)" onmousemove="star.cur(event,this)" title="Rate This!">
									   <li id="starCur'.$i.'" class="curr" title="'.$index['fr'].'" ></li>
									   </ul>
									   <br><div id="starUser'.$i.'" class="user">'.$index['fr'].'</div></td>';
			*/
			$deadline_list[]=$deadline;
		}


	}
	$dummy_values[]=array("date"=>"May 31, 2011","title"=>"CIHR - Aboriginal Community-Based Research");
	$dummy_values[]=array("date"=>"May 31, 2011","title"=>"CIHR: An Opportunity for New Researchers in Aboriginal Health");
	$dummy_values[]=array("date"=>"May 31, 2011","title"=>"NSERC Discovery Grants ");

	$deadline_list=array_merge($deadline_list,$dummy_values);
	return $deadline_list;
}

function getGrants() { ///MyGrants
	global $db,$internal;
	$grant_list=array();



	//if(isset($internal)) $values = $db->GetAll("SELECT * FROM deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id WHERE d_date >= $todays_date AND approved='yes' AND internal = 'yes' ORDER BY d_date");
	//else $values = $db->GetAll("SELECT * FROM deadlines as d left join deadline_dates as dd on d.deadline_id=dd.deadline_id WHERE d_date >= $todays_date AND approved='yes' AND internal != 'yes' ORDER BY d_date");
	//$date=date(dmY);

	$date=0;



	//$values_mygrants=$db->GetAll("SELECT * FROM grants");
	$values_mygrants=$db->GetAll("SELECT grants.pi_name, grants.agency_name AS Office, grants.finance_contact, grants.program AS title, grants.budget_auth AS Granted, grants.budget_done AS Remaining, grants.date_award FROM grants");
	$warning="";
	//$sql=getsqlRating($logged_in, 'grants',$internal,$todays_date,$user_id );
	//print $sql;
	//$values_mygrants =$db->GetAll($sql);



	if (is_array($values_mygrants)) {

		foreach($values_mygrants as $index) {

			//$index['synopsis'] = str_replace("\n", "<br>", $index['synopsis']);
			$index['date_award'] = date("F j, Y", $index['date_award']+45);
			if($index['date_award']==$todays_date){
				  $warning='!';
			}else {
				  $warning='&nbsp';
			}
			$grant=array();
			$grant["warning"]=$warning;
			$grant["title"]=$index['title'];
			//$mygrants .="<td align='right'><div>" .formatnumber($index['Granted']) ."</div></td>";
			//$mygrants  .="<td align='right'><div>" .formatnumber($index['Remaining'])."</div></td>";
			$grant["granted"]=number_format(floatval($index['Granted']),2,'.',',');
			$grant["remaining"]=number_format(floatval($index['Remaining']),2,'.',',');
			$grant["office"]=$index['Office'];
			//$mygrants .=drawstars($index['fr']);
			$grant_list[]=$grant;

		}

	}
	// DUMMY VALUES
	$dummy_values[]=array('warning'=>'!','title'=>'Design and implementation of...','Granted'=>'20000','Remaining'=>'13000','Office'=>'ORS');
	$dummy_values[]=array('warning'=>'!','title'=>'Perfect pitch...','Granted'=>'50000','Remaining'=>'19000','Office'=>'ORS');
	$dummy_values[]=array('warning'=>'!','title'=>'Park design in 2010...','Granted'=>'17000','Remaining'=>'9000','Office'=>'ORS');
	$dummy_values[]=array('warning'=>'!','title'=>'Design and implementation of...','Granted'=>'40000','Remaining'=>'8000','Office'=>'ORS');
	$dummy_values[]=array('warning'=>'!','title'=>'List conference travel...','Granted'=>'10000','Remaining'=>'3000','Office'=>'ORS');

	$grant_list=array_merge($grant_list,$dummy_values);
	return $grant_list;
}

function getNews() {
	global $db,$internal;
	$news_item=array();

	//$news = $db1->mysqlFetchRow("news", "post_date <= $todays_date AND expiry_date > $todays_date AND internal='yes' ORDER BY rand()");
	//$sql="SELECT * FROM news WHERE post_date <= $todays_date AND expiry_date > $todays_date AND internal='yes' ORDER BY rand()";
	$sql="SELECT * FROM news WHERE post_date ORDER BY rand() LIMIT 10";
	//$news =$db1->GetSQL($sql,'assoc');

	$news=$db->GetAll($sql);
	if($news) {
		foreach ($news as $new) {

			$new['synopsis'] = nl2br($new['synopsis']);
			$news_item["source"]=$new['source'];
			$news_item["title"]=$new['title'];
			$news_item["synopsis"]=$new['synopsis'];
			$news_item["news_id"]=$new['news_id'];
			$news_list[]=$news_item;
		}

	//	$news_item .= "<span style='font-size:11px;'>".date("M d", $news['start_date']);
	//	if($news['start_time'] == 0 ) $event['start_time'] = "";
		//else $event_item .= " - ".date("g:ia", $event['start_time']);
		//if($event['end_time'] == 0 ) $event['end_time'] = "";
		//else $event_item .= " - ".date("g:ia", $event['end_time']);
	}

	return $news_list;
}

function getResearcher() {

	$picture = "";
	$researcher_item = "";
	/*
	$researcher = mysqlFetchRow("researchers AS r
	LEFT JOIN pictures_associated as pa ON(r.researcher_id=pa.object_id)
	LEFT JOIN pictures AS p USING(picture_id)",
	"pa.table_name='researchers' AND r.feature=1 AND p.feature=1 ORDER BY rand()",
	"r.first_name, r.last_name, r.description, p.file_name, p.caption, r.researcher_id");*/

	$sql="SELECT DISTINCT r.first_name, r.last_name, r.description, p.file_name, p.caption, r.researcher_id
		  FROM researchers AS r LEFT JOIN pictures_associated as pa ON(r.researcher_id=pa.object_id)
		  LEFT JOIN pictures AS p USING(picture_id) WHERE pa.table_name='researchers' AND r.feature=1 AND p.feature=1 ORDER BY rand() LIMIT 10";


	//$researcher = $db1->GetSQL($sql,'assoc');
	//$researcher = $db->GetAll($sql);
	//print $sql;
	//$sql=getsqlRating($logged_in, 'researchers',$internal,$todays_date,$user_id );
	$researchers = $db->GetAll($sql);

	if ($researchers) {
		$i=0;
		$researcher_item .= "<div id='TextBox'>";
		foreach($researchers as $researcher)
		{
			//Trim string length to fit box
			$maxlength = 220;
			if (strlen($researcher['description']) > $maxlength) {
				$whitespace = strpos($researcher['description']," ",$maxlength);
				if ($whitespace !== false) $synop = substr($researcher['description'],0,$whitespace);
				else $synop = substr($researcher['description'],0,$maxlength);
				$synop .= "...";
				$synop = nl2br($synop);
			}
			else $synop = nl2br($researcher['description']);


	//	if($researcher['file_name'] != "") $picture = "<img src='".$public_picture_path.$researcher['file_name']."' bordercolor='black' border='1' title='".$researcher['caption']."' align='center'>";
			if($researcher['file_name'] != "") $picture = $public_picture_path.$researcher['file_name'];
			else $picture = "{$public_picture_path}default_researcher.jpg";
			if($i==0){
				$researcher_item .= "<style> div #Researchers { background: url('".$picture."') no-repeat left ;}</style>";

				$researcher_item .= "<div class='ResearcherText'>\"" .$synop ."\"<br><br></div>";
				$researcher_item .= "<div class='ResearcherName'>Prof. " . $researcher['first_name'] ." ". $researcher['last_name'] ."</div>";
				$researcher_item .= "<div id='MoreInfo'><a href='./researchers.php?section=single&id=". $researcher['researcher_id'] ."'>Read more ></a></div>";

				//$size = getimagesize($picture);
				//$researcher_item .= "<table width='100%'><tr><td valign='top' align='center'>";
				//$researcher_item .= "<table width='$size[0]' height='$size[1]' border='1' cellpadding='3' background='$picture'>";
				//$researcher_item .= "<tr><td>&nbsp;</td></tr></table>";
				//$researcher_item .= "</td></tr><tr><td align='center'><span class='bluebox'><b>" . $researcher['first_name'] ." ". $researcher['last_name'] ."</b></span></tr></td><tr><td class='bluebox'><br>";
				//$researcher_item .= "$synop";
				//$researcher_item .= "<a class='bluebox'  href='./researchers.php?section=single&id=". $researcher['researcher_id'] ."'><br><br>More Info...</a></span></td></tr><tr><td>&nbsp;</td></tr>";
				//$researcher_item .= "</table>";
			}else{
				/*
				$researcher_item .= "<a href='./researchers.php?section=single&id=". $researcher[$i]['researcher_id'] ."'><div id='Researchers".$i."'style='border: 1px solid #ffffff;>";
				$researcher_item .= "<img src='" .$picture."'></a>";
				$researcher_item .= "</div></a>";
				$researcher_item .= "";
				  */

				$researcher_item .= "<a class='bluebox'  href='./researchers.php?section=single&id=". $researcher['researcher_id'] ."'>";
				$researcher_item .= "<img src='" .$picture."'></a>";
				//$researcher_item .= "<div id='R".$i."'>";
				//$researcher_item .= "<style>div #R" .$i." { border: 1px; float:left; margin-top:20px; margin-right:5px; width:60%; width:150px; height:120px; background: url('".$picture."') no-repeat left ;}</style>";
				//$researcher_item .= "</div>";
				$researcher_item .= "</a>";
			}
			$i++;
		}
		$researcher_item .= "</div>";
	}
}

function startpage() {
	//$rating_value=getRating($logged_in,);
	//updateRatings();


	//$hasharray = array('research_item'=>$research_item, 'news_item'=>$news_item, 'researcher_item'=>$researcher_item,'deadline_list'=>$deadline_list,'mygrants'=>$mygrants);

	$tmpl=loadPage("myactivities_home","My activities","my_activities");

	$tmpl->addRows("deadlines",getDeadlines());
	$tmpl->addRows("grants",$grants=getGrants());
	$tmpl->addRows("news",getNews());
	//$tmpl->addRows("researcher",getResearcher());
	//$tmpl->addRows("research_items",getResearchItems());

	//print_r($hasharray);
	return $tmpl;
}

if(sessionLoggedin()==true) {
	$username=sessionLoggedUser();
	$orig_username=$username;

	cleanUp($username);
	if($username!=$orig_username) {
		displayBlankPage("Invalid username","<h1>Invalid username ($orig_username)</h1>Possible hacking attempt, please contact your sysadmin.");
		die(1);
	}

	switch($_REQUEST["section"]) {
		case "my_profile":
			require_once("includes/my_profile.inc.php");
			$tmpl=my_profile();
			break;
		case "my_research":
			require_once("includes/my_research.inc.php");
			$tmpl=my_research();
			break;
        case "my_projects":
            require_once("includes/my_projects.inc.php");
            $tmpl=my_projects();
            break;
        case "quick_save":
            require_once("includes/my_projects.inc.php");
            $result=quickSaveProject();
            break;
        case "":
		default:
//			$tmpl=startpage();
			break;
	}
}
else {
	$tmpl=loadPage("accessdenied","My Activities","my_activities");
}
$tmpl->displayParsedTemplate();

?>
