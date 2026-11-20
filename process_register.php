<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit();
}

if (!hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
    header('Location: register.php?error=session');
    exit();
}

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($name) || !is_string($email) || !is_string($password)) {
    header('Location: register.php?error=invalid');
    exit();
}

$name = trim($name);
$email = strtolower(trim($email));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    header('Location: register.php?error=invalid');
    exit();
}

try {
    $connection = getDatabaseConnection();
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    header('Location: register.php?error=server');
    exit();
}

$statement = sqlsrv_query($connection, 'SELECT TOP 1 email FROM shopusers WHERE email = ?', [$email]);
if ($statement === false) {
    error_log('Registration lookup failed: ' . print_r(sqlsrv_errors(), true));
    sqlsrv_close($connection);
    header('Location: register.php?error=server');
    exit();
}

$existingUser = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($statement);

if ($existingUser === false) {
    error_log('Registration lookup result fetch failed: ' . print_r(sqlsrv_errors(), true));
    sqlsrv_close($connection);
    header('Location: register.php?error=server');
    exit();
}

if ($existingUser) {
    sqlsrv_close($connection);
    header('Location: register.php?error=duplicate');
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$statement = sqlsrv_query(
    $connection,
    'INSERT INTO shopusers (name, email, password) VALUES (?, ?, ?)',
    [$name, $email, $hashedPassword]
);

if ($statement === false) {
    error_log('Registration insert failed: ' . print_r(sqlsrv_errors(), true));
    sqlsrv_close($connection);
    header('Location: register.php?error=server');
    exit();
}

sqlsrv_free_stmt($statement);
sqlsrv_close($connection);

header('Location: login.php?registered=1');
exit();
