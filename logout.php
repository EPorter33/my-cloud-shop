<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
    header('Location: account.php');
    exit();
}

$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
session_destroy();

header('Location: index.php');
exit();
