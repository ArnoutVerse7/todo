<?php
require_once __DIR__ . '/../Database.php';

class User
{
    private int $id;
    private string $email;

    public function __construct(int $id, string $email)
    {
        $this->setId($id);
        $this->setEmail($email);
    }
    public function setId(int $id): void
    {
        if ($id <= 0) throw new InvalidArgumentException('Invalid user id');
        $this->id = $id;
    }
    public function setEmail(string $email): void
    {
        $e = trim(strtolower($email));
        if (!filter_var($e, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email address');
        $this->email = $e;
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function getEmail(): string
    {
        return $this->email;
    }

    public static function findById(int $id): ?User
    {
        $pdo = Database::getConnection();
        $st = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? new User((int)$row['id'], $row['email']) : null;
    }
}
