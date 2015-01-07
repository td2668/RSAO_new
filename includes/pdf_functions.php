<?php
//functions used in all the pdf documents.

/**
 * Draw HR
 *
 * @param TCPDF $pdf
 */
function doHR ( &$pdf ) {
    $y = $pdf->GetY();
    if($y<270){
        //$y = $pdf->GetY();
        $pdf->Line( 35, $y, 170, $y, array( width => 0.4, color => array( 0,102,153 ) ) );
       // AddParagraph($pdf, "Y:$y");
        $pdf->Ln( 3 );
    }
}

/**
 * draw thin HR
 *
 * @param TCPDF $pdf
 */
function thinHR ( &$pdf ) {
    $y = $pdf->GetY();
    if($y<270){
    
        $pdf->Line( 55, $y, 150, $y, array( width => 0.2, color => array( 51,204,255 ) ) );
        $pdf->Ln( 3 );
    }
}

/**
 * Set H1
 *
 * @param TCPDF $pdf
 */
function SetH1 ( &$pdf ) {
    $pdf->SetFont( MRUPDF_H1_FONT_FACE, 'B', MRUPDF_H1_FONT_SIZE );
}

/**
 * Set H2
 *
 * @param TCPDF $pdf
 */
function SetH2 ( &$pdf ) {
    $pdf->SetFont( MRUPDF_H2_FONT_FACE, 'B', MRUPDF_H2_FONT_SIZE );
}

/**
 * Set H3
 *
 * @param TCPDF $pdf
 */
function SetH3 ( &$pdf ) {
    $pdf->SetFont( MRUPDF_H3_FONT_FACE, 'B', MRUPDF_H3_FONT_SIZE );
}

/**
 * Set H4
 *
 * @param TCPDF $pdf
 */
function SetH4 ( &$pdf ) {
    $pdf->SetFont(MRUPDF_H4_FONT_FACE, 'B', MRUPDF_H4_FONT_SIZE );
}

/**
 * Set text back to normal
 *
 * @param TCPDF $pdf
 */
function SetNormal ( &$pdf ) {
    $pdf->SetFont( MRUPDF_REGULAR_FONT_FACE, '', MRUPDF_REGULAR_FONT_SIZE );
}

/**
 * Set text to smaller font
 *
 * @param TCPDF $pdf
 */
function SetSmaller ( &$pdf ) {
    $pdf->SetFont( MRUPDF_SMALLER_FONT_FACE, '', MRUPDF_SMALLER_FONT_SIZE );
}

/**
 * Set to bold text
 *
 * @param TCPDF $pdf
 */
function SetNormalBold ( &$pdf ) {
    $pdf->SetFont( MRUPDF_REGULAR_FONT_FACE, 'B', MRUPDF_REGULAR_FONT_SIZE );
}

/**
 * Draw a H1
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddH1 ( &$pdf, $text ) {
    $y=$pdf->getY();
    if($y>250) $pdf->AddPage();
    SetH1( $pdf );
    $pdf->Ln( 8 );
    $pdf->SetTextColor( 255 );
    $pdf->SetFillColor( 10,106,144 );
    $pdf->setX( 15 );
    $text=" $text";
    $pdf->Cell( 0, 7, $text, '', 1, 'L', 1 );
    $pdf->SetTextColor( 0 );
    $pdf->Ln( 4 );
    $pdf->Bookmark( ( ( $text ) ), 0, 0 );
}

/**
 * Draw a H2
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddH2 ( &$pdf, $text ) {
    //$pdf->Bookmark($text, 0, 0);
    $y=$pdf->getY();
    if($y>260) $pdf->AddPage();
    SetH2( $pdf );
    $pdf->setX( 20 );
    $pdf->Cell( 0, 7, $text, '', 1, 'L' );
}

/**
 * Draw a H3
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddH3 ( &$pdf, $text ) {
    //$pdf->Bookmark($text, 0, 0);
    SetH3( $pdf );
    $pdf->Cell( 0, 4, $text, 0, 1, 'L' );
}

/**
 * Add a HTML formatted paragraph
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddParagraph ( &$pdf, $text ) {
    SetNormal( $pdf );

    $text=normalize_special_characters($text);
    //$text = htmlentities( $text, ENT_COMPAT, cp1252 );
    $pdf->SetX(20);
    $pdf->writeHTMLCell(155,4,$pdf->GetX(),$pdf->GetY(),nl2br($text),0,1);
    //$pdf->WriteHTML( nl2br( $text ), true, 0, true, true );
    $pdf->Ln( 2 );
    //}
}

/**
 * Render a line of text
 *
 * @param TCPDF $pdf
 */
function AddLine ( &$pdf, $text ) {
    SetNormal( $pdf );
    $pdf->Cell( 0, 5, $text, 0, 1, 'L' );
}

/**
 * Add a plain text paragraph
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddParagraphPlain ( &$pdf, $text ) {
    SetNormal( $pdf );
    //$text=normalize_special_characters($text);
    //Claero DLG took out the blank suppression
    //$text=htmlspecialchars($text,ENT_COMPAT, cp1252, true);
    
    
    //First convert everything to HTML - to get extended char set across
    $text = htmlentities( $text, ENT_COMPAT, cp1252 );
    //But this also converts existing markup, so now change all &lt and &gt back so that italics will work.
    $text=htmlspecialchars_decode($text,ENT_NOQUOTES);
    $pdf->SetX(20);
    $pdf->SetCellPadding(0);
    $pdf->WriteHTMLCell(160, 5,$pdf->GetX(),$pdf->GetY(),$text,0,1,0,true,'L',false);
    //if (substr($text, -1) != ')'){
    //$pdf->SetX(10);
    //$pdf->Cell(80,4,($text),0,0,'L',0);
    
    //$pdf->WriteHTML( nl2br( $text ), true, 0, true, true );
    $pdf->Ln( 2 );
    //}
}

/**
 * Add a Summary style paragraph.  Has larger margins and the text is smaller
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddParagraphSummary ( &$pdf, $text ) {
    SetNormal( $pdf );
    //$text=normalize_special_characters($text);
    $text = htmlentities( $text, ENT_COMPAT, cp1252 );
    $text=htmlspecialchars_decode($text,ENT_NOQUOTES);
    //$pdf->SetLeftMargin( 30 );
    //$pdf->SetRightMargin( 30 );
    SetSmaller( $pdf );
    //$pdf->WriteHTML( nl2br( $text ), true, 0, true, true );
    $pdf->SetX(30);
    $pdf->SetCellPadding(0);
    $pdf->SetY($pdf->GetY()-2,false);
    $pdf->WriteHTMLCell(130, 5,$pdf->GetX(),$pdf->GetY(),nl2br(($text)),0,1,0,true,'L',false);
    SetNormal( $pdf );
    //$pdf->SetLeftMargin( 20 );
    //$pdf->SetRightMargin( PDF_MARGIN_RIGHT );
    $pdf->Ln( 2 );
}

function normalize_special_characters( $str )
{
    /*
    # Quotes cleanup
    $str = ereg_replace( chr(ord("`")), "'", $str );        # `
    $str = ereg_replace( chr(ord("´")), "'", $str );        # ´
    $str = ereg_replace( chr(ord("„")), ",", $str );        # „
    $str = ereg_replace( chr(ord("`")), "'", $str );        # `
    $str = ereg_replace( chr(ord("´")), "'", $str );        # ´
    $str = ereg_replace( chr(ord("“")), "\"", $str );        # “
    $str = ereg_replace( chr(ord("”")), "\"", $str );        # ”
    $str = ereg_replace( chr(ord("´")), "'", $str );        # ´

    # Bullets, dashes, and trademarks
    $str = ereg_replace( chr(149), "&#8226;", $str );    # bullet •
    $str = ereg_replace( chr(150), "&ndash;", $str );    # en dash
    $str = ereg_replace( chr(151), "&mdash;", $str );    # em dash
    $str = ereg_replace( chr(153), "&#8482;", $str );    # trademark
    $str = ereg_replace( chr(169), "&copy;", $str );    # copyright mark
    $str = ereg_replace( chr(174), "&reg;", $str );        # registration mark 
*/
    return $str;
} 


?>