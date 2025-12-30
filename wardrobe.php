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
$projects=true;
include('header.php'); ?>



<div class="clearfix"></div>


<div class="slipheader">

<div class="abouthead">
        <h3>Wardrobe</h3>
        <ul class="about-short">
<li><a href="index.php">Home</a> <span>/</span></li>
<li><a href="projects.php">Projects</a> </li>

</ul>
        </div>

</div>



<div class="clearfix"></div>


<div class="clearfix"></div>


<div class="cont-out produlidt">
<div class="container-fluid position-relative">
<div class="row">


    <script src="src/jquery.littlelightbox.js"></script>
<script>
$('.lightbox').littleLightBox();
</script>




      <div class="col-md-3 smmob">
        <div class="projecthomeout">
          <a class="lightbox thumbnail attractions-gallery" data-littlelightbox-group="gallery" >
          <div class="projimg"><img src="images/project/wardrobe/1.jpg"></div>
          </a>
        </div>
      </div>
      
            <div class="col-md-3 smmob">
        <div class="projecthomeout">
          <a class="lightbox thumbnail attractions-gallery" data-littlelightbox-group="gallery" >
          <div class="projimg"><img src="images/project/wardrobe/2.jpg"></div>
          </a>
        </div>
      </div>


      <div class="col-md-3 smmob">
        <div class="projecthomeout">
          <a class="lightbox thumbnail attractions-gallery" data-littlelightbox-group="gallery" >
          <div class="projimg"><img src="images/project/wardrobe/3.jpg"></div>
          </a>
        </div>
      </div>


      <div class="col-md-3 smmob">
        <div class="projecthomeout">
          <a class="lightbox thumbnail attractions-gallery" data-littlelightbox-group="gallery" >
          <div class="projimg"><img src="images/project/wardrobe/4.jpg"></div>
          </a>
        </div>
      </div>


      


    
        
        <div class="clear"></div>





</div>



</div>
</div>




<?php include('footer.php'); ?>

<?php include('website-js.php'); ?>


</body>
</html>