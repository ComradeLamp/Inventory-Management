<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

//Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    //First check if there's an image to delete or not
    $sql = "SELECT name FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $row['name']) . '.jpg';
        $imagePath = "assets/img/$imageName";
        if (file_exists($imagePath)) {
            unlink($imagePath); //Delete the image file path
        }
    }
    
    //Delete the product
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
    
    //Add product
    $sql = "INSERT INTO products (name, description, price, quantity, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdis", $name, $description, $price, $quantity, $category);
    
    if ($stmt->execute()) {
        $product_id = $conn->insert_id;
        
        //Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $name) . '.jpg';
            $targetDir = "assets/img/";
            $targetFile = $targetDir . $imageName;
            
            //Check if file is an actual image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                //Create directory if it doesn't exist
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                //Upload the file
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

//Check if query was successful
if (!$result) {
    $_SESSION['error'] = "Error fetching products: " . $conn->error;
}

//Group the products by category
$allProducts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allProducts[$row['category']][] = $row;
    }
}

//Define the display order for categories
$orderedCategories = ['Laptop', 'GPU', 'CPU', 'SSD', 'Motherboard', 'PC Case'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Products - Bits & Bytes</title>
    <link rel="stylesheet" href="assets/css/admin_styles.css" />
    <style>
        /*Additional styles for the product page*/
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }
        
        .product-image {
            height: 150px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-details {
            padding: 15px;
        }
        
        .product-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .product-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .product-stock {
            color: #7f8c8d;
        }
        
        .product-actions {
            display: flex;
            gap: 5px;
        }
        
        .product-actions button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            transition: background-color 0.3s;
        }
        
        .edit-btn {
            background-color: #3498db;
            color: white;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        
        .add-product-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-title {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn-submit {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #27ae60;
        }
        
        .stock-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .stock-input {
            width: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .stock-btn {
            background-color: #f39c12;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .category-header {
            background-color: #34495e;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            margin-top: 30px;
            margin-bottom: 15px;
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

        /*Empty products message*/
        .no-products {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin: 30px 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">Admin Panel</div>
            <nav class="nav">
                <a href="dashboard_admin.php" class="nav-item"><span>📊</span> Dashboard</a>
                <a href="admin_products.php" class="nav-item active"><span>📦</span> Products</a>
                <a href="admin_users.php" class="nav-item"><span>👥</span> Users</a>
                <a href="admin_order.php" class="nav-item"><span>📄</span> Orders</a>
                <form action="logout.php" method="post" class="nav-item logout-form">
                    <button type="submit"><span>🚪</span> Logout</button>
                </form>
            </nav>
        </aside>
        
        <main class="main">
            <header class="header">
                <h1>Product Management</h1>
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
            
            <!--Add Product Form-->
            <div class="add-product-form">
                <h2 class="form-title">Add New Product</h2>
                <form action="admin_products.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₱)</label>
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
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    </div>
                    
                    <button type="submit" name="add_product" class="btn-submit">Add Product</button>
                </form>
            </div>
            
            <?php if (empty($allProducts)): ?>
                <div class="no-products">
                    <h3>No products available</h3>
                    <p>Start adding products using the form above.</p>
                </div>
            <?php else: ?>
                <!--Product List by Category-->
                <?php foreach ($orderedCategories as $category): ?>
                    <?php if (isset($allProducts[$category]) && count($allProducts[$category]) > 0): ?>
                        <h3 class="category-header"><?php echo htmlspecialchars($category); ?></h3>
                        <div class="product-grid">
                            <?php foreach ($allProducts[$category] as $product): ?>
                                <?php
                                $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $product['name']) . '.jpg';
                                $imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : 'assets/img/placeholder.jpg';
                                ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </div>
                                    <div class="product-details">
                                        <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="product-description"><?php echo htmlspecialchars($product['description']); ?></div>
                                        <div class="product-meta">
                                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                            <div class="product-stock">Stock: <?php echo $product['quantity']; ?></div>
                                        </div>
                                        
                                        <!--Stock Update Form-->
                                        <form action="admin_products.php" method="POST" class="stock-form">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" min="0" class="stock-input" required>
                                            <button type="submit" name="update_stock" class="stock-btn">Update</button>
                                        </form>
                                        
                                        <div class="product-actions">
                                            <form action="admin_products.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="delete_product" class="delete-btn">Delete</button>
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
    
    <!--Add debug output-->
    <?php if (isset($_SESSION['debug'])): ?>
        <div class="alert alert-info">
            <?php 
                echo $_SESSION['debug']; 
                unset($_SESSION['debug']);
            ?>
        </div>
    <?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>