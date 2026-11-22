<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antiques Shop</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5em;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .welcome-text {
            text-align: center;
            font-size: 1.2em;
            color: #555;
            margin-top: 50px;
        }

        .signup-link {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            margin-top: 20px;
        }

        .signup-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="container">
        <h1>Antiques Shop</h1>
        <p>Your one-stop destination for amazing products</p>
    </div>
</div>

<div class="container">
    <div class="welcome-text">
        <h2>Welcome to my Antiques Shop</h2>
        <p>Join our community today to access exclusive deals and features!</p>

        <?php if (isset($_SESSION['user'])): ?>
            <p>Welcome, <?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?>.</p>
            <a href="account.php" class="signup-link">Your Account</a>
        <?php else: ?>
            <a href="login.php" class="signup-link">Login</a>
            <a href="register.php" class="signup-link">Create Your Account</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
