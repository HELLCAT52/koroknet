<?php
class Application {
    private $conn;
    private $table_name = "applications";

    public $id;
    public $user_id;
    public $course_id;
    public $start_date;
    public $payment_method;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id = :user_id, course_id = :course_id, 
                      start_date = :start_date, payment_method = :payment_method,
                      status = 'new'";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':payment_method', $this->payment_method);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getByUser($user_id) {
        $query = "SELECT a.*, c.name as course_name 
                  FROM " . $this->table_name . " a
                  JOIN courses c ON a.course_id = c.id
                  WHERE a.user_id = :user_id
                  ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT a.*, u.full_name, u.username, c.name as course_name 
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.id
                  JOIN courses c ON a.course_id = c.id
                  ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>