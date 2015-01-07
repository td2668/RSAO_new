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


function 

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


?>