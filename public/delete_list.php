<?php
// Lijst verwijderen (alleen POST + CSRF, alleen eigen lijsten).
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Models/TodoList.php';

Auth::requireLogin();

// Alleen POST toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// CSRF check
Security::checkCsrf($_POST['csrf'] ?? '');

// ID ophalen en snel valideren
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_success'] = 'Invalid list';
    header('Location: /todo/public/index.php');
    exit;
}

try {
    // Verwijderen via model, met eigendomscheck
    $ok = TodoList::delete($id, $_SESSION['user_id']);
    $_SESSION['flash_success'] = $ok ? 'List deleted' : 'Could not delete list';
} catch (Throwable $t) {
    // Bij DB-fouten (bv. foreign keys)
    $_SESSION['flash_success'] = 'Could not delete list';
}

// Terug naar overzicht
header('Location: /todo/public/index.php');
exit;