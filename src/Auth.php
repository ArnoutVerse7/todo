<?php
// Basis authenticator: register, login, logout, requireLogin
require_once __DIR__ . '/Database.php';

class Auth
{
  // Nieuwe user registreren (validatie + bcrypt hash)
  public static function register(string $email, string $password): void
  {
    $email = trim(strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException('Invalid email address');
    }
    if (strlen($password) < 4) {
      throw new InvalidArgumentException('Password must be at least 4 characters long');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
    $stmt->execute([$email, $hash]);
  }
  // Inloggen: email opzoeken en wachtwoord verifiëren
  public static function login(string $email, string $password): bool
  {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->execute([trim(strtolower($email))]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
      session_regenerate_id(true); // tegen session fixation
      $_SESSION['user_id'] = (int)$user['id'];
      return true;
    }
    return false;
  }
  // Uitloggen: sessie leegmaken + cookie ongeldig maken
  public static function logout(): void
  {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
  }
  // Beschermde pagina's: als geen user, ga naar login
  public static function requireLogin(): void
  {
    if (empty($_SESSION['user_id'])) {
      header('Location: /todo/public/login.php');
      exit;
    }
  }
}
