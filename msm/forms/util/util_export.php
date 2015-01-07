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


function exportHeader() {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
?>
<html>
<body style="font-family: monospace;">

#-------------------------------------------------------------<br>
#  <?=CS_PROGNAME?>, version <?=CS_VERSION?><br>
#  <?=CS_SORCEFORGE_PROJ_URL ?><br>
#  Output commencing on <?=date('l, m/d/Y H:i:s')?><br>
#-------------------------------------------------------------<br>


<?php
}


function closeExport(){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
?>
#<br>
#<br>
# Output completed at <?=date('l, m/d/Y H:i:s')?><br>
</body>
</html>
<?php
}

function xlateFieldFlags($strFlags,
            &$bNotNull,       &$bPrimaryKey, &$bUniqueKey,
            &$bMultipleKey,   &$bBlob,       &$bUnsigned,
            &$bZeroFill,      &$bBinary,     &$bEnum,
            &$bAutoIncrement, &$bTimeStamp) {
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------

   $bNotNull       = false;
   $bPrimaryKey    = false;
   $bUniqueKey     = false;
   $bMultipleKey   = false;
   $bBlob          = false;
   $bUnsigned      = false;
   $bZeroFill      = false;
   $bBinary        = false;
   $bEnum          = false;
   $bAutoIncrement = false;
   $bTimeStamp     = false;

   $strFlags = explode(' ', $strFlags);
   foreach ($strFlags as $strFlag) {
      switch ($strFlag) {
         case '':                                       break;
         case 'not_null':       $bNotNull       = true; break;
         case 'primary_key':    $bPrimaryKey    = true; break;
         case 'unique_key':     $bUniqueKey     = true; break;
         case 'multiple_key':   $bMultipleKey   = true; break;
         case 'blob':           $bBlob          = true; break;
         case 'unsigned':       $bUnsigned      = true; break;
         case 'zerofill':       $bZeroFill      = true; break;
         case 'binary':         $bBinary        = true; break;
         case 'enum':           $bEnum          = true; break;
         case 'auto_increment': $bAutoIncrement = true; break;
         case 'timestamp':      $bTimeStamp     = true; break;
            break;
         default:
            screamForHelp('UNRECOGNIZED MYSQL FIELD PROPERTY "'.$strFlag.'"<br>error!!!<br><b>line:</b> '.__LINE__.'<br><b>file:</b> '.__FILE__.'<br><b>function:</b> '.__FUNCTION__."<br>\n");
            break;
      }
   }
}

?>