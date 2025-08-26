<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Auth.php';

$info = isset($_GET['registered']) ? 'Account aangemaakt. Log nu in.' : null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  Security::checkCsrf($_POST['csrf'] ?? '');
  if (Auth::login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '')) {
    header('Location: /todo/public/index.php');
    exit;
  } else {
    $err = 'Incorrect combination';
  }
}
?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="assets/style.css" rel="stylesheet">
<title>Login</title>

<style>
  .auth-card {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 8px
  }

  .auth-card .h1 {
    margin: 0 0 4px
  }

  .auth-card .muted {
    margin: 0 0 12px
  }

  .auth-card .form-input {
    width: 100%;
    margin: 6px 0 12px
  }

  .auth-cta {
    max-width: 480px;
    margin: 0 auto;
    text-align: center
  }
</style>

<div class="container">
  <div class="card auth-card" style="max-width:480px;margin:40px auto 16px;">
    <div class="h1">Login</div>
    <p class="muted">Welcome back</p>

    <?php if ($info): ?>
      <div id="flash" class="badge" style="background:#e7f9ef;color:#22863a;margin-bottom:8px;display:inline-block">
        <?= Security::e($info) ?>
      </div>
    <?php endif; ?>

    <?php if ($err): ?>
      <div class="badge" style="background:#ffe3e3;color:#e03131;margin-bottom:8px;display:inline-block">
        <?= Security::e($err) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= Security::csrfToken(); ?>">

      <label class="muted">Email</label>
      <input class="form-input" type="email" name="email" required>

      <label class="muted">Password</label>
      <input class="form-input" type="password" name="password" required>

      <button class="btn btn-primary" style="width:100%;margin-top:4px;">Login</button>
    </form>
  </div>

  <div class="card auth-cta">
    <span class="muted">No account yet?</span>
    <a href="register.php" class="btn btn-outline" style="margin-left:8px;">Register</a>
  </div>
</div>

<script>
  // laat info-bericht (na registratie) vanzelf verdwijnen
  setTimeout(() => {
    const f = document.getElementById('flash');
    if (f) f.remove();
  }, 2500);
</script>