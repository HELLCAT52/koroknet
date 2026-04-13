<?php

require_once __DIR__ . '/common/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Review.php';

initApi(['GET', 'POST', 'PUT']);

try {
    $db = (new Database())->getConnection();
    $reviewModel = new Review($db);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $scope = sanitizeString($_GET['scope'] ?? '', 30);
        $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
        $latest = filter_input(INPUT_GET, 'latest', FILTER_VALIDATE_INT);

        if ($scope === 'pending') {
            jsonResponse(200, [
                'success' => true,
                'data' => $reviewModel->getPendingReviews()
            ]);
        }

        if ($userId && $userId > 0) {
            jsonResponse(200, [
                'success' => true,
                'data' => $reviewModel->getUserReviews((int) $userId)
            ]);
        }

        $limit = ($latest && $latest > 0 && $latest <= 20) ? (int) $latest : 0;
        jsonResponse(200, [
            'success' => true,
            'data' => $reviewModel->getPublicReviews($limit)
        ]);
    }

    if ($method === 'POST') {
        enforceRateLimit('create-review', 15, 60);

        $data = getJsonInput();
        $applicationId = filter_var($data['application_id'] ?? null, FILTER_VALIDATE_INT);
        $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
        $rating = filter_var($data['rating'] ?? 5, FILTER_VALIDATE_INT);
        $content = sanitizeString($data['content'] ?? '', 1200);

        $fieldErrors = [];

        if (!$applicationId || $applicationId < 1) {
            $fieldErrors['application_id'] = 'Укажите корректную заявку';
        }

        if (!$userId || $userId < 1) {
            $fieldErrors['user_id'] = 'Пользователь не авторизован';
        }

        if (!$rating || $rating < 1 || $rating > 5) {
            $fieldErrors['rating'] = 'Оценка должна быть от 1 до 5';
        }

        if ($content === '') {
            $fieldErrors['content'] = 'Напишите текст отзыва';
        } elseif (stringLength($content) < 20) {
            $fieldErrors['content'] = 'Отзыв должен содержать минимум 20 символов';
        }

        if (!empty($fieldErrors)) {
            buildValidationError($fieldErrors);
        }

        $existingReview = $reviewModel->findByApplication((int) $applicationId);
        if ($existingReview) {
            jsonResponse(409, [
                'success' => false,
                'message' => 'Отзыв по этой заявке уже оставлен'
            ]);
        }

        $appStmt = $db->prepare('SELECT id, user_id, status FROM applications WHERE id = :id LIMIT 1');
        $appStmt->execute([':id' => (int) $applicationId]);
        $application = $appStmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            jsonResponse(404, [
                'success' => false,
                'message' => 'Заявка не найдена'
            ]);
        }

        if ((int) $application['user_id'] !== (int) $userId) {
            jsonResponse(403, [
                'success' => false,
                'message' => 'Вы можете оставить отзыв только по своей заявке'
            ]);
        }

        if ((string) $application['status'] !== 'completed') {
            jsonResponse(422, [
                'success' => false,
                'message' => 'Отзыв можно оставить только после завершения обучения',
                'field_errors' => ['application_id' => 'Обучение еще не завершено']
            ]);
        }

        if (!$reviewModel->create((int) $userId, (int) $applicationId, $content, (int) $rating)) {
            jsonResponse(500, [
                'success' => false,
                'message' => 'Не удалось сохранить отзыв'
            ]);
        }

        jsonResponse(201, [
            'success' => true,
            'message' => 'Отзыв отправлен на модерацию'
        ]);
    }

    if ($method === 'PUT') {
        enforceRateLimit('moderate-review', 50, 60);

        $data = getJsonInput();
        $reviewId = filter_var($data['review_id'] ?? null, FILTER_VALIDATE_INT);
        $status = sanitizeString($data['status'] ?? '', 20);
        $moderationNote = sanitizeString($data['moderation_note'] ?? '', 255);

        $fieldErrors = [];

        if (!$reviewId || $reviewId < 1) {
            $fieldErrors['review_id'] = 'Укажите корректный ID отзыва';
        }

        if (!in_array($status, ['approved', 'rejected'], true)) {
            $fieldErrors['status'] = 'Статус модерации должен быть approved или rejected';
        }

        if (!empty($fieldErrors)) {
            buildValidationError($fieldErrors);
        }

        $note = $moderationNote === '' ? null : $moderationNote;

        if (!$reviewModel->moderate((int) $reviewId, $status, $note)) {
            jsonResponse(500, [
                'success' => false,
                'message' => 'Не удалось обновить статус отзыва'
            ]);
        }

        jsonResponse(200, [
            'success' => true,
            'message' => $status === 'approved' ? 'Отзыв опубликован' : 'Публикация отзыва отклонена'
        ]);
    }
} catch (Throwable $exception) {
    error_log('Reviews API error: ' . $exception->getMessage());
    jsonResponse(500, [
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
