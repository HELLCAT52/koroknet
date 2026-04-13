<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../models/Application.php';

$database = new Database();
$db = $database->getConnection();

$application = new Application($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['user_id'])) {
            $user_id = $_GET['user_id'];
            $result = $application->getByUser($user_id);
            
            // Добавляем информацию об отзывах
            foreach($result as &$app) {
                $reviewQuery = "SELECT content, rating FROM reviews WHERE application_id = :app_id";
                $stmt = $db->prepare($reviewQuery);
                $stmt->bindParam(':app_id', $app['id']);
                $stmt->execute();
                $review = $stmt->fetch(PDO::FETCH_ASSOC);
                if($review) {
                    $app['review'] = $review['content'];
                    $app['rating'] = $review['rating'];
                }
            }
            
            echo json_encode([
                "success" => true,
                "data" => $result
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "User ID required"
            ]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->course_id) && !empty($data->start_date) && !empty($data->payment_method)) {
            $application->user_id = $data->user_id;
            $application->course_id = $data->course_id;
            $application->start_date = $data->start_date;
            $application->payment_method = $data->payment_method;
            
            if($application->create()) {
                echo json_encode([
                    "success" => true,
                    "message" => "Заявка успешно создана"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Ошибка при создании заявки"
                ]);
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Заполните все поля"
            ]);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id) && !empty($data->status)) {
            if($application->updateStatus($data->id, $data->status)) {
                echo json_encode([
                    "success" => true,
                    "message" => "Статус заявки обновлен"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Ошибка при обновлении статуса"
                ]);
            }
        }
        break;
}
?>