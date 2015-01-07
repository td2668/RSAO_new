<?php

error_reporting(E_ALL);
  //require_once("includes/mrclib.php");
  define("EAS_SERV", "ldap-dmz.mtroyal.ca");
  define("EAS_PORT", 389);
    define("EAS_BINDDN", "uid=readonly,o=MRC");
    define("EAS_BINDPW", "readingrailroad\$200");
    define("EAS_BASE", "o=MRC");

if (!$logfile = fopen("ldap_log.txt","w+")) die("Log Is Not Writeable");

echo("Starting loop");
$studentcount=$empcount=0;
for ($y=97; $y<=122; $y++){ 
set_time_limit(220); //122
for ($x=97; $x<=122; $x++){  
    sleep(2);  
    $uid=chr($y).chr($x).'*';
    echo("Searching $uid");
    $ldap_return = array("uid","givenName","sn","employeeNumber","mail");
    
    if(!($conn = ldap_connect(EAS_SERV, EAS_PORT))) {
        $terrmsg = ldap_error($conn);
        echo("mrclib_ldapinfo: could not connect to ldap: $terrmsg");
        ldap_close($conn);
    }
    else echo("Connected  ");

    if(!($r = ldap_bind($conn, EAS_BINDDN, EAS_BINDPW))) {
        $terrmsg = ldap_error($conn);
        echo("mrclib_ldapinfo: could not bind to ldap: $terrmsg");
        ldap_close($conn);
    }
    else echo("Bound  ");

    $search = "(uid=$uid)";

    if(!($sr = ldap_search($conn,EAS_BASE,$search,$ldap_return))) {
        $terrmsg = ldap_error($conn);
        echo("mrclib_ldapinfo: could not search for $search: $terrmsg");
        ldap_close($conn);
    }
    else echo("Searched   ");

    
    
    if(ldap_count_entries($conn, $sr) == 0) {
        $terrmsg = ldap_error($conn);
        echo("mrclib_ldapinfo: could not get LDAP information: no matches found for $search: $terrmsg");
        ldap_close($conn);
    }
    else echo("Got " .ldap_count_entries($conn, $sr));
    
    {
        //start looping
        //echo "<pre>";
        $info=ldap_get_entries($conn, $sr);
        if($info) {
            //echo "<table>";
            for ($entry=0; $entry < $info['count']; $entry++){
                $student='';
                if($info[$entry]['mail'][0] !="")
                    if(preg_match("/mymru/",$info[$entry]['mail'][0])) {$student='student'; $studentcount++;} 
                        else {$student='employee';$empcount++;}
                if($student != ''){
                    fwrite($logfile,$info[$entry]['givenname'][0].",".$info[$entry]['sn'][0].",".$info[$entry]['mail'][0] .",".$info[$entry]['employeenumber'][0] .",".$student ."\n");
                }
            }
            //echo("</table>");
            
        }
        //echo "</pre><br>";
        
        //echo "<pre>";
       // print_r(ldap_get_entries($conn,$sr));
       // echo "</pre><br>";
    }
    
} echo "$y<br>"; ob_flush();flush();
}
fclose($logfile);
echo "Total Students= ". $studentcount."<br>Total Employees= ".$empcount."<br>";
?>
