<?php
ob_start();
session_start();
require_once 'db_config.php';

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = $_GET['id'];
try {
    // Fetch Product Details
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: products.php');
        exit();
    }

    // Fetch Subcategories
    $stmt_sub = $db->prepare("SELECT * FROM product_subcategories WHERE product_id = ? ORDER BY id ASC");
    $stmt_sub->execute([$product_id]);
    $subcategories = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include('website-link.php'); ?>
</head>
<body>

<?php
$products=true;
include('header.php'); ?>

<div class="clearfix"></div>

<div class="slipheader">
    <div class="abouthead">
        <h3><?php echo htmlspecialchars($product['cate_title']); ?></h3>
        <ul class="about-short">
            <li><a href="index.php">Home</a> <span>/</span></li>
            <li><a href="products.php">Products</a> </li>
        </ul>
    </div>
</div>

<div class="clearfix"></div>

<!-- Services Section -->
<section class="services-section">
    <div class="auto-container">
        <div class="pt-oop">
            <div class="ccaro-out">
                <div class="row clearfix">
                    
                    <!-- Description Column (Static for now as per template, can be dynamic later) -->
                    <div class="title-column col-lg-12 col-md-12 col-sm-12">
                        <div class="text abtexzt">
                            <!-- Placeholder for dynamic description if added later -->
                            Currently viewing our collection of <?php echo htmlspecialchars($product['cate_title']); ?>. 
                            These products are designed to enhance your interior spaces with durability and style.
                        </div>
                    </div>

                    <div class="title-column col-lg-8 col-md-12 col-sm-12">
                        <div class="headingparr">
                            <h3>SPECIFICATION</h3>
                        </div>
                        
                        <!-- Static Specs from template - ideally these should be dynamic too but user didn't ask for spec CRUD -->
                        <div class="fscontact-info">
                            <span class="icon  fflaticon-before"><img src="images/tick.svg"></span> 
                            <p class="p2p"><span class="p2p-span">Material :</span> Premium Quality Finish</p>
                        </div>
                         <div class="fscontact-info">
                            <span class="icon  fflaticon-before"><img src="images/tick.svg"></span> 
                            <p class="p2p"><span class="p2p-span">Application :</span> Residential & Commercial</p>
                        </div>
                    </div>
                
                    <!-- Product Image -->
                    <div class="video-column col-lg-4 col-md-12 col-sm-12">
                        <div class="abtus-img">
                             <?php if (!empty($product['cat_image'])): ?>
                                <img src="images/products/<?php echo htmlspecialchars($product['cat_image']); ?>" style="width:100%; border-radius:10px;" />
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</section>

<div class="clearfix"></div>

<!-- Subcategories / Patterns Section -->
<section class="projectsection">
    <div class="container-fluid position-relative">
        
        <?php
        // Group subcategories by 'group_name'
        $grouped_subs = [];
        if (count($subcategories) > 0) {
            foreach ($subcategories as $sub) {
                $group = !empty($sub['group_name']) ? $sub['group_name'] : 'Designs'; // Default header if empty
                $grouped_subs[$group][] = $sub;
            }
        }
        ?>

        <?php if (count($grouped_subs) > 0): ?>
            <?php foreach ($grouped_subs as $group_title => $items): ?>
                
                <div class="headingpar proejcthead" >
                    <h3><?php echo htmlspecialchars($group_title); ?></h3>
                </div>

                <div class="row mb-5">
                    <?php foreach ($items as $sub): ?>
                        <div class="col-md-3 smmob">
                            <div class="projecthomeout">
                                <div class="projimg">
                                    <?php if (!empty($sub['subcat_image'])): ?>
                                        <img src="images/products/<?php echo htmlspecialchars($sub['subcat_image']); ?>" style="width:100%; height:250px; object-fit:cover;">
                                    <?php else: ?>
                                        <img src="images/default.jpg" style="width:100%; height:250px; object-fit:cover;">
                                    <?php endif; ?>
                                </div>
                                <h6><?php echo htmlspecialchars($sub['subcat_title']); ?></h6>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="text-center py-5">
                <h4>No specific designs added for this category yet.</h4>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php include('footer.php'); ?>
<?php include('website-js.php'); ?>

</body>
</html>
