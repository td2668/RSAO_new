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

function parseExportSnap($lBaseFS, $strType){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $lBaseFS = CL_BASE_FS;

   switch ($strType) {

      default:
         echo('<img src="../../images/misc/wip.gif"><br>Check back soon for this great feature!<br><br>');
         echo('strType='.$strType.'<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
         break;
   }
}


function exportSnapShot($lBaseFS, $lSnapID) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');
   require_once('../util/util_options.php');
   require_once('../util/util_export.php');

   load_mSM_Options($bDropTable, $bUseFunnyQuotes, $bIncludeCommies);

   getSnapInfoViaSnapID($lSnapID, $strDBName, $lDBID, $dteSnapDate, $strSnapNotes);

   if ($bIncludeCommies) {
      $strIndent    = '#&nbsp;&nbsp;&nbsp;';
      $strSnapNotes = $strIndent.str_replace("\n",'<br>'.$strIndent, $strSnapNotes);
   }else {
      $strIndent    = '';
      $strSnapNotes = '';
   }

   $strQuoteChar = $bUseFunnyQuotes?'`':'';


   exportHeader();
?>
#<br>
# Snapshot SQL Export<br>
# -------------------<br>
#&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Database: <?=$strQuoteChar.$strDBName.$strQuoteChar?><br>
#&nbsp;&nbsp;&nbsp;&nbsp;Snapshot ID: <?=$lSnapID?><br>
#&nbsp;&nbsp;Snapshot Date: <?=date('l, m/d/Y H:i:s', $dteSnapDate)?><br>
#&nbsp;Snapshot Notes:<br>
<?=wordwrap($strSnapNotes, 70,'<br>#&nbsp;&nbsp;&nbsp;')?><br>
#-------------------------------------------------------------<br>

<?php

   $sqlStr =
      'SELECT st_lKeyID, st_strTableName, st_strUserComment, '
         .'st_strCreateTableSQL, st_dteLastUpdate '
     .'FROM tbl_snapshot_table '
     ."WHERE st_lSnapMasterID=$lSnapID "
     .'ORDER BY st_strTableName;';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      for ($idx=0; $idx<$numRows; ++$idx) {
         $row = mysql_fetch_array($result);
         exportTable($row, $bDropTable, $strQuoteChar, $bIncludeCommies);
      }
   }
   closeExport();
}

function exportTable($row, $bDropTable, $strQuoteChar, $bIncludeCommies){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $strTableNote = '';
   $strIndent = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
   $strComment = $strIndent.'#&nbsp;';
   $strCommentBar = $strIndent.'#--------------------------------------------------------------------------<br>';

   if ($bIncludeCommies) {
      $strTableNote = $row['st_strUserComment'];
      if ($strTableNote!='') {
         $strTableNote = wordwrap('#&nbsp;<b>table '.$row['st_strTableName'].':</b> '
                            .$strTableNote, 70, '<br>'.$strComment);
         $strTableNote = $strCommentBar.$strIndent.str_replace("\n",'<br>'.$strComment, $strTableNote);
         $strTableNote .= '<br>'.$strCommentBar;
      }
   }

   echo('<br><br>'
       .'#-------------------------------------------------------<br>'
       .'# Table '.$strQuoteChar.$row['st_strTableName'].$strQuoteChar.'<br>'
       .'#-------------------------------------------------------<br>'
       .$strTableNote
   );

   if ($bIncludeCommies) {
      exportFieldComments($row['st_lKeyID']);
   }

   if ($bDropTable) {
      echo('DROP TABLE IF EXISTS '.$strQuoteChar.$row['st_strTableName'].$strQuoteChar.";<br><br>\n\n");
   }

   echo(strFormatCreateTable($row['st_strCreateTableSQL'])."<br>\n");
}


function exportFieldComments($lTableID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
       'SELECT sf_strFieldName, sf_strComment '
      .'FROM tbl_snapshot_fields '
      ."WHERE (sf_lTableID=$lTableID) "
          .'AND (sf_strComment!=\'\') '
      .'ORDER BY sf_lTableIDX;';
   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      $strIndent = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
      $strComment = $strIndent.'#&nbsp;';
      $strCommentBar = $strIndent.'#--------------------------------------------------------------------------<br>';

      for ($idx=0; $idx<$numRows; ++$idx) {
         $row = mysql_fetch_array($result);

         $strFieldNote = $row['sf_strComment'].'<br>';
         $strFieldNote = wordwrap('#&nbsp;<b>field '.$row['sf_strFieldName'].':</b> '
                               .$strFieldNote, 70, '<br>'.$strComment);
         $strFieldNote = $strIndent.str_replace("\n",'<br>'.$strComment, $strFieldNote);
         if ($idx==0) {
            $strFieldNote = '<br>'.$strCommentBar.$strFieldNote;
         }
         echo($strFieldNote.$strCommentBar);
      }
   }
}

/*---------------
function exportFieldInfo($lTableID, $strQuoteChar, $bIncludeCommies){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $strPrimary   = array(); $lCntPrimary = 0;
   $strUniqueKey = array(); $lCntUnique  = 0;
   $strMultiKey  = array(); $lCntMulti   = 0;

   $sqlStr =
       'SELECT sf_strFieldName, sf_strFieldType, sf_strProperties, '
          .'sf_lLength, sf_strComment '
      .'FROM tbl_snapshot_fields '
      ."WHERE sf_lTableID=$lTableID "
      .'ORDER BY sf_lTableIDX;';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      for ($idx=0; $idx<$numRows; ++$idx) {
         $row = mysql_fetch_array($result);
         $strFN = $row['sf_strFieldName'];
         xlateFieldFlags($row['sf_strProperties'],
                  $bNotNull,       $bPrimaryKey, $bUniqueKey,
                  $bMultipleKey,   $bBlob,       $bUnsigned,
                  $bZeroFill,      $bBinary,     $bEnum,
                  $bAutoIncrement, $bTimeStamp);

         if ($bPrimaryKey) {
            $strPrimary[$lCntPrimary] = $strFN; ++$lCntPrimary;
         }
         if ($bUniqueKey) {
            $strUniqueKey[$lCntUnique] = $strFN; ++$lCntUnique;
         }
         if ($bMultipleKey) {
            $strMultiKey[$lCntMulti] = $strFN; ++$lCntMulti;
         }
      }
   }
}

--------*/

function strFormatCreateTable($strCTable) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $strIndent = '&nbsp;&nbsp;&nbsp;&nbsp;';
   return(str_replace("\n", "<br>$strIndent", $strCTable)."<br>\n");
}


?>