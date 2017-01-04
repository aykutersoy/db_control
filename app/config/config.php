<?php

define('APPLICATION', 'db_control');
define('REDIS_CREDENTIALS', '/etc/security/redisConfig');

if (!defined('ROOT')){
    define('ROOT', "/var/www/vhost/db_control/");
}
define('CREDENTIALS_FILE', ROOT . 'app/credentials/credentials');
define('STRUCTURE_DIR', 'Structure');

// DEFAULT DB DETAILS
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASSWORD', '123456789');