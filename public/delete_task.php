<?php
// Verwijdert een taak van de ingelogde gebruiker en gaat terug naar de lijst.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';

Auth::requireLogin();
Security::checkCsrf($_POST['csrf'] ?? ''); // CSRF check

$taskId = (int)($_POST['id'] ?? 0);
$listId = (int)($_POST['list_id'] ?? 0);

$pdo = Database::getConnection();

// Alleen verwijderen als de taak tot de lijst van deze user behoort
$stmt = $pdo->prepare("DELETE t FROM tasks t
                       JOIN lists l ON l.id = t.list_id
                       WHERE t.id = ? AND l.user_id = ?");
$stmt->execute([$taskId, $_SESSION['user_id']]);

// Terug naar de lijst
header('Location: /todo/public/list.php?id=' . $listId);