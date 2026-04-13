<?php

class User
{
    private PDO $conn;
    private string $tableName = 'users';

    public int $id = 0;
    public string $username = '';
    public string $password = '';
    public string $full_name = '';
    public string $phone = '';
    public string $email = '';
    public string $role = 'user';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function register(): array
    {
        try {
            if ($this->existsByUsername($this->username)) {
                return ['success' => false, 'field' => 'username', 'message' => 'Логин уже занят'];
            }

            if ($this->existsByEmail($this->email)) {
                return ['success' => false, 'field' => 'email', 'message' => 'Email уже используется'];
            }

            $passwordHash = password_hash($this->password, PASSWORD_DEFAULT);

            $query = "
                INSERT INTO {$this->tableName} (username, password, full_name, phone, email, role)
                VALUES (:username, :password, :full_name, :phone, :email, 'user')
            ";

            $stmt = $this->conn->prepare($query);
            $ok = $stmt->execute([
                ':username' => $this->username,
                ':password' => $passwordHash,
                ':full_name' => $this->full_name,
                ':phone' => $this->phone,
                ':email' => $this->email
            ]);

            if (!$ok) {
                return ['success' => false, 'field' => null, 'message' => 'Не удалось создать пользователя'];
            }

            $this->id = (int) $this->conn->lastInsertId();
            return ['success' => true, 'field' => null, 'message' => 'Пользователь зарегистрирован'];
        } catch (PDOException $exception) {
            error_log('User register error: ' . $exception->getMessage());
            return ['success' => false, 'field' => null, 'message' => 'Ошибка сервера'];
        }
    }

    public function login(): array
    {
        try {
            $query = "SELECT id, username, password, full_name, role FROM {$this->tableName} WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':username' => $this->username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'field' => 'username', 'message' => 'Пользователь с таким логином не найден'];
            }

            $passwordFromDb = (string) $user['password'];
            $validPassword = false;

            if (password_get_info($passwordFromDb)['algo'] !== null) {
                $validPassword = password_verify($this->password, $passwordFromDb);
            } else {
                $validPassword = hash_equals($passwordFromDb, $this->password);

                if ($validPassword) {
                    $newHash = password_hash($this->password, PASSWORD_DEFAULT);
                    $updateStmt = $this->conn->prepare("UPDATE {$this->tableName} SET password = :password WHERE id = :id");
                    $updateStmt->execute([
                        ':password' => $newHash,
                        ':id' => (int) $user['id']
                    ]);
                }
            }

            if (!$validPassword) {
                return ['success' => false, 'field' => 'password', 'message' => 'Введен неверный пароль'];
            }

            $this->id = (int) $user['id'];
            $this->username = (string) $user['username'];
            $this->full_name = (string) $user['full_name'];
            $this->role = (string) $user['role'];

            return ['success' => true, 'field' => null, 'message' => 'Вход выполнен'];
        } catch (PDOException $exception) {
            error_log('User login error: ' . $exception->getMessage());
            return ['success' => false, 'field' => null, 'message' => 'Ошибка сервера'];
        }
    }

    private function existsByUsername(string $username): bool
    {
        $query = "SELECT id FROM {$this->tableName} WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':username' => $username]);

        return (bool) $stmt->fetchColumn();
    }

    private function existsByEmail(string $email): bool
    {
        $query = "SELECT id FROM {$this->tableName} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':email' => $email]);

        return (bool) $stmt->fetchColumn();
    }
}
