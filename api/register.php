<?php
// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Устанавливаем заголовки
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Получаем абсолютный путь к корню проекта
    $rootPath = realpath(__DIR__ . '/..');
    
    // Формируем пути к файлам
    $configPath = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
    $modelPath = $rootPath . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';
    
    // Проверяем существование файлов
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found: " . $configPath);
    }
    
    if (!file_exists($modelPath)) {
        throw new Exception("Model file not found: " . $modelPath);
    }
    
    // Подключаем файлы
    require_once $configPath;
    require_once $modelPath;
    
    // Подключаемся к базе данных
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Получаем данные
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData);
    
    if (!$data) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid JSON data"
        ]);
        exit();
    }
    
    // Проверяем обязательные поля
    $required = ['username', 'password', 'full_name', 'phone', 'email'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data->$field)) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        echo json_encode([
            "success" => false, 
            "message" => "Missing fields: " . implode(', ', $missing)
        ]);
        exit();
    }
    
    // Создаем пользователя
    $user = new User($db);
    $user->username = trim($data->username);
    $user->password = $data->password;
    $user->full_name = trim($data->full_name);
    $user->phone = trim($data->phone);
    $user->email = trim($data->email);
    
    if($user->register()) {
        echo json_encode([
            "success" => true, 
            "message" => "Пользователь успешно зарегистрирован"
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Пользователь с таким логином или email уже существует"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Ошибка сервера: " . $e->getMessage()
    ]);
}
?>