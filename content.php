<?php
require_once('includes/global.inc.php');

global $sitemapXML;

$page = (isset($_GET["page"])) ? $_GET["page"] : '';
cleanUp($page);

$sitemapXML = simplexml_load_file('sitemap.xml');

$nodeList = $sitemapXML->xpath("//$page");

if ($nodeList and count($nodeList)) {
    $node = reset($nodeList);
    $type = $node["type"];
    if ($node["protected"]=="yes" and sessionLoggedin()==false) {
        $tmpl = loadPage("accessdenied",(string)$node,$page);
    } else if ($type == "section") {
        global $XMLsitemapParsed;
        $tmpl = loadPage("section",(string) $node,$page);
        $pages = array();
        foreach ($node as $key => $item) {
            $built = parseXMLsitemapNode($key,$item);
            if ($built["protected"]=="yes" and sessionLoggedin()==false) continue;
            $page = array();
            $page["title"] = (string) $item;
            $page["url"] = $built['menuitemurl'];
            $pages[] = $page;
        }
        $tmpl->addRows("pages",$pages);
        $tmpl->addVar("page","SECTIONNAME",(string) $node);
    } else {
        if (file_exists("html/{$page}.html")) {
            $tmpl = loadPage($page);
        } else {
            $tmpl = loadPage("stub",(string)$node,$page);
        }
    }
} else {
    $tmpl = loadPage($page);
} 
$nav = (isset($_GET["menu"])) ? $_GET["menu"] : '';
//cleanUp($nav);
//showMenu($nav);

$tmpl->displayParsedTemplate('page');
?>