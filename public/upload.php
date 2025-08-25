<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/Security.php';
require_once __DIR__.'/../src/Database.php';

Auth::requireLogin();
Security::checkCsrf($_POST['csrf'] ?? '');

$taskId = (int)($_POST['task_id'] ?? 0);
$pdo = Database::getConnection();

// eigendomscheck
$own = $pdo->prepare("SELECT l.user_id FROM tasks t JOIN lists l ON l.id=t.list_id WHERE t.id=?");
$own->execute([$taskId]);
$row = $own->fetch();
if (!$row || $row['user_id'] !== $_SESSION['user_id']) { http_response_code(403); exit('Forbidden'); }

// basisvalidatie
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) { exit('Upload fout'); }

$f = $_FILES['file'];
$allowed = ['application/pdf','image/png','image/jpeg','image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($f['tmp_name']);
if (!in_array($mime, $allowed, true)) { exit('Ongeldig bestandstype'); }
if ($f['size'] > 10*1024*1024) { exit('Max 10MB'); }

$ext = pathinfo($f['name'], PATHINFO_EXTENSION);
$stored = bin2hex(random_bytes(16)).($ext ? '.'.$ext : '');
$dest = __DIR__.'/uploads/'.$stored;

if (!move_uploaded_file($f['tmp_name'], $dest)) { exit('Kon bestand niet opslaan'); }

$ins = $pdo->prepare("INSERT INTO attachments(task_id, original_name, stored_name, mime, size)
                      VALUES(?,?,?,?,?)");
$ins->execute([$taskId, $f['name'], $stored, $mime, $f['size']]);

header('Location: /todo/public/item.php?id='.$taskId);
