<?php

define('APPLICATION', 'db_control');

if (!defined('ROOT')){
    define('ROOT', __DIR__ . '/../../');
}
define('CREDENTIALS_FILE', ROOT . 'app/credentials/credentials');
define('STRUCTURE_DIR', 'Structure');

// DEFAULT DB DETAILS
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASSWORD', '12345678qQ');