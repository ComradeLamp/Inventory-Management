<?php
session_start();
include 'db.php';

//Check if coming from a successful reservation
$success_message = $_SESSION['message'] ?? null;
$error_message = $_SESSION['error'] ?? null;

//Clear messages after displaying them
unset($_SESSION['message']);
unset($_SESSION['error']);

//Fetch user's reservations
$user_id = $_SESSION['user']['id'] ?? null;
$reservations = [];
if ($user_id) {
    $reservationQuery = $conn->prepare("
        SELECT r.*, p.name as product_name, p.price, p.image 
        FROM reservations r
        JOIN products p ON r.product_id = p.id
        WHERE r.user_id = ? AND r.status = 'pending'
        ORDER BY r.reserved_at DESC
    ");
    $reservationQuery->bind_param("i", $user_id);
    $reservationQuery->execute();
    $result = $reservationQuery->get_result();
    $reservations = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Reservations - OptimaFlow</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!--Custom CSS-->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .overlay {
            background-color: rgba(235, 244, 255, 0.95);
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }
        
        .reservation-card {
            transition: all 0.3s ease;
            border-left: 4px solid #0d6efd;
        }
        
        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .product-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>

<body>
    <!--Navigation Bar-->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
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
                    <li class="nav-item"><a class="nav-link" href="dashboard_customer.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="AboutUS.html">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item"><a class="btn btn-outline-primary me-2" href="reservations.php">Cart</a></li>
                    <li class="nav-item"><a class="btn btn-outline-danger" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!--Main Content-->
    <div class="container my-5 overlay">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">My Reservations</h1>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add More Items
            </a>
        </div>

        <!--Messages-->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($reservations)): ?>
            <div class="card reservation-card mb-4">
                <div class="card-body text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                    <h3 class="text-muted">Your cart is empty</h3>
                    <p class="text-muted">Start shopping to add items to your cart</p>
                    <a href="products.php" class="btn btn-primary mt-3">Browse Products</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($reservations as $reservation): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card reservation-card h-100">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <?php
                                        $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $reservation['product_name']) . '.jpg';
                                        $imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : 'assets/img/placeholder.jpg';
                                        ?>
                                        <img src="<?php echo $imagePath; ?>" class="product-img w-100" alt="Product Image">
                                    </div>
                                    <div class="col-md-8">
                                        <h5 class="card-title"><?php echo htmlspecialchars($reservation['product_name']); ?></h5>
                                        <p class="text-muted mb-1">Reserved on: <?php echo date('M d, Y h:i A', strtotime($reservation['reserved_at'])); ?></p>
                                        <span class="status-badge status-pending">Pending</span>
                                        
                                        <div class="mt-3">
                                            <p class="mb-1">Quantity: <?php echo $reservation['quantity']; ?></p>
                                            <p class="mb-1">Price: ₱<?php echo number_format($reservation['price'], 2); ?></p>
                                            <p class="fw-bold">Total: ₱<?php echo number_format($reservation['price'] * $reservation['quantity'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <form action="cancel_reservation.php" method="POST" class="d-grid">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="fas fa-trash-alt me-1"></i> Cancel Reservation
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!--Footer-->
    <footer class="bg-dark text-white text-center py-3">
        <p class="mb-0">&copy; 2025 Bicol Pre-Owned Laptop Depot. All rights reserved.</p>
    </footer>

    <!--Bootstrap JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!--Custom JS-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            //Confirm before canceling reservation
            document.querySelectorAll('form[action="cancel_reservation.php"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to cancel this reservation?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>