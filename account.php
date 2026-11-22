<?php
require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$passwordMessages = [
    'success' => 'Your password has been updated.',
    'invalid' => 'Enter a new password of at least 6 characters and make sure both new-password fields match.',
    'current' => 'Your current password is incorrect. No changes were made.',
    'session' => 'Your session expired. Please try again.',
    'server' => 'Unable to update your password right now. Please try again later.'
];
$passwordStatus = $_GET['password'] ?? '';
$passwordMessage = is_string($passwordStatus) && isset($passwordMessages[$passwordStatus])
    ? $passwordMessages[$passwordStatus]
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account - Antiques Shop</title>
</head>
<body>
    <main>
        <h1>Your account</h1>
        <p>Welcome, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>.</p>
        <p><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><a href="shop.php">Browse items</a> | <a href="cart.php">View cart</a></p>
        <?php if (!empty($user['is_admin'])): ?>
            <p><a href="manage_products.php">Manage product catalog</a></p>
        <?php endif; ?>
        <section>
            <h2>Change your password</h2>
            <?php if ($passwordMessage !== ''): ?>
                <p><?= htmlspecialchars($passwordMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="post" action="process_change_password.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <p>
                    <label for="current_password">Current password</label><br>
                    <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
                </p>
                <p>
                    <label for="new_password">New password</label><br>
                    <input id="new_password" type="password" name="new_password" minlength="6" autocomplete="new-password" required>
                </p>
                <p>
                    <label for="confirm_password">Confirm new password</label><br>
                    <input id="confirm_password" type="password" name="confirm_password" minlength="6" autocomplete="new-password" required>
                </p>
                <button type="submit">Update password</button>
            </form>
        </section>
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Log out</button>
        </form>
        <p><a href="index.php">Back to home</a></p>
    </main>
</body>
</html>
