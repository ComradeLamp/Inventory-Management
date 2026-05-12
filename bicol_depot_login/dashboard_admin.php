<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

//Get le basic stats for le dashboard
$totalProductsQuery = "SELECT COUNT(*) as total FROM products";
$totalProductsResult = $conn->query($totalProductsQuery);
$totalProducts = $totalProductsResult->fetch_assoc()['total'];

$lowStockQuery = "SELECT COUNT(*) as low_stock FROM products WHERE quantity < 5";
$lowStockResult = $conn->query($lowStockQuery);
$lowStock = $lowStockResult->fetch_assoc()['low_stock'];

$totalOrdersQuery = "SELECT COUNT(*) as total FROM reservations";  //Assume reservations table exists
$totalOrdersResult = $conn->query($totalOrdersQuery);
$totalOrders = $totalOrdersResult ? $totalOrdersResult->fetch_assoc()['total'] : 0;

$totalUsersQuery = "SELECT COUNT(*) as total FROM users";  //Assume users table exists
$totalUsersResult = $conn->query($totalUsersQuery);
$totalUsers = $totalUsersResult ? $totalUsersResult->fetch_assoc()['total'] : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - OptimaFlow</title>

    <!--Font Awesome Icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!--Dashboard.css-->
    <link rel="stylesheet" href="assets/css/admin/dashboard.css" />
</head>
<body>
    <div class="geo-bg">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="hex" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                    <polygon points="30,2 58,17 58,47 30,62 2,47 2,17" fill="none" stroke="rgba(43,94,171,0.08)" stroke-width="1"/>
                </pattern>
                <pattern id="dots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <circle cx="20" cy="20" r="1.4" fill="rgba(201,168,76,0.22)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hex)"/>
            <rect width="100%" height="100%" fill="url(#dots)"/>
            <circle cx="82%" cy="14%" r="210" fill="none" stroke="rgba(43,94,171,0.06)" stroke-width="1.5"/>
            <circle cx="82%" cy="14%" r="140" fill="none" stroke="rgba(43,94,171,0.05)" stroke-width="1"/>
            <circle cx="12%" cy="86%" r="190" fill="none" stroke="rgba(201,168,76,0.1)" stroke-width="1.5"/>
            <circle cx="12%" cy="86%" r="120" fill="none" stroke="rgba(201,168,76,0.07)" stroke-width="1"/>
        </svg>
    </div>

    <div class="container">
        <aside class="sidebar">
            <div class="logo">Optima<span>Flow</span><br>Admin</div>
            <nav class="nav">
                <a href="dashboard_admin.php" class="nav-item active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="admin_products.php" class="nav-item"><i class="fa-solid fa-box"></i> Products</a>
                <a href="admin_users.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
                <a href="admin_order.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Orders</a>
                <a href="admin_analytics.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> Analytics</a>
                <form action="logout.php" method="post" class="logout-form" id="logoutForm">
                    <button type="submit" id="logoutBtn" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></button>
                </form>
            </nav>
        </aside>

        <main class="main">
            <header class="header">
                <h1>Admin Dashboard</h1>
                <div class="search-avatar">
                    <div class="avatar">
                        <i class="fa-solid fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?>
                    </div>
                </div>
            </header>

            <!--Stats Cards-->
            <section class="stats-cards">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
                    <div class="stat-value"><?php echo $totalProducts; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>

                <div class="stat-card red">
                    <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-value"><?php echo $lowStock; ?></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>

                <div class="stat-card yellow">
                    <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                    <div class="stat-value"><?php echo $totalOrders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Registered Users</div>
                </div>
            </section>

            <!--Quick Actions-->
            <section class="quick-actions">
                <a href="admin_products.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-box"></i></div>
                    <div class="action-title">Manage Products</div>
                    <div class="action-description">Add, edit or remove products</div>
                </a>

                <a href="admin_order.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="action-title">View Orders</div>
                    <div class="action-description">Process pending orders</div>
                </a>

                <a href="admin_users.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="action-title">Manage Users</div>
                    <div class="action-description">View and manage user accounts</div>
                </a>
            </section>

            <!--Recent Activity-->
            <section class="recent-activity">
                <h2 class="activity-title">Recent Activity</h2>
                <p>No recent activities to display.</p>
            </section>
        </main>
    </div>

    <!--Dashboard JS-->
    <script src="assets/js/admin/dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var logoutForm = document.getElementById('logoutForm');
            var logoutBtn = document.getElementById('logoutBtn');
            if(logoutForm && logoutBtn){
                logoutForm.addEventListener('submit', function(e){
                    logoutBtn.classList.add('loading');
                    logoutBtn.disabled = true;
                    var span = logoutBtn.querySelector('span');
                    if(span) span.textContent = 'Logging out...';
                });
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>