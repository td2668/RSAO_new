<?php
/**
 * 
 * PHP Class for sitemap
 *
 * This class lets help you to generate a site map in XML and Text format
 * You can generate the site map for defferent search engines like Yahoo,Google,msn etc.
 * Your can directly submit these generated site map to search engines for website listing and crawling purpose
 * 
 * PHP versions : php5
 * 
 * @author 			Anil Gupta <gupta.thinker@gmail.com>
 * 					Web Engineer
 * @version			v0.0.1
 * @licence			GNU General Public License (GPL) 
 * @modifiedby		$LastChangedBy: Anil Gupta
 * 
 */
$arrayMap = array();
class Sitemap 
{
	/**
	 * Extention of the different files that we want to list in sitemap.
	 * 
	 * @example array("php","html","htm","xml","php3",'tpl')
	 * @var array
	 */
	public 	$fileExtensions = array("php","html","htm","xml","php3",'tpl');
	
	/**
	 * Hostname which will add in your sitemap 
	 *
	 * @example 'www.thinkerminds.com'
	 * @var string
	 */
	public  $hostUrl		= '';
	
	/**
	 * root directory of your server.
	 * @example /var/www/html
	 * @var string
	 */
	public  $rootDir		= "";
	
	/**
	 * Name of your project directory in which your all files resides.
	 *
	 * @example sitemap
	 * @var string
	 */
	private $siteDir		= "";
	
	/**
	 * Contains all the files path that are in sitemap.
	 *
	 * @var array
	 */
	private $mapArray		= array();
	
	/**
	 * Contains the final text string for sitemap.txt file.
	 *
	 * @var string
	 */
	private $mapTextString	= '';
	
	/**
	 * Contanin the final xml string for sitemap.xml file.
	 *
	 * @var string
	 */
	private $mapXmlString	= '';
	
	//public  $changeFreq		= 'Weekly';   // Availabe option: (always,hourly,daily,weekly,monthly,yearly,never)
	//public  $priority		= 0.5;		//Valid Range : (0.0 to 1.0)
	
	/**
	 * Name of the sitemap file
	 * 
	 * @example sitemap
	 * @var string
	 */
	public  $fileName		= 'sitemap';
	
	/**
	 * path where the generated sitemap will store 
	 * Please make sure that this path have appropriate write permision
	 *
	 * @var string
	 */
	public  $filePath		= '';
	
	/**
	 * specify the end of line character (for windows - \n\r,for linux - \n )
	 *
	 * @var string
	 */
	private  $eol			= '';
	
	
	/**
	 * Constructor of the class
	 *
	 * @param string $rootDir=null (if nothing is passed than $_SERVER['DOCUMENT_ROOT'] will be consider as root dir )
	 * 
	 */
	public function __construct($rootDir=null) {
		if ($rootDir != null) {
			$this->rootDir = $rootDir;
		} else {
			$this->rootDir = $_SERVER['DOCUMENT_ROOT'];
		}
		if ($this->hostUrl=='') {
			$this->hostUrl = 'http://'.$_SERVER['HTTP_HOST'].'/';
		}
		$this->eol=(strpos( $_ENV[ "OS" ], "Win" ) !== false )?"\n\r":"\n";
	}
	/**
	 * generate the sitemap array 
	 *
	 * @desc It is a recursive method that will read each dir and stores the file in that directory to the sitemap array
	 * @param string $siteDir
	 * @return array
	 */
	private function generate($siteDir) {
		
		if ($siteDir == '') {
			$lastDir = $this->rootDir;
			$nextDir = '';
		} else {
			$lastDir = $this->rootDir."/".$siteDir;
			$nextDir = $siteDir.'/';
		}
		if (!is_dir($lastDir) || !is_readable($lastDir)) {	
			die($lastDir." : Invalid Directory or can't readable");
		}
		$dirHandle	=	opendir($lastDir);
		while ($file = readdir($dirHandle)) {
			if ($file != "." AND $file != "..") {
				if (is_dir($lastDir."/".$file)) {
					$arrayMap	= array_merge((array)$arrayMap,(array)$this->generate($nextDir.$file));
				} else {
					$file_info = pathinfo($file);
					if (in_array(strtolower($file_info["extension"]),(array) $this->fileExtensions)) {
						$arrayMap[]['loc'] = $siteDir."/".$file;
					}
				}
			}
		}
		return $arrayMap;
	}
	
	/**
	 * Generate the sitemap in text file format 
	 *
	 * @param string $siteDir ( If your project is in root directory than supply '' else your project directory)
	 * @param string $filPath ( Supply the path where you want to store the generated sitemap file if nothing 
	 * 			is passed then $rootDir will be consider as filePath)
	 * @return mixed (If file will generated successfuly than path of the sitemap will returns else false )
	 */
	public function getTextSiteMap($siteDir,$filPath=''){
		
		$lastDir 		= ($siteDir == '') ? $this->rootDir : $this->rootDir."/".$siteDir;
		//filePath is '' then $lastDir will consider as filepath 
		$this->filePath = ($filPath == '') ? $lastDir : $filPath;
		
		$this->mapArray = $this->generate($siteDir);
		if ($this->mapArray) {
			for($i=0,$length=count($this->mapArray);$i<$length;$i++) {
				$this->mapTextString .= $this->hostUrl.$this->mapArray[$i]['loc'].$this->eol;
			}
		}
		if (is_writable($this->filePath)) {
			$bytes = @file_put_contents($this->filePath."/".$this->fileName.'.txt',$this->mapString);
			if ($bytes) {
				return $this->filePath."/".$this->fileName.'.txt';
			} else {
				return false;
			}
		} else {
			die("Could not have write permission on ".$this->filePath);
		}
	}
	
	/**
	 * Generate the sitemap in xml file format 
	 *
	 * @param string $siteDir ( If your project is in root directory then supply '' else your project directory)
	 * @param string $filPath ( Supply the path where you want to store the generated sitemap file if nothing 
	 * 			is passed then $rootDir will be consider as filePath)
	 * @param string $changeFreq ( Frequency of the page modification )
	 * 		  default weekly    
	 * 		  availabe options ( always,hourly,daily,weekly,monthly,yearly,never )
	 * @param float $priority (Priority of the page among same site )
	 * 		  default 0.5
	 * 		  availabe options (0.0 to 1.0)
	 * @return mixed (If file will generated successfuly than path of the sitemap will returns else false )
	 */
	public function getXmlSiteMap($siteDir,$filPath='',$changeFreq='weekly',$priority=0.5){
		
		$lastDir 		= ($siteDir == '') ? $this->rootDir : $this->rootDir."/".$siteDir;
		$this->filePath = ($filPath == '') ? $lastDir : $filPath;
		//Generate the sitemap
		$this->mapArray = $this->generate($siteDir);
		
		if ($this->mapArray) {
			$this->mapXmlString = 	'<?xml version="1.0" encoding="UTF-8"?>
									 <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			for($i=0,$length=count($this->mapArray);$i<$length;$i++) {
				
				$this->mapXmlString .= '<url>';
				$this->mapXmlString .= '<loc>'.$this->hostUrl.$this->mapArray[$i]['loc'].'</loc>';
				$this->mapXmlString .= '<lastmod>'.date("Y-m-d", filemtime($this->rootDir."/".$this->mapArray[$i]['loc'])).'</lastmod>';
				$this->mapXmlString .= '<changefreq>'.$changeFreq.'</changefreq>';
				$this->mapXmlString .= '<priority>'.$priority.'</priority>';
				$this->mapXmlString .= '</url>';
			}
			$this->mapXmlString .= '</urlset>';	
		}
		if (is_writable($this->filePath)) {
			 $bytes = @file_put_contents($this->filePath."/".$this->fileName.'.xml',$this->mapXmlString);
			 if ($bytes) {
			 	return $this->filePath."/".$this->fileName.'.xml';
			 } else {
			 	return false;
			 }
		} else {
			die("Could not have write permission on ".$this->filePath);
		}
	}
}