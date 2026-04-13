<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $full_name;
    public $phone;
    public $email;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register() {
        try {
            // Проверка уникальности логина
            $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $this->username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                return false;
            }
            
            // Проверка уникальности email
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $this->email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                return false;
            }
            
            // Вставка пользователя
            $query = "INSERT INTO " . $this->table_name . "
                      SET username = :username, 
                          password = :password, 
                          full_name = :full_name,
                          phone = :phone, 
                          email = :email, 
                          role = 'user'";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':username', $this->username);
            $stmt->bindParam(':password', $this->password);
            $stmt->bindParam(':full_name', $this->full_name);
            $stmt->bindParam(':phone', $this->phone);
            $stmt->bindParam(':email', $this->email);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("User registration error: " . $e->getMessage());
            return false;
        }
    }

    public function login() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $this->username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if($this->password === $row['password']) {
                    $this->id = $row['id'];
                    $this->full_name = $row['full_name'];
                    $this->role = $row['role'];
                    return true;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("User login error: " . $e->getMessage());
            return false;
        }
    }
}
?>