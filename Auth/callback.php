<?php

require_once __DIR__ . '/../auth.php';

if (!isset($_GET['code'])) {
    header('Location: /login.php');
    exit();
}

/*
 * Temporary Microsoft user session
 * We will improve this later to read the real
 * Microsoft name and email from Graph API.
 */
$_SESSION['user'] = [
    'name' => 'Microsoft User',
    'email' => 'microsoft@example.com'
];

header('Location: /shop.php');
exit();