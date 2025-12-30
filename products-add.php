<?php
ob_start();
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Add Products</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <?php include('website-link.php'); ?>

    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #0d6efd, #6f42c1);
            color: white;
            padding: 30px 0;
            margin-top: 20px;
        }

        .dashboard-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
        }

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }
    </style>
</head>

<body>

    <?php
    $index = true;
    include('header.php');
    ?>

    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="welcome-section">
                <h1>Add Products</h1>
                <div>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">ADD NEW PRODUCT</h5>
                        <p class="card-text">You are successfully logged in!</p>
                        <p class="text-muted">
                            <strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?><br>
                            <strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <?php include('website-js.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>