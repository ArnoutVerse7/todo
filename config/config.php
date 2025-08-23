<?php
// Sessions veilig
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
if (!empty($_SERVER['HTTPS'])) { ini_set('session.cookie_secure','1'); }
session_start();

// Database credentials (XAMPP defaults)
const DB_HOST = '127.0.0.1';
const DB_NAME = 'todo_app';
const DB_USER = 'root';
const DB_PASS = '';
