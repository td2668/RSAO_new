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


function load_mSM_Options(
            &$bDropTable, &$bUseFunnyQuotes, &$bIncludeCommies) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------

   $sqlStr =
       'SELECT op_bAddDropTable, op_bUseBackQuotes, op_bIncludeCommentsInExport '
      .'FROM tbl_options '
      .'LIMIT 0,1;';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         initializeOptions();
         $bDropTable      = false;
         $bUseFunnyQuotes = false;
         $bIncludeCommies = true;
      }else {
         $row = mysql_fetch_array($result);
         $bDropTable      = (boolean)$row['op_bAddDropTable'];
         $bUseFunnyQuotes = (boolean)$row['op_bUseBackQuotes'];
         $bIncludeCommies = (boolean)$row['op_bIncludeCommentsInExport'];
      }
   }
}

function initializeOptions(){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
      'INSERT INTO tbl_options '
      .'SET op_bAddDropTable=0, op_bUseBackQuotes=0, op_bIncludeCommentsInExport=1;';
   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }
}




?>