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


function writeMainOptions($lMainOptType){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
//   $bAdmin = PM_bPermit($lPermissions, CI_PERM_CHAPTER_DBA);
   $lLinkFS = CL_BASE_FS-1;

   openSingleLeftLinkBox('mSM Options',
               CS_COLOR_OPTS_TBLBGR, CS_COLOR_OPTS_TBLHDRBGR, CS_COLOR_OPTS_TBLHDRFONT,
               $lLinkFS);

   addRowLeftLinkBox('../main/mainOpts.php', 
                  'Home',
                  $lLinkFS, false, '', -1, CS_HELP_IMAGE, CL_HELP_WIDTH, CL_HELP_HEIGHT,
                  '');

   addRowLeftLinkBox('../main/mainOpts.php?type=DB&sType=MAIN', 
                  'Databases',
                  $lLinkFS, false, 'helpWindow_5', 5, CS_HELP_IMAGE, CL_HELP_WIDTH, CL_HELP_HEIGHT,
                  'Info about the DATABASES Option');

   addRowLeftLinkBox('../main/mainOpts.php?type=OPTS&sType=DISPLAY', 
                  'Options',
                  $lLinkFS, false, 'helpWindow_5', 5, CS_HELP_IMAGE, CL_HELP_WIDTH, CL_HELP_HEIGHT,
                  '');
                     
   addRowLeftLinkBox('../../documentation/mSM_UsersGuide.pdf', 
                  'Help',
                  $lLinkFS, false, '', -1, CS_HELP_IMAGE, CL_HELP_WIDTH, CL_HELP_HEIGHT,
                  '');                     
                     
   closeSingleLeftLinkBox();
}

function show_enp_Welcome() {
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
   $lBaseFS = CL_BASE_FS;
   
   openCenterBlock();
   openCenterSection('Welcome to '.CS_PROGNAME,
                CS_COLOR_MAIN_TBLBGR, CS_COLOR_MAIN_TBLHDRBGR, CS_COLOR_MAIN_TBLHDRFONT,
                $lBaseFS);

   welcomeUserMain($lBaseFS);

   closeCenterSection();
   closeCenterBlock();
}

function welcomeUserMain($lBaseFS){
//----------------------------------------------------------------------
//
//----------------------------------------------------------------------
?>
   <font style="font-size: <?=$lBaseFS+2 ?>pt;"><b>
   Welcome to <?=CS_PROGNAME?>!</b></font><br><br>

   <font style="font-size: <?=$lBaseFS ?>pt;">
      This application will help you document <br>
      and manage your mySQL schemas.
      <br><br><br>
<?php
}


?>