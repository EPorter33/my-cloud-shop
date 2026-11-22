<?php

function getShopCategories()
{
    return ['Books', 'Ceramics', 'Clocks', 'Furniture', 'Other', 'Watches'];
}

function getShopProducts($connection, $includeInactive = false)
{
    $sql = 'SELECT product_id, name, description, category, price_pence, stock_quantity, is_active '
        . 'FROM dbo.shop_products';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY category, name';

    $statement = sqlsrv_query($connection, $sql);
    if ($statement === false) {
        throw new RuntimeException('Product lookup failed: ' . print_r(sqlsrv_errors(), true));
    }

    $products = [];
    while (($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) !== null) {
        if ($row === false) {
            $errors = sqlsrv_errors();
            sqlsrv_free_stmt($statement);
            throw new RuntimeException('Product result fetch failed: ' . print_r($errors, true));
        }

        $productId = (int) $row['product_id'];
        $products[$productId] = [
            'id' => $productId,
            'name' => $row['name'],
            'description' => $row['description'],
            'category' => $row['category'],
            'price_pence' => (int) $row['price_pence'],
            'stock_quantity' => (int) $row['stock_quantity'],
            'is_active' => (bool) $row['is_active']
        ];
    }

    sqlsrv_free_stmt($statement);
    return $products;
}
