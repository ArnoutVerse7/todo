<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Models/TodoList.php';
require_once __DIR__ . '/../src/Database.php';

Auth::requireLogin();

$err = $msg = null;

// Nieuwe lijst toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_list') {
  Security::checkCsrf($_POST['csrf'] ?? '');
  try {
    $list = new TodoList($_SESSION['user_id'], $_POST['title'] ?? '');
    $list->save();
    $msg = 'Lijst toegevoegd';
  } catch (Throwable $t) {
    $err = $t->getMessage();
  }
}

// Lijsten ophalen van ingelogde user
$pdo = Database::getConnection();
$stmt = $pdo->prepare("SELECT id, title, created_at FROM lists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$lists = $stmt->fetchAll();
?>
<!doctype html><meta charset="utf-8"><title>Mijn lijsten</title>
<h1>Mijn lijsten</h1>

<?php if($msg): ?><p style="color:green"><?= Security::e($msg) ?></p><?php endif; ?>
<?php if($err): ?><p style="color:red"><?= Security::e($err) ?></p><?php endif; ?>

<form method="post" style="margin-bottom:1rem">
  <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
  <input type="hidden" name="action" value="add_list">
  <label>Nieuwe lijst: <input name="title" required></label>
  <button>Toevoegen</button>
</form>

<ul>
<?php foreach($lists as $l): ?>
  <li>
    <a href="list.php?id=<?= (int)$l['id'] ?>"><?= Security::e($l['title']) ?></a>
    <small>(<?= Security::e($l['created_at']) ?>)</small>
    <form action="delete_list.php" method="post" style="display:inline">
      <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
      <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
      <button>Verwijderen</button>
    </form>
  </li>
<?php endforeach; ?>
</ul>

<p><a href="logout.php">Uitloggen</a></p>
