<?php
session_start();

$clientId = 'ab333ec7-3b98-445e-8e14-1e83e79f5fea';
$tenantId = 'd4aa62cd-8f9e-440b-a35a-8f3aefcb6411';

$redirectUri = 'https://cloud-cart-e8etarhfe0ejg2g5.germanywestcentral-01.azurewebsites.net/auth/callback.php';

$scope = urlencode('openid profile email User.Read');

$authUrl =
    "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/authorize" .
    "?client_id=" . urlencode($clientId) .
    "&response_type=code" .
    "&redirect_uri=" . urlencode($redirectUri) .
    "&response_mode=query" .
    "&scope=" . $scope;

header("Location: " . $authUrl);
exit;