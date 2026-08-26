<?php
require_once __DIR__ . '/../../../inc/includes.php';
use GlpiPlugin\Usernotifications\Manager;
header('Content-Type: application/json; charset=UTF-8');
Session::checkLoginUser();
try {
    // The notification menu may mark one or many entries during the same page load.
    Session::checkCSRF($_POST, true);
    $userId = (int) Session::getLoginUserID();
    $notificationId = (int) ($_POST['id'] ?? 0);
    if ($notificationId > 0) {
        Manager::markAsRead($userId, $notificationId);
    } else {
        Manager::markAllAsRead($userId);
    }
    echo json_encode(['ok' => true]);
} catch (\Throwable $exception) {
    Toolbox::logInFile('php-errors', 'usernotifications mark-read: ' . $exception->getMessage() . PHP_EOL);
    http_response_code(400);
    echo json_encode(['ok' => false]);
}