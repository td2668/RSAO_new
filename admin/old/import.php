#!/usr/local/bin/php
<?php
//$filepath = "/home/html_root/htdocs/";
$filepath = "../";
include("{$filepath}admin/includes/config.inc.php");
include("{$filepath}includes/functions-required.php");

if (!$infile = fopen("{$filepath}admin/projects.txt","r")) die("Infile Is Not Readable");

while (!feof ($infile)) {
    $buffer = fgets($infile);
    $pieces=explode("\t",$buffer);
//	while (stripos($pieces[1]
	$values = array('null', $pieces[1], $pieces[3], $pieces[4], 0, "", $pieces[8], $pieces[2], $pieces[5], 1, $pieces[6],0);
	$result = mysqlInsert("projects", $values);
	if($result != 1) echo "Problem inserting project $pieces[1]\n;";
}

?>