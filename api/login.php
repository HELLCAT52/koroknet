<?php

require_once __DIR__ . '/common/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

initApi(['POST']);
enforceRateLimit('login', 15, 60);

try {
    $data = getJsonInput();

    $username = sanitizeString($data['username'] ?? '', 50);
    $password = (string)($data['password'] ?? '');

    $fieldErrors = [];

    if ($username === '') {
        $fieldErrors['username'] = 'Введите логин';
    } elseif (!preg_match('/^[A-Za-z0-9_\-.]{4,50}$/', $username)) {
        $fieldErrors['username'] = 'Логин может содержать только латиницу, цифры, точку, дефис и подчеркивание';
    }

    if ($password === '') {
        $fieldErrors['password'] = 'Введите пароль';
    }

    if (!empty($fieldErrors)) {
        buildValidationError($fieldErrors);
    }

    $db = (new Database())->getConnection();
    $user = new User($db);
    $user->username = $username;
    $user->password = $password;

    $loginResult = $user->login();
    if (!$loginResult['success']) {
        $response = [
            'success' => false,
            'message' => $loginResult['message']
        ];

        if (!empty($loginResult['field'])) {
            $response['field_errors'] = [
                $loginResult['field'] => $loginResult['message']
            ];
        }

        jsonResponse(401, $response);
    }

    jsonResponse(200, [
        'success' => true,
        'message' => 'Вход выполнен успешно',
        'user_id' => $user->id,
        'username' => $user->username,
        'full_name' => $user->full_name,
        'role' => $user->role
    ]);
} catch (Throwable $exception) {
    error_log('Login API error: ' . $exception->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
