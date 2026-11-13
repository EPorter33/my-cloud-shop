<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration for Azure MySQL
$host = "apptestdb.mysql.database.azure.com";
$username = "AdminTest";
$password = "Abcdefgh0!";
$database = "mydatabase";

try {

    // PDO connection with SSL for Azure MySQL
    $dsn = "mysql:host=$host;port=3306;dbname=$database;charset=utf8";

    $options = [
        PDO::MYSQL_ATTR_SSL_CA => '/home/site/wwwroot/BaltimoreCyberTrustRoot.crt.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    $conn = new PDO($dsn, $username, $password, $options);

    // Get ALL registered users
    $sql = "SELECT name, email, password, created_at FROM shopusers ORDER BY created_at DESC";
    $stmt = $conn->query($sql);

    // Fetch all users into an array
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f0f8f0;
        }

        .success-message {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-icon {
            color: #4CAF50;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .all-users {
            background: #f9f9f9;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: left;
            max-height: 400px;
            overflow-y: auto;
        }

        .all-users h3 {
            color: #333;
            margin-top: 0;
            text-align: center;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .user-table th,
        .user-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .user-table th {
            background
