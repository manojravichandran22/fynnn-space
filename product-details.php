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

    // Fetch JSON Specs
    $json_file = 'product_specs.json';
    $specs = [];
    if (file_exists($json_file)) {
        $json_data = file_get_contents($json_file);
        $full_specs = json_decode($json_data, true);
        
        // Logic: Try to find specs for this specific product ID, else use DEFAULT
        if (isset($full_specs['products'])) {
            if (isset($full_specs['products'][$product_id])) {
                $specs = $full_specs['products'][$product_id];
            } elseif (isset($full_specs['products']['DEFAULT'])) {
                $specs = $full_specs['products']['DEFAULT'];
            }
        }
    }

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
                            <?php if (!empty($specs['description'])): ?>
                                <?php echo nl2br(htmlspecialchars($specs['description'])); ?>
                            <?php else: ?>
                                Currently viewing our collection of <?php echo htmlspecialchars($product['cate_title']); ?>. 
                                These products are designed to enhance your interior spaces with durability and style.
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="title-column col-lg-8 col-md-12 col-sm-12">
                        <div class="headingparr">
                            <h3>SPECIFICATION</h3>
                        </div>
                        
                        <!-- Dynamic Specs from JSON -->
                        <?php if (!empty($specs['attributes'])): ?>
                            <?php foreach ($specs['attributes'] as $attr): ?>
                                <div class="fscontact-info">
                                    <span class="icon  fflaticon-before"><img src="images/tick.svg"></span> 
                                    <p class="p2p"><span class="p2p-span"><?php echo htmlspecialchars($attr['label']); ?> :</span> <?php echo htmlspecialchars($attr['value']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback Static Specs if JSON fails -->
                            <div class="fscontact-info">
                                <span class="icon  fflaticon-before"><img src="images/tick.svg"></span> 
                                <p class="p2p"><span class="p2p-span">Material :</span> Premium Quality Finish</p>
                            </div>
                             <div class="fscontact-info">
                                <span class="icon  fflaticon-before"><img src="images/tick.svg"></span> 
                                <p class="p2p"><span class="p2p-span">Application :</span> Residential & Commercial</p>
                            </div>
                        <?php endif; ?>
                    </div>
                
                    <!-- Product Image -->
                    <div class="video-column col-lg-4 col-md-12 col-sm-12">
                        <div class="abtus-img">
                             <?php if (!empty($product['cat_image'])): ?>
                                <img src="images/products/<?php echo htmlspecialchars($product['cat_image']); ?>" style="width:100%; height:400px; object-fit:cover;" />
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Advantages & Features Section -->
<section class="advantages-section" style="padding: 40px 0; ">
    <div class="auto-container">
        <div class="headingparr" style="margin-bottom: 30px; text-align: center !important;">
            <h3 style="text-align: center !important;">Advantages & Features</h3>
        </div>
        
        <?php if (!empty($specs['image'])): ?>
            <div class="row clearfix">
                <?php foreach ($specs['image'] as $img): ?>
                    <?php if (!empty($img['image'])): ?>
                        <div class="col-lg-12 col-md-12 col-sm-12" style="margin-bottom: 30px; text-align: center;">
                            <img src="<?php echo htmlspecialchars($img['image']); ?>" alt="Advantage" style="width: 1000px; max-width: 100%; height: auto; border-radius: 10px;">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Content coming soon...</p>
        <?php endif; ?>
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
                    
                    <?php if (!empty($specs['specification'])): ?>
                        <div style="text-align: center; margin-bottom: 20px; font-size: 16px;">
                            <?php 
                            $total_specs = count($specs['specification']);
                            foreach ($specs['specification'] as $index => $spec): 
                            ?>
                                <span style="display: inline-block; margin: 0 5px;">
                                    <?php if (!empty($spec['label'])): ?>
                                        <span style="color: #FFA500;"><?php echo htmlspecialchars($spec['label']); ?></span>
                                    <?php endif; ?>
                                    <span style="color: inherit;"><?php echo htmlspecialchars($spec['value']); ?></span>
                                    <?php if ($index < $total_specs - 1): ?>
                                        <span style="color: #ccc; margin-left: 10px;">|</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row mb-5">
                    <?php foreach ($items as $sub): ?>
                        <div class="col-md-3 smmob">
                            <div class="projecthomeout">
                                <div class="projimg">
                                    <?php if (!empty($sub['subcat_image'])): ?>
                                        <img src="images/products/<?php echo htmlspecialchars($sub['subcat_image']); ?>" style="width:100%; height:300px; object-fit:cover;" >
                                    <?php else: ?>
                                        <img src="images/default.jpg" style="width:100%; height:300px; object-fit:cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h6><?php echo htmlspecialchars($sub['subcat_title']); ?></h6>
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