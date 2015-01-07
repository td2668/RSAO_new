<?php
/** 
* Import of HR personell data for import to Blackberry
* 
* Parses through the user database and matches up values with a separate, combined import table.
* The table can be exported as CSV and matched directly with the BB Sync to ASCII table function
* Since that function appears to require a row designating field names, the export to CSV has to be done here.
* 
* Normal use sequence is upload, import, then load, then dump. 

* @package orsadmin
*/

include("includes/config.inc.php");
include("includes/functions-required.php");
include("includes/class-template.php");





$template = new Template;
include("templates/template-header.html");
//error_reporting(E_ALL);
$flagtext='';
$success='';
$blankarray=array('null','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','');
$indexarray=Array
(
    'First_Name' => 0,
    'Middle_Name' => 1,
    'Last_Name' => 2,
    'Title' => 3,
    'Company_Name' => 4,
    'Work_Phone' => 5,
    'Work_Phone2' => 6,
    'Home_Phone' => 7,
    'Home_Phone2' => 8,
    'Other_Phone' => 9,
    'Work_Fax' => 10,
    'Mobile_Phone' => 11,
    'PIN' => 12,
    'Pager' => 13,
    'Email_Address_1' => 14,
    'Email_Address_2' => 15,
    'Email_Address_3' => 16,
    'Address1' => 17,
    'Address2' => 18,
    'Address3' => 19,
    'City' => 20,
    'State/Prov' => 21,
    'Zip/Postal_Code' => 22,
    'Country' => 23,
    'Home_Address1' => 24,
    'Home_Address2' => 25,
    'Home_Address3' => 26,
    'Home_City' => 27,
    'Home__State/Prov' => 28,
    'Home_Zip/Postal_Code' => 29,
    'Home_Country' => 30,
    'Notes' => 31,
    'Interactive_Handheld' => 32,
    '1-way_Pager' => 33,
    'User_Defined_1' => 34,
    'User_Defined_2' => 35,
    'User_Defined_3' => 36,
    'User_Defined_4' => 37,
    'Salutation' => 38,
    'Web_Address' => 39,
    'Direct_Connect' => 40,
    'Categories' => 41,
    'Birthday' => 42,
    'Anniversary' => 43,
    'Nickname' => 44,
    'Mobile_Phone_2' => 45,
    'Home_Fax' => 46
);



if (isset($_REQUEST['load'])){
    /**
    * Main compile routine to gather info in user tables and compare with bb table
    */
    $comparray=array(
        'first_name'=>'First_Name',
        'last_name'=>'Last_Name',
        'title'=>'Title',
        'phone'=>'Work_Phone',
        'email'=>'Email_Address_1',
        'user_id'=>'User_Defined_2'
    );
    $output=$output2='';
    $users=mysqlFetchRows('users left join profiles using (user_id)','1 order by last_name, first_name');
    //echo count($users);
    foreach($users as $user) {
        if($user['email']!= '' && $user['email']!='tdavis@mtroyal.ca') {
            $result=mysqlFetchRow('bb_contacts'," Email_Address_1 = '$user[email]' OR Email_Address_2 = '$user[email]' OR User_Defined_2 = '$user[user_id]' ",'bb_contact_id,First_Name,Last_Name,Title,Work_Phone,Email_Address_1,User_Defined_1,User_Defined_2,Company_Name');
            if(!is_array($result)) {
                $output.='Did not find '.$user['last_name'].', '.$user['first_name'].". Inserting record.<br />\n"; 
                $record=array();
                foreach($comparray as $from=>$to) $record[$to]= addslashes($user[$from]);
                $record['Company_Name']='MRU';
                $dept=mysqlFetchRow('departments',"department_id=$user[department_id]");
                if(is_array($dept))
                      $record['User_Defined_1']=$dept['name'];
                else 
                    $record['User_Defined_1']='';
                $res=mysqlInsert('bb_contacts',$blankarray);
                if($res!=1)$output2.="Error on Insert: $res <br>";
                $id=mysql_insert_id();
                $res=mysqlUpdate('bb_contacts',$record,"bb_contact_id=$id");
                if($res!=1)$output2.="Error on Update: $res <br>";
                
            }
            else {
                //The big comparison
                $outputtemp='';
                
                foreach($comparray as $from=>$to) {
                    if(rtrim($user[$from]) != rtrim($result[$to]) && $user[$from] != '') {
                        $outputtemp.= "Changed $to: $result[$to] to $user[$from]<br />\n";
                        $result[$to]= $user[$from];
                        
                    }
                    
                }
                $dept=mysqlFetchRow('departments',"department_id=$user[department_id]");
                $targname='MRU';
                if(is_array($dept)) $targname=$dept['name'] . ', ' . $targname;

                if($result['Company_Name']!= $targname) {
                    $outputtemp.= "Changed Company Name from $result[Company_Name] to $targname<br />\n";
                    $result['Company_Name']=$targname;
                }
                
                if($outputtemp != '') {
                    $output2.="<b>$user[last_name], $user[first_name]</b><br />\n";
                    $output2.=$outputtemp;
                    $output2.='---<br>';
                }
                
                
                $result['Last_Name']=addslashes($result['Last_Name']);
                $result['First_Name']=addslashes($result['First_Name']);
                $res=mysqlUpdate('bb_contacts',$result,"bb_contact_id=$result[bb_contact_id]");
                if($res != 1) $output2.="<b>Error updating: $res </b><br />\n";
                
            }
        }
        
    }
    if($output=='') $output='No new records to insert';
    if($output2=='') $output2='No changes found';
    $hasharray = array('success'=>$success, 'output'=>$output, 'output2'=>$output2);
    $filename = 'templates/template-bbimport_load.html';
    $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
    echo $parsed_html_file;
}




if(isset($_REQUEST['upload'])){
    if(isset($_FILES['file'])) if($_FILES['file']['name'] != ""){
        if($_FILES["file"]["error"] > 0) {
            $success.="Error uploading file-Return Code: " . $_FILES["file"]["error"] ;
            $filename="";
        }
        else {
            move_uploaded_file($_FILES["file"]["tmp_name"], $configInfo['file_root'] . 'admin/documents/bb_upload.csv');
            $filename=$_FILES["file"]["name"]; 
            $success.="Uploaded file. ";         
        }
    }
    
        $hasharray = array('success'=>$success);
        $filename = 'templates/template-bbimport_upload.html';
        $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
        echo $parsed_html_file;
      

}


if (isset($_REQUEST['import'])){
   //Compare the uploaded bb dump with the  file 
    
    
    $comparray=array(
        'first_name'=>'First_Name',
        'last_name'=>'Last_Name',
        'title'=>'Title',
        'phone'=>'Work_Phone',
        'email'=>'Email_Address_1',
        'user_id'=>'User_Defined_2'
    );
    $output=$output2='';
    
    //Load CSV file into array
    if($fileh=fopen($configInfo['file_root'] . 'admin/documents/bb_upload.csv',"r")){
        $row=0;
        while (($import[]=fgetcsv($fileh))!==FALSE) {
            $row++;
        }
        $success="Imported $row rows";
        //$output="<pre>";
        //$output.=print_r($import);
        //$output.="</pre>";
        if($row > 0){
            //lose the header row
            array_shift($import);
            foreach($import as $item) if(isset($item[1])){
                $item[0]= addslashes($item[0]);
                $item[2]=addslashes($item[2]);
                $result=mysqlFetchRow('bb_contacts'," (Email_Address_1 != '' AND Email_Address_1 = '$item[14]') OR (Email_Address_1 != '' AND Email_Address_1 = '$item[15]') OR (Email_Address_2 !='' AND Email_Address_2 = '$item[14]') OR (Email_Address_2 !='' AND Email_Address_2 = '$item[15]') OR (User_Defined_2 != '' AND User_Defined_2 = '$item[35]') ");
                if(!is_array($result)) {
                    //second try - match name. But this could be a duplicate, so flag
                    
                    $result2=mysqlFetchRow('bb_contacts'," (First_Name != '' AND First_Name = '$item[0]') AND (Last_Name !='' AND Last_Name = '$item[2]')");
                    if(!is_array($result2)) {
                        $output.='Did not find '.$item[2].', '.$item[0].". Inserting record.<br />\n";
                        $newuser=array( 'null',
                                        $item[0],
                                        $item[1],
                                        $item[2],
                                        $item[3],
                                        addslashes($item[4]),
                                        $item[5],
                                        $item[6],
                                        $item[7],
                                        $item[8],
                                        $item[9],
                                        $item[10],
                                        $item[11],
                                        $item[12],
                                        $item[13],
                                        $item[14],
                                        $item[15],
                                        $item[16],
                                        $item[17],
                                        $item[18],
                                        $item[19],
                                        $item[20],
                                        $item[21],
                                        $item[22],
                                        $item[23],
                                        $item[24],
                                        $item[25],
                                        $item[26],
                                        $item[27],
                                        $item[28],
                                        $item[29],
                                        $item[30],
                                        $item[31],
                                        $item[32],
                                        $item[33],
                                        $item[34],
                                        $item[35],
                                        $item[36],
                                        $item[37],
                                        $item[38],
                                        $item[39],
                                        $item[40],
                                        $item[41],
                                        $item[42],
                                        $item[43],
                                        $item[44],
                                        $item[45],
                                        $item[46]
                                        );
                        $resp=mysqlInsert('bb_contacts',$newuser);
                        if($resp!=1) $output.= "Error inserting: $resp <br>";
                    }
                    else {
                        //$output.='Found '.$item[2].', '.$item[0]." by name association only. Using existing record.<br />\n";
                       // $output.="<pre>";
                       // $output.= print_r($result2,true);
                       // $output.="</pre><br><pre>";
                       // $output.= print_r($item,true);
                       // $output.="</pre>";
                    }
                }
                //Do the matchups
                
                //Found a match using email or user id, so I am sure this is the person
                if(is_array($result) || is_array($result2)){
                    if(isset($result2)) if(is_array($result2)) $result=$result2;
                    unset($result2); $email1=''; $email2='';
                    
                    //First check if emails are messed up
                    if($result['Email_Address_1'] != '') $email1=$result['Email_Address_1'];
                    if($result['Email_Address_2'] != '')
                        if($email1!='') $email2=$result['Email_Address_2'];
                        else $email1=$result['Email_Address_2'];
                    if($item[$indexarray['Email_Address_1']]==$email1 || $item[$indexarray['Email_Address_1']]==$email2) ; //do nothing; I'm good
                    elseif($email1=='') $email1=$item[$indexarray['Email_Address_1']];
                    else $email2=$item[$indexarray['Email_Address_1']];
                    
                    if($item[$indexarray['Email_Address_2']]==$email1 || $item[$indexarray['Email_Address_2']]==$email2) ; //do nothing; I'm good
                    elseif($email1=='') $email1=$item[$indexarray['Email_Address_2']];
                    else $email2=$item[$indexarray['Email_Address_2']];
                    
                    if($email1 != $result['Email_Address_1'] || $email2 != $result['Email_Address_2']){
                        $output.="Now shows Email1 as $email1 and Email2 as $email2 <br>";
                    $resp=mysqlUpdate('bb_contacts',array("Email_Address_1"=>$email1, 'Email_Address_2'=>$email2),"bb_contact_id='$result[bb_contact_id]'");
                            if($resp!=1) $output.='Error updating: '. $resp . '<br>';
                    }
                    //Check each item that mis-matches and verify.
                    $checklist=array('First_Name','Last_Name','Work_Phone','Home_Phone','Mobile_Phone','User_Defined_1','User_Defined_2');
                    foreach($checklist as $checkitem){
                        
                        if($result[$checkitem]!=stripslashes($item[$indexarray[$checkitem]]) && $item[$indexarray[$checkitem]]!=''){
                            $output.="For user {$result['First_Name']} {$result['Last_Name']} replace {$result[$checkitem]} with {$item[$indexarray[$checkitem]]} <br>";
                            $resp=mysqlUpdate('bb_contacts',array("$checkitem"=>$item[$indexarray[$checkitem]]),"bb_contact_id='$result[bb_contact_id]'");
                            if($resp!=1) $output.='Error updating: '. $resp . '<br>';
                        }
                    }
                }
            }//foreach
        }// row > 0
    } // file exists
    else $success='Unable to open csv file';
    
    
    /*
    //echo count($users);
    foreach($users as $user) {
        if($user['email']!= '' && $user['email']!='tdavis@mtroyal.ca') {
            $result=mysqlFetchRow('bb_contacts'," Email_Address_1 = '$user[email]' OR Email_Address_2 = '$user[email]' OR User_Defined_2 = '$user[user_id]' ",'bb_contact_id,First_Name,Last_Name,Title,Work_Phone,Email_Address_1,User_Defined_1,User_Defined_2,Company_Name');
            if(!is_array($result)) {
                $output.='Did not find '.$user['last_name'].', '.$user['first_name'].". Inserting record.<br />\n"; 
                $record=array();
                foreach($comparray as $from=>$to) $record[$to]= addslashes($user[$from]);
                $record['Company_Name']='MRU';
                $dept=mysqlFetchRow('departments',"department_id=$user[department_id]");
                if(is_array($dept))
                      $record['User_Defined_1']=$dept['name'];
                else 
                    $record['User_Defined_1']='';
                $res=mysqlInsert('bb_contacts',$blankarray);
                if($res!=1)$output2.="Error on Insert: $res <br>";
                $id=mysql_insert_id();
                $res=mysqlUpdate('bb_contacts',$record,"bb_contact_id=$id");
                if($res!=1)$output2.="Error on Update: $res <br>";
                
            }
            else {
                //The big comparison
                $outputtemp='';
                
                foreach($comparray as $from=>$to) {
                    if(rtrim($user[$from]) != rtrim($result[$to]) && $user[$from] != '') {
                        $outputtemp.= "Changed $to: $result[$to] to $user[$from]<br />\n";
                        $result[$to]= $user[$from];
                        
                    }
                    
                }
                $dept=mysqlFetchRow('departments',"department_id=$user[department_id]");
                $targname='MRU';
                if(is_array($dept)) $targname=$dept['name'] . ', ' . $targname;

                if($result['Company_Name']!= $targname) {
                    $outputtemp.= "Changed Company Name from $result[Company_Name] to $targname<br />\n";
                    $result['Company_Name']=$targname;
                }
                
                if($outputtemp != '') {
                    $output2.="<b>$user[last_name], $user[first_name]</b><br />\n";
                    $output2.=$outputtemp;
                    $output2.='---<br>';
                }
                
                
                $result['Last_Name']=addslashes($result['Last_Name']);
                $result['First_Name']=addslashes($result['First_Name']);
                $res=mysqlUpdate('bb_contacts',$result,"bb_contact_id=$result[bb_contact_id]");
                if($res != 1) $output2.="<b>Error updating: $res </b><br />\n";
                
            }
        }
        
    }
    */
    if($output=='') $output='No new records to insert';
    if($output2=='') $output2='No changes found';
    $hasharray = array('success'=>$success,'output'=>$output);
    $filename = 'templates/template-bbimport_load.html';
    $parsed_html_file = $template->loadTemplate($filename,$hasharray,"HTML");
    echo $parsed_html_file;
}








if (isset($_REQUEST['dump'])){
    /** 
    * Dump bb_contacts file to a properly formatted CSV for BB Import/Sync
    */
    
    $contacts=mysqlFetchRows('bb_contacts','1');
    //Write the standard header
    if(!$outfile=fopen($configInfo['file_root'] . 'admin/documents/bb_contacts.csv','w')) die('Cant open file for writing');
    $header=array(  'First Name',
                    'Middle Name',
                    'Last Name',
                    'Title',
                    'Company Name',
                    'Work Phone',
                    'Work Phone2',
                    'Home Phone',
                    'Home Phone2',
                    'Other Phone',
                    'Work Fax',
                    'Mobile Phone',
                    'PIN',
                    'Pager',
                    'Email Address 1',
                    'Email Address 2',
                    'Email Address 3',
                    'Address1',
                    'Address2',
                    'Address3',
                    'City',
                    'State/Prov',
                    'Zip/Postal Code',
                    'Country',
                    'Home Address1',
                    'Home Address2',
                    'Home Address3',
                    'Home City',
                    'Home State/Prov',
                    'Home Zip/Postal Code',
                    'Home Country',
                    'Notes',
                    'Interactive Handheld',
                    '1-way Pager',
                    'User Defined 1',
                    'User Defined 2',
                    'User Defined 3',
                    'User Defined 4',
                    'Salutation',
                    'Web Address',
                    'Direct Connect',
                    'Categories',
                    'Birthday',
                    'Anniversary',
                    'Nickname',
                    'Mobile Phone 2',
                    'Home Fax');
    fputcsv($outfile,$header,',');
    
    foreach($contacts as $contact) {
        $values=array(  $contact['First_Name'],
                        $contact['Middle_Name'],
                        $contact['Last_Name'],
                        $contact['Title'],
                        $contact['Company_Name'],
                        $contact['Work_Phone'],
                        $contact['Work_Phone2'],
                        $contact['Home_Phone'],
                        $contact['Home_Phone2'],
                        $contact['Other_Phone'],
                        $contact['Work_Fax'],
                        $contact['Mobile_Phone'],
                        $contact['PIN'],
                        $contact['Pager'],
                        $contact['Email_Address_1'],
                        $contact['Email_Address_2'],
                        $contact['Email_Address_3'],
                        $contact['Address1'],
                        $contact['Address2'],
                        $contact['Address3'],
                        $contact['City'],
                        $contact['State/Prov'],
                        $contact['Zip/Postal_Code'],
                        $contact['Country'],
                        $contact['Home_Address1'],
                        $contact['Home_Address2'],
                        $contact['Home_Address3'],
                        $contact['Home_City'],
                        $contact['Home__State/Prov'],
                        $contact['Home_Zip/Postal_Code'],
                        $contact['Home_Country'],
                        $contact['Notes'],
                        $contact['Interactive_Handheld'],
                        $contact['1-way_Pager'],
                        $contact['User_Defined_1'],
                        $contact['User_Defined_2'],
                        $contact['User_Defined_3'],
                        $contact['User_Defined_4'],
                        $contact['Salutation'],
                        $contact['Web_Address'],
                        $contact['Direct_Connect'],
                        $contact['Categories'],
                        $contact['Birthday'],
                        $contact['Anniversary'],
                        $contact['Nickname'],
                        $contact['Mobile_Phone_2'],
                        $contact['Home_Fax']
                        
                        );
                        fputcsv($outfile,$values,',');
    }
    
    fclose($outfile);
    echo ("Download the file <a href='$configInfo[url_root]/admin/documents/bb_contacts.csv'> here</a>.");
}
//-- Footer File
include("templates/template-footer.html");

//matches two files intelligently
//the $db array is indexed by name, while the $bb array has to be looked up using the index
function matchusers($db,$bb){
    global $indexarray;
    global $output;
    if($db['First_Name']!=$bb[$indexarray['First_Name']]) $output.='Replacing '. $db['First_Name'].' '.$db['Last_Name'] .' with '. $bb[$indexarray['First_Name']] .' '.$bb[$indexarray['Last_Name']] .'<br>';
    $result['First_Name']='';
    return $result;
}

?>