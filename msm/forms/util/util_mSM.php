<?php
//---------------------------------------------------------------------
// Developed by Database Austin 
// http://www.dbaustin.com
//
// This software is provided under the GPL.
// Please see http://www.gnu.org/copyleft/gpl.html for details.
//---------------------------------------------------------------------
// screamForHelp('error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__);
//if ($_SESSION['hs_bDumpVars']) echo('<b>file:</b> '.__FILE__."<br>\n");

function makeTheConnection($strDBName, &$dbSel) {
//---------------------------------------------------------------------
// create a mySQL connection
//---------------------------------------------------------------------

//echo(
// '<br>CS_DB_HOST='.CS_DB_HOST
//.'<br>CS_DB_USER='.CS_DB_USER
//.'<br>CS_DB_PWORD='.CS_DB_PWORD
//);

   $db = mysql_connect(CS_DB_HOST, CS_DB_USER, CS_DB_PWORD);

   if (!$db) {
      echo ('<br>Could not connect to the database. Please try again later.<br><br>');
      return(False);
   } else {
      $dbSel = mysql_select_db($strDBName);
      if (!$dbSel) {
         echo ('<br>Can\'t select DB ' . $strDBName.'; check GRANT<br><br>');
         return(False);
      }else {
         return($db);
      }
   }
}

function load_JS_BuildEmailScript(){
//---------------------------------------------------------------
// insert a simple java script routine to help defeat the
// spam-rats' email harvesters.
//---------------------------------------------------------------
?>
   <SCRIPT LANGUAGE="JavaScript">
   function buildEmailAddr(sUser, sSite) {
      document.write('<a href=\"mailto:' + sUser + '@' + sSite + '\">');
      document.write(sUser + '@' + sSite + '</a>');
   }
   // End -->
   </SCRIPT>
<?php
}

function sessionExpireTest() {
//---------------------------------------------------------------
// return to the login form if session expired
// Also check against session impersonation
// (from article at http://shiflett.org/articles/the-truth-about-sessions
//---------------------------------------------------------------
   if (($_SESSION['hs_cookieTest'] != 'hs Peanut Butter')||
       (md5($_SERVER['HTTP_USER_AGENT'])!=$_SESSION['HTTP_USER_AGENT'])) {
     ?>
        <br><br><font color="red" size="4"><b>
        Your session has expired.<br><br></b></font>
        Please
        <a href="../main/login.php">click here</a>
        to continue working.
        </body>
        </html>
      <?php
      die;
   }
}

?>