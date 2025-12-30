<?php
ob_start();
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle Delete Category Request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Category deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting category: " . $e->getMessage();
    }
}

// Handle Delete Subcategory Request
if (isset($_GET['delete_sub_id'])) {
    $delete_sub_id = $_GET['delete_sub_id'];
    $redirect_id = $_GET['parent_id']; 
    try {
        $stmt = $db->prepare("DELETE FROM product_subcategories WHERE id = ?");
        $stmt->execute([$delete_sub_id]);
        header("Location: products-add.php?edit_id=" . $redirect_id . "&msg=Subcategory deleted");
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting subcategory: " . $e->getMessage();
    }
}

// Handle Main Product Form Submission (Add/Edit Category)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_category') {
    $cate_title = $_POST['cate_title'];
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
    
    // File Upload Logic
    $target_dir = "images/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $cat_image = '';

    if (!empty($_FILES["cat_image"]["name"])) {
        $cat_image_name = time() . "_cat_" . basename($_FILES["cat_image"]["name"]);
        $target_file = $target_dir . $cat_image_name;
        if (move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_file)) {
            $cat_image = $cat_image_name;
        }
    } else {
        $cat_image = $_POST['existing_cat_image'] ?? '';
    }

    try {
        if (!empty($product_id)) {
            // Update Existing Product
            $stmt = $db->prepare("UPDATE products SET cate_title = ?, cat_image = ? WHERE id = ?");
            $stmt->execute([$cate_title, $cat_image, $product_id]);
            $message = "Category updated successfully!";
        } else {
            // Add New Product
            $stmt = $db->prepare("INSERT INTO products (cate_title, cat_image) VALUES (?, ?)");
            $stmt->execute([$cate_title, $cat_image]);
            $new_id = $db->lastInsertId();
            header("Location: products-add.php?edit_id=" . $new_id . "&msg=Category added, now add subcategories");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Subcategory Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_subcategory') {
    $parent_id = $_POST['parent_id'];
    $subcat_title = $_POST['subcat_title'];
    
    $target_dir = "images/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $subcat_image = '';
    if (!empty($_FILES["subcat_image"]["name"])) {
        $subcat_image_name = time() . "_sub_" . basename($_FILES["subcat_image"]["name"]);
        $target_file = $target_dir . $subcat_image_name;
        if (move_uploaded_file($_FILES["subcat_image"]["tmp_name"], $target_file)) {
            $subcat_image = $subcat_image_name;
        }
    }

    try {
        $stmt = $db->prepare("INSERT INTO product_subcategories (product_id, subcat_title, subcat_image) VALUES (?, ?, ?)");
        $stmt->execute([$parent_id, $subcat_title, $subcat_image]);
        $message = "Subcategory added successfully!";
        // Refresh to show new subcategory
        header("Location: products-add.php?edit_id=" . $parent_id . "&msg=Subcategory added");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding subcategory: " . $e->getMessage();
    }
}


// Fetch All Categories
$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Product for Edit
$edit_product = null;
$subcategories = [];
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Subcategories
    if ($edit_product) {
        $stmt_sub = $db->prepare("SELECT * FROM product_subcategories WHERE product_id = ? ORDER BY id DESC");
        $stmt_sub->execute([$edit_id]);
        $subcategories = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Manage Products</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <?php include('website-link.php'); ?>

    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #7c4d4dff, #766496ff);
            color: white;
            padding: 20px 0;
            margin-top: 20px;
        }

        .dashboard-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
        }

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .img-preview {
            max-width: 80px;
            max-height: 80px;
            margin-top: 5px;
            border: 1px solid #ddd;
            padding: 2px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <?php
    $index = true;
    include('header.php');
    ?>

    <div class="banner-section">
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="welcome-section">
                <h1>Product Management</h1>
                <div>
                   <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="container mt-5">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Add/Edit Form -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $edit_product ? 'Edit Main Category' : 'Add New Category'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="products-add.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_category">
                            <input type="hidden" name="product_id" value="<?php echo $edit_product['id'] ?? ''; ?>">
                            <input type="hidden" name="existing_cat_image" value="<?php echo $edit_product['cat_image'] ?? ''; ?>">

                            <div class="mb-3">
                                <label class="form-label">Category Title</label>
                                <input type="text" name="cate_title" class="form-control" required value="<?php echo $edit_product['cate_title'] ?? ''; ?>" placeholder="e.g. Polygranite Sheets">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category Image</label>
                                <input type="file" name="cat_image" class="form-control" accept="image/*">
                                <?php if (!empty($edit_product['cat_image'])): ?>
                                    <img src="images/products/<?php echo htmlspecialchars($edit_product['cat_image']); ?>" class="img-preview">
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $edit_product ? 'Update Category' : 'Create Category'; ?></button>
                                <?php if ($edit_product): ?>
                                    <a href="products-add.php" class="btn btn-secondary">Back to Add New</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Subcategory Management (Only visible when editing) -->
                <?php if ($edit_product): ?>
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 text-primary">Add Subcategory to "<?php echo htmlspecialchars($edit_product['cate_title']); ?>"</h6>
                    </div>
                    <div class="card-body">
                        <form action="products-add.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_subcategory">
                            <input type="hidden" name="parent_id" value="<?php echo $edit_product['id']; ?>">

                            <div class="mb-3">
                                <label class="form-label">Subcategory Title</label>
                                <input type="text" name="subcat_title" class="form-control" required placeholder="e.g. Italian Black">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Subcategory Image</label>
                                <input type="file" name="subcat_image" class="form-control" accept="image/*" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-primary">Add Subcategory</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: List -->
            <div class="col-lg-7">
                <?php if ($edit_product): ?>
                    <!-- Subcategory List for Current Product -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Subcategories (<?php echo count($subcategories); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($subcategories) > 0): ?>
                                        <?php foreach ($subcategories as $sub): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($sub['subcat_image'])): ?>
                                                        <img src="images/products/<?php echo htmlspecialchars($sub['subcat_image']); ?>" class="rounded" width="50" height="50" style="object-fit:cover;">
                                                    <?php else: ?>
                                                        <span class="text-muted small">No Img</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($sub['subcat_title']); ?></td>
                                                <td>
                                                    <a href="products-add.php?delete_sub_id=<?php echo $sub['id']; ?>&parent_id=<?php echo $edit_product['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('Delete this subcategory?');">
                                                       <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted">No subcategories added yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- List of Main Categories -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">All Product Categories</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($products) > 0): ?>
                                        <?php foreach ($products as $index => $row): ?>
                                            <tr class="<?php echo ($edit_product && $edit_product['id'] == $row['id']) ? 'table-primary' : ''; ?>">
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($row['cat_image'])): ?>
                                                            <img src="images/products/<?php echo htmlspecialchars($row['cat_image']); ?>" class="rounded me-2" width="40" height="40" style="object-fit:cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                                                <i class="bi bi-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <span class="fw-bold d-block"><?php echo htmlspecialchars($row['cate_title']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="products-add.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary me-1">Manage</a>
                                                    <a href="products-add.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category and ALL subcategories?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">No categories found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <?php include('website-js.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>