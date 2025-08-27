<?php
// Startpagina na login: lijsten tonen, nieuwe lijst toevoegen (met CSRF), flash-melding.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Models/TodoList.php';

Auth::requireLogin();

$err = null;

// Flash 1x tonen
$flash = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

// Nieuwe lijst via POST (PRG + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'add_list')) {
    Security::checkCsrf($_POST['csrf'] ?? '');
    try {
        $title = trim($_POST['title'] ?? '');
        $list  = new TodoList($_SESSION['user_id'], $title);
        $list->save();

        $_SESSION['flash_success'] = 'Lijst toegevoegd';
        header('Location: index.php'); // PRG
        exit;
    } catch (Throwable $t) {
        $err = $t->getMessage();
    }
}

// Lijsten + counters voor statusbadge
$lists = TodoList::allWithCountersByUser($_SESSION['user_id']);
?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="assets/style.css" rel="stylesheet">
<title>My Todo Lists</title>

<div class="container">
    <div class="header">
        <div class="h1">My Todo Lists</div>
        <a href="logout.php" class="btn btn-outline">Logout</a>
    </div>

    <?php if ($flash): ?>
        <!-- succesmelding -->
        <div id="flash" class="badge" style="background:#e7f9ef;color:#22863a;margin:12px 0;display:inline-block">
            <?= Security::e($flash) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <!-- foutmelding -->
        <div class="badge" style="background:#ffe3e3;color:#e03131;margin:12px 0;display:inline-block">
            <?= Security::e($err) ?>
        </div>
    <?php endif; ?>

    <!-- Nieuwe lijst -->
    <form method="post" class="card" style="margin-bottom:16px">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
        <input type="hidden" name="action" value="add_list">
        <div class="left" style="flex:1; gap:10px">
            <span class="muted">New List</span>
            <input name="title" required class="form-input" style="flex:0.99">
        </div>
        <div class="right">
            <button class="btn btn-primary">Add</button>
        </div>
    </form>

    <?php if (!$lists): ?>
        <p class="muted">No lists yet.</p>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($lists as $l): ?>
                <?php
                $total    = (int)($l['total'] ?? 0);
                $done     = (int)($l['done']  ?? 0);
                $hasTasks = $total > 0;
                $allDone  = $hasTasks && ($done === $total);
                ?>
                <li class="card">
                    <div class="left">
                        <div>
                            <div class="muted">List</div>
                            <a class="task-title" href="list.php?id=<?= (int)$l['id'] ?>">
                                <?= Security::e($l['title']) ?>
                            </a>
                            <div class="muted" style="margin-top:4px">
                                Created at: <?= Security::e($l['created_at']) ?>
                            </div>
                            <?php if ($hasTasks): ?>
                                <div class="muted" style="margin-top:4px">
                                    <?= $done ?> / <?= $total ?> completed
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="right">
                        <?php if ($hasTasks): ?>
                            <!-- eenvoudige statusbadge -->
                            <span class="status <?= $allDone ? 'done' : 'todo' ?>">
                                <?= $allDone ? 'done' : 'to do' ?>
                            </span>
                        <?php endif; ?>
                        <form action="delete_list.php" method="post">
                            <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
                            <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                            <button class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<script>
    // Flash automatisch verbergen
    setTimeout(() => {
        const f = document.getElementById('flash');
        if (f) f.remove();
    }, 2500);
</script>