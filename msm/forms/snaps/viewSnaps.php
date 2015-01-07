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

function parseViewSnapOption($lBaseFS, $strType){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   switch ($strType) {
      case 'MAIN':
         showSnapShotSummary($lBaseFS, (integer)$_REQUEST['DBID']);
         break;

      case 'details':
         showSnapShotDetails($lBaseFS, (integer)$_REQUEST['DBID'], (integer)$_REQUEST['SSID']);
         break;

      default:
         echo('<img src="../../images/misc/wip.gif"><br>Check back soon for this great feature!<br><br>');
         echo('strType='.$strType.'<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
         break;
   }
}

function showSnapShotSummary($lBaseFS, $lDBID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');

   $strDBName = strDBName_Via_ID($lDBID);

   openCenterBlock();
   openCenterSection('Snap-Shot Summary for database "'.$strDBName.'"',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   showClearAllSnaps($lDBID);

   $sqlStr =
        'SELECT sm_lKeyID, UNIX_TIMESTAMP(sm_dteSnapDate) as dteSnapDate, sm_strNotes '
       .'FROM tbl_snapshot_master '
       ."WHERE sm_lDB_ID=$lDBID "
       .'ORDER BY sm_dteSnapDate DESC;';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
      ?>
         There are no Snap Shots recorded for database <b>"<?=$strDBName?>"</b>
      <?php
      }else {
         $strLinkStyle    = ' style="font-size: '.($lBaseFS-3).'pt;" ';
         $strLinkRemStyle = ' style="font-size: '.($lBaseFS-3).'pt; color: red;" ';
         openSnapShotSummary($lBaseFS, $strDBName);
         for ($idx=0; $idx<$numRows; ++$idx) {
            $row = mysql_fetch_array($result);
            writeSnapShotSummaryRow($row, $lDBID, $strLinkStyle, $strLinkRemStyle);
         }
         closeSnapShotSummary();
      }
   }

   closeCenterSection();
   closeCenterBlock();
}

function writeSnapShotSummaryRow(&$row, &$lDBID, &$strLinkStyle, &$strLinkRemStyle){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $lSnapID = $row['sm_lKeyID'];
   echo(
      '<tr>'
         .'<td align="center">'.$lSnapID
            .'&nbsp;<a '.$strLinkRemStyle.' href="../main/mainOpts.php'
               .'?type=SNAP'
               .'&sType=REMOVE'
               .'&ssType=SINGLE1'
               .'&DBID='.$lDBID
               .'&SSID='.$lSnapID
               .' style="color: red;">(remove)</a>'
         ."</td>\n");

   echo(
       '<td align="center"><font face="Courier New">'
          .date('D m/d/Y H:i:s',$row['dteSnapDate'])
      ."</td>\n");

   echo(
       '<td align="left">'
          .nl2br(htmlspecialchars($row['sm_strNotes'])).'&nbsp;'
      ."</td>\n");

   echo(
       '<td align="center">'
          .'<a '.$strLinkStyle.' href="../main/mainOpts.php?type=SNAP&sType=VIEW'
              .'&ssType=details'
              .'&DBID='.$lDBID
              .'&SSID='.$lSnapID
              .'">(details)</a>&nbsp;&nbsp;'

          .'<a '.$strLinkStyle.' href="../main/mainOpts.php'
              .'?type=EXPORT'
              .'&sType=snap'
              .'&ssType=showOpts'
              .'&noHDRS=TRUE'
              .'&SSID='.$lSnapID.'" target="export'.$lSnapID.'">(export)</a>'

      ."</td>\n");

   echo("</tr>\n");

}


function openSnapShotSummary($lBaseFS, $strDBName){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------

   $strStyle = 'style="font-size:'.($lBaseFS+1).'pt;" ';

?>
  <table border="1" cellpadding="3" cellspacing="1"
     style="width: 456pt; color:#000000;
            background-color:#f1f1f1;font-size: 100%; padding:0px;"
  >
      <tr>
         <td colspan="5" align="center" bgcolor="#b1b1b1" <?=$strStyle?> ><b>
            Snap-Shots Recorded for Database "<?=$strDBName?>"
         </td>
      </tr>

      <tr>
         <td align="center" bgcolor="#b1b1b1" <?=$strStyle?> ><b>
            Snap ID
         </td>

         <td align="center" bgcolor="#b1b1b1" <?=$strStyle?> ><b>
            Recorded On
         </td>

         <td align="center" bgcolor="#b1b1b1" <?=$strStyle?>  width="40%"><b>
            Notes
         </td>

         <td align="center" bgcolor="#b1b1b1" <?=$strStyle?> ><b>
            Links
         </td>
      </tr>
<?php
}


function showClearAllSnaps($lDBID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
?>
   <a href="../main/mainOpts.php?type=SNAP&sType=REMOVE&ssType=ALL1&DBID=<?=$lDBID?>"
         style="color: red;">
   (remove all snap-shots for this database)</a><br><br>

<?php
}

function closeSnapShotSummary(){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
?>
   </table><br><br>
<?php
}


function showSnapShotDetails($lBaseFS, $lDBID, $lSnapID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');

   $strDBName = strDBName_Via_ID($lDBID);

   openCenterBlock();
   openCenterSection('Snap-Shot Details for database "'.$strDBName.'"',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   snapShotDetailTop($lDBID, $lBaseFS, $strDBName, $lSnapID);

   showSnapShotTableDetail($lBaseFS, $lSnapID, $lDBID);

   closeCenterSection();
   closeCenterBlock();

}

function snapShotDetailTop($lDBID, $lBaseFS, $strDBName, $lSnapID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $strStyle = 'style="font-size:'.($lBaseFS+1).'pt;" ';

   ?>
      <a target="export<?=$lSnapID?>" href="../main/mainOpts.php?type=EXPORT&sType=snap&ssType=showOpts&noHDRS=TRUE&SSID=<?=$lSnapID?>">
      (create a sql export of this snapshot)</a><br><br>
   <?php

   $sqlStr =
        'SELECT UNIX_TIMESTAMP(sm_dteSnapDate) as dteSnap, sm_strNotes '
       .'FROM tbl_snapshot_master '
       ."WHERE (sm_lKeyID=$lSnapID);";

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         screamForHelp('UNEXPECTED EOF<br>error!!!<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
      }else {
         $row = mysql_fetch_array($result);
      }
   }

?>
  <table border="1" cellpadding="3" cellspacing="1"
     style="width: 356pt; color:#000000;
            background-color:#f1f1f1;font-size: 100%; padding:0px;"
  >
      <tr>
         <td colspan="5" align="center" bgcolor="#b1b1b1" <?=$strStyle?> ><b>
            Snap-Shot for Database "<?=$strDBName?>"
         </td>
      </tr>
      <tr>
         <td bgcolor="#b1b1b1" width="30%">
            Snap Shot ID:
         </td>
         <td>
            <?=$lSnapID?>
         </td>
      </tr>

      <tr>
         <td bgcolor="#b1b1b1">
            Date of Snap Shot:
         </td>
         <td>
            <?=date('l, m/d/Y H:i:s', $row['dteSnap'])?>
         </td>
      </tr>

      <tr>
         <form method="POST"
              action="../main/mainOpts.php"
              name="frmSnapUpdate"
         >
         <input type="hidden" name="type"   value="SNAP">
         <input type="hidden" name="sType"  value="updateSnapNotes">
         <input type="hidden" name="DBID"   value="<?=$lDBID?>">
         <input type="hidden" name="SSID"   value="<?=$lSnapID?>">
         <td bgcolor="#b1b1b1">
            Your Snapshot Notes:
         </td>
         <td>
            <textarea name="txtSnapNotes" rows="4" cols="55"><?=htmlspecialchars($row['sm_strNotes'])?></textarea>
         </td>
      </tr>
      <tr>
         <td align="center"  colspan="3" ><b><font color="#ffffff">
            <input type="submit"
                 name="cmdAdd"
                 value="Click Here to Update Snap-Shot Notes"
                 class="btn"
                    onmouseover="this.className='btn btnhov'"
                    onmouseout="this.className='btn'"
                 >
         </td>
      </tr>
      </form>
   </table><br><br>

<?php
}

function showSnapShotTableDetail($lBaseFS, $lSnapID, $lDBID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $sqlStr =
       'SELECT st_lKeyID, st_strTableName, st_strUserComment, '
          .'UNIX_TIMESTAMP(st_dteLastUpdate) as dteLastUpdate '
      .'FROM tbl_snapshot_table '
      ."WHERE st_lSnapMasterID=$lSnapID "
      .'ORDER BY st_strTableName;';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         ?>
            <font color="red">There are no tables in this database snapshot!<br><br></font>
            <a href="../main/mainOpts.php?type=SNAP&sType=VIEW&ssType=MAIN&DBID=<?=$lDBID?>">
            Click here to continue....</a><br><br>
         <?php
      }else {
         for ($idx=0; $idx<$numRows; ++$idx) {
            $row = mysql_fetch_array($result);
            displayTableDetails($row, $lBaseFS, $lSnapID, $lDBID);
         }
      }
   }
}

function displayTableDetails(&$objTableRow, $lBaseFS, $lSnapID, $lDBID){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $lTableID = $objTableRow['st_lKeyID'];
?>

  <table border="1" cellpadding="3" cellspacing="1"
     style="width: 456pt; color:#000000;
            background-color:#f1f1f1;font-size: 100%; padding:0px;"
  >

   <form method="POST"
        action="../main/mainOpts.php"
        name="frmTab<?=$lTableID?>"
   >
   <input type="hidden" name="type"   value="SNAP">
   <input type="hidden" name="sType"  value="updateTable">
   <input type="hidden" name="DBID"   value="<?=$lDBID?>">
   <input type="hidden" name="SSID"   value="<?=$lSnapID?>">
   <input type="hidden" name="TID"    value="<?=$lTableID?>">

      <tr>
         <td colspan="5" align="center" bgcolor="#038eb1" ><b>
            <font color="#ffffff" style="font-size: <?=$lBaseFS+2?>pt;">
            table "<?=$objTableRow['st_strTableName'] ?>"
         </td>
      </tr>

      <tr>
         <td align="left" bgcolor="#038eb1" ><b><font color="#ffffff">
            Your Table Notes:
         </td>
         <td>
            <textarea name="txtTableCom" rows="3" cols="50"><?=htmlspecialchars($objTableRow['st_strUserComment']) ?></textarea>
         </td>
      </tr>
      <tr>
         <td align="left" bgcolor="#038eb1" ><b><font color="#ffffff">
            Notes Last Updated:
         </td>
         <td>
            <?=date('l, m/d/Y H:i:s', $objTableRow['dteLastUpdate']) ?>
         </td>
      </tr>

      <tr>
         <td align="center"  colspan="3" ><b><font color="#ffffff">
            <input type="submit"
                 name="cmdAdd"
                 value="Click Here to Update Table and Field Comments"
                 class="btn"
                    onmouseover="this.className='btn btnhov'"
                    onmouseout="this.className='btn'"
                 >
         </td>
      </tr>

      <tr>
         <td colspan="5">
            <table width="100%" border="1" cellpadding="3" cellspacing="1"
                style="color:#000000; font-size: 100%; padding:0px;">
               <tr>
                  <td bgcolor="#9bfbff" align="center">
                     <b>Field/Type
                  </td>
                  <td bgcolor="#9bfbff" align="center">
                     <b>Null
                  </td>
                  <td bgcolor="#9bfbff" align="center">
                     <b>Default
                  </td>
                  <td bgcolor="#9bfbff" align="center">
                     <b>Keys
                  </td>
                  <td bgcolor="#9bfbff" align="center">
                     <b>Extras
                  </td>
                  <td bgcolor="#9bfbff" align="center">
                     <b>Your Annotation
                  </td>
               </tr>
<?php

   $sqlStr =
       'SELECT sf_lKeyID, sf_strFieldName, sf_strFieldType, '
         .'sf_keyType, sf_strExtra, sf_bNull, sf_varDefault, '
         .'sf_strComment '
      .'FROM  tbl_snapshot_fields '
      ."WHERE (sf_lTableID=$lTableID) "
      .'ORDER BY sf_lTableIDX;';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      for ($idx=0; $idx<$numRows; ++$idx) {
         $row = mysql_fetch_array($result);
         writeFieldDetail($row);
      }
   }

?>
            </table>
         </td>
      </tr>
   </table></form><br><br>
<?php
}

function writeFieldDetail(&$row){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $lFieldID = $row['sf_lKeyID'];
?>
   <tr>
      <td>
         <b><?=$row['sf_strFieldName']?></b><br>
         <?=$row['sf_strFieldType'] ?>
      </td>
      <td>
         <?=($row['sf_bNull']?'Null':'&nbsp;')?>
      </td>
      <td>
         <?=$row['sf_varDefault'] ?>&nbsp;
      </td>
      <td>
         <?=$row['sf_keyType'] ?>&nbsp;
      </td>
      <td>
         <?=$row['sf_strExtra'] ?>&nbsp;
      </td>
      <td>
         <textarea name="txtField<?=$lFieldID?>" rows="3" cols="35"><?=htmlspecialchars($row['sf_strComment']) ?></textarea>
      </td>
   </tr>
<?php
}


function updateSnapTableComments($lBaseFS, $lDBID, $lSnapID, $lTableID) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------

      //------------------------------
      // update the table comments
      //------------------------------
   $sqlStr =
      'UPDATE tbl_snapshot_table '
         .'SET st_strUserComment='.strPrepStr(strLoad_REQ('txtTableCom', true, false)).' '
         ."WHERE st_lKeyID=$lTableID;";
   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }


      //------------------------------
      // update field comments
      //------------------------------
   $sqlBase = 'UPDATE tbl_snapshot_fields SET sf_strComment=';
   while (list ($strKey, $vVal) = each ($_REQUEST)) {
      if (substr($strKey,0,8)=='txtField'){
         $lFieldID = (integer)substr($strKey, 8);
         $sqlStr = $sqlBase.strPrepStr(strLoad_REQ($strKey, true, false)).' '
            ."WHERE sf_lKeyID=$lFieldID;";

         $result = mysql_query($sqlStr);
         if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
            screamForHelp('Unexpected SQL error');
         }
      }
   }
   reset($_REQUEST);  // resets the array pointer

   showSnapShotDetails($lBaseFS, $lDBID, $lSnapID);
}


function updateSnapComments($lBaseFS, $lDBID, $lSnapID) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------

      //--------------------------------
      // update the snap-shot comments
      //--------------------------------
   $sqlStr =
      'UPDATE tbl_snapshot_master '
         .'SET sm_strNotes='.strPrepStr(strLoad_REQ('txtSnapNotes', true, false)).' '
         ."WHERE sm_lKeyID=$lSnapID;";
   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }

   showSnapShotDetails($lBaseFS, $lDBID, $lSnapID);
}


?>