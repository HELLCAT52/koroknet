<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $rootPath = realpath(__DIR__ . '/..');
    $configPath = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
    $modelPath = $rootPath . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found: " . $configPath);
    }
    
    require_once $configPath;
    require_once $modelPath;
    
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->username) && !empty($data->password)) {
        $user->username = $data->username;
        $user->password = $data->password;
        
        if($user->login()) {
            echo json_encode([
                "success" => true,
                "message" => "Вход выполнен успешно",
                "user_id" => $user->id,
                "username" => $user->username,
                "full_name" => $user->full_name,
                "role" => $user->role
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Неверный логин или пароль"
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Заполните все поля"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Ошибка сервера: " . $e->getMessage()
    ]);
}
?>