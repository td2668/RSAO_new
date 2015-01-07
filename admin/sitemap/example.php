<?php
/**
 * 
 * PHP Class for sitemap
 *
 * Here are some testing examples which will demonstrate how to use this class
 * 
 * PHP versions : php5
 * 
 * @author 			Anil Gupta <gupta.thinker@gmail.com>
 * 					Web Engineer
 * @version			v0.0.1
 * 
 * @todo 
 * 
 * 1.Copy class.sitemap.php and example.php file in server for which you want to genrate sitemap
 * 2.Set properties according to your site and requirements
 * 3.run the file example.php from browser
 * 
 */

require_once('class.sitemap.php');


/**
 * Example - 1 
 *  
 */
$sitemap = new Sitemap();
$sitemap->hostUrl 	= 'http://research.mtroyal.ca/';
/**
 * Please specify your project directory here don't add '/' before or after of the dir name 
 * @example 'shoping'
 */
$projectDir 		= "" ;

/**
 * Filepath 
 * specify the path where you want to store the generated sitemap files
 * keep it '' if you want to store it in your projectDir
 * Please make sure that the filepath has write permission for apache users
 * @example 
 * $filePath = '';
 * $filePath = '/var/www/html/sitemaps';
 */

$filePath			= "/opt/lampp/htdocs/sitemap";
$sitemapTextfile     = $sitemap->getTextSiteMap($projectDir,$filePath);
$sitemapXmlfile 	= $sitemap->getXmlSiteMap($projectDir,$filePath);




