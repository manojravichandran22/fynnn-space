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
    $products = true;
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

                        <!-- Description Column -->
                        <div class="title-column col-lg-12 col-md-12 col-sm-12">
                            <div class="text abtexzt">
                                <?php if (!empty($product['description'])): ?>
                                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
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

                        <!-- Description Image (Right Side) -->
                        <div class="video-column col-lg-4 col-md-12 col-sm-12">
                            <div class="abtus-img">
                                <?php if (!empty($product['description_image'])): ?>
                                    <img src="images/products/<?php echo htmlspecialchars($product['description_image']); ?>" style="width:100%; height:400px; object-fit:cover;" />
                                <?php elseif (!empty($product['cat_image'])): ?>
                                    <img src="images/products/<?php echo htmlspecialchars($product['cat_image']); ?>" style="width:100%; height:400px; object-fit:cover;" />
                                <?php endif; ?>
                            </div>
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
            <?php if (count($specs['image']) === 1 && empty($specs['image'][0]['title'])): ?>
                <!-- Single Banner Image Case -->
                <div class="row clearfix">
                    <div class="col-lg-12 col-md-12 col-sm-12" style="margin-bottom: 30px; text-align: center;">
                        <img src="<?php echo htmlspecialchars($specs['image'][0]['image'] ?? $specs['image'][0]['icon']); ?>" alt="Advantage" style="width: 1000px; max-width: 100%; height: auto; border-radius: 10px;">
                    </div>
                </div>
            <?php else: ?>
                <!-- Multi-Icon Grid Case -->
                <div class="container" style="max-width: 1500px; margin-bottom: 30px;">
                    <div class="row justify-content-center text-center">
                        <?php foreach ($specs['image'] as $adv): ?>
                            <?php 
                            $iconSrc = $adv['icon'] ?? ($adv['image'] ?? '');
                            $iconTitle = $adv['title'] ?? '';
                            if (!empty($iconSrc)): 
                            ?>
                                <div class="col-6 col-md-3 col-lg-1 mb-4">
                                    <div class="advantage-box">
                                        <img src="<?php echo htmlspecialchars($iconSrc); ?>" alt="<?php echo htmlspecialchars($iconTitle); ?>" style="width: 60px; height: 60px; object-fit: contain; margin-bottom: 12px; display: inline-block;">
                                        <h6 style="font-size: 14px; font-weight: 500; color: #333; line-height: 1.2; margin: 0;"><?php echo htmlspecialchars($iconTitle); ?></h6>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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

                    <div class="headingpar proejcthead">
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

                    <div class="row mb-5 group-images-wrapper" data-group="<?php echo htmlspecialchars($group_title); ?>">
                        <?php $max_initial = 8;
                        $item_count = count($items); ?>
                        <?php foreach ($items as $idx => $sub): ?>
                            <div class="col-md-3 smmob group-image-item" style="<?php echo ($idx >= $max_initial) ? 'display:none;' : ''; ?>">
                                <div class="projecthomeout">
                                    <div class="projimg">
                                        <?php if (!empty($sub['subcat_image'])): ?>
                                            <img src="images/products/<?php echo htmlspecialchars($sub['subcat_image']); ?>" style="width:100%; height:500px; object-fit:cover;">
                                        <?php else: ?>
                                            <img src="images/default.jpg" style="width:100%; height:300px; object-fit:cover;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <h6><?php echo htmlspecialchars($sub['subcat_title']); ?></h6>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($item_count > $max_initial): ?>
                            <div class="col-12 text-center mt-3">
                                <button class="btn load-more-btn orange-btn" data-group="<?php echo htmlspecialchars($group_title); ?>">Load More</button>
                            </div>
                        <?php endif; ?>
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
    <style>
        .orange-btn {
            background-color: #ff9800 !important;
            border-color: #ff9800 !important;
            color: #fff !important;
            transition: background 0.2s, border 0.2s;
        }

        .orange-btn:hover,
        .orange-btn:focus {
            background-color: #e68900 !important;
            border-color: #e68900 !important;
            color: #fff !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var IMAGES_PER_LOAD = 8;
            document.querySelectorAll('.load-more-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var group = btn.getAttribute('data-group');
                    var wrapper = document.querySelector('.group-images-wrapper[data-group="' + group.replace(/"/g, '\\"') + '"]');
                    if (wrapper) {
                        var items = wrapper.querySelectorAll('.group-image-item');
                        var hidden = [];
                        items.forEach(function(item) {
                            if (item.style.display === 'none') hidden.push(item);
                        });
                        for (var i = 0; i < IMAGES_PER_LOAD && i < hidden.length; i++) {
                            hidden[i].style.display = '';
                        }
                        // If all are now shown, hide the button
                        var anyHidden = false;
                        items.forEach(function(item) {
                            if (item.style.display === 'none') anyHidden = true;
                        });
                        if (!anyHidden) {
                            btn.style.display = 'none';
                        }
                    }
                });
            });
        });
    </script>

</body>

</html>