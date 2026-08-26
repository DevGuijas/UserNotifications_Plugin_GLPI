<?php
require_once __DIR__ . '/../../../inc/includes.php';
use GlpiPlugin\Usernotifications\Manager;
header('Content-Type: application/json; charset=UTF-8');
Session::checkLoginUser();
try {
    $submittedToken = (string) ($_POST['plugin_usernotifications_mark_token'] ?? '');
    $sessionToken = (string) ($_SESSION['plugin_usernotifications_mark_token'] ?? '');
    if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        throw new \RuntimeException('Invalid notification action token.');
    }
    $userId = (int) Session::getLoginUserID();
    $notificationId = (int) ($_POST['id'] ?? 0);
    if ($notificationId > 0) {
        Manager::markAsRead($userId, $notificationId);
    } else {
        Manager::markAllAsRead($userId);
    }
    $nextToken = bin2hex(random_bytes(32));
    $_SESSION['plugin_usernotifications_mark_token'] = $nextToken;
    echo json_encode(['ok' => true, 'mark_token' => $nextToken]);
} catch (\Throwable $exception) {
    Toolbox::logInFile('php-errors', 'usernotifications mark-read: ' . $exception->getMessage() . PHP_EOL);
    http_response_code(400);
    echo json_encode(['ok' => false]);
}