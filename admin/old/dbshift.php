<?php
  //Run through the entire active db and compare every item. First: how many have changed?
    //error_reporting(E_ALL);
    include("includes/config.inc.php");
    include("includes/functions-required.php");
    //$tmpl=loadPage("dbshift", 'DB Shift');
    /*
    $sql="SELECT * FROM cv_items WHERE 1 order by cv_item_id ";
    $cv_items=$db->getAll($sql);
    
    $fields=array('f1','f2','f3','f4','f5','f6','f7','f8','f9','f10','f11');
    
    foreach($fields as $field){
        $count=0;
        $miscount=0;
        echo("<br><br><br><b>Field $field</b><br>");
        foreach($cv_items as $cv_item){
            $sql="SELECT * FROM cas_cv_items WHERE
                cv_item_id=$cv_item[cv_item_id]";
            $item=$db->getRow($sql);
            if(!$item) $miscount++;
            elseif(strcmp($item[$field],$cv_item[$field])!=0) {
                echo("$item[$field] | $cv_item[user_id] | $cv_item[$field]<br>");
               $count++; 
            }
            
        }
        echo ("<br>Got $count <br>Missed: $miscount<br>");
    }
    /*
    AND (f1!=$cv_item[f1]
            )
    OR f2!=$cv_item[f2]
            OR f3!=$cv_item[f3]
            OR f4!=$cv_item[f4]
            OR f5!=$cv_item[f5]
            OR f6!=$cv_item[f6]
            OR f7!=$cv_item[f7]
            OR f8!=$cv_item[f8]
            
            OR f10!=$cv_item[f10]
            OR f11!=$cv_item[f11]
    
    
    //
    $sql="SELECT * FROM cv_items WHERE 1 order by cv_item_id   ";
    $cv_items=$db->getAll($sql);
    $fields=array('f1','f2','f3','f4','f5','f6','f7','f8','f9','f10','f11','current_par','web_show','report_flag');
    $cnew=$cupdate=0;
    foreach($cv_items as $cv_item){
            $sql="SELECT * FROM cas_cv_items WHERE
                cv_item_id=$cv_item[cv_item_id]";
            $item=$db->getRow($sql);
            if(!$item){
                //Write it to the cas table as a new item
                //First check if its blank
                $resetme=true;
                if($cv_item['f1']=='' &&
                    $cv_item['f4']=='' &&
                    $cv_item['f5']=='' &&
                    $cv_item['f6']=='' &&
                    $cv_item['f7']=='' &&
                    $cv_item['f8']=='' &&
                    $cv_item['f9']=='') $resetme=false;
                
                if($resetme){
                    //echo ("Writing new item ID# $cv_item[cv_item_id]<br>");
                    $cnew++;
                    $f1=mysql_real_escape_string($cv_item['f1']);
                    $f4=mysql_real_escape_string($cv_item['f4']);
                    $f5=mysql_real_escape_string($cv_item['f5']);
                    $f6=mysql_real_escape_string($cv_item['f6']);
                    $f7=mysql_real_escape_string($cv_item['f7']);
                    $f8=mysql_real_escape_string($cv_item['f8']);
                    $f9=mysql_real_escape_string($cv_item['f9']);
                    $sql="INSERT INTO cas_cv_items 
                    (`cv_item_id`,`user_id`,`cv_item_type_id`,`f1`,`f2`,`f3`,`f4`,`f5`,`f6`,`f7`,`f8`,`f9`,`f10`,`f11`,`current_par`,`web_show`,`report_flag`)
                    VALUES($cv_item[cv_item_id],
                            $cv_item[user_id],
                            $cv_item[cv_item_type_id],
                            '$f1',$cv_item[f2],$cv_item[f3],'$f4','$f5','$f6','$f7','$f8','$f9',$cv_item[f10],$cv_item[f11],
                            $cv_item[current_par],
                            $cv_item[web_show],
                            $cv_item[report_flag]
                            )";
                    //$result=$db->Execute($sql);
                    //var_dump($result);
                    echo("<br>");
                }
            }
            else {
                $resetme=false;
                foreach($fields as $field) if($cv_item[$field]!=$item[$field]) $resetme=true;
                if($cv_item['f1']=='' &&
                    $cv_item['f4']=='' &&
                    $cv_item['f5']=='' &&
                    $cv_item['f6']=='' &&
                    $cv_item['f7']=='' &&
                    $cv_item['f8']=='' &&
                    $cv_item['f9']=='') $resetme=false;
                    
                if($resetme) {
                    $cupdate++;
                    $cv_item['f1']=mysql_real_escape_string($cv_item['f1']);
                    $cv_item['f4']=mysql_real_escape_string($cv_item['f4']);
                    $cv_item['f5']=mysql_real_escape_string($cv_item['f5']);
                    $cv_item['f6']=mysql_real_escape_string($cv_item['f6']);
                    $cv_item['f7']=mysql_real_escape_string($cv_item['f7']);
                    $cv_item['f8']=mysql_real_escape_string($cv_item['f8']);
                    $cv_item['f9']=mysql_real_escape_string($cv_item['f9']);
                    
                    $sql="UPDATE cas_cv_items SET 
                    f1='$cv_item[f1]',
                    f2=$cv_item[f2],
                    f3=$cv_item[f3],
                    f4='$cv_item[f4]',
                    f5='$cv_item[f5]',
                    f6='$cv_item[f6]',
                    f7='$cv_item[f7]',
                    f8='$cv_item[f8]',
                    f9='$cv_item[f9]',
                    f10=$cv_item[f10],
                    f11=$cv_item[f11],
                    current_par=$cv_item[current_par],
                    web_show=$cv_item[web_show],
                    report_flag=$cv_item[report_flag],
                    converted=0
                    WHERE cv_item_id=$cv_item[cv_item_id]";
                    //$result=$db->Execute($sql);
                   //echo("$sql <br>");
                   
                }
            }
            flush();
    }
    echo("<br><br>Wrote $cnew New, $cupdate Updates<br>");
    
    */
    
    /*
    $sql="SELECT * from users2 WHERE 1 order by user_id";
    $users=$db->getAll($sql);
    foreach($users as $user){
        $sql="SELECT * from users where user_id=$user[user_id]";
        $user2=$db->getRow($sql);
        if(!$user2) echo ("Missing $user[user_id] - $user[last_name], $user[first_name] <br>");
    }
    */
    $sql="SELECT * FROM cas_cv_items LEFT JOIN users on (cas_cv_items.user_id=users.user_id) LEFT JOIN users_ext on (users.user_id=users_ext.user_id) WHERE 
    
    (cv_item_type_id='74'

    )
    AND (n_teaching=0 AND n_scholarship=0 AND n_service=0) AND users_ext.tss>=0 AND report_flag=1";
    $items=$db->getAll($sql);
    echo ("$sql <br>");
    echo ("Got ". count($items) ."<br");
    foreach($items as $item){
        //$sql="UPDATE cas_cv_items SET n_teaching=1 WHERE cv_item_id=$item[cv_item_id]";
        //$sql="UPDATE cas_cv_items SET n_scholarship=1 WHERE cv_item_id=$item[cv_item_id]";
        $sql="UPDATE cas_cv_items SET n_service=1 WHERE cv_item_id=$item[cv_item_id]";
        echo ("$sql<br>$item[last_name],$item[first_name] $item[n09]-$item[n18] $item[n01]<br><br>");
        $result=$db->Execute($sql);
    }
?>
