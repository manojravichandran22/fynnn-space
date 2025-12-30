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
  $index = true;
  include('header.php'); ?>

  <!--banner-->


  <section class="banner-section">
    <div class="container-fluid position-relative">


      <div id="banner_slider" class="owl-carousel">

        <div class="item_new">

          <div class="bannercont">
            <div class="bnner_contdet">
              <h2>Transforming<br />
                <span>Spaces, Enriching<br /></span>
                Lives
              </h2>

              <p>Your space deserves the best and we make sure you get it.
              <p>
                <?php /*?> <a class="appointment_now" href="services.php"><img src="images/about-icon.png" style="width:22px; "> View More</a><?php */ ?>

            </div>

          </div>


        </div>
        <div class="item_new">

          <div class="bannercont2">
            <div class="bnner_contdet">
              <h2>Designed for <br />
                <span>Spaces Made for <br /></span>
                Life
              </h2>

              <p>Your space deserves outstanding detail and we deliver with precision.
              <p>
                <!--  <a class="appointment_now" href="services.php"><img src="images/about-icon.png" style="width:22px; "> View More</a>-->

            </div>

          </div>

        </div>

      </div>














    </div>

  </section>


  </div>

  <!--headerout-->


  <div class="clearfix"></div>





  <section class="homewelsection">
    <div class="container-fluid position-relative">

      <div class="welcomimgout">
        <img src="images/welcome_abt.jpg" />
      </div>

      <div class="welcompartout">

        <div class="welpart1"><img src="images/homeicon/1.png" />
          <div class="welpartcon">
            <h3><span class="counter">25</span>K +</h3>
            <h6>Interior Design</h6>
          </div>
        </div>


        <div class="welpart1"><img src="images/homeicon/2.png" />
          <div class="welpartcon">
            <h3><span class="counter">100</span> +</h3>
            <h6>Design Experts</h6>
          </div>
        </div>


        <div class="welpart1"><img src="images/homeicon/3.png" />
          <div class="welpartcon">
            <h3><span class="counter">17</span>K +</h3>
            <h6>Design Options</h6>
          </div>
        </div>


        <div class="welpart1"><img src="images/homeicon/4.png" />
          <div class="welpartcon">
            <h3><span class="counter">03</span> +</h3>
            <h6>Experiences</h6>
          </div>
        </div>

      </div>


      <div class="welcomaboutout">
        <div class="headingpar">
          <h6>Welcome to<h6>
              <h3>FYNN SPACE - Interiors & Exteriors</h3>
        </div>
        <p>At FYNN SPACE, we design and deliver interior & exterior solutions that blend functionality, elegance, and durability. Based in Namakkal, we've been creating dream spaces for over 3 years, completing both residential and commercial projects with unmatched quality and customer satisfaction.
        </p>

        <a class="appointment_now" href="about-us.php"><img src="images/about-icon.png" /> About More</a>
      </div>


    </div>
  </section>



  <!--projectsection-->
  <section class="projectsection">
    <div class="container-fluid position-relative">


      <div class="headingpar proejcthead">
        <h6>Our Projects</h6>
        <h6>
        </h6>
        <h3>
          Design Ideas for Every Space</h3>
      </div>

      <div class="row">







        <div class="col-md-3 smmob">
          <div class="projecthomeout">
            <a href="living-room.php">
              <div class="projimg"><img src="images/project/1.jpg"></div>
              <h6>Living Room</h6>
            </a>
          </div>
        </div>


        <div class="col-md-3 smmob">
          <div class="projecthomeout">
            <a href="modular-kitchen.php">
              <div class="projimg"><img src="images/project/2.jpg"></div>
              <h6>Modular Kitchen</h6>
            </a>

          </div>
        </div>


        <div class="col-md-3 smmob">
          <div class="projecthomeout">
            <a href="bedroom.php">
              <div class="projimg"><img src="images/project/3.jpg"></div>
              <h6>Bedroom</h6>
            </a>
          </div>
        </div>


        <div class="col-md-3 smmob">
          <div class="projecthomeout">
            <a href="showroom.php">
              <div class="projimg"><img src="images/project/4.jpg"></div>
              <h6>Showroom</h6>
            </a>
          </div>
        </div>


        <div class="col-md-3 smmob">
          <div class="projecthomeout">
            <a href="wardrobe.php">
              <div class="projimg"><img src="images/project/5.jpg"></div>
              <h6>Wardrobe</h6>
            </a>
          </div>
        </div>


        <div class="col-md-3 smmob">
          <div class="projecthomeout">
            <a href="corporate-office.php">
              <div class="projimg"><img src="images/project/6.jpg"></div>
              <h6>Corporate Office</h6>
            </a>

          </div>
        </div>


        <div class="col-md-3 smmob">
          <div class="projecthomeout">
            <a href="coffee-shop.php">
              <div class="projimg"><img src="images/project/7.jpg"></div>
              <h6>Coffee Shop</h6>
            </a>
          </div>
        </div>


        <div class="col-md-3 smmob">
          <div class="projecthomeout">
            <a href="balcony.php">
              <div class="projimg"><img src="images/project/8.jpg"></div>
              <h6>Balcony</h6>
            </a>

          </div>
        </div>









      </div>



      <a class="appointment_now" href="projects.php"><img src="images/about-icon.png" /> View More</a>


    </div>

  </section>
  <!--projectsection-->

  <!--whysection-->
  <section class="whysection">
    <div class="container-fluid position-relative">

      <div class="row">



        <div class="col-md-7 othersmsiz">
          <div class="whychoose_out">
            <h3>WHY CHOOSE US</h3>
            <p>With us, you experience the power of ideas, design and craftsmanship come alive.</p>

            <ul class="whyul">

              <li>
                <img src="images/why/1.png" />
                <span>3+ Years of Expertise in interiors &amp; exteriors.</span>
              </li>

              <li>
                <img src="images/why/4.png" />
                <span>Customer-Centric Approach with personalized service.</span>
              </li>
              <li>
                <img src="images/why/2.png" />
                <span>Quality Materials for long-lasting results.</span>
              </li>

              <li>
                <img src="images/why/5.png" />
                <span>Complete Solutions design, supply, and installation.</span>
              </li>

              <li>
                <img src="images/why/3.png" />
                <span>Proven Track Record with successful home and commercial projects.</span>
              </li>




          </div>
        </div>

      </div>


    </div>
  </section>
  <!--whysection-->



  <!--servicesection-->


  <section class="servicesection">
    <div class="container-fluid position-relative">

      <h3>Services</h3>

      <div id="serviceslider" class="owl-carousel">



        <div class="item_new">
          <a href="services.php#modular">
            <div class="serciimg">
              <img src="images/service/1.jpg" />
              <h5>Modular Kitchens</h5>
              <p>Stylish, space efficient, and tailored to your lifestyle.</p>
            </div>
          </a>
        </div>




        <div class="item_new">
          <a href="services.php#cupboards">
            <div class="serciimg">
              <img src="images/service/2.jpg" />
              <h5>Cupboards &amp; Storage</h5>
              <p>Durable wooden and aluminium cupboards crafted for smart storage.</p>
            </div>
          </a>
        </div>



        <div class="item_new">
          <a href="services.php#roofing">
            <div class="serciimg">
              <img src="images/service/3.jpg" />
              <h5>Roofing Solutions</h5>
              <p>Strong, long-lasting, and aesthetically appealing.</p>
            </div>
          </a>
        </div>



        <div class="item_new">
          <a href="services.php#ceiling">
            <div class="serciimg">
              <img src="images/service/4.jpg" />
              <h5>Ceiling Works</h5>
              <p>False ceilings &amp; soffit ceilings for modern finishes.</p>
            </div>
          </a>
        </div>


        <div class="item_new">
          <a href="services.php#wall">
            <div class="serciimg">
              <img src="images/service/5.jpg" />
              <h5>Wall Panels</h5>
              <p>Redefine your walls with premium WPC panels.</p>
            </div>
          </a>
        </div>




        <div class="item_new">
          <a href="services.php#wallpaper">
            <div class="serciimg">
              <img src="images/service/2.jpg" />
              <h5>Wallpaper Designs</h5>
              <p>Add personality to your walls with our wide range</p>
            </div>
          </a>
        </div>



        <div class="item_new">
          <a href="services.php#wallpaper">
            <div class="serciimg">
              <img src="images/service/3.jpg" />
              <h5>Flooring Solutions</h5>
              <p> Step into luxury with our SPC and wooden flooring </p>
            </div>
          </a>
        </div>

















      </div>


    </div>

  </section>









  <!--testimonialout-->
  <section class="testimonialout">
    <div class="testmonleft"><img src="images/testbg1.jpg" /></div>



    <div class="testmonright">
      <div class="testmonhead">
        <h6>Testimonials</h6>
        <h3>Customer Reviews</h3>
      </div>



      <div id="testsilder" class="owl-carousel">



        <div class="item_new">

          <div class="testcont">
            <h4>Residential Interior Design</h4>
            <p>We recently renovated our home in Namakkal and choose Fynn Space for the interior design. From the first consultation to the final handover, their team was professional, creative, and incredibly detail oriented. The design matched our vision perfectly, and the implementation was seamless. They transformed our house into a warm, elegant, and functional space we are proud of. Highly recommended!</p>

            <h5>Mrs. Kavitha R.,<br />
              <span>Namakkal</span>
            </h5>
          </div>

        </div>

        <div class="item_new">

          <div class="testcont">
            <h4>Commercial Office Space</h4>
            <p>Fynn Space Interior and Exterior completely changed the look and feel of our office space. Their design brought a modern, vibrant energy while maintaining functionality. What impressed us most was their timely execution and transparent communication throughout the process. Our clients and employees love the new ambiance. Great Job, team!</p>

            <h5>Mr. Arun Prakash.,<br />
              <!--<span>Namakkal</span>-->
            </h5>
          </div>

        </div>


        <div class="item_new">

          <div class="testcont">
            <h4>Interior Implementation
            </h4>
            <p>We hired Fynn Space for a interior implementation of our new apartment, and the experience was fantastic. They took care of everything from layout planning and 3D design to furniture and lighting installation. The final result was beyond our expectations. Quality work, delivered on time, with a personal touch. Truly the best in Namakkal!</p>

            <h5>Mrs. S. Dinesh Kumar.,<br />
              <span>Namakkal</span>
            </h5>
          </div>

        </div>

      </div>









    </div>

  </section>
  <!--testimonialout-->







  <!--howewesection-->


  <section class="howewesection">
    <div class="container-fluid position-relative">

      <div class="headingpar howwehead">
        <h6> Our Process</h6>
        <h3 style="text-transform: uppercase;">How We Work</h3>
      </div>
      <p class="ourpro_pg">At <span>FYNN SPACE</span>, we believe in a clear and customer-friendly workflow to ensure your project is hassle-free.</p>



      <div class="howworkpart_out">

        <div class="hhowports_out">
          <div class="hhowports_in">
            <h6>1</h6>
            <h5>Consultation</h5>
            <p>
              We understand your
              Needs, Ideas, and Budget.
            </p>

          </div>
        </div>


        <div class="hhowports_out">
          <div class="hhowports_in">
            <h6>2</h6>
            <h5>Discussion & Design</h5>
            <p>
              Our team shares
              design concepts and
              practical solutions.

            </p>

          </div>
        </div>

        <div class="hhowports_out">
          <div class="hhowports_in">
            <h6>3</h6>
            <h5>Site Visit</h5>
            <p>
              Detailed measurement
              and site analysis for
              accurate planning.

            </p>

          </div>
        </div>

        <div class="hhowports_out">
          <div class="hhowports_in">
            <h6>4</h6>
            <h5>Material Selection</h5>
            <p>
              Choose from a curated
              range of high-quality
              materials.
            </p>

          </div>
        </div>

        <div class="hhowports_out">
          <div class="hhowports_in">
            <h6>5</h6>
            <h5>Installation</h5>
            <p>
              Skilled professionals
              execute the project
              with precision.
            </p>

          </div>
        </div>

        <div class="hhowports_out">
          <div class="hhowports_in">
            <h6>6</h6>
            <h5>Final Handover
              & Maintenance</h5>
            <p>
              We ensure quality finishing and
              provide guidance for long-term care.
            </p>

          </div>
        </div>





      </div>











    </div>

  </section>
  <!--howewesection-->





  <?php include('footer.php'); ?>

  <?php include('website-js.php'); ?>

</body>

</html>