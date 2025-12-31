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
        // Fetch images before deleting from DB
        $stmt_cat = $db->prepare("SELECT cat_image FROM products WHERE id = ?");
        $stmt_cat->execute([$delete_id]);
        $cat_row = $stmt_cat->fetch(PDO::FETCH_ASSOC);
        
        $stmt_sub = $db->prepare("SELECT subcat_image FROM product_subcategories WHERE product_id = ?");
        $stmt_sub->execute([$delete_id]);
        $sub_images = $stmt_sub->fetchAll(PDO::FETCH_COLUMN);

        // Delete from database
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);

        $target_dir = "images/products/";

        // Delete Category Image
        if ($cat_row && !empty($cat_row['cat_image'])) {
            $file_path = $target_dir . $cat_row['cat_image'];
            if (file_exists($file_path)) unlink($file_path);
        }

        // Delete all Subcategory Images
        foreach ($sub_images as $img) {
            if (!empty($img)) {
                $file_path = $target_dir . $img;
                if (file_exists($file_path)) unlink($file_path);
            }
        }

        header("Location: products-add.php?msg=Category deleted successfully");
        exit();
    } catch (PDOException $e) {
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

        header("Location: products-add.php?msg=Subcategory deleted"); 
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting subcategory: " . $e->getMessage();
    }
}

// Save New Category (and optional Subcategory)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_product') {
    $cate_title = $_POST['cate_title'];
    
    // Upload Category Image
    $cat_image = '';
    $target_dir = "images/products/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    if (!empty($_FILES["cat_image"]["name"])) {
        $cat_image = time() . "_cat_" . basename($_FILES["cat_image"]["name"]);
        move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_dir . $cat_image);
    }

    try {
        $db->beginTransaction();
        
        // Insert Category
        $stmt = $db->prepare("INSERT INTO products (cate_title, cat_image) VALUES (?, ?)");
        $stmt->execute([$cate_title, $cat_image]);
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
        header("Location: products-add.php?msg=Product added successfully");
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
    
    $cat_image = $_POST['existing_cat_image'];
    $target_dir = "images/products/";
    
    if (!empty($_FILES["cat_image"]["name"])) {
        $cat_image = time() . "_cat_" . basename($_FILES["cat_image"]["name"]);
        move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_dir . $cat_image);
    }

    try {
        $stmt = $db->prepare("UPDATE products SET cate_title = ?, cat_image = ? WHERE id = ?");
        $stmt->execute([$cate_title, $cat_image, $product_id]);
        
        // Also handle adding a NEW subcategory from the Edit modal if fields are present
        if (!empty($_POST['new_subcat_title']) && !empty($_FILES['new_subcat_image']['name'])) {
             $subcat_title = $_POST['new_subcat_title'];
             $group_name = !empty($_POST['new_subcat_group']) ? $_POST['new_subcat_group'] : 'General';
             
             $subcat_image = time() . "_sub_" . basename($_FILES["new_subcat_image"]["name"]);
             move_uploaded_file($_FILES["new_subcat_image"]["tmp_name"], $target_dir . $subcat_image);

             $stmt_sub = $db->prepare("INSERT INTO product_subcategories (product_id, subcat_title, subcat_image, group_name) VALUES (?, ?, ?, ?)");
             $stmt_sub->execute([$product_id, $subcat_title, $subcat_image, $group_name]);
        }
        
        header("Location: products-add.php?msg=Category updated successfully");
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
            background:url('images/banner1.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
            padding: 50px 0;
            color: white;
        }
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }
        .dashboard-header .container-fluid {
            position: relative;
            z-index: 2;
        }
        .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .modal-header { background: #f8f9fa; }
        .subcat-item { display: flex; align-items: center; justify-content: space-between; padding: 12px; border-bottom: 1px solid #eee; transition: background 0.2s;}
        .subcat-item:hover { background: #f9f9f9; }
        .subcat-item:last-child { border-bottom: none; }
        .subcat-img { width: 45px; height: 45px; object-fit: cover; border-radius: 5px; margin-right: 12px; }
        .group-badge { font-size: 0.75em; background: #f0f2f5; padding: 3px 8px; border-radius: 12px; margin-left:8px; color:#666; font-weight: 500;}
        
        /* Premium Table Styling */
        .table-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08) !important;
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
        .btn-primary, .bg-primary, .dropdown-item.active, .dropdown-item:active {
            background-color: #fc9401 !important;
            border-color: #fc9401 !important;
        }
        .btn-outline-primary, .btn-outline-info, .text-primary {
            color: #fc9401 !important;
        }
        .btn-outline-primary, .btn-outline-info {
            border-color: #fc9401 !important;
        }
        .btn-outline-primary:hover, .btn-outline-info:hover {
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
                        <span class="me-3 text-white">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
                                            <button class="btn btn-sm btn-outline-info me-1 view-btn" data-id="<?php echo $row['id']; ?>" data-bs-toggle="tooltip" data-bs-title="View Details"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="<?php echo $row['id']; ?>" data-bs-toggle="tooltip" data-bs-title="Edit Product"><i class="bi bi-pencil-square"></i></button>
                                            <a href="products-add.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?');" data-bs-toggle="tooltip" data-bs-title="Delete Product"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4">No products found.</td></tr>
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
            <form action="products-add.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">

                <div class="modal-body">

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
                        </div>
                    </div>

                    <!-- SUBCATEGORY CARD -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-layers me-1"></i> Initial Subcategory (Optional)
                            </h6>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Group Name</label>
                                    <input type="text"
                                           name="subcat_group"
                                           class="form-control"
                                           placeholder="e.g. 3MM Pattern"
                                           list="group_names_list">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Subcategory Title</label>
                                    <input type="text"
                                           name="subcat_title"
                                           class="form-control"
                                           placeholder="e.g. Marble Design">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Subcategory Image</label>
                                    <input type="file"
                                           name="subcat_image"
                                           class="form-control"
                                           accept="image/*"
                                           onchange="previewSubImage(event)">
                                    <img id="subPreview"
                                         class="mt-2 rounded d-none border"
                                         height="60">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer bg-light">
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
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalTitle">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    <h5 class="border-bottom pb-2">Subcategories / Designs</h5>
                    <div id="viewSubcatList">
                        <!-- Ajax Content -->
                        <div class="text-center py-3"><div class="spinner-border text-primary"></div></div>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="products-add.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" id="editProductId">
                    <input type="hidden" name="existing_cat_image" id="editExistingImage">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category Title</label>
                            <input type="text" name="cate_title" id="editCatTitle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category Image</label>
                            <div class="mb-2">
                                <img id="editImagePreview" src="" style="height: 60px; border-radius:4px;">
                            </div>
                            <input type="file" name="cat_image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep existing image</small>
                        </div>
                        
                        <hr>
                        <h6 class="text-primary">Add New Subcategory</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Group Name</label>
                                <input type="text" name="new_subcat_group" class="form-control" placeholder="e.g. Pattern A" list="edit_dynamic_list" autocomplete="off">
                                <datalist id="edit_dynamic_list"></datalist>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="new_subcat_title" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="new_subcat_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Shared Datalist for Group Names -->
    <datalist id="group_names_list">
        <?php foreach ($group_names as $grp): ?>
            <option value="<?php echo htmlspecialchars($grp); ?>">
        <?php endforeach; ?>
    </datalist>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
            
            // Handle View Click
            $('.view-btn').click(function() {
                var id = $(this).data('id');
                $('#viewModal').modal('show');
                
                // Clear previous data
                $('#viewCatTitle').text('Loading...');
                $('#viewCatImage').attr('src', '');
                $('#viewSubcatList').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');

                $.get('products-add.php?fetch_id=' + id, function(response) {
                    if(response.status === 'success') {
                        var prod = response.product;
                        var subs = response.subcategories;

                        $('#viewCatTitle').text(prod.cate_title);
                        if(prod.cat_image) {
                            $('#viewCatImage').attr('src', 'images/products/' + prod.cat_image).show();
                        } else {
                             $('#viewCatImage').hide();
                        }

                        var subHtml = '';
                        if(subs.length > 0) {
                            subs.forEach(function(s) {
                                var img = s.subcat_image ? 'images/products/' + s.subcat_image : 'images/placeholder.png';
                                var grp = s.group_name ? s.group_name : 'General';
                                
                                subHtml += '<div class="subcat-item">';
                                subHtml += '<div class="d-flex align-items-center">';
                                subHtml += '<img src="' + img + '" class="subcat-img">';
                                subHtml += '<div><strong>' + s.subcat_title + '</strong><span class="group-badge">' + grp + '</span></div>';
                                subHtml += '</div>';
                                subHtml += '<a href="products-add.php?delete_sub_id=' + s.id + '&parent_id=' + prod.id + '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Delete this subcategory?\')" data-bs-toggle="tooltip" data-bs-title="Delete Subcategory"><i class="bi bi-trash"></i></a>';
                                subHtml += '</div>';
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
                
                $.get('products-add.php?fetch_id=' + id, function(response) {
                    if(response.status === 'success') {
                        var prod = response.product;
                        $('#editProductId').val(prod.id);
                        $('#editCatTitle').val(prod.cate_title);
                        $('#editExistingImage').val(prod.cat_image);
                        
                        if(prod.cat_image) {
                            $('#editImagePreview').attr('src', 'images/products/' + prod.cat_image).show();
                        } else {
                            $('#editImagePreview').hide();
                        }

                        // Populate Dynamic Group List
                        var groups = response.unique_groups;
                        var dl = $('#edit_dynamic_list');
                        dl.empty();
                        if(groups && groups.length > 0) {
                            groups.forEach(function(g) {
                                dl.append('<option value="'+g+'">');
                            });
                        }
                    }
                });
            });

        });
    </script>
    <script>
function previewCatImage(event) {
    const img = document.getElementById('catPreview');
    img.src = URL.createObjectURL(event.target.files[0]);
    img.classList.remove('d-none');
}

function previewSubImage(event) {
    const img = document.getElementById('subPreview');
    img.src = URL.createObjectURL(event.target.files[0]);
    img.classList.remove('d-none');
}
</script>

</body>
</html>