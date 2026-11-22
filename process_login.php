<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

if (!hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
    header('Location: login.php?error=session');
    exit();
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($email) || !is_string($password) || $password === '') {
    header('Location: login.php?error=invalid');
    exit();
}

$email = strtolower(trim($email));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?error=invalid');
    exit();
}

try {
    $connection = getDatabaseConnection();
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    header('Location: login.php?error=server');
    exit();
}

$statement = sqlsrv_query(
    $connection,
    'SELECT TOP 1 name, email, password FROM shopusers WHERE email = ?',
    [$email]
);

if ($statement === false) {
    error_log('Login query failed: ' . print_r(sqlsrv_errors(), true));
    sqlsrv_close($connection);
    header('Location: login.php?error=server');
    exit();
}

$user = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($statement);
sqlsrv_close($connection);

if ($user === false) {
    error_log('Login result fetch failed: ' . print_r(sqlsrv_errors(), true));
    header('Location: login.php?error=server');
    exit();
}

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: login.php?error=invalid');
    exit();
}
session_regenerate_id(true);
$_SESSION['user'] = [
    'name' => $user['name'],
    'email' => $user['email']
];
unset($_SESSION['csrf_token']);

header('Location: account.php');
exit();
