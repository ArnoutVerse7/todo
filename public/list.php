<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Models/Task.php';

Auth::requireLogin();

$listId = (int)($_GET['id'] ?? 0);

// lijst ophalen + eigendom
$pdo = Database::getConnection();
$stmt = $pdo->prepare("SELECT id, title FROM lists WHERE id=? AND user_id=?");
$stmt->execute([$listId, $_SESSION['user_id']]);
$list = $stmt->fetch();
if (!$list) {
    http_response_code(404);
    exit('List not found');
}

$err   = null;
// flash message
$flash = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

// toevoegen (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    Security::checkCsrf($_POST['csrf'] ?? '');
    try {
        (new Task($listId, $_POST['title'] ?? '', $_POST['priority'] ?? 'low'))->save();
        $_SESSION['flash_success'] = 'Task added successfully';
        header('Location: list.php?id=' . $listId);
        exit;
    } catch (PDOException $e) {
        $err = $e->getCode() === '23000' ? 'This task name already exists in this list' : 'Database error';
    } catch (Throwable $t) {
        $err = $t->getMessage();
    }
}

// sortering
$typeParam = $_GET['type'] ?? 'priority';
$sortParam = $_GET['sort'] ?? 'descending';
$type = in_array($typeParam, ['title', 'priority'], true) ? $typeParam : 'priority';
$sort = ($sortParam === 'ascending') ? 'asc' : 'desc';

if ($type === 'title') {
    $orderBy = "t.is_done ASC, t.title $sort";
} else {
    $orderBy = $sort === 'asc'
        ? "t.is_done ASC, FIELD(t.priority,'low','medium','high'), t.created_at DESC"
        : "t.is_done ASC, FIELD(t.priority,'high','medium','low'), t.created_at DESC";
}

$tasksStmt = $pdo->prepare("SELECT t.* FROM tasks t
                            JOIN lists l ON l.id=t.list_id
                            WHERE t.list_id=? AND l.user_id=?
                            ORDER BY $orderBy");
$tasksStmt->execute([$listId, $_SESSION['user_id']]);
$tasks = $tasksStmt->fetchAll();
?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="assets/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<title><?= Security::e($list['title']) ?></title>

<div class="container">
    <div class="header">
        <div class="h1"><?= Security::e($list['title']) ?></div>
        <a href="index.php" class="btn btn-outline">← Back to lists</a>
    </div>

    <div class="card sort" style="gap:8px; align-items:center">
        <span class="muted">Sort by:</span>
        <a href="?id=<?= $listId ?>&type=title&sort=ascending">Title ↑</a>
        <a href="?id=<?= $listId ?>&type=title&sort=descending">Title ↓</a>
        <a href="?id=<?= $listId ?>&type=priority&sort=descending">Priority (high → low)</a>
        <a href="?id=<?= $listId ?>&type=priority&sort=ascending">Priority (low → high)</a>
    </div>

    <?php if ($flash): ?>
        <div id="flash" class="badge" style="background:#e7f9ef;color:#22863a;margin:12px 0;display:inline-block">
            <?= Security::e($flash) ?>
        </div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="badge" style="background:#ffe3e3;color:#e03131;margin:12px 0;display:inline-block">
            <?= Security::e($err) ?>
        </div>
    <?php endif; ?>

    <!-- Add task -->
    <form method="post" class="card" style="margin-bottom:16px">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
        <input type="hidden" name="action" value="add">
        <div class="left" style="flex:1; gap:10px">
            <span class="muted">Task</span>
            <input name="title" required class="form-input" style="flex:1">
            <span class="muted">Priority</span>
            <select name="priority" class="form-input">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
        </div>
        <div class="right">
            <button class="btn btn-primary">Add Task</button>
        </div>
    </form>

    <!-- Tasks -->
    <?php if (!$tasks): ?>
        <p class="muted">No tasks yet.</p>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($tasks as $t):
                $prioClass = $t['priority'] === 'high' ? 'p-high' : ($t['priority'] === 'medium' ? 'p-medium' : 'p-low');
                $rowClass  = $t['is_done'] ? 'card is-done' : 'card';
            ?>
                <li class="<?= $rowClass ?>" data-id="<?= (int)$t['id'] ?>">
                    <div class="left">
                        <input class="toggle" type="checkbox" <?= $t['is_done'] ? 'checked' : ''; ?>>
                        <div>
                            <div class="muted">Task</div>
                            <a class="task-title" href="item.php?id=<?= (int)$t['id'] ?>"><?= Security::e($t['title']) ?></a>
                        </div>
                    </div>

                    <div class="right">
                        <div>
                            <div class="muted">Priority</div>
                            <span class="badge <?= $prioClass ?>"><?= Security::e($t['priority']) ?></span>
                        </div>

                        <form action="delete_task.php" method="post">
                            <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                            <input type="hidden" name="list_id" value="<?= (int)$listId ?>">
                            <button class="icon-btn icon-danger" title="Delete"><i class="ri-delete-bin-6-line"></i></button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<script>
    // Toggle done/undo via AJAX
    document.querySelectorAll('.toggle').forEach(cb => {
        cb.addEventListener('change', async (e) => {
            const li = e.target.closest('li');
            const id = li.dataset.id;
            const res = await fetch('toggle_task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    id,
                    csrf: "<?= Security::csrfToken(); ?>"
                })
            });
            const data = await res.json();
            if (!data.ok) {
                alert('Could not change status');
                e.target.checked = !e.target.checked;
                return;
            }
            if (e.target.checked) li.classList.add('is-done');
            else li.classList.remove('is-done');
        });
    });

    // Flash message auto-hide
    setTimeout(() => {
        const f = document.getElementById('flash');
        if (f) f.remove();
    }, 2500);
</script>