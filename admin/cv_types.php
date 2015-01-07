<?php
include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");

//error_reporting(E_ALL ^ E_NOTICE);

$template = new Template;

include("html/header.html");

if(!isset($_REQUEST['section'])) $section='view'; else $section=$_REQUEST['section'];
$success="";
$date_error=FALSE;


if(isset($_REQUEST['update'])) {
    if(isset($_REQUEST['id'])){
        
        if(isset($_REQUEST['default_web'])) $default_web=1; else $default_web=0;

		$values = array(    'cv_item_header_id'=>$_REQUEST['cv_item_header_id'],
                            'rank'=>$_REQUEST['rank'],
                            'title'=>$_REQUEST['title'],
                            'title_plural'=>$_REQUEST['title_plural'],
                            'f1_name'=>$_REQUEST['f1_name'],
                            'f1_eg'=>$_REQUEST['f1_eg'],
                            'f1_type'=>$_REQUEST['f1_type'],
                            'f2_name'=>$_REQUEST['f2_name'],
                            'f2_eg'=>$_REQUEST['f2_eg'],
                            'f2_type'=>$_REQUEST['f2_type'],
                            'f3_name'=>$_REQUEST['f3_name'],
                            'f3_eg'=>$_REQUEST['f3_eg'],
                            'f3_type'=>$_REQUEST['f3_type'],
                            'f4_name'=>$_REQUEST['f4_name'],
                            'f4_eg'=>$_REQUEST['f4_eg'],
                            'f4_type'=>$_REQUEST['f4_type'],
                            'f5_name'=>$_REQUEST['f5_name'],
                            'f5_eg'=>$_REQUEST['f5_eg'],
                            'f5_type'=>$_REQUEST['f5_type'],
                            'f6_name'=>$_REQUEST['f6_name'],
                            'f6_eg'=>$_REQUEST['f6_eg'],
                            'f6_type'=>$_REQUEST['f6_type'],
                            'f7_name'=>$_REQUEST['f7_name'],
                            'f7_eg'=>$_REQUEST['f7_eg'],
                            'f7_type'=>$_REQUEST['f7_type'],
                            'f8_name'=>$_REQUEST['f8_name'],
                            'f8_eg'=>$_REQUEST['f8_eg'],
                            'f8_type'=>$_REQUEST['f8_type'],
                            'f9_name'=>$_REQUEST['f9_name'],
                            'f9_eg'=>$_REQUEST['f9_eg'],
                            'f9_type'=>$_REQUEST['f9_type'],
                            'f10_name'=>$_REQUEST['f10_name'],
                            'f10_eg'=>$_REQUEST['f10_eg'],
                            'f11_name'=>$_REQUEST['f11_name'],
                            'f11_eg'=>$_REQUEST['f11_eg'],
                            'default_web'=>$default_web,
                            'display_code'=>$_REQUEST['display_code'],
                            'show_url'=>$_REQUEST['show_url'],
                            'url_type'=>$_REQUEST['url_type'],
                            'show_abstract'=>$_REQUEST['show_abstract']
                            );
		$result=mysqlUpdate("cv_item_types",$values,"cv_item_type_id=$_REQUEST[id]");
		if($result != 1) $success= "Error updating database: $result";
		else $success="Updated";
        
        $section='view';
        
    }
}

if(isset($_REQUEST['add'])) {
    $result=mysqlFetchRows('cv_item_types',"rank=$_REQUEST[rank]");
    if(is_array($result)) $_REQUEST['rank']-=0.1;
    if(isset($_REQUEST['default_web'])) $default_web=1; else $default_web=0;

    $values = array(    'null',
                        $_REQUEST['cv_item_header_id'],
                        $_REQUEST['rank'],
                        $_REQUEST['title'],
                        $_REQUEST['title_plural'],
                        $_REQUEST['f1_name'],
                        $_REQUEST['f1_eg'],
                        $_REQUEST['f1_type'],
                        $_REQUEST['f2_name'],
                        $_REQUEST['f2_eg'],
                        $_REQUEST['f2_type'],
                        $_REQUEST['f3_name'],
                        $_REQUEST['f3_eg'],
                        $_REQUEST['f3_type'],
                        $_REQUEST['f4_name'],
                        $_REQUEST['f4_eg'],
                        $_REQUEST['f4_type'],
                        $_REQUEST['f5_name'],
                        $_REQUEST['f5_eg'],
                        $_REQUEST['f5_type'],
                        $_REQUEST['f6_name'],
                        $_REQUEST['f6_eg'],
                        $_REQUEST['f6_type'],
                        $_REQUEST['f7_name'],
                        $_REQUEST['f7_eg'],
                        $_REQUEST['f7_type'],
                        $_REQUEST['f8_name'],
                        $_REQUEST['f8_eg'],
                        $_REQUEST['f8_type'],
                        $_REQUEST['f9_name'],
                        $_REQUEST['f9_eg'],
                        $_REQUEST['f9_type'],
                        $_REQUEST['f10_name'],
                        $_REQUEST['f10_eg'],
                        $_REQUEST['f11_name'],
                        $_REQUEST['f11_eg'],
                        $default_web,
                        $_REQUEST['display_code'],
                        $_REQUEST['show_url'],
                        $_REQUEST['url_type'],
                        $_REQUEST['show_abstract']
                        );
		$result=mysqlInsert("cv_item_types",$values);
		if($result != 1) $success="<font color='#AA0000'>Error Adding Item: $result</font>";
		else $success="Added";


}



if(isset($_REQUEST['delete'])) {
	if(isset($_REQUEST['id'])){
		$result=mysqlDelete("cv_item_types","cv_item_type_id=$id");
		if($result != 1) $success= "Error Deleting: $result";
		else $success="Deleted";
		$section="view";
	}
}

if(isset($_REQUEST['sort_rank'])){
    $headers=mysqlFetchRows('cv_item_headers', '1 order by rank');
    foreach($headers as $header) {
        $items=mysqlFetchRows('cv_item_types',"cv_item_header_id=$header[cv_item_header_id] order by rank");
        $rank=1;
        if(is_array($items)) foreach ($items as $item){
            $item['rank']=$rank;
            $rank++;
            $item['display_code']=addslashes($item['display_code']);
            $item['f1_eg']=addslashes($item['f1_eg']);
            $item['f4_eg']=addslashes($item['f4_eg']);
            $item['f5_eg']=addslashes($item['f5_eg']);
            $item['f6_eg']=addslashes($item['f6_eg']);
            $item['f7_eg']=addslashes($item['f7_eg']);
            $item['f8_eg']=addslashes($item['f8_eg']);
            $item['f9_eg']=addslashes($item['f9_eg']);
            $item['f10_eg']=addslashes($item['f10_eg']);
            $item['f11_eg']=addslashes($item['f11_eg']);
            
            $result=mysqlUpdate('cv_item_types',$item,"cv_item_type_id=$item[cv_item_type_id]");
            if($result !=1) echo "Error writing: $result <br><br>";
            echo("Rewrote: $item[title] with rank: $item[rank]<br>");
        }
        
    }  
}

if(isset($section)) switch ($section){
	case"update":
        if(isset($_REQUEST['id'])){
            $item=mysqlFetchRow('cv_item_types',"cv_item_type_id=$_REQUEST[id]");
            if(is_array($item)){
                //load headers
                $headers=mysqlFetchRows('cv_item_headers','1 order by category,rank');
                $header_options='';
                foreach($headers as $header){
                    if($header['cv_item_header_id']==$item['cv_item_header_id']) $sel='selected'; else $sel='';
                    $header_options.="<option value=$header[cv_item_header_id] $sel>$header[category]: $header[title]</option>\n";
                }
                //load ranks - create menu that is read-only so they can see the existing rank list
                $ranks=mysqlFetchRows('cv_item_types','1 order by rank');
                $rank_options='';
                foreach($ranks as $rank) $rank_options.="<option value=''>$rank[rank] - $rank[title]</option>\n";
                //build something for the eval code to work on as an example
                if($item['display_code'] !=''){

                    //need to convert date examples to actual UNIX dates
                    if($item['f2_eg']=='') $f2=0;
                    else {
                        if(is_numeric($item['f2_eg'])) {//likely a year
                            if (($item['f2_eg'] > 1970) && ($item['f2_eg'] < 2038))
                                $f2=mktime(0,0,0,1,1,$item['f2_eg']);
                        }
                        else if(count(explode("/",$item['f2_eg'])) == 2) { // month and year?
                            $temp_date=explode("/",$item['f2_eg']);
                            $f2=mktime(0,0,0,$temp_date[0],1,$temp_date[1]);
                        }
                        else if(count(explode("/",$item['f2_eg'])) == 3) { // day month and year?
                            $temp_date=explode("/",$item['f2_eg']);
                             $f2=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
                        }
                        else $f2=0;
                    }

                    if($item['f3_eg']=='') $f3=0;
                    else {
                        if(is_numeric($item['f3_eg'])) {//likely a year
                            if (($item['f3_eg'] > 1970) && ($item['f3_eg'] < 2038))
                                $f3=mktime(0,0,0,1,1,$item['f3_eg']);
                        }
                        else if(count(explode("/",$item['f3_eg'])) == 2) { // month and year?
                            $temp_date=explode("/",$item['f3_eg']);
                            $f3=mktime(0,0,0,$temp_date[0],1,$temp_date[1]);
                        }
                        else if(count(explode("/",$item['f3_eg'])) == 3) { // day month and year?
                            $temp_date=explode("/",$item['f3_eg']);
                            $f3=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
                        }
                        else $f3=0;
                    }

                    $item['f1']=$item['f1_eg'];
                    $item['f2']=$f2;
                    $item['f3']=$f3;
                    $item['f4']=$item['f4_eg'];
                    $item['f5']=$item['f5_eg'];
                    $item['f6']=$item['f6_eg'];
                    $item['f7']=$item['f7_eg'];
                    $item['f8']=$item['f8_eg'];
                    $item['f9']=$item['f9_eg'];
                    $item['f10']=$item['f10_eg'];
                    $item['f11']=$item['f11_eg'];

                    $output="";
                    $showdescription=TRUE;
                    eval($item['display_code']);
                }
                else $output='';
                
                if($item['default_web']) $default_web='checked'; else $default_web='';
                
                $f2_type_options=$f3_type_options='';
                foreach(array('year','month','') as $type){
                    if($item['f2_type']==$type) $sel2='selected'; else $sel2='';
                    if($item['f3_type']==$type) $sel3='selected'; else $sel3='';
                    $f2_type_options.="<option value='$type' $sel2>$type</option>\n";
                    $f3_type_options.="<option value='$type' $sel3>$type</option>\n";
                }
                $f1_type_options=$f4_type_options=$f5_type_options=$f6_type_options=$f7_type_options=$f8_type_options=$f9_type_options='';
                foreach(array('','simple') as $type){
                    if($item['f1_type']==$type) $sel1='selected'; else $sel1='';
                    if($item['f4_type']==$type) $sel4='selected'; else $sel4='';
                    if($item['f5_type']==$type) $sel5='selected'; else $sel5='';
                    if($item['f6_type']==$type) $sel6='selected'; else $sel6='';
                    if($item['f7_type']==$type) $sel7='selected'; else $sel7='';
                    if($item['f8_type']==$type) $sel8='selected'; else $sel8='';
                    if($item['f9_type']==$type) $sel9='selected'; else $sel9='';
                    
                    $f1_type_options.="<option value='$type' $sel1>$type</option>\n";
                    $f4_type_options.="<option value='$type' $sel4>$type</option>\n";
                    $f5_type_options.="<option value='$type' $sel5>$type</option>\n";
                    $f6_type_options.="<option value='$type' $sel6>$type</option>\n";
                    $f7_type_options.="<option value='$type' $sel7>$type</option>\n";
                    $f8_type_options.="<option value='$type' $sel8>$type</option>\n";
                    $f9_type_options.="<option value='$type' $sel9>$type</option>\n";
                }

			    $hasharray = array( 'success'=>$success,
                                    'id'=>$_REQUEST['id'],
                                    'header_options'=>$header_options,
                                    'rank'=>$item['rank'],
                                    'rank_options'=>$rank_options,
                                    'title'=>$item['title'],
                                    'title_plural'=>$item['title_plural'],
                                    'f1_name'=>$item['f1_name'],
                                    'f1_eg'=>$item['f1_eg'],
                                    'f1_type_options'=>$f1_type_options,
                                    'f2_name'=>$item['f2_name'],
                                    'f2_eg'=>$item['f2_eg'],
                                    'f2_type_options'=>$f2_type_options,
                                    'f3_name'=>$item['f3_name'],
                                    'f3_eg'=>$item['f3_eg'],
                                    'f4_name'=>$item['f4_name'],
                                    'f4_type_options'=>$f4_type_options,
                                    'f3_type_options'=>$f3_type_options,
                                    'f4_eg'=>$item['f4_eg'],
                                    'f5_name'=>$item['f5_name'],
                                    'f5_eg'=>$item['f5_eg'],
                                    'f5_type_options'=>$f5_type_options,
                                    'f6_name'=>$item['f6_name'],
                                    'f6_eg'=>$item['f6_eg'],
                                    'f6_type_options'=>$f6_type_options,
                                    'f7_name'=>$item['f7_name'],
                                    'f7_eg'=>$item['f7_eg'],
                                    'f7_type_options'=>$f7_type_options,
                                    'f8_name'=>$item['f8_name'],
                                    'f8_eg'=>$item['f8_eg'],
                                    'f8_type_options'=>$f8_type_options,
                                    'f9_name'=>$item['f9_name'],
                                    'f9_eg'=>$item['f9_eg'],
                                    'f9_type_options'=>$f9_type_options,
                                    'f10_name'=>$item['f10_name'],
                                    'f10_eg'=>$item['f10_eg'],
                                    'f11_name'=>$item['f11_name'],
                                    'f11_eg'=>$item['f11_eg'],
                                    'default_web'=>$default_web,
                                    'show_url'=>$item['show_url'],
                                    'display_code'=>$item['display_code'],
                                    'url_type'=>$item['url_type'],
                                    'show_abstract'=>$item['show_abstract'],
                                    'eval_code'=>$output
                                    );
			    $filename = 'templates/template-cv_types_update.html';
			    $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
			    echo $parsed_html_file;
            }
            else echo "Not Found";
        } //isset ID

	break;

	case "add":

        //load headers
        $headers=mysqlFetchRows('cv_item_headers','1 order by category,rank');
        $header_options='<option value=0></option>\n';
        foreach($headers as $header)
            $header_options.="<option value=$header[cv_item_header_id]>$header[category]: $header[title]</option>\n";
        //load ranks - create menu that is read-only so they can see the existing rank list
        $ranks=mysqlFetchRows('cv_item_types','1 order by rank');
        $rank_options='';
        foreach($ranks as $rank) $rank_options.="<option value=''>$rank[rank] - $rank[title]</option>\n";
        //build something for the eval code to work on as an example

        $f2_type_options=$f3_type_options='';
        foreach(array('','year','month') as $type){
            $f2_type_options.="<option value='$type'>$type</option>\n";
            $f3_type_options.="<option value='$type'>$type</option>\n";
        }
        $f1_type_options=$f4_type_options=$f5_type_options=$f6_type_options=$f7_type_options=$f8_type_options=$f9_type_options='';
        foreach(array('','simple') as $type){
            
            $f1_type_options.="<option value='$type' >$type</option>\n";
            $f4_type_options.="<option value='$type' >$type</option>\n";
            $f5_type_options.="<option value='$type' >$type</option>\n";
            $f6_type_options.="<option value='$type' >$type</option>\n";
            $f7_type_options.="<option value='$type' >$type</option>\n";
            $f8_type_options.="<option value='$type' >$type</option>\n";
            $f9_type_options.="<option value='$type' >$type</option>\n";
        }
        
        $hasharray = array( 'success'=>$success,
                            'header_options'=>$header_options,
                            'rank_options'=>$rank_options,
                            'f1_type_options'=>$f1_type_options,
                            'f2_type_options'=>$f2_type_options,
                            'f3_type_options'=>$f3_type_options,
                            'f4_type_options'=>$f4_type_options,
                            'f5_type_options'=>$f5_type_options,
                            'f6_type_options'=>$f6_type_options,
                            'f7_type_options'=>$f7_type_options,
                            'f8_type_options'=>$f8_type_options,
                            'f9_type_options'=>$f9_type_options
                            );
        $filename = 'templates/template-cv_types_add.html';
        $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
        echo $parsed_html_file;

	break;

	case "view":
        //3 Level nested view
        
        $output='';
        foreach(array('Teaching'=>'teaching','Research'=>'research','Service'=>'service') as $key=>$category) {
            $output.="<tr><td colspan='2' bgcolor='#000000'><b><font color='#FFFFFF'>$key</font></b></td></tr>\n";
            $headers=mysqlFetchRows('cv_item_headers',"category='$category' order by rank");
            if(is_array($headers)) foreach($headers as $header){
                $output.="<tr><td colspan='2' bgcolor='#D7D7D9'>&nbsp;&nbsp;<b>$header[title]</b></td></tr>\n";
                $types=mysqlFetchRows('cv_item_types',"cv_item_header_id=$header[cv_item_header_id] order by rank");
                if(is_array($types))  foreach($types as $item){
                    $items=mysqlFetchRows('cv_items',"cv_item_type_id=$item[cv_item_type_id]");
                    if(is_array($items)) $num=count($items); else $num=0;
                    $output.="<tr><td bgcolor='#D7D7D9' style='border:left 10px;'><a href='cv_types.php?section=update&id=$item[cv_item_type_id]'>$num &nbsp; $item[title]</a></td>";
                    //Build an example of the display code
                    if($item['display_code'] !=''){
                        $output.="<td bgcolor='#D7D7D9'>";
                        //need to convert date examples to actual UNIX dates
                        if($item['f2_eg']=='') $f2=0;
                        else {
                            if(is_numeric($item['f2_eg'])) {//likely a year
                                if (($item['f2_eg'] > 1970) && ($item['f2_eg'] < 2038))
                                    $f2=mktime(0,0,0,1,1,$item['f2_eg']);
                            }
                            else if(count(explode("/",$item['f2_eg'])) == 2) { // month and year?
                                $temp_date=explode("/",$item['f2_eg']);
                                $f2=mktime(0,0,0,$temp_date[0],1,$temp_date[1]);
                            }
                            else if(count(explode("/",$item['f2_eg'])) == 3) { // day month and year?
                                $temp_date=explode("/",$item['f2_eg']);
                                 $f2=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
                            }
                            else $f2=0;
                        }

                        if($item['f3_eg']=='') $f3=0;
                        else {
                            if(is_numeric($item['f3_eg'])) {//likely a year
                                if (($item['f3_eg'] > 1970) && ($item['f3_eg'] < 2038))
                                    $f3=mktime(0,0,0,1,1,$item['f3_eg']);
                            }
                            else if(count(explode("/",$item['f3_eg'])) == 2) { // month and year?
                                $temp_date=explode("/",$item['f3_eg']);
                                $f3=mktime(0,0,0,$temp_date[0],1,$temp_date[1]);
                            }
                            else if(count(explode("/",$item['f3_eg'])) == 3) { // day month and year?
                                $temp_date=explode("/",$item['f3_eg']);
                                $f3=mktime(0,0,0,$temp_date[1],$temp_date[0],$temp_date[2]);
                            }
                            else $f3=0;
                        }

                        $item['f1']=$item['f1_eg'];
                        $item['f2']=$f2;
                        $item['f3']=$f3;
                        $item['f4']=$item['f4_eg'];
                        $item['f5']=$item['f5_eg'];
                        $item['f6']=$item['f6_eg'];
                        $item['f7']=$item['f7_eg'];
                        $item['f8']=$item['f8_eg'];
                        $item['f9']=$item['f9_eg'];
                        $item['f10']=$item['f10_eg'];
                        $item['f11']=$item['f11_eg'];

                        
                        eval($item['display_code']);
                        $output.="</td>";
                    }
                    else $output.='<td></td>';
                    
                    $output.="</tr>";
                }
            }
        }


		$hasharray = array('success'=>$success,'output'=>$output);
		$filename = 'templates/template-cv_types_view.html';
		$parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
		echo $parsed_html_file;
	break;


} //switch


//-- Footer File
include("templates/template-footer.html");
?>