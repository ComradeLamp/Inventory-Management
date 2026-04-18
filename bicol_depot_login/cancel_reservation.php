<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id']; //Fixed to match the session structure in reservations.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id']);
    
    //Get reservation details including product_id & quantity
    $check = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ? AND status = 'pending'");
    $check->bind_param("ii", $reservation_id, $user_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Invalid or already cancelled reservation.";
        header("Location: reservations.php");
        exit();
    }
    
    //Get reservation data including product_id & quantity
    $reservation = $result->fetch_assoc();
    $product_id = $reservation['product_id'];
    $quantity = $reservation['quantity'];
    
    //Update reservation status
    $updateReservation = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
    $updateReservation->bind_param("i", $reservation_id);
    $success = $updateReservation->execute();
    
    if (!$success) {
        $_SESSION['error'] = "Failed to cancel reservation: " . $conn->error;
        header("Location: reservations.php");
        exit();
    }
    
    //Restore product quantity
    $updateProduct = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
    $updateProduct->bind_param("ii", $quantity, $product_id);
    $productSuccess = $updateProduct->execute();
    
    if (!$productSuccess) {
        $_SESSION['error'] = "Reservation cancelled but stock not updated: " . $conn->error;
    } else {
        $_SESSION['message'] = "Reservation successfully cancelled.";
    }
    
    header("Location: reservations.php");
    exit();
} else {
    //If someone tries to access this page directly without POST data will be invalid
    $_SESSION['error'] = "Invalid request method.";
    header("Location: reservations.php");
    exit();
}

$conn->close();
?>