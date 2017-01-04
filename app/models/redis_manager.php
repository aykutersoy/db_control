<?php

// create redis connection to engine
class redis_manager {

    public $r_conf;
    public $r_conn;
    public $r_conns = []; // Create Redis Connection Array
    public static $r_conn_f;
    public static $r_conn_s = [];

    public static function getConfigurations() {

        $r_conf = REDIS_CREDENTIALS;
        self::$r_conn_f = json_decode(file_get_contents($r_conf));
    }

    public static function selectRedisDB($conn_obj, $conn) {


        $rsp = $conn_obj->select(self::$r_conn_f->$conn->db);

        if ($rsp === FALSE) {

            throw new Exception('Invalid Redis DB');
        }
        return $conn_obj;
    }

    public static function establish_connect($conn, $optional_name = null) {

        unset($conn_obj);

        if (!isset(self::$r_conn_f)) {

            self::getConfigurations();
        }

        $conn_obj = new Redis();
        $conn_obj->connect(self::$r_conn_f->$conn->host, self::$r_conn_f->$conn->port);
        try {

            $conn_obj = self::selectRedisDB($conn_obj, $conn);
        } catch (Exception $e) {

            shell_exec("nohup redis-server --host " . self::$r_conn_f->$conn->host . " --port " . self::$r_conn_f->$conn->port . " >> /dev/null 2>&1 &");
            $conn_obj = new Redis();
            $conn_obj->connect(self::$r_conn_f->$conn->host, self::$r_conn_f->$conn->port);

            try {
                $conn_obj = self::selectRedisDB($conn_obj, $conn);
            } catch (Exception $e) {

                if ($conn != "logmanager") {
                    log_handler::static_logger($e->getMessage() . " - $conn\n" . print_r(self::$r_conn_f->$conn, 1));
                }
                return false;
            }
        }
        self::$r_conn_s[$conn] = $conn_obj;

        if ((!is_null($optional_name)) || (isset(self::$r_conn_f->$conn->client_name))) {
            if (is_null($optional_name)) {
                $conn_obj->client('setname', self::$r_conn_f->$conn->client_name);
            } else {
                $conn_obj->client('setname', $optional_name);
            }
        }

        return true;
    }

    public static function getConnection($conn, $optional_name = null) {

        if (!isset(self::$r_conn_s[$conn])) {
            self::establish_connect($conn, $optional_name);
        }

        if (isset(self::$r_conn_s[$conn])) {

            if (!is_null($optional_name)) {
                self::$r_conn_s[$conn]->client('setname', $optional_name);
            }

            return self::$r_conn_s[$conn];
        }
    }

    public static function list_push_static($conn, $list_name, $content) {
        self::getConnection($conn)->lpush($list_name, json_encode($content));
    }

    public static function list_pop_static($conn, $listening_channels, $time_out = 0, $optional_name = NULL) {

        $comm_rsp = self::getConnection($conn, $optional_name)->blpop($listening_channels, $time_out);
        $pop_rsp = (empty($comm_rsp)) ? array('', '') : $comm_rsp;
        return $pop_rsp;
    }

    public static function get_key_data($conn, $key) {
        return self::getConnection($conn)->get($key);
    }

    public static function set_key_data($conn, $key, $value) {
        return self::getConnection($conn)->set($key, $value);
    }

    public static function set_key_expiry($conn, $key, $value) {
        return self::getConnection($conn)->expire($key, $value);
    }

    public static function delete_key_data($conn, $key) {
        return self::getConnection($conn)->del($key);
    }

    public static function communicate($conn, $o_list, $i_list, $data, $time_out = 0) {

        // PUSH REQUEST
        self::getConnection($conn)->lPush($o_list, json_encode($data));
        // LISTEN FOR RESPONSE
        $comm_rsp = self::getConnection($conn)->blPop($i_list, $time_out);
        list($list, $json) = (empty($comm_rsp)) ? array('', '') : $comm_rsp;

        $obj = json_decode($json);

        if (empty($json)) {
            $obj = 'Error';
        }


        return $obj;
    }

    public static function inRestartMode($conn) {

        // PUSH REQUEST
        return self::getConnection($conn)->get('restart_mode');
    }

    public static function flushAll($conn) {

        return self::getConnection($conn)->flushAll();
    }

    public static function hash_get_all_data($conn, $key) {

        return self::getConnection($conn)->hgetall($key);
    }

    public static function incr($conn, $key) {

        return self::getConnection($conn)->incr($key);
    }

    public static function decr($conn, $key) {

        return self::getConnection($conn)->decr($key);
    }

    public static function client_list($conn) {

        return self::getConnection($conn)->client('list');
    }

    public static function clear_list($conn, $key) {

        return self::getConnection($conn)->hgetall($key);
    }

    public static function client_kill($conn, $key) {

        return self::getConnection($conn)->client('kill', $key);
    }

    public static function ping($conn) {

        return self::getConnection($conn)->ping();
    }

    public static function close($conn) {

        self::getConnection($conn)->close();
        if (isset(self::$r_conn_s[$conn])) {
            unset(self::$r_conn_s[$conn]);
        }
        return true;
    }

}
