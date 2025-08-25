<?php
// src/Models/TodoList.php
require_once __DIR__ . '/../Database.php';

class TodoList {
  private int $userId;
  private string $title;

  public function __construct(int $userId, string $title) {
    $this->setUserId($userId);
    $this->setTitle($title);
  }

  public function setUserId(int $id): void {
    if ($id <= 0) throw new InvalidArgumentException('Ongeldige gebruiker');
    $this->userId = $id;
  }

  public function setTitle(string $title): void {
    $t = trim($title);
    if ($t === '') throw new InvalidArgumentException('Titel mag niet leeg zijn');
    if (mb_strlen($t) > 150) throw new InvalidArgumentException('Titel te lang');
    $this->title = $t;
  }

  public function save(): void {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("INSERT INTO lists (user_id, title) VALUES (?, ?)");
    $stmt->execute([$this->userId, $this->title]);
  }
}
