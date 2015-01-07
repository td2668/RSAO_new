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

function processSnapOptions($strType){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $lBaseFS = CL_BASE_FS;

   switch ($strType) {
      case 'ADDNEW':
         require_once('../snaps/addEditSnaps.php');
         parseAddNewSnapOption($lBaseFS, $_REQUEST['ssType']);
         break;

      case 'REMOVE':
         require_once('../snaps/addEditSnaps.php');
         parseRemoveSnapOption($lBaseFS, $_REQUEST['ssType']);
         break;
         

      case 'updateSnapNotes':
         require_once('../snaps/viewSnaps.php');
         updateSnapComments($lBaseFS,
              (integer)$_REQUEST['DBID'], (integer)$_REQUEST['SSID']);
         break;

      case 'updateTable':
         require_once('../snaps/viewSnaps.php');
         updateSnapTableComments($lBaseFS,
              (integer)$_REQUEST['DBID'], (integer)$_REQUEST['SSID'], (integer)$_REQUEST['TID']);
         break;

      case 'VIEW':
         require_once('../snaps/viewSnaps.php');
         parseViewSnapOption($lBaseFS, $_REQUEST['ssType']);
         break;

      default:
         echo('<img src="../../images/misc/wip.gif"><br>Check back soon for this great feature!<br><br>');
         echo('strType='.$strType.'<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
         break;
   }
}



?>