<?php
// update_db_groups.php
ob_start();

$db_path = __DIR__ . '/db/fynn_space.db';

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columns = $db->query("PRAGMA table_info(product_subcategories)")->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('group_name', $columns)) {
        echo "Adding 'group_name' column...";
        $db->exec("ALTER TABLE product_subcategories ADD COLUMN group_name TEXT DEFAULT 'General'");
        echo "Done.";
    } else {
        echo "Column 'group_name' already exists.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
