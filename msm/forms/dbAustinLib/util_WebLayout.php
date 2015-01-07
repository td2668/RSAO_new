<?php
//---------------------------------------------------------------------------------
// copyright (c) 2004 by Database Austin. All rights reserved.
//---------------------------------------------------------------------------------

function displayHTML_Header(
                    $strTitle,          $strCSS_FileName,
                    $strBackColor,      $strBackImage,
                    $strTopBorderImage, $strLeftLogo,
                    $strCenterLogo,     $strLinkLeftImage,
                    $strLinkCntrImage,  $strBodyTag=''){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   if ($strLinkLeftImage=='') {
      $strLinkLeft =  '';
   }else {
      $strLinkLeft =  '<a href="'.$strLinkLeftImage.'">';
   }

   if ($strLinkCntrImage=='') {
      $strLinkCenter = '';
   }else {
      $strLinkCenter = '<a href="'.$strLinkCntrImage.'">';
   }

?>
<html>
   <head>
      <title><?=$strTitle ?></title>
      <?php
      if ($strCSS_FileName!='') {
      ?>
         <link rel="stylesheet" href="<?=$strCSS_FileName?>" type="text/css" />
      <?php
      }
      ?>
   </head>

   <body bgcolor="<?=$strBackColor?>"  <?=$strBodyTag ?>
         style="background-image: url(<?=$strBackImage?>)">

      <table
         border="0"
         cellpadding="0"
         cellspacing="0"
         summary=""
         width="100%"
      >

         <tr>
            <td valign="center"
                width="25%"
                height="70"
                   style="padding-left: 5pt;
                   padding-right : 5pt;
                   background-repeat: repeat-x;
                   background-image: url(<?=$strTopBorderImage ?>);"
            >
               <?=$strLinkLeft?>
               <img src="<?=$strLeftLogo?>" border="0"></a>
            </td>

            <td valign="center"
                   style="padding-left: 5pt;
                   padding-right : 5pt;
                   background-image: url(<?=$strTopBorderImage?>);
                   background-repeat: repeat-x"
                   align="left" >
               <?php
                  if ($strCenterLogo!='') {
               ?>
                  <?=$strLinkCenter?>
                  <img alt="dbAustinLib"
                      src="<?=$strCenterLogo?>" border="0" />
                  </a>
               <?php
                  }
               ?>
            </td>
         </tr>
      </table>
<?php
}


function openLayoutTable(){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   echo(
    "<!----- layout table ---->\n"
   ."<table\n"
      ."border=\"0\"\n"
      ."cellpadding=\"0\"\n"
      ."cellspacing=\"0\"\n"
      ."summary=\"\"\n"
      ."width=\"100%\"\n"
   .">\n");

}

function openCenterBlock(){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
?>
   <td valign="top" align="left"><!----- open center section ---->
<?php
}

function openCenterSection($strTitle, $strBackColorTable, $strBackColorTD,
                           $strFontColorTitle, $lFontSize=10){
//--------------------------------------------------------------------------
//  to "forward" a message to a future screen, set
//         $_SESSION['msm_strForwardMSG']
//--------------------------------------------------------------------------
   echo(
      '<table '
          .'border="0" '
          .'cellpadding="5" '
          .'cellspacing="0" '
          .'style="background-color:'.$strBackColorTable.'; '
                 .'border:1px solid '.$strBackColorTD.';" '
          .'width="95%"> '."\n"

         .'<tr>'
            .'<td style="background-color: '.$strBackColorTD.'; '
               .'font-size: '.$lFontSize.'pt; '
               .'color: '.$strFontColorTitle.'"> '
                     .'<b>'.$strTitle."</b>\n"
            ."</td>\n"
         ."</tr>\n"
         ."<tr>\n"
            .'<td valign="top"> '
               .'<table width="100%" border="0" cellpadding="2" cellspacing="0"> '
                  ."<tr>\n"
                     ."<td>\n");

      // the previous screen can "forward" a message to this screen
   $strHoldMessage = @$_SESSION['msm_strForwardMSG'];
   if ($strHoldMessage!='') {
      echo($strHoldMessage);
      $_SESSION['msm_strForwardMSG'] = '';
   }
}



function closeCenterSection(){
//--------------------------------------------------------------------------
// note: the string of "&nbsp;" is to overcome an idiotic IE bug that
// doesn't properly assign cell widths. Firefox works properly. I've
// got a call in to Bill Gates, so IE should be fixed any day now....
//--------------------------------------------------------------------------
   echo(
                      "</td>\n"
                  ."</tr>\n"
               ."</table><!----- close center section ---->\n"
            ."</td>\n"
         ."</tr>\n"
      ."</table><br>\n");

}

function closeCenterBlock(){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   echo("</td>\n");
}

function writeFooter($strCopyright){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
?>
   <tr>
      <td colspan="3" align="center">
         <p>
         <font style="font-size: 10pt;">
         <?=$strCopyright?><br /></font>
         </p>
      </td>
   </tr>
<?php
}

function closeHTML(){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
?>
         </table>
      </body>
   </html>
<?php
}



function openLeftLinkBoxes($lWidth) {
//--------------------------------------------------------------------------
// $lWidth should be expressed in "em's" - the height of the element's font
//--------------------------------------------------------------------------

   echo(
      "<td \n"
          ."valign=\"top\" \n"
          ."style=\" \n"
             .'width: '.$lWidth.'em; '
             .'padding-left: 5pt; '
             .'padding-right: 5pt;" >'."\n");
}

function closeLeftLinkBoxes(){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   echo("</td>\n");
}

function openSingleLeftLinkBox(
                      $strTitle,          $strBackColorTable, $strBackColorTD,
                      $strFontColorTitle, $lFontSize=10){
//--------------------------------------------------------------------------
// $strBackColorTable - table background
// $strBackColorTD - background color of the top banner
//--------------------------------------------------------------------------
   echo(
     '<table border="0" '
          .'cellpadding="3" '
          .'cellspacing="1" '
          .'width="100%" '
          .'style="background-color: '.$strBackColorTable.'; '
                 .'border:1px solid ' .$strBackColorTD   .';" >'."\n");

   echo(
      '<tr>'
         .'<td style="background-color: '.$strBackColorTD.'; '
                    .'color: '.    $strFontColorTitle .'; '
                    .'font-size: '.$lFontSize .'pt;" '
             .'width="100%" > '
            ."<b>$strTitle </b>\n"
         .'</td>'
      .'</tr>'
      .'<tr>'
         .'<td valign="top">'
            .'<table width="100%" border="0" cellpadding="2" cellspacing="0"> '."\n\n");
}


function addRowLeftLinkBox(
             $strLink,            $strText,          $lFontSize=8,
             $bAddHelpLink=false, $strHelpWindow='', $lHelpID=-1,
             $strHelpImage='',    $lHelpWidth=300,   $lHelpHeight=400,
             $strHelpMOTitle=''){
//--------------------------------------------------------------------------
// if the link is empty, the text is displayed with no hyperlink
//--------------------------------------------------------------------------
   if ($strLink=='') {
      $strHTML_Link = '';
      $strHTML_CloseLink = '';
   }else {
      $strHTML_Link = '<a href="'.$strLink.'">';
      $strHTML_CloseLink = '</a>';
   }

   echo(
    '<tr> '
       .'<td align="left" nowrap="nowrap"> '
          .'<font style="font-size: '.$lFontSize.'pt;"> '
          .$strHTML_Link.$strText.$strHTML_CloseLink);

   if ($bAddHelpLink) {
      placeHelpButton($strHelpWindow, $lHelpID,     $strHelpImage,
                      $lHelpWidth,    $lHelpHeight, $strHelpMOTitle);
   }
   echo("</td></tr>\n");
}

function add_seperatorLine_LLBox($strImg){
//--------------------------------------------------------------------------
// deprecated, use addHR_LeftLinkBox($strLineImg='')
//--------------------------------------------------------------------------
   echo(
      '<tr> '
         .'<td colspan="7"><img border="0" src="'.$strImg.'" width="100%" height="1"></td>'
     ."</tr>\n");
}

function addHR_LeftLinkBox($strLineImg=''){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   if ($strLineImg==''){
      ?>
         <tr>
            <td>
               <hr />
            </td>
         </tr>
      <?php
   }else {
      ?>
         <tr>
            <td valign="center">
               <img
                  style="margin-top: 3pt;"
                  border="0"
                  src="<?=$strLineImg ?>" width="95%" height="1">
            </td>
         </tr>
      <?php
   }
}

function closeSingleLeftLinkBox(){
//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
   echo(
           '</table>'
        .'</td>'
     .'</tr>'
  ."</table><br>\n");
}

?>