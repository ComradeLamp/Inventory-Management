<?php
session_start();
include 'db.php';

//Read filter parameters (used by both display and CSV export)
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';
$searchFilter = isset($_GET['search']) ? trim($_GET['search']) : '';

//Build dynamic WHERE clause for filters
function buildProductFilterClause($categoryFilter, $stockFilter, $searchFilter, &$params, &$types)
{
    $where = " WHERE 1=1";

    if (!empty($categoryFilter)) {
        $where .= " AND category = ?";
        $params[] = $categoryFilter;
        $types .= "s";
    }
    if (!empty($stockFilter)) {
        if ($stockFilter === 'low') {
            $where .= " AND quantity < 5";
        } elseif ($stockFilter === 'medium') {
            $where .= " AND quantity >= 5 AND quantity < 10";
        } elseif ($stockFilter === 'healthy') {
            $where .= " AND quantity >= 10";
        }
    }
    if (!empty($searchFilter)) {
        $where .= " AND name LIKE ?";
        $params[] = "%" . $searchFilter . "%";
        $types .= "s";
    }
    return $where;
}

// ==========================================
// 1. CSV EXPORT HANDLER (MUST BE AT VERY TOP)
//    Respects current filters
// ==========================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility
    fputcsv($output, ['ID', 'Name', 'Description', 'Category', 'Price (Peso)', 'Quantity', 'Image Path']);

    //Build filtered query for export
    $exportParams = [];
    $exportTypes = "";
    $whereClause = buildProductFilterClause($categoryFilter, $stockFilter, $searchFilter, $exportParams, $exportTypes);
    $sql = "SELECT id, name, description, category, price, quantity, image FROM products" . $whereClause . " ORDER BY category, name";

    $stmt = $conn->prepare($sql);
    if (!empty($exportTypes)) {
        $stmt->bind_param($exportTypes, ...$exportParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
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
    exit;
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
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
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

//Fetch products with filters applied
$displayParams = [];
$displayTypes = "";
$whereClause = buildProductFilterClause($categoryFilter, $stockFilter, $searchFilter, $displayParams, $displayTypes);
$sql = "SELECT * FROM products" . $whereClause . " ORDER BY category, name";

$stmt = $conn->prepare($sql);
if (!empty($displayTypes)) {
    $stmt->bind_param($displayTypes, ...$displayParams);
}
$stmt->execute();
$result = $stmt->get_result();

$allProducts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allProducts[$row['category']][] = $row;
    }
}

$orderedCategories = ['Laptop', 'GPU', 'CPU', 'SSD', 'Motherboard', 'PC Case'];

//Check if any filter is active (for showing filter status)
$hasActiveFilter = !empty($categoryFilter) || !empty($stockFilter) || !empty($searchFilter);
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
                <a href="dashboard_admin.php" class="nav-item"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="admin_products.php" class="nav-item active"><i class="fa-solid fa-box"></i> Products</a>
                <a href="admin_users.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
                <a href="admin_order.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Orders</a>
                <a href="admin_analytics.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> Analytics</a>
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

            <!--Action Cards (centered): Add Product + Export CSV-->
            <div class="action-cards">
                <!--Add Product (opens modal)-->
                <button type="button" class="action-card-btn" id="openAddProductModal">
                    <div class="action-card-icon"><i class="fa-solid fa-plus"></i></div>
                    <div class="action-card-label">Add Product</div>
                </button>

                <!--Export CSV (preserves current filters)-->
                <form method="GET" action="admin_products.php" class="action-card-form">
                    <?php if (!empty($categoryFilter)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($stockFilter)): ?>
                        <input type="hidden" name="stock" value="<?php echo htmlspecialchars($stockFilter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($searchFilter)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchFilter); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="export" value="csv">
                    <button type="submit" class="action-card-btn">
                        <div class="action-card-icon"><i class="fa-solid fa-file-csv"></i></div>
                        <div class="action-card-label">
                            <?php echo $hasActiveFilter ? 'Export Filtered CSV' : 'Export CSV'; ?>
                        </div>
                    </button>
                </form>
            </div>

            <!--Filter Bar-->
            <div class="filter-bar">
                <h2 class="filter-title"><i class="fa-solid fa-filter"></i> Filter Products</h2>
                <form method="GET" action="admin_products.php" class="filter-form">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label for="filter-category">Category</label>
                            <select id="filter-category" name="category" class="filter-control">
                                <option value="">All Categories</option>
                                <?php foreach ($orderedCategories as $category): ?>
                                    <option value="<?php echo $category; ?>" <?php echo $categoryFilter === $category ? 'selected' : ''; ?>>
                                        <?php echo $category; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter-stock">Stock Level</label>
                            <select id="filter-stock" name="stock" class="filter-control">
                                <option value="">All Stock Levels</option>
                                <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>Low Stock
                                    (Less than 5)</option>
                                <option value="medium" <?php echo $stockFilter === 'medium' ? 'selected' : ''; ?>>Medium
                                    Stock (5 to 9)</option>
                                <option value="healthy" <?php echo $stockFilter === 'healthy' ? 'selected' : ''; ?>>
                                    Healthy Stock (10 or more)</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter-search">Search Product</label>
                            <input type="text" id="filter-search" name="search" class="filter-control"
                                placeholder="Product name..." value="<?php echo htmlspecialchars($searchFilter); ?>">
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">
                                <i class="fa-solid fa-filter"></i> Apply Filters
                            </button>
                            <a href="admin_products.php" class="btn-filter-clear" title="Clear filters">
                                <i class="fa-solid fa-rotate"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (empty($allProducts)): ?>
                <div class="no-products">
                    <h3>No products found</h3>
                    <p><?php echo $hasActiveFilter ? 'No products match your current filters. Try adjusting them or click Clear.' : 'Start adding products using the Add Product button above.'; ?>
                    </p>
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
                                            <div class="product-stock <?php echo $stockClass; ?>">Stock:
                                                <?php echo $product['quantity']; ?></div>
                                        </div>
                                        <form action="admin_products.php" method="POST" class="stock-form">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" min="0"
                                                class="stock-input" required>
                                            <button type="submit" name="update_stock" class="stock-btn">
                                                <i class="fa-solid fa-rotate"></i> Update
                                            </button>
                                        </form>
                                        <div class="product-actions">
                                            <form action="admin_products.php" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this product?');">
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

    <!--Add Product Modal-->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fa-solid fa-plus"></i> Add New Product</h2>
                <button type="button" class="close-modal" id="closeAddProductModal" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="admin_products.php" method="POST" enctype="multipart/form-data" class="modal-form">
                <div class="form-grid">
                    <div class="form-group full-row">
                        <label for="modal-name">Product Name</label>
                        <input type="text" id="modal-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group full-row">
                        <label for="modal-description">Description</label>
                        <textarea id="modal-description" name="description" class="form-control" rows="3"
                            required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="modal-price">Price (&#8369;)</label>
                        <input type="number" id="modal-price" name="price" class="form-control" step="0.01" min="0"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="modal-quantity">Stock Quantity</label>
                        <input type="number" id="modal-quantity" name="quantity" class="form-control" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="modal-category">Category</label>
                        <select id="modal-category" name="category" class="form-control" required>
                            <?php foreach ($orderedCategories as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-row">
                        <label for="modal-image">Product Image</label>
                        <input type="file" id="modal-image" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelAddProduct">Cancel</button>
                    <button type="submit" name="add_product" class="btn-submit">
                        <i class="fa-solid fa-plus"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/admin/products.js"></script>
</body>

</html>
<?php $conn->close(); ?>