<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../src/Security.php';
require_once __DIR__.'/../src/Auth.php';

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  Security::checkCsrf($_POST['csrf'] ?? '');
  try {
    if (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
      throw new InvalidArgumentException('Wachtwoorden komen niet overeen');
    }
    Auth::register($_POST['email'] ?? '', $_POST['password'] ?? '');
    header('Location: /todo/public/login.php?registered=1'); exit;
  } catch (PDOException $e) {
    $err = (($e->errorInfo[1] ?? null) === 1062) ? 'Email bestaat al' : 'Databasefout';
  } catch (Throwable $t) {
    $err = $t->getMessage();
  }
}
?>
<!doctype html><meta charset="utf-8"><title>Registreren</title>
<h1>Registreren</h1>
<?php if($err): ?><p style="color:red"><?= Security::e($err) ?></p><?php endif; ?>
<form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
  <p><label>Email<br><input type="email" name="email" required></label></p>
  <p><label>Wachtwoord<br><input type="password" name="password" minlength="8" required></label></p>
  <p><label>Bevestig wachtwoord<br><input type="password" name="password_confirm" minlength="8" required></label></p>
  <button>Aanmaken</button>
</form>
<p>Heb je al een account? <a href="login.php">Inloggen</a></p>
