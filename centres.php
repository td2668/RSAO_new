<?php
require_once('includes/global.inc.php');

$_GET["menu"]="centres";
$tmpl=loadPage("centres", 'Research Centres');

$html = simplexml_load_file('./html/centres.xml');
$id = (isset($_GET['id'])) ? (int) $_GET['id'] : 0;
$tmpl->addVar('page_single', 'title', (string)$html->center[$id]->title);
$tmpl->addVar('page_single', 'xml', (string)$html->center[$id]->p);
$tmpl->displayParsedTemplate('page');

?>