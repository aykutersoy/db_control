<?php

class log_handler {

    public static $r_conn;
    public static $times = [];

    public function __construct() {

        @set_error_handler(array($this, 'error_handler'));
        @set_exception_handler(array($this, 'exception_handler'));
        @register_shutdown_function(array($this, 'fatal_handler'));
    }

    public function custom_handler($str, $hash = null) {

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $file = $caller["file"];
        $line = $caller["line"];
        if (empty($hash)) {
            $hash = "";
        }

        $this->logger('Custom Log', $str, $file, $line, $hash);
    }

    public function logger($type, $str, $file, $line, $hash = null) {


        $log = array(
            "application" => APPLICATION,
            "type" => $type,
            "str" => $str,
            "file" => $file,
            "line" => $line,
            "hash" => $hash
        );
        redis_manager::list_push_static('logmanager', 'LIVELOAD.SLAVE.customlogwriter', $log);
    }

    public function error_handler($no, $str, $file, $line) {

        switch ($no) {
            case E_ERROR: $error = "Error";
                break;
            case E_WARNING: $error = "Warning";
                break;
            case E_PARSE: $error = "Parse Error";
                break;
            case E_NOTICE: $error = "Notice";
                break;
            case E_CORE_ERROR: $error = "Core Error";
                break;
            case E_CORE_WARNING: $error = "Core Warning";
                break;
            case E_COMPILE_ERROR: $error = "Compile Error";
                break;
            case E_COMPILE_WARNING: $error = "Compile Warning";
                break;
            case E_USER_ERROR: $error = "User Error";
                break;
            case E_USER_WARNING: $error = "User Warning";
                break;
            case E_USER_NOTICE: $error = "User Notice";
                break;
            case E_STRICT: $error = "Strict Notice";
                break;
            case E_RECOVERABLE_ERROR: $error = "Recoverable Error";
                break;
            default: $error = "Unknown error ($errno)";
                break;
        }
        $this->logger('PHP ' . $error, $str, $file, $line);
    }

    public function exception_handler($exception) {

        $file = $exception->getFile();
        $line = $exception->getLine();
        $str = str_replace(array("\r\n", "\n", "\r"), ' ', $exception->getMessage());

        if ($str != "Connection closed") {

            $this->logger('PHP Exception', $str, $file, $line);
        }
    }

    public function fatal_handler() {

        // log_handler::log_time(true);
        $fatal_error = error_get_last();

        if (isset($fatal_error)) {
            $str = $fatal_error["message"];
            $file = $fatal_error["file"];
            $line = $fatal_error["line"];
            $this->logger('PHP Fatal Error', $str, $file, $line);
        }
    }

    public static function getNow() {

        $comps = explode(' ', microtime());
        return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
    }

    public static function log_file($log) {
        if (LOG_TO_FILE) {
            redis_manager::list_push_static('logmanager', 'LIVELOAD.SLAVE.logwriter', $log);
        }
    }

    public static function log_time($store = false) {

        if (LOG_TIME) {
            $c_log = new stdClass();
            $bt = debug_backtrace();
            $bt_array = array_shift($bt);
            $c_log->time = self::getNow();
            $c_log->file = $bt_array['file'];
            $c_log->line = $bt_array['line'];
            $c_log->uid = model_hash::getUID();

            array_push(self::$times, $c_log);

            if ($store === true) {

                redis_manager::list_push_static('logmanager', 'LIVELOAD.SLAVE.logtimer', self::$times);
            }
        }
    }

    public static function static_logger($str, $hash = null) {

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $file = $caller["file"];
        $line = $caller["line"];
        if (empty($hash)) {
            $hash = "";
        }
        
        $log = array(
            "application" => APPLICATION,
            "type" => (isset($type))?$type:'',
            "str" => $str,
            "file" => $file,
            "line" => $line,
            "hash" => (empty($hash))?'':$hash
        );
        
        redis_manager::list_push_static('logmanager', 'LIVELOAD.SLAVE.customlogwriter', $log);
    }
    
    public static function txlog($tx_obj, $file, $line, $note, $data = null) {
        
        $now =  self::getNow();
        
        if(!isset($tx_obj->admin->start_time)){
            
            $tx_obj->admin->start_time =  $now;
            $tx_obj->admin->time_elapsed_point = 0;
            $tx_obj->admin->time_elapsed_total = 0;
            $tx_obj->admin->end_time =  $now;
            
        }
        else
        {
            $tx_obj->admin->time_elapsed_point = $now - $tx_obj->admin->end_time;
            $tx_obj->admin->time_elapsed_total = $tx_obj->admin->end_time + $tx_obj->admin->time_elapsed_point;
        }    
        
        if ($note == 'Send response') {
            $tx_obj->admin->response_sent = $tx_obj->admin->time_elapsed_total;
        }

        $tx_obj->log[] = array(
            'file' => $file,
            'line' => $line,
            'note' => $note,
            'data' => $data,
            'time' => $now,
            'elapsed' => $tx_obj->admin->time_elapsed_point,
            'elapsedTotal' => $tx_obj->admin->time_elapsed_total
        );
        
    }

}
