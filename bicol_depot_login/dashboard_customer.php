<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

//Check if this is a new login (Customer)
$welcomeUser = false;
$userName = '';
if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'] === true) {
    $welcomeUser = true;
    $userName = $_SESSION['user']['name'] ?? $_SESSION['user']['first_name'] ?? 'Customer';
    $_SESSION['just_logged_in'] = false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bits & Bytes - Dashboard</title>

    <!--Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!--BS ICONS CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!--Custom CSS-->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /*General Styles*/
        body {
            background-color: #f8f9fa;
            color: #343a40;
        }

        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #0d6efd;
        }

        /*Hero Section*/
        .hero {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            padding: 4rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/img/circuit-pattern.png');
            opacity: 0.1;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero .btn-hero {
            padding: 0.8rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .hero .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        /*Stats Section*/
        .stats-section {
            background-color: #ffffff;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            position: relative;
            border-right: 1px solid #e9ecef;
        }

        .stat-card:last-child {
            border-right: none;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #343a40;
        }

        .stat-text {
            font-size: 1rem;
            color: #6c757d;
        }

        /*Categories Section*/
        .category-section {
            padding: 2rem 0;
        }

        .category-card {
            transition: all 0.3s ease;
            overflow: hidden;
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            height: 100%;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .category-card .card-img-container {
            height: 180px;
            overflow: hidden;
        }

        .category-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .category-card:hover img {
            transform: scale(1.1);
        }

        .category-card .card-body {
            padding: 1.5rem;
        }

        .category-card .card-title {
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .category-card .card-text {
            color: #6c757d;
            margin-bottom: 1.25rem;
        }

        .category-card .btn {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
        }

        /*Featured Products Section*/
        .featured-products {
            padding: 3rem 0;
            background-color: #f8f9fa;
        }

        .product-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .product-card .card-img-container {
            height: 220px;
            overflow: hidden;
        }

        .product-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover img {
            transform: scale(1.05);
        }

        .product-card .card-body {
            padding: 1.5rem;
        }

        .product-card .card-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .product-card .price {
            color: #0d6efd;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .product-card .btn {
            border-radius: 50px;
            padding: 0.5rem 0;
            font-weight: 600;
            width: 100%;
        }

        .product-card .btn-view {
            background-color: #ffffff;
            border: 2px solid #0d6efd;
            color: #0d6efd;
        }

        .product-card .btn-view:hover {
            background-color: #0d6efd;
            color: #ffffff;
        }

        /*Call to Action Section*/
        .cta-section {
            background: linear-gradient(135deg, #0099ff 0%, #0d6efd 100%);
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            border-radius: 12px;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/img/circuit-pattern.png');
            opacity: 0.1;
            z-index: 0;
        }

        .cta-content {
            position: relative;
            z-index: 1;
        }

        .cta-section h2 {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .cta-section .btn {
            padding: 0.8rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .cta-section .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        /*Welcome User*/
        .welcome-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 350px;
            background-color: #fff;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }

        .welcome-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .welcome-header {
            background-color: #1a4b84;
            color: white;
            padding: 0.75rem 1rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-body {
            padding: 1rem;
        }

        .close-toast {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
        }

        /*Footer*/
        footer {
            background-color: #212529;
            padding: 3rem 0 2rem;
        }

        footer .footer-links {
            margin-bottom: 1.5rem;
        }

        footer .footer-links a {
            margin: 0 1rem;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.2s ease;
        }

        footer .footer-links a:hover {
            color: #ffffff;
            text-decoration: none;
        }

        footer .social-links {
            margin-bottom: 1.5rem;
        }

        footer .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            margin: 0 0.5rem;
            transition: all 0.2s ease;
        }

        footer .social-links a:hover {
            background-color: #0d6efd;
            transform: translateY(-3px);
        }

        /*Responsive Adjustments*/
        @media (max-width: 992px) {
            .stat-card {
                border-right: none;
                border-bottom: 1px solid #e9ecef;
                padding: 1rem;
            }

            .stat-card:last-child {
                border-bottom: none;
            }
        }

        @media (max-width: 767px) {
            .hero {
                padding: 3rem 0;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-icon {
                font-size: 2rem;
            }

            .stat-number {
                font-size: 1.5rem;
            }

            .category-card .card-img-container {
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <!--Navigation Bar-->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <div class="d-flex align-items-center">
                <a href="assets/img/BPOLD.jpg" target="_blank">
                    <img src="assets/img/BPOLD.jpg" alt="Logo" style="height: 40px;" class="me-2 img-fluid">
                </a>
                <a class="navbar-brand mb-0 h1">Bits & Bytes</a>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard_customer.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="AboutUS.html">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item"><a class="btn btn-outline-primary me-2" href="reservations.php"><i class="bi bi-cart"></i> Cart</a></li>
                    <li class="nav-item"><a class="btn btn-outline-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!--Welcome User-->
    <?php if ($welcomeUser): ?>
        <div id="welcomeToast" class="welcome-toast">
            <div class="welcome-header">
                <span><i class="bi bi-hand-thumbs-up-fill me-2"></i>Welcome Back!</span>
                <button type="button" class="close-toast" onclick="closeWelcomeToast()">&times;</button>
            </div>
            <div class="welcome-body">
                <p class="mb-0">Hello, <?= htmlspecialchars($userName) ?>! We're glad to see you again. Happy shopping!</p>
            </div>
        </div>
    <?php endif; ?>

    <!--Hero Section-->
    <header class="hero">
        <div class="container hero-content text-center">
            <h1 class="display-4 fw-bold text-white mb-3">Welcome to Bits & Bytes</h1>
            <p class="lead text-white mb-4">Quality & Affordable Pre-Owned Laptops from Japan & US</p>
            <a href="/bicol_depot_login/products.php" class="btn btn-light btn-lg btn-hero">
                <i class="bi bi-laptop me-2"></i>Shop Now
            </a>
        </div>
    </header>

    <!--Stats Section-->
    <section class="container stats-section">
        <div class="row">
            <div class="col-md-4 stat-card">
                <div class="stat-icon">
                    <i class="bi bi-laptop"></i>
                </div>
                <div class="stat-number">20+</div>
                <div class="stat-text">Products Available</div>
            </div>
            <div class="col-md-4 stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-number">1,000+</div>
                <div class="stat-text">Satisfied Customers</div>
            </div>
            <div class="col-md-4 stat-card">
                <div class="stat-icon">
                    <i class="bi bi-star"></i>
                </div>
                <div class="stat-number">4.8</div>
                <div class="stat-text">Customer Rating</div>
            </div>
        </div>
    </section>

    <!--Featured Categories Section-->
    <section class="category-section">
        <div class="container">
            <h2 class="section-title fw-bold mb-4">Featured Categories</h2>
            <div class="row g-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="category-card card">
                        <div class="card-img-container">
                            <img src="assets/img/Laptop.jpg" class="card-img-top" alt="Laptops">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Laptops</h5>
                            <p class="card-text">Explore our range of quality pre-owned laptops sourced from Japan and US.</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i> Explore
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="category-card card">
                        <div class="card-img-container">
                            <img src="assets/img/Moba.jpg" class="card-img-top" alt="Motherboard">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Motherboard</h5>
                            <p class="card-text">High-quality motherboards for your custom PC builds and upgrades.</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i> Explore
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="category-card card">
                        <div class="card-img-container">
                            <img src="assets/img/CPU.webp" class="card-img-top" alt="Processor">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Processor</h5>
                            <p class="card-text">Power your system with our selection of reliable processors.</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i> Explore
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="category-card card">
                        <div class="card-img-container">
                            <img src="assets/img/GPU.jpg" class="card-img-top" alt="Graphics Card">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Graphics Card</h5>
                            <p class="card-text">Enhance your gaming and visual experience with our graphics cards.</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i> Explore
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="category-card card">
                        <div class="card-img-container">
                            <img src="assets/img/SSD.jpg" class="card-img-top" alt="SSD">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">SSD</h5>
                            <p class="card-text">Boost your system's performance with our fast and reliable SSDs.</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i> Explore
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="category-card card">
                        <div class="card-img-container">
                            <img src="assets/img/PCcase.jpg" class="card-img-top" alt="PC Case">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">PC Case</h5>
                            <p class="card-text">Stylish and functional PC cases to house your components.</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i> Explore
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--CtA-->
    <section class="container">
        <div class="cta-section">
            <div class="container text-center cta-content text-white">
                <h2 class="fw-bold mb-3">Build Your Dream Setup Today</h2>
                <p class="lead mb-4">We have all the components you need at affordable prices</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="products.php" class="btn btn-light">Browse All Products</a>
                    <a href="contact.php" class="btn btn-outline-light">Get Expert Advice</a>
                </div>
            </div>
        </div>
    </section>

    <!--Featured Products Section-->
    <section class="featured-products py-5">
        <div class="container">
            <h2 class="section-title fw-bold mb-4">Featured Products</h2>
            <div class="row g-4">
                <!--Product Cards-->
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card card h-100">
                        <div class="card-img-container">
                            <img src="assets/img/product1" class="card-img-top" alt="Product Name">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">ASUS ROG Strix B550-F</h5>
                            <p class="price">₱189.99</p>
                            <p class="card-text flex-grow-1">For AMD Ryzen builds</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary mt-auto">
                                <i class="bi bi-cart-plus me-1"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card card h-100">
                        <div class="card-img-container">
                            <img src="assets/img/product1" class="card-img-top" alt="Product Name">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">NVIDIA GeForce RTX 3060</h5>
                            <p class="price">₱379.99</p>
                            <p class="card-text flex-grow-1">Great performance for 1080p and 1440p gaming</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary mt-auto">
                                <i class="bi bi-cart-plus me-1"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card card h-100">
                        <div class="card-img-container">
                            <img src="assets/img/product1" class="card-img-top" alt="Product Name">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Corsair 4000D</h5>
                            <p class="price">₱94.99</p>
                            <p class="card-text flex-grow-1">Great airflow and cable management</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary mt-auto">
                                <i class="bi bi-cart-plus me-1"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card card h-100">
                        <div class="card-img-container">
                            <img src="assets/img/product1" class="card-img-top" alt="Product Name">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Samsung 970 EVO Plus 1TB</h5>
                            <p class="price">₱129.99</p>
                            <p class="card-text flex-grow-1">High-speed NVMe storage</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary mt-auto">
                                <i class="bi bi-cart-plus me-1"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card card h-100">
                        <div class="card-img-container">
                            <img src="assets/img/product1" class="card-img-top" alt="Product Name">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">HP Spectre x360</h5>
                            <p class="price">₱45,000</p>
                            <p class="card-text flex-grow-1">Intel Core i7, 16GB RAM, 1TB SSD</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary mt-auto">
                                <i class="bi bi-cart-plus me-1"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card card h-100">
                        <div class="card-img-container">
                            <img src="assets/img/product1" class="card-img-top" alt="Product Name">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Asus ROG Zephyrus G14</h5>
                            <p class="price">₱1399.99</p>
                            <p class="card-text flex-grow-1">Intel Core i7, 16GB RAM, 1TB SSD</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary mt-auto">
                                <i class="bi bi-cart-plus me-1"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card card h-100">
                        <div class="card-img-container">
                            <img src="assets/img/product1" class="card-img-top" alt="Product Name">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Lenovo ThinkPad T14</h5>
                            <p class="price">₱999.99</p>
                            <p class="card-text flex-grow-1">AMD Ryzen 7, 16GB RAM, 512GB SSD</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary mt-auto">
                                <i class="bi bi-cart-plus me-1"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card card h-100">
                        <div class="card-img-container">
                            <img src="assets/img/product1" class="card-img-top" alt="Product Name">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Intel Core i7-12700K</h5>
                            <p class="price">₱399.99</p>
                            <p class="card-text flex-grow-1">Intel Core i7 12th Gen</p>
                            <a href="/bicol_depot_login/products.php" class="btn btn-primary mt-auto">
                                <i class="bi bi-cart-plus me-1"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--Footer-->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 Bicol Pro-Owned Laptop Depot. All rights reserved.</p>
            <p>
                <a href="privacy.html" class="text-white">Privacy Policy</a> |
                <a href="terms.html" class="text-white">Terms of Service</a>
            </p>
        </div>
    </footer>

    <!--Bootstrap JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!--Custom JS-->
    <script src="js/script.js"></script>

    <script>
        //Welcome toast functionality
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($welcomeUser): ?>
                //Show welcome on page load
                setTimeout(function() {
                    document.getElementById('welcomeToast').classList.add('show');

                    //Auto hide after 5 seconds
                    setTimeout(function() {
                        closeWelcomeToast();
                    }, 5000);
                }, 500);
            <?php endif; ?>
        });

        function closeWelcomeToast() {
            const toast = document.getElementById('welcomeToast');
            toast.classList.remove('show');

            //Remove from DOM after animation completes
            setTimeout(function() {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    </script>
</body>

</html>