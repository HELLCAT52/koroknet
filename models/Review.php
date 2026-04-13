<?php

class Review
{
    private PDO $conn;
    private string $tableName = 'reviews';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->ensureModerationSchema();
    }

    public function create(int $userId, int $applicationId, string $content, int $rating): bool
    {
        $query = "
            INSERT INTO {$this->tableName} (user_id, application_id, content, rating, status)
            VALUES (:user_id, :application_id, :content, :rating, 'pending')
        ";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':user_id' => $userId,
            ':application_id' => $applicationId,
            ':content' => $content,
            ':rating' => $rating
        ]);
    }

    public function findByApplication(int $applicationId): ?array
    {
        $query = "
            SELECT id, user_id, application_id, content, rating, status, moderation_note, created_at, moderated_at
            FROM {$this->tableName}
            WHERE application_id = :application_id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':application_id' => $applicationId]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        return $review ?: null;
    }

    public function getUserReviews(int $userId): array
    {
        $query = "
            SELECT
                r.id,
                r.application_id,
                r.content,
                r.rating,
                r.status,
                r.moderation_note,
                r.created_at,
                r.moderated_at,
                c.name AS course_name
            FROM {$this->tableName} r
            INNER JOIN applications a ON a.id = r.application_id
            INNER JOIN courses c ON c.id = a.course_id
            WHERE r.user_id = :user_id
            ORDER BY r.created_at DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPublicReviews(int $limit = 0): array
    {
        $query = "
            SELECT
                r.id,
                r.content,
                r.rating,
                r.created_at,
                u.full_name,
                c.name AS course_name
            FROM {$this->tableName} r
            INNER JOIN users u ON u.id = r.user_id
            INNER JOIN applications a ON a.id = r.application_id
            INNER JOIN courses c ON c.id = a.course_id
            WHERE r.status = 'approved'
            ORDER BY r.created_at DESC
        ";

        if ($limit > 0) {
            $query .= ' LIMIT :limit';
        }

        $stmt = $this->conn->prepare($query);
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingReviews(): array
    {
        $query = "
            SELECT
                r.id,
                r.application_id,
                r.content,
                r.rating,
                r.created_at,
                u.full_name,
                u.username,
                c.name AS course_name
            FROM {$this->tableName} r
            INNER JOIN users u ON u.id = r.user_id
            INNER JOIN applications a ON a.id = r.application_id
            INNER JOIN courses c ON c.id = a.course_id
            WHERE r.status = 'pending'
            ORDER BY r.created_at ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function moderate(int $reviewId, string $status, ?string $moderationNote = null): bool
    {
        $query = "
            UPDATE {$this->tableName}
            SET status = :status,
                moderation_note = :moderation_note,
                moderated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':status' => $status,
            ':moderation_note' => $moderationNote,
            ':id' => $reviewId
        ]);
    }

    private function ensureModerationSchema(): void
    {
        $columns = $this->conn->query("SHOW COLUMNS FROM {$this->tableName}")->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($columns)) {
            return;
        }

        if (!in_array('status', $columns, true)) {
            $this->conn->exec("ALTER TABLE {$this->tableName} ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        }

        if (!in_array('moderation_note', $columns, true)) {
            $this->conn->exec("ALTER TABLE {$this->tableName} ADD COLUMN moderation_note VARCHAR(255) NULL");
        }

        if (!in_array('moderated_at', $columns, true)) {
            $this->conn->exec("ALTER TABLE {$this->tableName} ADD COLUMN moderated_at TIMESTAMP NULL DEFAULT NULL");
        }
    }
}
