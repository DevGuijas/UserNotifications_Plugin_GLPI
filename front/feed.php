<?php
require_once __DIR__ . '/../../../inc/includes.php';
use GlpiPlugin\Usernotifications\Manager;
header('Content-Type: application/json; charset=UTF-8');
Session::checkLoginUser();
try {
    $userId = (int) Session::getLoginUserID();
    $notifications = $userId > 0 ? Manager::getForUser($userId) : [];
    $unread = count(array_filter($notifications, static fn(array $notification): bool => !$notification['is_read']));
    echo json_encode([
        'notifications' => $notifications,
        'unread' => $unread,
        'csrf_token' => Session::getNewCSRFToken(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $exception) {
    Toolbox::logInFile('php-errors', 'usernotifications feed: ' . $exception->getMessage() . PHP_EOL);
    http_response_code(500);
    echo json_encode(['notifications' => [], 'unread' => 0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}