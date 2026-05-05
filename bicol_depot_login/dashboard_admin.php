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
    <link rel="stylesheet" href="assets/css/admin_styles.css" />
    <style>
        /*Styles for Dashboard*/
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.blue {
            border-top: 4px solid #3498db;
        }
        
        .stat-card.red {
            border-top: 4px solid #e74c3c;
        }
        
        .stat-card.yellow {
            border-top: 4px solid #f39c12;
        }
        
        .stat-card.green {
            border-top: 4px solid #2ecc71;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
            text-transform: uppercase;
        }
        
        .stat-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .recent-activity {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .activity-title {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            background-color: #f8f9fa;
        }
        
        .action-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .action-title {
            font-weight: bold;
        }
        
        .action-description {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">Admin Panel</div>
            <nav class="nav">
                <a href="dashboard_admin.php" class="nav-item"><span>📊</span> Dashboard</a>
                <a href="admin_products.php" class="nav-item"><span>📦</span> Products</a>
                <a href="admin_users.php" class="nav-item"><span>👥</span> Users</a>
                <a href="admin_order.php" class="nav-item"><span>📄</span> Orders</a>
                <form action="logout.php" method="post" class="nav-item logout-form">
                    <button type="submit"><span>🚪</span> Logout</button>
                </form>
            </nav>
        </aside>
        
        <main class="main">
            <header class="header">
                <h1>Admin Dashboard</h1>
                <div class="search-avatar">
                    <div class="avatar">👤 <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?></div>
                </div>
            </header>
            
            <!--Stats Cards-->
            <section class="stats-cards">
                <div class="stat-card blue">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $totalProducts; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-value"><?php echo $lowStock; ?></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
                
                <div class="stat-card yellow">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-value"><?php echo $totalOrders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Registered Users</div>
                </div>
            </section>
            
            <!--Quick Actions-->
            <section class="quick-actions">
                <a href="admin_products.php" class="action-card">
                    <div class="action-icon">📦</div>
                    <div class="action-title">Manage Products</div>
                    <div class="action-description">Add, edit or remove products</div>
                </a>
                
                <a href="admin_order.php" class="action-card">
                    <div class="action-icon">📋</div>
                    <div class="action-title">View Orders</div>
                    <div class="action-description">Process pending orders</div>
                </a>
                
                <a href="#" class="action-card">
                    <div class="action-icon">📊</div>
                    <div class="action-title">Sales Report</div>
                    <div class="action-description">View sales analytics</div>
                </a>
                
                <a href="#" class="action-card">
                    <div class="action-icon">⚙️</div>
                    <div class="action-title">Settings</div>
                    <div class="action-description">Configure system settings</div>
                </a>
            </section>
            
            <!--Recent Activity-->
            <section class="recent-activity">
                <h2 class="activity-title">Recent Activity</h2>
                <p>No recent activities to display.</p>
                <!--No activities to add...-->
            </section>
        </main>
    </div>

</body>
</html>

<?php $conn->close(); ?>