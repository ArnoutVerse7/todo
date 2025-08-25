<?php
// src/Models/Task.php
require_once __DIR__ . '/../Database.php';

class Task {
  private int $listId;
  private string $title;
  private string $priority;
  private bool $isDone;

  public function __construct(int $listId, string $title, string $priority='low', bool $isDone=false) {
    $this->setListId($listId);
    $this->setTitle($title);
    $this->setPriority($priority);
    $this->isDone = $isDone;
  }

  private function setListId(int $id): void {
    if ($id <= 0) throw new InvalidArgumentException('Ongeldige lijst');
    $this->listId = $id;
  }
  public function setTitle(string $t): void {
    $t = trim($t);
    if ($t === '') throw new InvalidArgumentException('Titel mag niet leeg zijn');
    if (mb_strlen($t) > 150) throw new InvalidArgumentException('Titel te lang');
    $this->title = $t;
  }
  public function setPriority(string $p): void {
    $allowed = ['low','medium','high'];
    if (!in_array($p, $allowed, true)) throw new InvalidArgumentException('Ongeldige prioriteit');
    $this->priority = $p;
  }

  public function save(): void {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("INSERT INTO tasks(list_id,title,priority,is_done) VALUES(?,?,?,?)");
    $stmt->execute([$this->listId, $this->title, $this->priority, (int)$this->isDone]);
  }
}
