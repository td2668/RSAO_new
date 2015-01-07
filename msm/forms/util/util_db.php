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


function lNumSnapshotsViaDBName($strDBName, &$lDBID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $lDBID = lDBID_Via_Name($strDBName);

   if ($lDBID<=0) return(0);

   $sqlStr =
       'SELECT COUNT(*) as lNumSnap '
      .'FROM tbl_snapshot_master '
      ."WHERE sm_lDB_ID=$lDBID;";

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         return(0);
      }else {
         $row = mysql_fetch_array($result);
         return($row['lNumSnap']);
      }
   }
}


function lNumSnapshotsViaDBID($lDBID, &$strDBName){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
       'SELECT COUNT(*) AS lNumSnap, db_strDBName '
      .'FROM tbl_snapshot_master '
          .'INNER JOIN tbl_db_list ON db_lKeyID=sm_lDB_ID '
      ."WHERE sm_lDB_ID=$lDBID "
      .'GROUP BY sm_lDB_ID;';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         $strDBName = '#error#';
         return(0);
      }else {
         $row = mysql_fetch_array($result);
         $strDBName = $row['db_strDBName'];
         return($row['lNumSnap']);
      }
   }
}


function lDBID_Via_Name($strDBName) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
      'SELECT db_lKeyID '
      .'FROM tbl_db_list '
      .'WHERE db_strDBName='.strPrepStr($strDBName).';';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         return(-1);
      }else {
         $row = mysql_fetch_array($result);
         return($row['db_lKeyID']);
      }
   }
}


function strDBName_Via_ID($lDBID) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
       'SELECT db_strDBName '
      .'FROM tbl_db_list '
      ."WHERE db_lKeyID=$lDBID;";

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         return('#error - eof#');
      }else {
         $row = mysql_fetch_array($result);
         return($row['db_strDBName']);
      }
   }
}

function lCreateNewDBMaster($strDBName){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
      'INSERT INTO tbl_db_list '
      .'SET '
          .'db_strDBName='.strPrepStr($strDBName).', '
          .'db_strUserName=\'pending\';';
   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }
   return(mysql_insert_id());
}


function lCreateSnapTableRecord($lSnapMasterID, $strTableName, $strCreateSQL){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
       'INSERT INTO  tbl_snapshot_table '
      .'SET '
          ."st_lSnapMasterID=$lSnapMasterID, "
          .'st_strTableName='.strPrepStr($strTableName).', '
          .'st_strCreateTableSQL='.strPrepStr($strCreateSQL).', '
          .'st_strUserComment=\'\';';
   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }
   return(mysql_insert_id());
}

function getSnapInfoViaSnapID(
                $lSnapID,      &$strDBName, &$lDBID, 
                &$dteSnapDate, &$strSnapNotes) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
      'SELECT UNIX_TIMESTAMP(sm_dteSnapDate) as dteSnapDate, sm_strNotes, '
          .'db_strDBName, db_lKeyID '
     .'FROM tbl_db_list '
     .'INNER JOIN tbl_snapshot_master ON db_lKeyID=sm_lDB_ID '
     ."WHERE sm_lKeyID=$lSnapID;";

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         screamForHelp('UNEXPECTED EOF<br>error!!!<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
      }else {
         $row = mysql_fetch_array($result);
         $strDBName    = $row['db_strDBName'];
         $lDBID        = $row['db_lKeyID'];
         $strSnapNotes = $row['sm_strNotes'];
         $dteSnapDate  = $row['dteSnapDate'];
      }
   }
}

?>