<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../models/Application.php';

$database = new Database();
$db = $database->getConnection();

$application = new Application($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $result = $application->getAll();
        echo json_encode([
            "success" => true,
            "data" => $result
        ]);
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
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Недостаточно данных"
            ]);
        }
        break;
}
?>