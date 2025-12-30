<?php
$db_path = __DIR__ . '/fynn_space.db';

// Delete old database file if it exists (to reset)
// Uncomment the line below if you want to reset the database
// if (file_exists($db_path)) {
//     unlink($db_path);
// }

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop old users table if exists (fresh start)
    $db->exec("DROP TABLE IF EXISTS users");

    // Create new users table
    $db->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
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
