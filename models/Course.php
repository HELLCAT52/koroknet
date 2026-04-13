<?php

class Course
{
    private PDO $conn;
    private string $tableName = 'courses';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $query = "
            SELECT c.id, c.name, c.description
            FROM {$this->tableName} c
            INNER JOIN (
                SELECT MAX(id) AS id
                FROM {$this->tableName}
                GROUP BY LOWER(TRIM(name))
            ) uniq ON uniq.id = c.id
            ORDER BY c.id DESC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $name, string $description): bool
    {
        $query = "INSERT INTO {$this->tableName} (name, description) VALUES (:name, :description)";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':name' => $name,
            ':description' => $description
        ]);
    }

    public function existsByName(string $name): bool
    {
        $query = "SELECT id FROM {$this->tableName} WHERE name = :name LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':name' => $name]);

        return (bool) $stmt->fetchColumn();
    }

    public function existsById(int $courseId): bool
    {
        $query = "SELECT id FROM {$this->tableName} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $courseId]);

        return (bool) $stmt->fetchColumn();
    }
}
