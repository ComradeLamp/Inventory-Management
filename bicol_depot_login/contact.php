<?php
session_start();

//Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$success_message = "";
$error_message = "";

//Handle message submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_message'])) {
    //Get form data
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    $user_id = $_SESSION['user']['id'];
    
    //Validate input
    if (empty($subject) || empty($message)) {
        $error_message = "Please fill in all fields";
    } else {
        //Escape strings after validation
        $subject = mysqli_real_escape_string($conn, $subject);
        $message = mysqli_real_escape_string($conn, $message);
        
        //First, check the structure of the messages table to determine column names
        $columnCheckQuery = "DESCRIBE messages";
        $columnResult = $conn->query($columnCheckQuery);
        
        if (!$columnResult) {
            //Table doesn't exist, create it
            $createTableQuery = "CREATE TABLE messages (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            //First, check if users table exists to set up FK
            $userTableCheck = $conn->query("SHOW TABLES LIKE 'users'");
            
            if ($userTableCheck && $userTableCheck->num_rows > 0) {
                //Add FK constraint
                $createTableQuery = "CREATE TABLE messages (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
            }
            
            if (!$conn->query($createTableQuery)) {
                $error_message = "Error creating messages table: " . $conn->error;
            } else {
                $columnResult = $conn->query($columnCheckQuery); //Re-query after creating table
            }
        }
        
        if ($columnResult) {
            //Check which columns exist in the table
            $columns = array();
            $hasSubject = false;
            $hasTitle = false;
            $hasContent = false;
            $hasMessage = false;
            
            while ($row = $columnResult->fetch_assoc()) {
                $columns[] = $row['Field'];
                if ($row['Field'] == 'subject') $hasSubject = true;
                if ($row['Field'] == 'title') $hasTitle = true;
                if ($row['Field'] == 'content') $hasContent = true;
                if ($row['Field'] == 'message') $hasMessage = true;
            }
            
            //Determine which column names to use
            $subjectColumn = $hasSubject ? 'subject' : ($hasTitle ? 'title' : 'subject');
            $messageColumn = $hasContent ? 'content' : ($hasMessage ? 'message' : 'content');
            
            try {
                //Alter table to add subject column if needed
                if (!$hasSubject && !$hasTitle) {
                    $alterQuery = "ALTER TABLE messages ADD COLUMN subject VARCHAR(255) NOT NULL AFTER user_id";
                    $conn->query($alterQuery);
                    $subjectColumn = 'subject';
                }
                
                //Alter table to add content column if needed
                if (!$hasContent && !$hasMessage) {
                    $alterQuery = "ALTER TABLE messages ADD COLUMN content TEXT NOT NULL AFTER $subjectColumn";
                    $conn->query($alterQuery);
                    $messageColumn = 'content';
                }
                
                //Insert message into db
                $insertQuery = "INSERT INTO messages (user_id, $subjectColumn, $messageColumn) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                
                //Check if prepare was successful
                if ($stmt) {
                    $stmt->bind_param("iss", $user_id, $subject, $message);
                    
                    if ($stmt->execute()) {
                        $success_message = "Your message has been sent to the administrator.";
                    } else {
                        throw new Exception("Error executing statement: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Error preparing statement: " . $conn->error);
                }
            } catch (Exception $e) {
                //Fallback approach. Direct SQL with detected column names
                $insertDirectQuery = "INSERT INTO messages (user_id, $subjectColumn, $messageColumn) 
                    VALUES ('$user_id', '$subject', '$message')";
                if ($conn->query($insertDirectQuery)) {
                    $success_message = "Your message has been sent to the administrator.";
                } else {
                    $error_message = "Error sending message: " . $conn->error . " - Query: $insertDirectQuery";
                }
            }
        } else {
            $error_message = "Could not determine table structure: " . $conn->error;
        }
    }
}

//Get user's previous messages
$user_id = $_SESSION['user']['id'];
$previousMessages = array();

//First check the structure of the messages table to determine column names
$columnCheckQuery = "DESCRIBE messages";
$columnResult = $conn->query($columnCheckQuery);

if ($columnResult) {
    //Check which columns exist in the table
    $columns = array();
    $hasSubject = false;
    $hasTitle = false;
    $hasContent = false;
    $hasMessage = false;
    
    while ($row = $columnResult->fetch_assoc()) {
        $columns[] = $row['Field'];
        if ($row['Field'] == 'subject') $hasSubject = true;
        if ($row['Field'] == 'title') $hasTitle = true;
        if ($row['Field'] == 'content') $hasContent = true;
        if ($row['Field'] == 'message') $hasMessage = true;
    }
    
    //Determine which column names to use
    $subjectColumn = $hasSubject ? 'subject' : ($hasTitle ? 'title' : '');
    $messageColumn = $hasContent ? 'content' : ($hasMessage ? 'message' : '');
    
    if (!empty($subjectColumn) && !empty($messageColumn)) {
        try {
            $messagesQuery = "SELECT id, user_id, $subjectColumn as subject, $messageColumn as content, is_read, created_at 
                FROM messages WHERE user_id = ? ORDER BY created_at DESC";
            $stmt = $conn->prepare($messagesQuery);
            
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $previousMessages[] = $row;
                }
                $stmt->close();
            } else {
                //Go back to direct query
                $messagesDirectQuery = "SELECT id, user_id, $subjectColumn as subject, $messageColumn as content, is_read, created_at 
                    FROM messages WHERE user_id = '$user_id' ORDER BY created_at DESC";
                $result = $conn->query($messagesDirectQuery);
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $previousMessages[] = $row;
                    }
                }
            }
        } catch (Exception $e) {
            //Will just show no previous messages
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Admin - OptimaFlow</title>
    
    <!--Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!--BS ICONS CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!--Custom CSS-->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .message-list {
            margin-top: 3rem;
        }
        
        .message-card {
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .message-read {
            border-left-color: #95a5a6;
        }
        
        .read-status {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .status-read {
            background-color: #95a5a6;
            color: white;
        }
        
        .status-unread {
            background-color: #3498db;
            color: white;
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
                <a class="navbar-brand mb-0 h1">OptimaFlow</a>
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

    <!--Main Con-->
    <div class="container py-5">
        <h1 class="mb-4 text-center">Contact Administrator</h1>
        <p class="text-center mb-5">Have a question or concern? Send a message directly to our administrators.</p>
        
        <!--Alerts for success/error messages-->
        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!--Message Form-->
            <div class="col-md-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Send New Message</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="submit_message" class="btn btn-primary">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!--User Information-->
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h4 class="mb-0">User Information</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>
                        <?php if(isset($_SESSION['user']['email'])): ?>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>
                        <?php endif; ?>
                        <p><strong>Account Type:</strong> <?php echo ucfirst($_SESSION['user']['role']); ?></p>
                        <p class="mb-0 text-muted">This information will be included with your message to help administrators identify you.</p>
                    </div>
                </div>
                
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">Contact Options</h4>
                    </div>
                    <div class="card-body">
                        <p><i class="bi bi-envelope-fill me-2"></i> Email: <a href="mailto:support@optimaflow.com">support@optimaflow.com</a></p>
                        <p><i class="bi bi-telephone-fill me-2"></i> Phone: +63 912 345 6789</p>
                        <p class="mb-0"><i class="bi bi-clock-fill me-2"></i> Response Time: Within 24-48 hours</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!--Previous Messages Section-->
        <div class="message-list">
            <h2 class="mb-4">Your Previous Messages</h2>
            
            <?php if(empty($previousMessages)): ?>
                <div class="alert alert-info">
                    You haven't sent any messages yet.
                </div>
            <?php else: ?>
                <?php foreach($previousMessages as $msg): ?>
                    <div class="card message-card <?php echo $msg['is_read'] ? 'message-read' : ''; ?>">
                        <div class="card-body">
                            <div class="message-header mb-2">
                                <span>Sent on: <?php echo date('F d, Y - h:i A', strtotime($msg['created_at'])); ?></span>
                                <span class="read-status <?php echo $msg['is_read'] ? 'status-read' : 'status-unread'; ?>">
                                    <?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?>
                                </span>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($msg['subject']); ?></h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!--Footer-->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 OptimaFlow. All rights reserved.</p>
            <p>
                <a href="privacy.html" class="text-white">Privacy Policy</a> |
                <a href="terms.html" class="text-white">Terms of Service</a>
            </p>
        </div>
    </footer>

    <!--Bootstrap JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>