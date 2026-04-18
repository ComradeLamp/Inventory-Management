<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bicol Depot - Login or Sign Up</title>

    <!--Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!--Custom CSS-->
    <style>
        .hero {
            background: linear-gradient(to right, #007bff, #6610f2);
            color: white;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-outline-light:hover {
            background-color: #ffffff;
            color: #007bff;
        }
    </style>
</head>

<body>
    <!--Navigation Bar-->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Bicol Depot</a>
        </div>
    </nav>

    <!--Hero Section-->
    <header class="hero text-center py-5">
        <div class="container">
            <h1 class="display-4">Welcome to Bicol Depot</h1>
            <p class="lead">Login or create your account to continue</p>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="login.php" class="btn btn-light btn-lg px-4">Login</a>
                <a href="signup.php" class="btn btn-outline-light btn-lg px-4">Sign Up</a>
            </div>
        </div>
    </header>

    <!--Footer-->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <p>&copy; 2025 Bicol Depot. All rights reserved.</p>
        </div>
    </footer>

    <!--Bootstrap JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>