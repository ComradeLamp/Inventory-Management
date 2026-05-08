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
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

//Process reservation status update if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id']) && isset($_POST['action'])) {
    $reservationId = $_POST['reservation_id'];
    $action = $_POST['action'];

    if ($action === 'update_status' && isset($_POST['status'])) {
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
    header("Location: admin_order.php?" . http_build_query($_GET));
    exit();
}

//Modified query to remove user details that aren't in the table
$query = "SELECT r.id, r.user_id, r.product_id, r.quantity, r.status, r.reserved_at, p.name as product_name, p.price 
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
    $query .= " AND p.name LIKE ?";
    $countQuery .= " AND p.name LIKE ?";
    $params[] = "%" . $searchQuery . "%";
    $types .= "s";
}

// Add sorting & pagination
$query .= " ORDER BY r.reserved_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $itemsPerPage;
$types .= "ii";

//Prepare and execute count query
$countStmt = $conn->prepare($countQuery);
if (!empty($types) && !empty($params)) {
    $countTypes = substr($types, 0, -2);
    $countParams = array_slice($params, 0, -2);
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

    <!--Font Icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

    <!--Users.css-->
    <link rel="stylesheet" href="assets/css/admin/orders.css"/>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">OptimaFlow Admin</div>
            <nav class="nav">
                <a href="dashboard_admin.php" class="nav-item"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="admin_products.php" class="nav-item"><i class="fa-solid fa-box"></i> Products</a>
                <a href="admin_users.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
                <a href="admin_order.php" class="nav-item active"><i class="fa-solid fa-clipboard-list"></i> Orders</a>
                <form action="logout.php" method="post" class="logout-form">
                    <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main">
            <header class="header">
                <h1>Order Management</h1>
                <div class="search-avatar">
                    <div class="avatar">
                        <i class="fa-solid fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?>
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($_SESSION['message']);
                    unset($_SESSION['message']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <!--Stats Cards-->
            <section class="stats-cards">
                <div class="stat-card pending">
                    <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="stat-value"><?php echo $statusCounts['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
                <div class="stat-card approved">
                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-value"><?php echo $statusCounts['approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved Orders</div>
                </div>
                <div class="stat-card fulfilled">
                    <div class="stat-icon"><i class="fa-solid fa-truck-fast"></i></div>
                    <div class="stat-value"><?php echo $statusCounts['fulfilled'] ?? 0; ?></div>
                    <div class="stat-label">Fulfilled Orders</div>
                </div>
                <div class="stat-card cancelled">
                    <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
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
                            <input type="date" class="filter-control" id="date" name="date"
                                value="<?php echo htmlspecialchars($dateFilter); ?>">
                        </div>
                        <div>
                            <label for="search" class="form-label">Search Product</label>
                            <input type="text" class="filter-control" id="search" name="search"
                                placeholder="Product name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-filter"></i> Apply Filters
                            </button>
                            <a href="admin_order.php" class="btn btn-clear" title="Clear filters">
                                <i class="fa-solid fa-rotate"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>

                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox empty-state-icon"></i>
                        <h3 class="empty-state-message">No orders found</h3>
                        <p class="empty-state-description">Try changing your filters or check back later</p>
                    </div>
                <?php else: ?>
                    <!--Orders Table-->
                    <div class="table-wrapper">
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
                                    <?php
                                    $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $order['product_name']) . '.jpg';
                                    $imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : 'assets/img/placeholder.jpg';
                                    ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                        <td>
                                            <div class="product-details">
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" class="product-image"
                                                    alt="Product">
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
                                            <button class="update-status-btn"
                                                data-id="<?php echo htmlspecialchars($order['id']); ?>"
                                                data-product="<?php echo htmlspecialchars($order['product_name']); ?>"
                                                data-quantity="<?php echo htmlspecialchars($order['quantity']); ?>"
                                                data-price="₱<?php echo number_format($order['price'] * $order['quantity'], 2); ?>"
                                                data-status="<?php echo htmlspecialchars($order['status']); ?>">
                                                Update Status
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!--Pagination-->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"
                                class="pagination-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"
                                    class="pagination-item <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"
                                class="pagination-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!--Single Shared Status Modal-->
            <div id="statusModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Update Order Status</h3>
                        <button type="button" class="close-modal" title="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <form id="orderStatusForm" action="admin_order.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="reservation_id" value="">
                            <input type="hidden" name="action" value="update_status">
                            <div class="product-image-container">
                                <div class="modal-order-details">
                                    <strong id="modalOrderId"></strong>
                                    <span id="modalProductName"></span>
                                    <div>Quantity: <span id="modalQuantity"></span></div>
                                    <div>Total: <span id="modalTotalPrice"></span></div>
                                </div>
                            </div>
                            <label for="modalStatusSelect" class="form-label">Select New Status</label>
                            <select id="modalStatusSelect" name="status" class="filter-control">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="fulfilled">Fulfilled</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <p style="margin-top: 10px; font-size: 12px; color: var(--text-muted);">
                                Current Status: <span id="modalCurrentStatus" class="status-badge"></span>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary cancel-modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin/orders.js"></script>
</body>

</html>
<?php $conn->close(); ?>