<?php
/**
 * Backup functionality for MySQL tables
 */

class Backup
{

    const BACKUP_LOG = "/../secure-store/backups/backup.log";
    const BACKUP_PATH = "/../secure-store/backups";

    /**
     * The constructor
     *
     * @var mixed - the table name or an array of table names
     *            - on restore:
     *                         tables['name] - the table name to restore to
     *                         tables['infileName'] - the file to restore from (from the backup directory)
     * @throws Exception
     */
    function __construct($tables)
    {

        $this->backupPath = $_SERVER['DOCUMENT_ROOT'] . Backup::BACKUP_PATH;

        if (!isset($tables)) {
            throw new Exception("Tables are not properly defined : " . $tables);
        }

        $this->tables = $tables;
    }

    /**
     * Backup one or more tables set in $this->tables
     */
    public function doBackup()
    {
        try {
            $this->backupTables($this->tables);
        } catch (Exception $e) {
            $this->logResult("Failure : " . $e->getMessage());
        }
    }

    /**
     * Restore one or more tables set in $this->tables
     */
    public function doRestore()
    {
        try {
            $this->restoreTables($this->tables);
        } catch (Exception $e) {
            $this->logResult("Failure : " . $e->getMessage());
        }
    }

    /**
     * Backup one or more tables
     *
     * @param mixed $tables - a single string tablename or an array of table names
     * @throws Exception
     */
    protected function backupTables($tables)
    {
        $tables = is_array($tables) ? $tables : explode(',', $tables);

        foreach ($tables as $table) {
            $filename = $this->backupPath . "/" . $table . "-" . date('m_d_Y-') . time() . ".sql";
            $command = sprintf(
                "mysqldump --host=localhost --user=ors --password=rilinc research %s --single-transaction > %s",
                $table, $filename);
            passthru($command);

            // log a successful backup, but hide the mysql user details.
            $command = str_replace('user=ors', 'user=*****', $command);
            $command = str_replace('password=rilinc', 'password=******', $command);
            $this->logResult("Backed up table using : " . $command);
        }
    }

    /**
     * Restore to one or more tables
     * @param $tables
     */
    protected function restoreTables($tables)
    {
        // currently no restore functionality
    }

    /**
     * Log a result to the backup log
     *
     * @param $message - the message to log
     * @throws Exception
     */
    protected function logResult($message)
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . Backup::BACKUP_LOG;

        try {
            $log = fopen($logFile, 'a');
        } catch (Exception $e) {
            throw new Exception("Unable to open backup log file for writing : " . $logFile);
        }

        $dateString = date('m-d-Y-H:m:s');
        fwrite($log, $dateString . " - " . $message . PHP_EOL);
        fclose($log);
    }

    /**
     * Return a list of backup files in the backup directory
     *
     * @static
     *
     * @return array - the backup files
     */
    static function getBackupFiles()
    {
        $backupFiles = scandir($_SERVER['DOCUMENT_ROOT'] . Backup::BACKUP_PATH);
        $backupFiles = array_diff($backupFiles, array('..', '.'));
        $backupFiles = array_diff($backupFiles, array('backup.log')); // exclude the log file

        $files = array();
        $i = 0;
        foreach ($backupFiles as $file) {
            $files[$i]['filename'] = $file;

            $splitFile = explode('.', $file); // remove file extension
            $splitFile = explode('-', $splitFile[0]); // parse
            $files[$i]['table'] = $splitFile[0];
            $files[$i]['date'] = date('F d, Y H:i:s', $splitFile[2]);
            $i++;
        }

        return $files;
    }

    /**
     * Delete a backup file
     *
     * @static
     * @param $filename - the filename to delete
     */
    public static function deleteBackup($filename)
    {
        $file = ($_SERVER['DOCUMENT_ROOT'] . Backup::BACKUP_PATH . "/" . $filename);
        if (!unlink($file)) {
            echo "Unable to delete file :" . $filename;
        }
    }

    /**
     * Return a list of table names from the research database
     *
     * @static
     * @return mixed - table names
     */
    public static function getTableNames()
    {
        $sql = "SHOW tables from research";
        global $db;
        $tableNames = $db->getAll($sql);

        return $tableNames;
    }

    /**
     * @var string - the path to the backup file location
     */
    protected $backupPath;

    /**
     * @var mixed - the table name or an array of table names
     *            - on restore:
     *                         tables['name] - the table name to restore to
     *                         tables['infileName'] - the file to restore from (from the backup directory)
     *
     */
    protected $tables;


}

