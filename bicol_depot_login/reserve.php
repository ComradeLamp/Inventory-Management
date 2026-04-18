<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['pending_action'] = [
        'page' => 'reserve.php',
        'product_id' => $_POST['product_id'] ?? null,
        'quantity' => $_POST['quantity'] ?? null
    ];
    header("Location: login.php?message=" . urlencode("Please log in to reserve products"));
    exit();
}

//Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit();
}

//Validate required parameters
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    $_SESSION['error'] = "Missing required information to process reservation.";
    header("Location: products.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

//Validate inputs
if ($product_id <= 0 || $quantity <= 0) {
    $_SESSION['error'] = "Invalid product or quantity.";
    header("Location: product.php?id=" . $product_id);
    exit();
}

//Check stock
$stockQuery = $conn->prepare("SELECT name, quantity, price FROM products WHERE id = ?");
$stockQuery->bind_param("i", $product_id);
$stockQuery->execute();
$stockResult = $stockQuery->get_result();

if ($stockResult->num_rows === 0) {
    $_SESSION['error'] = "Product not found.";
    header("Location: products.php");
    exit();
}

$product = $stockResult->fetch_assoc();
$availableStock = $product['quantity'];

if ($quantity > $availableStock) {
    $_SESSION['error'] = "Sorry, only {$availableStock} units available for this product.";
    header("Location: product.php?id=" . $product_id);
    exit();
}

//Start transaction
$conn->begin_transaction();

try {
    //Make reservation
    $insert = $conn->prepare("INSERT INTO reservations (user_id, product_id, quantity, status, reserved_at) VALUES (?, ?, ?, 'pending', NOW())");
    $insert->bind_param("iii", $user_id, $product_id, $quantity);
    
    if (!$insert->execute()) {
        throw new Exception("Failed to reserve product: " . $conn->error);
    }
    
    //Deduct quantity from product
    $update = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
    $update->bind_param("iii", $quantity, $product_id, $quantity);
    $update->execute();
    
    if ($update->affected_rows === 0) {
        throw new Exception("Product quantity update failed - stock may have changed.");
    }
    
    $conn->commit();
    
    //Set success message and redirect
    $_SESSION['message'] = "Successfully reserved {$quantity} units of {$product['name']}.";
    header("Location: reservations.php");
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
    header("Location: product.php?id=" . $product_id);
    exit();
}
$conn->close();
?>