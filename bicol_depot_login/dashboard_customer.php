<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

$welcomeUser = false;
$userName = '';
if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'] === true) {
    $welcomeUser = true;
    $userName = $_SESSION['user']['name'] ?? $_SESSION['user']['first_name'] ?? $_SESSION['user']['username'] ?? 'Customer';
    $_SESSION['just_logged_in'] = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Optima Flow - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue:    #2B5EAB;
            --blue-dk: #1A3F7A;
            --blue-lt: #E8F0FA;
            --gold:    #C9A84C;
            --gold-lt: #F5EDD0;
            --beige:   #F5F0DC;
            --dark:    #0E1F3D;
            --mid:     #2C3A5E;
            --muted:   #6B7A99;
            --white:   #ffffff;
            --card-bg: rgba(255,255,255,0.82);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--beige);
            color: var(--dark);
            min-height: 100vh;
        }

        /* ── NAVBAR ── */
        .navbar {
            background: rgba(245,240,220,0.92) !important;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(43,94,171,0.1);
            padding: 0.9rem 0;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 900;
            color: var(--dark) !important;
            letter-spacing: -0.02em;
        }

        .navbar-brand span { color: var(--blue); }

        .nav-link {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--mid) !important;
            padding: 0.4rem 0.9rem !important;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--blue) !important;
            background: var(--blue-lt);
        }

        .btn-nav-cart {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--blue) !important;
            border: 1.5px solid rgba(43,94,171,0.35) !important;
            border-radius: 6px !important;
            padding: 0.38rem 1rem !important;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-nav-cart:hover {
            background: var(--blue) !important;
            color: white !important;
        }

        .btn-nav-logout {
            font-size: 0.82rem;
            font-weight: 500;
            color: #922B21 !important;
            border: 1.5px solid rgba(146,43,33,0.3) !important;
            border-radius: 6px !important;
            padding: 0.38rem 1rem !important;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-nav-logout:hover {
            background: #922B21 !important;
            color: white !important;
        }

        /* ── HERO ── */
        .hero {
            position: relative;
            padding: 5rem 0 4rem;
            overflow: hidden;
            background: var(--beige);
        }

        .hero-geo {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        .hero-content { position: relative; z-index: 1; }

        .hero-pill {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--blue);
            background: rgba(43,94,171,0.08);
            border: 1px solid rgba(43,94,171,0.2);
            border-radius: 999px;
            padding: 0.35rem 1rem;
            margin-bottom: 1.4rem;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.4rem, 5vw, 4rem);
            font-weight: 900;
            color: var(--dark);
            line-height: 1.1;
            margin-bottom: 1rem;
        }

        .hero h1 em {
            font-style: normal;
            color: var(--blue);
            position: relative;
        }

        .hero h1 em::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -4px;
            height: 3px;
            background: var(--gold);
            border-radius: 2px;
        }

        .hero-sub {
            font-size: 1rem;
            font-weight: 300;
            color: var(--muted);
            margin-bottom: 2rem;
            max-width: 460px;
        }

        .btn-hero-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            color: white;
            background: var(--blue);
            border: none;
            border-radius: 6px;
            padding: 0.8rem 1.8rem;
            text-decoration: none;
            box-shadow: 0 4px 18px rgba(43,94,171,0.3);
            transition: all 0.22s;
        }

        .btn-hero-primary:hover {
            background: var(--blue-dk);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(43,94,171,0.4);
        }

        /* ── STATS ── */
        .stats-section {
            padding: 2.5rem 0;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid rgba(43,94,171,0.1);
            border-radius: 12px;
            padding: 1.8rem 1.5rem;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(14,31,61,0.1);
        }

        .stat-icon {
            font-size: 1.8rem;
            color: var(--blue);
            margin-bottom: 0.8rem;
            display: block;
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            color: var(--dark);
            display: block;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--muted);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        /* ── SECTION TITLE ── */
        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 0.4rem;
            position: relative;
            display: inline-block;
        }

        .section-heading::after {
            content: '';
            position: absolute;
            left: 0; bottom: -8px;
            width: 40px; height: 3px;
            background: var(--gold);
            border-radius: 2px;
        }

        .section-wrap { padding: 3rem 0; }

        /* ── CATEGORY CARDS ── */
        .cat-card {
            background: var(--card-bg);
            border: 1px solid rgba(43,94,171,0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            height: 100%;
        }

        .cat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 40px rgba(14,31,61,0.12);
        }

        .cat-img {
            height: 175px;
            overflow: hidden;
        }

        .cat-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .cat-card:hover .cat-img img { transform: scale(1.08); }

        .cat-body { padding: 1.3rem 1.4rem 1.5rem; }

        .cat-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.45rem;
        }

        .cat-text {
            font-size: 0.83rem;
            color: var(--muted);
            margin-bottom: 1.1rem;
            line-height: 1.6;
        }

        .btn-explore {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--blue);
            background: var(--blue-lt);
            border: 1px solid rgba(43,94,171,0.2);
            border-radius: 6px;
            padding: 0.45rem 1rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-explore:hover {
            background: var(--blue);
            color: white;
            border-color: var(--blue);
        }

        /* ── CTA BANNER ── */
        .cta-banner {
            background: var(--blue);
            border-radius: 14px;
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
            margin: 0 0 3rem;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52'%3E%3Cpolygon points='30,2 58,17 58,47 30,62 2,47 2,17' fill='none' stroke='rgba(255,255,255,0.06)' stroke-width='1'/%3E%3C/svg%3E");
            pointer-events: none;
        }

        .cta-banner h2 {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 0.6rem;
        }

        .cta-banner p {
            color: rgba(255,255,255,0.75);
            font-size: 0.95rem;
            margin-bottom: 1.6rem;
        }

        .btn-cta-light {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--blue);
            background: white;
            border: none;
            border-radius: 6px;
            padding: 0.7rem 1.6rem;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
        }

        .btn-cta-light:hover {
            background: var(--beige);
            color: var(--blue-dk);
            transform: translateY(-2px);
        }

        .btn-cta-outline {
            font-size: 0.88rem;
            font-weight: 500;
            color: white;
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.5);
            border-radius: 6px;
            padding: 0.7rem 1.6rem;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
        }

        .btn-cta-outline:hover {
            border-color: white;
            background: rgba(255,255,255,0.1);
            color: white;
        }

        /* ── PRODUCT CARDS ── */
        .product-card {
            background: var(--card-bg);
            border: 1px solid rgba(43,94,171,0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px rgba(14,31,61,0.12);
        }

        .product-img {
            height: 200px;
            overflow: hidden;
            background: var(--blue-lt);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .product-img .img-placeholder {
            color: rgba(43,94,171,0.2);
            font-size: 3rem;
        }

        .product-card:hover .product-img img { transform: scale(1.05); }

        .product-body {
            padding: 1.2rem 1.3rem 1.4rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.3rem;
        }

        .product-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--blue);
            margin-bottom: 0.5rem;
        }

        .product-desc {
            font-size: 0.8rem;
            color: var(--muted);
            flex: 1;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .btn-view {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--blue);
            background: var(--blue-lt);
            border: 1.5px solid rgba(43,94,171,0.2);
            border-radius: 6px;
            padding: 0.6rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: var(--blue);
            color: white;
            border-color: var(--blue);
        }

        /* ── WELCOME TOAST ── */
        .welcome-toast {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1050;
            width: 320px;
            background: white;
            border: 1px solid rgba(43,94,171,0.15);
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(14,31,61,0.15);
            overflow: hidden;
            opacity: 0;
            transform: translateY(-16px);
            transition: all 0.35s ease;
        }

        .welcome-toast.show { opacity: 1; transform: translateY(0); }

        .toast-header {
            background: var(--blue);
            color: white;
            padding: 0.7rem 1rem;
            font-size: 0.82rem;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toast-body {
            padding: 0.9rem 1rem;
            font-size: 0.85rem;
            color: var(--mid);
        }

        .toast-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            line-height: 1;
            padding: 0;
        }

        /* ── FOOTER ── */
        footer {
            background: var(--dark);
            color: rgba(255,255,255,0.5);
            padding: 2rem 0;
            font-size: 0.8rem;
            text-align: center;
        }

        footer strong { color: var(--gold); }

        footer a {
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            transition: color 0.2s;
        }

        footer a:hover { color: white; }

        /* ── GEO BACKGROUND ── */
        .geo-layer {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="d-flex align-items-center gap-2">
                <a href="assets/img/BPOLD.jpg" target="_blank">
                    <img src="assets/img/BPOLD.jpg" alt="Logo" style="height:36px;" class="img-fluid">
                </a>
                <a class="navbar-brand mb-0" href="dashboard_customer.php">Bicol <span>Depot</span></a>
            </div>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    <li class="nav-item"><a class="nav-link active" href="dashboard_customer.php"><i class="bi bi-house me-1"></i>Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-grid me-1"></i>Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="AboutUS.html"><i class="bi bi-info-circle me-1"></i>About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php"><i class="bi bi-envelope me-1"></i>Contact</a></li>
                    <li class="nav-item ms-2"><a class="btn-nav-cart" href="reservations.php"><i class="bi bi-cart me-1"></i>Cart</a></li>
                    <li class="nav-item ms-1"><a class="btn-nav-logout" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- WELCOME TOAST -->
    <?php if ($welcomeUser): ?>
    <div id="welcomeToast" class="welcome-toast">
        <div class="toast-header">
            <span><i class="bi bi-hand-thumbs-up-fill me-2"></i>Welcome Back!</span>
            <button class="toast-close" onclick="closeWelcomeToast()">&times;</button>
        </div>
        <div class="toast-body">
            Hello, <strong><?= htmlspecialchars($userName) ?></strong>! We're glad to see you again. Happy shopping!
        </div>
    </div>
    <?php endif; ?>

    <!-- HERO -->
    <header class="hero">
        <div class="hero-geo">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="hHex" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                        <polygon points="30,2 58,17 58,47 30,62 2,47 2,17" fill="none" stroke="rgba(43,94,171,0.07)" stroke-width="1"/>
                    </pattern>
                    <pattern id="hDots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <circle cx="20" cy="20" r="1.4" fill="rgba(201,168,76,0.2)"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#hHex)"/>
                <rect width="100%" height="100%" fill="url(#hDots)"/>
                <circle cx="90%" cy="50%" r="260" fill="none" stroke="rgba(43,94,171,0.05)" stroke-width="1.5"/>
                <circle cx="90%" cy="50%" r="180" fill="none" stroke="rgba(43,94,171,0.04)" stroke-width="1"/>
                <circle cx="5%" cy="90%" r="180" fill="none" stroke="rgba(201,168,76,0.08)" stroke-width="1.5"/>
            </svg>
        </div>
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="hero-pill"><i class="bi bi-laptop me-1"></i> Pre-Owned Laptops &amp; PC Parts</span>
                    <h1>Quality Tech,<br><em>Affordable</em> Prices</h1>
                    <p class="hero-sub">Sourced from Japan &amp; US — tested, trusted, and ready to power your setup.</p>
                    <a href="products.php" class="btn-hero-primary">
                        <i class="bi bi-grid-fill"></i> Shop Now
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- STATS -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="stat-card">
                        <span class="stat-icon"><i class="bi bi-laptop"></i></span>
                        <span class="stat-number">20+</span>
                        <span class="stat-label">Products Available</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <span class="stat-icon"><i class="bi bi-people"></i></span>
                        <span class="stat-number">1,000+</span>
                        <span class="stat-label">Satisfied Customers</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <span class="stat-icon"><i class="bi bi-star-fill" style="color:var(--gold)"></i></span>
                        <span class="stat-number">4.8</span>
                        <span class="stat-label">Customer Rating</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURED CATEGORIES -->
    <section class="section-wrap">
        <div class="container">
            <div class="mb-5 mt-1">
                <h2 class="section-heading">Featured Categories</h2>
            </div>
            <div class="row g-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="cat-card">
                        <div class="cat-img"><img src="assets/img/Laptop.jpg" alt="Laptops"></div>
                        <div class="cat-body">
                            <h5 class="cat-title">Laptops</h5>
                            <p class="cat-text">Explore our range of quality pre-owned laptops sourced from Japan and US.</p>
                            <a href="products.php" class="btn-explore"><i class="bi bi-arrow-right-circle"></i> Explore</a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="cat-card">
                        <div class="cat-img"><img src="assets/img/Moba.jpg" alt="Motherboard"></div>
                        <div class="cat-body">
                            <h5 class="cat-title">Motherboard</h5>
                            <p class="cat-text">High-quality motherboards for your custom PC builds and upgrades.</p>
                            <a href="products.php" class="btn-explore"><i class="bi bi-arrow-right-circle"></i> Explore</a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="cat-card">
                        <div class="cat-img"><img src="assets/img/CPU.webp" alt="Processor"></div>
                        <div class="cat-body">
                            <h5 class="cat-title">Processor</h5>
                            <p class="cat-text">Power your system with our selection of reliable processors.</p>
                            <a href="products.php" class="btn-explore"><i class="bi bi-arrow-right-circle"></i> Explore</a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="cat-card">
                        <div class="cat-img"><img src="assets/img/GPU.jpg" alt="Graphics Card"></div>
                        <div class="cat-body">
                            <h5 class="cat-title">Graphics Card</h5>
                            <p class="cat-text">Enhance your gaming and visual experience with our graphics cards.</p>
                            <a href="products.php" class="btn-explore"><i class="bi bi-arrow-right-circle"></i> Explore</a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="cat-card">
                        <div class="cat-img"><img src="assets/img/SSD.jpg" alt="SSD"></div>
                        <div class="cat-body">
                            <h5 class="cat-title">SSD</h5>
                            <p class="cat-text">Boost your system's performance with our fast and reliable SSDs.</p>
                            <a href="products.php" class="btn-explore"><i class="bi bi-arrow-right-circle"></i> Explore</a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="cat-card">
                        <div class="cat-img"><img src="assets/img/PCcase.jpg" alt="PC Case"></div>
                        <div class="cat-body">
                            <h5 class="cat-title">PC Case</h5>
                            <p class="cat-text">Stylish and functional PC cases to house your components.</p>
                            <a href="products.php" class="btn-explore"><i class="bi bi-arrow-right-circle"></i> Explore</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA BANNER -->
    <div class="container">
        <div class="cta-banner text-center">
            <h2>Build Your Dream Setup Today</h2>
            <p>We have all the components you need at affordable prices</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="products.php" class="btn-cta-light">Browse All Products</a>
                <a href="contact.php" class="btn-cta-outline">Get Expert Advice</a>
            </div>
        </div>
    </div>

    <!-- FEATURED PRODUCTS -->
    <section class="section-wrap pt-0">
        <div class="container">
            <div class="mb-5">
                <h2 class="section-heading">Featured Products</h2>
            </div>
            <div class="row g-4">
                <?php
                $products = [
                    ['ASUS ROG Strix B550-F', '₱189.99', 'For AMD Ryzen builds'],
                    ['NVIDIA GeForce RTX 3060', '₱379.99', 'Great performance for 1080p and 1440p gaming'],
                    ['Corsair 4000D', '₱94.99', 'Great airflow and cable management'],
                    ['Samsung 970 EVO Plus 1TB', '₱129.99', 'High-speed NVMe storage'],
                    ['HP Spectre x360', '₱45,000', 'Intel Core i7, 16GB RAM, 1TB SSD'],
                    ['Asus ROG Zephyrus G14', '₱1,399.99', 'Intel Core i7, 16GB RAM, 1TB SSD'],
                    ['Lenovo ThinkPad T14', '₱999.99', 'AMD Ryzen 7, 16GB RAM, 512GB SSD'],
                    ['Intel Core i7-12700K', '₱399.99', 'Intel Core i7 12th Gen'],
                ];
                foreach ($products as $p):
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <div class="product-img">
                            <img src="assets/img/product1" alt="<?= htmlspecialchars($p[0]) ?>" onerror="this.style.display='none';this.parentNode.innerHTML='<i class=\'bi bi-laptop img-placeholder\'></i>'">
                        </div>
                        <div class="product-body">
                            <h5 class="product-title"><?= htmlspecialchars($p[0]) ?></h5>
                            <p class="product-price"><?= $p[1] ?></p>
                            <p class="product-desc"><?= htmlspecialchars($p[2]) ?></p>
                            <a href="products.php" class="btn-view">
                                <i class="bi bi-eye"></i> View Product
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p class="mb-1">&copy; 2025 <strong>Bicol Depot</strong>. All rights reserved.</p>
            <p>
                <a href="privacy.html">Privacy Policy</a>
                <span class="mx-2" style="opacity:0.3;">|</span>
                <a href="terms.html">Terms of Service</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($welcomeUser): ?>
            setTimeout(function() {
                document.getElementById('welcomeToast').classList.add('show');
                setTimeout(closeWelcomeToast, 5000);
            }, 500);
            <?php endif; ?>
        });

        function closeWelcomeToast() {
            const t = document.getElementById('welcomeToast');
            if (!t) return;
            t.classList.remove('show');
            setTimeout(() => t.parentNode && t.parentNode.removeChild(t), 350);
        }
    </script>
</body>
</html>