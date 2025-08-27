<?php
// AJAX: toggle done/todo voor een task (alleen eigen taken).
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';

Auth::requireLogin();
header('Content-Type: application/json');

// CSRF check
Security::checkCsrf($_POST['csrf'] ?? '');

// Input
$taskId = (int)($_POST['id'] ?? 0);
$pdo    = Database::getConnection();

// Toggle via UPDATE + eigendomscheck (JOIN met lists.user_id)
$stmt = $pdo->prepare("UPDATE tasks t
                       JOIN lists l ON l.id = t.list_id
                       SET t.is_done = 1 - t.is_done
                       WHERE t.id = ? AND l.user_id = ?");
$stmt->execute([$taskId, $_SESSION['user_id']]);

// JSON resultaat voor frontend
echo json_encode(['ok' => $stmt->rowCount() === 1]);