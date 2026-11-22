<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/products.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$products = [];
$catalogError = false;
try {
    $connection = getDatabaseConnection();
    $products = getShopProducts($connection);
    sqlsrv_close($connection);
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    if (isset($connection) && is_resource($connection)) {
        sqlsrv_close($connection);
    }
    $catalogError = true;
}

$groupedProducts = [];
foreach ($products as $product) {
    $groupedProducts[$product['category']][] = $product;
}

$cart = $_SESSION['shopping_cart'] ?? [];
$cartCount = 0;
if (is_array($cart)) {
    foreach ($cart as $quantity) {
        if (is_int($quantity) && $quantity > 0) {
            $cartCount += $quantity;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Antiques - Antiques Shop</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; color: #222; }
        header { background: #2c3e50; color: white; padding: 20px; }
        header h1 { margin: 0; }
        main { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .notice { padding: 12px; background: #fff3cd; border: 1px solid #ffecb5; border-radius: 5px; }
        .products { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-top: 20px; }
        .category { margin: 28px 0; }
        .products { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .product { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .price { font-weight: bold; font-size: 1.2em; }
        button, .link-button { display: inline-block; padding: 10px 16px; border: 0; border-radius: 4px; color: white; background: #007bff; text-decoration: none; cursor: pointer; }
        button:disabled { background: #6c757d; cursor: not-allowed; }
        .links { margin-top: 24px; }
        .links a { margin-right: 14px; }
    </style>
</head>
<body>
<header>
    <h1>Antiques Shop</h1>
    <p>Browse our sample antiques</p>
</header>
<main>
    <p class="notice">Demo shop: checkout updates stock but does not take payment or save an order.</p>
    <p><a class="link-button" href="cart.php">View cart (<?= $cartCount ?>)</a></p>
    <?php if ($catalogError): ?>
        <p role="alert">The product catalog could not be loaded.</p>
    <?php elseif (!$groupedProducts): ?>
        <p>No products are currently available.</p>
    <?php else: ?>
        <?php foreach ($groupedProducts as $category => $categoryProducts): ?>
            <section class="category" aria-label="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
                <h2><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="products">
                    <?php foreach ($categoryProducts as $product): ?>
                        <article class="product">
                            <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="price">£<?= number_format($product['price_pence'] / 100, 2) ?></p>
                            <p><?= $product['stock_quantity'] ?> in stock</p>
                            <form method="post" action="cart.php">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" <?= $product['stock_quantity'] < 1 ? 'disabled' : '' ?>>Add to cart</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
    <nav class="links">
        <a href="account.php">Your account</a>
        <?php if (!empty($_SESSION['user']['is_admin'])): ?>
            <a href="manage_products.php">Manage catalog</a>
        <?php endif; ?>
        <a href="index.php">Home</a>
    </nav>
</main>
</body>
</html>
