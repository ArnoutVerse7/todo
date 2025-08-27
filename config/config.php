<?php
// Sessions
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', '1');
}
session_start();

// DB settings: eerst Railway env vars, anders lokale defaults
define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'todo_app');
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
