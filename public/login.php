<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../src/Security.php';
require_once __DIR__.'/../src/Auth.php';

$info = isset($_GET['registered']) ? 'Account aangemaakt. Log nu in.' : null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  Security::checkCsrf($_POST['csrf'] ?? '');
  if (Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
    header('Location: /todo/public/index.php'); exit;
  } else {
    $err = 'Onjuiste combinatie';
  }
}
?>
<!doctype html><meta charset="utf-8"><title>Inloggen</title>
<h1>Inloggen</h1>
<?php if($info): ?><p style="color:green"><?= Security::e($info) ?></p><?php endif; ?>
<?php if($err): ?><p style="color:red"><?= Security::e($err) ?></p><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">
  <p><label>Email<br><input type="email" name="email" required></label></p>
  <p><label>Wachtwoord<br><input type="password" name="password" required></label></p>
  <button>Login</button>
</form>
<p>Nog geen account? <a href="register.php">Registreren</a></p>
