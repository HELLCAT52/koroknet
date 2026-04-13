<?php

function initApi(array $allowedMethods): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods) . ', OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods, true)) {
        jsonResponse(405, [
            'success' => false,
            'message' => 'Метод не поддерживается'
        ]);
    }
}

function jsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'Некорректный JSON'
        ]);
    }

    return $decoded;
}

function sanitizeString(?string $value, int $maxLength = 255): string
{
    $value = trim((string) $value);
    $value = strip_tags($value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';

    if (stringLength($value) > $maxLength) {
        return stringSlice($value, $maxLength);
    }

    return $value;
}

function stringLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function stringSlice(string $value, int $maxLength): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function validateDmyDate(string $date): bool
{
    if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
        return false;
    }

    [$day, $month, $year] = array_map('intval', explode('.', $date));
    return checkdate($month, $day, $year);
}

function convertDmyToYmd(string $date): string
{
    [$day, $month, $year] = explode('.', $date);
    return $year . '-' . $month . '-' . $day;
}

function getClientIp(): string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($headers as $header) {
        if (empty($_SERVER[$header])) {
            continue;
        }

        $ipList = explode(',', (string) $_SERVER[$header]);
        $ip = trim($ipList[0]);

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }

    return 'unknown';
}

function enforceRateLimit(string $scope, int $maxRequests, int $windowSeconds): void
{
    $ip = getClientIp();
    $bucketKey = $scope . ':' . $ip;
    $storageFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'koroki_rate_limits.json';

    $storageHandle = fopen($storageFile, 'c+');
    if ($storageHandle === false) {
        return;
    }

    try {
        if (!flock($storageHandle, LOCK_EX)) {
            return;
        }

        $currentTime = time();
        $content = stream_get_contents($storageHandle);
        $records = [];

        if (is_string($content) && $content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $records = $decoded;
            }
        }

        foreach ($records as $key => $record) {
            if (!is_array($record) || !isset($record['timestamps'])) {
                unset($records[$key]);
                continue;
            }

            $filtered = array_values(array_filter(
                $record['timestamps'],
                static fn ($timestamp): bool => is_int($timestamp) && ($timestamp > $currentTime - $windowSeconds)
            ));

            if (empty($filtered)) {
                unset($records[$key]);
            } else {
                $records[$key]['timestamps'] = $filtered;
            }
        }

        $bucket = $records[$bucketKey]['timestamps'] ?? [];
        $bucket = array_values(array_filter(
            $bucket,
            static fn ($timestamp): bool => is_int($timestamp) && ($timestamp > $currentTime - $windowSeconds)
        ));

        if (count($bucket) >= $maxRequests) {
            jsonResponse(429, [
                'success' => false,
                'message' => 'Слишком много запросов. Повторите позже.'
            ]);
        }

        $bucket[] = $currentTime;
        $records[$bucketKey] = ['timestamps' => $bucket];

        rewind($storageHandle);
        ftruncate($storageHandle, 0);
        fwrite($storageHandle, json_encode($records));
    } finally {
        flock($storageHandle, LOCK_UN);
        fclose($storageHandle);
    }
}

function buildValidationError(array $fieldErrors): void
{
    jsonResponse(422, [
        'success' => false,
        'message' => 'Ошибка валидации данных',
        'field_errors' => $fieldErrors
    ]);
}
