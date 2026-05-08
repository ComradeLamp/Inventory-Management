<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// =========================================================
// SQL QUERIES - WRITE YOUR ANALYTICS QUERIES HERE
// =========================================================

// Example 1: Products by category (count)
$categoryQuery = "SELECT category, COUNT(*) as count FROM products GROUP BY category";
$categoryResult = $conn->query($categoryQuery);
$categoryData = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryData[] = $row;
}

// Example 2: Orders by status
$statusQuery = "SELECT status, COUNT(*) as count FROM reservations GROUP BY status";
$statusResult = $conn->query($statusQuery);
$statusData = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusData[] = $row;
}

// Example 3: Stock levels (all products with their quantity)
$stockQuery = "SELECT name, quantity FROM products ORDER BY quantity ASC";
$stockResult = $conn->query($stockQuery);
$stockData = [];
while ($row = $stockResult->fetch_assoc()) {
    $stockData[] = $row;
}

// Example 4: Reservations over time (last 30 days)
$timeQuery = "SELECT DATE(reserved_at) as date, COUNT(*) as count 
              FROM reservations 
              WHERE reserved_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              GROUP BY DATE(reserved_at) 
              ORDER BY date ASC";
$timeResult = $conn->query($timeQuery);
$timeData = [];
while ($row = $timeResult->fetch_assoc()) {
    $timeData[] = $row;
}

// Example 5: Top 5 selling products (only approved/fulfilled)
$topQuery = "SELECT p.name, SUM(r.quantity) as total_sold
             FROM reservations r
             JOIN products p ON r.product_id = p.id
             WHERE r.status IN ('approved', 'fulfilled')
             GROUP BY p.id, p.name
             ORDER BY total_sold DESC
             LIMIT 5";
$topResult = $conn->query($topQuery);
$topData = [];
while ($row = $topResult->fetch_assoc()) {
    $topData[] = $row;
}

// Example 6: Revenue by category (only approved/fulfilled)
$revenueQuery = "SELECT p.category, SUM(r.quantity * p.price) as revenue
                 FROM reservations r
                 JOIN products p ON r.product_id = p.id
                 WHERE r.status IN ('approved', 'fulfilled')
                 GROUP BY p.category";
$revenueResult = $conn->query($revenueQuery);
$revenueData = [];
while ($row = $revenueResult->fetch_assoc()) {
    $revenueData[] = $row;
}

// Example 7: Low stock count
$lowStockQuery = "SELECT COUNT(*) as count FROM products WHERE quantity < 5";
$lowStockResult = $conn->query($lowStockQuery);
$lowStockCount = $lowStockResult->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Analytics - OptimaFlow</title>

    <!--Font Awesome (icons)-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!--Chart.js-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!--Analytics page styles-->
    <link rel="stylesheet" href="assets/css/admin/analytics.css" />
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">OptimaFlow Admin</div>
            <nav class="nav">
                <a href="dashboard_admin.php" class="nav-item"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="admin_products.php" class="nav-item"><i class="fa-solid fa-box"></i> Products</a>
                <a href="admin_users.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
                <a href="admin_order.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Orders</a>
                <a href="admin_analytics.php" class="nav-item active"><i class="fa-solid fa-chart-line"></i> Analytics</a>
                <form action="logout.php" method="post" class="logout-form">
                    <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main">
            <header class="header">
                <h1>Analytics</h1>
                <div class="search-avatar">
                    <div class="avatar">
                        <i class="fa-solid fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?>
                    </div>
                </div>
            </header>

            <!-- Pass PHP data to JavaScript using JSON -->
            <script>
                const analyticsData = {
                    categories: <?php echo json_encode($categoryData); ?>,
                    statuses: <?php echo json_encode($statusData); ?>,
                    stock: <?php echo json_encode($stockData); ?>,
                    timeline: <?php echo json_encode($timeData); ?>,
                    topProducts: <?php echo json_encode($topData); ?>,
                    revenue: <?php echo json_encode($revenueData); ?>,
                    lowStockCount: <?php echo $lowStockCount; ?>
                };
            </script>

            <!-- Chart cards grid -->
            <section class="chart-grid">
                <div class="chart-card">
                    <h2 class="chart-title">Products by Category</h2>
                    <canvas id="categoryChart"></canvas>
                </div>

                <div class="chart-card">
                    <h2 class="chart-title">Orders by Status</h2>
                    <canvas id="statusChart"></canvas>
                </div>

                <div class="chart-card chart-card-wide">
                    <h2 class="chart-title">Stock Levels by Product</h2>
                    <canvas id="stockChart"></canvas>
                </div>

                <div class="chart-card chart-card-wide">
                    <h2 class="chart-title">Reservations - Last 30 Days</h2>
                    <canvas id="timelineChart"></canvas>
                </div>

                <div class="chart-card">
                    <h2 class="chart-title">Top 5 Selling Products</h2>
                    <canvas id="topProductsChart"></canvas>
                </div>

                <div class="chart-card">
                    <h2 class="chart-title">Revenue by Category</h2>
                    <canvas id="revenueChart"></canvas>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/admin/analytics.js"></script>
</body>
</html>

<?php 
$conn->close(); 
?>