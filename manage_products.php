<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/products.php';

function renderProductFields($product, $categories)
{
    $name = $product['name'] ?? '';
    $description = $product['description'] ?? '';
    $selectedCategory = $product['category'] ?? $categories[0];
    $price = isset($product['price_pence'])
        ? number_format($product['price_pence'] / 100, 2, '.', '')
        : '';
    $stock = $product['stock_quantity'] ?? 0;
    $isActive = $product['is_active'] ?? true;
    ?>
    <div class="field">
        <label>Name <input type="text" name="name" maxlength="150" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required></label>
    </div>
    <div class="field">
        <label>Description <textarea name="description" maxlength="1000"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea></label>
    </div>
    <div class="field">
        <label>Category
            <select name="category" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" <?= $category === $selectedCategory ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="field">
        <label>Price (£) <input type="number" name="price" min="0" max="9999999.99" step="0.01" value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>" required></label>
    </div>
    <div class="field">
        <label>Stock quantity <input type="number" name="stock_quantity" min="0" max="1000000" step="1" value="<?= (int) $stock ?>" required></label>
    </div>
    <div class="field">
        <label>Status
            <select name="is_active">
                <option value="1" <?= $isActive ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= !$isActive ? 'selected' : '' ?>>Inactive</option>
            </select>
        </label>
    </div>
    <?php
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if (empty($_SESSION['user']['is_admin'])) {
    http_response_code(403);
    echo 'You do not have permission to manage products.';
    exit();
}

$categories = getShopCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
        header('Location: manage_products.php?error=session');
        exit();
    }

    $productIdValue = $_POST['product_id'] ?? '';
    $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) && is_string($_POST['description'])
        ? trim($_POST['description'])
        : '';
    $category = $_POST['category'] ?? '';
    $priceText = $_POST['price'] ?? '';
    $stockText = $_POST['stock_quantity'] ?? '';
    $activeText = $_POST['is_active'] ?? '1';

    $priceIsValid = is_string($priceText)
        && preg_match('/\A(?:0|[1-9][0-9]{0,6})(?:\.[0-9]{1,2})?\z/', $priceText) === 1;
    $stock = is_string($stockText)
        ? filter_var(
            $stockText,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 1000000]]
        )
        : false;
    $nameIsValid = preg_match('/\A.{1,150}\z/us', $name) === 1;
    $descriptionIsValid = preg_match('/\A.{0,1000}\z/us', $description) === 1;
    $categoryIsValid = is_string($category) && in_array($category, $categories, true);
    $activeIsValid = is_string($activeText) && in_array($activeText, ['0', '1'], true);
    $productId = $productIdValue === ''
        ? null
        : (is_string($productIdValue)
            ? filter_var($productIdValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false);

    if (!$priceIsValid || $stock === false || !$nameIsValid || !$descriptionIsValid
        || !$categoryIsValid || !$activeIsValid
        || ($productIdValue !== '' && $productId === false)) {
        header('Location: manage_products.php?error=invalid');
        exit();
    }

    $priceParts = explode('.', $priceText);
    $pricePence = ((int) $priceParts[0] * 100)
        + (int) str_pad($priceParts[1] ?? '', 2, '0');

    try {
        $connection = getDatabaseConnection();
    } catch (RuntimeException $exception) {
        error_log($exception->getMessage());
        header('Location: manage_products.php?error=server');
        exit();
    }

    if ($productId === null) {
        $statement = sqlsrv_query(
            $connection,
            'INSERT INTO dbo.shop_products (name, description, category, price_pence, stock_quantity, is_active) '
                . 'VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $description, $category, $pricePence, $stock, (int) $activeText]
        );
    } else {
        $statement = sqlsrv_query(
            $connection,
            'UPDATE dbo.shop_products '
                . 'SET name = ?, description = ?, category = ?, price_pence = ?, stock_quantity = ?, '
                . 'is_active = ?, updated_at = SYSUTCDATETIME() WHERE product_id = ?',
            [$name, $description, $category, $pricePence, $stock, (int) $activeText, $productId]
        );
    }

    if ($statement === false) {
        error_log('Product save failed: ' . print_r(sqlsrv_errors(), true));
        sqlsrv_close($connection);
        header('Location: manage_products.php?error=server');
        exit();
    }

    sqlsrv_free_stmt($statement);
    sqlsrv_close($connection);
    header('Location: manage_products.php?saved=1');
    exit();
}

$products = [];
$catalogError = false;
try {
    $connection = getDatabaseConnection();
    $products = getShopProducts($connection, true);
    sqlsrv_close($connection);
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    if (isset($connection) && is_resource($connection)) {
        sqlsrv_close($connection);
    }
    $catalogError = true;
}

$errorMessages = [
    'session' => 'Your session expired. Please try again.',
    'invalid' => 'Check the product details. Price, stock, category, or text is invalid.',
    'server' => 'The product could not be saved or loaded.'
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
    <title>Manage Products - Antiques Shop</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; color: #222; }
        header { background: #2c3e50; color: white; padding: 20px; }
        header h1 { margin: 0; }
        main { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .product-form { background: white; padding: 20px; margin: 18px 0; border-radius: 8px; }
        .field { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 4px; }
        input, textarea, select { box-sizing: border-box; max-width: 100%; padding: 8px; }
        input[type="text"], input[type="number"], textarea, select { width: 100%; }
        textarea { min-height: 70px; }
        button { padding: 10px 16px; border: 0; border-radius: 4px; color: white; background: #007bff; cursor: pointer; }
        .error { padding: 12px; background: #f8d7da; color: #842029; border-radius: 5px; }
        .success { padding: 12px; background: #d1e7dd; color: #0f5132; border-radius: 5px; }
    </style>
</head>
<body>
<header><h1>Manage product catalog</h1></header>
<main>
    <p><a href="shop.php">View shop</a> | <a href="account.php">Your account</a></p>
    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
        <p class="success">Product saved.</p>
    <?php endif; ?>
    <?php if ($catalogError): ?>
        <p class="error">The product catalog could not be loaded.</p>
    <?php else: ?>
        <h2>Add a product</h2>
        <form class="product-form" method="post" action="manage_products.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="product_id" value="">
            <?php renderProductFields(null, $categories); ?>
            <button type="submit">Add product</button>
        </form>

        <h2>Existing products</h2>
        <?php if (!$products): ?>
            <p>No products have been added.</p>
        <?php endif; ?>
        <?php foreach ($products as $product): ?>
            <form class="product-form" method="post" action="manage_products.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <?php renderProductFields($product, $categories); ?>
                <button type="submit">Save changes</button>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
