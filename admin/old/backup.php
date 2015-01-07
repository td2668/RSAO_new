<?php

include("includes/config.inc.php");

$tmpl=loadPage("backups", 'Database Backups');

include "includes/backup.php";

$action = $_REQUEST['action'];

switch($action) {
    case "list" :
        showBackups();
        break;
    case "tables" :
        $tables = Backup::getTableNames();
        $tmpl->addRows('tablenames',$tables);
        $tmpl->setAttribute('tables','visibility','visible');
        break;
    case "backup" :
        $backup = new Backup($_REQUEST['tablename']);
        $backup->doBackup();
        showBackups();
        break;
    case "restore" :
/*        $backup = new Backup(array(
            array(
                'name' => $_REQUEST['table'],
                'infileName' => $_REQUEST['filename']
            )));
        $backup->doRestore();*/
        showBackups();
        break;
    case "delete" :
        $filename = $_REQUEST['filename'];
        Backup::deleteBackup($filename);
        showBackups();
        break;
    default :
         // do a list of backups files if no action is defined
        showBackups();
        break;
}

function showBackups() {
    $backupFiles = Backup::getBackupFiles();
    global $tmpl;
    $tmpl->addRows('mainlist',$backupFiles);
    $freeSpace = round(disk_free_space(".") / 1073741824, 2); // disk space in GB
    $tmpl->addVar('restore', 'freeSpace', $freeSpace);
    $tmpl->setAttribute('restore','visibility','visible');
}

$tmpl->displayParsedTemplate('page');
