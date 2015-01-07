#--------------------------------------------------------
# Schema for mySQL Schema Manager
#
# http://sourceforge.net/projects/mysqlsm/
#
# copyright (c) 2005 by Database Austin
# Austin, Texas
#
# This software is provided under the GPL.
# Please see http://www.gnu.org/copyleft/gpl.html for details.
#--------------------------------------------------------
# phpMyAdmin SQL Dump
# version 2.5.7-pl1
# http://www.phpmyadmin.net
# Generation Time: Sep 06, 2005 at 07:03 PM
# 
# Database : `mysql_schema_mgr`
# 
# -------------------------------------------------------

#
# Table structure for table `tbl_db_list`
#

CREATE TABLE tbl_db_list (
  db_lKeyID int(11) NOT NULL auto_increment,
  db_strDBName varchar(64) NOT NULL default '',
  db_strUserName varchar(80) NOT NULL default '',
  db_strPWord varchar(80) NOT NULL default '',
  db_dteOrigin timestamp(14) NOT NULL,
  PRIMARY KEY  (db_lKeyID),
  KEY db_strDBName (db_strDBName)
) TYPE=MyISAM;


#
# Table structure for table `tbl_options`
#

CREATE TABLE tbl_options (
  op_lKeyID int(11) NOT NULL auto_increment,
  op_bAddDropTable tinyint(1) NOT NULL default '0',
  op_bUseBackQuotes tinyint(1) NOT NULL default '0',
  op_bIncludeCommentsInExport tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (op_lKeyID)
) TYPE=MyISAM;


#
# Table structure for table `tbl_snapshot_fields`
#

CREATE TABLE tbl_snapshot_fields (
  sf_lKeyID int(11) NOT NULL auto_increment,
  sf_lTableID int(11) NOT NULL default '0',
  sf_strFieldName varchar(64) NOT NULL default '',
  sf_strFieldType varchar(25) NOT NULL default '',
  sf_bNull tinyint(1) NOT NULL default '0',
  sf_keyType enum('none','primary','unique','multi') NOT NULL default 'none',
  sf_varDefault varchar(255) NOT NULL default '',
  sf_strExtra varchar(255) NOT NULL default '',
  sf_lTableIDX int(11) NOT NULL default '0',
  sf_strComment text NOT NULL,
  PRIMARY KEY  (sf_lKeyID),
  KEY sf_lTableID (sf_lTableID)
) TYPE=MyISAM;


#
# Table structure for table `tbl_snapshot_master`
#

CREATE TABLE tbl_snapshot_master (
  sm_lKeyID int(11) NOT NULL auto_increment,
  sm_lDB_ID int(11) NOT NULL default '0',
  sm_dteSnapDate datetime NOT NULL default '0000-00-00 00:00:00',
  sm_strNotes text NOT NULL,
  PRIMARY KEY  (sm_lKeyID),
  KEY sm_lDB_ID (sm_lDB_ID),
  KEY sm_dteSmapDate (sm_dteSnapDate)
) TYPE=MyISAM;


#
# Table structure for table `tbl_snapshot_table`
#

CREATE TABLE tbl_snapshot_table (
  st_lKeyID int(11) NOT NULL auto_increment,
  st_lSnapMasterID int(11) NOT NULL default '0',
  st_strTableName varchar(64) NOT NULL default '',
  st_strUserComment text NOT NULL,
  st_strCreateTableSQL text NOT NULL,
  st_dteLastUpdate timestamp(14) NOT NULL,
  PRIMARY KEY  (st_lKeyID),
  KEY st_lSnapMasterID (st_lSnapMasterID)
) TYPE=MyISAM;
    

