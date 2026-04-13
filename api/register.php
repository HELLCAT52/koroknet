<?php

require_once __DIR__ . '/common/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

initApi(['POST']);
enforceRateLimit('register', 40, 300);

try {
    $data = getJsonInput();

    $username = sanitizeString($data['username'] ?? '', 50);
    $password = (string)($data['password'] ?? '');
    $fullName = sanitizeString($data['full_name'] ?? '', 100);
    $phone = sanitizeString($data['phone'] ?? '', 20);
    $email = sanitizeString($data['email'] ?? '', 100);

    $fieldErrors = [];

    if ($username === '') {
        $fieldErrors['username'] = 'Введите логин';
    } elseif (!preg_match('/^[A-Za-z0-9_\-.]{6,50}$/', $username)) {
        $fieldErrors['username'] = 'Логин должен содержать минимум 6 символов (латиница/цифры/._-)';
    }

    if ($password === '') {
        $fieldErrors['password'] = 'Введите пароль';
    } elseif (strlen($password) < 8) {
        $fieldErrors['password'] = 'Пароль должен содержать минимум 8 символов';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $fieldErrors['password'] = 'Пароль должен содержать хотя бы одну букву и одну цифру';
    }

    if ($fullName === '') {
        $fieldErrors['full_name'] = 'Введите ФИО';
    } elseif (!preg_match('/^[\p{L}\s\-]{2,100}$/u', $fullName)) {
        $fieldErrors['full_name'] = 'ФИО может содержать только буквы, пробелы и дефис';
    }

    if ($phone === '') {
        $fieldErrors['phone'] = 'Введите телефон';
    } elseif (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
        $fieldErrors['phone'] = 'Телефон должен быть в формате 8(XXX)XXX-XX-XX';
    }

    if ($email === '') {
        $fieldErrors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Введите корректный email';
    }

    if (!empty($fieldErrors)) {
        buildValidationError($fieldErrors);
    }

    $db = (new Database())->getConnection();
    $user = new User($db);
    $user->username = $username;
    $user->password = $password;
    $user->full_name = $fullName;
    $user->phone = $phone;
    $user->email = $email;

    $registerResult = $user->register();
    if (!$registerResult['success']) {
        $response = [
            'success' => false,
            'message' => $registerResult['message']
        ];

        if (!empty($registerResult['field'])) {
            $response['field_errors'] = [
                $registerResult['field'] => $registerResult['message']
            ];
        }

        jsonResponse(409, $response);
    }

    jsonResponse(201, [
        'success' => true,
        'message' => 'Пользователь успешно зарегистрирован'
    ]);
} catch (Throwable $exception) {
    error_log('Register API error: ' . $exception->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
