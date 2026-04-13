<?php

class Application
{
    private PDO $conn;
    private string $tableName = 'applications';

    public int $id = 0;
    public int $user_id = 0;
    public int $course_id = 0;
    public string $start_date = '';
    public string $payment_method = 'cash';
    public string $status = 'new';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function create(): bool
    {
        $normalizedDate = $this->normalizeDate($this->start_date);
        if ($normalizedDate === null) {
            return false;
        }

        $query = "
            INSERT INTO {$this->tableName} (user_id, course_id, start_date, payment_method, status)
            VALUES (:user_id, :course_id, :start_date, :payment_method, 'new')
        ";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':user_id' => $this->user_id,
            ':course_id' => $this->course_id,
            ':start_date' => $normalizedDate,
            ':payment_method' => $this->payment_method
        ]);
    }

    public function getByUser(int $userId): array
    {
        $reviewColumns = $this->reviewColumns();

        $query = "
            SELECT
                a.id,
                a.user_id,
                a.course_id,
                a.start_date,
                a.payment_method,
                a.status,
                a.created_at,
                c.name AS course_name,
                r.id AS review_id,
                r.content AS review_content,
                r.rating AS review_rating,
                {$reviewColumns}
            FROM {$this->tableName} a
            INNER JOIN courses c ON c.id = a.course_id
            LEFT JOIN reviews r ON r.application_id = a.id
            WHERE a.user_id = :user_id
            ORDER BY a.created_at DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['start_date_formatted'] = $this->formatDateForDisplay($row['start_date'] ?? '');
            $row['created_at_formatted'] = $this->formatDateTimeForDisplay($row['created_at'] ?? '');
        }

        return $rows;
    }

    public function getAll(): array
    {
        $query = "
            SELECT
                a.id,
                a.user_id,
                a.course_id,
                a.start_date,
                a.payment_method,
                a.status,
                a.created_at,
                u.full_name,
                u.username,
                c.name AS course_name
            FROM {$this->tableName} a
            INNER JOIN users u ON u.id = a.user_id
            INNER JOIN courses c ON c.id = a.course_id
            ORDER BY a.created_at DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['start_date_formatted'] = $this->formatDateForDisplay($row['start_date'] ?? '');
            $row['created_at_formatted'] = $this->formatDateTimeForDisplay($row['created_at'] ?? '');
        }

        return $rows;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $allowedStatuses = ['new', 'in_progress', 'completed'];
        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }

        $query = "UPDATE {$this->tableName} SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
    }

    private function normalizeDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date) === 1) {
            [$day, $month, $year] = explode('.', $date);
            if (!checkdate((int) $month, (int) $day, (int) $year)) {
                return null;
            }

            return $year . '-' . $month . '-' . $day;
        }

        return null;
    }

    private function formatDateForDisplay(string $date): string
    {
        if ($date === '' || $date === '0000-00-00') {
            return 'Дата не указана';
        }

        try {
            $dateObj = new DateTime($date);
            return $dateObj->format('d.m.Y');
        } catch (Exception $exception) {
            return 'Дата не указана';
        }
    }

    private function formatDateTimeForDisplay(string $datetime): string
    {
        if ($datetime === '') {
            return 'Дата не указана';
        }

        try {
            $dateObj = new DateTime($datetime);
            return $dateObj->format('d.m.Y H:i');
        } catch (Exception $exception) {
            return 'Дата не указана';
        }
    }

    private function reviewColumns(): string
    {
        if (!$this->columnExists('reviews', 'status')) {
            return "'approved' AS review_status, NULL AS review_moderation_note";
        }

        $moderationNotePart = $this->columnExists('reviews', 'moderation_note')
            ? 'r.moderation_note AS review_moderation_note'
            : 'NULL AS review_moderation_note';

        return "r.status AS review_status, {$moderationNotePart}";
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->conn->prepare("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND COLUMN_NAME = :column
            LIMIT 1
        ");
        $stmt->execute([
            ':table' => $table,
            ':column' => $column
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
