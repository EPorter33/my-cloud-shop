<?php
require_once __DIR__ . '/auth.php';

if (isset($_SESSION['user'])) {
    header('Location: account.php');
    exit();
}

$errorMessages = [
    'invalid' => 'Email or password is incorrect.',
    'session' => 'Your session expired. Please try again.',
    'server' => 'Unable to sign in right now. Please try again later.'
];
$errorCode = $_GET['error'] ?? '';
$error = is_string($errorCode) && isset($errorMessages[$errorCode])
    ? $errorMessages[$errorCode]
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Antiques Shop</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-form { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,.1); width: 100%; max-width: 400px; box-sizing: border-box; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: 0; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        .error, .success { padding: 10px; margin: 10px 0; border-radius: 4px; text-align: center; }
        .error { color: #842029; background: #f8d7da; border: 1px solid #f5c2c7; }
        .success { color: #0f5132; background: #d1e7dd; border: 1px solid #badbcc; }
    </style>
</head>
<body>
<main class="login-form">
    <h1>Login</h1>
    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
        <div class="success">Account created. Please sign in.</div>
    <?php endif; ?>
    <form method="post" action="process_login.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" autocomplete="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn" type="submit">Sign in</button>
    </form>
    <p>Need an account? <a href="register.php">Register</a></p>
    <p><a href="index.php">Back to home</a></p>
</main>
</body>
</html>
