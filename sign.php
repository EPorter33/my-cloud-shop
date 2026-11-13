<?php

// Simple database connection
$serverName = "tcp:mycardiffmet.database.windows.net,1433";

$connectionOptions = array(
    "Database" => "myDatabase",
    "Uid" => "myadmin",
    "PWD" => "Abcdefgh0!",
    "Encrypt" => 1,
    "TrustServerCertificate" => 0
);

// Process form submission
if ($_POST) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Connect to database
    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if ($conn) {

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $sql = "INSERT INTO shopusers (name, email, password) VALUES (?, ?, ?)";

        $params = array(
            $name,
            $email,
            $hashed_password
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $message = "User registered successfully!";
        } else {
            $message = "Error: " . print_r(sqlsrv_errors(), true);
        
