<?php
session_start();
include 'db.php';

// ==========================================
// 1. CSV EXPORT HANDLER (MUST BE AT VERY TOP)
// ==========================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility
    fputcsv($output, ['ID', 'Name', 'Description', 'Category', 'Price (₱)', 'Quantity', 'Image Path']);

    // Fetches exactly what's displayed on the page
    $sql = "SELECT id, name, description, category, price, quantity, image FROM products ORDER BY category, name";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Matches your existing dynamic image naming fallback
            $imgPath = $row['image'] ?? (preg_replace('/[^a-zA-Z0-9]/', '', $row['name']) . '.jpg');
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['description'] ?? '',
                $row['category'] ?? '',
                number_format($row['price'], 2),
                $row['quantity'],
                $imgPath
            ]);
        }
    }

    fclose($output);
    exit; // CRITICAL: Stops execution so HTML doesn't leak into the CSV
}
// ==========================================

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

//Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    $sql = "SELECT name FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $row['name']) . '.jpg';
        $imagePath = "assets/img/$imageName";
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Product deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting product: " . $conn->error;
    }
    header("Location: admin_products.php");
    exit();
}

//Handle product addition
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category = $_POST['category'];

    $sql = "INSERT INTO products (name, description, price, quantity, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdis", $name, $description, $price, $quantity, $category);
    if ($stmt->execute()) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $name) . '.jpg';
            $targetDir = "assets/img/";
            $targetFile = $targetDir . $imageName;
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                if (!file_exists($targetDir)) { mkdir($targetDir, 0777, true); }
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $_SESSION['message'] = "Product added successfully with image";
                } else {
                    $_SESSION['message'] = "Product added but failed to upload image";
                }
            } else {
                $_SESSION['message'] = "Product added but file is not an image";
            }
        } else {
            $_SESSION['message'] = "Product added successfully without image";
        }
    } else {
        $_SESSION['error'] = "Error adding product: " . $conn->error;
    }
    header("Location: admin_products.php");
    exit();
}

//Handle stock updates
if (isset($_POST['update_stock'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $sql = "UPDATE products SET quantity = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quantity, $product_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Stock updated successfully";
    } else {
        $_SESSION['error'] = "Error updating stock: " . $conn->error;
    }
    header("Location: admin_products.php");
    exit();
}

//Fetch all the products
$sql = "SELECT * FROM products ORDER BY category, name";
$result = $conn->query($sql);
if (!$result) {
    $_SESSION['error'] = "Error fetching products: " . $conn->error;
}

$allProducts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allProducts[$row['category']][] = $row;
    }
}

$orderedCategories = ['Laptop', 'GPU', 'CPU', 'SSD', 'Motherboard', 'PC Case'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Products - OptimaFlow</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
<link rel="stylesheet" href="assets/css/admin/products.css" />
</head>
<body>
<div class="container">
<aside class="sidebar">
<div class="logo">OptimaFlow Admin</div>
<nav class="nav">
<a href="dashboard_admin.php" class="nav-item"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
<a href="admin_products.php" class="nav-item active"><i class="fa-solid fa-box"></i> Products</a>
<a href="admin_users.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
<a href="admin_order.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Orders</a>
<form action="logout.php" method="post" class="logout-form">
<button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
</form>
</nav>
</aside>
<main class="main">
<header class="header">
<h1>Product Management</h1>
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
<span><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></span>
</div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger">
<i class="fa-solid fa-circle-exclamation"></i>
<span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
</div>
<?php endif; ?>

<!-- ========================================== -->
<!-- 2. CSV EXPORT BUTTON -->
<!-- ========================================== -->
<div class="export-bar" style="margin: 20px 0; display: flex; justify-content: flex-end;">
    <form method="GET" action="admin_products.php" style="margin: 0;">
        <input type="hidden" name="export" value="csv">
        <button type="submit" class="btn-export" style="background: var(--primary, #00539C); color: #fff; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: background 0.2s;">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </button>
    </form>
</div>
<!-- ========================================== -->

<!--Add Product Form-->
<div class="add-product-form">
<h2 class="form-title"><i class="fa-solid fa-plus"></i> Add New Product</h2>
<form action="admin_products.php" method="POST" enctype="multipart/form-data">
<div class="form-grid">
<div class="form-group full-row">
<label for="name">Product Name</label>
<input type="text" id="name" name="name" class="form-control" required>
</div>
<div class="form-group full-row">
<label for="description">Description</label>
<textarea id="description" name="description" class="form-control" rows="3" required></textarea>
</div>
<div class="form-group">
<label for="price">Price (&#8369;)</label>
<input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
</div>
<div class="form-group">
<label for="quantity">Stock Quantity</label>
<input type="number" id="quantity" name="quantity" class="form-control" min="0" required>
</div>
<div class="form-group">
<label for="category">Category</label>
<select id="category" name="category" class="form-control" required>
<?php foreach ($orderedCategories as $category): ?>
<option value="<?php echo $category; ?>"><?php echo $category; ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group full-row">
<label for="image">Product Image</label>
<input type="file" id="image" name="image" class="form-control" accept="image/*">
</div>
</div>
<div class="form-actions">
<button type="submit" name="add_product" class="btn-submit">
<i class="fa-solid fa-plus"></i> Add Product
</button>
</div>
</form>
</div>

<?php if (empty($allProducts)): ?>
<div class="no-products">
<h3>No products available</h3>
<p>Start adding products using the form above.</p>
</div>
<?php else: ?>
<?php foreach ($orderedCategories as $category): ?>
<?php if (isset($allProducts[$category]) && count($allProducts[$category]) > 0): ?>
<h3 class="category-header"><?php echo htmlspecialchars($category); ?></h3>
<div class="product-grid">
<?php foreach ($allProducts[$category] as $product): ?>
<?php
$imageName = preg_replace('/[^a-zA-Z0-9]/', '', $product['name']) . '.jpg';
$imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : 'assets/img/placeholder.jpg';
$stockClass = $product['quantity'] < 5 ? 'low' : ($product['quantity'] < 10 ? 'medium' : '');
?>
<div class="product-card">
<div class="product-image">
<img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
</div>
<div class="product-details">
<div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
<div class="product-description"><?php echo htmlspecialchars($product['description']); ?></div>
<div class="product-meta">
<div class="product-price">&#8369;<?php echo number_format($product['price'], 2); ?></div>
<div class="product-stock <?php echo $stockClass; ?>">Stock: <?php echo $product['quantity']; ?></div>
</div>
<form action="admin_products.php" method="POST" class="stock-form">
<input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
<input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" min="0" class="stock-input" required>
<button type="submit" name="update_stock" class="stock-btn">
<i class="fa-solid fa-rotate"></i> Update
</button>
</form>
<div class="product-actions">
<form action="admin_products.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
<input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
<button type="submit" name="delete_product" class="delete-btn">
<i class="fa-solid fa-trash"></i> Delete
</button>
</form>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
</main>
</div>
<script src="assets/js/admin/products.js"></script>
</body>
</html>
<?php $conn->close(); ?>