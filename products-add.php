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
        
        echo json_encode(['status' => 'success', 'product' => $product, 'subcategories' => $subcategories]);
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
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: products-add.php?msg=Category deleted successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting category: " . $e->getMessage();
    }
}

// Handle Delete Subcategory (via simple GET for now, simplifies modal logic vs AJAX delete)
if (isset($_GET['delete_sub_id'])) {
    $delete_sub_id = $_GET['delete_sub_id'];
    $redirect_id = $_GET['parent_id']; 
    try {
        $stmt = $db->prepare("DELETE FROM product_subcategories WHERE id = ?");
        $stmt->execute([$delete_sub_id]);
        // stay on page, maybe pass a param to re-open modal? Simple refresh is safer for now.
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
            background: linear-gradient(135deg, #7c4d4dff, #766496ff);
            color: white;
            padding: 20px 0;
            margin-top: 20px;
        }
        .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .modal-header { background: #f8f9fa; }
        .subcat-item { display: flex; align-items: center; justify-content: space-between; padding: 10px; border-bottom: 1px solid #eee; }
        .subcat-item:last-child { border-bottom: none; }
        .subcat-img { width: 40px; height: 40px; object-fit: cover; border-radius: 3px; margin-right: 10px; }
        .group-badge { font-size: 0.75em; background: #e9ecef; padding: 2px 6px; border-radius: 4px; margin-left:8px; color:#555;}
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
        <div class="card shadow-sm">
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
                                            <button class="btn btn-sm btn-outline-info me-1 view-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-pencil-square"></i></button>
                                            <a href="products-add.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?');"><i class="bi bi-trash"></i></a>
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
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="products-add.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Main Category -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category Title</label>
                            <input type="text" name="cate_title" class="form-control" required placeholder="e.g. Wall Panels">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category Image</label>
                            <input type="file" name="cat_image" class="form-control" accept="image/*">
                        </div>

                        <hr>
                        <h6 class="text-primary"><i class="bi bi-plus-circle"></i> Add Initial Subcategory (Optional)</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Group Name / Heading</label>
                                <input type="text" name="subcat_group" class="form-control" placeholder="e.g. 3MM Pattern">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subcategory Title</label>
                                <input type="text" name="subcat_title" class="form-control" placeholder="e.g. Marble Pattern">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subcategory Image</label>
                            <input type="file" name="subcat_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Product</button>
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
                                <input type="text" name="new_subcat_group" class="form-control" placeholder="e.g. Pattern A">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            
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
                                subHtml += '<a href="products-add.php?delete_sub_id=' + s.id + '&parent_id=' + prod.id + '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Delete this subcategory?\')"><i class="bi bi-trash"></i></a>';
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
                    }
                });
            });

        });
    </script>
</body>
</html>