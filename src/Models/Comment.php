<?php
// src/Models/Comment.php
require_once __DIR__ . '/../Database.php';

class Comment {
  private int $taskId;
  private string $body;

  public function __construct(int $taskId, string $body) {
    $this->setTaskId($taskId);
    $this->setBody($body);
  }
  public function setTaskId(int $id): void {
    if ($id <= 0) throw new InvalidArgumentException('Invalid task id');
    $this->taskId = $id;
  }
  public function setBody(string $b): void {
    $b = trim($b);
    if ($b === '') throw new InvalidArgumentException('Comment cannot be empty');
    $this->body = $b;
  }

  public function save(): void {
    $pdo = Database::getConnection();
    $st = $pdo->prepare("INSERT INTO comments(task_id, body) VALUES(?, ?)");
    $st->execute([$this->taskId, $this->body]);
  }

  public static function create(int $taskId, string $body): void {
    (new Comment($taskId, $body))->save();
  }
}