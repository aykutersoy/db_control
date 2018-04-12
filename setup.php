<?php

if (!defined('ROOT')){
    define('ROOT', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT . "app/config/config.php";
require_once ROOT . "app/models/database.php";

define('SCHEMA', 'db_control');

$set = new Setup('Default');
$set->index();

class Setup {

    protected $_connection;
    protected $dbInstance;
    protected $gitCommit;

    public function __construct($db) {

        $this->dbInstance = Database::getInstance($db);
        $this->_connection = $this->dbInstance->getConnection();

    }
    public function index() {
        
        $this->copyTemplateFiles();
        
        $createSchema = $this->createSchema();
        if ($createSchema === TRUE || strpos($createSchema, "exists") != FALSE) {
            
            echo "Schema created or already existed.\n";
            
            $this->dbInstance->query("USE `" . SCHEMA . "`;");
            
            $createLogTable = $this->createLogTable();
            $createMigrationTable = $this->createMigrationTable();
            
            if (($createLogTable === TRUE || strpos($createLogTable, "exists") != FALSE) &&
               (($createMigrationTable === TRUE || strpos($createMigrationTable, "exists") != FALSE))) {
                echo "Tables created or already existed.\n";
            }
        } else {
            echo "Couldn't create schema '" . SCHEMA . "' \nAborting...\n\n";
            echo "Error Message::" . $createSchema;
            return false;
        }
        
        $this->getLatestCommitFromGit();
        if (strpos($this->setLatestCommitToDB(), "Duplicate") != FALSE) {
            echo "It seems Setup has already been run before\nAborting...";
            return false;
        } else {
            echo "Setup has completed!!! \n";
        }

    }
    
    private function copyTemplateFiles() {
        
        $files = array(
            ROOT . "ignoredDBs",
            ROOT . "app/credentials/credentials");
        
        foreach ($files as $file) {
            
            if (!file_exists($file)) {
                copy($file . ".template", $file);
                echo "'$file.template' file has been copied as '$file'" . PHP_EOL;
            }
        }
    }
    
    private function createSchema() {
        
        return $this->prepareAndExecute("CREATE SCHEMA ". SCHEMA . ";");
    }
    private function createLogTable() {
        
        $fullPath = ROOT . STRUCTURE_DIR . '/' . SCHEMA . '/Tables/logs.sql';
        return $this->prepareAndExecute($this->fromFile($fullPath));
    }
    private function createMigrationTable() {
        
        $fullPath = ROOT . STRUCTURE_DIR . '/' . SCHEMA . '/Tables/migration.sql';
        return $this->prepareAndExecute($this->fromFile($fullPath));
    }
    private function prepareAndExecute($sql) {

        try {
            // prepare the statement
            $stmt = $this->_connection->prepare($sql);
            // execute the statement
            return $stmt->execute();
            
        } catch (PDOException $e) {
            //echo "\nError Code:: " . $e->getCode() . "\nError Message:: " . $e->getMessage() . PHP_EOL;
            return $e->getMessage();
        }

    }
    private function getLatestCommitFromGit() {
        
        $this->gitCommit = trim(shell_exec('git log -1 --pretty=format:"%H"'));
    }

    private function setLatestCommitToDB() {
        
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
            //echo "\nError Code:: " . $e->getCode() . "\nError Message:: " . $e->getMessage();
            return $e->getMessage();
        }
    }
    
    private function fromFile($filePath) {
        
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
    }

}