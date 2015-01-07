<?php
require_once('includes/global.inc.php');

require_once('includes/xml_domit/xml_domit_rss_lite.php');

$tmpl=loadPage("rss","Blog",'blog');
showMenu("research_office");
$url ='http://blogs.mtroyal.ca/research/feed/rss2/';

//*******************CODE*************************

//instantiate rss document
$cacheDir = './';
$cacheTime = 3600;

$rssdoc =& new xml_domit_rss_document_lite($url, $cacheDir, $cacheTime);

//get total number of channels
$totalChannels = $rssdoc->getChannelCount();

//set max Channel
$maxChannels=1;

//set max ites per channelh
$maxItems=10;

if($totalChannels < $maxChannels)
$maxChannels=$totalChannels;

//If a full length item was specified....
if(isset($_REQUEST['fullchannel'])) {
    //Set order with this one first
    $channels=array(intval($_REQUEST['fullchannel']));
    $fullchannel=$_REQUEST['fullchannel'];$maxChannels--;
}
else 
    {$channels=array(); $fullchannel= -1;}
    
for($i=0; $i <$maxChannels; $i++){
        if($i!=$fullchannel) $channels[]=$i;
    }
//print_r ($channels);

//loop through each channel
foreach ($channels as $i) {
		//get reference to current channel
		$currChannel =& $rssdoc->getChannel($i);

		//echo channel info
//        echo "<h2><a href=\"" . $currChannel->getLink() . "\" target=\"_child\">" .
		$currChannel->getTitle() . "</a>";
//        echo "  " . $currChannel->getDescription() . "</h2>\n\n";

		//get total number of items
		$totalItems = $currChannel->getItemCount();

		//loop through each item or til max intem
		if($totalItems < $maxItems)
		$maxItems=$totalItems;
        
        if(isset($_REQUEST['fullitem'])) {
            //Set order with this one first
            $items=array(intval($_REQUEST['fullitem']));
            $fullitem=$_REQUEST['fullitem'];$maxItems--;
        }
        else 
            {$items=array(); $fullitem= -1;}
            
        for($i=0; $i <$maxItems; $i++){
                if($i!=$fullitem) $items[]=$i;
            }

        $index=0;
		foreach($items as $j) {
				//get reference to current item
				$currItem =& $currChannel->getItem($j);

				//echo item info                                  
				$rss_feed[$index]['link']=$currItem->getLink();
				$rss_feed[$index]['title']=$currItem->getTitle();
                if($j==$fullitem){
                    $fulltext=$currItem->getDescription();
                    $rss_feed[$index]['description']=$fulltext;
                }
                else {
                    $desc=$currItem->getDescription();
                    if(strlen($desc) >256)   {
                        for($x=256;$x<=280;$x++){
                            if(substr($desc,$x,1)==" ") break;   
                        }
				        $rss_feed[$index]['description']=substr($desc,0,$x).'<b>...</b>';
                    }
                    else $rss_feed[$index]['description']=$desc;
                }
//                echo "<p><a href=\"" . $currItem->getLink() . "\" target=\"_child\">" .
//                                $currItem->getTitle() . "</a> " . $currItem->getDescription() . "</p>\n\n";
                $index++;
		}
}







$tmpl->addVar('header', 'title', 'Blog');

if($rss_feed) {
	$tmpl->addRows('articles', $rss_feed);
}

$tmpl->displayParsedTemplate('page');

