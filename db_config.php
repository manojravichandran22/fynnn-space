<?php
$db_folder = __DIR__ . '/db';
$db_path = $db_folder . '/fynn_space.db';

// Create db folder if it doesn't exist
if (!is_dir($db_folder)) {
    mkdir($db_folder, 0755, true);
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop old users table if exists (fresh start)
    $db->exec("DROP TABLE IF EXISTS users");

    // Create new users table (without AUTOINCREMENT - no sqlite_sequence)
    $db->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert admin user with hashed password
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);

    $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)")
        ->execute(['admin', $hashed_password]);
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
