<?php

function readProfile($username) {
	global $db;
	$sql="SELECT * FROM users WHERE username = \"$username\"";
	$user=$db->GetRow($sql);

	if(is_array($user)==false or count($user)==0) {
		$sql="INSERT INTO users (username) VALUES (\"$username\")";
		if($db->Execute($sql)===false) {
			displayBlankPage("Error","<h1>Error</h1>There was a database error creating your initial user record.");
			die;
		}
		$user=Array();
		$user["user_id"]=$db->Insert_ID();
	}

	$user_id=$user["user_id"];
	$sql="SELECT * FROM profiles WHERE user_id = $user_id";
	$profile=$db->GetRow($sql);

	if(is_array($profile)==false or count($profile)==0) {
		$sql="INSERT INTO profiles (user_id) VALUES ($user_id)";
		if($db->Execute($sql)===false) {
			displayBlankPage("Error","<h1>Error</h1>There was a database error creating your initial profile record.");
			die;
		}
		$profile=array();
	}

	$profile=(array) array_merge($profile,$user);
	return $profile;
}

function my_profile() {
    global $configInfo;
	global $db;


	$tmpl=loadPage("myactivities_profile","Edit profile","my_profile");
	$username=sessionLoggedUser();
	$profile=readProfile($username); //first call readProfile to get the user_id and be sure the records do exist
	$user_id=$profile["user_id"];
	if(isset($_POST['action'])) if($_POST["action"]=="update") {
		$validUsersFields=array (
		  'first_name' => 'First name',
		  'last_name' => 'Last name',
		  'department_id' => 'Last name',
		  'department2_id' => 'Last name' );
		$validProfileFields=array (
		  'email' => 'email',
		  'title' => 'job title',
		  'secondary_title' => 'secondary title',
		  'office' => 'office location',
		  'phone' => 'phone number',
		  'fax' => 'fax number',
		  'homepage' => 'personal webpage',
		  'keywords' => 'keywords',
		  'profile_ext' => 'full profile statement (will be used when viewing your detailed profile)',
		  'profile_short' => 'short profile statement (will be used in the listings of people)');

		//'home_dept' => 'home department',

		$arrFields=array("user_id"=>$user_id);
		foreach($validUsersFields as $field => $label) {
			$arrFields[$field]=$_POST[$field];
		}
        $arrFields['date']=mktime();
        
        
		$db->Replace("users",$arrFields,"user_id",true);


		// user_level=0 => visible, user_level=1 => hidden
		if($_POST['show_checkbox'] && $_POST['show_checkbox']=="on")
			$user_level = 0;
		else
			$user_level = 1;
        $db->Replace("users", array('user_id'=>$user_id, 'user_level'=>$user_level), "user_id", true);
        
        if($_POST['events_checkbox'] && $_POST['events_checkbox']=="on")
            $mail_events = 1;
        else
            $mail_events = 0;
        $db->Replace("users", array('user_id'=>$user_id, 'mail_events'=>$mail_events), "user_id", true);
            
        if($_POST['deadlines_checkbox'] && $_POST['deadlines_checkbox']=="on")
            $mail_deadlines = 1;
        else
            $mail_deadlines = 0;   
        $db->Replace("users", array('user_id'=>$user_id, 'mail_deadlines'=>$mail_deadlines), "user_id", true); 
        

		



		$arrFields=array("user_id"=>$user_id);
		foreach($validProfileFields as $field => $label) {
			$arrFields[$field]=$_POST[$field];
		}
        
        //Do some replacements to keep quotes etc from turning into little boxes in IE6
        $search=array(  '/&#8216;/',        # 0x2018 Left single quotation mark
                        '/&#8217;/',        # 0x2019 Right single quotation mark
                        '/&#8218;/',        # 0x201A Single low-9 quotation mark
                        '/&#8219;/',        # 0x201B Single high-reversed-9 quotation mark
                        '/&#8220;/',        # 0x201C Left double quotation mark
                        '/&#8221;/',        # 0x201D Right double quotation mark
                        '/&#8208;/',       # 0x2010 Hyphen
                        '/&#8209;/',        # 0x2011 Non-breaking hyphen
                        '/&#8211;/',       # 0x2013 En dash
                        '/&#8212;/',       # 0x2014 Em dash
                        '/&#8213;/',       # 0x2015 Horizontal bar/quotation dash
                        '/&#8214;/',       # 0x2016 Double vertical line

                        
                        '/&#8222;/',        # 0x201E Double low-9 quotation mark
                        '/&#8223;/',        # 0x201F Double high-reversed-9 quotation mark
                        '/&#8226;/',      # 0x2022 Bullet
                        '/&#8227;/',      # 0x2023 Triangular bullet
                        '/&#8228;/',      # 0x2024 One dot leader
                        '/&#8229;/',      # 0x2026 Two dot leader
                        '/&#8230;/',      # 0x2026 Horizontal ellipsis
                        '/&#8231;/',         # 0x2027 Hyphenation point
                        '/\x91/',
                        '/\x92/',
                        '/\x93/',
                        '/\x94/',
                        '/\x95/',
                        '/\x96/',
                        '/\x97/'     
                        );
        $replace=array( "'",        # 0x2018 Left single quotation mark
                        "'",        # 0x2019 Right single quotation mark
                        ',',        # 0x201A Single low-9 quotation mark
                        "'",        # 0x201B Single high-reversed-9 quotation mark
                        '"',        # 0x201C Left double quotation mark
                        '"',        # 0x201D Right double quotation mark
                        '-',        # 0x2010 Hyphen
                        '-',        # 0x2011 Non-breaking hyphen
                        '--',       # 0x2013 En dash
                        '--',       # 0x2014 Em dash
                        '--',       # 0x2015 Horizontal bar/quotation dash
                        '||',       # 0x2016 Double vertical line

                        
                        ',,',        # 0x201E Double low-9 quotation mark
                        '"',        # 0x201F Double high-reversed-9 quotation mark
                        '&#183;',      # 0x2022 Bullet
                        '&#183;',      # 0x2023 Triangular bullet
                        '&#183;',      # 0x2024 One dot leader
                        '..',      # 0x2026 Two dot leader
                        '...',      # 0x2026 Horizontal ellipsis
                        '&#183;',   # 0x2027 Hyphenation point
                        "'",
                        "'",
                        '"',
                        '"',
                        '*',
                        '-',
                        '--'      
                        );
                        
        
        $arrFields['profile_ext']=stripslashes(preg_replace($search,$replace,$arrFields['profile_ext']));
        $arrFields['profile_short']=stripslashes(preg_replace($search,$replace,$arrFields['profile_short']));
                        
                        
		$db->Replace("profiles",$arrFields,"user_id",true);

		//Delete old Topic associations to store new selection
		// Old way
		/*$sql="DELETE FROM researchers_associated
					WHERE researcher_id = $user_id
					  AND table_name    = 'topics_research'";
		*/
		$sql = "DELETE FROM user_topics_profile ";
		$sql .= "WHERE user_id=".$user_id;
		//print "<br />Here::$sql::ereH<br />";
		$db->Execute($sql);
		
		// Add new topics
		if($_POST["topics"])
		foreach($_POST["topics"] as $topic) {
			// Old way
			/*$db->Replace("researchers_associated",
					array(	"researcher_id"=>$user_id,
							"table_name"=>"topics_research",
							"object_id"=>$topic),
					"associated_id",true);
			*/
			$db->Replace("user_topics_profile",
					array(	"user_id"=>$user_id,         
							"topic_id"=>$topic),
					"user_topics_profile_id",true);
            //echo("Inserting $topic<br>  ");
		}

		// Delete old Filter associations to store new selections
		$sql = "DELETE FROM user_topics_filter ";
		$sql .= "WHERE user_id=".$user_id;
		$db->Execute($sql);
		
		// Add new filters
		if($_POST["filters"])
		foreach($_POST["filters"] as $filter) {
			$db->Replace("user_topics_filter",
					array(	"user_id"=>$user_id,
							"topic_id"=>$filter),
					"user_topics_filter_id",true);
		}

	}
	/*
	else if($_POST['action']="toggle_visibility"){
		$sql = "UPDATE users SET user_level='";
		if($profile['user_level']==0)
			$sql .= "1";
		else
			$sql .= "0";
		$sql .= "' WHERE username='$username';";	
		$db->Execute($sql);
	}
	*/



	$profile=readProfile($username); // Re-read users profile from database

	$tmpl->addVar("profile_visibility", "checked", $profile['user_level']);
    $tmpl->addVar("mail_events", "checked", $profile['mail_events']);
    $tmpl->addVar("mail_deadlines", "checked", $profile['mail_deadlines']);

    //Get Picture
    $sql=" SELECT pictures.file_name,pictures.picture_id
                 FROM pictures,pictures_associated
                WHERE pictures_associated.table_name=\"users\"
                  AND object_id=$profile[user_id]
                  AND pictures_associated.picture_id=pictures.picture_id
                  AND pictures.feature=FALSE
                  ORDER BY RAND()
                  LIMIT 1";
        
        $pictures=$db->GetAll($sql);
        $picture=reset($pictures);
        if($picture){
            $img_url=$configInfo['picture_url']."$picture[file_name]";
            $img_text="Your web picture";
        }  
        else {
            $img_url="images/your-picture-here.gif";
            $img_text="No picture associated";
            
        }
        
            
        $tmpl->addVar("PAGE","img_url","$img_url"); 
        $tmpl->addVar("PAGE","img_text","$img_text");

        $profile['profile_ext']=stripslashes($profile['profile_ext']);
        $profile['profile_short']=stripslashes($profile['profile_short']);
        
	$tmpl->addVars("PAGE",$profile);

	/* GET ALL TOPICS */
	$sql="SELECT topic_id,name FROM topics_research";
	$topics=$db->CacheGetAll(180,$sql);  // cACHE 3 MINUTES ONLY
	if(is_array($topics))
		foreach($topics as $key=>$topic)
			$topic_ids[$key]=$topic["topic_id"];
	else $topic_ids=array();
    
	
	// Attempt at filters: -- seems to be working!!
	$sql="SELECT topic_id,name FROM topics_research";
	$filters=$db->CacheGetAll(180,$sql);  // cACHE 3 MINUTES ONLY
	if(is_array($filters))
		foreach($filters as $key=>$filter)
			$filter_ids[$key]=$filter["topic_id"];
	else $filter_ids=array();

	/* GET TOPICS ASSOCIATED TO THIS RESEARCHER */
	// Old way
	/*$sql="SELECT topic_id FROM topics_research,researchers_associated
		WHERE  researchers_associated.researcher_id = $user_id
		  AND  researchers_associated.table_name    = 'topics_research'
		  AND  researchers_associated.object_id     = topics_research.topic_id
		GROUP BY topic_id";
	*/
	$sql = "SELECT topic_id FROM user_topics_profile ";
	$sql .= "WHERE user_id='".$user_id."'";
	$mytopics=$db->GetAll($sql);

	if(is_array($mytopics))
		foreach($mytopics as $mytopic) {
			//$topic=reset($mytopic);
			$topic_id=$mytopic["topic_id"];
			if( ($key=array_search($topic_id,$topic_ids))!==false)
				$topics[$key]["topic_checked"]="checked";
		}

	$tmpl->addRows("TOPICLIST",$topics);
	
	// Attempt at filters: -- seems to be working, with checked also
	$sql = "SELECT topic_id FROM user_topics_filter ";
	$sql .= "WHERE user_id='".$user_id."'";
	$myfilters=$db->GetAll($sql);

	if(is_array($myfilters))
		foreach($myfilters as $myfilter) {
			//$filter=reset($myfilter);
			$filter_id=$myfilter["topic_id"];
			if( ($key=array_search($filter_id,$filter_ids))!==false)
				$filters[$key]["filter_checked"]="checked";
		}

	$tmpl->addRows("FILTERLIST",$filters);

	/* GET DEPARTMENTS THIS RESEARCHER HAD SELECTED */

	$sql="SELECT department_id,name FROM departments ORDER BY name";
	$departments=$db->GetAll($sql);

	$dep1=-1;
	$dep2=-1;
	if($departments) {
		foreach($departments as $key=>$dep) {
			//$home_dept[]["department_id"]=$dep["department_id"];
			$departments[$key]["department_name"]=$dep["name"];
			if( $profile["department_id"]==$dep["department_id"])
				$dep1=$key;
			if( $profile["department2_id"]==$dep["department_id"])
				$dep2=$key;
		}
		if($dep1 != -1)	$departments[$dep1]["department_selected"]="selected";
		$tmpl->addRows("home_dept",$departments);
		if($dep1 != -1)	$departments[$dep1]["department_selected"]="";

		if($dep2 != -1)	$departments[$dep2]["department_selected"]="selected";
		$tmpl->addRows("second_dept",$departments);
		if($dep2 != -1)	$departments[$dep2]["department_selected"]="";
	}

	return $tmpl;
}


?>
