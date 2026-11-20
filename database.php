<?php

function getDatabaseConnection()
{
    if (!function_exists('sqlsrv_connect')) {
        throw new RuntimeException('The PHP SQLSRV extension is not installed.');
    }

    $server = getenv('SHOP_DB_SERVER');
    $database = getenv('SHOP_DB_DATABASE');
    $username = getenv('SHOP_DB_USERNAME');
    $password = getenv('SHOP_DB_PASSWORD');

    if ($server === false || $server === ''
        || $database === false || $database === ''
        || $username === false || $username === ''
        || $password === false || $password === '') {
        throw new RuntimeException('One or more SHOP_DB_* environment variables are not configured.');
    }

    $connection = sqlsrv_connect($server, [
        'Database' => $database,
        'Uid' => $username,
        'PWD' => $password,
        'Encrypt' => true,
        'TrustServerCertificate' => false
    ]);

    if ($connection === false) {
        throw new RuntimeException('SQL Server connection failed: ' . print_r(sqlsrv_errors(), true));
    }

    return $connection;
}
