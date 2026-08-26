<?php
require_once __DIR__ . '/../../../inc/includes.php';
use GlpiPlugin\Usernotifications\Manager;
header('Content-Type: application/json; charset=UTF-8');
Session::checkLoginUser();
try {
    Session::checkCSRF($_POST);
    Manager::markAllAsRead((int) Session::getLoginUserID());
    echo json_encode(['ok' => true]);
} catch (\Throwable $exception) {
    Toolbox::logInFile('php-errors', 'usernotifications mark-read: ' . $exception->getMessage() . PHP_EOL);
    http_response_code(400);
    echo json_encode(['ok' => false]);
}