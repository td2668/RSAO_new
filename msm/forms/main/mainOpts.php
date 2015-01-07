<?php
//---------------------------------------------------------------------
// Copyright (c) 2005 Database Austin
//
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of the GNU General Public License
//   as published by the Free Software Foundation; either version 2
//   of the License, or (at your option) any later version.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
// This software is provided under the GPL.
// Please see http://www.gnu.org/copyleft/gpl.html for details.
//---------------------------------------------------------------------
// to avoid headers being written, include the following in your request stream:
//    <input type="hidden" name="noHDRS" value="TRUE">
//---------------------------------------------------------------------

// screamForHelp('<br>error on line '.__LINE__.',<br>file '.__FILE__.',<br>function '.__FUNCTION__);


require_once('../main/opts_main.php');
require_once('../util/util_mSM.php');
require_once('../config/util_DEF_DB.php');
require_once('../dbAustinLib/util_WebLayout.php');
require_once('../dbAustinLib/util_zUtilities.php');
require_once('../config/util_DEF_mSM.php');
require_once('../config/util_DEF_colors.php');

session_start();
ini_set('session.cache_limiter', 'private');

//dumpRequest(true, true, false);

//if ($_SESSION['enp_bDumpVars']) {
//   dumpRequest(true, true, false);
//   echo('<b>remote address=</b>'.gethostbyaddr($_SERVER['REMOTE_ADDR'])."<br>\n");
//}

main();

function main(){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   $strProcType = @$_REQUEST['type'];

      //---------------------------------------------------------------
      // a link can optionally request no headers (for example,
      // a "printer-friendly" page
      // example:
      //   <input type="hidden" name="noHDRS" value="TRUE">
      //---------------------------------------------------------------
   $bNoHeaders = bTestForNoHeaders($strProcType);

   processUserSelection($strProcType, $bNoHeaders);

   if (!$bNoHeaders) {
      ?></tr><?php

      writeFooter('<b>'.CS_PROGNAME.'</b><br>Copyright &#169; 2005 by Database Austin');
      closeHTML();
   }
}

function processUserSelection($strType,  $bNoHeaders) {
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   global $dbConnMSM;
   $dbConnMSM = makeTheConnection(CS_MSM_DB_NAME, $dbSel);
   $bDeveloper = CB_DEVELOPER;

   switch ($strType) {

         //------------------------------------------
         // 
         //------------------------------------------
      case 'DB':
         require_once('../main/opts_db.php');
         if (!$bNoHeaders) {
            genericHTML_Open('Database Display');
            openLayoutTable();
            ?><tr><?php writeLeftLinkBoxes(CL_MAINOPT_DB, $bDeveloper);
         }

         processDBOptions(@$_REQUEST['sType']);
         break;
         
      case 'SNAP':
         require_once('../main/opts_snap.php');
         if (!$bNoHeaders) {
            genericHTML_Open('Database Snapshots');
            openLayoutTable();
            ?><tr><?php writeLeftLinkBoxes(CL_MAINOPT_DB, $bDeveloper);
         }

         processSnapOptions(@$_REQUEST['sType']);
         break;  
         
      case 'OPTS':
         require_once('../main/opts_opts.php');
         if (!$bNoHeaders) {
            genericHTML_Open('mSM Options');
            openLayoutTable();
            ?><tr><?php writeLeftLinkBoxes(CL_MAINOPT_DB, $bDeveloper);
         }

         processOptOptions(@$_REQUEST['sType']);
         break;                  
         
      case 'EXPORT':
         require_once('../main/opts_export.php');
         if (!$bNoHeaders) {
            genericHTML_Open('Schema Export');
            openLayoutTable();
            ?><tr><?php writeLeftLinkBoxes(CL_MAINOPT_DB, $bDeveloper);
         }

         processExportOptions(@$_REQUEST['sType']);
         break;                  

         //------------------------------------------
         // developer utilities
         //------------------------------------------
      case 'DEBUG':
         genericHTML_Open('Developer Utilities');
         set_CSS(CL_MAINOPT_DEV);
         openLayoutTable();
         require_once('../dev/util_debug.php');
         ?><tr><?php writeLeftLinkBoxes($lPermissions, CL_MAINOPT_DEV, $bDeveloper, $strSeed);

         processDebugOptions($dbConn, @$_REQUEST['subType']);
         break;

         //------------------------------------------
         // display the welcome page
         //------------------------------------------
      case 'display':
      default:
         genericHTML_Open('Welcome to '.CS_PROGNAME);
         openLayoutTable();
         ?><tr><?php writeLeftLinkBoxes(-1, $bDeveloper);

         show_enp_Welcome();
         break;
   }
}


function genericHTML_Open($strTitle) {
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------

   displayHTML_Header(
            $strTitle,
            '../styles/mSM_Style01.css',
            '#ffffff',
            '',
            '',
            '../../images/logos/dba_logo01.gif',
            '../../images/logos/logo01.gif',
            '',
            '../main/mainOpts.php?type=display'
            );
//   load_JS_PopUpFunction();
   load_JS_BuildEmailScript();
}

function writeLeftLinkBoxes($lMainOptType, $bDeveloper){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   openLeftLinkBoxes(11);

   writeMainOptions($lMainOptType);
//
//   switch ($lMainOptType) {
//      case CL_MAINOPT_FAMILY:
//         writeFamilyOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_PEOPLE:
//         writePeopleOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_BIZ:
//         writeBizOptions($lPermissions, $strSeed);
//         break;
//         
//      case CL_MAINOPT_OFFICE:
//         writeOfficeOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_SPONSORSHIP:
//         writeSponsorshipOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_GIFTS:
//         writeGiftOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_REPORTS:
//         writeReportOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_SURVEY:
//         writeSurveyOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_ADMIN:
//         writeAdminOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_CALENDAR:
//         writeCalendarOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_VOLUNTEER:
//         writeVolOptions($lPermissions);
//         break;
//
//      case CL_MAINOPT_DEV:
//         break;
//
//      case CL_MAINOPT_ACCT:
//         writeAccountOptions($lPermissions);
//         break;
//
//      default:
//         break;
//   }
//
//   if ($bDeveloper) {
//      require_once('../main/opts_dev.php');
//      writeDevOptions();
//   }
//
//   writeLinkOptions($lPermissions);
   closeLeftLinkBoxes();
}


function bTestForNoHeaders($strType){
//--------------------------------------------------------------------------
// test for request of no headers, or special parsing cases requiring
// no headers.
//--------------------------------------------------------------------------

   $bNoHeaders = @$_REQUEST['noHDRS']=='TRUE';
   
   return($bNoHeaders);
}


?>
