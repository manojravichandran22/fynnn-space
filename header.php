<!--headerout-->
<?php
// Auto-logout if visiting a public page while logged in
if (isset($_SESSION['user_id'])) {
    // Specifically check for boolean true to avoid conflicts with data variables like $products
    $is_public_page = (isset($index) && $index === true) || 
                      (isset($about) && $about === true) || 
                      (isset($services) && $services === true) || 
                      (isset($projects) && $projects === true) || 
                      (isset($products) && $products === true) || 
                      (isset($contact) && $contact === true);
    
    if ($is_public_page) {
        session_destroy();
        $_SESSION = array();
        // Redirect to same page but now logged out
       // header("Location: " . $_SERVER['PHP_SELF']);
       header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));

        exit();
    }
}
?>
<div class="headerbg">

<div class="topheader_outer">
      <div class="container-fluid">

    <nav
      class="navbar navbar-expand-lg navbar-light"
      aria-label="Main navigation"
    >
    <a class="navbar-brand" href="/"><img src="images/logo.jpg" title=" Logo" alt='Logo"' /></a>


<button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false"  aria-label=" Menu"
          title=" Menu">
          <span class="navbar-toggler-icon"></span>
                <span class="navbar-toggler-icon"></span>
                <span class="navbar-toggler-icon"></span>
        </button>

        <div
          class="navbar-collapse collapse ms-auto"
          id="navbarsExampleDefault">
        
<ul class="navbar-nav ml-auto mb-2 mb-lg-0">
          
          
          
        <li class="nav-item ">
          <a class="nav-link <?php if(isset($index)) { echo ' active '; } ?> "  href="/"><span>Home</span></a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(isset($about)) { echo ' active '; } ?>  " href="/about-us"><span>About Us</span></a>
        </li>
			
         <li class="nav-item">
          <a class="nav-link <?php if(isset($services)) { echo ' active '; } ?>  "   href="/services"><span>Services</span></a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link k <?php if(isset($projects)) { echo ' active '; } ?>  "   href="/projects"><span>Projects</span></a>
        </li>
        
          <li class="nav-item">
          <a class="nav-link k <?php if(isset($products)) { echo ' active '; } ?>  "   href="/products"><span>Products</span></a>
        </li>
       
        <li class="nav-item">
          <a class="nav-link <?php if(isset($contact)) { echo ' active '; } ?>  " href="/contact-us"><span>Contact Us</span></a>
        </li>
          
          

          </ul>
          

          <div class="last_list" style="display: flex; align-items: center;">
            <div class="reqcallout">

<div class="reqcall"><div class="topicons"><img src="images/top-mob.png" /></div>
<a  href="tel:+919514991999">+91 9514991999</a></div>
<div class="reqcall">
  <!-- <div class="topicons2 ">
    <a href="mailto:info@fynn.com">
      <img src="images/top-email.png" />
    </a>
  </div> -->
<?php /*?><a style="display:none" href="mailto:info@fynn.com">info@fynn.com</a><?php */?></div>


</div>


                    
                    </div>


                
          
          
          
          
        </div>
        
        
        
        
        
        
    </nav>

      </div>

</div>