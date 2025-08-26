<?php
// src/Models/TodoList.php
require_once __DIR__ . '/../Database.php';

final class TodoList
{
    private ?int $id = null;
    private int $userId;
    private string $title;

    public function __construct(int $userId, string $title, ?int $id = null)
    {
        $this->setUserId($userId);
        $this->setTitle($title);
        if ($id !== null) { $this->id = $id; }
    }

    // --- getters ---
    public function getId(): ?int     { return $this->id; }
    public function getUserId(): int  { return $this->userId; }
    public function getTitle(): string{ return $this->title; }

    // --- setters & validatie ---
    public function setUserId(int $id): void
    {
        if ($id <= 0) throw new InvalidArgumentException('Invalid user');
        $this->userId = $id;
    }

    public function setTitle(string $title): void
    {
        $t = trim($title);
        if ($t === '')              throw new InvalidArgumentException('Title cannot be empty');
        if (mb_strlen($t) > 150)    throw new InvalidArgumentException('Title is too long');
        $this->title = $t;
    }

    // --- create ---
    public function save(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO lists (user_id, title) VALUES (?, ?)");
        $stmt->execute([$this->userId, $this->title]);
        $this->id = (int)$pdo->lastInsertId();
    }

    // --- delete (met eigendomscheck) ---
    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM lists WHERE id=? AND user_id=?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    // --- ophalen mét counters (voor index.php status badges) ---
    public static function allWithCountersByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT l.id, l.title, l.created_at,
                       COUNT(t.id) AS total,
                       SUM(CASE WHEN t.is_done=1 THEN 1 ELSE 0 END) AS done
                FROM lists l
                LEFT JOIN tasks t ON t.list_id = l.id
                WHERE l.user_id=?
                GROUP BY l.id
                ORDER BY l.created_at DESC";
        $st = $pdo->prepare($sql);
        $st->execute([$userId]);
        return $st->fetchAll();
    }
}
