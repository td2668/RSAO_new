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

function showDB_entriesMain($lBaseFS){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_db.php');
   
   openCenterBlock();
   openCenterSection('Your Databases',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   showDB_entries($lBaseFS);

   closeCenterSection();
   closeCenterBlock();

}


function showDB_entries($lBaseFS){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $result = mysql_list_dbs();
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, 'mysql_list_dbs') ) {
      screamForHelp('Unexpected SQL error');
   }else{
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         screamForHelp('UNEXPECTED EOF<br>error!!!<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
      }else {
         openDB_EntriesTable($lBaseFS);
         $strSmallLink = 'style="font-size: '.($lBaseFS-1).'pt;" ';
         for ($idx=0; $idx<$numRows; ++$idx) {

            $row = mysql_fetch_array($result);
            writeDB_EntryRow($row, $strSmallLink);
         }
         closeDB_EntriesTable();
      }
   }
}

function writeDB_EntryRow(&$row, &$strSmallLink){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $strHoldDB = $row['Database'];
   
   if ($strHoldDB!=CS_MSM_DB_NAME) {   
      $lNumSS = lNumSnapshotsViaDBName($strHoldDB, $lDBID);
      
      if ($lNumSS>0) {
         $strLinkViewSS =
            '&nbsp;&nbsp;'
           .'<a href="../main/mainOpts.php?type=SNAP'
              .'&sType=VIEW'
              .'&ssType=MAIN'
              .'&DBID='.$lDBID
              .'" '.$strSmallLink.'>(view)</a>';         
      }else {
         $strLinkViewSS = '';
      }
      
      $strLinkAddSS =
            '<a href="../main/mainOpts.php?type=SNAP&sType=ADDNEW&ssType=step1'
              .'&SDBN='.urlencode($strHoldDB)
              .'" '.$strSmallLink.'>(add new)</a>';
      echo('<tr>'
              .'<td>'
                 .'<b>'.$strHoldDB.'</b>'
              ."</td>\n");
          
      echo('<td align="center">'.$lNumSS
          .' '.$strLinkAddSS.$strLinkViewSS
          ."</td>\n");

      echo('<td align="center">&nbsp;'."</td>\n");                

      echo("</tr>\n");
   }
}


function openDB_EntriesTable($lBaseFS){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   $strStyle = 'style="font-size:'.($lBaseFS+2).'pt;" ';
?>
  <table border="1" cellpadding="3" cellspacing="1"
     style="width: 456pt; color:#000000;
            background-color:#f1f1f1;font-size: 100%; padding:0px;"
  >
      <tr>
         <td align="center" bgcolor="#b1b1b1" <?=$strStyle?> ><b>
            Database
         </td>

         <td align="center" bgcolor="#b1b1b1" <?=$strStyle?> ><b>
            # of Snapshots
         </td>
         
         <td align="center" bgcolor="#b1b1b1" <?=$strStyle?> ><b>
            # of Views
         </td>         

      </tr>
<?php
}


function closeDB_EntriesTable(){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   echo("</table><br><br><br>\n");
}




?>