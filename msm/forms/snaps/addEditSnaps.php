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


function parseAddNewSnapOption($lBaseFS, $strType){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------

   switch ($strType) {
      case 'step1':
         step1_form($lBaseFS, $_REQUEST['SDBN']);
         break;

      case 'step2':
         step2_addSnap($lBaseFS, (integer)$_REQUEST['DBID'], $_REQUEST['STRDBNAME'],
                  strLoad_REQ('txtComments', true, false));
         break;

      default:
         echo('<img src="../../images/misc/wip.gif"><br>Check back soon for this great feature!<br><br>');
         echo('strType='.$strType.'<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
         break;
   }
}

function step1_form($lBaseFS, $strDBName){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');

   openCenterBlock();
   openCenterSection('Add New Schema Snap Shot',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   $lNumSnaps = lNumSnapshotsViaDBName($strDBName, $lDBID);

   $result = mysql_list_tables($strDBName);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, 'mysql_list_tables') ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         ?>
         There are no tables in database <b><?=$strDBName?></b></i>.<br><br>
         Nothing to do!<br><br>
         <a href="../main/mainOpts.php?type=DB&sType=MAIN">
         Click here to continue....</a><br><br><br>
         <?php
      }else {
         showSnapShotForm($strDBName, $lDBID, $numRows);

//         for ($idx=0; $idx<$numRows; ++$idx) {
//            $row = mysql_fetch_array($result);
//            echo "Table: $row[0]<br>\n";
//         }
      }
   }

   closeCenterSection();
   closeCenterBlock();
}


function showSnapShotForm($strDBName, $lDBID, $lNumTables){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../java/util_JavaJoeCheckBox.php');

?>
<fieldset style="width: 420pt;">
   <legend>
      Create a Schema Snapshot for <b><?=$strDBName?>&nbsp;
   </legend>
   <table border="1" cellpadding="3" cellspacing="1"
     style="width: 400pt; color:#000000;
            background-color:#f1f1f1;font-size: 100%; padding:0px;"
   >

   <form method="POST"
        action="../main/mainOpts.php"
        name="frmSS"
   >
   <input type="hidden" name="type"      value="SNAP">
   <input type="hidden" name="sType"     value="ADDNEW">
   <input type="hidden" name="ssType"    value="step2">
   <input type="hidden" name="DBID"      value="<?=$lDBID?>">
   <input type="hidden" name="STRDBNAME" value="<?=$strDBName?>">

      <tr>
         <td bgcolor="#c1c1c1" width="35%">
            Database Name:
         </td>

         <td>
            <b><?=$strDBName?>
         </td>
      </tr>

      <tr>
         <td bgcolor="#c1c1c1" width="35%">
            # of Tables:
         </td>

         <td>
            <b><?=$lNumTables?>
         </td>
      </tr>

      <tr>
         <td bgcolor="#c1c1c1" width="35%">
            Version Comments:
         </td>

         <td>
            <textarea name="txtComments" rows="5" cols="45"></textarea>
         </td>
      </tr>

      <tr>
         <td bgcolor="#c1c1c1" width="35%">
            Verbose?:
         </td>

         <td>
            <input type="checkbox" name="chkVerbose" value="YES"> 
             <span onClick=toggleCheckBox(frmSS.chkVerbose)>
            (check for verbose mode)</span>
         </td>
      </tr>

      <tr>
         <td align="center" colspan="5" class="back">
            <input type="submit"
                  name="cmdAdd"
                  value="Click Here to Add Schema Snapshot"
                  class="btn"
                     onmouseover="this.className='btn btnhov'"
                     onmouseout="this.className='btn'"
                  >
         </td>
         </form>
      </tr>
   </table>
</fieldset><br><br>
<?php
}


function step2_addSnap($lBaseFS, $lDBID, $strDBName,
                  $strSnapComments){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');

   openCenterBlock();
   openCenterSection('Creating a New Schema Snap Shot',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   $bVerbose = @$_REQUEST['chkVerbose']=='YES';

   if ($lDBID<=0) {
      $lDBID = lCreateNewDBMaster($strDBName);
   }

      //-------------------------------------
      // create the snapshot master record
      //-------------------------------------
   $sqlStr =
      'INSERT INTO tbl_snapshot_master '
      .'SET '
         ."sm_lDB_ID=$lDBID, "
         .'sm_dteSnapDate=NOW(), '
         .'sm_strNotes='.strPrepStr($strSnapComments).';';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }
   $lSnapMasterID = mysql_insert_id();


      //-----------------------------------------------------------------
      // iterate through the database tables and capture the field info
      //-----------------------------------------------------------------
   $result = $result = mysql_list_tables($strDBName);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         screamForHelp('UNEXPECTED EOF DETECTED<br>error!!!<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
      }else {
         for ($idx=0; $idx<$numRows; ++$idx) {
            $row = mysql_fetch_array($result);
            snapCaptureDBTable($strDBName, $lSnapMasterID, $row[0], $bVerbose);
         }

         ?>
         <br>A snapshot of database <b><?=$strDBName?></b> was created.<br><br>
         <a href="../main/mainOpts.php?type=DB&sType=MAIN">
         Click here to continue....</a><br><br><br>
         <?php
      }
   }

   closeCenterSection();
   closeCenterBlock();
}

function snapCaptureDBTable($strDBName, $lSnapMasterID, $strTableName, $bVerbose){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------

   mysql_select_db($strDBName);
   $sqlCreateTable = 'SHOW CREATE TABLE '.$strTableName.';';

//echo("\$sqlCreateTable=$sqlCreateTable <br>\n");

   $result = mysql_query($sqlCreateTable);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlCreateTable) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         screamForHelp('UNEXPECTED EOF<br>error!!!<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
      }else {
         $row = mysql_fetch_array($result);
         $strCreateTable = $row[1];
      }
   }

   mysql_select_db(CS_MSM_DB_NAME);

//echo("before lCreateSnapTableRecord<br>\n");
   $lTableID = lCreateSnapTableRecord($lSnapMasterID, $strTableName, $strCreateTable);
//echo("after lCreateSnapTableRecord<br>\n");

//echo("\$strTableName=$strTableName <br>\n");

//   $objFields = mysql_list_fields($strDBName, $strTableName);
//   $lNumCols = mysql_num_fields($objFields);


   mysql_select_db($strDBName);
   $sqlStr = 'SHOW COLUMNS FROM '.$strTableName.';';
   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
      }else {
         if ($bVerbose) openVerboseTable($strTableName);
         for ($idx=0; $idx<$numRows; ++$idx) {
            $row = mysql_fetch_array($result);
            addFieldEntry($idx, $row, $lTableID, $bVerbose);
         }
         if ($bVerbose) echo("</table><br>\n");
      }
   }
}


function addFieldEntry($idx, &$row, $lTableID, $bVerbose){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $strFN      = $row['Field'];
   $strType    = $row['Type'];
   $bNull      = $row['Null']=='YES';
   $strDefault = $row['Default'];
   $strKey     = strXlateKey($row['Key']);
   $strExtra   = $row['Extra'];

   if (is_null($strDefault)) $strDefault = 'NULL';

//echo("<b>$strFN</b> <br>\n");
//echo("\$strType=$strType <br>\n");
//echo("\$bNull=$bNull <br>\n");
//echo("\$strDefault=$strDefault ".(is_null($strDefault)?'--NULL--':'')."<br>\n");
//echo("\$strKey=$strKey <br>\n");
//echo("\$strExtra=$strExtra <br><br>\n");
//die;

   $sqlStr =
      'INSERT INTO tbl_snapshot_fields '
      .'SET '
         ."sf_lTableID=$lTableID, sf_lTableIDX=$idx, "
         .'sf_strFieldName='.strPrepStr($strFN).', '
         .'sf_strFieldType='.strPrepStr($strType).', '
         .'sf_keyType='.strPrepStr($strKey).', '
         .'sf_bNull='.($bNull?'1':'0').', '
         .'sf_varDefault='.strPrepStr($strDefault.'').', '
         .'sf_strExtra='.strPrepStr($strExtra).';';

//echo("\$sqlStr=$sqlStr <br>\n");

   mysql_select_db(CS_MSM_DB_NAME);
//echo("before insert, \$idx=$idx<br>\n");
   $result = mysql_query($sqlStr);
//echo("after insert, \$idx=$idx<br><br>\n");
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }

   if ($bVerbose) {
      echo(
         '<td>'.$strFN
        .'<td>'.$strType
        .'<td align="center">&nbsp;'.($strKey=='none'?'':$strKey).'&nbsp;'
        .'<td>&nbsp;'.($bNull?'Null':'').'&nbsp;'
        .'<td>&nbsp;'.$strExtra.'&nbsp;'
        ."</td></tr>\n");
   }
}

function openVerboseTable($strTableName){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   echo('<table border="1" bgcolor="#ffffff" '
         .'style="width: 400pt; color:#000000; background-color:#f1f1f1;font-size: 100%; padding:0px;" '
      .'<tr><td colspan="10" align="center" bgcolor="#d1d1d1">Table <b>'.$strTableName."</td></tr><tr>\n");

   echo('<td align="center" width="120" bgcolor="#d1d1d1"><b><i>Field Name</td>'."\n");
   echo('<td align="center" width="70"  bgcolor="#d1d1d1"><b><i>Type</td>'."\n");
   echo('<td align="center" width="40"  bgcolor="#d1d1d1"><b><i>Key</td>'."\n");
   echo('<td align="center" width="150" bgcolor="#d1d1d1"><b><i>Null</td>'."\n");
   echo('<td align="center" width="150" bgcolor="#d1d1d1"><b><i>Extra</td></tr>'."\n");

}

function parseRemoveSnapOption($lBaseFS, $strType){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   switch ($strType) {
      case 'ALL1':
         areYouSureAll($lBaseFS, (integer)$_REQUEST['DBID']);
         break;

      case 'ALL2':
         removeAllDBSnaps($lBaseFS, (integer)$_REQUEST['DBID']);
         break;

      case 'SINGLE1':
         areYouSureSingle($lBaseFS, (integer)$_REQUEST['DBID'], (integer)$_REQUEST['SSID']);
         break;

      case 'SINGLE2':
         removeSingleSnap($lBaseFS, (integer)$_REQUEST['DBID'], (integer)$_REQUEST['SSID']);
         break;

      default:
         echo('<img src="../../images/misc/wip.gif"><br>Check back soon for this great feature!<br><br>');
         echo('strType='.$strType.'<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
         break;
   }
}

function areYouSureAll($lBaseFS, $lDBID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');

   openCenterBlock();
   openCenterSection('Delete Snap Shots',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   $lNumSS = lNumSnapshotsViaDBID($lDBID, $strDBName);
   ?>
      You have requested to remove the <?=$lNumSS?> Schema Snap Shot<?=($lNumSS==1?'':'s')?>
      associated with database <b>"<?=$strDBName?>"</b>.<br><br>
      <b>ARE YOU SURE?</b><br><br>

      <a style="color: red;" href="../main/mainOpts.php?type=SNAP&sType=REMOVE&ssType=ALL2&DBID=<?=$lDBID?>">
      YES - REMOVE ALL SNAP-SHOTS FOR THIS DATABASE</a><br><br>

      <a href="../main/mainOpts.php?type=SNAP&sType=VIEW&ssType=MAIN&DBID=<?=$lDBID?>">
      NO - CANCEL - DO NOT REMOVE THE SNAP SHOTS</a><br><br>
   <?php

   closeCenterSection();
   closeCenterBlock();
}

function removeAllDBSnaps($lBaseFS, $lDBID){
//---------------------------------------------------------------------
// It's the little things that make a house a home....
//---------------------------------------------------------------------
   require_once('../util/util_db.php');

   openCenterBlock();
   openCenterSection('Delete Snap Shots',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   $lNumSS = lNumSnapshotsViaDBID($lDBID, $strDBName);

   $sqlStr =
       'DELETE tbl_db_list, tbl_snapshot_master, tbl_snapshot_table, tbl_snapshot_fields '
      .'FROM tbl_db_list '
          .'LEFT JOIN tbl_snapshot_master ON db_lKeyID=sm_lDB_ID '
          .'LEFT JOIN tbl_snapshot_table  ON sm_lKeyID=st_lSnapMasterID '
          .'LEFT JOIN tbl_snapshot_fields ON st_lKeyID=sf_lTableID '
      ."WHERE db_lKeyID=$lDBID;";

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }

   ?>
      The <?=$lNumSS?> Schema Snap Shot<?=($lNumSS==1?'':'s')?>
      associated with database <b>"<?=$strDBName?>"</b>
      <?=($lNumSS==1?'was':'were')?> removed.<br><br>

      <a href="../main/mainOpts.php?type=DB&sType=MAIN">
      Click here to continue....</a><br><br><br>

   <?php

   closeCenterSection();
   closeCenterBlock();
}

function areYouSureSingle($lBaseFS, $lDBID, $lSnapID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');

   openCenterBlock();
   openCenterSection('Delete Snap Shot',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   getSnapInfoViaSnapID($lSnapID, $strDBName, $lDUMMY, $dteSnapDate, $strSnapNotes);

   ?>
      You have requested to remove the Schema Snap Shot
      <b><?=$lSnapID?></b> of <b><?=date('l, m/d/Y H:i:s', $dteSnapDate)?></b><br>
      associated with database <b>"<?=$strDBName?>"</b>.<br><br>
      <b>ARE YOU SURE?</b><br><br>

      <a style="color: red;" href="../main/mainOpts.php?type=SNAP&sType=REMOVE&ssType=SINGLE2&DBID=<?=$lDBID?>&SSID=<?=$lSnapID?>">
      YES - REMOVE THIS SNAP-SHOT</a><br><br>

      <a href="../main/mainOpts.php?type=SNAP&sType=VIEW&ssType=MAIN&DBID=<?=$lDBID?>">
      NO - CANCEL - DO NOT REMOVE THIS SNAP SHOT</a><br><br>
   <?php

   closeCenterSection();
   closeCenterBlock();
}

function removeSingleSnap($lBaseFS, $lDBID, $lSnapID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');

   openCenterBlock();
   openCenterSection('Delete Snap Shot',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   getSnapInfoViaSnapID($lSnapID, $strDBName, $lDUMMY, $dteSnapDate, $strSnapNotes);

   $sqlStr =
       'DELETE tbl_snapshot_master, tbl_snapshot_table, tbl_snapshot_fields '
      .'FROM tbl_snapshot_master '
          .'LEFT JOIN tbl_snapshot_table  ON sm_lKeyID=st_lSnapMasterID '
          .'LEFT JOIN tbl_snapshot_fields ON st_lKeyID=sf_lTableID '
      ."WHERE sm_lKeyID=$lSnapID;";

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }

   ?>
      The  Schema Snap Shot
      <b><?=$lSnapID?></b> of <b><?=date('l, m/d/Y H:i:s', $dteSnapDate)?></b><br>
      associated with database <b>"<?=$strDBName?>"</b>
      was removed.<br><br>

      <a href="../main/mainOpts.php?type=DB&sType=MAIN">
      Click here to continue....</a><br><br><br>

   <?php

   closeCenterSection();
   closeCenterBlock();
}


function strXlateKey($strKeyVal){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   switch ($strKeyVal){
      case '':    return('none');    break;
      case 'UNI': return('unique');  break;
      case 'MUL': return('multi');   break;
      case 'PRI': return('primary'); break;
      default:
         screamForHelp('INVALID KEY DESIGNATION "'.$strKeyVal.'"<br>error!!!<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
         break;
   }
}

?>