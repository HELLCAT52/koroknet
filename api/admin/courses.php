<?php

require_once __DIR__ . '/../common/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';

initApi(['GET', 'POST']);

try {
    $db = (new Database())->getConnection();
    $courseModel = new Course($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        jsonResponse(200, [
            'success' => true,
            'data' => $courseModel->getAll()
        ]);
    }

    enforceRateLimit('admin-create-course', 20, 60);

    $data = getJsonInput();
    $name = sanitizeString($data['name'] ?? '', 100);
    $description = sanitizeString($data['description'] ?? '', 2000);

    $fieldErrors = [];

    if ($name === '') {
        $fieldErrors['name'] = 'Введите название курса';
    } elseif (stringLength($name) < 3) {
        $fieldErrors['name'] = 'Название курса должно содержать минимум 3 символа';
    }

    if ($description === '') {
        $fieldErrors['description'] = 'Введите описание курса';
    } elseif (stringLength($description) < 10) {
        $fieldErrors['description'] = 'Описание курса должно содержать минимум 10 символов';
    }

    if (!empty($fieldErrors)) {
        buildValidationError($fieldErrors);
    }

    if ($courseModel->existsByName($name)) {
        jsonResponse(409, [
            'success' => false,
            'message' => 'Курс с таким названием уже существует',
            'field_errors' => ['name' => 'Курс с таким названием уже существует']
        ]);
    }

    if (!$courseModel->create($name, $description)) {
        jsonResponse(500, [
            'success' => false,
            'message' => 'Не удалось добавить курс'
        ]);
    }

    jsonResponse(201, [
        'success' => true,
        'message' => 'Курс успешно добавлен'
    ]);
} catch (Throwable $exception) {
    error_log('Admin courses API error: ' . $exception->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
