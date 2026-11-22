<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/products.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['shopping_cart']) || !is_array($_SESSION['shopping_cart'])) {
    $_SESSION['shopping_cart'] = [];
}

$connection = false;
$products = [];
$databaseError = false;
try {
    $connection = getDatabaseConnection();
    $products = getShopProducts($connection, true);
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    $databaseError = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
        header('Location: cart.php?error=session');
        exit();
    }
    if ($databaseError) {
        header('Location: cart.php?error=server');
        exit();
    }

    $action = $_POST['action'] ?? '';
    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'add' && $productId !== false
        && isset($products[$productId]) && $products[$productId]['is_active']) {
        $quantity = $_SESSION['shopping_cart'][$productId] ?? 0;
        if (!is_int($quantity) || $quantity < 0 || $quantity >= 99
            || $quantity >= $products[$productId]['stock_quantity']) {
            sqlsrv_close($connection);
            header('Location: cart.php?error=stock');
            exit();
        }
        $_SESSION['shopping_cart'][$productId] = $quantity + 1;
        sqlsrv_close($connection);
        header('Location: cart.php?added=1');
        exit();
    }

    if ($action === 'update' && $productId !== false
        && isset($products[$productId]) && $products[$productId]['is_active']) {
        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 99]]
        );
        if ($quantity === false || $quantity > $products[$productId]['stock_quantity']) {
            sqlsrv_close($connection);
            header('Location: cart.php?error=stock');
            exit();
        }
        if ($quantity === 0) {
            unset($_SESSION['shopping_cart'][$productId]);
        } else {
            $_SESSION['shopping_cart'][$productId] = $quantity;
        }
        sqlsrv_close($connection);
        header('Location: cart.php');
        exit();
    }

    if ($action === 'remove' && $productId !== false) {
        unset($_SESSION['shopping_cart'][$productId]);
        sqlsrv_close($connection);
        header('Location: cart.php');
        exit();
    }

    if ($action === 'checkout' && $_SESSION['shopping_cart']) {
        if (!sqlsrv_begin_transaction($connection)) {
            error_log('Checkout transaction could not start: ' . print_r(sqlsrv_errors(), true));
            sqlsrv_close($connection);
            header('Location: cart.php?error=server');
            exit();
        }

        foreach ($_SESSION['shopping_cart'] as $cartProductId => $quantity) {
            $cartProductId = filter_var($cartProductId, FILTER_VALIDATE_INT);
            if ($cartProductId === false || !is_int($quantity) || $quantity < 1 || $quantity > 99) {
                sqlsrv_rollback($connection);
                sqlsrv_close($connection);
                header('Location: cart.php?error=invalid');
                exit();
            }

            $statement = sqlsrv_query(
                $connection,
                'UPDATE dbo.shop_products '
                    . 'SET stock_quantity = stock_quantity - ?, updated_at = SYSUTCDATETIME() '
                    . 'OUTPUT INSERTED.product_id '
                    . 'WHERE product_id = ? AND is_active = 1 AND stock_quantity >= ?',
                [$quantity, $cartProductId, $quantity]
            );
            if ($statement === false) {
                error_log('Stock update failed: ' . print_r(sqlsrv_errors(), true));
                sqlsrv_rollback($connection);
                sqlsrv_close($connection);
                header('Location: cart.php?error=server');
                exit();
            }

            $updatedProduct = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($statement);
            if ($updatedProduct === false) {
                error_log('Stock update result could not be read: ' . print_r(sqlsrv_errors(), true));
                sqlsrv_rollback($connection);
                sqlsrv_close($connection);
                header('Location: cart.php?error=server');
                exit();
            }
            if ($updatedProduct === null) {
                sqlsrv_rollback($connection);
                sqlsrv_close($connection);
                header('Location: cart.php?error=stock');
                exit();
            }
        }

        if (!sqlsrv_commit($connection)) {
            error_log('Checkout transaction could not commit: ' . print_r(sqlsrv_errors(), true));
            sqlsrv_rollback($connection);
            sqlsrv_close($connection);
            header('Location: cart.php?error=server');
            exit();
        }

        sqlsrv_close($connection);
        $_SESSION['shopping_cart'] = [];
        header('Location: cart.php?checkout=1');
        exit();
    }

    sqlsrv_close($connection);
    header('Location: cart.php?error=invalid');
    exit();
}

if (!$databaseError) {
    sqlsrv_close($connection);
}

$cartItems = [];
$totalPence = 0;
$canCheckout = true;
foreach ($_SESSION['shopping_cart'] as $productId => $quantity) {
    $productId = filter_var($productId, FILTER_VALIDATE_INT);
    if ($productId === false || !isset($products[$productId])
        || !is_int($quantity) || $quantity < 1 || $quantity > 99) {
        $canCheckout = false;
        continue;
    }

    $product = $products[$productId];
    $subtotalPence = $product['price_pence'] * $quantity;
    $totalPence += $subtotalPence;
    if (!$product['is_active'] || $product['stock_quantity'] < $quantity) {
        $canCheckout = false;
    }

    $cartItems[] = [
        'product' => $product,
        'quantity' => $quantity,
        'subtotal_pence' => $subtotalPence
    ];
}

$errorMessages = [
    'session' => 'Your session expired. Please try again.',
    'stock' => 'The requested quantity is not available. Check the current stock and update your cart.',
    'invalid' => 'That cart action could not be completed.',
    'server' => 'The cart could not be processed right now.'
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
    <title>Your Cart - Antiques Shop</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; color: #222; }
        header { background: #2c3e50; color: white; padding: 20px; }
        header h1 { margin: 0; }
        main { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .message { padding: 12px; border-radius: 5px; margin: 12px 0; }
        .success { background: #d1e7dd; color: #0f5132; }
        .error { background: #f8d7da; color: #842029; }
        .cart-item { background: white; padding: 16px; margin: 12px 0; border-radius: 6px; }
        button, .link-button { display: inline-block; padding: 9px 14px; border: 0; border-radius: 4px; color: white; background: #007bff; text-decoration: none; cursor: pointer; }
        button:disabled { background: #6c757d; cursor: not-allowed; }
        .remove { background: #6c757d; }
    </style>
</head>
<body>
<header><h1>Your cart</h1></header>
<main>
    <?php if (isset($_GET['added'])): ?>
        <p class="message success">Item added to your cart.</p>
    <?php endif; ?>
    <?php if (isset($_GET['checkout'])): ?>
        <p class="message success">Demo checkout complete. Stock has been updated. No payment was taken and no order was saved.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="message error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($databaseError): ?>
        <p class="message error">The cart could not load product stock.</p>
    <?php elseif (!$cartItems): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <?php foreach ($cartItems as $item): ?>
            <article class="cart-item">
                <h2><?= htmlspecialchars($item['product']['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p>Category: <?= htmlspecialchars($item['product']['category'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Price: £<?= number_format($item['product']['price_pence'] / 100, 2) ?></p>
                <p>In stock: <?= $item['product']['stock_quantity'] ?></p>
                <p>Item total: £<?= number_format($item['subtotal_pence'] / 100, 2) ?></p>
                <?php if (!$item['product']['is_active']): ?>
                    <p>This product is no longer for sale. Remove it from your cart.</p>
                <?php elseif ($item['product']['stock_quantity'] < $item['quantity']): ?>
                    <p>There is not enough stock for the current quantity. Update your cart.</p>
                <?php endif; ?>
                <?php if ($item['product']['is_active']): ?>
                    <form method="post" action="cart.php" style="display: inline-block;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                        <label>
                            Quantity
                            <input type="number" name="quantity" min="0" max="99" value="<?= $item['quantity'] ?>" required>
                        </label>
                        <button type="submit">Update</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="cart.php" style="display: inline-block;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                    <button class="remove" type="submit">Remove</button>
                </form>
            </article>
        <?php endforeach; ?>
        <h2>Total: £<?= number_format($totalPence / 100, 2) ?></h2>
        <form method="post" action="cart.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="checkout">
            <button type="submit" <?= $canCheckout ? '' : 'disabled' ?>>Complete demo checkout</button>
        </form>
    <?php endif; ?>

    <p><a class="link-button" href="shop.php">Continue shopping</a></p>
    <p><a href="account.php">Your account</a> | <a href="index.php">Home</a></p>
</main>
</body>
</html>
