<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/Security.php';
require_once __DIR__.'/../src/Database.php';
require_once __DIR__.'/../src/Models/Task.php';

Auth::requireLogin();

$listId = (int)($_GET['id'] ?? 0);

// check eigendom van lijst
$pdo = Database::getConnection();
$stmt = $pdo->prepare("SELECT id, title FROM lists WHERE id=? AND user_id=?");
$stmt->execute([$listId, $_SESSION['user_id']]);
$list = $stmt->fetch();
if (!$list) { http_response_code(404); exit('Lijst niet gevonden'); }

$err = $msg = null;

// toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
  Security::checkCsrf($_POST['csrf'] ?? '');
  try {
    $task = new Task($listId, $_POST['title'] ?? '', $_POST['priority'] ?? 'low');
    $task->save();
    $msg = 'Taak toegevoegd';
  } catch (PDOException $e) {
    // 23000 = unique constraint (geen dubbele taken binnen dezelfde lijst)
    $err = $e->getCode()==='23000' ? 'Deze taaknaam bestaat al in deze lijst' : 'Databasefout';
  } catch (Throwable $t) {
    $err = $t->getMessage();
  }
}

// sortering
$type = $_GET['type'] ?? 'priority';
$sort = $_GET['sort'] ?? 'desc';
$sort = ($sort === 'ascending') ? 'asc' : (($sort === 'descending') ? 'desc' : $sort);
$allowedTypes = ['title','priority']; $allowedSort = ['asc','desc'];
if (!in_array($type,$allowedTypes,true)) $type='priority';
if (!in_array($sort,$allowedSort,true)) $sort='desc';

if ($type === 'title') {
  $orderBy = "t.is_done ASC, t.title $sort";
} else {
  // prioriteit: high bovenaan bij desc, laag bovenaan bij asc
  $orderBy = $sort === 'asc'
    ? "t.is_done ASC, FIELD(t.priority,'low','medium','high'), t.created_at DESC"
    : "t.is_done ASC, FIELD(t.priority,'high','medium','low'), t.created_at DESC";
}

$tasks = $pdo->prepare("SELECT t.* FROM tasks t
                        JOIN lists l ON l.id=t.list_id
                        WHERE t.list_id=? AND l.user_id=?
                        ORDER BY $orderBy");
$tasks->execute([$listId, $_SESSION['user_id']]);
$tasks = $tasks->fetchAll();
?>
<!doctype html><meta charset="utf-8"><title><?= Security::e($list['title']) ?></title>
<h1><?= Security::e($list['title']) ?></h1>

<p>
  Sorteren:
  <a href="?id=<?= $listId ?>&type=title&sort=asc">Titel ↑</a> |
  <a href="?id=<?= $listId ?>&type=title&sort=desc">Titel ↓</a> |
  <a href="?id=<?= $listId ?>&type=priority&sort=desc">Prioriteit (hoog → laag)</a> |
  <a href="?id=<?= $listId ?>&type=priority&sort=asc">Prioriteit (laag → hoog)</a>
</p>

<?php if($msg): ?><p style="color:green"><?= Security::e($msg) ?></p><?php endif; ?>
<?php if($err): ?><p style="color:red"><?= Security::e($err) ?></p><?php endif; ?>

<form method="post" style="margin-bottom:1rem">
  <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
  <input type="hidden" name="action" value="add">
  <label>Titel <input name="title" required></label>
  <label>Prioriteit
    <select name="priority">
      <option value="low">low</option>
      <option value="medium">medium</option>
      <option value="high">high</option>
    </select>
  </label>
  <button>Toevoegen</button>
</form>

<ul>
<?php foreach($tasks as $t): ?>
  <li data-id="<?= (int)$t['id'] ?>" style="<?= $t['is_done'] ? 'text-decoration:line-through;color:#666;' : '' ?>">
    <input class="toggle" type="checkbox" <?= $t['is_done']?'checked':''; ?>>
    <strong><?= Security::e($t['title']) ?></strong>
    <small>(<?= Security::e($t['priority']) ?>)</small>
    <form action="delete_task.php" method="post" style="display:inline">
      <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
      <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
      <input type="hidden" name="list_id" value="<?= $listId ?>">
      <button>Verwijderen</button>
    </form>
  </li>
<?php endforeach; ?>
</ul>

<p><a href="index.php">← Terug naar lijsten</a></p>

<script>
document.querySelectorAll('.toggle').forEach(cb=>{
  cb.addEventListener('change', async (e)=>{
    const li = e.target.closest('li');
    const id = li.dataset.id;
    const res = await fetch('toggle_task.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ id, csrf: "<?= Security::csrfToken(); ?>" })
    });
    const data = await res.json();
    if(!data.ok){
      alert('Kon status niet aanpassen');
      e.target.checked = !e.target.checked;
      return;
    }
    // stijl updaten
    if(e.target.checked){ li.style.textDecoration='line-through'; li.style.color='#666'; }
    else { li.style.textDecoration=''; li.style.color=''; }
  });
});
</script>
