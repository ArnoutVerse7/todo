<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';

Auth::requireLogin();
Security::checkCsrf($_POST['csrf'] ?? '');

$id = (int)($_POST['id'] ?? 0);
$pdo = Database::getConnection();

// Alleen lijsten van de ingelogde user mogen weg
$stmt = $pdo->prepare("DELETE FROM lists WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);

header('Location: /todo/public/index.php');
