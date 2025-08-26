<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Models/Comment.php';

Auth::requireLogin();

$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) {
    http_response_code(404);
    exit('Item not found');
}

$pdo = Database::getConnection();
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
    exit('Item not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::checkCsrf($_POST['csrf'] ?? '');
    try {
        Comment::create($taskId, $_POST['body'] ?? '');
    } catch (Throwable $e) {
    }
    header('Location: item.php?id=' . $taskId . '#comments');
    exit;
}

$cq = $pdo->prepare("SELECT body, created_at FROM comments WHERE task_id=? ORDER BY created_at DESC");
$cq->execute([$taskId]);
$comments = $cq->fetchAll();

$fq = $pdo->prepare("SELECT id, original_name, stored_name, created_at FROM attachments WHERE task_id=? ORDER BY created_at DESC");
$fq->execute([$taskId]);
$files = $fq->fetchAll();
?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="assets/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<title>Item</title>

<div class="container">
    <div class="header">
        <div class="h1"><?= Security::e($task['title']) ?></div>
        <a href="list.php?id=<?= (int)$task['list_id'] ?>" class="btn btn-outline">← Back</a>
    </div>

    <!-- Info card -->
    <div class="card" style="margin-bottom:16px">
        <div class="left" style="gap:24px">
            <div>
                <div class="muted">List</div>
                <div class="task-title"><?= Security::e($task['list_title']) ?></div>
            </div>
            <div>
                <div class="muted">Prioriteit</div>
                <?php
                $prioClass = $task['priority'] === 'high' ? 'p-high' : ($task['priority'] === 'medium' ? 'p-medium' : 'p-low');
                ?>
                <span class="badge <?= $prioClass ?>"><?= Security::e($task['priority']) ?></span>
            </div>
            <div>
                <div class="muted">Status</div>
                <span class="status <?= $task['is_done'] ? 'done' : 'todo' ?>"><?= $task['is_done'] ? 'Done' : 'To Do' ?></span>
            </div>
        </div>
    </div>

    <!-- Comment toevoegen -->
    <div class="card" id="comments" style="margin-bottom:16px">
        <form method="post" style="width:100%; display:flex; gap:12px; align-items:flex-start">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
            <div style="flex:1">
                <div class="muted">Comment</div>
                <textarea name="body" required rows="3" class="form-input" style="width:100%"></textarea>
            </div>
            <button class="btn btn-primary" style="align-self:center">Save</button>
        </form>
    </div>

    <!-- Commentlijst -->
    <div class="card" style="margin-bottom:16px">
        <div style="width:100%">
            <div class="muted" style="margin-bottom:8px">Comments</div>
            <?php if (!$comments): ?>
                <p class="muted" style="margin:0">No comments yet.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($comments as $c): ?>
                        <li class="card">
                            <div><?= nl2br(Security::e($c['body'])) ?></div>
                            <div class="muted"><?= Security::e($c['created_at']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upload -->
    <div class="card" style="margin-bottom:16px">
        <form action="upload.php" method="post" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:center; width:100%">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
            <input type="hidden" name="task_id" value="<?= (int)$taskId ?>">
            <input type="file" name="file" accept="application/pdf,image/*" required class="form-input" style="flex:1">
            <button class="btn btn-primary"><i class="ri-upload-2-line"></i>&nbsp;Upload</button>
        </form>
    </div>

    <!-- Files -->
    <div class="card">
        <div style="width:100%">
            <div class="muted" style="margin-bottom:8px">Files</div>
            <?php if (!$files): ?>
                <p class="muted" style="margin:0">No files.</p>
            <?php else: ?>
                <ul class="list" id="attachments-list">
                    <?php foreach ($files as $f): ?>
                        <li class="card" id="att-<?= (int)$f['id'] ?>" style="padding:12px 16px">
                            <div class="left">
                                <i class="ri-file-2-line" style="color:#768394"></i>
                                <a href="uploads/<?= Security::e($f['stored_name']) ?>" download="<?= Security::e($f['original_name']) ?>" target="_blank" class="task-title">
                                    <?= Security::e($f['original_name']) ?>
                                </a>
                                <span class="muted">(<?= Security::e($f['created_at']) ?>)</span>
                            </div>
                            <div class="right">
                                <form class="del-attach" method="post" action="delete_attachment.php">
                                    <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
                                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                    <input type="hidden" name="task_id" value="<?= (int)$taskId ?>">
                                    <button class="icon-btn icon-danger" title="delete"><i class="ri-delete-bin-6-line"></i></button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // AJAX: bijlage verwijderen zonder refresh
    document.querySelectorAll('.del-attach').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const li = form.closest('li');
            const fd = new FormData(form);
            const res = await fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            let ok = false;
            try {
                const data = await res.json();
                ok = !!data.ok;
            } catch (_) {}
            if (ok) {
                li.remove();
            } else {
                alert('Delete failed.');
            }
        });
    });
</script>