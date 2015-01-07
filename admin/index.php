<?php



include_once("includes/config.inc.php");
include_once("includes/functions-required.php");
include("includes/class-template.php");

$tmpl=loadPage("index", 'ORS Admin');

$tmpl->displayParsedTemplate('page');

