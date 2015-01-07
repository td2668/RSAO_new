<?php
    //Enter, edit, view user log information
    include("includes/config.inc.php");
    include("includes/functions-required.php");
    include("includes/class-template.php");
    
    
   // $template = new Template;
    $hdr=loadPage("header",'Header');
    
    $menuitems=array();
    $menuitems[]=array('title'=>'Add Entry','url'=>'userlog.php?section=add');
    $menuitems[]=array('title'=>'Add Category','url'=>'userlog.php?section=addcategory');
    
    $hdr->AddRows("list",$menuitems);
    
    //categories in menu. 
    $hdr->setAttribute("extras","visibility","visible");
    $sql="SELECT * FROM users_log_categories ORDER BY name";
    $cats=$db->GetAll($sql);
    foreach($cats as $key=>$cat){
        $cats[$key]['url']="userlog.php?section=viewcat&cat=$cat[name]";
    }
    $cats[]=array('url'=>"userlog.php?section=viewcat&cat=All",'name'=>'All');
    $hdr->AddRows("extras",$cats);
    
    
    $tmpl=loadPage("userlog", 'User Log');
    
    if(isset($_REQUEST['success'])) $success=$_REQUEST['success'];
    
    //Actions Section
    if(isset($_REQUEST['add'])){
        if(isset($_REQUEST['user_id']) && isset($_REQUEST['category'])) {
            $contents=(isset($_REQUEST['contents'])) ? mysql_escape_string($_REQUEST['contents']) : '';
            //date parse
            $mysqldate = isset($_REQUEST['date']) ? ($_REQUEST['date']!='' ? $_REQUEST['date'] : date('Y-m-d')) : date('Y-m-d');
            $sql=   "INSERT into users_log (user_id,log_id,category,date,contents) VALUES(
                    $_REQUEST[user_id],
                    null,
                    '$_REQUEST[category]',
                    '$mysqldate',
                    '$contents')";
            if(!$db->Execute($sql)) $success.=" Error Inserting ";
            else $success="Inserted";
        }
        else $success.=" Missing person or category";
        
        //If 'letter' exists then the request to add came from the users screen and so we need to return there pronto
        if(isset($_REQUEST['letter'])) header("Location: users.php?section=view&letter=$_REQUEST[letter]");
    }
    
    if(isset($_REQUEST['update'])){
        if(isset($_REQUEST['user_id']) && isset($_REQUEST['category'])) {
            $contents=(isset($_REQUEST['contents'])) ? mysql_escape_string($_REQUEST['contents']) : '';
            //date parse
            $mysqldate = isset($_REQUEST['date']) ? ($_REQUEST['date']!='' ? $_REQUEST['date'] : date('Y-m-d')) : date('Y-m-d');
            $sql=   "	UPDATE users_log SET
            			user_id=$_REQUEST[user_id],
            			category='$_REQUEST[category]',
            			date='$mysqldate',
            			contents='$contents'
            			WHERE log_id=$_REQUEST[log_id]";
            if(!$db->Execute($sql)) $success.=" Error Updating ";
            else $success="Updated";
        }
        else $success.=" Missing person or category";
        
        $_REQUEST['section']='viewcat';
        $_REQUEST['cat']=$_REQUEST['category'];
    }
    
    if(isset($_REQUEST['delete'])){
    	if(isset($_REQUEST['log_id'])){
    		$sql="DELETE from users_log WHERE log_id=$_REQUEST[log_id]";
    		if(!$db->Execute($sql)) $success.=" Error Deleting ";
            else $success="Deleted";
            
            $_REQUEST['section']='viewcat';
        	$_REQUEST['cat']=$_REQUEST['category'];
            
            
    	}
    }
    
    if(isset($_REQUEST['addcat'])){
        if(isset($_REQUEST['category'])) {
            $sql="INSERT INTO users_log_categories (name) VALUES('$_REQUEST[category]')";
            if(!$db->Execute($sql)) $success.=" Error Inserting ";
            else $success="Added";
            //Have to reload the page to get the new one in the list.
            header('Location: userlog.php?success=Added');
        }
    }
    
    //Template Sections
    if (!isset($_REQUEST['section'])) $_REQUEST['section']='add';

    if(!isset($success)) $success="";
    
    switch($_REQUEST['section']){
        case "add":
            $hdr->AddVar("header","title","User Log: Add");
            $tmpl->setAttribute("add","visibility","visible");
            //deal with incoming data from user list request
            $values=array();
            if(isset($_REQUEST['fac_only'])) $values['fac_only']="fac_only"; else $values['fac_only']='';
            if(isset($_REQUEST['letter'])) {
                $values['letter']="$_REQUEST[letter]";
                
            }
            else $values['letter']='';
            $tmpl->AddVars("add",$values);
            
            $sql="SELECT user_id,last_name,first_name FROM users WHERE 1 ORDER BY last_name,first_name";
            $users=$db->GetAll($sql);
            if(isset($_REQUEST['user_id'])){
                $result=search_md_array($_REQUEST['user_id'],$users,'user_id');
                if($result!==false) $users[$result]['selected']="selected='selected'" ;   
            }
            $tmpl->AddRows("namelist",$users);
            if(isset($_REQUEST['category'])){
                $result=search_md_array($_REQUEST['category'],$cats,'name');
                if($result!==false) $cats[$result]['selected']="selected='selected'";   
            }
            $tmpl->AddRows("category",$cats);
        break;
        
        case "edit":
        	if(isset($_REQUEST['log_id'])) {
        		$hdr->AddVar("header","title","User Log: Edit");
            	$tmpl->setAttribute("edit","visibility","visible");
        		$sql="SELECT * from users_log WHERE log_id=$_REQUEST[log_id]";
        		$log=$db->GetRow($sql);
        		if(empty($log)) $success='Record Not Found';
        		else {
        			$sql="SELECT user_id,last_name,first_name FROM users WHERE 1 ORDER BY last_name,first_name";
		            $users=$db->GetAll($sql);
		            $result=search_md_array($log['user_id'],$users,'user_id');
		            if($result!==false) $users[$result]['selected']="selected='selected'" ;   
		            
		            $tmpl->AddRows("nameliste",$users);
		            
		            $result=search_md_array($log['category'],$cats,'name');
		            if($result!==false) $cats[$result]['selected']="selected='selected'";   
		            
		            $tmpl->AddRows("categorye",$cats);
        			
        			$tmpl->AddVars('edit',$log);
        		}
        	}
        	else {
        		//Error - no ID
        	}
            
        break;
        
        case "viewcat":
        	
        	if(isset($_REQUEST['cat'])){
        		$hdr->AddVar("header","title","User Log: View Category: $_REQUEST[cat]");
                if($_REQUEST['cat']=='All') $tmpl->AddVar("viewcat","cat","All");
            	$tmpl->setAttribute("viewcat","visibility","visible");
            	$sortby=(isset($_REQUEST['sortby'])) ? $_REQUEST['sortby'] : 'date';
            	if(isset($_REQUEST['dir'])) $dir=$_REQUEST['dir'];
            	if($sortby=='date' && !isset($dir)) $dir='desc'; 
            	if($sortby=='name' && !isset($dir)) $dir='asc'; 
            	$altdir=($dir=='asc') ? 'desc' : 'asc';
                //secondary sort needed too
                if($sortby=='date') $sortby2='name asc';
                else $sortby2='date desc';
            	$tmpl->AddVar("viewcat","dateurl","userlog.php?section=viewcat&cat=$_REQUEST[cat]&sortby=date&dir=$altdir");
            	$tmpl->AddVar("viewcat","nameurl","userlog.php?section=viewcat&cat=$_REQUEST[cat]&sortby=name&dir=$altdir");
        		if($_REQUEST['cat']=='All') $catwhere="1"; else $catwhere="category='$_REQUEST[cat]'";
                //if($_REQUEST['cat']=='All') $sortby="category, $sortby"; 
        		$sql="SELECT users_log.category, users_log.date, users_log.log_id,
        		IF(CHAR_LENGTH(users_log.contents) > 80, CONCAT(SUBSTRING(users_log.contents,1,80),'...'),users_log.contents)  as contents, CONCAT(users.last_name,', ',users.first_name) as name FROM users_log LEFT JOIN users ON(users_log.user_id=users.user_id) WHERE $catwhere ORDER BY $sortby $dir, $sortby2";
        		$list=$db->GetAll($sql);
        		if(count($list)>0){
        			$tmpl->AddRows("catlist",$list);
        		}
        		else {
        			//no entries
        		}
        	}
        break;
        
        case "addcategory":
            $hdr->AddVar("header","title","User Log: Add Category");
            $tmpl->setAttribute("addcat","visibility","visible");
    
        break;
    
    }
    if($success !='') $hdr->AddVar("success","success",$success);
    $hdr->displayParsedTemplate('header');
    $tmpl->displayParsedTemplate('page');
    
    include("templates/template-footer.html");
    

    /**
    * Searches a multi-dimensional array such as that returned from a $db->GetAll() function
    * Returns the key of the upper level 
    * Note: must explicitly test for identical with !== false on return or the zero array element is ignored
    * 
    * @param mixed $needle
    * @param mixed $haystack  Multidimensional array
    * @param mixed $subkey  field in the sub array to search
    */
    function search_md_array($needle,$haystack,$subkey){
        $result=array_search($needle,$haystack);
        if($result !== false) return $result;  
        foreach($haystack as $key=>$element) {
            if(is_array($element)) { 
                $result=search_md_array($needle,$element,$subkey);
                if($result==$subkey) return $key;
                
            }
        }
        return false;   
    }
?>
