<?php
// src/Security.php
class Security {
  // Escape voor veilige output (tegen XSS)
  public static function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }

  // CSRF-token genereren/halen
  public static function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
  }

  // CSRF-token valideren
  public static function checkCsrf(string $token): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
      http_response_code(403);
      exit('CSRF check failed');
    }
  }
}
