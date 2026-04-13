<?php

class Database
{
    private string $host = 'localhost';
    private string $db_name = 'koroki_portal';
    private string $username = 'root';
    private string $password = '';

    public function getConnection(): PDO
    {
        try {
            $conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            $conn->exec('SET NAMES utf8mb4');
            return $conn;
        } catch (PDOException $exception) {
            error_log('Database connection error: ' . $exception->getMessage());
            throw new Exception('РћС€РёР±РєР° РїРѕРґРєР»СЋС‡РµРЅРёСЏ Рє Р±Р°Р·Рµ РґР°РЅРЅС‹С…');
        }
    }
}
