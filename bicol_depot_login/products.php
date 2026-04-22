<?php
session_start();
include 'db.php';
//Fetch all products from the db
$sql = "SELECT * FROM products ORDER BY category, name";
$result = mysqli_query($conn, $sql);

//Group products by category
$allProducts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $allProducts[$row['category']][] = $row;
}

//Define display order for categories
$orderedCategories = ['Laptop', 'GPU', 'CPU', 'SSD', 'Motherboard', 'PC Case'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Products - OptimaFlow</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!--Custom CSS-->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .overlay {
            background-color: rgba(235, 244, 255, 0.95);
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        .card {
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: scale(1.03);
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

    <!--Product Section-->
    <div class="container my-5 overlay">
        <h1 class="text-center mb-5 fw-bold">Browse Our Products</h1>

        <?php foreach ($orderedCategories as $category): ?>
            <?php if (isset($allProducts[$category])): ?>
                <h3 class="mb-3 text-dark"><?php echo htmlspecialchars($category); ?></h3>
                <div class="row g-4 mb-5">
                    <?php foreach ($allProducts[$category] as $product): ?>
                        <?php
                        $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $product['name']) . '.jpg';
                        $imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : 'assets/img/placeholder.jpg';
                        ?>
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card h-100">
                                <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="Product Image" style="height: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-success fw-bold mb-1">₱<?php echo number_format($product['price'], 2); ?></p>
                                        <p class="small text-muted">Available: <?php echo $product['quantity']; ?></p>

                                        <!--Reserve Button and Quantity Input (also not working)-->
                                        <form action="reserve.php" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>" class="form-control mb-2" style="width: 80px;">
                                            <button type="submit"  class="btn btn-warning w-100" <?php if ($product['quantity'] <= 0) echo 'disabled'; ?>>
                                                <?php echo ($product['quantity'] > 0) ? 'Reserve' : 'Out of Stock'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!--Footer-->
    <footer class="bg-dark text-white text-center py-3">
        <p class="mb-0">&copy; 2025 Bicol Pre-Owned Laptop Depot. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
