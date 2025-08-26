<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Auth.php';

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  Security::checkCsrf($_POST['csrf'] ?? '');
  try {
    if (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
      throw new InvalidArgumentException('Passwords do not match');
    }
    Auth::register(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
    header('Location: /todo/public/login.php?registered=1');
    exit;
  } catch (PDOException $e) {
    $err = ($e->errorInfo[1] ?? null) === 1062 ? 'Email already exists' : 'Database error';
  } catch (Throwable $t) {
    $err = $t->getMessage();
  }
}
?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="assets/style.css" rel="stylesheet">
<title>Register</title>

<style>
  /* page-specific fix: kaart in kolom i.p.v. rij */
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
    <div class="h1">Register</div>
    <p class="muted">Create a new account</p>

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

      <label class="muted">Confirm password</label>
      <input class="form-input" type="password" name="password_confirm" required>

      <button class="btn btn-primary" style="width:100%;margin-top:4px;">Create</button>
    </form>
  </div>

  <div class="card auth-cta">
    <span class="muted">Already have an account?</span>
    <a href="login.php" class="btn btn-outline" style="margin-left:8px;">Login</a>
  </div>
</div>