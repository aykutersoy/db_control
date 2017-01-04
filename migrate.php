<?php

if (!defined('ROOT')){
    define('ROOT', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT . "app/config/config.php";
require_once ROOT . "app/models/database.php";
require_once ROOT . "app/models/log_manager.php";
require_once ROOT . "app/models/redis_manager.php";

$mig = new Migrate('Default');
$mig->index();

class Migrate {
    
    protected $_connection;
    protected $dbInstance;
    protected $dbName;
    protected $type;
    protected $fileName;
    protected $fullPath;
    protected $fullPathBackup;
    protected $gitCommit;
    protected $dbCommit;

    public function __construct($db) {

        $this->dbInstance = Database::getInstance($db);
        $this->_connection = $this->dbInstance->getConnection();

    }
    
    public function index() {
        
        $this->getLatestCommitFromDB();
        $this->getLatestCommitFromGit();
        
        if ($this->dbCommit == $this->gitCommit) {
            echo "\n**********************************************************\n\n"
                . "This commit \n\n"
                . "$this->gitCommit\n\n"
                . "has already been migrated.\n"
                . "Aborting...\n\n"
                . "**********************************************************\n";
            return false;
        }
        
        foreach ($this->findChanges() as $singleChange) {
            
            $detailOfChanges = explode(DIRECTORY_SEPARATOR, $singleChange);

            // this will ignore all the changes except files with extension of "sql"
            if (isset($detailOfChanges[3]) && pathinfo($detailOfChanges[3], PATHINFO_EXTENSION) == 'sql') {
                $this->fullPath = ROOT . $singleChange;
                $this->dbName   = $detailOfChanges[1]; // this should be the db name (DB_NAME)
                $this->type     = $detailOfChanges[2]; // this should be one of the followings Data, Tables, Functions, Procedures, Views or Events
                $this->fileName = $detailOfChanges[3]; // this should be the file name

                //select schema
                $this->dbInstance->query("USE `$this->dbName`;");

                switch ($this->type) {
                    case 'Tables':
                        break;
                    case 'Views':
                        break;
                    case 'Triggers':
                        break;
                    case 'Procedures':
                        $rs = $this->procedures();
                        break;
                    case 'Functions':
                        break;
                    case 'Events':
                        break;

                    default:
                        break;
                }
                $this->log(isset($rs['StatusCode']) ? $rs['StatusCode'] : '000', isset($rs['StatusDesc']) ? $rs['StatusDesc'] : 'Success');
            }
        }
        $this->setLatestCommitToDB();
        
    }
    
    private function findChanges(){
        
        $compare = ($this->dbCommit!= NULL ? $this->dbCommit : 'HEAD@{1}');
        //$gitCommand = 'git diff --name-only ' . $compare . ' HEAD'; //git diff-tree -r --name-only --no-commit-id ORIG_HEAD HEAD
        $gitCommand = 'git diff --name-only ' . $compare . ' ' . $this->gitCommit;

        return explode(PHP_EOL, trim(shell_exec($gitCommand)));
        
    }

    private function procedures() {

        $SPname = explode(".sql", $this->fileName);
        
        // drop if exists
        if (isset($SPname[0]) && !empty($this->query("SHOW PROCEDURE STATUS WHERE DB = '$this->dbName' and `Name` = '$SPname[0]';"))) {

            // backup the file before drop it
            if ($this->backup($SPname[0]) !== FALSE) {

                //drop it
                $this->dbInstance->query("DROP PROCEDURE IF EXISTS `$SPname[0]`;");
            }
        }
        
        //execute the modified file
        $rs = $this->dbInstance->query($this->fromFile($this->fullPath));
        
        // execute from backed up file if it errors above
        if (!empty($rs) && isset($rs['StatusCode'])) {
            
            $this->dbInstance->query($this->fromFile($this->fullPathBackup));
        }
            
        return $rs;
        
    }
    
    private function toFileBackup($content) {
        
        $path = ROOT . "_BACKUP" . DIRECTORY_SEPARATOR . $this->dbName;
        if (!file_exists($path)) {
            mkdir($path);
        }
        
        $fileFullPath = $path . DIRECTORY_SEPARATOR . $this->fileName;
        $this->fullPathBackup = $fileFullPath;
        if (!file_put_contents($fileFullPath, $content)) {
            return TRUE;
        }
    }
    private function fromFile($filePath) {
        
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
    }
    
    private function backup($name) {
        
        return $this->toFileBackup($this->dbInstance->query("SHOW CREATE PROCEDURE `$name`;")[0][2]);
        
    }
    
    private function log($statusCode, $statusDesc){
        
        $sql = "INSERT INTO `db_control`.`logs` (`migrationCommit`, `filePath`, `statusCode`, `statusDesc`)"
                . " VALUE (:commit, :fullPath, :statusCode, :statusDesc);";

        try {
            // prepare the statement
            $stmt = $this->_connection->prepare($sql);
            // bind parameters
            $stmt->bindParam(':commit', $this->gitCommit);
            $stmt->bindParam(':fullPath', $this->fullPath);
            $stmt->bindParam(':statusCode', $statusCode);
            $stmt->bindParam(':statusDesc', $statusDesc);
            // execute the statement
            $stmt->execute();
            
        } catch (PDOException $e) {
            echo "\nError Code:: " . $e->getCode() . "\nError Message:: " . $e->getMessage();
        }
    }

    private function setLatestCommitToDB(){
        
        $sql = "INSERT INTO `db_control`.`migration` (`migratedCommit`)"
                . " VALUE (:commit);";

        try {
            // prepare the statement
            $stmt = $this->_connection->prepare($sql);
            // bind parameters
            $stmt->bindParam(':commit', $this->gitCommit);
            // execute the statement
            $stmt->execute();
            
        } catch (PDOException $e) {
            echo "\nError Code:: " . $e->getCode() . "\nError Message:: " . $e->getMessage();
        }
    }

    private function getLatestCommitFromDB() {
        
        $rs = $this->dbInstance->query("SELECT migratedCommit FROM `db_control`.`migration` ORDER BY `datetime` DESC LIMIT 1;");
        $this->dbCommit = (!empty($rs) && !isset($rs['StatusCode']) ? $rs[0][0] : NULL);
        
    }
    
    private function getLatestCommitFromGit() {
        
        $this->gitCommit = trim(shell_exec('git log -1 --pretty=format:"%H"'));
    }

    private function query($sql)
    {
        try {
            return $this->_connection->query($sql);
        } catch (PDOException $e) {
            echo "\nError Code:: " . $e->getCode() . "\nError Message:: " . $e->getMessage();
        }
    }
}
