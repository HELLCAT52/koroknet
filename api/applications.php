<?php

require_once __DIR__ . '/common/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Application.php';
require_once __DIR__ . '/../models/Course.php';

initApi(['GET', 'POST', 'PUT']);

try {
    $db = (new Database())->getConnection();
    $application = new Application($db);
    $courseModel = new Course($db);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
        if (!$userId || $userId < 1) {
            jsonResponse(422, [
                'success' => false,
                'message' => 'Некорректный user_id',
                'field_errors' => ['user_id' => 'Укажите корректный user_id']
            ]);
        }

        $result = $application->getByUser((int) $userId);
        jsonResponse(200, [
            'success' => true,
            'data' => $result
        ]);
    }

    if ($method === 'POST') {
        enforceRateLimit('create-application', 25, 60);

        $data = getJsonInput();
        $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
        $courseId = filter_var($data['course_id'] ?? null, FILTER_VALIDATE_INT);
        $startDate = sanitizeString($data['start_date'] ?? '', 10);
        $paymentMethod = sanitizeString($data['payment_method'] ?? '', 20);

        $fieldErrors = [];

        if (!$userId || $userId < 1) {
            $fieldErrors['user_id'] = 'Пользователь не авторизован';
        }

        if (!$courseId || $courseId < 1) {
            $fieldErrors['course_id'] = 'Выберите курс';
        } elseif (!$courseModel->existsById((int) $courseId)) {
            $fieldErrors['course_id'] = 'Выбранный курс не найден';
        }

        if ($startDate === '') {
            $fieldErrors['start_date'] = 'Выберите дату начала обучения';
        } else {
            $normalizedDate = $startDate;
            if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $startDate) === 1) {
                $normalizedDate = convertDmyToYmd($startDate);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalizedDate) !== 1) {
                $fieldErrors['start_date'] = 'Дата должна быть в формате ДД.ММ.ГГГГ или YYYY-MM-DD';
            } elseif ($normalizedDate < date('Y-m-d')) {
                $fieldErrors['start_date'] = 'Дата начала не может быть в прошлом';
            }
        }

        $allowedPaymentMethods = ['cash', 'transfer'];
        if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
            $fieldErrors['payment_method'] = 'Выберите способ оплаты';
        }

        if (!empty($fieldErrors)) {
            buildValidationError($fieldErrors);
        }

        $application->user_id = (int) $userId;
        $application->course_id = (int) $courseId;
        $application->start_date = $startDate;
        $application->payment_method = $paymentMethod;

        if (!$application->create()) {
            jsonResponse(500, [
                'success' => false,
                'message' => 'Не удалось создать заявку'
            ]);
        }

        jsonResponse(201, [
            'success' => true,
            'message' => 'Заявка успешно создана'
        ]);
    }

    if ($method === 'PUT') {
        enforceRateLimit('update-application-status', 40, 60);

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
                'message' => 'Не удалось обновить статус заявки'
            ]);
        }

        jsonResponse(200, [
            'success' => true,
            'message' => 'Статус заявки обновлен'
        ]);
    }
} catch (Throwable $exception) {
    error_log('Applications API error: ' . $exception->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
