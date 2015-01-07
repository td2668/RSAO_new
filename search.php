<?php
require_once('includes/global.inc.php');

$tmpl=loadPage("search", 'Search Results');
showMenu("index");



$tmpl->displayParsedTemplate('page');
?>
