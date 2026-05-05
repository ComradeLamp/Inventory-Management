<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

//Handle user deletion if requested
if(isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $user_id);

    if($stmt->execute()) {
        $success_message = "User deleted successfully";
    } else {
        $error_message = "Error deleting user: " . $conn->error;
    }
    $stmt->close();
}

//Handle user activation/deactivation
if(isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];

    $updateQuery = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $new_status, $user_id);

    if($stmt->execute()) {
        $status_text = ($new_status == 'active') ? 'activated' : 'deactivated';
        $success_message = "User account " . $status_text . " successfully";
    } else {
        $error_message = "Error updating user status: " . $conn->error;
    }
    $stmt->close();
}

//Fetch all users
$usersQuery = "SELECT * FROM users ORDER BY created_at DESC";
$usersResult = $conn->query($usersQuery);

//Count messages per user if messages table exists
$messagesByUser = [];
$checkTableQuery = "SHOW TABLES LIKE 'messages'";
$tableExists = $conn->query($checkTableQuery);

if($tableExists && $tableExists->num_rows > 0) {
    $userMessagesQuery = "SELECT user_id, COUNT(*) as message_count FROM messages GROUP BY user_id";
    $userMessagesResult = $conn->query($userMessagesQuery);

    if($userMessagesResult) {
        while($row = $userMessagesResult->fetch_assoc()) {
            $messagesByUser[$row['user_id']] = $row['message_count'];
        }
    }
}

//settings
$resultsPerPage = 10;
$totalUsers = $usersResult->num_rows;
$totalPages = ceil($totalUsers / $resultsPerPage);

//current page or set to 1
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$page = max(1, min($page, $totalPages));
$startFrom = ($page - 1) * $resultsPerPage;

//Getpaginated users
$paginatedUsersQuery = "SELECT * FROM users ORDER BY created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($paginatedUsersQuery);
$stmt->bind_param("ii", $startFrom, $resultsPerPage);
$stmt->execute();
$paginatedUsers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Management - OptimaFlow</title>

    <!--Font Icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

    <!--Users.css-->
    <link rel="stylesheet" href="assets/css/admin/users.css"/>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">OptimaFlow Admin</div>
            <nav class="nav">
                <a href="dashboard_admin.php" class="nav-item"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="admin_products.php" class="nav-item"><i class="fa-solid fa-box"></i> Products</a>
                <a href="admin_users.php" class="nav-item active"><i class="fa-solid fa-users"></i> Users</a>
                <a href="admin_order.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Orders</a>
                <form action="logout.php" method="post" class="logout-form">
                    <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main">
            <header class="header">
                <h1>User Management</h1>
                <div class="search-avatar">
                    <div class="avatar">
                        <i class="fa-solid fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?>
                    </div>
                </div>
            </header>

            <!--Users List Section-->
            <section class="users-container">
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <div class="users-header">
                    <h2 class="users-title">All Users</h2>
                    <div class="users-search">
                        <div class="search-input-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="user-search" class="search-input" placeholder="Search users...">
                        </div>
                        <button class="search-button">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                    </div>
                </div>

                <div class="user-count">Total Users: <strong><?php echo $totalUsers; ?></strong></div>

                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Messages</th>
                            <th>Registered</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($paginatedUsers && $paginatedUsers->num_rows > 0): ?>
                            <?php while($user = $paginatedUsers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="user-role <?php echo $user['role'] == 'admin' ? 'role-admin' : 'role-user'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="user-status <?php echo isset($user['status']) && $user['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo isset($user['status']) ? ucfirst($user['status']) : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo isset($messagesByUser[$user['id']]) ? $messagesByUser[$user['id']] : 0; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td><?php echo isset($user['login_at']) ? date('M d, Y', strtotime($user['login_at'])) : 'Never'; ?></td>
                                    <td class="action-buttons">
                                        <?php if(isset($tableExists) && $tableExists->num_rows > 0): ?>
                                            <button class="view-button" onclick="viewUserMessages(<?php echo $user['id']; ?>)" title="View messages">
                                                <i class="fa-solid fa-envelope"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if($user['role'] != 'admin' || $_SESSION['user']['id'] != $user['id']): ?>
                                            <!--Activate/Deactivate Button-->
                                            <?php if(isset($user['status']) && $user['status'] == 'active'): ?>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to deactivate this user account? They will no longer be able to log in.');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="inactive">
                                                    <button type="submit" name="toggle_status" class="deactivate-button" title="Deactivate user">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to activate this user account?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" name="toggle_status" class="activate-button" title="Activate user">
                                                        <i class="fa-solid fa-circle-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!--Only show delete for non-admin users or admins that aren't themselves-->
                                            <?php if($user['role'] != 'admin' || ($_SESSION['user']['id'] != $user['id'])): ?>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="delete-button" title="Delete user">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="no-users-cell">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!--Pagination-->
                <div class="pagination">
                    <?php if($totalPages > 1): ?>
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" title="Previous page"><i class="fa-solid fa-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" title="Next page"><i class="fa-solid fa-chevron-right"></i></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!--User Messages Modal-->
    <div id="messagesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-envelope"></i> User Messages</h2>
                <button type="button" class="close-modal" onclick="closeModal()" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="userMessagesContainer" class="message-list">
                <p class="modal-empty">Loading messages...</p>
            </div>
        </div>
    </div>

    <!--Users page JS-->
    <script src="assets/js/admin/users.js"></script>
</body>
</html>

<?php $conn->close(); ?>