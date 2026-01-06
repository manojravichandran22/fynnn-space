<?php
ob_start();
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

$message = '';
$error = '';

// --- AJAX HANDLERS ---

// Fetch Product Details (for View/Edit)
if (isset($_GET['fetch_id'])) {
    header('Content-Type: application/json');
    $fetch_id = $_GET['fetch_id'];

    try {
        // Fetch Category
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$fetch_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch Subcategories
        $stmt_sub = $db->prepare("SELECT * FROM product_subcategories WHERE product_id = ? ORDER BY id DESC");
        $stmt_sub->execute([$fetch_id]);
        $subcategories = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Unique Groups for THIS Product
        $stmt_grps = $db->prepare("SELECT DISTINCT group_name FROM product_subcategories WHERE product_id = ? AND group_name IS NOT NULL AND group_name != '' ORDER BY group_name ASC");
        $stmt_grps->execute([$fetch_id]);
        $unique_groups = $stmt_grps->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['status' => 'success', 'product' => $product, 'subcategories' => $subcategories, 'unique_groups' => $unique_groups]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit(); // Stop executing the rest of the page
}

// --- FORM HANDLERS ---

// Handle Delete Category
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $db->beginTransaction();

        // 1. Fetch images from Products table (category and description images)
        $stmt_prod = $db->prepare("SELECT cat_image, description_image FROM products WHERE id = ?");
        $stmt_prod->execute([$delete_id]);
        $prod_row = $stmt_prod->fetch(PDO::FETCH_ASSOC);

        // 2. Fetch images from Subcategories table
        $stmt_sub = $db->prepare("SELECT subcat_image FROM product_subcategories WHERE product_id = ?");
        $stmt_sub->execute([$delete_id]);
        $sub_images = $stmt_sub->fetchAll(PDO::FETCH_COLUMN);

        // 3. Delete records from database (Cascading delete will handle subcategories if FK enabled, 
        // but explicit delete is safer or we can rely on PRAGMA foreign_keys = ON)
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);

        $db->commit();

        $target_dir = "images/products/";

        // 4. Delete physical files
        if ($prod_row) {
            if (!empty($prod_row['cat_image'])) {
                $file_path = $target_dir . $prod_row['cat_image'];
                if (file_exists($file_path)) unlink($file_path);
            }
            if (!empty($prod_row['description_image'])) {
                $file_path = $target_dir . $prod_row['description_image'];
                if (file_exists($file_path)) unlink($file_path);
            }
        }

        foreach ($sub_images as $img) {
            if (!empty($img)) {
                $file_path = $target_dir . $img;
                if (file_exists($file_path)) unlink($file_path);
            }
        }

        header("Location: products-add?msg=Category and all related data deleted successfully");
        exit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = "Error deleting category: " . $e->getMessage();
    }
}

// Handle Delete Subcategory
if (isset($_GET['delete_sub_id'])) {
    $delete_sub_id = $_GET['delete_sub_id'];
    try {
        // Fetch image before deleting
        $stmt_img = $db->prepare("SELECT subcat_image FROM product_subcategories WHERE id = ?");
        $stmt_img->execute([$delete_sub_id]);
        $row = $stmt_img->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("DELETE FROM product_subcategories WHERE id = ?");
        $stmt->execute([$delete_sub_id]);

        if ($row && !empty($row['subcat_image'])) {
            $file_path = "images/products/" . $row['subcat_image'];
            if (file_exists($file_path)) unlink($file_path);
        }

        header("Location: products-add?msg=Subcategory deleted");
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting subcategory: " . $e->getMessage();
    }
}

// Handle Delete Group (and all subcategories in that group)
if (isset($_GET['delete_group']) && isset($_GET['product_id'])) {
    $group_name = $_GET['delete_group'];
    $product_id = $_GET['product_id'];

    try {
        // Fetch all images in this group before deleting
        $stmt_imgs = $db->prepare("SELECT subcat_image FROM product_subcategories WHERE product_id = ? AND group_name = ?");
        $stmt_imgs->execute([$product_id, $group_name]);
        $images = $stmt_imgs->fetchAll(PDO::FETCH_COLUMN);

        // Delete all subcategories in this group
        $stmt = $db->prepare("DELETE FROM product_subcategories WHERE product_id = ? AND group_name = ?");
        $stmt->execute([$product_id, $group_name]);

        // Delete all images
        $target_dir = "images/products/";
        foreach ($images as $img) {
            if (!empty($img)) {
                $file_path = $target_dir . $img;
                if (file_exists($file_path)) unlink($file_path);
            }
        }

        header("Location: products-add?msg=Group deleted successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting group: " . $e->getMessage();
    }
}

// Add Subcategory to Existing Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_subcategory') {
    $product_id = $_POST['product_id'];
    $subcat_title = $_POST['subcat_title'];
    $group_name = !empty($_POST['subcat_group']) ? $_POST['subcat_group'] : 'General';

    $target_dir = "images/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    try {
        if (!empty($_FILES['subcat_image']['name'])) {
            $subcat_image = time() . "_sub_" . basename($_FILES["subcat_image"]["name"]);
            move_uploaded_file($_FILES["subcat_image"]["tmp_name"], $target_dir . $subcat_image);

            $stmt = $db->prepare("INSERT INTO product_subcategories (product_id, subcat_title, subcat_image, group_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$product_id, $subcat_title, $subcat_image, $group_name]);

            header("Location: products-add?msg=Subcategory added successfully");
            exit();
        } else {
            $error = "Please upload a subcategory image";
        }
    } catch (Exception $e) {
        $error = "Error adding subcategory: " . $e->getMessage();
    }
}

// Update Subcategory
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_subcategory') {
    $subcat_id = $_POST['subcat_id'];
    $product_id = $_POST['product_id'];
    $subcat_title = $_POST['subcat_title'];
    $group_name = !empty($_POST['group_name']) ? $_POST['group_name'] : 'General';
    $existing_image = $_POST['existing_subcat_image'];

    $target_dir = "images/products/";
    $new_image = $existing_image;

    try {
        if (!empty($_FILES['subcat_image']['name'])) {
            $new_image = time() . "_sub_" . basename($_FILES["subcat_image"]["name"]);
            if (move_uploaded_file($_FILES["subcat_image"]["tmp_name"], $target_dir . $new_image)) {
                // Delete old image
                if ($existing_image && file_exists($target_dir . $existing_image)) {
                    unlink($target_dir . $existing_image);
                }
            }
        }

        $stmt = $db->prepare("UPDATE product_subcategories SET subcat_title = ?, subcat_image = ?, group_name = ? WHERE id = ?");
        $stmt->execute([$subcat_title, $new_image, $group_name, $subcat_id]);

        header("Location: products-add?msg=Subcategory updated successfully&product_id=" . $product_id);
        exit();
    } catch (Exception $e) {
        $error = "Error updating subcategory: " . $e->getMessage();
    }
}


// Save New Category (and optional Subcategory)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_product') {
    $cate_title = $_POST['cate_title'];
    $description = $_POST['description'] ?? '';

    // Upload Category Image
    $cat_image = '';
    $description_image = '';
    $target_dir = "images/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (!empty($_FILES["cat_image"]["name"])) {
        $cat_image = time() . "_cat_" . basename($_FILES["cat_image"]["name"]);
        move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_dir . $cat_image);
    }

    if (!empty($_FILES["description_image"]["name"])) {
        $description_image = time() . "_desc_" . basename($_FILES["description_image"]["name"]);
        move_uploaded_file($_FILES["description_image"]["tmp_name"], $target_dir . $description_image);
    }

    try {
        $db->beginTransaction();

        // Insert Category
        $stmt = $db->prepare("INSERT INTO products (cate_title, cat_image, description, description_image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cate_title, $cat_image, $description, $description_image]);
        $product_id = $db->lastInsertId();

        // Check if Subcategory was added
        if (!empty($_POST['subcat_title']) && !empty($_FILES['subcat_image']['name'])) {
            $subcat_title = $_POST['subcat_title'];
            $group_name = !empty($_POST['subcat_group']) ? $_POST['subcat_group'] : 'General';

            $subcat_image = time() . "_sub_" . basename($_FILES["subcat_image"]["name"]);
            move_uploaded_file($_FILES["subcat_image"]["tmp_name"], $target_dir . $subcat_image);

            $stmt_sub = $db->prepare("INSERT INTO product_subcategories (product_id, subcat_title, subcat_image, group_name) VALUES (?, ?, ?, ?)");
            $stmt_sub->execute([$product_id, $subcat_title, $subcat_image, $group_name]);
        }

        $db->commit();
        header("Location: products-add?msg=Product added successfully");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Update Category (Main Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_product') {
    $product_id = $_POST['product_id'];
    $cate_title = $_POST['cate_title'];
    $description = $_POST['description'] ?? '';

    $cat_image = $_POST['existing_cat_image'];
    $description_image = $_POST['existing_description_image'];
    $target_dir = "images/products/";

    if (!empty($_FILES["cat_image"]["name"])) {
        $cat_image = time() . "_cat_" . basename($_FILES["cat_image"]["name"]);
        move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_dir . $cat_image);
    }

    if (!empty($_FILES["description_image"]["name"])) {
        $description_image = time() . "_desc_" . basename($_FILES["description_image"]["name"]);
        move_uploaded_file($_FILES["description_image"]["tmp_name"], $target_dir . $description_image);
    }

    try {
        $stmt = $db->prepare("UPDATE products SET cate_title = ?, cat_image = ?, description = ?, description_image = ? WHERE id = ?");
        $stmt->execute([$cate_title, $cat_image, $description, $description_image, $product_id]);

        // Also handle adding a NEW subcategory from the Edit modal if fields are present
        if (!empty($_POST['new_subcat_title']) && !empty($_FILES['new_subcat_image']['name'])) {
            $subcat_title = $_POST['new_subcat_title'];
            $group_name = !empty($_POST['new_subcat_group']) ? $_POST['new_subcat_group'] : 'General';

            $subcat_image = time() . "_sub_" . basename($_FILES["new_subcat_image"]["name"]);
            move_uploaded_file($_FILES["new_subcat_image"]["tmp_name"], $target_dir . $subcat_image);

            $stmt_sub = $db->prepare("INSERT INTO product_subcategories (product_id, subcat_title, subcat_image, group_name) VALUES (?, ?, ?, ?)");
            $stmt_sub->execute([$product_id, $subcat_title, $subcat_image, $group_name]);
        }

        header("Location: products-add?msg=Category updated successfully");
        exit();
    } catch (Exception $e) {
        $error = "Error updating: " . $e->getMessage();
    }
}


// Fetch All Categories for Display
$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Unique Group Names for Autosuggest
$group_names = [];
try {
    $stmt_grp = $db->query("SELECT DISTINCT group_name FROM product_subcategories WHERE group_name IS NOT NULL AND group_name != '' ORDER BY group_name ASC");
    $group_names = $stmt_grp->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // silently fail or log if needed, autosuggest just won't work
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <?php include('website-link.php'); ?>
    <style>
        .dashboard-header {
            background: url('images/banner1.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
            padding: 50px 0;
            color: white;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .dashboard-header .container-fluid {
            position: relative;
            z-index: 2;
        }

        .img-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: #f8f9fa;
        }

        .subcat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }

        .subcat-item:hover {
            background: #f9f9f9;
        }

        .subcat-item:last-child {
            border-bottom: none;
        }

        .subcat-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 12px;
        }

        .group-badge {
            font-size: 0.75em;
            background: #f0f2f5;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 8px;
            color: #666;
            font-weight: 500;
        }

        /* Premium Table Styling */
        .table-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08) !important;
            overflow: hidden;
        }

        .table thead th {
            background-color: #fcfcfc;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.03em;
            padding: 16px;
            border-bottom: 2px solid #f0f0f0;
            color: #555;
            font-weight: 600;
        }

        .table tbody td {
            padding: 16px;
            border-bottom: 1px solid #f4f4f4;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Theme Color Overrides */
        .btn-primary,
        .bg-primary,
        .dropdown-item.active,
        .dropdown-item:active {
            background-color: #fc9401 !important;
            border-color: #fc9401 !important;
        }

        .btn-outline-primary,
        .btn-outline-info,
        .text-primary {
            color: #ffb84d !important;
        }

        .btn-outline-primary,
        .btn-outline-info {
            border-color: #fc9401 !important;
        }

        .btn-outline-primary:hover,
        .btn-outline-info:hover {
            background-color: #fc9401 !important;
            color: #fff !important;
        }

        .spinner-border.text-primary {
            color: #fc9401 !important;
        }

        .modal-header.bg-primary .modal-title {
            color: white !important;
        }
    </style>
</head>

<body>
    <?php include('header.php'); ?>

    <!-- Banner Wrapper for Scroll Fix -->
    <div class="banner-section">
        <div class="dashboard-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Product List</h1>
                    <div>
                        <span class="me-3 text-white">Welcome, <?php echo htmlspecialchars(strtoupper($_SESSION['username'])); ?></span>
                        <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5 mb-5">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <!-- Optional Filter could go here -->
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg"></i> Add Product
            </button>
        </div>

        <!-- Product Table -->
        <div class="card table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Category Name</th>
                                <th>Created At</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $row): ?>
                                    <tr>
                                        <td>
                                            <?php if ($row['cat_image']): ?>
                                                <img src="images/products/<?php echo htmlspecialchars($row['cat_image']); ?>" class="img-thumb">
                                            <?php else: ?>
                                                <span class="text-muted">No Img</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['cate_title']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-primary me-1 add-subcat-btn" data-id="<?php echo $row['id']; ?>" data-title="<?php echo htmlspecialchars($row['cate_title']); ?>" data-bs-toggle="tooltip" data-bs-title="Add Subcategory"><i class="bi bi-plus-circle"></i></button>
                                            <button class="btn btn-sm btn-outline-info me-1 view-btn" data-id="<?php echo $row['id']; ?>" data-bs-toggle="tooltip" data-bs-title="View Details"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="<?php echo $row['id']; ?>" data-bs-toggle="tooltip" data-bs-title="Edit Product"><i class="bi bi-pencil-square"></i></button>
                                            <a href="products-add?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-msg="Delete this category and all its subcategories?" data-bs-toggle="tooltip" data-bs-title="Delete Product"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <?php include('website-js.php'); ?>

    <!-- ADD MODAL -->
    <!-- ADD PRODUCT MODAL -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">

                <!-- HEADER -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-box-seam me-2"></i> Add New Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <!-- FORM -->
                <form action="products-add" method="POST" enctype="multipart/form-data" style="height:100%;display:flex;flex-direction:column;">
                    <input type="hidden" name="action" value="add_product">

                    <div class="modal-body" style="overflow-y:auto;max-height:55vh;">

                        <!-- CATEGORY CARD -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="bi bi-folder me-1"></i> Product Category
                                </h6>

                                <div class="row align-items-end">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category Title</label>
                                        <input type="text"
                                            name="cate_title"
                                            class="form-control"
                                            placeholder="e.g. Wall Panels"
                                            required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category Image</label>
                                        <input type="file"
                                            name="cat_image"
                                            class="form-control"
                                            accept="image/*"
                                            onchange="previewCatImage(event)">
                                        <img id="catPreview"
                                            class="mt-2 rounded d-none border"
                                            height="70">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description"
                                            class="form-control"
                                            rows="3"
                                            placeholder="Enter product description..."></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Description Image</label>
                                        <input type="file"
                                            name="description_image"
                                            class="form-control"
                                            accept="image/*"
                                            onchange="previewDescImage(event)">
                                        <img id="descPreview"
                                            class="mt-2 rounded d-none border"
                                            height="70">
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer bg-light sticky-modal-footer" style="position:sticky;bottom:0;z-index:10;">
                        <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit"
                            class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i> Save Product
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    <!-- VIEW MODAL -->
    <div class="modal fade " id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg ">
            <div class="modal-content ">
                <div class="modal-header  bg-primary">
                    <h5 class="modal-title " id="viewModalTitle">Product Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <img id="viewCatImage" src="" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                        <div class="col-md-8">
                            <h4 id="viewCatTitle"></h4>
                            <p class="text-muted">Main Category</p>
                        </div>
                    </div>
                    <h5 class="border-bottom pb-2 text-primary">Subcategories / Designs</h5>
                    <div id="viewSubcatList">
                        <!-- Ajax Content -->
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <form action="products-add" method="POST" enctype="multipart/form-data" style="height:100%;display:flex;flex-direction:column;">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" id="editProductId">
                    <input type="hidden" name="existing_cat_image" id="editExistingImage">
                    <input type="hidden" name="existing_description_image" id="editExistingDescImage">

                    <!-- HEADER -->
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square me-2"></i> Edit Product
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body" style="overflow-y:auto;max-height:55vh;">
                        <!-- CATEGORY CARD -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="bi bi-folder me-1"></i> Product Category
                                </h6>

                                <div class="row align-items-end">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category Title</label>
                                        <input type="text" name="cate_title" id="editCatTitle" class="form-control" placeholder="e.g. Wall Panels" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category Image</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="editImagePreview" src="" class="rounded border" height="60" style="object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <input type="file" name="cat_image" class="form-control" accept="image/*" onchange="previewEditCatImage(event)">
                                                <small class="text-muted">Leave empty to keep existing image</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" id="editDescription" class="form-control" rows="3" placeholder="Enter product description..."></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Description Image</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="editDescImagePreview" src="" class="rounded border" height="60" style="object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <input type="file" name="description_image" class="form-control" accept="image/*" onchange="previewEditDescImage(event)">
                                                <small class="text-muted">Leave empty to keep existing image</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer bg-light sticky-modal-footer" style="position:sticky;bottom:0;z-index:10;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD SUBCATEGORY MODAL -->
    <div class="modal fade" id="addSubcatModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <form action="products-add" method="POST" enctype="multipart/form-data" style="height:100%;display:flex;flex-direction:column;">
                    <input type="hidden" name="action" value="add_subcategory">
                    <input type="hidden" name="product_id" id="subcatProductId">

                    <!-- HEADER -->
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i> Add Subcategory
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body add-subcat-modal-body" style="overflow-y:auto;max-height:55vh;">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-2">
                                    <i class="bi bi-folder me-1"></i> Category
                                </h6>
                                <p class="mb-0"><span id="subcatCategoryName"></span></p>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="bi bi-layers me-1"></i> Subcategory Details
                                </h6>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Group Name</label>
                                        <select name="subcat_group" class="form-control" id="subcat_group_select" onchange="toggleCustomGroupInput(this)">
                                            <option value="">-- Select Group --</option>
                                            <?php foreach ($group_names as $gname): ?>
                                                <option value="<?php echo htmlspecialchars($gname); ?>"><?php echo htmlspecialchars($gname); ?></option>
                                            <?php endforeach; ?>
                                            <option value="__custom__">Other (Type New)</option>
                                        </select>
                                        <input type="text" name="subcat_group_custom" id="subcat_group_custom" class="form-control mt-2" placeholder="Enter new group name" style="display:none;" />
                                        <script>
                                            function toggleCustomGroupInput(sel) {
                                                var custom = document.getElementById('subcat_group_custom');
                                                if (sel.value === '__custom__') {
                                                    custom.style.display = '';
                                                    custom.name = 'subcat_group';
                                                    sel.name = 'subcat_group_old';
                                                } else {
                                                    custom.style.display = 'none';
                                                    custom.name = 'subcat_group_custom';
                                                    sel.name = 'subcat_group';
                                                }
                                            }
                                        </script>
                                        <small class="text-muted">Optional - Groups subcategories together</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Subcategory Title</label>
                                        <input type="text"
                                            name="subcat_title"
                                            class="form-control"
                                            placeholder="e.g. Marble Design"
                                            required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Subcategory Image</label>
                                    <input type="file"
                                        name="subcat_image"
                                        class="form-control"
                                        accept="image/*"
                                        onchange="previewAddSubImage(event)"
                                        required>
                                    <img id="addSubPreview"
                                        class="mt-2 rounded d-none border"
                                        height="70">
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="bi bi-layers-half me-1"></i> Existing Subcategories
                                </h6>
                                <div id="editSubcatListContainer">
                                    <!-- AJAX loaded list of subcategories with edit/delete buttons -->
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- FOOTER -->
                    <div class="modal-footer bg-light sticky-modal-footer" style="position:sticky;bottom:0;z-index:10;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-plus-circle me-1"></i> Add Subcategory
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT SUBCATEGORY MODAL (NEW) -->
    <div class="modal fade" id="editSubcatModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form action="products-add" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_subcategory">
                    <input type="hidden" name="subcat_id" id="editSubcatId">
                    <input type="hidden" name="product_id" id="editSubcatProductId">
                    <input type="hidden" name="existing_subcat_image" id="editExistingSubcatImage">

                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i> Edit Subcategory</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Group Name</label>
                            <input type="text" name="group_name" id="editSubcatGroupName" class="form-control" list="group_names_list">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subcategory Title</label>
                            <input type="text" name="subcat_title" id="editSubcatTitleText" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subcategory Image</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img id="editSubcatPreview" src="" class="rounded border" height="60" style="object-fit: cover;">
                            </div>
                            <input type="file" name="subcat_image" class="form-control" accept="image/*" onchange="previewEditSubImage(event)">
                            <small class="text-muted">Leave empty to keep existing image</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL (NEW) -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered justify-content-center">
            <div class="modal-content border-0 shadow-lg delete-modal-width">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i> Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center py-4">
                    <p class="mb-0 fs-5" id="deleteConfirmMessage">
                        Are you sure you want to delete this item?
                    </p>
                    <!-- <small class="text-muted">
                    This action cannot be undone and will permanently remove the record.
                </small> -->
                </div>

                <div class="modal-footer bg-light border-0 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <a href="#" id="deleteConfirmBtn" class="btn btn-primary px-4 shadow-sm">
                        Delete Permanently
                    </a>
                </div>
            </div>
        </div>
    </div>


    <!-- Shared Datalist for Group Names -->
    <datalist id="group_names_list">
        <?php foreach ($group_names as $grp): ?>
            <option value="<?php echo htmlspecialchars($grp); ?>">
            <?php endforeach; ?>
    </datalist>


    <script>
        $(document).ready(function() {
            // Initialize Tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

            // Handle Add Subcategory Click
            $('.add-subcat-btn').click(function() {
                var id = $(this).data('id');
                var title = $(this).data('title');
                $('#subcatProductId').val(id);
                $('#subcatCategoryName').text(title);

                // Clear and show loading
                $('#editSubcatListContainer').html('<div class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div></div>');

                $('#addSubcatModal').modal('show');

                // --- FIX: Reset Form and Previews ---
                $('#addSubcatModal form')[0].reset();
                $('#addSubPreview').addClass('d-none').attr('src', '');
                $('#subcat_group_custom').hide().attr('name', 'subcat_group_custom');
                $('#subcat_group_select').attr('name', 'subcat_group');
                // --- END FIX ---

                // Fetch existing subcategories
                $.get('products-add?fetch_id=' + id, function(response) {
                    if (response.status === 'success') {
                        var subs = response.subcategories;
                        var subHtml = '';
                        if (subs.length > 0) {
                            subHtml = '<div class="table-responsive"><table class="table table-sm align-middle">';
                            subHtml += '<thead><tr><th>Image</th><th>Title</th><th>Group</th><th>Action</th></tr></thead><tbody>';
                            subs.forEach(function(s) {
                                var img = s.subcat_image ? 'images/products/' + s.subcat_image : 'images/placeholder.png';
                                subHtml += '<tr>';
                                subHtml += '<td><img src="' + img + '" class="rounded border" height="40" width="40" style="object-fit:cover;"></td>';
                                subHtml += '<td>' + s.subcat_title + '</td>';
                                subHtml += '<td><span class="group-badge">' + (s.group_name || 'General') + '</span></td>';
                                subHtml += '<td>';
                                subHtml += '<button type="button" class="btn btn-sm btn-outline-primary me-1 edit-sub-btn" data-id="' + s.id + '" data-prodid="' + id + '" data-title="' + s.subcat_title + '" data-group="' + (s.group_name || '') + '" data-img="' + s.subcat_image + '"><i class="bi bi-pencil"></i></button>';
                                subHtml += '<button type="button" class="btn btn-sm btn-outline-danger confirm-delete" data-href="products-add?delete_sub_id=' + s.id + '" data-msg="Delete this subcategory?"><i class="bi bi-trash"></i></button>';
                                subHtml += '</td></tr>';
                            });
                            subHtml += '</tbody></table></div>';
                        } else {
                            subHtml = '<p class="text-muted text-center py-3">No subcategories yet.</p>';
                        }
                        $('#editSubcatListContainer').html(subHtml);

                        // --- FIX: Filter Group Name Dropdown for THIS product ---
                        var groupSelect = $('#subcat_group_select');
                        
                        // Clear existing options except default and "Other"
                        groupSelect.find('option').each(function() {
                            var val = $(this).val();
                            if (val !== "" && val !== "__custom__") {
                                $(this).remove();
                            }
                        });

                        // Add unique groups for this product
                        if (response.unique_groups && response.unique_groups.length > 0) {
                            response.unique_groups.forEach(function(g) {
                                // Add before the "__custom__" option
                                $('<option>').val(g).text(g).insertBefore(groupSelect.find('option[value="__custom__"]'));
                            });
                        }
                        // --- END FIX ---

                        // Bind Edit Sub button
                        $('.edit-sub-btn').click(function() {
                            var sid = $(this).data('id');
                            var pid = $(this).data('prodid');
                            var stitle = $(this).data('title');
                            var sgroup = $(this).data('group');
                            var simg = $(this).data('img');

                            $('#editSubcatId').val(sid);
                            $('#editSubcatProductId').val(pid);
                            $('#editSubcatTitleText').val(stitle);
                            $('#editSubcatGroupName').val(sgroup);
                            $('#editExistingSubcatImage').val(simg);

                            if (simg) {
                                $('#editSubcatPreview').attr('src', 'images/products/' + simg).show();
                            } else {
                                $('#editSubcatPreview').hide();
                            }

                            // --- FIX: Filter Group Name Datalist for THIS product ---
                            var dl = $('#group_names_list');
                            dl.empty();
                            if (response.unique_groups && response.unique_groups.length > 0) {
                                response.unique_groups.forEach(function(g) {
                                    dl.append('<option value="' + g + '">');
                                });
                            }
                            // --- END FIX ---

                            $('#editSubcatModal').modal('show');
                        });
                    }
                });
            });

            // Handle View Click
            $('.view-btn').click(function() {
                var id = $(this).data('id');
                $('#viewModal').modal('show');

                // Clear previous data
                $('#viewCatTitle').text('Loading...');
                $('#viewCatImage').attr('src', '');
                $('#viewSubcatList').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');

                $.get('products-add?fetch_id=' + id, function(response) {
                    if (response.status === 'success') {
                        var prod = response.product;
                        var subs = response.subcategories;

                        $('#viewCatTitle').text(prod.cate_title);
                        if (prod.cat_image) {
                            $('#viewCatImage').attr('src', 'images/products/' + prod.cat_image).show();
                        } else {
                            $('#viewCatImage').hide();
                        }

                        var subHtml = '';
                        if (subs.length > 0) {
                            // Group subcategories by group_name
                            var grouped = {};
                            subs.forEach(function(s) {
                                var grp = s.group_name ? s.group_name : 'General';
                                if (!grouped[grp]) {
                                    grouped[grp] = [];
                                }
                                grouped[grp].push(s);
                            });

                            // Display each group
                            Object.keys(grouped).forEach(function(groupName) {
                                var items = grouped[groupName];

                                // Group header with delete button
                                subHtml += '<div class="d-flex justify-content-between align-items-center mb-2 mt-3">';
                                subHtml += '<h6 class="mb-0 text-primary"><i class="bi bi-folder me-1"></i>' + groupName + '</h6>';
                                subHtml += '<button type="button" class="btn btn-sm btn-outline-danger confirm-delete" data-href="products-add?delete_group=' + encodeURIComponent(groupName) + '&product_id=' + prod.id + '" data-msg="Delete entire group ' + groupName.replace(/'/g, "\\'") + '? This will remove all ' + items.length + ' subcategories in this group."><i class="bi bi-trash me-1"></i>Delete Group</button>';
                                subHtml += '</div>';
                                subHtml += '<hr class="mt-1 mb-2">';

                                // Display subcategories in this group
                                items.forEach(function(s) {
                                    var img = s.subcat_image ? 'images/products/' + s.subcat_image : 'images/placeholder.png';

                                    subHtml += '<div class="subcat-item">';
                                    subHtml += '<div class="d-flex align-items-center">';
                                    subHtml += '<img src="' + img + '" class="subcat-img">';
                                    subHtml += '<div><strong>' + s.subcat_title + '</strong></div>';
                                    subHtml += '</div>';
                                    subHtml += '<button type="button" class="btn btn-sm btn-outline-danger confirm-delete" data-href="products-add?delete_sub_id=' + s.id + '&parent_id=' + prod.id + '" data-msg="Delete this subcategory?" data-bs-toggle="tooltip" data-bs-title="Delete Subcategory"><i class="bi bi-trash"></i></button>';
                                    subHtml += '</div>';
                                });
                            });
                        } else {
                            subHtml = '<p class="text-muted text-center">No subcategories found.</p>';
                        }
                        $('#viewSubcatList').html(subHtml);
                    } else {
                        alert('Error fetching data');
                    }
                });
            });

            // Handle Edit Click
            $('.edit-btn').click(function() {
                var id = $(this).data('id');
                $('#editModal').modal('show');

                $.get('products-add?fetch_id=' + id, function(response) {
                    if (response.status === 'success') {
                        var prod = response.product;
                        $('#editProductId').val(prod.id);
                        $('#editCatTitle').val(prod.cate_title);
                        $('#editDescription').val(prod.description || '');
                        $('#editExistingImage').val(prod.cat_image);
                        $('#editExistingDescImage').val(prod.description_image);

                        if (prod.cat_image) {
                            $('#editImagePreview').attr('src', 'images/products/' + prod.cat_image).show();
                        } else {
                            $('#editImagePreview').hide();
                        }

                        if (prod.description_image) {
                            $('#editDescImagePreview').attr('src', 'images/products/' + prod.description_image).show();
                        } else {
                            $('#editDescImagePreview').hide();
                        }

                        // Populate Dynamic Group List
                        var groups = response.unique_groups;
                        var dl = $('#edit_dynamic_list');
                        dl.empty();
                        if (groups && groups.length > 0) {
                            groups.forEach(function(g) {
                                dl.append('<option value="' + g + '">');
                            });
                        }
                    }
                });
            });

        }); // End of document.ready

        // Centralized Delete Confirmation Logic
        $(document).on('click', '.confirm-delete', function(e) {
            e.preventDefault();
            var href = $(this).attr('href') || $(this).data('href');
            var msg = $(this).data('msg') || "Are you sure you want to delete this item?";

            $('#deleteConfirmMessage').text(msg);
            $('#deleteConfirmBtn').attr('href', href);
            $('#deleteConfirmModal').modal('show');
        });
    </script>
    <script>
        function previewCatImage(event) {
            const img = document.getElementById('catPreview');
            img.src = URL.createObjectURL(event.target.files[0]);
            img.classList.remove('d-none');
        }

        function previewDescImage(event) {
            const img = document.getElementById('descPreview');
            img.src = URL.createObjectURL(event.target.files[0]);
            img.classList.remove('d-none');
        }

        function previewEditCatImage(event) {
            const img = document.getElementById('editImagePreview');
            img.src = URL.createObjectURL(event.target.files[0]);
            img.style.display = 'block';
        }

        function previewEditDescImage(event) {
            const img = document.getElementById('editDescImagePreview');
            img.src = URL.createObjectURL(event.target.files[0]);
            img.style.display = 'block';
        }

        function previewAddSubImage(event) {
            const img = document.getElementById('addSubPreview');
            img.src = URL.createObjectURL(event.target.files[0]);
            img.classList.remove('d-none');
        }

        function previewEditSubImage(event) {
            const img = document.getElementById('editSubcatPreview');
            img.src = URL.createObjectURL(event.target.files[0]);
            img.style.display = 'block';
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Auto-dismiss alerts after 3 seconds
            setTimeout(function() {
                let alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    let bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 3000);

            // 2. Clear URL parameters (msg, error) without refreshing
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                if (url.searchParams.has('msg') || url.searchParams.has('error')) {
                    url.searchParams.delete('msg');
                    url.searchParams.delete('error');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            }
        });
    </script>

</body>

</html>