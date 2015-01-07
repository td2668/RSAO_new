<?php

/**
 * This file is used to load the annual report pages, the $_GET['page'] parameter is required.
 */
/* * *********************************
 * INCLUDES
 * ********************************** */
require_once('includes/config.inc.php');

/* * *********************************
 * CONFIGURATION
 * ********************************** */

$mrAction = (isset( $_REQUEST["mr_action"] )) ? CleanString( $_REQUEST["mr_action"] ) : '';
$getUserId = (isset( $_GET["user_id"] )) ? CleanString( $_GET["user_id"] ) : false;
$nextPageFlag = (isset( $_REQUEST["next_flag"] )) ? CleanString( $_REQUEST["next_flag"] ) : false;
$casHeadingId = (isset( $_REQUEST["cas_heading_id"] )) ? CleanString( $_REQUEST["cas_heading_id"] ) : false;
$citationStyle = (isset( $_REQUEST["citation_style"] )) ? CleanString( $_REQUEST["citation_style"] ) : 'apa';
$generateWhat = (isset( $_REQUEST["generate"] )) ? CleanString( $_REQUEST["generate"] ) : '';
$style = (isset( $_REQUEST["style"] )) ? CleanString( $_REQUEST["style"] ) : '';

$userId = isset( $_REQUEST["user_id"] ) ? $_REQUEST["user_id"] : false;
if ($userId===false) $userId = isset( $_REQUEST["report_user_id"] ) ? $_REQUEST["report_user_id"] : false;

//setup menu heading id based on page being sent in

/* * *********************************
 * MAIN
 * ********************************** */

    if ( $userId == false || $userId < 1 ) {
        displayBlankPage( "Invalid username", "<h1>Security Error</h1><p>Session error, please contact your system administrator.</p>" );
        // log an error here?

        die( 1 );
    }
    //print_r($_REQUEST);
    if(isset($_REQUEST['togglebanner'])){
        $sql="SELECT * FROM user_disable_banner WHERE user_id=$userId";
        $result=$db->getRow($sql);
        if(count($result) > 0){

            $sql="DELETE FROM user_disable_banner WHERE user_id=$userId";
            $result=$db->Execute($sql);
            if(!$result) echo $db->ErrorMsg();
        }
        else { //Create a record
            $sql="INSERT INTO user_disable_banner SET user_id=$userId";
            $result=$db->Execute($sql);
            if(!$result) echo $db->ErrorMsg();
        }
        $tmpl=loadPage('caqccv','','');
        $tmpl->displayParsedTemplate();
        exit;
    }

    if ($generateWhat){
        if (strtolower($generateWhat) == 'everything'){
            $generateWhat = '';
        }

        if(strtolower($generateWhat) == 'caqc') {
            require_once('includes/pdf.php');
            GenerateCAQC($userId);


        }
        elseif(strtolower($generateWhat) == 'importcourses'){
            $num=ImportCourses($userId);
            $tmpl=loadPage('caqccv','','');
            $tmpl->setAttribute('success1','visibility','visible');
            $tmpl->addVar('success1','reply',"Imported $num courses");
            $tmpl->displayParsedTemplate();
        }
        else {
            require_once('includes/pdf.php');
            //echo("Generating with $userId,$generateWhat,$style\n");
            GenerateCV($userId,$generateWhat,$style);
        }
        exit();
    }else{
        $tmpl = loadPage( 'cv_review_submit', '','');

        if ($citationStyle == 'apa'){
            $tmpl->addVar( 'Page', 'APA_CHECKED', 'CHECKED="CHECKED"' );
        }else{
            $tmpl->addVar( 'Page', 'CHICAGO_CHECKED', 'CHECKED="CHECKED"' );
        }
        //AddPageVars( $casHeadingId, 'Review / Submit', $mrAction, $tmpl );

}

// load the common annual report javascript library
//$tmpl->addVar('HEADER','ADDITIONAL_HEADER_ITEMS','    <script type="text/javascript" src="js/annual_report.js"></script>');
//set
// display the template to the user
$tmpl->addVar( 'HEADER', 'ADDITIONAL_HEADER_ITEMS', '    <script type="text/javascript" src="' . MRJQUERYPATH . '"></script>' );
$tmpl->addVar('Page','USER_ID',$userId);
$tmpl->displayParsedTemplate();
exit;

/**
 *
 * Load courses from 'courses' into the Courses Taught cv item type.
 *Not finished
 */

function ImportCourses($userId) {
    //Note - use f11 == 2 as the flag for 'imported item'
    global $db;
    $sql="SELECT * FROM cas_institutions WHERE name='Mount Royal University'";
    $inst=$db->getRow($sql);

    $sql="SELECT * FROM course_teaching LEFT JOIN courses on (course_teaching.course_id=courses.course_id) WHERE course_teaching.user_id={$userId} group by CONCAT(subject,crsenumb)";
    $courses=$db->GetAll($sql);


    if(count($courses)>0) foreach($courses as $course){
        //Need to combine all instances of the same course.


        //Might want a check here to see if the course exists. But tricky...
        //Need hours to credits ratio
        $term=$course['term']-((int)($course['term']/10)) * 10;
        $year=(int)($course['term']/100);
        if($term==1) {$start=$year . '-01-00'; $end=$year . '-04-00';}
        elseif($term==2) {$start=$year . '-01-00'; $end=$year . '-04-00';}
        elseif($term==3) {$start=$year . '-05-00'; $end=$year . '-06-00';}
        elseif($term==4) {$start=$year . '-09-00'; $end=$year . '-12-00';}
        else $start=$end='0000-00-00';
        $n08=$n10=$n11=$n12=0;
        //08=Lecture, 10-tutorial, 11=lab, 12=other
        switch($course['schedcode']){
            case 'A':
                $n11=$course['hours'];
                break;
            case 'BL':
                $n08=$course['hours'];
                break;
            case 'C':
                $n12=$course['hours'];
                break;
            case 'D':
                $n12=$course['hours'];
                break;
            case 'DD':
                $n12=$course['hours'];
                break;
            case 'F':
                $n12=$course['hours'];
                break;
            case 'I':
                $n12=$course['hours'];
                break;
            case 'K':
                $n12=$course['hours'];
                break;
            case 'L':
                $n08=$course['hours'];
                break;
            case 'P':
                $n12=$course['hours'];
                break;
            case 'T':
                $n10=$course['hours'];
                break;
            default:
                $n12=$course['hours'];

        }
        $sql="INSERT INTO cas_cv_items SET
    			n01='$course[subject] $course[crsenumb]',
    			n02=$term,
    			n04=$inst[id],
    			n05='$course[crsedescript]',
    			n06=$course[numstudents],
    			n08=$n08,
    			n09='$start',
    			n10=$n10,
    			n11=$n11,
    			n12=$n12,
    			n18='$end',
    			n14='$course[sectnumb]',
    			user_id=$userId,
    			cas_type_id=29,
    			mycv2=1
    			";
        //$result=$db->Execute($sql);
        //if(!$result) echo "Error: ". $db->ErrorMsg();

        echo("<pre><br><br><br>");
        print_r($course);
        echo $sql;
        echo ("<br>");

    }
    return count($courses);
}
