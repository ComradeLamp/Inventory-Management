<?php
session_start();
include 'db.php';

$sql = "SELECT * FROM products ORDER BY category, name";
$result = mysqli_query($conn, $sql);

$allProducts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $allProducts[$row['category']][] = $row;
}

$orderedCategories = ['Laptop', 'GPU', 'CPU', 'SSD', 'Motherboard', 'PC Case'];

$categoryIcons = [
    'Laptop'      => 'bi-laptop',
    'GPU'         => 'bi-gpu-card',
    'CPU'         => 'bi-cpu',
    'SSD'         => 'bi-device-ssd',
    'Motherboard' => 'bi-motherboard',
    'PC Case'     => 'bi-pc-display',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Products - Bicol Depot</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue:    #2B5EAB;
            --blue-dk: #1A3F7A;
            --blue-lt: #E8F0FA;
            --gold:    #C9A84C;
            --beige:   #F5F0DC;
            --dark:    #0E1F3D;
            --mid:     #2C3A5E;
            --muted:   #6B7A99;
            --card-bg: rgba(255,255,255,0.85);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--beige);
            color: var(--dark);
            min-height: 100vh;
        }

        /* ── GEO BACKGROUND ── */
        .geo-fixed { 
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
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

        .btn-nav-cart:hover { background: var(--blue) !important; color: white !important; }

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

        .btn-nav-logout:hover { background: #922B21 !important; color: white !important; }

        /* ── PAGE HEADER ── */
        .page-header {
            position: relative;
            z-index: 1;
            padding: 3.5rem 0 2.5rem;
            text-align: center;
        }

        .page-header-pill {
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
            margin-bottom: 1rem;
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .page-header h1 span {
            color: var(--blue);
            position: relative;
        }

        .page-header h1 span::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -4px;
            height: 3px;
            background: var(--gold);
            border-radius: 2px;
        }

        .page-header p {
            color: var(--muted);
            font-size: 0.95rem;
            font-weight: 300;
        }

        /* ── CATEGORY SECTION ── */
        .category-block {
            position: relative;
            z-index: 1;
            margin-bottom: 3.5rem;
        }

        .category-label {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin-bottom: 1.4rem;
        }

        .category-label h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--dark);
            margin: 0;
        }

        .cat-icon-badge {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: var(--blue-lt);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--blue);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .cat-divider {
            flex: 1;
            height: 1px;
            background: rgba(43,94,171,0.12);
            margin-left: 0.5rem;
        }

        /* ── PRODUCT CARD ── */
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
            height: 190px;
            overflow: hidden;
            background: var(--blue-lt);
        }

        .product-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .product-card:hover .product-img img { transform: scale(1.06); }

        .product-body {
            padding: 1.1rem 1.2rem 1.3rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.35rem;
            line-height: 1.35;
        }

        .product-desc {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.55;
            flex: 1;
            margin-bottom: 0.8rem;
        }

        .product-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--blue);
            margin-bottom: 0.25rem;
        }

        .product-stock {
            font-size: 0.75rem;
            color: var(--muted);
            margin-bottom: 0.9rem;
        }

        .product-stock.low { color: #C0392B; }
        .product-stock.out { color: #999; }

        /* ── RESERVE FORM ── */
        .reserve-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .qty-input {
            width: 64px;
            padding: 0.5rem 0.6rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            color: var(--dark);
            background: var(--beige);
            border: 1.5px solid rgba(43,94,171,0.2);
            border-radius: 6px;
            text-align: center;
            transition: border-color 0.2s;
        }

        .qty-input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(43,94,171,0.1);
        }

        .btn-reserve {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            color: white;
            background: var(--blue);
            border: none;
            border-radius: 6px;
            padding: 0.55rem 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-reserve:hover:not(:disabled) {
            background: var(--blue-dk);
            transform: translateY(-1px);
        }

        .btn-reserve:disabled {
            background: #ccc;
            cursor: not-allowed;
            color: #888;
        }

        .btn-reserve.out-of-stock {
            background: rgba(43,94,171,0.08);
            color: var(--muted);
            border: 1px solid rgba(43,94,171,0.15);
        }

        /* ── FOOTER ── */
        footer {
            position: relative;
            z-index: 1;
            background: var(--dark);
            color: rgba(255,255,255,0.5);
            padding: 2rem 0;
            font-size: 0.8rem;
            text-align: center;
            margin-top: 2rem;
        }

        footer strong { color: var(--gold); }

        .main-content {
            position: relative;
            z-index: 1;
        }

        /* ── FILTER BAR ── */
        .filter-wrap {
            position: relative;
            z-index: 1;
            margin: 0 auto 2rem;
            max-width: 1100px;
            background: rgba(255,255,255,0.78);
            border: 1px solid rgba(43,94,171,0.12);
            border-radius: 12px;
            padding: 1rem;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .filter-label {
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--mid);
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .filter-select {
            width: 100%;
            border: 1.5px solid rgba(43,94,171,0.18);
            border-radius: 8px;
            background: rgba(245,240,220,0.55);
            color: var(--dark);
            padding: 0.58rem 0.75rem;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(43,94,171,0.1);
            background: #fff;
        }

        .filter-reset {
            width: 100%;
            border: 1.5px solid rgba(43,94,171,0.3);
            border-radius: 8px;
            background: transparent;
            color: var(--blue);
            font-size: 0.83rem;
            font-weight: 600;
            padding: 0.58rem 0.75rem;
            transition: all 0.2s;
        }

        .filter-reset:hover {
            background: var(--blue);
            color: #fff;
        }

        .empty-filter {
            display: none;
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
            padding: 1.25rem 0;
        }
    </style>
</head>
<body>

    <!-- GEO BACKGROUND -->
    <div class="geo-fixed">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="gHex" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                    <polygon points="30,2 58,17 58,47 30,62 2,47 2,17" fill="none" stroke="rgba(43,94,171,0.06)" stroke-width="1"/>
                </pattern>
                <pattern id="gDots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <circle cx="20" cy="20" r="1.3" fill="rgba(201,168,76,0.18)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#gHex)"/>
            <rect width="100%" height="100%" fill="url(#gDots)"/>
            <circle cx="92%" cy="8%" r="280" fill="none" stroke="rgba(43,94,171,0.04)" stroke-width="1.5"/>
            <circle cx="92%" cy="8%" r="200" fill="none" stroke="rgba(43,94,171,0.03)" stroke-width="1"/>
            <circle cx="5%" cy="95%" r="240" fill="none" stroke="rgba(201,168,76,0.07)" stroke-width="1.5"/>
        </svg>
    </div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="d-flex align-items-center gap-2">
                <a href="assets/img/BPOLD.jpg" target="_blank">
                    <img src="assets/img/BPOLD.jpg" alt="Logo" style="height:36px;" class="img-fluid">
                </a>
                <a class="navbar-brand mb-0" href="dashboard_customer.php">Optima <span>Flow</span></a>
            </div>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    <li class="nav-item"><a class="nav-link" href="dashboard_customer.php"><i class="bi bi-house me-1"></i>Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="products.php"><i class="bi bi-grid me-1"></i>Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="AboutUS.html"><i class="bi bi-info-circle me-1"></i>About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php"><i class="bi bi-envelope me-1"></i>Contact</a></li>
                    <li class="nav-item ms-2"><a class="btn-nav-cart" href="reservations.php"><i class="bi bi-cart me-1"></i>Cart</a></li>
                    <li class="nav-item ms-1"><a class="btn-nav-logout" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <span class="page-header-pill"><i class="bi bi-grid me-1"></i>All Products</span>
        <h1>Browse Our <span>Products</span></h1>
        <p>Quality pre-owned tech — laptops, parts & components</p>
    </div>

    <div class="container">
        <div class="filter-wrap">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="filter-label" for="filterCategory">Category</label>
                    <select id="filterCategory" class="filter-select">
                        <option value="all">All Categories</option>
                        <?php foreach ($orderedCategories as $category): ?>
                            <option value="<?= htmlspecialchars(strtolower($category)) ?>"><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="filter-label" for="filterPrice">Price</label>
                    <select id="filterPrice" class="filter-select">
                        <option value="default">Default Order</option>
                        <option value="asc">Price: Lowest to Highest</option>
                        <option value="desc">Price: Highest to Lowest</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="button" id="filterReset" class="filter-reset">Reset Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PRODUCTS -->
    <div class="main-content">
        <div class="container pb-5">
            <?php foreach ($orderedCategories as $category): ?>
                <?php if (isset($allProducts[$category])): ?>
                <div class="category-block" data-category-block="<?= htmlspecialchars(strtolower($category)) ?>">
                    <div class="category-label">
                        <div class="cat-icon-badge">
                            <i class="bi <?= $categoryIcons[$category] ?? 'bi-box' ?>"></i>
                        </div>
                        <h3><?= htmlspecialchars($category) ?></h3>
                        <div class="cat-divider"></div>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($allProducts[$category] as $product):
                            $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $product['name']) . '.jpg';
                            $imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : 'assets/img/placeholder.jpg';
                            $inStock = $product['quantity'] > 0;
                            $lowStock = $product['quantity'] > 0 && $product['quantity'] <= 3;
                        ?>
                        <div class="col-sm-6 col-md-4 col-lg-3 product-item"
                            data-category="<?= htmlspecialchars(strtolower($product['category'])) ?>"
                            data-name="<?= htmlspecialchars(strtolower($product['name'])) ?>"
                            data-description="<?= htmlspecialchars(strtolower($product['description'])) ?>"
                            data-price="<?= htmlspecialchars($product['price']) ?>">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                </div>
                                <div class="product-body">
                                    <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>
                                    <p class="product-price">₱<?= number_format($product['price'], 2) ?></p>
                                    <p class="product-stock <?= !$inStock ? 'out' : ($lowStock ? 'low' : '') ?>">
                                        <i class="bi <?= $inStock ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                                        <?php if (!$inStock): ?>Out of Stock
                                        <?php elseif ($lowStock): ?>Only <?= $product['quantity'] ?> left!
                                        <?php else: ?>Available: <?= $product['quantity'] ?>
                                        <?php endif; ?>
                                    </p>
                                    <form action="reserve.php" method="POST">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <div class="reserve-row">
                                            <input type="number" name="quantity" value="1" min="1"
                                                max="<?= $product['quantity'] ?>"
                                                class="qty-input"
                                                <?= !$inStock ? 'disabled' : '' ?>>
                                            <button type="submit" class="btn-reserve <?= !$inStock ? 'out-of-stock' : '' ?>"
                                                <?= !$inStock ? 'disabled' : '' ?>>
                                                <i class="bi <?= $inStock ? 'bi-bag-plus' : 'bi-x' ?>"></i>
                                                <?= $inStock ? 'Reserve' : 'Out of Stock' ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div id="emptyFilterState" class="empty-filter">
                <i class="bi bi-search me-1"></i>No products found for your filters.
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p>&copy; 2025 <strong>Bicol Depot</strong>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const filterCategory = document.getElementById('filterCategory');
        const filterPrice = document.getElementById('filterPrice');
        const filterReset = document.getElementById('filterReset');
        const productItems = Array.from(document.querySelectorAll('.product-item'));
        const categoryBlocks = Array.from(document.querySelectorAll('[data-category-block]'));
        const emptyFilterState = document.getElementById('emptyFilterState');

        function productMatchesFilters(item) {
            const category = filterCategory.value;

            const itemCategory = item.dataset.category || '';
            const categoryMatch = category === 'all' || itemCategory === category;

            return categoryMatch;
        }

        function applyPriceSort() {
            const sortOrder = filterPrice.value;
            if (sortOrder === 'default') return;

            categoryBlocks.forEach((block) => {
                const row = block.querySelector('.row.g-4');
                if (!row) return;

                const items = Array.from(row.querySelectorAll('.product-item'));
                items.sort((a, b) => {
                    const priceA = parseFloat(a.dataset.price || '0');
                    const priceB = parseFloat(b.dataset.price || '0');
                    return sortOrder === 'asc' ? priceA - priceB : priceB - priceA;
                });

                items.forEach((item) => row.appendChild(item));
            });
        }

        function applyFilters() {
            productItems.forEach((item) => {
                item.style.display = productMatchesFilters(item) ? '' : 'none';
            });

            applyPriceSort();

            let visibleCount = 0;
            categoryBlocks.forEach((block) => {
                const hasVisibleItems = Array.from(block.querySelectorAll('.product-item')).some((item) => item.style.display !== 'none');
                block.style.display = hasVisibleItems ? '' : 'none';
                if (hasVisibleItems) visibleCount++;
            });

            emptyFilterState.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        filterCategory.addEventListener('change', applyFilters);
        filterPrice.addEventListener('change', applyFilters);

        filterReset.addEventListener('click', () => {
            filterCategory.value = 'all';
            filterPrice.value = 'default';
            applyFilters();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>