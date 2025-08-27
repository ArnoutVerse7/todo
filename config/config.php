<?php
// Sessions
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', '1');
}
session_start();

// ===== Base path voor links/redirects =====
// Zet lokaal (indien je via http://localhost/todo/public werkt) APP_BASE op '/todo/public'.
// Op Railway laat je dit leeg of zet je geen env var; dan is APP_BASE ''.
$__base = getenv('APP_BASE');
if ($__base === false) {
    // fallback: pas aan naar '/todo/public' als je lokaal onder die submap draait
    $__base = '';
}
define('APP_BASE', rtrim($__base, '/'));

// ===== DB settings: Railway env vars of lokale defaults =====
define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'todo_app');
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
