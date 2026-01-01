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
    $db->exec("PRAGMA foreign_keys = ON;");

    // Create users table if not exists (only runs once)
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Check if admin user exists, if not create one
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)")
            ->execute(['admin', $hashed_password]);
    }

    // Create products table
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cate_title TEXT NOT NULL,
        cat_image TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create product_subcategories table
    $db->exec("CREATE TABLE IF NOT EXISTS product_subcategories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        subcat_title TEXT NOT NULL,
        subcat_image TEXT,
        group_name TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    // Add description and description_image columns to products table if they don't exist
    try {
        $db->exec("ALTER TABLE products ADD COLUMN description TEXT");
    } catch (Exception $e) {
        // Column already exists, ignore
    }
    
    try {
        $db->exec("ALTER TABLE products ADD COLUMN description_image TEXT");
    } catch (Exception $e) {
        // Column already exists, ignore
    }

    // Add group_name column to product_subcategories if it doesn't exist
    try {
        $db->exec("ALTER TABLE product_subcategories ADD COLUMN group_name TEXT");
    } catch (Exception $e) {
        // Column already exists, ignore
    }

} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
