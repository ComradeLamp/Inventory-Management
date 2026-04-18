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

// Getpaginated users
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
    <title>User Management - Bicol Depot</title>
    <link rel="stylesheet" href="assets/css/admin_styles.css" />
    <style>
        /*styles users*/
        .users-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .users-title {
            color: #2c3e50;
            margin: 0;
        }
        
        .users-search {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-button {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            color: #2c3e50;
        }
        
        .users-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .user-role {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .role-admin {
            background-color: #e74c3c;
            color: white;
        }
        
        .role-user {
            background-color: #3498db;
            color: white;
        }
        
        .user-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-inactive {
            background-color: #95a5a6;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .view-button, .delete-button, .activate-button, .deactivate-button {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .view-button {
            background-color: #2ecc71;
            color: white;
        }
        
        .delete-button {
            background-color: #e74c3c;
            color: white;
        }
        
        .activate-button {
            background-color: #27ae60;
            color: white;
        }
        
        .deactivate-button {
            background-color: #7f8c8d;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .pagination a:hover {
            background-color: #f8f9fa;
        }
        
        .pagination .active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
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
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 60%;
            max-width: 700px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: #000;
        }
        
        .message-list {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .message-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .message-meta {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .message-content {
            line-height: 1.5;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
        
        .user-count {
            margin-bottom: 20px;
            color: #7f8c8d;
        }
        
        @media (max-width: 768px) {
            .users-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .users-search {
                width: 100%;
            }
            
            .search-input {
                flex-grow: 1;
            }
            
            .users-table th:nth-child(3),
            .users-table td:nth-child(3) {
                display: none;
            }
            
            .modal-content {
                width: 90%;
                margin: 20% auto;
            }
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
                <a href="admin_users.php" class="nav-item active"><span>👥</span> Users</a>
                <a href="admin_order.php" class="nav-item"><span>📄</span> Orders</a>
                <form action="logout.php" method="post" class="nav-item logout-form">
                    <button type="submit"><span>🚪</span> Logout</button>
                </form>
            </nav>
        </aside>
        
        <main class="main">
            <header class="header">
                <h1>User Management</h1>
                <div class="search-avatar">
                    <div class="avatar">👤 <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?></div>
                </div>
            </header>
            
            <!--Users List Section-->
            <section class="users-container">
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="users-header">
                    <h2 class="users-title">All Users</h2>
                    <div class="users-search">
                        <input type="text" id="user-search" class="search-input" placeholder="Search users...">
                        <button class="search-button">Search</button>
                    </div>
                </div>
                
                <div class="user-count">Total Users: <?php echo $totalUsers; ?></div>
                
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
                                            <button class="view-button" onclick="viewUserMessages(<?php echo $user['id']; ?>)">Messages</button>
                                        <?php endif; ?>
                                        
                                        <?php if($user['role'] != 'admin' || $_SESSION['user']['id'] != $user['id']): ?>
                                            <!--Activate/Deactivate Button-->
                                            <?php if(isset($user['status']) && $user['status'] == 'active'): ?>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate this user account? They will no longer be able to log in.');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="inactive">
                                                    <button type="submit" name="toggle_status" class="deactivate-button">Deactivate</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to activate this user account?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" name="toggle_status" class="activate-button">Activate</button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <!--Only show delete for non-admin users or admins that aren't themselves-->
                                            <?php if($user['role'] != 'admin' || ($_SESSION['user']['id'] != $user['id'])): ?>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="delete-button">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!--Pagination-->
                <div class="pagination">
                    <?php if($totalPages > 1): ?>
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    
    <!--User Messages Modal-->
    <div id="messagesModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2>User Messages</h2>
            <div id="userMessagesContainer" class="message-list">
                <!--Messages will be loaded here-->
                <p>Loading messages...</p>
            </div>
        </div>
    </div>
    
    <script>
        //Function to view user messages in modal
        function viewUserMessages(userId) {
            //Show the modal
            document.getElementById('messagesModal').style.display = 'block';
            
            //Fetch messages via AJAX
            fetch('get_user_messages.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('userMessagesContainer');
                    
                    if(data.length === 0) {
                        container.innerHTML = '<p>No messages found for this user.</p>';
                        return;
                    }
                    
                    let html = '';
                    data.forEach(message => {
                        html += `
                            <div class="message-item">
                                <div class="message-meta">
                                    <strong>Date:</strong> ${message.created_at}
                                </div>
                                <div class="message-content">
                                    ${message.content}
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('userMessagesContainer').innerHTML = 
                        '<p>Error loading messages. Please try again.</p>';
                    console.error('Error:', error);
                });
        }
        
        //Function to close modal
        function closeModal() {
            document.getElementById('messagesModal').style.display = 'none';
        }
        
        //Close when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('messagesModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        //Filter users based on search input
        document.querySelector('.search-button').addEventListener('click', function() {
            const input = document.getElementById('user-search').value.toLowerCase();
            const rows = document.querySelectorAll('.users-table tbody tr');
            
            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                
                if(username.includes(input) || email.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>