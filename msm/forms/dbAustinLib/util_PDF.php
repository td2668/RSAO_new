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
// general pdf utilities
//
//  sngMM2pt       - convert millimeters to points
//  lCenterString  - return the x position to center a string
//  lGetBoxHeight  - return the height of a text box
//  strSetFont     - set the font based on the list of fonts available on
//                   the host machine
//  xlateColor     - convert html-type color values (0..255) to pdf-style
//                   color values
//  trimPDF        - shrink a string until it is less that specified width
//---------------------------------------------------------------------
// screamForHelp('<br>error on line '.__LINE__.',<br>file '.__FILE__.',<br>function '.__FUNCTION__);

function loadDefaultPDF_Values($dbConn, $lUserID, &$lTopMargin, &$lLeftMargin) {
//---------------------------------------------------------------
//  for the given user account, load printer margin offset values
//  (used to calibrate individual printers)
//---------------------------------------------------------------
   $sqlStr =
        'SELECT pdf_lTopMarginPnts, pdf_lLeftMarginPnts '
       .'FROM tbl_pdf_calibrate '
       ."WHERE pdf_lAcctID=$lUserID;";

   $result = mysql_query($sqlStr);
   if (!bSQLError('myLittleRoutine', $sqlStr) ) {
      $numRows = mysql_num_rows($result);
      if ($numRows==0) {
         $lTopMargin  = 0;
         $lLeftMargin = 0;
      }else {
         $row = mysql_fetch_array($result);
         $lTopMargin  = $row['pdf_lTopMarginPnts'];
         $lLeftMargin = $row['pdf_lLeftMarginPnts'];
      }
   }
}

function sngMM2pt($lMM) {
//---------------------------------------------------------------------
// convert millimeters to points
//---------------------------------------------------------------------
   return( ($lMM/25.4)*72);
}


function lCenterString($PDFobj, $strText, $lLeftMargin, $lRightMargin, $lWidth) {
//---------------------------------------------------------------------
// return the x position to center a string. All values are in points.
//
//    $lLeftMargin - length, in points, of the left margin
//    $lRightMargin - length, in points, of the right margin
//    $lWidth - total width of print area in points
//
// Output
//    function - x position to place screen
//
// examples
//    to center on a portrait sheet, with left margin of 50pt and
//    right margin of 72 pt:
//         PDF_set_text_pos($PDF_ps,
//                lCenterString($PDF_ps, "Hello World", 50, 72, 8.5*72), $lTopPosition);
//
//    to center a field on a label, with the label start at 200 pts
//    from the left edge of the paper, and a label width of 125
//         PDF_set_text_pos($PDF_ps,
//                lCenterString($PDF_ps, "Hello World", 200, 0, 200+125), $lTopPosition);
//---------------------------------------------------------------------
   $lWidthAvail = $lWidth-($lLeftMargin+$lRightMargin);

   return($lLeftMargin + (($lWidthAvail- pdf_stringwidth($PDFobj, $strText))/2) );
}

function strTrimPDF($PDFobj, $strText, $lWidth){
//---------------------------------------------------------------------
//  return the trimmed string
//  lWidth is in points
//---------------------------------------------------------------------
   $strTemp = $strText;

   if ($lWidth<=0) {
      return('');
   }else {
      while (pdf_stringwidth($PDFobj, $strTemp)>$lWidth) {
         $strTemp = substr($strTemp, 0, strlen($strTemp)-1);
      }
   }
   return($strTemp);
}

//function strSetFont($strFontName, $bBold, $bItalic) {
////-------------------------------------------------------------------------
//// for a given base font and given bold/italic specs, build full font name
////
//// note: Univers not installed on bigdaddy as of 9/5/03
////-------------------------------------------------------------------------
//   $strFont = $strFontName;
//
//   switch ($strFontName) {
//      case 'Univers':
//         $strFont .= ' Bold';
//         if ($bItalic) {$strFont .= ' Italic';}
//         break;
//
//      case 'Tahoma':
//         if ($bBold)   {$strFont .= ' Bold';}
//         break;
//
//      default:
//         if ($bBold)   {$strFont .= ' Bold';}
//         if ($bItalic) {$strFont .= ' Italic';}
//         break;
//   }
//
////echo("strFont=$strFont<br><br>");
//   return($strFont);
//}
//
//

function strSetCoreFontName($strFontName, $bBold, $bItalic) {
//-------------------------------------------------------------------------
// for a given core font and bold/italic specs, build full font name
//-------------------------------------------------------------------------
   $strFont = $strFontName;

      //----------------------------------------------
      // if not bold or italic, return core font name
      //----------------------------------------------
   if ($bBold || $bItalic) {
      switch ($strFontName) {
         case 'Courier':
            if ($bBold && $bItalic) {
               $strFont = 'Courier-BoldOblique';
            }elseif ($bBold) {
               $strFont = 'Courier-Bold';
            }else {
               $strFont = 'Courier-Oblique';
            }
            break;

         case 'Helvetica':
            if ($bBold && $bItalic) {
               $strFont = 'Helvetica-BoldOblique';
            }elseif ($bBold) {
               $strFont = 'Helvetica-Bold';
            }else {
               $strFont = 'Helvetica-Oblique';
            }
            break;

         case 'Times-Roman':
            if ($bBold && $bItalic) {
               $strFont = 'Times-BoldItalic';
            }elseif ($bBold) {
               $strFont = 'Times-Bold';
            }else {
               $strFont = 'Times-Italic';
            }
            break;

         default:
            if ($bBold)   {$strFont .= ' Bold';}
            if ($bItalic) {$strFont .= ' Italic';}
            break;
      }
   }

//echo("strFont=$strFont<br><br>");
   return($strFont);
}

function xlateColor($strFontColor, &$l_Red, &$l_Green, &$l_Blue) {
//-------------------------------------------------------------------------
// sample calling sequence when used with PDF file generation:
//
//       xlateColor('navy', $l_Red, $l_Green, $l_Blue);
//       PDF_setcolor(PDF_obj,
//                 'both', 'rgb', $l_Red/0xff, $l_Green/0xff, $l_Blue/0xff, 0);
//
//-------------------------------------------------------------------------
   switch (strtolower($strFontColor)) {
      case 'black':
         $l_Red = 0x00; $l_Green = 0x00; $l_Blue = 0x00;
         break;

      case 'red':
         $l_Red = 0xff; $l_Green = 0x00; $l_Blue = 0x00;
         break;

      case 'purple':
         $l_Red = 0x99; $l_Green = 0x33; $l_Blue = 0x99;
         break;

      case 'blue':
         $l_Red = 0x00; $l_Green = 0x00; $l_Blue = 0xff;
         break;

      case 'brown':
         $l_Red = 0x99; $l_Green = 0x66; $l_Blue = 0x33;
         break;

      case 'navy':
         $l_Red = 0x00; $l_Green = 0x33; $l_Blue = 0x99;
         break;

      case 'orange':
         $l_Red = 0xF0; $l_Green = 0x7D; $l_Blue = 0x56;
         break;

      default:
         $l_Red = 0x00; $l_Green = 0x00; $l_Blue = 0x00;
         break;
   }
}

function lGetBoxHeight($objPDF, $strTest, $lWidthPnts, $lGuessHeightPnts, $strMode, $lFontSize){
//---------------------------------------------------------------------
// algorithm by John Zimmerman (c) 2003
//
// The caller can provide his/her own estimate of box size, or can
// leave the value 0 and let this routine determine an estimate
// based on font size, string width, etc.
//
// $strMode is passed to PDF_show_boxed, and can be
//   'left', 'right', 'center', 'justify', or 'fulljustify'
//
// potential enhancements: use a binary search for generating step size;
// factor in a count of the new-line characters in the source string
// to get a better initial estimate.
//
// Return the box height for a given sample string
//
// 3/10/2004 jpz added test for blank string
//---------------------------------------------------------------------
   if (strlen($strTest)==0) return(0);
   if ($lGuessHeightPnts<=0) {
      $lNewGuess = (integer)(strlen($strTest)/($lWidthPnts/$lFontSize));
      if ($lNewGuess==0) $lNewGuess=1;
//echo("\$lNewGuess=$lNewGuess<br>");
   }else {
      $lNewGuess = (integer)$lGuessHeightPnts;
   }

      //--------------------------------------------------------------------------------
      // by working the step size, we greatly reduce the number of iterations required
      // to assertain the correct box size
      //--------------------------------------------------------------------------------
   $lNewGuess = lGetBoxHeightWithStep($objPDF, $strTest, $lWidthPnts, $lNewGuess, $strMode, $lFontSize*5);
   $lNewGuess = lGetBoxHeightWithStep($objPDF, $strTest, $lWidthPnts, $lNewGuess, $strMode, $lFontSize);
   $lNewGuess = lGetBoxHeightWithStep($objPDF, $strTest, $lWidthPnts, $lNewGuess, $strMode, 1);

   return($lNewGuess+1);
}

function lGetBoxHeightWithStep($objPDF, $strTest, $lWidthPnts, $lNewGuess, $strMode,
                         $lStepSize) {
//---------------------------------------------------------------------
//  use the "blind" feature of the text box rendering tool to
//  determine the text fit. Given our estimate ($lNewGuess), see
//  if we need to increase or decrease the box size. The box size
//  is altered by $lStepSize.
//
//---------------------------------------------------------------------

//echo("\$lNewGuess=$lNewGuess<br>");
   $lNumNoFit = PDF_show_boxed($objPDF, $strTest,
                              0, 0, $lWidthPnts, $lNewGuess,
                              $strMode, 'blind');

   $bGuessHeigher = ($lNumNoFit>0);

   $lCnt = 0;
   if ($bGuessHeigher) {
      do {
         ++$lCnt; if($lCnt>5000){echo('hosed! - util_Global/util_PDF.php/lGetBoxHeightWithStep (1)<br>');exit;}
         $lNewGuess += $lStepSize;
         $lNumNoFit = PDF_show_boxed($objPDF, $strTest,
                              0, 0, $lWidthPnts, $lNewGuess,
                              $strMode, 'blind');
      } while ($lNumNoFit>0);
      return($lNewGuess);
   }else {
      do {
         ++$lCnt; if($lCnt>5000){echo('hosed! -  util_Global/util_PDF.php/lGetBoxHeightWithStep (2)<br>');exit;}
         $lNewGuess -= $lStepSize;
         $lNumNoFit = PDF_show_boxed($objPDF, $strTest,
                              0, 0, $lWidthPnts, $lNewGuess,
                              $strMode, 'blind');
      } while ($lNumNoFit==0);
      return($lNewGuess+$lStepSize);
   }
}



?>