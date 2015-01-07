<?php
//---------------------------------------------------------------------
// copyright (c) 2004 by Database Austin
//
// This software is provided under the GPL.
// Please see http://www.gnu.org/copyleft/gpl.html for details.
//
//  function dumpRequest($bDumpRequestArray=true, $bDumpSessionArray=true, $bDumpServerArray=false) {
//  function bSQLError($strNote='', $sqlStr='#unknown#') {
//  function lDecryptID($strKeyID, $Seed) {
//  function strEncryptID($lKeyID, $Seed) {
//  function hex2bin($strHex){
//  function strPrepNow() {
//  function strPrepDateTime($lTimeStamp) {
//  function strPrepDate($lTimeStamp) {
//  function make_seed() {
//  function strPrepStr($myStr) {
//  function screamForHelp($strMsg) {
//  function echoB($bBool, $strBool) {
//  function strBuildAddress($strAddress1, $strAddress2, $strCity, $strState, $strCountry, $strZip, $bHTML_BR)
//  function insertJavaBuildEmailAddr() {
//  function strLoad_REQ($strReqVarName, $bTrim=true, $bHideErr=false)
//
//  function zStrToTime($strTime)
//  function strFormatName($strFirstName, $strMiddleName, $strLastName, $strNickname, $strTitle, $strTitle2, $iFormatStyle, $bIncludeNick, $bItalNick)
//  function strAddIfNB($strTest, $strPrefix, $strPostfix, $strElse)
//  function dteServerAdjusted($dteOriginal)
//---------------------------------------------------------------------
// screamForHelp('<br>error on line '.__LINE__.',<br>file '.__FILE__.',<br>function '.__FUNCTION__);

//if (CB_ENABLE_MCRYPT) {
//   define('ENCRYPT_MODE',MCRYPT_BLOWFISH);
//}

function dumpRequest($bDumpRequestArray=true, $bDumpSessionArray=true, $bDumpServerArray=false) {
//-------------------------------------------------------------------
// developer utility - dump useful information about the
// request array, session array, and server array
//-------------------------------------------------------------------
   if ($bDumpRequestArray) {
      dump_REQUEST_array();
   }

   if ($bDumpSessionArray) {
      dump_SESSION_array();
   }

   if ($bDumpServerArray) {
      dump_SERVER_array();
   }

   @ob_flush(); @flush();
}

function dump_REQUEST_array() {
//-------------------------------------------------------------------
// dump info about the request array
//-------------------------------------------------------------------
   echo('<br><u><b>REQUEST</b></u><br>');
   echo('count='.count($_REQUEST)."<br>\n");
   while (list ($key, $val) = each ($_REQUEST)) {
      if (is_array($val)) {
         echo("<b>Array $key:</b><br>\n");
         foreach ($val as $vArrayKey=>$vArrayVal) {
            echo("&nbsp;&nbsp;&nbsp;$vArrayKey = ".htmlspecialchars($vArrayVal)."<br>\n");
         }
      }else {   
         echo "<b>$key</b> = ".htmlspecialchars($val)."<br>\n";
      }
   }
   reset($_REQUEST);  // resets the array pointer
}

function dump_SESSION_array() {
//-------------------------------------------------------------------
//
//-------------------------------------------------------------------

      //--------------------------------------------------------------
      // if the program hasn't started the session yet, start it here
      // or it will not be possible to dump the session variables
      //--------------------------------------------------------------
   if (!defined(session_id())){
      @session_start();
   }

   echo('<br><u><b>SESSION</u></b><br>');
   while (list ($key, $val) = each ($_SESSION)) {
      if (is_array($val)) {
         echo("<b>Array $key:</b><br>\n");
         foreach ($val as $vArrayKey=>$vArrayVal) {
            echo("&nbsp;&nbsp;&nbsp;$vArrayKey = ".htmlspecialchars($vArrayVal)."<br>\n");
         }
      }else {   
         echo "<b>$key</b> = ".htmlspecialchars($val)."<br>\n";
      }
   }
   reset($_SESSION);
}

function dump_SERVER_array() {
//-------------------------------------------------------------------
//
//-------------------------------------------------------------------

   echo('<br><u><b>SERVER</b></u><br>');
   while (list ($key, $val) = each ($_SERVER)) {
      if (is_array($val)) {
         echo("<b>Array $key:</b><br>\n");
         foreach ($val as $vArrayKey=>$vArrayVal) {
            echo("&nbsp;&nbsp;&nbsp;$vArrayKey = ".htmlspecialchars($vArrayVal)."<br>\n");
         }
      }else {
         echo "<b>$key</b> = ".htmlspecialchars($val)."<br>\n";
      }
   }
   reset($_SERVER);
}


function bSQLError($strNote='', $sqlStr='#unknown#') {
//-------------------------------------------------------------------
// return true if SQL error and print an error message
//-------------------------------------------------------------------
   $strHold = mysql_error();
   $iLen = strlen($strHold);
   if ($iLen>0) {
      echo("\n<font color=\"red\">\n****<br>\n**** mysql error: ".$strHold."\n<br>****</font><br>\n");
      if (strlen($strNote)>0){
         echo($strNote."<br>\n");
      }
      if (CB_SHOWSQL_ON_ERROR) {
         echo("\nsqlStr=<br>$sqlStr\n<br><br>");
      }
   }
   return($iLen>0);
}

function bSQLErrorNonFatal() {
//-------------------------------------------------------------------
// return true if SQL error and print an error message
//-------------------------------------------------------------------
   $strHold = mysql_error();
   $iLen = strlen($strHold);
   if ($iLen>0) {
   ?>   
      The following non-fatal error was detected: <br>
      <font color="red">****<br>
      **** mysql error: <?=$strHold?><br>
      ****</font><br><br>
   <?php
   }
   return($iLen>0);
}


function lDecryptID($strKeyID, $Seed) {
//------------------------------------------------------------------
//  if the mcrypt package is not available, the original key is
//  returned as an integer.
//------------------------------------------------------------------
   if (CB_ENABLE_MCRYPT) {
         // keys converted to hex equivalen
      $strKeyID = hex2bin($strKeyID);

      srand(1);
      $iv = mcrypt_create_iv (mcrypt_get_iv_size (ENCRYPT_MODE, MCRYPT_MODE_ECB), MCRYPT_RAND);
      $key = $Seed;
      $strHold = mcrypt_decrypt (ENCRYPT_MODE, $key, $strKeyID, MCRYPT_MODE_ECB, $iv);
      return((integer)$strHold);
   }else {
      return((integer)$strKeyID);
   }
}

function strEncryptID($lKeyID, $Seed) {
//------------------------------------------------------------------
//  if the mcrypt package is not available, the key is returned
//  as a string. It is strongly recommended that the mcrypt
//  package be installed and keys encrypted when sent in the
//  http stream.
//------------------------------------------------------------------
   if (CB_ENABLE_MCRYPT) {
      srand(1);
      $iv = mcrypt_create_iv (mcrypt_get_iv_size (ENCRYPT_MODE, MCRYPT_MODE_ECB), MCRYPT_RAND);
      $key = $Seed;
      $text = (string)$lKeyID;
      $cryptText = mcrypt_encrypt (ENCRYPT_MODE, $key, $text, MCRYPT_MODE_ECB, $iv);
      return(bin2hex($cryptText));
   }else {
      return((string)$lKeyID);
   }
}

function hex2bin($strHex){
//-------------------------------------------------------------------------
//
//-------------------------------------------------------------------------
   $strOut = '';

   for ($idx=0; $idx<strlen($strHex); $idx+=2 ) {
      $strOut .= chr(hexdec(substr($strHex, $idx, 2)));
   }
   return($strOut);
}


function strPrepNow() {
//-------------------------------------------------------------------------
//  return the current date/time in mySQL format
//-------------------------------------------------------------------------
   return(strPrepStr(date('Y-m-d H:i:s')));
}

function strPrepDateTime($lTimeStamp) {
//-------------------------------------------------------------------------
//  return the specified date/time in mySQL format
//-------------------------------------------------------------------------
   if (is_null($lTimeStamp)) {
      return('NULL');
   }else {
      return(strPrepStr(date('Y-m-d H:i:s', $lTimeStamp)));
   }
}

function strPrepDate($lTimeStamp) {
//-------------------------------------------------------------------------
//  return the specified date only in mySQL format
//-------------------------------------------------------------------------
   if (is_null($lTimeStamp)) {
      return('NULL');
   }else {
      return(strPrepStr(date('Y-m-d', $lTimeStamp)));
   }
}

function strPrepTime($lTimeStamp) {
//-------------------------------------------------------------------------
//  return the specified time in mySQL format
//-------------------------------------------------------------------------
   if (is_null($lTimeStamp)) {
      return('NULL');
   }else {
      return(strPrepStr(date('H:i:s', $lTimeStamp)));
   }
}

function make_seed() {
//-------------------------------------------------------------------------
// seed with microseconds - from the php manual
// example: mt_srand(make_seed());
//-------------------------------------------------------------------------
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000);
}

function strPrepStr($myStr) {
//-------------------------------------------------------------------------
// part of the defense against the dark arts
//-------------------------------------------------------------------------
   return('\'' . addslashes($myStr) . '\'');
}


function screamForHelp($strMsg) {
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
?>
   <blockquote>
   <font color="RED">
      <b>An error has occurred while processing your form!<br><br>
         The following error was reported:<br>
         <?=$strMsg?><br></b>

      This is an unexpected situation and should be reviewed by technical support. <br><br><?="\n" ?>

      Please report this to technical support at
      <SCRIPT LANGUAGE="JavaScript">buildEmailAddr('<?=CS_SUPPORT_EMAIL_A ?>', '<?=CS_SUPPORT_EMAIL_B ?>');</SCRIPT>
      <br><br>
   </font>
   We apologize for this inconvenience.<br><br>
   </blockquote>
<?php
   exit;
}

function echoB($bBool, $strBool) {
//---------------------------------------------------------------
// assists in debug
//---------------------------------------------------------------
   echo('<b>'.$strBool.'=</b>'.($bBool?'Yes':'No')."<br>\n");
}

function strBuildAddress(
               $strAddress1, $strAddress2, $strCity,
               $strState,    $strCountry,  $strZip,
               $bHTML_BR){
//------------------------------------------------------------------
//
//------------------------------------------------------------------
   $strBreak = ($bHTML_BR?"<br>\n":"\n");
   $strBuildAddress = '';
   if (strlen($strAddress1.'')>0) {
      $strBuildAddress = $strAddress1.$strBreak;
   }
   if (strlen($strAddress2.'')>0) {
      $strBuildAddress .= $strAddress2.$strBreak;
   }
   if (strlen($strCity)>0){
      $strBuildAddress .= $strCity.', ';
   }
   $strBuildAddress .= $strState.' '.$strZip;
   $strUC_Country = strtoupper($strCountry);
   if (! ( ($strUC_Country=='USA')
         ||($strUC_Country=='UNITED STATES')
         ||($strUC_Country=='')
         ||($strUC_Country=='AMERICA')) ){
      $strBuildAddress .= ' '.$strCountry;
   }
   return(trim($strBuildAddress));
}

//function dteGetLocalTime($lOffsetHrs){
////------------------------------------------------------------------
////  return the server time, adjusted by an hour offset
////------------------------------------------------------------------
//   $objRightNow = getdate();
//
//   return(mktime(
//              $objRightNow['hours']+$lOffsetHrs,
//              $objRightNow['minutes'],
//              $objRightNow['seconds'],
//              $objRightNow['mon'],
//              $objRightNow['mday'],
//              $objRightNow['year']));
//}

function insertJavaBuildEmailAddr() {
//------------------------------------------------------------------
//
//------------------------------------------------------------------
?>
<SCRIPT LANGUAGE="JavaScript">
<!-------
function buildEmailAddr(sUser, sSite) {
   document.write('<a href=\"mailto:' + sUser + '@' + sSite + '\">');
   document.write(sUser + '@' + sSite + '</a>');
}
// End -->
</SCRIPT>
<?php
}

function strLoad_REQ($strReqVarName, $bTrim=true, $bHideErr=false) {
//------------------------------------------------------------------
//  This function returns a request variable. If the server's php
//  installation includes magic quotes on gpc (Get/Post/Cookies),
//  then the php routine "stripslashes" is run agains the
//  request value.
//
//  The caller can optionally trim the value and hide error messages
//  if the request variable doesn't exist.
//
//------------------------------------------------------------------
   if ($bHideErr) {
      $strBase = @$_REQUEST[$strReqVarName];
   }else {
      $strBase = $_REQUEST[$strReqVarName];
   }

   if (get_magic_quotes_gpc()) {
      $strBase = stripslashes($strBase);
   }
   if ($bTrim) {
      $strBase = trim($strBase);
   }
   return($strBase);
}

function zStrToTime($strDateTime){
//------------------------------------------------------------------
// similar to the php strtotime, but with the following 
// exceptions:
//   - an empty string returns NULL
//   - an invalid date returns NULL
//   - patch for a strtotime bug that appears in some 
//     windows versions of php
//------------------------------------------------------------------

//echo("zStrToTime, \$strDateTime=$strDateTime <br>\n");

   $strHold = trim(str_replace("/",'-',$strDateTime));
//$strHold=$strDateTime;   
//echo("zStrToTime, after replace, \$strHold=$strHold <br>\n");

   if ($strHold=='') {
      return(NULL);
   }else {
      if (($dteHold = strtotime($strHold)) === -1) {
echo("error<br>");      
         return(NULL);
      }else {
echo("zStrToTime, \$dteHold=$dteHold <br>\n");
echo("zStrToTime, \date(\$dteHold)=".date('m/d/Y',$dteHold)."<br><br>\n");
      
         return($dteHold);
      }
   }
}


function strFormatName(
              $strFirstName, $strMiddleName, $strLastName, 
              $strNickname,  $strTitle,      $strTitle2, 
              $iFormatStyle, $bIncludeNick,  $bItalNick){
//------------------------------------------------------------------
// assumes first and last are non-blank; all others can be
// blank or null
//
//  for title='Pfc.', title2='USMC'
//
//   Format Styles:
//     CI_DBA_NAME_T1T2FL - 1) Pfc. Gomer M. Pyle, USMC (Goober)   (bIncludeNick=True)
//     CI_DBA_NAME_T1T2LF - 2) Pyle USMC, Pfc. Gomer M. (Goober)   (bIncludeNick=True)
//     CI_DBA_NAME_FL     - 3) Gomer (Goober) Pyle
//     CI_DBA_NAME_LF     - 4) Pyle, Gomer (Goober)
//
//------------------------------------------------------------------
   $strNick = '';
   if ($bIncludeNick){
      if (strlen($strNickname.'')>0 ){
         if ($bItalNick){
            $strNick = '<i>';
         }
         $strNick .= '('.$strNickname.') ';
         if ($bItalNick){
            $strNick .= '</i>';
         }
      }
   }

   switch ($iFormatStyle) {
      case CI_DBA_NAME_T1T2FL:
         $strFormatName = 
                strAddIfNB($strTitle, '', ' ', '') 
               .$strFirstName.' ' 
               .strAddIfNB($strMiddleName, '', ' ', '') 
               .$strLastName
               .strAddIfNB($strTitle2, ', ', ' ', ' ')
               .$strNick;
         break;

      case CI_DBA_NAME_T1T2LF:
         $strFormatName = 
                $strLastName 
               .strAddIfNB($strTitle2, ' ', ', ', ', ') 
               .strAddIfNB($strTitle, '', ' ', '') 
               .$strFirstName.' ' 
               .strAddIfNB($strMiddleName, '', ' ', '')
               .$strNick;
         break;

      case CI_DBA_NAME_FL:
         $strFormatName = 
                $strFirstName.' '.$strNick.$strLastName;
         break;

      case CI_DBA_NAME_LF:
         $strFormatName = 
                $strLastName.', '.$strFirstName.' '.$strNick;
         break;

      default:
         $strFormatName = '#error#';
         break;
   }   
   return(trim($strFormatName));
}


function strAddIfNB($strTest, $strPrefix, $strPostfix, $strElse){
//------------------------------------------------------------------
//
//------------------------------------------------------------------
   if (strlen($strTest.'')>0) {
      $strAddIfNB = $strPrefix.$strTest.$strPostfix;
   }else {
      $strAddIfNB = $strElse;
   }
   return($strAddIfNB);
}

function dteServerAdjusted($dteOriginal){
//------------------------------------------------------------------
// modify the time by the server offset
//
// for example, time() returns the server time, dteServerAdjusted(time())
// returns the user's local time
//------------------------------------------------------------------
   return ($dteOriginal+CL_SERVEROFFSET_SEC);
}

?>