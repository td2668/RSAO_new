<?php
//---------------------------------------------------------------------
// The Empowered Non-Profit
//
// copyright (c) 2005 by Database Austin
// Austin, Texas
//
// This software is provided under the GPL.
// Please see http://www.gnu.org/copyleft/gpl.html for details.
//---------------------------------------------------------------------
// utilities to manage temporary files (such as those used in pdf
// generation)
//---------------------------------------------------------------------
// The path for the temporary directory is taken from
// $_SESSION['enp_strTempFilePath']
//---------------------------------------------------------------------
// screamForHelp('<br>error on line '.__LINE__.',<br>file '.__FILE__.',<br>function '.__FUNCTION__);

function strTempFilePath() {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   return($_SESSION['enp_strTempFilePath']);
}

function strTempVirtualFilePath() {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   return($_SESSION['enp_strTempVirtualFilePath']);
}

function strTemp_file_name($strLabel1, $strLabel2, $strLabel3, $bAddRand, $strExt) {
//---------------------------------------------------------------------
// returns a filename in the following format
//
//     yymmdd_hhmmss_rrrrr[_strLabel1][_strLabel2][_strLabel3][.[$strExt]]
//
// where rrrrr is a 5-digit random number (if bAddRand=True)
//
// labels 1,2,3 and extension are optional.
//
//---------------------------------------------------------------------

   $dteNow = time();

   $strHold =
       date('y',$dteNow).date('m',$dteNow).date('d',$dteNow).'_'
      .date('H',$dteNow).date('i',$dteNow).date('s',$dteNow);

   if ($bAddRand) {
      $lRandNum = mt_rand(0, 99999);
      $strHold = $strHold . "_" . str_pad((string)$lRandNum, 5, '0', STR_PAD_LEFT);
   }

   if (strlen($strLabel1)>0) { $strHold = $strHold . "_" . $strLabel1; }
   if (strlen($strLabel2)>0) { $strHold = $strHold . "_" . $strLabel2; }
   if (strlen($strLabel3)>0) { $strHold = $strHold . "_" . $strLabel3; }

      // add the optional file extension

   if (strlen($strExt)>0) {
      $strHold = $strHold . ".$strExt";
   }
   return($strHold);
}

?>