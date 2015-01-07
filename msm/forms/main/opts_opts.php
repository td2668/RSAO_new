<?php
//---------------------------------------------------------------------
// mySQL Schema Manager
//
// copyright (c) 2005 by Database Austin
// Austin, Texas
//
// This software is provided under the GPL.
// Please see http://www.gnu.org/copyleft/gpl.html for details.
//---------------------------------------------------------------------
// screamForHelp('<br>error!!!<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
if (@$_SESSION['msm_bToggleFileTrace']) echo('<b>file:</b> '.__FILE__."<br>\n");

function processOptOptions($strType){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $lBaseFS = CL_BASE_FS;

   switch ($strType) {
      case 'DISPLAY':
         require_once('../options/mSMOptions.php');
         show_mSM_Options($lBaseFS);
         break;
         
      case 'update':
         require_once('../options/mSMOptions.php');
         save_mSM_Options($lBaseFS);
         break;         
         
      default:
         echo('<img src="../../images/misc/wip.gif"><br>Check back soon for this great feature!<br><br>');
         echo('strType='.$strType.'<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
         break;
   }
}



?>