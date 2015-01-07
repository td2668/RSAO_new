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
// Copyright (c) 2004 Database Austin                  www.dbaustin.com
//---------------------------------------------------------------------
// Images:
//   These navigation utilities require 3 groups of 4 images. The images
//   represent navigation buttons for displayed pages of records. The
//   images are used to navigate to first/previous/next/last page of
//   records.
//
//   For each navigation, there is a basic button, a mouse-over button,
//   and a mouse-depressed button. There are also two background colors:
//   a background color for first/last, and a background color for
//   prev/next. You probably want to coordinate the button image colors with
//   the background colors. The background image colors will only be
//   visible of the button image is not displayed. This occurs when the
//   context doesn't support the image (i.e. the "first" button is not
//   displayed if the user is already on the first page).
//
//---------------------------------------------------------------------------
// For a fairly compact example of using this recordset navigation, see
// routine searchDisplayEOY in file forms/reports/reportGiftsEOY.php
//---------------------------------------------------------------------------


function rs_navigate_preloadRecordSelectorImages(
               $strFirst, $strFirstMO, $strFirstMD,
               $strLast,  $strLastMO,  $strLastMD,
               $strNext,  $strNextMO,  $strNextMD,
               $strPrev,  $strPrevMO,  $strPrevMD) {
//---------------------------------------------------------------------------
// this routine writes the javascript that will pre-load the images
// associated with record-set navigation.
//
// Inputs:
//   $strFirst/Last/Next/Prev - default naviation images
//   $strFirstMO/LastMO, etc  - mouse-over image
//   $strFirstMD/LastMD, etc  - mouse-depressed image
//---------------------------------------------------------------------------
?>

<script type="text/javascript">
<!--
if (typeof(rs_page_first) != "object") {
   rs_page_first        = new Image();
   rs_page_first.src    = '<?=$strFirst ?>';
   rs_page_first_mo     = new Image();
   rs_page_first_mo.src = '<?=$strFirstMO ?>';
   rs_page_first_md     = new Image();
   rs_page_first_md.src = '<?=$strFirstMD ?>';

   rs_page_last         = new Image();
   rs_page_last.src     = '<?=$strLast ?>';
   rs_page_last_mo      = new Image();
   rs_page_last_mo.src  = '<?=$strLastMO ?>';
   rs_page_last_md      = new Image();
   rs_page_last_md.src  = '<?=$strLastMD ?>';

   rs_page_prev         = new Image();
   rs_page_prev.src     = '<?=$strPrev ?>';
   rs_page_prev_mo      = new Image();
   rs_page_prev_mo.src  = '<?=$strPrevMO ?>';
   rs_page_prev_md      = new Image();
   rs_page_prev_md.src  = '<?=$strPrevMD ?>';

   rs_page_next         = new Image();
   rs_page_next.src     = '<?=$strNext ?>';
   rs_page_next_mo      = new Image();
   rs_page_next_mo.src  = '<?=$strNextMO ?>';
   rs_page_next_md      = new Image();
   rs_page_next_md.src  = '<?=$strNextMD ?>';
}
// -->
</script>
<?php
}

function rs_navigate_insertNavigationTable(
                 $lNavButtonWidth,     $lNavButtonHeight,
                 $strFirstLastBGColor, $strPrevNextBGColor,
                 $strFirstLink,        $strFirstImageSrc,
                 $strPrevLink,         $strPrevImageSrc,
                 $strNextLink,         $strNextImageSrc,
                 $strLastLink,         $strLastImageSrc
                 ){
//---------------------------------------------------------------------------
// this routine creates an html table that contains 4 record set
// navigation buttons. The caller supplies the links, button images,
// image sizes (all images must be the same size), and the background
// colors (the first/last table entries have the same background color,
// and the prev/next table entries have the same color).
//
// If an image file source is NULL, then the corresponding table entry
// is blank.
//---------------------------------------------------------------------------
   $lTableWidth = $lNavButtonWidth * 4;

?>
   <table
      cellpadding="0"
      cellspacing="1"
      border="0"
      width="<?=$lTableWidth ?>"
      height="<?=$lNavButtonHeight ?>">

      <tr>
<?php

   rs_navigate_writeSingleNavEntry(
             $strFirstLink,          $strFirstLastBGColor,   $strFirstImageSrc,
             $lNavButtonWidth,       $lNavButtonHeight,
             'First Page',
             'rs_page_first_mo.src', 'rs_page_first_md.src', 'rs_page_first.src');

   rs_navigate_writeSingleNavEntry(
             $strPrevLink,           $strPrevNextBGColor,    $strPrevImageSrc,
             $lNavButtonWidth,       $lNavButtonHeight,
             'Previous Page',
             'rs_page_prev_mo.src',  'rs_page_prev_md.src',  'rs_page_prev.src');

   rs_navigate_writeSingleNavEntry(
             $strNextLink,           $strPrevNextBGColor,    $strNextImageSrc,
             $lNavButtonWidth,       $lNavButtonHeight,
             'Next Page',
             'rs_page_next_mo.src',  'rs_page_next_md.src',  'rs_page_next.src');

   rs_navigate_writeSingleNavEntry(
             $strLastLink,           $strFirstLastBGColor,   $strLastImageSrc,
             $lNavButtonWidth,       $lNavButtonHeight,
             'Last Page',
             'rs_page_last_mo.src',  'rs_page_last_md.src',  'rs_page_last.src');
?>
      </tr>
   </table>
<?php
}


function rs_navigate_writeSingleNavEntry(
             $strLink,          $strBGColor,       $strImageSrc,
             $lNavButtonWidth,  $lNavButtonHeight,
             $strAltText,
             $strMO_Obj,        $strMD_Obj,        $strSrc_Obj){
//---------------------------------------------------------------------------
//
//---------------------------------------------------------------------------
   if (is_null($strLink)) {
      echo(
         '<td width="25%" bgcolor="'.$strBGColor.'">&nbsp;</td>');
   }else {
      echo(
         '<td width="25%" bgcolor="'.$strBGColor.'">'
            .'<a href="'.$strLink.'">'
            .'<img onmouseover="this.src='.$strMO_Obj.'" '
                 .'onmousedown="this.src='.$strMD_Obj.'" '
                 .'onmouseout="this.src='.$strSrc_Obj.'" '
                 .'src="'.$strImageSrc.'" '
                 .'width="'.$lNavButtonWidth.'" '
                 .'height="'.$lNavButtonHeight.'" '
                 .'border="0" '
                 .'alt="'.$strAltText.'" ></a>'
         ."</td>\n");
      
   }
}



function rs_navigate_pageDDL($lCurrentPage, $lTotPages, $strOnChangeLink){
//---------------------------------------------------------------------------
// This utility places a drop-down list of the available record pages. When
// the user selects a page from the drop-down list, java script invokes a
// transfer to the caller's link. The destination page number is appended
// to the transfer link.
//
// Inputs
//    $lCurrentPage - the ddl is set to this page
//    $lTotPages - total pages available for display. Function of the
//         total number of records and the records displayed per page
//    $strOnChangeLink - string representing the destination when the
//         user selects a page. The user's selection is appended to the
//         link.
//
//  Example:
//      rs_navigate_pageDDL(3, 22, './showDataPage.php?goToPage=');
//
//  Note that the caller does not need to create a form for this ddl object.
//---------------------------------------------------------------------------
?>
   Page
   <select
        onchange="location.href='<?=$strOnChangeLink?>' + this.options[this.selectedIndex].value"
        style="font-weight:bold">
<?php

   for ($idx=1; $idx<=$lTotPages; ++$idx) {
      ?>
         <option value="<?=$idx ?>" <?=($idx==$lCurrentPage?'selected':'')?> ><?=$idx?></option>
      <?php
   }
?>
   </select> of <b>[<?=$lTotPages ?>]</b><br>
<?php
}

function rs_navigate_recsPerPageDDL($lCurrentRecsPerPage, $lRecsPerPage, $strOnChangeLink){
//---------------------------------------------------------------------------
// This utility places a drop-down list for records per page selection. When
// the user selects an entry from the drop-down list, java script invokes a
// transfer to the caller's link. The selected recs-per-page is appended
// to the transfer link.
//
// Inputs
//    $lCurrentRecsPerPage - the ddl is set to this value
//    $lRecsPerPage[] - array containing the allowable recs-per-pages values
//    $strOnChangeLink - string representing the destination when the
//         user selects a ddl value. The user's selection is appended to the
//         link.
//
//  Example:
//      rs_navigate_recsPerPageDDL(30, $lArryRecsPerPage, './showDataPage.php?recsPerPage=');
//
//  Note that the caller does not need to create a form for this ddl object.
//---------------------------------------------------------------------------
?>
   <select
      onchange="location.href='<?=$strOnChangeLink ?>' + this.options[this.selectedIndex].value"
      style="font-weight:bold">

<?php

   foreach ($lRecsPerPage as $lRecCnt) {
      echo(
         '<option value="'.$lRecCnt.'" '
              .($lRecCnt==$lCurrentRecsPerPage?'selected':'')
              .'>'.$lRecCnt."</option>\n");
   }
   echo("</select> <i>records per page</i>\n");
}


function rs_navigate_lTotPagesCalcs($lTotRecs, $lRecsPerPage){
//---------------------------------------------------------------------
// Utility to return the total number of pages, based on the total
// number of records and the records displayed per page
//
//  INPUTS
//     $lTotRecs  - the total number of records
//     $lRecsPerPage - the number of records to display per screen display
//
//   OUTPUTS
//     (function, integer) - total pages to display
//---------------------------------------------------------------------
   $lTotPages = (integer)($lTotRecs/$lRecsPerPage);
   if (($lTotPages*$lRecsPerPage)!=$lTotRecs) ++$lTotPages;

   return($lTotPages);
}

function rs_navigate_lStartRecLastPage($lTotRecs, $lRecsPerPage){
//---------------------------------------------------------------------
// return the starting record number for the last page of display,
// given the inputs:
//    $lTotRecs - total number of records in the recordset
//    $lRecsPerPage - number of records displayed per page
//---------------------------------------------------------------------
   $lLastStartRec =
       (integer)((rs_navigate_lTotPagesCalcs($lTotRecs, $lRecsPerPage)-1) * $lRecsPerPage) + 1;

   return($lLastStartRec);
}


function set_RS_Navigation(
                    $strLinkBase,  $strImgBase, $lTotRecs,
                    $lRecsPerPage, $lStartRec) {
//-------------------------------------------------------------------------
// Create and display the record set navigation buttons
//
//  Input:
//    $strLinkBase  - the basic response link. This routine adds pertinant
//           information to the end the string
//    $strImgBase   - all the navigation buttons are in the same directory.
//           This routine adds the specific file name for the 12 images
//    $lStartRec    - current starting record being displayed
//    $lRecsPerPage - current number of records displayed per page
//    $lTotRecs     - total number of records in the recordset
//-------------------------------------------------------------------------
//   require_once('../dbAustinLib/util_rsNavigate.php');

      // determine the starting record of the very last page
   $lStartRecLastPage = rs_navigate_lStartRecLastPage($lTotRecs, $lRecsPerPage);

      // determine the starting record for the previous and next pages
   $lNextPageRec = $lStartRec + $lRecsPerPage;
   $lPrevPageRec = $lStartRec - $lRecsPerPage;

      //-------------------------------------------------------------------
      // initialize the navigation utilities by supplying the path to the
      // images that represent the navigation buttons
      //-------------------------------------------------------------------
   rs_navigate_preloadRecordSelectorImages(
               $strImgBase.'first.gif', $strImgBase.'first_mo.gif', $strImgBase.'first_md.gif',
               $strImgBase.'last.gif',  $strImgBase.'last_mo.gif',  $strImgBase.'last_md.gif',
               $strImgBase.'next.gif',  $strImgBase.'next_mo.gif',  $strImgBase.'next_md.gif',
               $strImgBase.'prev.gif',  $strImgBase.'prev_mo.gif',  $strImgBase.'prev_md.gif');

      //-------------------------
      // create the links
      //-------------------------
      // if we're at the first page, we don't want links
      // to "first" and "previous".
      //-------------------------
   if ($lStartRec<=1) {
      $strFirstLink     = NULL;
      $strPrevLink      = NULL;
      $strFirstImageSrc = NULL;
      $strPrevImageSrc  = NULL;
   }else {
      $strFirstLink     = $strLinkBase.'1';
      $strPrevLink      = $strLinkBase.$lPrevPageRec;
      $strFirstImageSrc = $strImgBase.'first.gif';
      $strPrevImageSrc  = $strImgBase.'prev.gif';
   }

      //-------------------------
      // if we're at the last page, we don't want links
      // to "last" and "next".
      //-------------------------
   if ($lStartRec>=$lStartRecLastPage) {
      $strNextLink     = NULL;
      $strLastLink     = NULL;
      $strNextImageSrc = NULL;
      $strLastImageSrc = NULL;
   }else {
      $strNextLink     = $strLinkBase.$lNextPageRec;
      $strLastLink     = $strLinkBase.$lStartRecLastPage;
      $strNextImageSrc = $strImgBase.'next.gif';
      $strLastImageSrc = $strImgBase.'last.gif';
   }

   echo(
      '<table>'
         .'<tr>'
            .'<td>'
       );

      // display the navigation buttons
   rs_navigate_insertNavigationTable(
                 31,     19,
                 '#E5E5E5', '#CCCCCC',
                 $strFirstLink,        $strFirstImageSrc,
                 $strPrevLink,         $strPrevImageSrc,
                 $strNextLink,         $strNextImageSrc,
                 $strLastLink,         $strLastImageSrc
                 );

      // populate the array that will fill the "records per page"
      // drop down list
   $lRecsPP[0] = 10;
   $lRecsPP[1] = 25;
   $lRecsPP[2] = 50;
   $lRecsPP[3] = 100;
   $lRecsPP[4] = 500;

   ?>
            </td>
            <td width="10">&nbsp;</td>
            <td>
   <?php

      //---------------------------------------------------------------
      // call the routine to populate and display the drop-down list
      // for records per page. We also supply a link to respond to
      // the user changing the records per page.
      //---------------------------------------------------------------
   rs_navigate_recsPerPageDDL($lRecsPerPage, $lRecsPP,
            $strLinkBase.$lStartRec.'&lRecsPerPage=');

   ?>
            </td>
         </tr>
      </table>
   <?php
}


function strLoadRecSpecs(&$lRecsPerPage, &$lStartRec) {
//-------------------------------------------------------------------------
// see if the user changes the records per page, return the appropriate
// sql limit clause
//-------------------------------------------------------------------------
   $lRecsPerPage = @$_REQUEST['lRecsPerPage'];
   if (is_numeric($lRecsPerPage)) {
      $_SESSION['enp_lRecsPerPage'] = (integer)$lRecsPerPage;
   }
   $lRecsPerPage = $_SESSION['enp_lRecsPerPage'];

      //----------------------------
      // starting record number
      //----------------------------
   $lStartRec = @$_REQUEST['lStartRec'];
   if (!is_numeric($lStartRec)){
      $lStartRec = 1;
   }else {
      $lStartRec = (integer)$lStartRec;
      if ($lStartRec<=0) $lStartRec = 1;
   }
   return('LIMIT '.($lStartRec-1).', '.$lRecsPerPage.' ');
}

?>