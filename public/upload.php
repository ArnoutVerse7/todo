<?php
// Upload bijlage voor een task (alleen eigenaar). Kleine, directe checks.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';

Auth::requireLogin();
// Alleen POST toegestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// CSRF controleren
Security::checkCsrf($_POST['csrf'] ?? '');

// Basis input
$taskId = (int)($_POST['task_id'] ?? 0);
if ($taskId <= 0) {
    http_response_code(400);
    exit('Bad request');
}

$pdo = Database::getConnection();

// Eigenaarschap: task moet van ingelogde user zijn
$own = $pdo->prepare("
  SELECT t.id FROM tasks t
  JOIN lists l ON l.id = t.list_id
  WHERE t.id = ? AND l.user_id = ?
");
$own->execute([$taskId, $_SESSION['user_id']]);
if (!$own->fetch()) {
    http_response_code(403);
    exit('Forbidden');
}

// Bestandscontrole (moet aanwezig zijn en zonder upload-error)
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('Upload failed');
}

// Mime check (alleen pdf/png/jpg/webp)
$finfo   = new finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->file($_FILES['file']['tmp_name']);
$allowed = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    http_response_code(415);
    exit('Unsupported file type');
}

// Max 10MB
if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
    http_response_code(413);
    exit('Max 10MB');
}

// Veilige bestandsnaam voor opslag
$ext    = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$stored = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
$dest   = __DIR__ . '/uploads/' . $stored;

// Fysiek wegschrijven
if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    http_response_code(500);
    exit('Could not save file');
}

// Registreren in DB
$ins = $pdo->prepare("
  INSERT INTO attachments(task_id, original_name, stored_name, mime, size)
  VALUES(?,?,?,?,?)
");
$ins->execute([
    $taskId,
    $_FILES['file']['name'],
    $stored,
    $mime,
    $_FILES['file']['size']
]);

// Terug naar detail
header('Location: item.php?id=' . $taskId . '#attachments');