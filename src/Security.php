<?php
// Kleine helpers: XSS-escape + CSRF token/check.
// (Zorg dat session_start() al gebeurd is.)

class Security {
  // Veilige HTML-output (tegen XSS)
  public static function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }

  // CSRF-token per sessie (aanmaken of ophalen)
  public static function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(32)); // 64 hex chars
    }
    return $_SESSION['csrf'];
  }

  // CSRF controleren, stop bij fout
  public static function checkCsrf(string $token): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
      http_response_code(403);
      exit('CSRF check failed');
    }
  }
}
