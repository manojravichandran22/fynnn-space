<?php
ob_start();
session_start();

include('db_config.php');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username and password are required!';
            header('Location: login.php');
            exit();
        }

        // Query user from database
        $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to products-add page
            header('Location: products-add.php');
            exit();
        } else {
            $_SESSION['error'] = 'Invalid username or password!';
            header('Location: login.php');
            exit();
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header('Location: login.php');
    exit();
}