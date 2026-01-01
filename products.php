<?php
ob_start();
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php include('website-link.php'); ?>



</head>
<body>

<?php
$products=true;
include('header.php'); ?>



<div class="clearfix"></div>


<div class="slipheader">

<div class="abouthead">
        <h3>Our Products</h3>
        <h6>Design Ideas for Every Space</h6>
        </div>

</div>



<div class="clearfix"></div>


<div class="clearfix"></div>


<div class="cont-out produlidt">
<div class="container-fluid position-relative">
    <div class="row">

      <?php
      require_once 'db_config.php';
      try {
          $stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
          $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if (count($products) > 0) {
              foreach ($products as $row) {
                  $imagePath = !empty($row['cat_image']) ? 'images/products/' . htmlspecialchars($row['cat_image']) : 'images/default-product.jpg';
                  ?>
                  <div class="col-md-3 smmob">
                    <div class="projecthomeout">
                      <a href="product-details?id=<?php echo $row['id']; ?>">
                      <div class="projimg"><img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($row['cate_title']); ?>" style="width:100%; height:400px; object-fit:cover;"></div>
                      <h6><?php echo htmlspecialchars($row['cate_title']); ?></h6>
                      </a>
                    </div>
                  </div>
                  <?php
              }
          } else {
              echo '<div class="col-12 text-center"><p>No products available at the moment.</p></div>';
          }
      } catch (PDOException $e) {
          echo '<p class="text-danger">Error loading products.</p>';
      }
      ?>

    </div>



</div>
</div>




<?php include('footer.php'); ?>

<?php include('website-js.php'); ?>

</body>
</html>