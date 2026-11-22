<?php
require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
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
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Log out</button>
        </form>
        <p><a href="index.php">Back to home</a></p>
    </main>
</body>
</html>
