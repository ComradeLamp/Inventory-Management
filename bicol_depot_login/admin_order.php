<?php
session_start();
include 'db.php';

//Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

//Initialize variables
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

//Process reservation status update if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id']) && isset($_POST['action'])) {
    $reservationId = $_POST['reservation_id'];
    $action = $_POST['action'];
    
    if ($action === 'update_status' && isset($_POST['status'])) {
        //Update reservation status
        $newStatus = $_POST['status'];
        $updateQuery = "UPDATE reservations SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $newStatus, $reservationId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Order #" . $reservationId . " status updated to " . ucfirst($newStatus);
        } else {
            $_SESSION['error'] = "Failed to update order status. Please try again.";
        }
        $stmt->close();
    }
}

//Modified query to remove user details that aren't in the table
$query = "SELECT r.id, r.user_id, r.product_id, r.quantity, r.status, r.reserved_at, 
        p.name as product_name, p.price
        FROM reservations r
        JOIN products p ON r.product_id = p.id
        WHERE 1=1";

$countQuery = "SELECT COUNT(*) as total FROM reservations r 
            JOIN products p ON r.product_id = p.id
            WHERE 1=1";

$params = [];
$types = "";

if (!empty($statusFilter)) {
    $query .= " AND r.status = ?";
    $countQuery .= " AND r.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($dateFilter)) {
    $query .= " AND DATE(r.reserved_at) = ?";
    $countQuery .= " AND DATE(r.reserved_at) = ?";
    $params[] = $dateFilter;
    $types .= "s";
}

if (!empty($searchQuery)) {
    //Modified to search only in product names since we don't have user details
    $query .= " AND p.name LIKE ?";
    $countQuery .= " AND p.name LIKE ?";
    $searchParam = "%" . $searchQuery . "%";
    $params[] = $searchParam;
    $types .= "s";
}

//Add sorting
$query .= " ORDER BY r.reserved_at DESC";

//Add pagination
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $itemsPerPage;
$types .= "ii";

//Prepare and execute count query
$countStmt = $conn->prepare($countQuery);
if (!empty($types) && !empty($params)) {
    $countTypes = substr($types, 0, -2); //Remove 'ii' for pagination
    $countParams = array_slice($params, 0, -2); //Remove offset and limit params
    
    if (!empty($countTypes)) {
        $countRef = [];
        foreach ($countParams as $key => $value) {
            $countRef[] = &$countParams[$key];
        }
        
        call_user_func_array([$countStmt, 'bind_param'], array_merge([$countTypes], $countRef));
    }
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $itemsPerPage);
$countStmt->close();

//Prepare and execute main query
$stmt = $conn->prepare($query);
if (!empty($types) && !empty($params)) {
    $paramRefs = [];
    foreach ($params as $key => $value) {
        $paramRefs[] = &$params[$key];
    }
    
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $paramRefs));
}
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

//Get reservation statuses for filter
$statusesQuery = "SELECT DISTINCT status FROM reservations ORDER BY status";
$statusesResult = $conn->query($statusesQuery);
$availableStatuses = [];
while ($status = $statusesResult->fetch_assoc()) {
    $availableStatuses[] = $status['status'];
}

//Get stats for each status
$statuses = ['pending', 'approved', 'fulfilled', 'cancelled'];
$statusCounts = [];

foreach ($statuses as $status) {
    $statusCountQuery = "SELECT COUNT(*) as count FROM reservations WHERE status = ?";
    $statusStmt = $conn->prepare($statusCountQuery);
    $statusStmt->bind_param("s", $status);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    $statusCounts[$status] = $statusResult->fetch_assoc()['count'];
    $statusStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Management - OptimaFlow</title>
    <link rel="stylesheet" href="assets/css/admin_styles.css" />
    <style>
        /* Additional styles for the orders page */
        .orders-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
        
        .stat-card.pending {
            border-top: 4px solid #f39c12;
        }
        
        .stat-card.approved {
            border-top: 4px solid #3498db;
        }
        
        .stat-card.fulfilled {
            border-top: 4px solid #2ecc71;
        }
        
        .stat-card.cancelled {
            border-top: 4px solid #e74c3c;
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
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .orders-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
        }
        
        .orders-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .product-details {
            display: flex;
            align-items: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-fulfilled {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .action-button:hover {
            background-color: #2980b9;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination-item {
            margin: 0 5px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #3498db;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .pagination-item:hover {
            background-color: #f8f9fa;
        }
        
        .pagination-item.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination-item.disabled {
            color: #aaa;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /*Modal styles*/
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }
        
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-btn:hover {
            color: #000;
        }
        
        .modal-header {
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }
        
        .modal-title {
            margin: 0;
            font-size: 18px;
            color: #2c3e50;
        }
        
        .modal-body {
            margin-bottom: 15px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .product-image-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .empty-state-message {
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        
        .empty-state-description {
            color: #adb5bd;
            margin-bottom: 20px;
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
                <a href="admin_order.php" class="nav-item active"><span>📄</span> Orders</a>
                <form action="logout.php" method="post" class="nav-item logout-form">
                    <button type="submit"><span>🚪</span> Logout</button>
                </form>
            </nav>
        </aside>
        
        <main class="main">
            <header class="header">
                <h1>Order Management</h1>
                <div class="search-avatar">
                    <div class="avatar">👤 <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?></div>
                </div>
            </header>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!--Stats Cards-->
            <section class="stats-cards">
                <div class="stat-card pending">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $statusCounts['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
                
                <div class="stat-card approved">
                    <div class="stat-icon">👍</div>
                    <div class="stat-value"><?php echo $statusCounts['approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved Orders</div>
                </div>
                
                <div class="stat-card fulfilled">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $statusCounts['fulfilled'] ?? 0; ?></div>
                    <div class="stat-label">Fulfilled Orders</div>
                </div>
                
                <div class="stat-card cancelled">
                    <div class="stat-icon">❌</div>
                    <div class="stat-value"><?php echo $statusCounts['cancelled'] ?? 0; ?></div>
                    <div class="stat-label">Cancelled Orders</div>
                </div>
            </section>
            
            <!--Orders Container-->
            <div class="orders-container">
                <h2>Customer Orders</h2>
                
                <!--Filters-->
                <form action="admin_order.php" method="GET">
                    <div class="filters">
                        <div>
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="filter-control">
                                <option value="">All Statuses</option>
                                <?php foreach ($availableStatuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(htmlspecialchars($status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date" class="form-label">Order Date</label>
                            <input type="date" class="filter-control" id="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                        </div>
                        
                        <div>
                            <label for="search" class="form-label">Search Product</label>
                            <input type="text" class="filter-control" id="search" name="search" placeholder="Product name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        
                        <div style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                        </div>
                    </div>
                </form>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3 class="empty-state-message">No orders found</h3>
                        <p class="empty-state-description">Try changing your filters or check back later</p>
                    </div>
                <?php else: ?>
                    <!--Orders Table-->
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                                <th>Order Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                    <td>
                                        <div class="product-details">
                                            <?php
                                            $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $order['product_name']) . '.jpg';
                                            $imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : 'assets/img/placeholder.jpg';
                                            ?>
                                            <img src="<?php echo $imagePath; ?>" class="product-image" alt="Product">
                                            <span><?php echo htmlspecialchars($order['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                    <td>₱<?php echo number_format($order['price'] * $order['quantity'], 2); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($order['reserved_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-button" onclick="openStatusModal('<?php echo $order['id']; ?>')">Update Status</button>
                                    </td>
                                </tr>
                                
                                <!--Status Modal for each order-->
                                <div id="statusModal<?php echo $order['id']; ?>" class="modal">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h3 class="modal-title">Update Order Status</h3>
                                            <span class="close-btn" onclick="closeStatusModal('<?php echo $order['id']; ?>')">&times;</span>
                                        </div>
                                        <form action="admin_order.php" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="reservation_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                
                                                <div class="product-image-container">
                                                    <img src="<?php echo $imagePath; ?>" class="product-image" alt="Product">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($order['product_name']); ?></strong>
                                                        <div>Quantity: <?php echo htmlspecialchars($order['quantity']); ?></div>
                                                        <div>Total: ₱<?php echo number_format($order['price'] * $order['quantity'], 2); ?></div>
                                                    </div>
                                                </div>
                                                
                                                <label for="status<?php echo $order['id']; ?>" class="form-label">Select New Status</label>
                                                <select id="status<?php echo $order['id']; ?>" name="status" class="filter-control">
                                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $order['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="fulfilled" <?php echo $order['status'] === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                
                                                <p style="margin-top: 10px; font-size: 12px; color: #6c757d;">
                                                    Current Status: 
                                                    <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>" style="font-size: 10px;">
                                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" onclick="closeStatusModal('<?php echo $order['id']; ?>')">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!--Pagination-->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($searchQuery); ?>" class="pagination-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">Previous</a>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($searchQuery); ?>" class="pagination-item <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($searchQuery); ?>" class="pagination-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">Next</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script> //JS
        //Functions to open and close the status update modals
        function openStatusModal(orderId) {
            document.getElementById('statusModal' + orderId).style.display = 'block';
        }
        
        function closeStatusModal(orderId) {
            document.getElementById('statusModal' + orderId).style.display = 'none';
        }
        
        //Close the modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>