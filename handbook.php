<?php
require_once('includes/global.inc.php'); 



if($_GET["print"]) $print=$_GET["print"];
if($_GET["chapter"]) $chapter=$_GET["chapter"];


if(!isset($print)){
	if(!isset($chapter)
		or !(is_numeric($chapter) && $chapter>=0 && $chapter <=10))
		$chapter="0";
	$text=file_get_contents("html/handbook{$chapter}.html");

	$tmpl=loadPage("handbook","Research Handbook");
	showMenu("handbook",$tmpl);
	$tmpl->addVar("page","more",$text);
	$tmpl->displayParsedTemplate();
}
else {
	echo "<html><head><title>Research Handbook</title>
	<link href='text/handbook.css' rel='stylesheet' type='text/css'>
	</head>
	<body>";
	for($x=1;$x<=10;$x++) include("html/handbook{$x}.html");
	echo "</body> 
	</html>";
}
?> 

