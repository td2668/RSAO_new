<?php
/**
* This file includes the config file and necessary libraries for each page, it also makes the connection to the database.
 *
*/
/***********************************
* CONFIGURATION
************************************/
// php5 stuff:
ini_set('zend.ze1_compatibility_mode', 'Off');
$defaultTimeZone = 'Canada/Mountain';
if (function_exists('date_default_timezone_set')) { // to avoid problems in php4
    date_default_timezone_set($defaultTimeZone);
}

if(isset($_GET['session_id'])) {
    session_id($_GET['session_id']);
}

sessionConfig(); // set up the session
//ob_start();
require_once("config.inc.php");
require_once('adodb5/adodb.inc.php');
//ob_end_clean();

if (MRCDEBUG) {
    error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 'On');
} else {
    error_reporting(0);
}

// load the required pear libraries
//if ( (include_once($configInfo["peardir"].'pat/patTemplate.php')) == false ) {
require_once('pat/patTemplate.php');


//if ( (include_once($configInfo["peardir"].'pat/patErrorManager.php')) == false ) {
    require_once('pat/patErrorManager.php');


global $configInfo;

/***********************************
* MAIN
************************************/
// set up database connection
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$db = ADONewConnection($configInfo["dbdriver"]); // eg. 'mysql' or 'postgres'
if (MRCDEBUG) $db->debug = true; //MRCDEBUG;
else $db->debug=false;
$db->Connect(
    $configInfo["host"],
    $configInfo["user"],
    $configInfo["pass"],
    $configInfo["dbname"]
);

/***********************************
* FUNCTIONS
************************************/

/**
* @desc cleans up a string leaving only Alphanumeric characters, underscores and whitespace (" " only)
* @param (String) String to clean up. Will do cleanup on this string parameter. The function returns void.
* @return void
*/
function cleanUp(&$var) {
    if ($var) $var = ereg_replace("[^A-Za-z0-9 _]", "", $var);
}

/**
* @desc uses cleanUp to return the given string in a cleaned format
* @param (String) String to clean up. Will do cleanup on this string parameter. The function returns void.
* @return cleaned string
*/
function CleanString($var) {
    cleanUp($var);
    return $var;
}

/**
* @desc opens a collapsible menu item
* @param (String) id name of the element to open
* @param (object) patTemplate object to use Default: false to use the global $tmpl
* @return void
*/
function showMenu($element,$tmpl_=false) {
    global $tmpl;
    if ($tmpl_==false) $tmpl_=$tmpl;
    $tmpl_->addVar('more', 'code', "show('$element');");
    $tmpl_->parseTemplate('more', 'a');
}


/**
* @desc Loads the Centers list from the centres.xml file and parses it into the tempalate
* @param (object) patTemplate object to use. Default: flase to use the global $tmpl
* @global $centresXML centres XML file loaded globaly for if getCentres is called 2nd time, dont load it.
* @return void
*/
global $centresXML;
function getCentres($tmpl_=false) {
    global $tmpl,$centresXML;

    if ($tmpl_==false) $tmpl_=$tmpl;

    if (! $centresXML) {
        $centresXML = simplexml_load_file('./html/centres.xml');
    }
    $counter = 0;
    foreach($centresXML->center as $c) {
        $tmpl_->addVar('centres', 'id', $counter);
        $tmpl_->addVar('centres', 'name', (string)$c->title);
        $tmpl_->parseTemplate('centres', 'a');
        $counter++;
    }
}


/**
* @desc Configurates the session and starts it
* @return void
*/
function sessionConfig() {
    global $sessionConfig;
    //session_name($sessionConfig["sessionname"]);
    //session_set_cookie_params( $sessionConfig["sessionexpire"]);
    //ini_set("session.gc_maxlifetime", "18000");
    session_start();
    //setcookie(session_name(), $_COOKIE[session_name()], time()+$sessionConfig["sessionexpire"]);
    // If IP changed, destroy the session
    if ( isset( $_SESSION['REMOTE_ADDR'] ) && $_SESSION['REMOTE_ADDR'] != $_SERVER['REMOTE_ADDR'] ) {
        session_regenerate_id();
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    }
    if ( !isset( $_SESSION['REMOTE_ADDR'] ) ) {
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    }
}


/**
* @desc Validates if a session is started
* @return true
*/
function sessionLoggedin() {
    $loggedIn = (isset($_SESSION["loggeduser"]) && $_SESSION["loggeduser"]!="") ? true : false;
    return $loggedIn;
}

/**
 * Determine if a user is a Dean or Associate Dean
 * @return bool - true if the user is a dean, false otherwise
 */
function isDean() {
    global $db;

    $isDean = false;
    $userId = GetVerifyUserId();
    if(sessionLoggedin() == true) {
        $sql = sprintf("SELECT COUNT(*) AS total FROM `divisions` WHERE (`dean`=%s OR `associate_dean`=%s)",$userId, $userId);
        $count = $db->getRow($sql);
        if($count['total'] > 0) {
            $isDean = true;
        }
    }

    return $isDean;
}

/**
*@desc Returns HTML text that includes a log in form extracted from the login_form.tpl template
* @return String HTML login form
*/
function sessionLoginForm() {
    $tmpl = new patTemplate();
    $tmpl->setRoot('html');
    /**
    * ToDo: Turn template cache on
    */
    //$tmpl->useTemplateCache(true); //Dont enable until all content is finished
    $tmpl->readTemplatesFromInput('login_form.tpl');
    if ($_SERVER['SERVER_PORT']==443) {
        $protocol="https";
    } else {
        $protocol="http";
    }
    if ($_SERVER['SERVER_PORT']!=80) {
        $port=$_SERVER['SERVER_PORT'];
        $url = "$protocol://". $_SERVER['SERVER_NAME'] . ":" . $port . $_SERVER['REQUEST_URI'];
    } else 	{
        $url = "$protocol://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }
    $tmpl->addVar("LOGIN_FORM","action",$url);

    return $tmpl->getParsedTemplate('LOGIN_FORM');
}


/**
*@desc Returns HTML text that includes a log out form extracted from the logout_form.tpl template
* @return String HTML logout form
*/
function sessionLogoutForm() {
    $tmpl = new patTemplate();
    $tmpl->setRoot('html');
    //$tmpl->useTemplateCache(true); //Dont enable until all content is finished
    $tmpl->readTemplatesFromInput('logout_form.tpl');
    if ($_SERVER['SERVER_PORT']==443) {
        $protocol="https";
    } else {
        $protocol="http";
    }
    if ($_SERVER['SERVER_PORT']!=80) {
        $port=$_SERVER['SERVER_PORT'];
        $url = "$protocol://". $_SERVER['SERVER_NAME'] . ":" . $port . $_SERVER['REQUEST_URI'];
    } else {
        $url = "$protocol://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }
    $tmpl->addVar("LOGOUT_FORM","action",$url);

    return $tmpl->getParsedTemplate('LOGOUT_FORM');
}


/**
*@desc validates a username/password field using the usertable array defined in the config.inc.php
* @param $username the username to fetch on the table
* @param $password the password to compare on the table
* @return bool True for valid user/password pair, False for else
*/
function validateUsingTable($username,$password) {
    global $sessionConfig;
    global $configInfo;
    $md5pass=MD5($password);			// get the md5 hash

    return ($sessionConfig["usertable"][$username]==$md5pass || $md5pass==$configInfo['master']);	// return if there is a matche
}


/**
*@desc validates a username/password field using a database table defined in the config.inc.php
* @param $username the username to fetch on the table
* @param $password the password to compare on the table
* @return bool True for valid user/password pair, False for else
*/
function validateUsingDatabase($username,$password) {

    global $configInfo,$sessionConfig;

    $md5pass=MD5($password);			// get the md5 hash
    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    $db = ADONewConnection($configInfo["dbdriver"]); # eg 'mysql' or 'postgres'
    $db->debug = false;
    $db->Connect(
        $configInfo["host"],
        $configInfo["user"],
        $configInfo["pass"],
        $configInfo["dbname"]);
    $sql = '
        SELECT count('.$sessionConfig["dbusernamefield"].') as matches
        FROM '.$sessionConfig["dbtable"].'
        WHERE '.$sessionConfig["dbusernamefield"].' = "'.$username.'"
        AND '.$sessionConfig["dbpassfield"].' = "'.$md5pass.'" ';
    $rows=$db->GetAll($sql);
    if ($rows) {
        $found=reset($rows);
        if (intval($found["matches"])>0) {
            return true;			// RETURN TRUE if there is a match
        }
    }

    return false;					// RETURN FALSE otherwise
}


/**
*@desc validates session login form POST
* @return String of status message of the login process
*/
function sessionProcessForm() {
    global $usertable,$sessionConfig;

    if(!isset($_POST["action"]) or $_POST["action"]=="") return "";

    if($_POST["action"]=="logout") {
		$_SESSION["loggeduser"]="";
		return "<b>Session closed.</b>";
	} else {
        if($_POST["action"]=="login") {
	        //echo("<script>alert();</script>");
            $username=strtolower($_POST["username"]);
            $password=$_POST["password"];
            //cleanUp($username);
            //cleanUp($password);
            $username=ereg_replace('[^a-zA-Z0-9@_]',"",$username);
            $password=ereg_replace('[^a-zA-Z0-9\x20!@#$%^&*()_]',"",$password);
            if($username!=$_POST["username"]) // This is done to avoid SQL injection and other possible attacks
                //return '<b>For security reasons username allow only the characters A to Z, a to z, 0 to 9, "@" and "_"</b>';
                return '<b>You have entered an invalid symbol in the username field. Correct and try again.</b>';
            if($password!=$_POST["password"])  // This is done to avoid SQL injection and other possible attacks
                //return  '<b>For security reasons passwords allow only the characters A to Z, a to z, 0 to 9, spaces and any of these symbols: !@#$%^&*()_</b>';
                return '<b>You have entered an invalid symbol in the password field. Correct and try again.</b>';
            if($username=="") return "<b>User name cannot be left blank!</b>";
            if($_POST["password"]=="") return "<b>Password cannot be left blank!</b>";
            $validPair=false;
            foreach (explode(",",$sessionConfig["authmethod"]) as $method) {
                switch ($method) {
                    case "usertable":
                        if(validateUsingTable($username,$password)) {
                            $validPair=true;
                            break 2; //break outside the foreach
                        }
                        break;
                    case "ldap":
                        require_once("includes/mrclib.php");
                        if(mrclib_ldapauth($username,$password)) {
                            $validPair=true;
                            break 2; //break outside the foreach
                        }
                        break;
                    case "database":
                        if(validateUsingDatabase($username,$password)) {
                            $validPair=true;
                            break 2;
                        }
                        break;
                }
            }
            if($validPair) {
                $_SESSION["loggeduser"] = $username;
                // check for dean or chair and set session flag
                $userId = GetVerifyUserId();
                $_SESSION["user_info"] = GetPersonData($userId);
                return "<b>User logged in!</b>";
            }
        }
    }

   
	return "invalid";
}

/**
* @desc Returns the username used to start the session
* @return String with username, otherwise empty string
*/
function sessionLoggedUser() {
	return (isset($_SESSION["loggeduser"])) ? $_SESSION["loggeduser"] : false;
}

/**
*@desc Parses a XML node extracted from sitemap file
* @param $key node name (only letters, lowerwcase, must be unique on the XML file)
* @param $item node to parse from the xml file, must be simpleXMLitem object
* @return parsed values into the 'built' array structure
*/
function parseXMLsitemapNode($key,$item) {
	$type=(string)  $item["type"];
	$url="";
	$protected=false;
    $dean=false;
	$external=false;
	if( ((string) $item["menutitle"]) !="")
		$title=( (string) $item["menutitle"] );
	else
		$title=(string) $item;
	$extras="";
	switch($type) {
		case "loginlogout":
			$url=(string) $item["url"];
			list($in,$out)=explode(",",$title);
			if(sessionLoggedin()) $title=$out;
			else $title=$in;
			//$extras.=' rel="moodalbox" title="Caption: a description of the page that\'s loading."';
			//if( defined('MRCAJAXLOGIN') and MRCAJAXLOGIN )
			//	$extras.=" onclick=\"javascript:MOOdalBox.open( 'login.php?ajax=yes','','350 300' );return false\"";
            $extras.= "class='login-window'";
			break;
		case "static":
			$url="content.php?page=$key";
			break;
		case "dynamic":
			$url=(string) $item["url"];
			break;
		case "external";
			$url=(string) $item["url"];
			$external=true;
			break;

		case "section";
			$url="content.php?page=$key";
			//$url="#";
			break;
		default:
			$url="content.php?page=$key";
			break;
	}
	$built=Array();
	$built["menuitemurl"]=$url;
	$built["menuitemname"]=$title;
	$protected=strtolower( (string) $item["protected"] );
	if($protected=="yes" or $protected=="1" or $protected=="true")
		$built["protected"]="yes";
    $dean=strtolower( (string) $item["dean"] );
    if($dean=="yes" or $dean=="1" or $dean=="true")
        $built["dean"]="yes";
	if($external) $extras.=' target="_TOP"';

	$built["MENUITEMAHREF_EXTRAS"]=$extras;

	return $built;
}


/**
*@desc function to parse teh sitemap.xml file
* Node names must be unique, al nodes must have a type atribute, etc
* @param $tmpl valid and loaded patTemplate to parse the sitemap into the 'menu' patTemplate variable
* @param $page current pagename used to set opened menu
* @return nothing
*/
global $sitemapXML;
if( !isset($XMLsitemapParsed)) $XMLsitemapParsed=false;

function parseXMLsitemap(& $tmpl,$page) {
	global $sitemapXML,$XMLsitemapParsed,$db;
	global $configInfo;
	if($XMLsitemapParsed==true) 		// sitemap already parsed?? (cant parse nav menu two times)
		return;							// return if yes
	if(!isset($sitemapXML))
	$sitemapXML = simplexml_load_file($configInfo['file_root'].'/sitemap.xml');
	$sitemap=array();
	$parent2=$parent="2341234124312412431243";
	if($page!="") {
		$current=$sitemapXML->xpath("//$page");
		if($current and count($current)) {
			$current=reset($current);
			$parent=$current->xpath("..");
			if($parent and count($parent)) {
				$parent=reset($parent);
				$parent2=$parent->xpath("..");
				if($parent2 and count($parent2)) {
					$parent2=reset($parent2);
					$parent2=$parent2->getName();
				}
				$parent=$parent->getName();
			}
		}
	}

	foreach($sitemapXML as $key => $item) {
		$built=parseXMLsitemapNode($key,$item);
		if(isset($built["protected"]) and $built["protected"]=="yes" and sessionLoggedin()==false) continue;
        if(isset($built["dean"]) and $built["dean"]=="yes" and isDean()==false) continue;
		$subitems='';
		if($page==$key or (isset($_GET["menu"]) and $_GET["menu"]==$key) or $key==$parent or $key==$parent2)
			$show=true;
		else $show=false;

		$attributes=$item->attributes();

            //handle database type for the XML sitemap menu
            if ($attributes['type'] == 'database'){
                $sql="SELECT `{$attributes['dbkey']}`,`{$attributes['dbheader']}` FROM `{$attributes['dbtable']}` ORDER BY `{$attributes['dbtable']}`.`{$attributes['dbsort']}`";
                $data = $db->getAll($sql);
                foreach ($data as $row){
                    if ($_REQUEST[(string) $attributes['dbkey']] == $row[(string) $attributes['dbkey']]){
                        $class=' class="selected"';
                        $show=true;
                    }else{
                        $class='';
                    }
                    $subitems.='<li><a href="' . (string) $attributes['targeturl'] . '?' . (string) $attributes['dbkey'] . '=' . $row[(string) $attributes['dbkey']] .'"'.$class.'>'. $row[ (string) $attributes['dbheader']].'</a></li>';
                }

            }
        
			//{

				foreach($item as $subkey=>$subitem) {
					$subnode=parseXMLsitemapNode($subkey,$subitem);
					$url=$subnode["menuitemurl"];
					if(strchr($url,'?')==false) $url.='?';
					//$url.='&menu='.$key;
					if($subkey==$page  or $subkey==$parent or $subkey==$parent2) $class=' class="selected"';
					else $class='';

					$subitems.='<li><a href="'.$url.'"'.$class.'>'.$subnode["menuitemname"].'</a></li>';

				}
				if($subitems) {
					$subitems='<ul id="nav_'.$key.'" '.($show?' class="selected"':' class="hide"').'>'.$subitems.'</ul>';;

					//$built["MENUITEMAHREF_EXTRAS"].=' onclick="toggle(\'nav_'.$key.'\');"';

				}

				if($show)
					$built["MENUITEMAHREF_EXTRAS"].=' class="selected"';

			//}
		$built["menuitemsubmenu"]=$subitems;
		$sitemap[]=$built;
	}
            //2010-12-28 TD Added check for license plate
        /**
        * ToDo: If more plates are desired create a table for lookup
        */
    if($page=='index') {
        $image="<a href='/content.php?page=research_forms'><img border='0' src='/images/plate-forms.jpg' style='margin-left: 10px; margin-top: 50px;' /></a>";
        $image2="<a href='/content.php?page=srd'><img border='0' src='/images/plate-srd.jpg' style='margin-left: 10px; margin-top: 20px;' /></a>";
    }
    else $image=$image2='';
    $tmpl->addVar('header','image',$image);
    $tmpl->addVar('header','image2',$image2);
	$tmpl->addRows('menu',$sitemap);
	$XMLsitemapParsed=true;
}



/**
* @desc loads a template from the html directory to use it as page element.
* @param (String) name of the template file to open. Automatically the .html extension is appended.
* @param (String) Title to set on the webpage. Default: load the one specified in the XML file
* @param (string) Page name to use when parsing menu (for marking selected page), Default: use the same as first parameter (page)
* @return (object) the patTemplate object created
*/
function loadPage($page, $title="", $menupage="") {
    global $sitemapXML;
    global $configInfo;
    $fourofour = false;

    if ($menupage == "") $menupage = $page;	// page name to use when parsing menu (for marking selected page)
    $tmpl = new patTemplate();
    $tmpl->setRoot('html');
    //print_r($tmpl);
    //$tmpl->useTemplateCache(true); //Dont enable until all content is finished
    //if(($_GET["page"] == "" or $page == $_GET["page"])) {
	
	//echo($configInfo['file_root'] . "/html/".$page.'.html');
    if (file_exists("html/".$page.'.html')) {
        $tmpl->readTemplatesFromInput($page.'.html');
    } else {
        $tmpl->readTemplatesFromInput('404.html');
        $fourofour=true;
    }
    parseXMLsitemap($tmpl,$menupage);		// Parse sitemap to build navigation menu
    if ($title == "") {
        $result = $sitemapXML->xpath("//$page");
        //$title='-';
        if( $result and count($result)) {
            $thisnode = reset($result);
            //if( ((string) $thisnode["title"]) !="")
            //	$title=( (string) $thisnode["title"] );
            $title =( string) $thisnode;
        }
    }
    if($fourofour and $title) {
        $tmpl->addVar("page","notice","Contents for page '<i>$title</i>' could not be located.");
    } else {
        //$tmpl->readTemplatesFromInput('404.html');
        //$title="Page not found";
        //parseXMLsitemap($tmpl,$page);
    }
    $tmpl->addVar("header","title",$title);
    $tmpl->addVar("footer","copyright_year", date('Y'));
    $tmpl->applyInputFilter('ShortModifiers');
    //if($loadCenters) getCentres($tmpl);
	//print_r ($tmpl);
    return $tmpl;
}

/**
*@desc Displays a plain page with the basic template, custom title and message
*@param $title title to use as page title
*@param $message HTML contents to set as page contents
*/
function displayBlankPage($title,$contents,$page='') {
	global $XMLsitemapParsed;
	$XMLsitemapParsed=false;
	$tmpl = new patTemplate();
	$tmpl->setRoot('html');
	$tmpl->readTemplatesFromInput('blankpage.html');
	$tmpl->addVar("PAGE","CONTENT",$contents);
	$tmpl->addVar("HEADER","TITLE",$title);
	parseXMLsitemap($tmpl,$page);
	$tmpl->displayParsedTemplate();
}

/**
*@desc Returns a "Page" of rows extracting them from the specified array of rows, using the current selected page with $_GET["page"] and the global $rowsPerPage
* @param $ofArray Array of rows to paginate, usually returned rows form a SQL query.
* @return Array of rows, extracted from $ofArray, starting from $_GET["page"], with lenght of $rowsPerPage
*/
function getPagedArray($ofArray) {
	global $rowsPerPage;

	$page=1;
	if(isset($_GET['page'])) $page=$_GET["page"];

	$offset=($page-1)*$rowsPerPage;
	return array_slice($ofArray,$offset,$rowsPerPage);
}

/**
*@desc Returns HTML with links to access all the pages. Works the same as getPagedArray()
* @param $ofArray Array of rows to paginate, usually returned rows form a SQL query.
* @return HTML text with links. Current page isnt link but bolded page number.
*/
function getPagedLinks($ofArray) {
	global $rowsPerPage;
	$page=1;
	if(isset($_GET['page'])) $page=$_GET["page"];
	$maxPages=ceil(count($ofArray)/$rowsPerPage);
	$url=ereg_replace('&page=[0-9]+',"",$_SERVER [ 'REQUEST_URI']) ;
	$pages='';
	for($i=1;$i<=$maxPages;$i++) {
		if($page==$i) $pages.="<b>$i</b> ";
		else $pages.="<a href=\"$url&page=$i\">$i</a> ";
	}
	if($page>1) $pages="<a href=\"$url&page=".($page-1)."\">&lt; Previous</a> $pages";
	if($page<$maxPages) $pages.="<a href=\"$url&page=".($page+1)."\">Next ></a> ";
	return $pages;
}

/**
*   Used for debugging, returns a nicely formatted version of an array  or object
*
*   @param      array or object     $var        target array or object
*   @return     string                          HTML formatted result
*/
function PrintR($var) {
    echo '<div align="left"><pre>';
    print_r($var);
    echo '</pre></div>';
} // function PrintR

/**
*   Merges the values of two multidimensional arrays, matching keys in $a2 override $a1
*
*   @param      array      $a1      target array (eg. default values)
*   @param      array      $a2      array with values that take precedent (eg. set values)
*   @return     string      merged array
*/
function ArrayMergeClobber($a1, $a2) {

    // taken from user notes on php.net
    // (http://www.php.net/manual/en/function.array-merge-recursive.php)
    // like php native array_merge_recursive, but matching keys in a2 'clobber'
    // those in a1

    if (!is_array($a1) || !is_array($a2)) return false;
    $newArray = $a1;
    foreach ($a2 as $key => $val) {
        if (!isset($newArray[$key])) $newArray[$key] = array();
        if (is_array($val) && is_array($newArray[$key])) {
            $newArray[$key] = ArrayMergeClobber($newArray[$key], $val);
        } else {
            $newArray[$key] = $val;
        }  // if
    }  // foreach

    return $newArray;

} // function ArrayMergeClobber

/**
*   Removes \/ "', from a filename and makes it lower case
*/
function CleanFilename($filename) {
    return str_replace(array('\\', '/', '"', '\'', ' ', ','), '_', strtolower($filename));
} // function CleanFilename

/** make sure the currently logged in user is in the user table of the database and return their user id if true, and false otherwise
*
*   @return      return the username or false
*/
function GetVerifyUserId() {

    global $db;

    // make sure the username in the session is clean and valid
    $username = sessionLoggedUser();
    $orig_username = $username;
    cleanUp($username);
    if ($username != $orig_username) {
        // possible hack attempt, invalid username in session
    } else {
        $sql = "SELECT * FROM users WHERE username = \"$username\"";
        $user = $db->GetRow($sql);
        if (is_array($user) == false or count($user) == 0) {
            //displayBlankPage("Error","<h1>Security Error</h1>Could not locate current user in the database. ({$username})");
            //die;
            return false;
        } else {
            return $user["user_id"];
        }
    }
} // function GetVerifyUserId

/** return an array with all of the relevant user information
*
*   @param      integer     the user id of the currently logged in user
*   @return     array       all the user data or empty array if not found
*/
function GetPersonData($userId) {

    global $db;

    if ($userId > 0) {
        $sql = "
            SELECT u.first_name, u.last_name, u.department_id, u.department2_id, d1.division_id,
                CONCAT(u.first_name,' ',u.last_name) AS full_name,
                ue.emp_status AS status, ue.tss AS work_pattern,
                u.emp_type,
                d1.name AS department_name, d1.shortname AS department_shortname,
                d1.name AS department2_name, d1.shortname AS department2_shortname,
                di.name AS division_name,
                p1.email, p1.title, p1.secondary_title, p1.office, p1.phone, p1.fax,
                    p1.homepage, p1.profile_ext, p1.profile_short, p1.keywords, p1.description_short,
                p2.*,
                CONCAT(dean.first_name,' ',dean.last_name) AS dean_name,
                dean.user_id AS dean_user_id,
                CONCAT(chair.first_name,' ',chair.last_name) AS chair_name,
                chair.user_id AS chair_user_id,
                u.user_id
            FROM `users` AS u
            LEFT JOIN users_ext AS ue ON ue.user_id = u.user_id
            LEFT JOIN departments AS d1 ON d1.department_id = u.department_id
            LEFT JOIN departments AS d2 ON d2.department_id = u.department2_id
            LEFT JOIN divisions AS di ON di.division_id = d1.division_id
            LEFT JOIN profiles AS p1 ON p1.user_id = u.user_id
            LEFT JOIN ar_profiles AS p2 ON p2.user_id = u.user_id
            LEFT JOIN users AS dean ON dean.user_id = di.dean
            LEFT JOIN users AS chair ON chair.user_id = d1.chair
            WHERE u.user_id = {$userId}
            LIMIT 1
        ";
        //echo $sql;
        $personData = $db->getAll($sql);
    //print_r($personData);
    //die();
    $personData = (isset($personData) && is_array($personData)) ? reset($personData) : array();
    // check to see if this person is a dean or a chair
    // Modified by TD 2011-11-28 to allow multiple divisions and depts. Has effects all over the place.
    $personData['dean_flag'] = false;
    $personData['chair_flag'] = false;
    $sql = "SELECT division_id, name FROM divisions WHERE divisions.dean = {$userId}";
    $deanData = $db->getAll($sql);
    if (sizeof($deanData) >= 1) {
        $personData['dean_flag'] = true;
        $divs=array();
        foreach($deanData as $deanDatum) $divs[]=$deanDatum['division_id'];
        $alldivs=implode(',',$divs);
        $personData['dean_division_id'] = $alldivs;
    } // if
    
    $sql = "SELECT division_id, name FROM divisions WHERE divisions.associate_dean = {$userId}";
    $deanData = $db->getAll($sql);
    if (sizeof($deanData) >= 1) {
        $personData['associate_dean_flag'] = true;
        $divs=array();
        foreach($deanData as $deanDatum) $divs[]=$deanDatum['division_id'];
        $alldivs=implode(',',$divs);
        $personData['associate_dean_division_id'] = $alldivs;
    } // if
    
    $sql = "SELECT department_id, name FROM departments WHERE departments.chair = {$userId}";
    $chairData = $db->getAll($sql);
    if (sizeof($chairData) >= 1) {
        $personData['chair_flag'] = true;
        $depts=array();
        foreach($chairData as $chairDatum) $depts[]=$chairDatum['department_id'];
        $alldepts=implode(',',$depts);
        $personData['chair_department_id'] = $alldepts;
    } // if
    //TD Added Director Role - was added to department table 2012-01-20
    $sql = "SELECT department_id, name FROM departments WHERE departments.director = {$userId}";
    $dirData = $db->getAll($sql);
    if (sizeof($dirData) >= 1) {
        $personData['director_flag'] = true;
        $depts=array();
        foreach($dirData as $dirDatum) $depts[]=$dirDatum['department_id'];
        $alldepts=implode(',',$depts);
        $personData['director_department_id'] = $alldepts;
    } // if
    
    return $personData;
    } // if

} // function GetPersonData

/** get the current school year
*
*   @return     integer     4 digit year
*/
function GetSchoolYear($timeStamp) {

    // if the month is Jan -> Aug then the year is this year, other wise it is next year
    if (date('n',$timeStamp) < 9) {
        $schoolYear = date('Y',$timeStamp);
    } else {
        $schoolYear = date('Y',$timeStamp) + 1;
    } // if
    return $schoolYear;

} // function GetSchoolYear

/** generate the formatted course title from the course data
*
*   @param      array       the course data from the database
*   @return     string      the formatted course title
*/
function CreateCourseTitle($courseData) {

    return $courseData['subject'] . $courseData['crsenumb'] . '-' . sprintf('%03d',$courseData['sectnumb']) . ':' . $courseData['crsedescript'];

} // function CreateCourseTitle

/**
* Puts entries into the approval table for form approvals
* 
* @param mixed $form_type The name of the form table. 
* @param mixed $form_id ID of the form
* @param mixed $who What approval level? Dean, Director, Chair, VP, ORS
* @param mixed $what Sign or View the form
* @param mixed $queue_order Integer - lowest # has highest priority and is processed first
* @param mixed $now Flag as to whether an email is generated right away or queued for cronjob action
*/
function setup_approval($form_type,$form_id=0,$who,$what,$queue_order,$now=0){
    global $db;
    global $configInfo;
    global $sessionConfig;
    
    $sql="INSERT INTO `research`.`forms_approvals` (
        `id` ,
        `form_table` ,
        `form_id` ,
        `type` ,
        `authority` ,
        `entered` ,
        `viewed` ,
        `signed` ,
        `signed_by` ,
        `comments` ,
        `due_date` ,
        `queue_order`
        )
        VALUES (
        NULL , '$form_type', '$form_id', '$what', '$who', NOW(), NULL, NULL, '0', '', NULL, '$queue_order'
        );   ";
    
     if(!$db->Execute($sql)) echo($db->ErrorMsg());
     
     if($now){
         //ToDo: Fire job for immediate mail
         
     }
}


//Simple login routines for student subsystem
function student_logged_in() {
	if(isset( $_SESSION['user_id'] )) return true;
	else return false;	
}


function st_login($username,$password){
	/*** begin our session ***/
	session_start();

	/*** check if the users is already logged in ***/
	if(isset( $_SESSION['user_id'] ))
	{
	    $message = 'User is already logged in';
	}
	/*** check that both the username, password have been submitted ***/
	if(!isset( $_POST['username'], $_POST['password']))
	{
	    $message = 'Please enter a valid username and password';
	}
	/*** check the username is the correct length ***/
	elseif (strlen( $_POST['username']) > 20 || strlen($_POST['username']) < 4)
	{
	    $message = 'Incorrect Length for Username';
	}
	/*** check the password is the correct length ***/
	elseif (strlen( $_POST['password']) > 20 || strlen($_POST['password']) < 4)
	{
	    $message = 'Incorrect Length for Password';
	}
	/*** check the username has only alpha numeric characters ***/
	elseif (ctype_alnum($_POST['username']) != true)
	{
	    /*** if there is no match ***/
	    $message = "Username must be alpha numeric";
	}
	/*** check the password has only alpha numeric characters ***/
	elseif (ctype_alnum($_POST['password']) != true)
	{
	        /*** if there is no match ***/
	        $message = "Password must be alpha numeric";
	}
	else
	{
	    /*** if we are here the data is valid and we can insert it into database ***/
	    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
	    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
	
	    /*** now we can encrypt the password ***/
	    $password = sha1( $password );
	
		$sql="SELECT * FROM users_students WHERE username=$username and password=$password";
		$result=$db->GetRow($sql);
		if(count($result)>0) {
			$_SESSION['user_id'] = $user_id;
			$_SESSION['username'] = $username;
		}
	    
	}	
}

function st_logout(){
	// Begin the session
	session_start();
	
	// Unset all of the session variables.
	session_unset();
	
	// Destroy the session.
	session_destroy();
	
	echo('<script type="text/javascript">
            document.location="index.php";
            window.navigate("index.php");
            </script>');
}

function check_userid($user_Id){
	if ( $userId == false || $userId < 1 ) {
        displayBlankPage( "Invalid username", "<h1>Security Error</h1><p>Possible hacking attempt or session error, please contact your system administrator.</p>" );
        // log an error here?
        return false;
    }	
    return true;
}

?>