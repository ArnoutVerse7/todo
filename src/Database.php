<?php
// Eenvoudige PDO-connector (hergebruikt dezelfde connectie)
require_once __DIR__ . '/../config/config.php';

class Database
{
  public static function getConnection(): PDO
  {
    static $pdo = null; // één instance per request
    if ($pdo === null) {
      // DSN op basis van config-constanten
      $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

      // Maak PDO en zet basis opties
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // gooi exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch als associatieve array
      ]);
    }
    return $pdo;
  }
}
