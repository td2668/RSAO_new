<?php
//============================================================+
// File name   : tcpdf_config.php
// Begin       : 2004-06-11
// Last Update : 2013-05-16
//
// Description : Configuration file for TCPDF.
// Author      : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2004-2013  Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with TCPDF.  If not, see <http://www.gnu.org/licenses/>.
//
// See LICENSE.TXT file for more information.
//============================================================+

require_once('sys_get_temp_dir.php'); //work around for PHP < 5.2 where this functiond doesn't exist

/**
 * Configuration file for TCPDF.
 * @author Nicola Asuni
 * @package com.tecnick.tcpdf
 * @version 4.9.005
 * @since 2004-10-27
 */

// If you define the constant K_TCPDF_EXTERNAL_CONFIG, the following settings will be ignored.

/**
 * Installation path (/var/www/tcpdf/).
 * By default it is automatically calculated but you can also set it as a fixed string to improve performances.
 */
//define ('K_PATH_MAIN', '');

/**
 * URL path to tcpdf installation folder (http://localhost/tcpdf/).
 * By default it is automatically set but you can also set it as a fixed string to improve performances.
 */
//define ('K_PATH_URL', '');

/**
 * Path for PDF fonts.
 * By default it is automatically set but you can also set it as a fixed string to improve performances.
 */
//define ('K_PATH_FONTS', K_PATH_MAIN.'fonts/');

/**
 * Default images directory.
 * By default it is automatically set but you can also set it as a fixed string to improve performances.
 */
//define ('K_PATH_IMAGES', '');

/**
 * Deafult image logo used be the default Header() method.
 * Please set here your own logo or an empty string to disable it.
 */
//define ('PDF_HEADER_LOGO', '');

/**
 * Header logo image width in user units.
 */
//define ('PDF_HEADER_LOGO_WIDTH', 0);
	// Automatic calculation for the following K_PATH_MAIN constant
	$k_path_main = str_replace( '\\', '/', realpath(substr(dirname(__FILE__), 0, 0-strlen('config'))));
	if (substr($k_path_main, -1) != '/') {
		$k_path_main .= '/';
	}

	/**
	 * Installation path (/var/www/tcpdf/).
	 * By default it is automatically calculated but you can also set it as a fixed string to improve performances.
	 */
	define ('K_PATH_MAIN', $k_path_main);
	/**
	 *images directory
	 */
	define ('K_PATH_IMAGES', K_PATH_MAIN.'images/');

/**
 * Cache directory for temporary files (full path).
 */
define ('K_PATH_CACHE', sys_get_temp_dir().'/');

/**
 * Generic name for a blank image.
 */
define ('K_BLANK_IMAGE', '_blank.png');

/**
 * Page format.
 */
define ('PDF_PAGE_FORMAT', 'A4');

/**
	 * page orientation (P=portrait, L=landscape)
	 */
	define ('PDF_PAGE_ORIENTATION', 'P');

	/**
	 * document creator
	 */
	define ('PDF_CREATOR', 'MRU TCPDF');

	/**
	 * document author
	 */
	define ('PDF_AUTHOR', 'MRU TCPDF');

	/**
	 * header title
	 */
	define ('PDF_HEADER_TITLE', '');

	/**
	 * header description string
	 */
	define ('PDF_HEADER_STRING', "");

	/**
	 * image logo
	 */
	define ('PDF_HEADER_LOGO', 'mount-royal-logo-227x80.png');
	/**
	 * header logo image width [mm]
	 */
	define ('PDF_HEADER_LOGO_WIDTH', 30);

	/**
	 *  document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
	 */
	define ('PDF_UNIT', 'mm');

	/**
	 * header margin
	 */
	define ('PDF_MARGIN_HEADER', 10);

	/**
	 * footer margin
	 */
	define ('PDF_MARGIN_FOOTER', 20);

	/**
	 * top margin
	 */
	define ('PDF_MARGIN_TOP', 35);

	/**
	 * bottom margin
	 */
	define ('PDF_MARGIN_BOTTOM', 25);

	/**
	 * left margin
	 */
	define ('PDF_MARGIN_LEFT', 15);

	/**
	 * right margin
	 */
	define ('PDF_MARGIN_RIGHT', 15);

	/**
	 * main font name
	 */
	define ('PDF_FONT_NAME_MAIN', 'coprg');

	/**
	 * main font size
	 */
	define ('PDF_FONT_SIZE_MAIN', 12);

	/**
	 * data font name
	 */
	define ('PDF_FONT_NAME_DATA', 'helvetica');

	/**
	 * data font size
	 */
	define ('PDF_FONT_SIZE_DATA', 8);

	/**
	 * Ratio used to scale the images
	 */
	define ('PDF_IMAGE_SCALE_RATIO', 4);

	/**
	 * magnification factor for titles
	 */
	define('HEAD_MAGNIFICATION', 1.1);

	/**
	 * height of cell respect font height
	 */
	define('K_CELL_HEIGHT_RATIO', 1.25); // 20090311 CSN was 1.25

	/**
	 * title magnification respect main font size
	 */
	define('K_TITLE_MAGNIFICATION', 1.3);

	/**
	 * reduction factor for small font
	 */
	define('K_SMALL_RATIO', 2/3);
	
	/**
 * Set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language.
 */
define('K_THAI_TOPCHARS', true);

/**
 * If true allows to call TCPDF methods using HTML syntax
 * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
 */
define('K_TCPDF_CALLS_IN_HTML', true);

/**
 * If true adn PHP version is greater than 5, then the Error() method throw new exception instead of terminating the execution.
 */
define('K_TCPDF_THROW_EXCEPTION_ERROR', false);


//============================================================+
// END OF FILE
//============================================================+
