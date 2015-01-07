<?php
/**
* This file is used to perform the end of year maintenance:
*
* The following tasks are performed:
* - uncheck all items for display in the report
* - move profile information 'goals for coming year' to 'previous year goals'
* - move scholarship 'goals for coming year' to 'previous goals'
* - move service 'goals for coming year' to 'previous goals'
*/
/***********************************
* INCLUDES
************************************/
require_once('../includes/global.inc.php');

/***********************************
* CONFIGURATION
************************************/
$logFile = (isset($configInfo["batch_log_file"])) ? $configInfo["batch_log_file"] : false;
$backupDir = (isset($configInfo["backup_root"])) ? $configInfos["backup_root"] : false;
$mailLog = '';
$status = true;
$continue = true;

/***********************************
* MAIN
************************************/

// make sure someone isn't just calling this page, it has to be called from the command line

// make sure this is the right time to do this

// make sure we have a log file and backupDir
if (!$logFile OR !$backupDir) {
    AddToLog("No batch log file or backup root defined in configiInfos.",$logFile,$mailLog);
    $continue = false;
} // if

// uncheck all cv_items items for display in the reports
if ($continue) {
    AddToLog("",$logFile,$mailLog);
    // backup cv_items table first
    if (BackupDatabase('cv_items',$backupDir . '/' . date('Ymd-') . 'cv_items',$logFile,$mailLog)) {
        $sql = "UPDATE cv_items SET report_flag = 0";
        if (0 && $db->Execute($sql)) {
            // worked
        } else {
            // failed
            AddToLog("Failed to reset report flag on cv_items: {$sql}",$logFile,$mailLog);
        } // if
    } else {
        // backup failed
        AddToLog('',$logFile,$mailLog);
        $status = false;
    } // if
} // if

// uncheck all course items for display in the reports
// backup courses and course_teaching table first

// move profile information 'goals for coming year' to 'previous year goals'

// move scholarship 'goals for coming year' to 'previous goals'

// move service 'goals for coming year' to 'previous goals'


// email log file to someone?
echo nl2br($mailLog);
exit;

/***********************************
* FUNCTIONS
************************************/

/**
* update log files with text
*
* @param mixed $text
* @param mixed $logFile
* @param mixed $mailLog
*/
function AddToLog($text, $logFile, &$mailLog) {
    $text = date('Y-m-d H:i:s') . ': ' . $text . "\n";
    error_log($text, 3, $logFile);
    $mailLog .= $text;
} // function

/**
* backup the specified database and tables
*
* @param mixed $backupTables
* @param mixed $backupFilePath
* @param mixed $logFile
* @param mixed $mailLog
*/
function BackupDatabase($backupTables, $backupFilePath, $logFile, &$mailLog) {

    global $SITE;
    $status = false;
    $stopwatch = new StopWatch(3);

    // dump the table to a file
    $commandLine = $GLOBALS['mysqldump_path'] . ' -l -h ' . $SITE->dbMysqlHost .
        ' -u ' . $SITE->dbMysqlUsername .
        ' -p' . $SITE->dbMysqlPassword . ' ' . $SITE->dbMysqlDatabase . ' ' . $backupTables . ' > ' . $backupFilePath;
    system($commandLine, $commandStatus); // this can take a long time... ($cmdStatus == 0 - success)
    if ($commandStatus == 0) {
        // worked
        $timeCheck = $stopwatch->now();
        AddToLog('Backed up tables in ' . $timeCheck . ' seconds', $logFile, $mailLog);
        AddToLog('Database: ' . $SITE->dbMysqlDatabase . ' Tables: ' . $backupTables, $logFile, $mailLog);
        //AddToLog('Command: ' . $commandLine, $logFile, $mailLog); // contains db password!!!!!!!!!!!!
        // compress the file
        $commandLine = $GLOBALS['gzip_path'] . ' -f ' . $backupFilePath; // -f forces the operation, even if the file exists
        system($commandLine, $commandStatus);
        if ($commandStatus == 0) {
            $compressTime = $stopwatch->now() - $timeCheck;
            AddToLog('Compressed the backup file in ' . $compressTime . ' seconds', $logFile, $mailLog);
            AddToLog('Command: ' . $commandLine, $logFile, $mailLog);
            $status = true;
            // now create the monthly snapshot (overwrite if already created)
            // we don't delete these files, so this way we always have one copy from each month
            $commandLine = '/bin/cp ' . $backupFilePath . '.gz ' . date('Ym') . '_manual_sales_data.sql.gz';
            system($commandLine, $commandStatus);
            if ($commandStatus == 0) {
                AddToLog('Created monthly snapshot copy. (' . $commandLine . ')', $logFile, $mailLog);
            } else {
                AddToLog('Failed to create monthly snapshot copy. (' . $commandLine . ')', $logFile, $mailLog);
            } // if
        } else {
            AddToLog('Failed to compress the backup file.', $logFile, $mailLog);
            AddToLog('Command: ' . $commandLine, $logFile, $mailLog);
        } // if
    } else {
        AddToLog('FAILED to backup tables in master database.', $logFile, $mailLog);
        AddToLog('Database: ' . $SITE->dbMysqlDatabase . ' Tables: ' . $backupTables, $logFile, $mailLog);
       // AddToLog('Command: ' . $commandLine, $logFile, $mailLog); // contains db password!!!!!!!!!!!!
    } // if

    return $status;

} // function BackupDatabase
?>