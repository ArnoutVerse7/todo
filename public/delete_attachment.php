<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/Security.php';
require_once __DIR__.'/../src/Database.php';

Auth::requireLogin();
Security::checkCsrf($_POST['csrf'] ?? '');

$attId  = (int)($_POST['id'] ?? 0);
$taskId = (int)($_POST['task_id'] ?? 0);

$pdo = Database::getConnection();

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
  $path = __DIR__ . '/uploads/' . $row['stored_name'];
  if (is_file($path)) { @unlink($path); }
  $del = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
  $del->execute([$attId]);
  $deleted = ($del->rowCount() === 1);
}

$isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
       || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

if ($isAjax) {
  header('Content-Type: application/json');
  echo json_encode(['ok' => $deleted]);
  exit;
}

header('Location: item.php?id='.$taskId.'#attachments');
