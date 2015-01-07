<?php
/*
	
	Copyright (c) Reece Pegues
	sitetheory.com

    Reece PHP Calendar is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or 
    any later version if you wish.

    You should have received a copy of the GNU General Public License
    along with this file; if not, write to the Free Software
    Foundation Inc, 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	
*/

// the sql server host - this is usually "localhost" if the database server is on the same machine
define("CAL_SQL_HOST","bckup-sigyn.mtroyal.ca");
// the sql database user that php will log in with
define("CAL_SQL_USER","cal");
// the sql database user's password
define("CAL_SQL_PASSWD","rilinc");
// this defines the connection is to a mysql database.  This is the only one supported at the moment.
define("CAL_SQL_TYPE","mysql");
// this is the name of the database the calendar tables are stored in.
define("CAL_SQL_DATABASE","calendar");
// this is the prefix of the database tables.  If you change the names of the tables, you need to change this.
define("CAL_SQL_PREFIX","cal_");
// this is the user password salt.  do not change this unless you know what you are doing!
define("CAL_SQL_PASSWD_SALT", "!saltismyfriend!");
// make this true to print any SQL error messages to screen.
define("CAL_SQL_DEBUG",FALSE);

// the root username for the system
define("CAL_ROOT_USERNAME","root");
// the default root user password (overwritten later from options table)
define("CAL_ROOT_PASSWORD","abc123");

// This adds extra path info to where it looks for the css, javascript, and language files.
// normally this is blank, but if you are loading the calendar from a file in a 
// different directory, you will probalby need to this for it to find the files it needs.
//define("CAL_INCLUDE_PATH","c:/cygwin/home/tdavis/htdocs/cal/");
define("CAL_INCLUDE_PATH","/var/www/research_htdocs/cal/");
define("CAL_INCLUDE_PATH_URL","/cal/");
// this defines the main php page we use for links and form posting
// in case you integrate this with a site, you might not use index.php for instance
define("CAL_URL_FILENAME","calendar.php");
// This defines if the calendar is stand alone, or integrated into a website
define("CAL_STAND_ALONE", FALSE);   // true or false
// only used if CAL_STAND_ALONE is true.  Sets the title tags in the html header to this.
define("CAL_STAND_ALONE_TITLE","Presentations Calendar");


?>
