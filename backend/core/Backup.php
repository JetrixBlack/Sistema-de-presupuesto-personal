<?php
namespace Core;

class Backup {
    private $backupDir;

    public function __construct() {
        $this->backupDir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    public function create() {
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $name = DB_NAME;
        
        $filename = $this->backupDir . '/backup_' . $name . '_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Command for Windows (XAMPP usually has mysqldump in the path or same bin as mysql)
        $dumpPath = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
        $command = "\"$dumpPath\" --user=$user " . ($pass ? "--password=$pass " : "") . "--host=$host $name > \"$filename\"";
        
        exec($command, $output, $returnVar);
        
        return ($returnVar === 0) ? $filename : false;
    }
}
