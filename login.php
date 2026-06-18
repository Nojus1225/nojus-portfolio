<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === 'password') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        header('Location: /');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/global.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .login-card h1 {
            margin-bottom: 8px;
            font-size: 28px;
        }
        .login-card p {
            color: var(--muted);
            margin-bottom: 32px;
        }
        .login-card input {
            width: 100%;
            padding: 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 16px;
            margin-bottom: 24px;
            outline: none;
        }
        .login-card input:focus {
            border-color: var(--primary);
        }
        .login-card button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .login-card button:hover {
            background: var(--primary-h);
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <h1>🔐 Admin Login</h1>
        <p>Enter your password to continue</p>
        <form method="post">
            <input type="password" name="password" placeholder="Password" required autofocus>
            <button type="submit">Login →</button>
        </form>
    </div>
</div>
</body>
</html>
