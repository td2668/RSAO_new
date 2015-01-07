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


function show_mSM_Options($lBaseFS){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   require_once('../util/util_options.php');
   require_once('../java/util_JavaJoeCheckBox.php');

   openCenterBlock();
   openCenterSection('mySQL Schema Manager Options',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   load_mSM_Options(
            $bDropTable, $bUseFunnyQuotes, $bIncludeCommies);


?>
   <form method="POST"
        action="../main/mainOpts.php"
        name="frmOpts"
   >
   <input type="hidden" name="type"   value="OPTS">
   <input type="hidden" name="sType"  value="update">

  <table border="1" cellpadding="3" cellspacing="1"
     style="width: 256pt; color:#000000;
            background-color:#f1f1f1;font-size: 100%; padding:0px;"
  >
      <tr>
         <td colspan="5" align="center" bgcolor="#b1b1b1" ><b>
            SQL Schema Export Options
         </td>
      </tr>
      <tr>
         <td align="left" bgcolor="#d1d1d1" ><b>
            Include "Drop Table If Exists":
         </td>

         <td align="left" bgcolor="#ffffff" >
            <input type="checkbox" name="chkDropTable" value="YES" <?=($bDropTable?'checked':'')?> >
            <span onClick=toggleCheckBox(frmOpts.chkDropTable)>(check for yes)</span>
         </td>
      </tr>

      <tr>
         <td align="left" bgcolor="#d1d1d1" ><b>
            Use `Wacky` Backquotes:
         </td>

         <td align="left" bgcolor="#ffffff" >
            <input type="checkbox" name="chkBackQuotes" value="YES" <?=($bUseFunnyQuotes?'checked':'')?> >
            <span onClick=toggleCheckBox(frmOpts.chkBackQuotes)>(check for yes)</span>
         </td>
      </tr>

      <tr>
         <td align="left" bgcolor="#d1d1d1" ><b>
            Include your snap-shot,<br>table, and field comments:
         </td>

         <td align="left" bgcolor="#ffffff" >
            <input type="checkbox" name="chkAddComments" value="YES" <?=($bIncludeCommies?'checked':'')?> >
            <span onClick=toggleCheckBox(frmOpts.chkAddComments)>(check for yes)</span>
         </td>
      </tr>

      <tr>
         <td align="center"  colspan="3" ><b><font color="#ffffff">
            <input type="submit"
                 name="cmdAdd"
                 value="Click Here to Update Your Options"
                 class="btn"
                    onmouseover="this.className='btn btnhov'"
                    onmouseout="this.className='btn'"
                 >
         </td>
      </tr>
   </table></form><br><br>
<?php

   closeCenterSection();
   closeCenterBlock();
}

function save_mSM_Options($lBaseFS){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   openCenterBlock();
   openCenterSection('mySQL Schema Manager Options',
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   $bDropTable      = @$_REQUEST['chkDropTable']=='YES';
   $bUseFunnyQuotes = @$_REQUEST['chkBackQuotes']=='YES';
   $bIncludeCommies = @$_REQUEST['chkAddComments']=='YES';

   $sqlStr =
      'UPDATE tbl_options '
      .'SET '
         .'op_bAddDropTable='.($bDropTable?'1':'0').', '
         .'op_bUseBackQuotes='.($bUseFunnyQuotes?'1':'0').', '
         .'op_bIncludeCommentsInExport='.($bIncludeCommies?'1':'0').';';

   $result = mysql_query($sqlStr);
   if (bSQLError('SQL error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__, $sqlStr) ) {
      screamForHelp('Unexpected SQL error');
   }

   ?>
      Your <b><i>mySQL Schema Manager</b></i> options were saved.<br><br>
      <a href="../main/mainOpts.php">
      Click here to continue....</a><br><br><br>
   <?php

   closeCenterSection();
   closeCenterBlock();

}


?>