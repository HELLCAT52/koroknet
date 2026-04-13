<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->application_id) && !empty($data->content)) {
    // Проверяем, существует ли уже отзыв
    $checkQuery = "SELECT id FROM reviews WHERE application_id = :app_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':app_id', $data->application_id);
    $checkStmt->execute();
    
    if($checkStmt->rowCount() > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Отзыв для этой заявки уже оставлен"
        ]);
        exit;
    }
    
    $query = "INSERT INTO reviews (user_id, application_id, content, rating) 
              SELECT user_id, :app_id, :content, :rating 
              FROM applications WHERE id = :app_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':app_id', $data->application_id);
    $stmt->bindParam(':content', $data->content);
    $stmt->bindParam(':rating', $data->rating);
    
    if($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Отзыв успешно добавлен"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Ошибка при добавлении отзыва"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Заполните все поля"
    ]);
}
?>