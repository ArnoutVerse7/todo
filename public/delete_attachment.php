<?php
// Verwijder een bijlage (alleen eigen taak), retourneer JSON bij AJAX.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';

Auth::requireLogin();
Security::checkCsrf($_POST['csrf'] ?? ''); // CSRF check

$attId  = (int)($_POST['id'] ?? 0);
$taskId = (int)($_POST['task_id'] ?? 0);

$pdo = Database::getConnection();

// Bijlage opzoeken en meteen eigendom controleren via JOIN op lists.user_id
$q = $pdo->prepare("
  SELECT a.stored_name
  FROM attachments a
  JOIN tasks t ON t.id = a.task_id
  JOIN lists l ON l.id = t.list_id
  WHERE a.id = ? AND t.id = ? AND l.user_id = ?
");
$q->execute([$attId, $taskId, $_SESSION['user_id']]);
$row = $q->fetch();

$deleted = false;
if ($row) {
    // Bestand op schijf verwijderen (als het bestaat)
    $path = __DIR__ . '/uploads/' . $row['stored_name'];
    if (is_file($path)) {
        @unlink($path);
    }

    // DB-record verwijderen
    $del = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
    $del->execute([$attId]);
    $deleted = ($del->rowCount() === 1);
}

// AJAX detecteren
$isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

// Bij AJAX: JSON teruggeven
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => $deleted]);
    exit;
}

// Anders: terug naar item-pagina
header('Location: item.php?id=' . $taskId . '#attachments');