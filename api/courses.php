<?php

require_once __DIR__ . '/common/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Course.php';

initApi(['GET']);

try {
    $db = (new Database())->getConnection();
    $courseModel = new Course($db);

    jsonResponse(200, [
        'success' => true,
        'data' => $courseModel->getAll()
    ]);
} catch (Throwable $exception) {
    error_log('Courses API error: ' . $exception->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
