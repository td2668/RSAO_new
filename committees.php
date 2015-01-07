<?php
require_once('includes/global.inc.php');

$tmpl=loadPage("committees", 'Committees');
showMenu("research_office");

$committees=array(
                    'rsac'=>'Research & Scholarship Advisory Committee',
                    'irc'=>'Institutes Review Committee',
                    'src'=>'Scholarship Review Committee',
                    'rcrc'=>'Responsible Conduct of Research Committee',
                    'ugrc'=>'Undergraduate Research Standing Committee');

foreach($committees as $key=>$committee){

    $sql="  select users.last_name, users.first_name, profiles.email, committee_members.chair, profiles.phone, departments.name from committee_members
            left join committees using (committee_id) 
            left join users using (user_id) 
            left join departments using (department_id) 
            left join profiles using (user_id)
            where committees.name = '$committee' 
            order by committee_members.chair desc, users.last_name, users.first_name";
   // echo $sql;
    $comm=$db->GetAll($sql);
    
    foreach($comm as $key2=>$value) {
        $comm[$key2]['email']=strrev( $comm[$key2]['email']);
        if($value['chair']==1 ) $comm[$key2]['last_name']=  $comm[$key2]['last_name'].' (Chair)';
    }
    
    $tmpl->addRows($key, $comm);

    $tmpl->addRows($key.'_min',grabMinutes($committee));

}



$tmpl->displayParsedTemplate('page');

function grabMinutes($committeeName) {
    global $db;
    global $configInfo;
    $sql="  SELECT * FROM minutes
        LEFT JOIN committees using (committee_id)
        where committees.name = '$committeeName'
        ORDER BY date desc";
    $min=$db->GetAll($sql);
    if(is_array($min)) {
        foreach($min as $key=>$value) {
            $min[$key]['date']=date('F j, Y',$value['date'])   ;
            //sort out the proper icon and build the full line
            $path=$configInfo['minutes_url'] . $value['filename'];
            $pathinfo = pathinfo($path);
            $extension = $pathinfo['extension'];
            switch ($extension){
                case "doc":
                case "docx" :
                   $min[$key]['icon']='/images/icon-doc.gif';
                break;
                case "pdf":
                    $min[$key]['icon']='/images/icon-pdf.gif'; 
                break;
                default:
                    $min[$key]['icon']='/images/icon-unknown.gif'; 
            } //switch
            $min[$key]['minlink'] = "<a href='$path'>Minutes</a>";  
        }
    }
    else $min='';
    return $min;
}
?>