<?php

class Database {

    private $_connection;
    private $dbHost;
    private $dbUserName;
    private $dbPassword;
    private static $_instance; //The single instance

    /*
      Get an instance of the Database
      @return Instance
     */

    public static function getInstance($db) {
        if (!self::$_instance) { // If no instance then make one
            self::$_instance = new self($db);
        }
        return self::$_instance;
    }

    // Constructor
    private function __construct($db) {
        $this->getDbConfig($db);
        try {
            $charset = "UTF8";
            $db != 'Default' ?
                $dsn = "mysql:host=$this->dbHost;dbname=$db;charset=$charset" :
                $dsn = "mysql:host=$this->dbHost;charset=$charset";
            $opt = [
                PDO::ATTR_DEFAULT_FETCH_MODE        => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT                => true,
                PDO::MYSQL_ATTR_INIT_COMMAND        => "SET NAMES utf8"
            ];
            $this->_connection = new PDO($dsn, $this->dbUserName, $this->dbPassword, $opt);
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $error = "Failed to connect to MySQL: " . $e->getMessage();
            echo $error;
            log_handler::static_logger($error);
        }
    }

    // Magic method clone is empty to prevent duplication of connection
    private function __clone() {
        
    }
    public function getDbConfig($db){

        // set these default values in case file doesn't exist or db config
        $this->dbHost = DB_HOST;
        $this->dbUserName = DB_USER;
        $this->dbPassword = DB_PASSWORD;
        $credentialsFile = CREDENTIALS_FILE;
        if (file_exists($credentialsFile)) {
            $credentials = json_decode(file_get_contents($credentialsFile));
            if (isset($credentials->$db)){
                $this->dbHost       = $credentials->$db->host;
                $this->dbUserName   = $credentials->$db->user;
                $this->dbPassword   = $credentials->$db->password;
            }
        }    
    }

    // Get PDO connection
    public function getConnection() {
        return $this->_connection;
    }
    
    public function query($sql) {
        
        try {
            $result = $this->_connection->query($sql);
            
            $rs = isset($result) && !empty($result->rowCount()) ? $result->fetchAll(PDO::FETCH_NUM) : array();

        } catch (PDOException $e) {
            echo "\nError Code:: " . $e->getCode() . "\nError Message:: " . $e->getMessage();
            $rs['StatusCode'] = $e->getCode();
            $rs['StatusDesc'] = $e->getMessage();
        }
        return $rs;
            
    }

    // this method will only EXECUTE STORED PROCEDURES
    public function runSQL($sp, $params = null) {
        $rowset = array();
        $qMarks = '';
        
        try {
            //BEGIN the TRANSACTION
            $this->_connection->beginTransaction();

            // find out how many question mark we need
            if (isset($params)) {
                foreach ($params as $param) {
                    $qMarks .= '?,';
                }
                $qMarks = substr($qMarks, 0, -1);
            }
            // Prepare the statement
            $stmt = $this->_connection->prepare("CALL $sp ($qMarks)");
            
            // bind the parameters here
            if (isset($params)) {
                $i = 1;
                foreach ($params as $param) {
                    $paramType = gettype($param);
                    switch ($paramType) {
                        case 'integer':
                            $pdoVarType = PDO::PARAM_INT;
                            break;
                        case 'string':
                        case 'double':
                            $pdoVarType = PDO::PARAM_STR;
                            break;
                        case 'boolean':
                            $pdoVarType = PDO::PARAM_BOOL;
                            break;
                        default:
                            $pdoVarType = PDO::PARAM_NULL;
                            break;
                    }
                    $stmt->bindValue($i++, $param, $pdoVarType);
                }
            }
            // execute the statement
            $stmt->execute();
            if (!$stmt) {
                log_handler::static_logger("***** DB ERROR *****  " . PHP_EOL .
                                            "STATEMENT :: [" . $stmt . "]" . PHP_EOL .
                                            "ERROR :: [" . print_r($this->_connection->errorInfo(),1) . "]");
                return $this->_connection->errorInfo();
            }
            // fetch all the rows
            $numRows = $stmt->rowCount();
            if ($numRows > 0){
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $rowset[] = $row;
                }
            }
            
            // COMMIT the TRANSACTION
            $this->_connection->commit();
        } catch (Exception $e) {
            // ROLLBACK the TRANSACTION in case if it fails
            $this->_connection->rollBack();
            log_handler::static_logger("***** DB ERROR *****  " . PHP_EOL .
                                        "REQUEST :: [" . $sp . "]" . PHP_EOL .
                                        "ERROR :: [" . print_r($e->getMessage(),1) . "]");
        }
        return $rowset;
    }
}
