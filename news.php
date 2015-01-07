<?php
require_once('includes/global.inc.php');
$id=intval($_GET["id"]);
if($id>0) {
	$sql="SELECT news_id, source, title, synopsis, description
		  FROM news
		  WHERE news_id=$id
		  ";


	$tmpl=loadPage("news_article",'News and Announcements',"news");

	$news=$db->GetAll($sql);
	if($news) {
		foreach($news as $k=>$v) {
			if($news[$k]["title"]!="") $news[$k]["title"]="<b>".$news[$k]["title"]."</b><br />";
			if($news[$k]["source"]!="") $news[$k]["source"]="<small>(<i>source: ".$news[$k]["source"]."</i>)</small><br />";
			if($news[$k]["description"]=="") $news[$k]["description"]=$news[$k]["synopsis"];
			$news[$k]["description"]=nl2br($news[$k]["description"]);
		}
		$tmpl->addRows('page', $news);
	}

}
else {
	$sql="SELECT news_id, source, title, synopsis
		  FROM news
		  WHERE 1
		  ORDER BY post_date DESC
		  LIMIT 30";


	$tmpl=loadPage("news_index",'News and Announcements',"news");

	$news=$db->GetAll($sql);
	if($news) {
		foreach($news as $k=>$v) {
			if($news[$k]["title"]!="") $news[$k]["title"]="<b>".$news[$k]["title"]."</b><br />";
			if($news[$k]["source"]!="") $news[$k]["source"]="<small>(<i>source: ".$news[$k]["source"]."</i>)</small><br />";
		}
		$tmpl->addRows('articles', $news);
	}
}

$tmpl->displayParsedTemplate('page');

/*
function brief($text) {
  return str_split(nl2br($text), 100); //Only first 100 chars
}
function one_or_two($text) {
  return 2 - ($text % 2);
}*/

?>