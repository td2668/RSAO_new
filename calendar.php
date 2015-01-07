<?php
require_once('includes/global.inc.php');

$tmpl=loadPage("calendar", 'Presentation Calendar');
showMenu("research_office");

$tmpl->displayParsedTemplate('header');
$tmpl->displayParsedTemplate('leadin');
require_once('cal/index.php');
$tmpl->displayParsedTemplate('leadout');
$tmpl->displayParsedTemplate('footer');
?>