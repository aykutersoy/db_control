<?php

if (!defined('ROOT')){
    define('ROOT', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT . "app/config/config.php";
require_once ROOT . "app/models/database.php";
require_once ROOT . "app/models/log_manager.php";
require_once ROOT . "app/models/redis_manager.php";


$init = new Initiate('Default');
$init->init();

class Initiate {
    
    protected $_connection;
    protected $dbInstance;
    protected $schemas;
    protected $procedures;

    public function __construct($db) {

        $this->dbInstance = Database::getInstance($db);
        $this->_connection = $this->dbInstance->getConnection();

    }
    public function init() {
        
        $this->getSchemas();

        if (!empty($this->schemas)) {
            foreach ($this->schemas as $schema) {
                $this->dbInstance->query("USE `$schema`;");

                $this->getTables($schema);
                $this->getViews($schema);
                $this->getTriggers($schema);
                $this->getProcedures($schema);
                $this->getFunctions($schema);
                $this->getEvents($schema);
            }
        }
    }

    private function getSchemas() {

        foreach ($this->dbInstance->query('SHOW DATABASES;') AS $schemaList) {

            // ignore MySQL databases;
            $schema = $schemaList[0];
            if ($this->ignoredDBS($schema)) {
                $this->schemas[] = $schema;
            }
        }
    }
    private function getTables($schemas) {

        foreach($this->dbInstance->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE';") AS $tables) {

            $tableName = $tables[0];
            $this->writeToFile($schemas, 'Tables', $tableName, preg_replace("/\s?AUTO_INCREMENT=\d+\s?/", " ", $this->dbInstance->query("SHOW CREATE TABLE `$tableName`;")[0][1]));
            
        }

    }
    private function getViews($schemas) {

        foreach($this->dbInstance->query("SHOW FULL TABLES WHERE Table_type='VIEW';") AS $views) {

            $viewName = $views[0];
            $this->writeToFile($schemas, 'Views', $viewName, $this->dbInstance->query("SHOW CREATE VIEW `$viewName`;")[0][1]);
            
        }

    }
    private function getTriggers($schemas) {

        foreach($this->dbInstance->query("SHOW TRIGGERS;") AS $triggers) {

            $triggerName = $triggers[0];
            $this->writeToFile($schemas, 'Triggers', $triggerName, $this->dbInstance->query("SHOW CREATE TRIGGER `$triggerName`;")[0][2]);

        }
    }
    private function getProcedures($schemas) {
        
        foreach($this->dbInstance->query("SHOW PROCEDURE STATUS WHERE DB = '$schemas';") AS $procedures) {

            $procedureName = $procedures[1];
            $this->writeToFile($schemas, 'Procedures', $procedureName, $this->dbInstance->query("SHOW CREATE PROCEDURE `$procedureName`;")[0][2]);

        }
    }
    private function getFunctions($schemas) {

        foreach($this->dbInstance->query("SHOW FUNCTION STATUS WHERE DB = '$schemas';") AS $functions) {

            $functionName = $functions[1];
            $this->writeToFile($schemas, 'Functions', $functionName, $this->dbInstance->query("SHOW CREATE FUNCTION `$functionName`;")[0][2]);

        }
    }
    private function getEvents($schemas) {

        foreach($this->dbInstance->query("SHOW EVENTS;") AS $events) {

            $eventName = $events[1];
            $this->writeToFile($schemas, 'Events', $eventName, $this->dbInstance->query("SHOW CREATE EVENT `$eventName`;")[0][3]);

        }
    }
    private function ignoredDBS($schemas) {
        
        if ($schemas != 'information_schema' && $schemas != 'mysql' && $schemas != 'performance_schema' && $schemas != 'sys') {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    private function dirCheckAndMake($dir) {
        
        $checkDir = ROOT . $dir;
        if (!file_exists($checkDir)) {
            return mkdir($checkDir, 0644, TRUE);
        }
    }
    private function writeToFile($schema, $type, $fileName, $content) {
        
        $path = STRUCTURE_DIR . DIRECTORY_SEPARATOR . $schema . DIRECTORY_SEPARATOR . $type;
        $this->dirCheckAndMake($path);
        $fileFullPath = ROOT . $path . DIRECTORY_SEPARATOR . $fileName . ".sql";
        if (!file_exists($fileFullPath)) {
            return file_put_contents($fileFullPath, $content);
        }
    }
}

