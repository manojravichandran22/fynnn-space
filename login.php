<?php
ob_start();
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Your common website css -->
  <?php include('website-link.php'); ?>

  <style>
    body {
      margin: 0;
      padding: 0;
    }

    .login-section {
      min-height: 100vh;
      background: linear-gradient(135deg, #0d6efd, #6f42c1);
      display: flex;
      align-items: center;
    }

    .login-card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .login-card h3 {
      font-weight: 600;
    }

    .input-group-text {
      background-color: #f1f1f1;
      cursor: pointer;
    }
  </style>
</head>

<body>

<?php
$index = true;
include('header.php');
?>

<section class="login-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-4 col-md-6 col-sm-10">
        <div class="login-card p-4">

          <h3 class="text-center mb-2">Welcome Back... Manoj</h3>
          <p class="text-center text-muted mb-4">Login to continue</p>

          <!-- Show error message -->
          <?php if (isset($_SESSION['error'])) { ?>
            <div class="alert alert-danger text-center">
              <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']); 
              ?>
            </div>
          <?php } ?>

          <form action="login_process.php" method="post">

            <!-- Username -->
            <div class="mb-3">
              <label class="form-label">Username</label>
              <div class="input-group">
                <span class="input-group-text">
                  <i class="bi bi-person"></i>
                </span>
                <input type="text" class="form-control" name="username" placeholder="Enter username" required>
              </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text">
                  <i class="bi bi-lock"></i>
                </span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                <span class="input-group-text" onclick="togglePassword()">
                  <i class="bi bi-eye" id="eyeIcon"></i>
                </span>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember">
                <label class="form-check-label" for="remember">
                  Remember me
                </label>
              </div>
              <a href="#" class="text-decoration-none small">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100">
              Login
            </button>

          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('footer.php'); ?>
<?php include('website-js.php'); ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function togglePassword() {
  const password = document.getElementById("password");
  const eyeIcon = document.getElementById("eyeIcon");

  if (password.type === "password") {
    password.type = "text";
    eyeIcon.classList.remove("bi-eye");
    eyeIcon.classList.add("bi-eye-slash");
  } else {
    password.type = "password";
    eyeIcon.classList.remove("bi-eye-slash");
    eyeIcon.classList.add("bi-eye");
  }
}
</script>

</body>
</html>
