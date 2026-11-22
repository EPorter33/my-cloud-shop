<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account.php');
    exit();
}

if (!hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
    header('Location: account.php?password=session');
    exit();
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmPassword)
    || $currentPassword === '' || strlen($newPassword) < 6
    || !hash_equals($newPassword, $confirmPassword)) {
    header('Location: account.php?password=invalid');
    exit();
}

try {
    $connection = getDatabaseConnection();
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    header('Location: account.php?password=server');
    exit();
}

$email = $_SESSION['user']['email'];
$statement = sqlsrv_query(
    $connection,
    'SELECT TOP 1 password FROM shopusers WHERE email = ?',
    [$email]
);

if ($statement === false) {
    error_log('Current password lookup failed: ' . print_r(sqlsrv_errors(), true));
    sqlsrv_close($connection);
    header('Location: account.php?password=server');
    exit();
}

$user = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($statement);
if ($user === false) {
    error_log('Current password lookup result failed: ' . print_r(sqlsrv_errors(), true));
    sqlsrv_close($connection);
    header('Location: account.php?password=server');
    exit();
}

if (!$user || !password_verify($currentPassword, $user['password'])) {
    sqlsrv_close($connection);
    header('Location: account.php?password=current');
    exit();
}

$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
$statement = sqlsrv_query(
    $connection,
    'UPDATE shopusers SET password = ? WHERE email = ? AND password = ?',
    [$newPasswordHash, $email, $user['password']]
);

if ($statement === false) {
    error_log('Password update failed: ' . print_r(sqlsrv_errors(), true));
    sqlsrv_close($connection);
    header('Location: account.php?password=server');
    exit();
}

$updatedRows = sqlsrv_rows_affected($statement);
sqlsrv_free_stmt($statement);
sqlsrv_close($connection);

if ($updatedRows === false) {
    error_log('Password update row count failed: ' . print_r(sqlsrv_errors(), true));
    header('Location: account.php?password=server');
    exit();
}
if ($updatedRows !== 1) {
    header('Location: account.php?password=current');
    exit();
}

session_regenerate_id(true);
unset($_SESSION['csrf_token']);

header('Location: account.php?password=success');
exit();
