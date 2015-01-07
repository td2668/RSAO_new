<?php

include_once('includes/image-functions.php');


function projects_list() {
	global $db;

	$tmpl=loadPage("myactivities_projects_home","My Projects","my_projects");
	$username=sessionLoggedUser();
	$sql="SELECT * FROM users WHERE username = \"$username\"";
	$user=$db->GetRow($sql);

	if(is_array($user)==false or count($user)==0) {
			displayBlankPage("Error","<h1>Error</h1>Couldnt locate current user in the database.");
			die;
	}
	$user_id=$user["user_id"];

    /*
	if(isset($_POST['action'])) if($_POST["action"]=="update_project_list") {
		$sql="SELECT *
				FROM projects  as p
                LEFT JOIN projects_associated as pa ON(p.project_id=pa.project_id)
				WHERE pa.table_name='researchers'
                AND pa.object_id=$user_id ";

		$items=$db->getAll($sql);
		if($items)
		foreach($items as $item) {
			$project_id=$item["project_id"];
			if($_POST["item_{$cv_item_id}_cv"]=="checked" and $item["current_par"]==0
			or $_POST["item_{$cv_item_id}_cv"]!="checked" and $item["current_par"]==1
			or $_POST["item_{$cv_item_id}_profile"]=="checked" and $item["web_show"]==0
			or $_POST["item_{$cv_item_id}_profile"]!="checked" and $item["web_show"]==1) {
				//database and posted info are not the same, update needed
				$sql="UPDATE cv_items SET
								current_par=".($_POST["item_{$cv_item_id}_cv"]=="checked" ? 1 : 0).",
								web_show=".($_POST["item_{$cv_item_id}_profile"]=="checked" ? 1 : 0)."
								WHERE user_id=$user_id AND cv_item_id=$cv_item_id ";

				if($db->Execute($sql)==false) {
					displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
					die;
				}
			}

		}
	} // end update
*/





	$projdata=Array();
    $index=0;
	$odd_even="oddrow";
    $sql="SELECT *
                FROM projects  as p
                LEFT JOIN projects_associated as pa ON(p.project_id=pa.project_id)
                WHERE pa.table_name='researchers'
                AND pa.object_id=$user_id
                ORDER BY approved DESC, name ";

    $items=$db->getAll($sql);
   // echo("Found ".count($items));
	if($items) {
	    foreach($items as $item) {
		    $projdata[$index]["type"]=$odd_even;
		    if($odd_even=="oddrow")
			    $odd_even="evenrow";
		    else
			    $odd_even="oddrow";
            if($item['approved']) $projdata[$index]["name"]=$item['name'];
            else $projdata[$index]["name"]=$item['name']. ' (hidden)';
            $projdata[$index]["project_id"]=$item['project_id'];
            if(strlen($item['synopsis'])>100) $item['synopsis']=substr($item['synopsis'],0,98) . '...';
            $projdata[$index]['synopsis']=$item['synopsis'];
            if($item['modified']=='0000-00-00') $mod='Undefined';
            else $mod=date('M d Y',strtotime($item['modified']));
            $projdata[$index]['modified']=$mod;
	    $index++;	
	    }
    }
    else $projdata[$index]["type"]='empty';
    
    //var_dump($projdata);
	$tmpl->addRows("project_list",$projdata);
	return $tmpl;
}

function edit_project() {
    /* This routine can go several ways. 
    1 - save and continue
        called with action='update_research_item' and cv_item_id is set

    3 - just create new 
        called with action='add'  
    4 - delete item                           */
    

	global $db,$configInfo;
    
    // Get user info
	$tmpl=loadPage("myactivities_projects_edit","My Projects","my_projects");
	$username=sessionLoggedUser();
	$sql="SELECT * FROM users WHERE username = \"$username\"";
	$user=$db->GetRow($sql);

	if(is_array($user)==false or count($user)==0) {
			displayBlankPage("Error","<h1>Error</h1>Couldnt locate current user in the database.");
			die;
	}
	$user_id=$user["user_id"];

    //Process delete request
    if(!isset($_REQUEST['action'])) $_REQUEST['action']='';
    if($_REQUEST['action'] == "delete_project"){
        $delete_project_id = $_REQUEST['delete_project_id'];

        if($delete_project_id>0){
            $sql="UPDATE projects
                  SET approved = 0
                  WHERE project_id=$delete_project_id ";

            if($db->Execute($sql)==false) {
              displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
                die;
            }
            
            unset($items);
            unset($item);
            unset($fields);
        }

        header("location: /myactivities.php?section=my_projects");
    } // end delete
    
    //Process delete pictures request
    if($_REQUEST['action'] == "delete_picture"){
        $delete_picture_id = $_REQUEST['delete_picture_id'];
        //echo("Deleting $delete_picture_id");
        $sql="SELECT * FROM pictures WHERE picture_id=$delete_picture_id";
        $pic=$db->getRow($sql);
        $sql="DELETE from pictures WHERE picture_id=$delete_picture_id";
        if($db->Execute($sql)==false) {
            displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
            die;
        }
        $sql="DELETE from pictures_associated WHERE picture_id=$delete_picture_id";
        if($db->Execute($sql)==false) {
            displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
            die;
        }
        //echo("Unlinking ". $configInfo['picture_path'].$pic['file_name']);
        unlink($configInfo['picture_path'].$pic['file_name']);
        unlink($configInfo['picture_path']."thumb_".$pic['file_name']);
        
    }
    
    
    //First save 
    if($_REQUEST["action"]=="update_project" ) {
        //Make sure item to save is legit
        $project_id=intval($_POST["project_id"]);
        if($project_id==0)
            $project_id=intval($_GET["project_id"]);
        if($project_id==0)  {
            displayBlankPage("Error","<h1>Error</h1>Couldnt locate item record in the database.");
            die;
        } 
        $update_project_id = $_POST['update_project_id'];
              
        //do the update
        if(strtotime($_POST['end_date'])){
            $tmp_date = explode("/", $_POST['end_date']);
            $end_date = mktime(0,0,0,$tmp_date[1],$tmp_date[0],$tmp_date[2]);
        }
        else $end_date=0;
        $mod=date('Y-m-d');

    $boyerDiscovery = $_POST['boyer'] == 'boyerDiscovery' ? 1 : 0;
    $boyerIntegration = $_POST['boyer'] == 'boyerIntegration' ? 1 : 0;
    $boyerApplication = $_POST['boyer'] == 'boyerApplication' ? 1 : 0;
    $boyerTeaching = $_POST['boyer'] == 'boyerTeaching' ? 1 : 0;
    $boyerService = $_POST['boyer'] == 'boyerService' ? 1 : 0;

        $sql="UPDATE projects SET
              name='".mysql_real_escape_string($_POST['name'])."',
              synopsis='".mysql_real_escape_string($_POST['synopsis'])."',
              description='".mysql_real_escape_string($_POST['description'])."',
              keywords='".mysql_real_escape_string($_POST['keywords'])."',
              studentproj=".(isset($_POST["studentproj"]) ? 1 : 0).",
              student_names='".mysql_real_escape_string($_POST['student_names'])."',
              end_date=$end_date,
              approved=".(isset($_POST['approved']) ? 0 : 1) .",
              modified=NOW(),
              who_modified=$user_id,
    	      boyerDiscovery = $boyerDiscovery, 
    	      boyerIntegration = $boyerIntegration, 
    	      boyerApplication = $boyerApplication,
    	      boyerTeaching = $boyerTeaching,
   	      boyerService = $boyerService
              WHERE project_id=$update_project_id ";

        if($db->Execute($sql)==false) {
            displayBlankPage("Error","<h1>Error</h1>Couldn\'t update the database.<br />$sql");
            die;
        }

        // update the project collaborators in case they have changed
        updateProjectCollaborators($project_id, $user_id, $_POST['user_options']);

        //end of user_options save
        
        
        //image processing - New Picture
        if(is_uploaded_file($_FILES['uploadimage']['tmp_name'])) {
            
            $ext = explode(".", $_FILES['uploadimage']['name']);
            $file_name_noext = "picture".mktime();
            $file_name = $file_name_noext.".".$ext[1];
            //echo($configInfo['picture_path'].$file_name);
            copy($_FILES['uploadimage']['tmp_name'], $configInfo['picture_path'].$file_name);
            unlink($_FILES['uploadimage']['tmp_name']);
            
            //thumbnail
            resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path']."thumb_".$file_name);
        
            //now resize the image if neccessary. Need to check with a variety of images
            resizeImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 140);
            shadowImage($configInfo['picture_path'].$file_name, $configInfo['picture_path'].$file_name, 6, 87);
            
            $sql="INSERT INTO pictures
                 (caption,file_name,feature)
                 VALUES('".mysql_real_escape_string($_POST['caption'])."','$file_name',0)";
            if($db->Execute($sql)==false) {
                displayBlankPage("Error","<h1>Error</h1>Couldnt update the picture database.<br />$sql");
                die;
            }
            $picture_id=mysql_insert_id();
            
            //And save the relate
            
            $sql="INSERT INTO pictures_associated
                  (picture_id,object_id,table_name)
                  VALUES($picture_id,$project_id,'projects')";
            if($db->Execute($sql)==false) {
                displayBlankPage("Error","<h1>Error</h1>Couldnt update the picture-relate database.<br />$sql");
                die;
            }
            
       
        }//is upload image
        
        //Process incoming caption changes
        $sql="SELECT * FROM pictures
              LEFT JOIN pictures_associated as pa
              ON (pictures.picture_id=pa.picture_id)
              WHERE pa.table_name='projects'
              AND pa.object_id=$project_id
              ";
        $pics=$db->getAll($sql);
        if($pics){
            foreach($pics as $pic)
            if(isset($_POST['caption_'.$pic['picture_id']])){
                $sql="UPDATE pictures
                      SET caption='".mysql_real_escape_string($_POST['caption_'.$pic['picture_id']])."'
                      WHERE picture_id=$pic[picture_id]";
                if($db->Execute($sql)==false) {
                    displayBlankPage("Error","<h1>Error</h1>Couldnt update the caption in the database.<br />$sql");
                    die;
                }
            }
        }
        
        unset($items);
        unset($item);
        unset($fields);

        
    }//save section
 
	if($_REQUEST["action"]=="addnew_project" ) {
		//generate a new item and reload it
        //echo("Made it");
        $mod=mktime();
        $sql="INSERT INTO projects
             (name,approved,modified,who_modified)
             VALUES ('Untitled Project',1,NOW(),$user_id)";
        $db->Execute($sql);
        $project_id=$db->insert_id();
         $sql="INSERT INTO projects_associated 
               (`project_id`, `object_id`, `table_name`)
               VALUES($project_id, $user_id, 'researchers')";
                        
         if($db->Execute($sql)==false) {
            displayBlankPage("Error","<h1>Error</h1>Couldnt update the user list database.<br />$sql");
            die;
         }        
        header("location: /myactivities.php?section=my_projects&subsection=edititem&project_id=$project_id");
        
	} //new section
	

    // If we are simply updating, then reload the item
    if(isset($project_id) || isset($_REQUEST['project_id'])){
        if(!(isset($project_id))) $project_id=$_REQUEST['project_id'];
        $sql="SELECT     *
             FROM     projects as p
             LEFT JOIN projects_associated as pa 
             ON (p.project_id=pa.project_id)
            WHERE  p.project_id=$project_id";
        $items=$db->getAll($sql);
        $item=reset($items); 
        if($item['studentproj']) $item['studentproj']='checked'; else $item['studentproj']='';
        if($item['approved']) $item['approved']=''; else $item['approved']='checked';
        if($item['end_date']==0) $item['end_date']='';
        else $item['end_date']=date('d/m/Y',$item['end_date']);

        //Deal with quotes, etc
        $item['name']= htmlentities($item['name']);
        $item['keywords']= htmlentities($item['keywords']);
        $item['student_names']= htmlentities($item['student_names']);
        if($item['boyerDiscovery']) $item['boyerDiscovery']='checked'; else $item['boyerDiscovery']='';
        if($item['boyerIntegration']) $item['boyerIntegration']='checked'; else $item['boyerIntegration']='';
        if($item['boyerApplication']) $item['boyerApplication']='checked'; else $item['boyerApplication']='';
        if($item['boyerTeaching']) $item['boyerTeaching']='checked'; else $item['boyerTeaching']='';
        if($item['boyerService']) $item['boyerService']='checked'; else $item['boyerService']='';


        //Load the last modifier
        if($item['who_modified']!=0) {
            $sql="SELECT last_name,first_name FROM users where user_id=$user_id";
            $moduser=$db->getRow($sql);
            if($moduser) $item['who_modified']=  "$moduser[last_name], $moduser[first_name]";
            else $item['who_modified']='ORS'; 
        }
        else $item['who_modified']='ORS';
        if($item['modified']==0) $item['modified']='';
        else $item['modified']=date('d/m/Y',strtotime($item['modified']));

       //Load Users
        $sql="SELECT * FROM projects_associated
            WHERE project_id=$project_id
            AND object_id != $user_id
            AND table_name='researchers'";
        $people=$db->getAll($sql) ;
        require_once('includes/user_functions.php');
        $allusers = getUsers();
        if (is_array($people)) {
            foreach ($people as $person) $ids[] = $person['object_id'];
        }
        if (is_array($allusers)) {
            foreach ($allusers as $oneuser) {
                if (isset($ids) && in_array($oneuser['user_id'], $ids)) {
                    $user_options .= "<option selected value='$oneuser[user_id]'> $oneuser[last_name], $oneuser[first_name]</option>";
                }
                else {
                    $user_options .= "<option value='$oneuser[user_id]'>$oneuser[last_name], $oneuser[first_name]</option>";
                }
            }
        }
        //now the images
        $sql="SELECT * FROM pictures
              LEFT JOIN pictures_associated as pa
              ON (pictures.picture_id=pa.picture_id)
              WHERE pa.table_name='projects'
              AND pa.object_id=$project_id
              ";
              
        $pics=$db->getAll($sql);
        $imagerows=Array();
        if($pics){
            foreach($pics as $key=>$pic) {
                $imagerows[$key]['url']=$configInfo['picture_url'].$pic['file_name'];
                $imagerows[$key]['caption']=htmlentities($pic['caption']);
                $imagerows[$key]['capnum']=$pic['picture_id'];
            }
        }
        
           
        $tmpl->addRows("image_list",$imagerows);
        
              
    } //end reload the item
    
    
    //Default Actions
    if(!(is_array($item))) {
       displayBlankPage("Error","<h1>Error</h1>Couldn\'t locate item record in the database.");
       die;
    }
    
    $tmpl->addVars("page",$item);
    $tmpl->addVar("page",'user_options',$user_options);


//    $tmpl->addRows("research_item_fields",$fields);
    return $tmpl;

}

/**
 * Update the collaborators associated with a project
 *
 * @param $project_id - the project ID
 * @param $user_id - the user id that the project belongs to
 * @param $collaborators - the collaborators
 */
function updateProjectCollaborators($project_id, $user_id, $collaborators)
{
    global $db;

    // first - clear out the associated collaborators, if any.
    $sql = "DELETE FROM projects_associated
                WHERE project_id=$project_id AND table_name='researchers' AND object_id !=  $user_id";
    if ($db->Execute($sql) == false) {
        displayBlankPage("Error", "<h1>Error</h1>Couldnt update the user list database.<br />$sql");
        die;
    }

    if ($db->Execute($sql) == false) {
        displayBlankPage("Error", "<h1>Error</h1>Couldnt update the user list database.<br />$sql");
        die;
    }

    if (isset($collaborators)) {
        foreach ($collaborators as $index) {
            $sql = "SELECT * FROM projects_associated
                      WHERE project_id=$project_id AND object_id=$index AND table_name='researchers'";
            if (!$db->getAll($sql)) {
                if ($index != "") {
                    $sql = "INSERT INTO projects_associated
                        (`project_id`, `object_id`, `table_name`)
                        VALUES($project_id, $index, 'researchers')";

                    if ($db->Execute($sql) == false) {
                        displayBlankPage("Error", "<h1>Error</h1>Couldnt update the user list database.<br />$sql");
                        die;
                    }
                }
            }
        }
    }
}

function addnew_project() {
	global $db;

	$tmpl=loadPage("myactivities_projects_addnew","My Projects","my_projects");
	$username=sessionLoggedUser();
	$sql="SELECT * FROM users WHERE username = \"$username\"";
	$user=$db->GetRow($sql);

	if(is_array($user)==false or count($user)==0) {
			displayBlankPage("Error","<h1>Error</h1>Couldnt locate current user in the database.");
			die;
	}
	$user_id=$user["user_id"];



	if($_POST["action"]=="addnew_project") {

		$sql="INSERT INTO projects
			 (modified)
			 VALUES (NOW())";

		if($db->Execute($sql)==false) {
			displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
			die;
		}
        
		unset($_POST);
		$project_id=$db->insert_id();
        $sql="INSERT INTO projects_associated
        (project_id,object_id,table_name)
        VALUES($project_id,$user_id,'researchers')";
        if($db->Execute($sql)==false) {
            displayBlankPage("Error","<h1>Error</h1>Couldnt update the database.<br />$sql");
            die;
        }
        
		header("location: myactivities.php?section=my_projects&subsection=edititem&project_id=$project_id");
		echo " ";
		die();

	} // end update
	else {

		$sql="SELECT cv_item_type_id,title
				 FROM 	cv_item_types ";
		$types=$db->getAll($sql);

		$tmpl->addRows("research_item_types",$types);
	}
	return $tmpl;
}

/**
 * This function adds a project to the database when given a subset of the project fields
 * Used for quick-adding a project when editing cv items.
 *
 * This should be called from an AJAX request, therefore we return a JSON response rather than render a page.
 */
function quickSaveProject()
{
    global $db;

    $username = sessionLoggedUser();
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);

    if (is_array($user) == false or count($user) == 0) {
        $response = array(
            'type' => 'Error',
            'msg'  => 'Couldn\'t locate current user in the database.'
        );
        echo json_encode($response);
        return;
    }
    $user_id = $user["user_id"];

    $name = isset($_POST['name']) ? mysql_escape_string($_POST['name']) : '';
    $approved = isset($_POST['approved']) ? 0 : 1;
    $synopsis = isset($_POST['synopsis']) ? mysql_escape_string($_POST['synopsis']) : '';
    $description = isset($_POST['description']) ? mysql_escape_string($_POST['description']) : '';
    $keywords = isset($_POST['keywords']) ? mysql_escape_string($_POST['keywords']) : '';
    $studentproj = isset($_POST['studentproj']) ? 1 : 0;
    $studentNames = isset($_POST['student_names']) ? mysql_escape_string($_POST['student_names']) : '';
    $endDate = $_POST['end_date'] != '' ? strtotime ($_POST['end_date']) : 0;
    $boyerDiscovery = $_POST['boyer'] == 'boyerDiscovery' ? 1 : 0;
    $boyerIntegration = $_POST['boyer'] == 'boyerIntegration' ? 1 : 0;
    $boyerApplication = $_POST['boyer'] == 'boyerApplication' ? 1 : 0; 
    $boyerTeaching = $_POST['boyer'] == 'boyerTeaching' ? 1 : 0; 
    $boyerService = $_POST['boyer'] == 'boyerService' ? 1 : 0; 

    $sql = "INSERT INTO projects
          (name, approved, synopsis, description, modified, keywords, studentproj, student_names, end_date,
                 boyerDiscovery, boyerIntegration, boyerApplication, boyerTeaching, boyerService)
           VALUES ('$name', $approved, '$synopsis', '$description', NOW(), '$keywords', $studentproj, '$studentNames',
                   $endDate, $boyerDiscovery, $boyerIntegration, $boyerApplication, $boyerTeaching, $boyerService)";

    if ($db->Execute($sql) == false) {
        $response = array(
            'type' => 'Error',
            'msg'  => 'Couldn\'t update the database'
        );
        echo json_encode($response);
        return;
    }

    $project_id = $db->insert_id();

    $sql = "INSERT INTO projects_associated
            (project_id, object_id, table_name)
            VALUES($project_id, $user_id, 'researchers')";

    if ($db->Execute($sql) == false) {
        $response = array(
            'type' => 'Error',
            'msg'  => 'Couldn\'t update the projects_associated database'
        );
        echo json_encode($response);
        return;
    }

    $response = array(
        'type' => 'Success',
        'msg'  => 'Project added successfully',
        'projectId' => $project_id,
        'projectName' => $name
    );
    echo json_encode($response);
}



function my_projects() {

	$subsection=(isset($_REQUEST['subsection'])) ? $_REQUEST["subsection"] : '';
	switch ($subsection) {
		case "edititem":
            $tmpl=edit_project();
			break;
		//case "addnew":
			//$tmpl=addnew_research_item();
			//break;
		case "save_visibility":
			save_visibility();
		//so we don't get an error when we try to display nothing
			$tmpl=new patTemplate();
			$tmpl->setRoot('html');
			$tmpl->readTemplatesFromInput("blanktemplate.html");
			break;
		case "":
		default:
			$tmpl=projects_list();
			break;
	}

	return $tmpl;
}
?>
