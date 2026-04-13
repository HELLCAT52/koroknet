<?php

require_once __DIR__ . '/../common/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Application.php';

initApi(['GET', 'PUT']);

try {
    $db = (new Database())->getConnection();
    $application = new Application($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $application->getAll();

        jsonResponse(200, [
            'success' => true,
            'data' => $result
        ]);
    }

    enforceRateLimit('admin-update-status', 60, 60);

    $data = getJsonInput();
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $status = sanitizeString($data['status'] ?? '', 30);

    $fieldErrors = [];

    if (!$id || $id < 1) {
        $fieldErrors['id'] = 'Укажите корректный ID заявки';
    }

    if (!in_array($status, ['new', 'in_progress', 'completed'], true)) {
        $fieldErrors['status'] = 'Недопустимый статус';
    }

    if (!empty($fieldErrors)) {
        buildValidationError($fieldErrors);
    }

    if (!$application->updateStatus((int) $id, $status)) {
        jsonResponse(500, [
            'success' => false,
            'message' => 'Не удалось обновить статус'
        ]);
    }

    jsonResponse(200, [
        'success' => true,
        'message' => 'Статус заявки обновлен'
    ]);
} catch (Throwable $exception) {
    error_log('Admin applications API error: ' . $exception->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
