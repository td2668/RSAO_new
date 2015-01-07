<?php
require_once('includes/global.inc.php');

function deadlines(){
	global $db,$show;

	$tmpl=loadPage("deadlines","Deadlines","deadlines");

	if(isset($_GET['deadline_id']) && isset($_GET['date_id'])){
		$sql = "SELECT d.deadline_id, d.title, d.warning_message, d.description,
						d.synopsis, d.topics, d.approved, d.internal, d.override,d.no_deadline,

						dd.date_id,	dd.d_date, 	dd.close_warning_date,
						dd.early_warning_date, dd.expiry_date, 	dd.days_in_advance

						FROM deadlines d, deadline_dates dd
						WHERE d.deadline_id = dd.deadline_id
						AND d.deadline_id = ".$_GET['deadline_id']."
						ORDER BY dd.d_date";

		$deadlines = $db->GetAll($sql);

		if(count($deadlines)>0){
		// set up the "date" field for the template
			$deadline = $deadlines[0];
			for($i=0; $i<count($deadlines); $i++){
				if($deadlines[$i]["d_date"])
					$deadlines[$i]["date"] = date("F j, Y", $deadlines[$i]["d_date"]);
			}
		}

		$tmpl->setAttribute("page_single", "visibility", "show");
		$tmpl->setAttribute("page_all", "visibility", "hidden");

		if(count($deadlines)==1){
			// one deadline, just put it in
			$tmpl->addRows("page_single", $deadlines);
		}
		else if(count($deadlines>1)){
			// more than one, add other dates to list
			$deadline = $deadlines[0];
			for($i=1; $i<count($deadlines); $i++){
				$deadline['date'] .= "; " . $deadlines[$i]['date'];
			}
			// show a single entry version of the template rather than the all-entries version
			$tmpl->addRows("page_single", array($deadline));
		}
		else{
			// none found, error
			$tmpl->addVar("DEADLINE","DESCRIPTION","Deadline not found.");
		}
	}
	else{
		// list all deadlines
        if(sessionLoggedin()){
            $username=sessionLoggedUser();
            $sql="SELECT * FROM users WHERE username = \"$username\"";
            $user=$db->GetRow($sql);

            if(is_array($user)==false or count($user)==0) {
                displayBlankPage("Error","<h1>Error</h1>There was a database error creating your initial user record.");
                die;
            }
           $user_topics=array();
           $user_topics_list=$db->GetAll("select topic_id from user_topics_filter where user_id=$user[user_id]");
           //$user_topics=reset($user_topic_list);
           if(is_array($user_topics_list)) $filter="<span style='color:red'>Engaged</span>";
           else $filter="None";
           foreach($user_topics_list as $v) $user_topics[] = $v['topic_id'];
        }
        else $filter = "(Log in to set topic filters)";  // not logged in

        $tmpl->addVar("PAGE_ALL","FILTER",$filter);


		$sql = "SELECT d.deadline_id, d.title, d.warning_message, d.description,
						d.synopsis, d.topics, d.approved, d.internal, d.override, d.no_deadline,

						dd.date_id,	dd.d_date, 	dd.close_warning_date,
						dd.early_warning_date, dd.expiry_date, 	dd.days_in_advance

						FROM deadlines d, deadline_dates dd
						WHERE d.deadline_id = dd.deadline_id
                          AND dd.d_date > ".time(). "
                          AND d.approved = 'yes'
						ORDER BY dd.d_date
                        ";
                        //removed LIMIT 30 for our writer (TD 2011-06-15)
//						AND d.d_date >= ".(time()+24*60*60)."

		$deadlines = $db->GetAll($sql);

		if(count($deadlines)>0){
            $deadlines_show=array();
			for($i=0; $i<count($deadlines); $i++){
				if($deadlines[$i]["d_date"])
					$deadlines[$i]["date"] = date("F j, Y", $deadlines[$i]["d_date"]);

                //remove any deadlines not in the filter
                $deadline_topics = explode(",",$deadlines[$i]['topics']);
                //Override - if no topics entered then assume all
                if($deadline_topics[0]=='') $deadlines_show[]=$deadlines[$i];
                else {
                    $show = false;
                    if(isset($user_topics)){
                        if(is_array($user_topics)){
                            foreach($user_topics as $user_topic)
                                if(in_array($user_topic,$deadline_topics)) $show=TRUE;
                            //array_walk($user_topics, 'compareArrays', $deadline_topics);
                            //if($show) $deadlines_show[]=$deadlines[$i];
                           if($show) $deadlines_show[]=$deadlines[$i];
                        }
                        else $deadlines_show[]=$deadlines[$i];  //no topics, so default to show all
                    }
                    else $deadlines_show[]=$deadlines[$i];  //no topics, so default to show all
                }


			}
            if(count($deadlines_show) ==0) $tmpl->addVar("DEADLINES", "SYNOPSIS", "There are no deadlines matching your filter settings.<br><br><br><br><br><br>");
			$tmpl->addRows("deadlines", $deadlines_show);
		}
		else{
			// THERE SHOULD PROBABLY BE CODE HERE EXPLAINING THAT THERE ARE NO DEADLINES
			$tmpl->addVar("DEADLINES", "SYNOPSIS", "There are no deadlines at the moment.");
		}

	}


	return $tmpl;
}

function news(){
    global $configInfo;
	global $db;

	$tmpl=loadPage("news","News","news"); 

	if(isset($_GET['news_id'])){
		$sql = "
            SELECT n.*, m.media_id AS media_id, m.title AS media_title, m.synopsis AS media_synopsis
            FROM news AS n
            LEFT JOIN media_associated AS ma ON ma.object_id = n.news_id AND ma.table_name = 'news'
            LEFT JOIN media AS m ON m.media_id = ma.media_id
            WHERE news_id = {$_GET['news_id']}
        ";
		$news = $db->GetAll($sql);
		if(count($news) > 0){
            $news[0]["event_date"] = date("F j, Y", $news[0]["event_date"]);
            $news[0]["post_date"] = date("F j, Y", $news[0]["post_date"]);
			$tmpl->setAttribute("page_single", "visibility", "show");
			$tmpl->setAttribute("page_all", "visibility", "hidden");

			//**** ADD PICTURE **************************************
			$sql=" SELECT pictures.file_name,pictures.picture_id
							 FROM pictures,pictures_associated
							WHERE pictures_associated.table_name=\"news\"
								AND object_id=".intval($_GET["news_id"])."
								AND pictures_associated.picture_id=pictures.picture_id
								AND pictures.feature=FALSE
								ORDER BY RAND()
								LIMIT 1";

			$pictures=$db->GetAll($sql);
			$picture=reset($pictures);
            //echo("$sql<br><br>");
			if($picture){
					$img_url=$configInfo['picture_url']."$picture[file_name]";
					$tmpl->addVar("PIX","picture_path","$img_url");
			}
			//*******************************************************

            //**** ADD MEDIA LINK(S) **************************************
            for($i=0; $i<count($news); $i++){
                $news[0]["media_link"] .= (isset($news[$i]['media_title']) && $news[$i]['media_title'] != '') ? '<a href="/media.php?mr_action=detail&media_id=' . $news[$i]['media_id'] . '">' . $news[$i]['media_title'] . '</a><br />' : '';
            } // for
            //*******************************************************
            $displayData = array();
            $displayData[] = $news[0];
			$tmpl->addRows("page_single", $displayData);
		}
		else{
			$tmpl->addVar("NEWS","DESCRIPTION","News item not found.");
		}
	}
	else{
		// list all news
        $todays_date = time();
		$sql = "SELECT n.*, m.title AS media_title
            FROM news AS n
            LEFT JOIN media_associated AS ma ON ma.object_id = n.news_id AND ma.table_name = 'news'
            LEFT JOIN media AS m ON m.media_id = ma.media_id
            WHERE $todays_date >= n.post_date 
            ORDER BY n.event_date desc
        ";
        //td 22-12-10 rEMOVED THE EXPIRY DATE FROM THE WHERE CLAUSE TO SHOW ARCHIVE TOO 
        //AND $todays_date <= n.expiry_date
        
		$news = $db->GetAll($sql);
		if(count($news)>0){
			for ($i=0; $i<count($news); $i++) {
				$news[$i]["event_date"] = date("F j, Y", $news[$i]["event_date"]);
				$news[$i]["post_date"] = date("F j, Y", $news[$i]["post_date"]);

                //**** ADD PICTURE **************************************
                $sql=" SELECT pictures.file_name,pictures.picture_id
                             FROM pictures,pictures_associated
                            WHERE pictures_associated.table_name=\"news\"
                                AND object_id=".$news[$i]['news_id']."
                                AND pictures_associated.picture_id=pictures.picture_id
                                AND pictures.feature=FALSE
                                ORDER BY RAND()
                                LIMIT 1";
                //echo("$sql<br><br>");
                $pictures=$db->GetAll($sql);
               // echo ("Got ".count($pictures). "<br>");
                $picture=reset($pictures);

                if($picture){
                        $img_url=$configInfo['picture_url']."$picture[file_name]";
                        $news[$i]['picture_path']=$img_url;
                        $img_size = GetImageSize($configInfo['picture_path'].$picture['file_name']);
                        if($img_size[1]>110) $news[$i]['picture_height']=110;
                } else {
                        $img_url="images/spacer.gif";
                        $news[$i]['picture_path']=$img_url;
                }
                //*******************************************************
                $news[$i]["media_html"] = ($news[$i]['media_title'] != '') ? 'MEDIA' : '';
			}

			$tmpl->addRows("news", $news);
		}
		else{
			// THERE SHOULD PROBABLY BE CODE HERE EXPLAINING THAT THERE IS NO NEWS
			$tmpl->addVar("NEWS", "DESCRIPTION", "There is no news at the moment.");
		}

	}


	return $tmpl;
}

function format_event_dates($event){
		if($event["start_date"]){
			$event["start_date"] = date("F j, Y", $event["start_date"]);
		}
		if($event["start_time"]){
			$event["start_time"] = date("g:i A", $event["start_time"]);
		}
		if($event["end_date"]){
			$event["end_date"] = date("F j, Y", $event["end_date"]);
		}
		if($event["end_time"]){
			$event["end_time"] = date("g:i A", $event["end_time"]);
		}

		return $event;
}

function events(){
	global $db;

	$tmpl=loadPage("events","Events","events");

	if(isset($_GET['event_id'])){
		$sql = "SELECT *
                  FROM events
                 WHERE event_id=".$_GET['event_id']."
						ORDER BY start_date ASC";

		$events = $db->GetAll($sql);
		for($i=0; $i<count($events); $i++){
			$events[$i] = format_event_dates($events[$i]);
            if($events[$i]['description'] == '') $events[$i]['description']=$events[$i]['synopsis'];
            $events[$i]['description'] = rtrim($events[$i]['description']);
            $events[$i]['synopsis'] = rtrim($events[$i]['synopsis']);
		}


		if(count($events)>0){
			$tmpl->setAttribute("page_single", "visibility", "show");
			$tmpl->setAttribute("page_all", "visibility", "hidden");
			$tmpl->addRows("page_single", $events);
		}
		else{
			$tmpl->addVar("EVENTS","DESCRIPTION","Event not found.");
		}
	}
	else{
		// list all deadlines

		$sql = "SELECT * FROM events
                WHERE events.start_date >= ".time()."
						ORDER BY start_date ASC";

		$events = $db->GetAll($sql);

		if(count($events)>0){
			for($i=0; $i<count($events); $i++){
				$events[$i] = format_event_dates($events[$i]);
			}
			$tmpl->addRows("events", $events);
		}
		else{

            $tmpl->addVar("NOEVENTS","MESSAGE","No events in the near future. All the faculty are off doing research. Check back in the fall.");
		}

	}


	return $tmpl;
}

switch($_GET['section']){
	case "deadlines":
		$tmpl=deadlines();
		break;
	case "news":
		$tmpl=news();
		break;
	case "events":
		$tmpl=events();
		break;
	default:
	break;
}

$tmpl->displayParsedTemplate();

?>

