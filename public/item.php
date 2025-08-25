<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';

Auth::requireLogin();

$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) {
    http_response_code(404);
    exit('Item niet gevonden');
}

$pdo = Database::getConnection();

// Task moet van de ingelogde user zijn → filter in SQL
$stmt = $pdo->prepare("
  SELECT t.*, l.title AS list_title
  FROM tasks t
  JOIN lists l ON l.id = t.list_id
  WHERE t.id = ? AND l.user_id = ?
");
$stmt->execute([$taskId, $_SESSION['user_id']]);
$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    exit('Item niet gevonden');
}

// Comment toevoegen (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::checkCsrf($_POST['csrf'] ?? '');
    $body = trim($_POST['body'] ?? '');
    if ($body !== '') {
        $ins = $pdo->prepare("INSERT INTO comments(task_id, body) VALUES(?, ?)");
        $ins->execute([$taskId, $body]);
    }
    header('Location: /todo/public/item.php?id=' . $taskId);
    exit;
}

// Comments & files ophalen
$cq = $pdo->prepare("SELECT body, created_at FROM comments WHERE task_id=? ORDER BY created_at DESC");
$cq->execute([$taskId]);
$comments = $cq->fetchAll();

$fq = $pdo->prepare("SELECT id, original_name, stored_name, created_at
                     FROM attachments
                     WHERE task_id=? ORDER BY created_at DESC");
$fq->execute([$taskId]);
$files = $fq->fetchAll();
?>
<!doctype html>
<meta charset="utf-8">
<title>Item</title>
<h1><?= Security::e($task['title']) ?></h1>
<p>Lijst: <?= Security::e($task['list_title']) ?> |
    Prioriteit: <strong><?= Security::e($task['priority']) ?></strong> |
    Status: <?= $task['is_done'] ? 'done' : 'todo' ?></p>

<h2>Commentaar toevoegen</h2>
<form method="post">
    <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
    <textarea name="body" required rows="3" cols="50"></textarea><br>
    <button>Opslaan</button>
</form>

<h2>Commentaren</h2>
<?php if (!$comments): ?>
    <p>Nog geen commentaar.</p>
<?php else: ?>
    <ul>
        <?php foreach ($comments as $c): ?>
            <li><?= nl2br(Security::e($c['body'])) ?>
                <small>(<?= Security::e($c['created_at']) ?>)</small>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Bestanden</h2>
<form action="upload.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
    <input type="hidden" name="task_id" value="<?= $taskId ?>">
    <input type="file" name="file" accept="application/pdf,image/*" required>
    <button>Upload</button>
</form>

<?php if (!$files): ?>
    <p>Geen bestanden.</p>
<?php else: ?>
    <ul>
        <?php foreach ($files as $f): ?>
            <li>
                <a href="uploads/<?= Security::e($f['stored_name']) ?>" target="_blank">
                    <?= Security::e($f['original_name']) ?>
                </a>
                <small>(<?= Security::e($f['created_at']) ?>)</small>
                <form method="post" action="delete_attachment.php" style="display:inline" onsubmit="return confirm('Bestand verwijderen?');">
                    <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <input type="hidden" name="task_id" value="<?= (int)$taskId ?>">
                    <button>Verwijderen</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p><a href="list.php?id=<?= (int)$task['list_id'] ?>">← Terug naar lijst</a></p>